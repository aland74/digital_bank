<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Card;
use App\Models\Currency;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashApiController extends Controller
{
    /**
     * ATM withdrawal. Validate account, amount, and PIN.
     */
    public function cashOut(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'pin' => 'required|string|size:4',
        ]);

        $account = $request->user()->accounts()->findOrFail($request->account_id);

        // Validate amount based on currency
        $currency = Currency::where('code', $account->currency)->first();
        $symbol = $currency?->symbol ?? $account->currency;
        $decimals = $currency?->decimal_places ?? 2;

        if ($account->currency === 'IQD') {
            if ($request->amount < 10000 || $request->amount > 5000000) {
                return response()->json(['message' => 'IQD withdrawal must be between 10,000 and 5,000,000 IQD.'], 422);
            }
        } else {
            if ($request->amount < 10 || $request->amount > 5000) {
                return response()->json(['message' => 'USD withdrawal must be between $10 and $5,000.'], 422);
            }
        }

        // Verify PIN against user's active card for this account
        $card = Card::where('user_id', $request->user()->id)
            ->where('account_id', $account->id)
            ->where('status', 'active')
            ->first();

        if (!$card) {
            return response()->json(['message' => 'No active card found for this account.'], 422);
        }

        if ($card->isFrozen()) {
            return response()->json(['message' => 'Card is frozen due to too many failed PIN attempts. Please contact support.'], 422);
        }

        if (!$card->verifyPin($request->pin)) {
            $card->recordFailedPinAttempt();
            return response()->json(['message' => 'Invalid PIN.'], 422);
        }

        if ($account->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient funds.'], 422);
        }

        DB::transaction(function () use ($account, $request, $currency, $symbol, $decimals) {
            $balanceBefore = $account->balance;
            $account->decrement('balance', $request->amount);
            $account->decrement('available_balance', $request->amount);

            Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'currency' => $account->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->fresh()->balance,
                'status' => 'completed',
                'description' => 'ATM Cash Withdrawal',
                'channel' => 'atm',
                'completed_at' => now(),
            ]);

            Notification::create([
                'user_id' => $account->user_id,
                'title' => 'Cash Withdrawal Successful',
                'message' => "You have successfully withdrawn {$symbol} " . number_format($request->amount, $decimals) . " from your account.",
                'type' => 'transaction',
                'icon' => '🏧',
            ]);
        });

        return response()->json([
            'message' => 'Withdrawal successful! Please take your cash.',
            'amount' => $request->amount,
            'currency' => $account->currency,
        ]);
    }
}
