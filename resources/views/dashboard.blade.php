@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', __('Dashboard'))
@section('page-subtitle', __('Welcome back') . ', ' . auth()->user()->name . ' · ' . __('Branch') . ': ' . auth()->user()->branch_display_name)

@php
    $currencySymbol = $viewCurrency->symbol ?? '$';
    $currencyDecimals = $viewCurrency->decimal_places ?? 2;
    $currencyCode = $viewCurrency->code ?? 'USD';
@endphp

<script>window.__currencySymbol = '{{ $currencySymbol }}';</script>

@section('content')
{{-- Currency Switcher + Exchange Rate --}}
<div class="card card-body p-4 mb-4 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(168, 85, 247, 0.1));">
    <div class="flex-between">
        <div class="flex align-items-center flex-gap-2">
            <span style="font-size: 20px;">💱</span>
            <div>
                <div style="font-size: 14px; font-weight: 600; color: var(--text-primary);">{{ __('Live Exchange Rate') }}</div>
                <div class="text-muted text-xs">{{ $exchangeRate['formatted'] }}</div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="text-right">
                <div style="font-size: 13px; color: var(--info); font-weight: 500;">{{ $exchangeRate['inverse_formatted'] }}</div>
                <div class="text-muted text-xs">{{ __('Updated') }}: {{ $exchangeRate['updated_at'] }}</div>
            </div>
            <div style="display:flex;background:var(--bg-secondary);border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);">
                @foreach($currencies as $cur)
                    <a href="?currency={{ $cur->code }}" style="padding:6px 14px;font-size:12px;font-weight:600;text-decoration:none;transition:all 0.2s;{{ $cur->code === $currencyCode ? 'background:var(--primary);color:white;' : 'color:var(--text-muted);' }}">
                        {{ $cur->symbol }} {{ $cur->code }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Balance Cards --}}
<div class="stats-grid animate-fade-in-up">
    <div class="stat-card">
        <div class="stat-icon cyan">💰</div>
        <div class="stat-value" data-count-to="{{ $totalBalance }}" data-prefix="{{ $currencySymbol }} " data-decimals="{{ $currencyDecimals }}">{{ $currencySymbol }}0</div>
        <div class="stat-label">{{ __('Total Balance') }} ({{ $currencyCode }})</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📥</div>
        <div class="stat-value" data-count-to="{{ $monthlyData[5]['income'] ?? 0 }}" data-prefix="{{ $currencySymbol }} " data-decimals="{{ $currencyDecimals }}">{{ $currencySymbol }}0</div>
        <div class="stat-label">{{ __('Monthly Income') }}</div>
        @php
            $prevIncome = $monthlyData[4]['income'] ?? 0;
            $curIncome = $monthlyData[5]['income'] ?? 0;
            $incomeChange = $prevIncome > 0 ? round((($curIncome - $prevIncome) / $prevIncome) * 100, 1) : 0;
        @endphp
        @if($incomeChange != 0)
            <span class="stat-change {{ $incomeChange > 0 ? 'positive' : 'negative' }}">
                {{ $incomeChange > 0 ? '↑' : '↓' }} {{ abs($incomeChange) }}%
            </span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-icon red">📤</div>
        <div class="stat-value" data-count-to="{{ $monthlyData[5]['expense'] ?? 0 }}" data-prefix="{{ $currencySymbol }} " data-decimals="{{ $currencyDecimals }}">{{ $currencySymbol }}0</div>
        <div class="stat-label">{{ __('Monthly Expenses') }}</div>
        @php
            $prevExpense = $monthlyData[4]['expense'] ?? 0;
            $curExpense = $monthlyData[5]['expense'] ?? 0;
            $expenseChange = $prevExpense > 0 ? round((($curExpense - $prevExpense) / $prevExpense) * 100, 1) : 0;
        @endphp
        @if($expenseChange != 0)
            <span class="stat-change {{ $expenseChange > 0 ? 'negative' : 'positive' }}">
                {{ $expenseChange > 0 ? '↑' : '↓' }} {{ abs($expenseChange) }}%
            </span>
        @endif
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">💳</div>
        <div class="stat-value">{{ $totalCards }}</div>
        <div class="stat-label">{{ __('Active Cards') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📈</div>
        <div class="stat-value">{{ $activeLoans }}</div>
        <div class="stat-label">{{ __('Active Loans') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🏦</div>
        <div class="stat-value">{{ $accounts->count() }}</div>
        <div class="stat-label">{{ __('Active Accounts') }}</div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="card card-body p-6 mb-6 animate-fade-in-up delay-100">
    <div class="section-header">
        <h2 class="section-title">{{ __('Quick Actions') }}</h2>
    </div>
    <div class="quick-actions">
        <a href="{{ route('transfers.create') }}" class="quick-action quick-action-blue" data-tooltip="{{ __('Send money to anyone') }}">
            <div class="quick-action-icon">💸</div>
            <span class="quick-action-label">{{ __('Send Money') }}</span>
        </a>
        <a href="{{ route('cash.index') }}" class="quick-action quick-action-green" data-tooltip="{{ __('Withdraw physical cash') }}">
            <div class="quick-action-icon">🏧</div>
            <span class="quick-action-label">{{ __('Cash Out') }}</span>
        </a>
        <a href="{{ route('cards.index') }}" class="quick-action quick-action-purple" data-tooltip="{{ __('Manage your active cards') }}">
            <div class="quick-action-icon">💳</div>
            <span class="quick-action-label">{{ __('My Cards') }}</span>
        </a>
        <a href="{{ route('transfers.convert') }}" class="quick-action quick-action-orange" data-tooltip="{{ __('Convert between USD and IQD') }}">
            <div class="quick-action-icon">🔄</div>
            <span class="quick-action-label">{{ __('Convert Currency') }}</span>
        </a>
        <a href="{{ route('transactions.index') }}" class="quick-action quick-action-blue" data-tooltip="{{ __('View transaction history') }}">
            <div class="quick-action-icon">📋</div>
            <span class="quick-action-label">{{ __('Transactions') }}</span>
        </a>
    </div>
</div>

<div class="grid-2 grid-align-start">
    {{-- Income vs Expenses Chart --}}
    <div class="card card-body p-6 animate-fade-in-up delay-200">
        <div class="section-header">
            <h2 class="section-title">{{ __('Income vs Expenses') }}</h2>
            <span class="text-sm text-muted">{{ __('Last 6 months') }} · {{ $currencyCode }}</span>
        </div>
        <canvas data-chart='@json($monthlyData)' style="width:100%;height:260px;"></canvas>
    </div>

    {{-- Spending Categories --}}
    <div class="card card-body p-6 animate-fade-in-up delay-300">
        <div class="section-header">
            <h2 class="section-title">{{ __('Spending Categories') }}</h2>
            <span class="text-sm text-muted">{{ __('This month') }} · {{ $currencyCode }}</span>
        </div>
        <div class="donut-chart-container flex-column">
            <canvas data-donut='@json($categoryData)' style="width:200px;height:200px;"></canvas>
            <div class="donut-center-text">
                <div class="donut-center-value">{{ $currencySymbol }} {{ number_format(collect($categoryData)->sum('value'), 0) }}</div>
                <div class="donut-center-label">{{ __('Total Spent') }}</div>
            </div>
            <div class="donut-legend flex-justify-center"></div>
        </div>
    </div>
</div>

{{-- My Cards & Accounts --}}
<div class="card card-body p-6 mt-6 animate-fade-in-up delay-300">
    <div class="section-header">
        <h2 class="section-title">{{ __('My Cards & Accounts') }}</h2>
        <a href="{{ route('cards.index') }}" class="btn btn-ghost btn-sm">{{ __('View All →') }}</a>
    </div>
    <div class="grid-fill-280">
        @forelse($accounts as $account)
            <a href="{{ route('accounts.show', $account) }}" class="account-card">
                <div class="flex-between" style="margin-bottom:12px;">
                    <span class="account-type-badge {{ $account->account_type }}">
                        {{ __(ucfirst($account->account_type)) }}
                    </span>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <span class="badge badge-{{ $account->currency === 'USD' ? 'info' : 'warning' }}" style="font-size:10px;">{{ $account->currency }}</span>
                        @if($account->is_primary)
                            <span class="badge badge-info">{{ __('Primary') }}</span>
                        @endif
                    </div>
                </div>
                <div class="account-balance">{{ $account->formatted_balance }}</div>
                <div class="account-number">
                    <span>{{ $account->account_number }}</span>
                    <button type="button" class="btn btn-ghost btn-sm btn-copy-mini" onclick="event.preventDefault(); event.stopPropagation(); copyToClipboard('{{ $account->account_number }}')" title="{{ __('Copy Account Number') }}">
                        📋
                    </button>
                </div>
            </a>
        @empty
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-state-icon">💳</div>
                <p class="empty-state-title">{{ __('No cards or accounts yet') }}</p>
                <a href="{{ route('cards.create') }}" class="btn btn-primary btn-sm">{{ __('Create Card & Account') }}</a>
            </div>
        @endforelse
    </div>
</div>

{{-- Recent Transactions --}}
<div class="card mt-6 animate-fade-in-up delay-400">
    <div class="section-header p-6" style="margin-bottom:0;">
        <h2 class="section-title">{{ __('Recent Transactions') }}</h2>
        <a href="{{ route('transactions.index') }}" class="btn btn-ghost btn-sm">{{ __('View All →') }}</a>
    </div>
    @forelse($recentTransactions as $txn)
        <a href="{{ route('transactions.show', $txn) }}" class="transaction-item">
            <div class="transaction-icon {{ $txn->isCredit() ? 'credit' : 'debit' }}">
                {{ $txn->isCredit() ? '↓' : '↑' }}
            </div>
            <div class="transaction-details">
                <div class="transaction-title">{{ $txn->description ?: __(ucfirst(str_replace('_', ' ', $txn->type))) }}</div>
                <div class="transaction-meta">{{ $txn->created_at->format('M d, Y · h:i A') }} · {{ $txn->reference_number }}</div>
            </div>
            <div class="transaction-amount {{ $txn->isCredit() ? 'credit' : 'debit' }}">
                {{ $txn->formatted_amount }}
            </div>
            <span class="badge badge-{{ $txn->status === 'completed' ? 'success' : ($txn->status === 'pending' ? 'warning' : 'danger') }}">
                {{ __(ucfirst($txn->status)) }}
            </span>
        </a>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <p class="empty-state-title">{{ __('No transactions yet') }}</p>
            <p class="empty-state-text">{{ __('Your transaction history will appear here.') }}</p>
        </div>
    @endforelse
</div>
@endsection
