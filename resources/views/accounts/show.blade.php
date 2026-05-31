@extends('layouts.app')
@section('title', __('Account Details'))
@section('page-title', $account->account_name ?? __('Account Details'))
@section('page-subtitle', $account->account_number)

@section('content')
@php
    $cur = \App\Models\Currency::where('code', $account->currency)->first();
    $sym = $cur?->symbol ?? $account->currency;
    $dec = $cur?->decimal_places ?? 2;
    $isUsd = $account->currency === 'USD';
@endphp

<div class="grid-2" style="align-items: start;">

    {{-- LEFT: Account Info --}}
    <div>
        {{-- Balance Card --}}
        <div class="card p-6 mb-4 animate-fade-in-up" style="border-left: 4px solid {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
            <div class="flex-between mb-4">
                <div class="flex flex-gap-2">
                    <span class="badge badge-{{ $account->account_type === 'savings' ? 'success' : ($account->account_type === 'checking' ? 'info' : ($account->account_type === 'business' ? 'purple' : 'warning')) }}">
                        {{ __(ucfirst(str_replace('_', ' ', $account->account_type))) }}
                    </span>
                    <span class="badge badge-{{ $account->status === 'active' ? 'success' : 'danger' }}">
                        {{ __(ucfirst($account->status)) }}
                    </span>
                    @if($account->is_primary)
                        <span class="badge badge-info">{{ __('Primary') }}</span>
                    @endif
                </div>
            </div>

            <div style="text-align: center; margin-bottom: 24px;">
                <div style="font-size: 48px; font-weight: 800; color: {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
                    {{ $sym }}{{ number_format($account->balance, $dec) }}
                </div>
                <div class="text-muted">{{ __('Total Balance') }} ({{ $account->currency }})</div>
                <div class="text-sm mt-2">
                    {{ __('Available') }}: <strong>{{ $sym }}{{ number_format($account->available_balance, $dec) }}</strong>
                </div>
                @if($account->hold_amount > 0)
                    <div class="text-xs text-muted mt-1">
                        {{ __('On Hold') }}: {{ $sym }}{{ number_format($account->hold_amount, $dec) }}
                    </div>
                @endif
            </div>

            {{-- Info Grid --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Currency') }}</div>
                    <div class="font-semibold mt-1">{{ $account->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ $account->currency }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Interest Rate') }}</div>
                    <div class="font-semibold mt-1">{{ $account->interest_rate * 100 }}% {{ __('APY') }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Daily Limit') }}</div>
                    <div class="font-semibold mt-1">{{ $sym }}{{ number_format($account->daily_transfer_limit, $dec) }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Monthly Limit') }}</div>
                    <div class="font-semibold mt-1">{{ $sym }}{{ number_format($account->monthly_transfer_limit, $dec) }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Opened') }}</div>
                    <div class="font-semibold mt-1">{{ $account->opened_at?->format('M d, Y') ?? __('N/A') }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Transactions') }}</div>
                    <div class="font-semibold mt-1">{{ $account->transactions_count ?? 0 }}</div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-gap-2 mt-6">
                <a href="{{ route('transfers.create') }}" class="btn btn-primary flex-1">💸 {{ __('Transfer') }}</a>
                <button onclick="copyToClipboard('{{ $account->account_number }}')" class="btn btn-secondary flex-1">📋 {{ __('Copy Number') }}</button>
            </div>
        </div>

        {{-- Account Number Card --}}
        <div class="card p-6 animate-fade-in-up">
            <h3 class="section-title mb-4">{{ __('Account Number') }}</h3>
            <div style="padding: 20px; background: var(--bg-secondary); border-radius: var(--radius-md); text-align: center; border: 2px dashed var(--border);">
                <div style="font-size: 28px; font-weight: 700; font-family: monospace; letter-spacing: 3px; color: {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
                    {{ $account->account_number }}
                </div>
                <button type="button" class="btn btn-ghost btn-sm mt-3" onclick="copyToClipboard('{{ $account->account_number }}')">
                    📋 {{ __('Copy to Clipboard') }}
                </button>
            </div>
            <p class="text-sm text-muted mt-4">{{ __('Share this number to receive transfers. This is your unique Distributed Bank identifier.') }}</p>
        </div>
    </div>

    {{-- RIGHT: Transaction History --}}
    <div class="card animate-fade-in-up">
        <div class="flex-between p-6" style="border-bottom: 1px solid var(--border);">
            <h3 class="section-title">{{ __('Transaction History') }}</h3>
            <span class="text-sm text-muted">{{ $transactions->total() }} {{ __('transactions') }}</span>
        </div>

        @forelse($transactions as $txn)
            @php
                $txnCur = \App\Models\Currency::where('code', $txn->currency)->first();
                $txnSym = $txnCur?->symbol ?? $txn->currency;
                $txnDec = $txnCur?->decimal_places ?? 2;
                $isCredit = $txn->isCredit();
            @endphp
            <div class="flex-between" style="padding: 14px 24px; border-bottom: 1px solid var(--border);">
                <div class="flex align-items-center flex-gap-3">
                    <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; {{ $isCredit ? 'background: rgba(34, 197, 94, 0.1); color: #22c55e;' : 'background: rgba(239, 68, 68, 0.1); color: #ef4444;' }}">
                        {{ $isCredit ? '↓' : '↑' }}
                    </div>
                    <div>
                        <div class="text-sm font-semibold">{{ $txn->description ?: __(ucfirst(str_replace('_', ' ', $txn->type))) }}</div>
                        <div class="text-xs text-muted">{{ $txn->created_at->format('M d, Y · h:i A') }}</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div class="font-semibold" style="color: {{ $isCredit ? '#22c55e' : '#ef4444' }};">
                        {{ $isCredit ? '+' : '-' }}{{ $txnSym }}{{ number_format($txn->amount, $txnDec) }}
                    </div>
                    <span class="badge badge-{{ $txn->status === 'completed' ? 'success' : ($txn->status === 'pending' ? 'warning' : 'danger') }}" style="font-size: 10px;">
                        {{ __(ucfirst($txn->status)) }}
                    </span>
                </div>
            </div>
        @empty
            <div class="empty-state" style="padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
                <p class="empty-state-title">{{ __('No transactions yet') }}</p>
                <p class="empty-state-text">{{ __('Your transaction history will appear here.') }}</p>
            </div>
        @endforelse

        @if($transactions->hasPages())
            <div class="p-4" style="border-top: 1px solid var(--border);">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const original = btn.textContent;
        btn.textContent = '✅ Copied!';
        setTimeout(() => btn.textContent = original, 1500);
    });
}
</script>
@endsection
