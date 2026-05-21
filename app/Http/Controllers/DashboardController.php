<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Currency;
use App\Models\Loan;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accounts = $user->accounts()->active()->get();

        // Get view currency from session, query param, or default to USD
        $viewCurrencyCode = $request->query('currency')
            ?? session('view_currency')
            ?? 'USD';

        $viewCurrency = Currency::where('code', $viewCurrencyCode)->first()
            ?? Currency::where('code', 'USD')->first()
            ?? new \App\Models\Currency(['code' => 'USD', 'symbol' => '$', 'decimal_places' => 2]);

        // Persist selection in session
        session(['view_currency' => $viewCurrency->code]);

        $rate = ExchangeRateService::getRate(); // IQD per 1 USD

        // Helper: convert any amount to the view currency
        $toViewCurrency = function (float $amount, string $fromCurrency) use ($viewCurrency, $rate) {
            if ($fromCurrency === $viewCurrency->code) return $amount;
            if ($fromCurrency === 'USD' && $viewCurrency->code === 'IQD') return round($amount * $rate, 0);
            if ($fromCurrency === 'IQD' && $viewCurrency->code === 'USD') return round($amount / $rate, 2);
            return $amount;
        };

        // Balance per currency
        $usdAccounts = $accounts->where('currency', 'USD');
        $iqdAccounts = $accounts->where('currency', 'IQD');
        $usdBalance = $usdAccounts->sum('balance');
        $iqdBalance = $iqdAccounts->sum('balance');
        $usdAvailable = $usdAccounts->sum('available_balance');
        $iqdAvailable = $iqdAccounts->sum('available_balance');

        // Total balance in view currency
        $totalBalance = $toViewCurrency($usdBalance, 'USD') + $toViewCurrency($iqdBalance, 'IQD');

        $recentTransactions = Transaction::whereIn('account_id', $accounts->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Monthly income/expense for chart (convert to view currency)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $incomeRaw = Transaction::whereIn('account_id', $accounts->pluck('id'))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereIn('type', ['deposit', 'transfer_in', 'refund', 'interest'])
                ->where('status', 'completed')
                ->selectRaw('SUM(amount) as total, currency')
                ->groupBy('currency')
                ->get();

            $expenseRaw = Transaction::whereIn('account_id', $accounts->pluck('id'))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereIn('type', ['withdrawal', 'transfer_out', 'payment', 'fee'])
                ->where('status', 'completed')
                ->selectRaw('SUM(amount) as total, currency')
                ->groupBy('currency')
                ->get();

            $income = $incomeRaw->sum(fn ($row) => $toViewCurrency($row->total, $row->currency));
            $expense = $expenseRaw->sum(fn ($row) => $toViewCurrency($row->total, $row->currency));

            $monthlyData[] = [
                'month' => $date->format('M'),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
            ];
        }

        // Spending categories (convert to view currency)
        $categoryData = [];
        $categoryColors = [
            'transfer_out' => '#3b82f6',
            'payment' => '#8b5cf6',
            'withdrawal' => '#ef4444',
            'fee' => '#f97316',
        ];
        $categoryLabels = [
            'transfer_out' => __('Transfers'),
            'payment' => __('Payments'),
            'withdrawal' => __('Withdrawals'),
            'fee' => __('Fees'),
        ];
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        foreach (['transfer_out', 'payment', 'withdrawal', 'fee'] as $type) {
            $amountRaw = Transaction::whereIn('account_id', $accounts->pluck('id'))
                ->whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])
                ->where('type', $type)
                ->where('status', 'completed')
                ->selectRaw('SUM(amount) as total, currency')
                ->groupBy('currency')
                ->get();
            $amount = $amountRaw->sum(fn ($row) => $toViewCurrency($row->total, $row->currency));
            if ($amount > 0) {
                $categoryData[] = [
                    'label' => $categoryLabels[$type],
                    'value' => round($amount, 2),
                    'color' => $categoryColors[$type],
                ];
            }
        }
        if (empty($categoryData)) {
            $categoryData = [['label' => __('Transfers'), 'value' => 0, 'color' => '#3b82f6']];
        }

        $activeLoans = $user->loans()->active()->count();
        $totalCards = $user->cards()->active()->count();
        $unreadNotifications = $user->unreadNotificationsCount();

        // Exchange rate info
        $exchangeRate = ExchangeRateService::getRateInfo();

        // Available currencies for switcher
        $currencies = Currency::active()->get();
        $primaryCurrency = $viewCurrency;

        return view('dashboard', compact(
            'accounts', 'totalBalance', 'recentTransactions',
            'monthlyData', 'activeLoans', 'totalCards', 'unreadNotifications',
            'primaryCurrency', 'exchangeRate', 'viewCurrency', 'currencies',
            'usdBalance', 'iqdBalance', 'usdAvailable', 'iqdAvailable',
            'categoryData', 'toViewCurrency'
        ));
    }
}
