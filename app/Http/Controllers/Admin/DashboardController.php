<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\Card;
use App\Models\SupportTicket;
use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\BankSetting;
use App\Models\PinChangeRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $reserveHealth = BankSetting::reserveHealth();

        $stats = [
            'total_users' => User::customers()->count(),
            'active_users' => User::customers()->active()->count(),
            'new_users_week' => User::customers()->where('created_at', '>=', now()->subWeek())->count(),
            'total_accounts' => Account::count(),
            'total_balance' => Account::active()->sum('balance'),
            'total_cards' => Card::count(),
            'active_cards' => Card::where('status', 'active')->count(),
            'transactions_today' => Transaction::whereDate('created_at', today())->count(),
            'transactions_week' => Transaction::where('created_at', '>=', now()->subWeek())->count(),
            'volume_today' => Transaction::whereDate('created_at', today())->sum('amount'),
            'pending_loans' => Loan::pending()->count(),
            'active_loans' => Loan::where('status', 'approved')->count(),
            'total_loan_outstanding' => Loan::where('status', 'approved')->sum('remaining_balance'),
            'open_tickets' => SupportTicket::open()->count(),
            'pending_kyc' => KycDocument::pending()->count(),
            'pending_pin_requests' => PinChangeRequest::pending()->count(),
        ];

        $recentTransactions = Transaction::with('account.user')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $recentAuditLogs = auth()->user()->isSuperAdmin()
            ? AuditLog::with('user')->orderBy('created_at', 'desc')->limit(10)->get()
            : collect();

        $recentUsers = User::customers()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $pendingLoans = Loan::pending()->with('user')->orderBy('applied_at')->limit(5)->get();

        // IQD exchange rate and loan capacity
        $iqdRate = \App\Models\Currency::where('code', 'IQD')->value('exchange_rate') ?? 1310;
        $totalLoanOutstanding = Loan::whereIn('status', ['approved', 'disbursed'])->sum('amount');
        $loanCapacity = max(0, $reserveHealth['total_deposits'] - $reserveHealth['minimum'] - $totalLoanOutstanding);

        return view('admin.dashboard', compact('stats', 'recentTransactions', 'recentAuditLogs', 'reserveHealth', 'recentUsers', 'pendingLoans', 'iqdRate', 'loanCapacity'));
    }
}
