<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashController extends Controller
{
    /**
     * Show branch cash management page for admin/teller.
     */
    public function index(Request $request)
    {
        $branch = auth()->user()->branch;

        // Get all accounts for the admin's branch
        $accounts = Account::whereHas('user', function ($q) use ($branch) {
            $q->where('branch', $branch)->where('role', 'customer');
        })->with('user')->get();

        $todayTransactions = Transaction::whereHas('account.user', function ($q) use ($branch) {
            $q->where('branch', $branch);
        })
            ->whereDate('created_at', today())
            ->whereIn('type', ['deposit', 'withdrawal'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'deposits_today' => $todayTransactions->where('type', 'deposit')->sum('amount'),
            'withdrawals_today' => $todayTransactions->where('type', 'withdrawal')->sum('amount'),
            'total_transactions' => $todayTransactions->count(),
        ];

        return view('admin.cash.index', compact('accounts', 'todayTransactions', 'stats'));
    }

    /**
     * Process a teller deposit (cash in).
     */
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1|max:100000',
            'description' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($validated['account_id']);

        // Verify account belongs to admin's branch
        if ($account->user->branch !== auth()->user()->branch) {
            return back()->with('error', 'Account does not belong to your branch.');
        }

        DB::transaction(function () use ($account, $validated) {
            $balanceBefore = $account->balance;

            $account->increment('balance', $validated['amount']);
            $account->increment('available_balance', $validated['amount']);

            Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => 'deposit',
                'amount' => $validated['amount'],
                'currency' => $account->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->fresh()->balance,
                'status' => 'completed',
                'description' => $validated['description'] ?? 'Teller Deposit',
                'channel' => 'branch',
                'completed_at' => now(),
            ]);

            Notification::create([
                'user_id' => $account->user_id,
                'title' => 'Deposit Received',
                'message' => "A deposit of \${$validated['amount']} was made to your account {$account->account_number}.",
                'type' => 'success',
                'icon' => '💰',
            ]);

            AuditLog::log('teller_deposit', [
                'model_type' => 'Account',
                'model_id' => $account->id,
                'new_values' => ['amount' => $validated['amount']],
                'severity' => 'high',
            ]);
        });

        return back()->with('success', "Deposit of \${$validated['amount']} processed successfully.");
    }

    /**
     * Process a teller withdrawal (cash out).
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1|max:100000',
            'description' => 'nullable|string|max:255',
        ]);

        $account = Account::findOrFail($validated['account_id']);

        // Verify account belongs to admin's branch
        if ($account->user->branch !== auth()->user()->branch) {
            return back()->with('error', 'Account does not belong to your branch.');
        }

        if ($account->balance < $validated['amount']) {
            return back()->with('error', 'Insufficient account balance.');
        }

        DB::transaction(function () use ($account, $validated) {
            $balanceBefore = $account->balance;

            $account->decrement('balance', $validated['amount']);
            $account->decrement('available_balance', $validated['amount']);

            Transaction::create([
                'account_id' => $account->id,
                'reference_number' => Transaction::generateReference(),
                'type' => 'withdrawal',
                'amount' => $validated['amount'],
                'currency' => $account->currency,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->fresh()->balance,
                'status' => 'completed',
                'description' => $validated['description'] ?? 'Teller Withdrawal',
                'channel' => 'branch',
                'completed_at' => now(),
            ]);

            Notification::create([
                'user_id' => $account->user_id,
                'title' => 'Withdrawal Processed',
                'message' => "A withdrawal of \${$validated['amount']} was processed from your account {$account->account_number}.",
                'type' => 'info',
                'icon' => '💸',
            ]);

            AuditLog::log('teller_withdrawal', [
                'model_type' => 'Account',
                'model_id' => $account->id,
                'new_values' => ['amount' => $validated['amount']],
                'severity' => 'high',
            ]);
        });

        return back()->with('success', "Withdrawal of \${$validated['amount']} processed successfully.");
    }
}
