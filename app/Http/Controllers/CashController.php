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

    /**
     * Admin: Show Cash Management Panel (Teller)
     * Supports user search by name, email, account number, or national ID.
     */
    public function adminIndex(Request $request)
    {
        $query = User::where('role', 'customer')->with('accounts');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('accounts', function ($aq) use ($search) {
                      $aq->where('account_number', 'like', "%{$search}%");
                  });
            });
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.cash', compact('users'));
    }

    /**
     * AJAX: Get accounts for a specific user (for branch cash forms).
     */
    public function getUserAccounts(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $accounts = Account::where('user_id', $request->user_id)
            ->where('status', 'active')
            ->get()
            ->map(function ($acc) {
                $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                $sym = $cur?->symbol ?? $acc->currency;
                $dec = $cur?->decimal_places ?? 2;
                return [
                    'id' => $acc->id,
                    'text' => ucfirst($acc->account_type) . ' — ' . $acc->account_number . ' (' . $sym . ' ' . number_format($acc->available_balance, $dec) . ')',
                ];
            });

        return response()->json($accounts);
    }

    /**
     * Admin: Process Branch Deposit (Physical to Digital)
     */
    public function adminDeposit(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1'
        ]);

        $account = Account::where('user_id', $request->user_id)
            ->findOrFail($request->account_id);

        DB::transaction(function () use ($account, $request) {
            $balanceBefore = $account->balance;
            $account->increment('balance', $request->amount);
            $account->increment('available_balance', $request->amount);

            Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => 'deposit',
                'amount' => $request->amount,
                'currency' => $account->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->fresh()->balance,
                'status' => 'completed',
                'description' => 'Branch Cash Deposit',
                'channel' => 'branch',
                'completed_at' => now(),
            ]);

            $currency = Currency::where('code', $account->currency)->first();
            $symbol = $currency?->symbol ?? $account->currency;
            $decimals = $currency?->decimal_places ?? 2;

            Notification::create([
                'user_id' => $account->user_id,
                'title' => 'Cash Deposit Received 🏦',
                'message' => "A cash deposit of {$symbol} " . number_format($request->amount, $decimals) . " has been added to your account.",
                'type' => 'success',
                'icon' => '🏦',
                'action_url' => route('transactions.index'),
            ]);
        });

        return back()->with('success', 'Cash deposit processed successfully.');
    }

    /**
     * Admin: Process Branch Withdrawal (Digital to Physical)
     */
    public function adminWithdraw(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1'
        ]);

        $account = Account::where('user_id', $request->user_id)
            ->findOrFail($request->account_id);

        if ($account->balance < $request->amount) {
            return back()->withErrors(['amount' => 'Insufficient funds for this withdrawal.']);
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
                'description' => 'Branch Cash Withdrawal',
                'channel' => 'branch',
                'completed_at' => now(),
            ]);

            $currency = Currency::where('code', $account->currency)->first();
            $symbol = $currency?->symbol ?? $account->currency;
            $decimals = $currency?->decimal_places ?? 2;

            Notification::create([
                'user_id' => $account->user_id,
                'title' => 'Branch Cash Withdrawal 🏦',
                'message' => "A cash withdrawal of {$symbol} " . number_format($request->amount, $decimals) . " was processed at the branch.",
                'type' => 'transaction',
                'icon' => '🏦',
                'action_url' => route('transactions.index'),
            ]);
        });

        return back()->with('success', 'Cash withdrawal processed successfully. Hand cash to customer.');
    }
}
