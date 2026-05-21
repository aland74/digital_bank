@extends('layouts.app')
@section('title', __('Account Details'))
@section('page-title', $account->account_name ?? __('Account Details'))
@section('page-subtitle', $account->account_number)

@section('content')
<div class="grid-2 grid-align-start">
    {{-- Account Info --}}
    <div class="card p-6 animate-fade-in-up">
        <div class="flex-between mb-4">
            <span class="account-type-badge {{ $account->account_type }}">
                {{ __(ucfirst(str_replace('_', ' ', $account->account_type))) }}
            </span>
            <span class="badge badge-{{ $account->status === 'active' ? 'success' : 'danger' }}">
                {{ __(ucfirst($account->status)) }}
            </span>
        </div>
        <div class="account-balance account-balance-large">{{ $account->formatted_balance }}</div>
        <div class="text-muted text-sm mb-6">{{ __('Available:') }} {{ $account->currencyModel()?->symbol ?? $account->currency }} {{ number_format($account->available_balance, $account->currencyModel()?->decimal_places ?? 2) }}</div>

        <div class="grid-2 flex-gap-2">
            <div class="card p-4">
                <div class="text-muted text-xs text-uppercase-tracking">{{ __('Currency') }}</div>
                <div class="font-semibold mt-1">{{ $account->currency }}</div>
            </div>
            <div class="card p-4">
                <div class="text-muted text-xs text-uppercase-tracking">{{ __('Interest Rate') }}</div>
                <div class="font-semibold mt-1">{{ $account->interest_rate * 100 }}% {{ __('APY') }}</div>
            </div>
            <div class="card p-4">
                <div class="text-muted text-xs text-uppercase-tracking">{{ __('Daily Limit') }}</div>
                <div class="font-semibold mt-1">{{ $account->currencyModel()?->symbol ?? $account->currency }} {{ number_format($account->daily_transfer_limit, $account->currencyModel()?->decimal_places ?? 2) }}</div>
            </div>
            <div class="card p-4">
                <div class="text-muted text-xs text-uppercase-tracking">{{ __('Opened') }}</div>
                <div class="font-semibold mt-1">{{ $account->opened_at?->format('M d, Y') ?? __('N/A') }}</div>
            </div>
        </div>

        <div class="flex flex-gap-2 mt-5">
            <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm">💸 {{ __('Transfer') }}</a>
            <button onclick="copyToClipboard('{{ $account->account_number }}')" class="btn btn-secondary btn-sm">📋 {{ __('Copy Number') }}</button>
        </div>
    </div>

    {{-- Account Number Card --}}
    <div class="card p-6 animate-fade-in-up delay-100">
        <h3 class="section-title mb-4">{{ __('Account Number') }}</h3>
        <div class="dotted-card-container">
            <div class="dotted-card-number">
                {{ $account->account_number }}
            </div>
            <button type="button" class="btn btn-ghost btn-sm dotted-copy-btn" onclick="copyToClipboard('{{ $account->account_number }}')" title="{{ __('Copy Account Number') }}">
                📋
            </button>
        </div>
        <p class="text-sm text-muted mt-4">{{ __('Share this number to receive transfers. This is your unique NexusBank identifier.') }}</p>
    </div>
</div>

{{-- Transaction History --}}
<div class="card mt-6 animate-fade-in-up delay-200">
    <div class="section-header p-6" style="margin-bottom:0;">
        <h2 class="section-title">{{ __('Transaction History') }}</h2>
    </div>
    @forelse($transactions as $txn)
        <div class="transaction-item">
            <div class="transaction-icon {{ $txn->isCredit() ? 'credit' : 'debit' }}">
                {{ $txn->isCredit() ? '↓' : '↑' }}
            </div>
            <div class="transaction-details">
                <div class="transaction-title">{{ $txn->description ?: __(ucfirst(str_replace('_', ' ', $txn->type))) }}</div>
                <div class="transaction-meta">{{ $txn->created_at->format('M d, Y · h:i A') }}</div>
            </div>
            <div class="transaction-amount {{ $txn->isCredit() ? 'credit' : 'debit' }}">
                {{ $txn->formatted_amount }}
            </div>
            <span class="badge badge-{{ $txn->status === 'completed' ? 'success' : ($txn->status === 'pending' ? 'warning' : 'danger') }}">
                {{ __(ucfirst($txn->status)) }}
            </span>
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <p class="empty-state-title">{{ __('No transactions yet') }}</p>
        </div>
    @endforelse

    @if($transactions->hasPages())
        <div class="pagination-wrapper">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
