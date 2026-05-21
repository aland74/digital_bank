@extends('layouts.app')
@section('title', __('My Accounts'))
@section('page-title', __('My Accounts'))
@section('page-subtitle', __('View your bank accounts linked to cards'))

@section('content')
<div class="section-header">
    <h2 class="section-title">{{ $accounts->count() }} {{ __('Account') }}{{ $accounts->count() !== 1 ? 's' : '' }}</h2>
    <a href="{{ route('cards.create') }}" class="btn btn-primary">
        💳 {{ __('Create New Card & Account') }}
    </a>
</div>

<div class="grid-2 animate-fade-in-up">
    @forelse($accounts as $account)
        <a href="{{ route('accounts.show', $account) }}" class="account-card card">
            <div class="flex-between mb-4">
                <span class="account-type-badge {{ $account->account_type }}">
                    {{ ucfirst(str_replace('_', ' ', $account->account_type)) }}
                </span>
                <div class="flex flex-gap-2">
                    @if($account->is_primary)
                        <span class="badge badge-info">{{ __('Primary') }}</span>
                    @endif
                    <span class="badge badge-{{ $account->status === 'active' ? 'success' : 'danger' }}">
                        {{ __($account->status) }}
                    </span>
                </div>
            </div>

            <div class="account-balance">{{ $account->formatted_balance }}</div>
            <div class="account-number">
                <span>{{ $account->account_number }}</span>
                <button type="button" class="btn btn-ghost btn-sm btn-copy-mini" onclick="event.preventDefault(); event.stopPropagation(); copyToClipboard('{{ $account->account_number }}')" title="{{ __('Copy Account Number') }}">
                    📋
                </button>
            </div>

            <div class="account-card-footer">
                <span>{{ $account->currency }}</span>
                <span>{{ $account->transactions_count ?? 0 }} {{ __('transactions') }}</span>
                <span>{{ $account->interest_rate * 100 }}% {{ __('APY') }}</span>
            </div>
        </a>
    @empty
        <div class="card p-6" style="grid-column:1/-1;">
            <div class="empty-state">
                <div class="empty-state-icon">💳</div>
                <p class="empty-state-title">{{ __('No accounts yet') }}</p>
                <p class="empty-state-text">{{ __('Create a card to automatically open your first bank account.') }}</p>
                <a href="{{ route('cards.create') }}" class="btn btn-primary">{{ __('Create Card & Account') }}</a>
            </div>
        </div>
    @endforelse
</div>
@endsection
