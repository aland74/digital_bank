<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSetting;
use App\Models\AuditLog;
use App\Services\DistributedDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        $reserveHealth = BankSetting::reserveHealth();

        // Financial overview from HQ
        $hqConnection = DistributedDatabaseService::getHqConnection();

        $totalHeldFunds = DB::connection($hqConnection)->table('accounts')
            ->where('status', 'active')->whereNull('deleted_at')
            ->sum('hold_amount');

        $totalAvailableBalance = DB::connection($hqConnection)->table('accounts')
            ->where('status', 'active')->whereNull('deleted_at')
            ->sum('available_balance');

        $totalAccounts = DB::connection($hqConnection)->table('accounts')
            ->whereNull('deleted_at')->count();

        $activeAccounts = DB::connection($hqConnection)->table('accounts')
            ->where('status', 'active')->whereNull('deleted_at')->count();

        $totalCards = DB::connection($hqConnection)->table('cards')->whereNull('deleted_at')->count();
        $activeCards = DB::connection($hqConnection)->table('cards')->where('status', 'active')->count();
        $frozenCards = DB::connection($hqConnection)->table('cards')->where('status', 'frozen')->count();

        $pendingTransfers = DB::connection($hqConnection)->table('pending_transfers')
            ->where('status', 'pending')->where('expires_at', '>', now())->count();

        $totalLoans = DB::connection($hqConnection)->table('loans')->whereNull('deleted_at')->count();
        $pendingLoans = DB::connection($hqConnection)->table('loans')->where('status', 'pending')->count();
        $totalLoanAmount = DB::connection($hqConnection)->table('loans')
            ->whereIn('status', ['approved', 'disbursed'])->whereNull('deleted_at')
            ->sum('amount');

        $totalUsers = DB::connection($hqConnection)->table('users')->whereNull('deleted_at')->count();

        $financialOverview = compact(
            'totalHeldFunds', 'totalAvailableBalance', 'totalAccounts', 'activeAccounts',
            'totalCards', 'activeCards', 'frozenCards', 'pendingTransfers',
            'totalLoans', 'pendingLoans', 'totalLoanAmount', 'totalUsers'
        );

        $settings = [
            'loan_reserve_minimum' => BankSetting::get('loan_reserve_minimum', 50000),
            'transfer_expiry_hours' => BankSetting::get('transfer_expiry_hours', 48),
            'max_pin_attempts' => BankSetting::get('max_pin_attempts', 3),
            'bank_name' => BankSetting::get('bank_name', 'Distributed Bank'),
            'support_email' => BankSetting::get('support_email', 'support@distributedbank.com'),
            'default_currency' => BankSetting::get('default_currency', 'USD'),
            'daily_transfer_limit' => BankSetting::get('daily_transfer_limit', 10000),
            'min_transfer_amount' => BankSetting::get('min_transfer_amount', 1),
            'max_transfer_amount' => BankSetting::get('max_transfer_amount', 50000),
        ];

        // Loan capacity
        $loanCapacity = max(0, $reserveHealth['total_deposits'] - $reserveHealth['minimum'] - $financialOverview['totalLoanAmount']);

        // IQD exchange rate
        $iqdRate = \App\Models\Currency::where('code', 'IQD')->value('exchange_rate') ?? 1310;

        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('admin.settings', compact('reserveHealth', 'settings', 'financialOverview', 'loanCapacity', 'iqdRate', 'isSuperAdmin'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'loan_reserve_minimum' => 'required|numeric|min:0',
            'transfer_expiry_hours' => 'required|integer|min:1|max:168',
            'max_pin_attempts' => 'required|integer|min:1|max:10',
            'bank_name' => 'required|string|max:100',
            'support_email' => 'required|email|max:255',
            'default_currency' => 'required|string|size:3',
            'daily_transfer_limit' => 'required|numeric|min:0',
            'min_transfer_amount' => 'required|numeric|min:0',
            'max_transfer_amount' => 'required|numeric|min:0',
        ]);

        foreach ($validated as $key => $value) {
            BankSetting::set($key, $value);
        }

        AuditLog::log('admin_settings_updated', [
            'new_values' => $validated,
            'severity' => 'high',
        ]);

        return back()->with('success', 'Bank settings updated successfully.');
    }
}
