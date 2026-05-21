@extends('layouts.app')
@section('title', __('Dashboard'))
@section('page-title', '🏠 ' . __('Dashboard'))
@section('page-subtitle', __('Welcome back') . ', ' . auth()->user()->name . ' · ' . __('Branch') . ': ' . auth()->user()->branch_display_name)

@section('content')

{{-- Welcome & Exchange Rate --}}
<div class="card p-4 mb-6 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(139, 92, 246, 0.08));">
    <div class="flex-between flex-wrap" style="gap: 16px;">
        <div>
            <div style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                {{ __('Good') }} {{ now()->hour < 12 ? __('Morning') : (now()->hour < 18 ? __('Afternoon') : __('Evening')) }}, {{ Str::before(auth()->user()->name, ' ') }} 👋
            </div>
            <div class="text-sm text-muted">{{ __('Here\'s your financial overview for today.') }}</div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--bg-primary); border-radius: var(--radius-md); border: 1px solid var(--border);">
            <span style="font-size: 24px;">💱</span>
            <div>
                <div class="text-xs text-muted">{{ __('Live Exchange Rate') }}</div>
                <div class="font-semibold text-sm">{{ $exchangeRate['formatted'] }}</div>
                <div class="text-xs text-muted">{{ $exchangeRate['inverse_formatted'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Balance Cards --}}
<div class="stats-grid mb-6 animate-fade-in-up">
    {{-- USD Balance --}}
    <div class="stat-card" style="border-left: 4px solid #3b82f6; background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), transparent);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="text-xs text-muted" style="margin-bottom: 4px;">🇺🇸 {{ __('US Dollar') }}</div>
                <div class="stat-value" style="color: #3b82f6;" data-count-to="{{ $usdBalance }}" data-prefix="$ " data-decimals="2">$0</div>
                <div class="text-xs text-muted" style="margin-top: 4px;">{{ __('Available') }}: ${{ number_format($usdAvailable, 2) }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; font-size: 20px;">💵</div>
        </div>
    </div>

    {{-- IQD Balance --}}
    <div class="stat-card" style="border-left: 4px solid #f59e0b; background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), transparent);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="text-xs text-muted" style="margin-bottom: 4px;">🇮🇶 {{ __('Iraqi Dinar') }}</div>
                <div class="stat-value" style="color: #f59e0b;" data-count-to="{{ $iqdBalance }}" data-prefix="د.ع " data-decimals="0">د.ع0</div>
                <div class="text-xs text-muted" style="margin-top: 4px;">{{ __('Available') }}: د.ع{{ number_format($iqdAvailable, 0) }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center; font-size: 20px;">💰</div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="text-xs text-muted" style="margin-bottom: 4px;">🏦 {{ __('Accounts') }}</div>
                <div class="stat-value">{{ $accounts->count() }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(139, 92, 246, 0.1); display: flex; align-items: center; justify-content: center; font-size: 20px;">🏦</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #22c55e;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="text-xs text-muted" style="margin-bottom: 4px;">💳 {{ __('Cards') }}</div>
                <div class="stat-value">{{ $totalCards }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(34, 197, 94, 0.1); display: flex; align-items: center; justify-content: center; font-size: 20px;">💳</div>
        </div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #f97316;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="text-xs text-muted" style="margin-bottom: 4px;">📈 {{ __('Active Loans') }}</div>
                <div class="stat-value">{{ $activeLoans }}</div>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(249, 115, 22, 0.1); display: flex; align-items: center; justify-content: center; font-size: 20px;">📈</div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="card p-4 mb-6 animate-fade-in-up">
    <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Quick Actions') }}</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 8px;">
        <a href="{{ route('transfers.create') }}" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.1); border-radius: var(--radius-md); text-decoration: none; transition: all 0.2s;">
            <span style="font-size: 24px;">💸</span>
            <span class="text-xs font-semibold" style="color: var(--text-primary);">{{ __('Send') }}</span>
        </a>
        <a href="{{ route('cash.index') }}" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; background: rgba(34, 197, 94, 0.05); border: 1px solid rgba(34, 197, 94, 0.1); border-radius: var(--radius-md); text-decoration: none; transition: all 0.2s;">
            <span style="font-size: 24px;">🏧</span>
            <span class="text-xs font-semibold" style="color: var(--text-primary);">{{ __('ATM') }}</span>
        </a>
        <a href="{{ route('transfers.convert') }}" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.1); border-radius: var(--radius-md); text-decoration: none; transition: all 0.2s;">
            <span style="font-size: 24px;">🔄</span>
            <span class="text-xs font-semibold" style="color: var(--text-primary);">{{ __('Convert') }}</span>
        </a>
        <a href="{{ route('cards.index') }}" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.1); border-radius: var(--radius-md); text-decoration: none; transition: all 0.2s;">
            <span style="font-size: 24px;">💳</span>
            <span class="text-xs font-semibold" style="color: var(--text-primary);">{{ __('Cards') }}</span>
        </a>
        <a href="{{ route('loans.index') }}" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; background: rgba(249, 115, 22, 0.05); border: 1px solid rgba(249, 115, 22, 0.1); border-radius: var(--radius-md); text-decoration: none; transition: all 0.2s;">
            <span style="font-size: 24px;">📈</span>
            <span class="text-xs font-semibold" style="color: var(--text-primary);">{{ __('Loans') }}</span>
        </a>
        <a href="{{ route('transactions.index') }}" style="display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 8px; background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.1); border-radius: var(--radius-md); text-decoration: none; transition: all 0.2s;">
            <span style="font-size: 24px;">📋</span>
            <span class="text-xs font-semibold" style="color: var(--text-primary);">{{ __('History') }}</span>
        </a>
    </div>
</div>

{{-- Charts --}}
<div class="grid-2 mb-6" style="align-items: start;">
    {{-- Income vs Expenses --}}
    <div class="card p-6 animate-fade-in-up">
        <div class="flex-between mb-4">
            <h2 class="section-title" style="margin: 0;">📊 {{ __('Income vs Expenses') }}</h2>
            <span class="text-xs text-muted">{{ __('Last 6 months · USD') }}</span>
        </div>
        <canvas data-chart='@json($monthlyData)' style="width: 100%; height: 240px;"></canvas>
    </div>

    {{-- Spending Categories --}}
    <div class="card p-6 animate-fade-in-up">
        <div class="flex-between mb-4">
            <h2 class="section-title" style="margin: 0;">🍩 {{ __('Spending Categories') }}</h2>
            <span class="text-xs text-muted">{{ __('This month · USD') }}</span>
        </div>
        <div class="donut-chart-container flex-column">
            <canvas data-donut='@json($categoryData)' style="width: 200px; height: 200px;"></canvas>
            <div class="donut-center-text">
                <div class="donut-center-value">${{ number_format(collect($categoryData)->sum('value'), 0) }}</div>
                <div class="donut-center-label">{{ __('Total Spent') }}</div>
            </div>
            <div class="donut-legend flex-justify-center"></div>
        </div>
    </div>
</div>

{{-- My Accounts --}}
<div class="card p-4 mb-6 animate-fade-in-up">
    <div class="flex-between mb-3">
        <h2 class="section-title" style="margin: 0;">🏦 {{ __('My Accounts') }}</h2>
        <a href="{{ route('accounts.index') }}" class="btn btn-ghost btn-sm">{{ __('View All →') }}</a>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px;">
        @forelse($accounts as $account)
            @php
                $cur = \App\Models\Currency::where('code', $account->currency)->first();
                $sym = $cur?->symbol ?? $account->currency;
                $dec = $cur?->decimal_places ?? 2;
                $isUsd = $account->currency === 'USD';
            @endphp
            <a href="{{ route('accounts.show', $account) }}" style="display: block; padding: 16px; background: var(--bg-secondary); border-radius: var(--radius-md); border-left: 3px solid {{ $isUsd ? '#3b82f6' : '#f59e0b' }}; text-decoration: none; transition: all 0.2s;">
                <div class="flex-between mb-2">
                    <span class="text-xs text-muted">{{ $isUsd ? '🇺🇸' : '🇮🇶' }} {{ $account->currency }} · {{ __(ucfirst($account->account_type)) }}</span>
                    @if($account->is_primary)
                        <span class="badge badge-info" style="font-size: 9px;">{{ __('Primary') }}</span>
                    @endif
                </div>
                <div class="font-bold" style="font-size: 20px; color: {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">{{ $sym }}{{ number_format($account->balance, $dec) }}</div>
                <div class="flex-between mt-2">
                    <span class="text-xs text-muted" style="font-family: monospace;">{{ $account->account_number }}</span>
                    <span class="text-xs text-muted">{{ __('Available') }}: {{ $sym }}{{ number_format($account->available_balance, $dec) }}</span>
                </div>
            </a>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 24px;">
                <a href="{{ route('cards.create') }}" class="btn btn-primary">💳 {{ __('Create Card & Account') }}</a>
            </div>
        @endforelse
    </div>
</div>

{{-- Recent Transactions --}}
<div class="card animate-fade-in-up">
    <div class="flex-between p-4" style="border-bottom: 1px solid var(--border);">
        <h2 class="section-title" style="margin: 0;">📋 {{ __('Recent Transactions') }}</h2>
        <a href="{{ route('transactions.index') }}" class="btn btn-ghost btn-sm">{{ __('View All →') }}</a>
    </div>
    @forelse($recentTransactions as $txn)
        @php
            $cur = \App\Models\Currency::where('code', $txn->currency)->first();
            $sym = $cur?->symbol ?? $txn->currency;
            $dec = $cur?->decimal_places ?? 2;
            $isCredit = $txn->isCredit();
        @endphp
        <a href="{{ route('transactions.show', $txn) }}" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--border); text-decoration: none; transition: all 0.2s;">
            <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; {{ $isCredit ? 'background: rgba(34, 197, 94, 0.1); color: #22c55e;' : 'background: rgba(239, 68, 68, 0.1); color: #ef4444;' }}">
                {{ $isCredit ? '↓' : '↑' }}
            </div>
            <div style="flex: 1; min-width: 0;">
                <div class="text-sm font-semibold" style="color: var(--text-primary);">{{ $txn->description ?: __(ucfirst(str_replace('_', ' ', $txn->type))) }}</div>
                <div class="text-xs text-muted">{{ $txn->created_at->format('M d, Y · h:i A') }} · {{ $txn->reference_number }}</div>
            </div>
            <div style="text-align: right;">
                <div class="font-semibold" style="color: {{ $isCredit ? '#22c55e' : '#ef4444' }};">
                    {{ $isCredit ? '+' : '-' }}{{ $sym }}{{ number_format($txn->amount, $dec) }}
                </div>
                <span style="padding: 1px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; {{ $txn->status === 'completed' ? 'background: rgba(34, 197, 94, 0.1); color: #22c55e;' : ($txn->status === 'pending' ? 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;' : 'background: rgba(239, 68, 68, 0.1); color: #ef4444;') }}">
                    {{ __(ucfirst($txn->status)) }}
                </span>
            </div>
        </a>
    @empty
        <div style="padding: 40px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">📋</div>
            <p class="font-semibold" style="margin-bottom: 4px;">{{ __('No transactions yet') }}</p>
            <p class="text-muted text-sm">{{ __('Your transaction history will appear here.') }}</p>
        </div>
    @endforelse
</div>
@endsection
