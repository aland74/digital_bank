<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashController extends Controller
{
    /**
     * User: Show Cash Out / ATM Simulator page
     */
    public function index(Request $request)
    {
        $accounts = $request->user()->accounts()->where('status', 'active')->get();
        return view('cash.index', compact('accounts'));
    }

    /**
     * User: Process Cash Out (Digital to Physical)
     */
    public function cashOut(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:10|max:5000',
            'pin' => 'required|string|size:4'
        ]);

        $account = $request->user()->accounts()->findOrFail($request->account_id);

        if ($account->balance < $request->amount) {
            return back()->withErrors(['amount' => 'Insufficient funds.']);
        }

        DB::transaction(function () use ($account, $request) {
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

            $currency = Currency::where('code', $account->currency)->first();
            $symbol = $currency?->symbol ?? $account->currency;
            $decimals = $currency?->decimal_places ?? 2;

            Notification::create([
                'user_id' => $account->user_id,
                'title' => 'Cash Withdrawal Successful 🏧',
                'message' => "You have successfully withdrawn {$symbol} " . number_format($request->amount, $decimals) . " from your account.",
                'type' => 'transaction',
                'icon' => '🏧',
                'action_url' => route('transactions.index'),
            ]);
        });

        return back()->with('success', 'Withdrawal successful! Please take your cash.');
}
