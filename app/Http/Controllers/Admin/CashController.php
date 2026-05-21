<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
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

        // Search customers
        $users = User::where('branch', $branch)
            ->where('role', 'customer')
            ->with('accounts')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhereHas('accounts', function ($aq) use ($search) {
                            $aq->where('account_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Today's stats for this branch
        $todayTransactions = Transaction::whereHas('account.user', function ($q) use ($branch) {
            $q->where('branch', $branch);
        })
            ->whereDate('created_at', today())
            ->whereIn('type', ['deposit', 'withdrawal'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'deposits_usd' => $todayTransactions->where('type', 'deposit')->where('currency', 'USD')->sum('amount'),
            'deposits_iqd' => $todayTransactions->where('type', 'deposit')->where('currency', 'IQD')->sum('amount'),
            'withdrawals_usd' => $todayTransactions->where('type', 'withdrawal')->where('currency', 'USD')->sum('amount'),
            'withdrawals_iqd' => $todayTransactions->where('type', 'withdrawal')->where('currency', 'IQD')->sum('amount'),
            'total_transactions' => $todayTransactions->count(),
        ];

        // Recent transactions (last 20)
        $recentTransactions = Transaction::whereHas('account.user', function ($q) use ($branch) {
            $q->where('branch', $branch);
        })
            ->where('channel', 'branch')
            ->with(['account.user'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.cash', compact('users', 'stats', 'recentTransactions'));
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
                $cur = Currency::where('code', $acc->currency)->first();
                $sym = $cur?->symbol ?? $acc->currency;
                $dec = $cur?->decimal_places ?? 2;
                return [
                    'id' => $acc->id,
                    'currency' => $acc->currency,
                    'symbol' => $sym,
                    'decimals' => $dec,
                    'text' => ucfirst($acc->account_type) . ' — ' . $acc->account_number . ' (' . $sym . ' ' . number_format($acc->available_balance, $dec) . ')',
                ];
            });

        return response()->json($accounts);
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

        $currency = Currency::where('code', $account->currency)->first();
        $symbol = $currency ? $currency->symbol : '$';

        DB::transaction(function () use ($account, $validated, $symbol) {
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
                'message' => "A deposit of {$symbol}{$validated['amount']} was made to your account {$account->account_number}.",
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

        return back()->with('success', "Deposit of {$symbol}{$validated['amount']} processed successfully.");
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

        $currency = Currency::where('code', $account->currency)->first();
        $symbol = $currency ? $currency->symbol : '$';

        DB::transaction(function () use ($account, $validated, $symbol) {
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
                'message' => "A withdrawal of {$symbol}{$validated['amount']} was processed from your account {$account->account_number}.",
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

        return back()->with('success', "Withdrawal of {$symbol}{$validated['amount']} processed successfully.");
    }
}
