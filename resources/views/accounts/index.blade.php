@extends('layouts.app')
@section('title', __('My Accounts'))
@section('page-title', '🏦 ' . __('My Accounts'))
@section('page-subtitle', __('View your bank accounts'))

@section('content')

{{-- Stats --}}
@php
    $usdAccounts = $accounts->where('currency', 'USD');
    $iqdAccounts = $accounts->where('currency', 'IQD');
    $usdBalance = $usdAccounts->sum('balance');
    $iqdBalance = $iqdAccounts->sum('balance');
@endphp

<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">💵</div>
        <div class="stat-value">${{ number_format($usdBalance, 2) }}</div>
        <div class="stat-label">{{ __('USD Balance') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #f59e0b;">
        <div class="stat-icon orange">💰</div>
        <div class="stat-value">د.ع{{ number_format($iqdBalance, 0) }}</div>
        <div class="stat-label">{{ __('IQD Balance') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #8b5cf6;">
        <div class="stat-icon purple">🏦</div>
        <div class="stat-value">{{ $accounts->count() }}</div>
        <div class="stat-label">{{ __('Total Accounts') }}</div>
    </div>
</div>

{{-- Header --}}
<div class="flex-between mb-6">
    <h2 class="section-title">{{ $accounts->count() }} {{ __('Account') }}{{ $accounts->count() !== 1 ? 's' : '' }}</h2>
    <a href="{{ route('cards.create') }}" class="btn btn-primary">💳 {{ __('Create New Card & Account') }}</a>
</div>

{{-- Account Cards --}}
<div class="grid-2 animate-fade-in-up">
    @forelse($accounts as $account)
        @php
            $cur = \App\Models\Currency::where('code', $account->currency)->first();
            $sym = $cur?->symbol ?? $account->currency;
            $dec = $cur?->decimal_places ?? 2;
            $isUsd = $account->currency === 'USD';
        @endphp
        <a href="{{ route('accounts.show', $account) }}" class="card p-6" style="display: block; text-decoration: none; border-left: 4px solid {{ $isUsd ? '#3b82f6' : '#f59e0b' }}; transition: all 0.2s;">
            <div class="flex-between mb-4">
                <div class="flex flex-gap-2">
                    <span class="badge badge-{{ $account->account_type === 'savings' ? 'success' : ($account->account_type === 'checking' ? 'info' : ($account->account_type === 'business' ? 'purple' : 'warning')) }}">
                        {{ $account->account_type === 'savings' ? '💰' : ($account->account_type === 'checking' ? '🏦' : ($account->account_type === 'business' ? '💼' : '🔒')) }}
                        {{ __(ucfirst(str_replace('_', ' ', $account->account_type))) }}
                    </span>
                    @if($account->is_primary)
                        <span class="badge badge-info">{{ __('Primary') }}</span>
                    @endif
                </div>
                <span class="badge badge-{{ $account->status === 'active' ? 'success' : 'danger' }}">
                    {{ __($account->status) }}
                </span>
            </div>

            <div style="margin-bottom: 16px;">
                <div style="font-size: 32px; font-weight: 800; color: {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
                    {{ $sym }}{{ number_format($account->balance, $dec) }}
                </div>
                <div class="text-sm text-muted">
                    {{ __('Available') }}: {{ $sym }}{{ number_format($account->available_balance, $dec) }}
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <span class="text-sm text-muted" style="font-family: monospace; letter-spacing: 1px;">{{ $account->account_number }}</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="event.preventDefault(); event.stopPropagation(); copyToClipboard('{{ $account->account_number }}')" title="{{ __('Copy') }}" style="padding: 2px 6px;">📋</button>
            </div>

            <div class="flex-between text-xs text-muted" style="padding-top: 12px; border-top: 1px solid var(--border);">
                <span>{{ $account->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ $account->currency }}</span>
                <span>{{ $account->transactions_count ?? 0 }} {{ __('txn') }}</span>
                <span>{{ $account->interest_rate * 100 }}% {{ __('APY') }}</span>
            </div>
        </a>
    @empty
        <div class="card p-6" style="grid-column: 1 / -1;">
            <div class="empty-state">
                <div style="font-size: 64px; margin-bottom: 16px;">🏦</div>
                <p class="empty-state-title">{{ __('No accounts yet') }}</p>
                <p class="empty-state-text">{{ __('Create a card to automatically open your first bank account.') }}</p>
                <a href="{{ route('cards.create') }}" class="btn btn-primary mt-4">💳 {{ __('Create Card & Account') }}</a>
            </div>
        </div>
    @endforelse
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show brief feedback
        const btn = event.target;
        const original = btn.textContent;
        btn.textContent = '✅';
        setTimeout(() => btn.textContent = original, 1500);
    });
}
</script>
@endsection
