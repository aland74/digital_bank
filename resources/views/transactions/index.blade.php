@extends('layouts.app')
@section('title', __('Transactions'))
@section('page-title', __('Transactions'))
@section('page-subtitle', __('View and filter your transaction history'))

@section('content')
{{-- Filters --}}
<div class="card p-6 mb-6 animate-fade-in-up">
    <form method="GET" action="{{ route('transactions.index') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;">
            <label class="form-label">{{ __('Search') }}</label>
            <input type="text" name="search" class="form-input" placeholder="{{ __('Reference, description...') }}" value="{{ request('search') }}">
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:150px;">
            <label class="form-label">{{ __('Account') }}</label>
            <select name="account_id" class="form-select">
                <option value="">{{ __('All Accounts') }}</option>
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                        {{ $acc->account_number }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:140px;">
            <label class="form-label">{{ __('Type') }}</label>
            <select name="type" class="form-select">
                <option value="">{{ __('All Types') }}</option>
                @foreach(['deposit','withdrawal','transfer_in','transfer_out','payment','refund','fee','interest'] as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                        {{ __(ucfirst(str_replace('_', ' ', $type))) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;min-width:140px;">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select">
                <option value="">{{ __('All Status') }}</option>
                @foreach(['completed','pending','failed','cancelled','reversed'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                        {{ __(ucfirst($status)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">{{ __('From') }}</label>
            <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">{{ __('To') }}</label>
            <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
        </div>
        <button type="submit" class="btn btn-primary">🔍 {{ __('Filter') }}</button>
        <a href="{{ route('transactions.index') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
    </form>
</div>

{{-- Transactions List --}}
<div class="card animate-fade-in-up delay-100">
    <div class="section-header p-6" style="margin-bottom:0;">
        <h2 class="section-title">{{ $transactions->total() }} {{ __('Transaction') }}{{ $transactions->total() !== 1 ? 's' : '' }}</h2>
    </div>

    @forelse($transactions as $txn)
        <a href="{{ route('transactions.show', $txn) }}" class="transaction-item" style="text-decoration:none;">
            <div class="transaction-icon {{ $txn->isCredit() ? 'credit' : 'debit' }}">
                {{ $txn->isCredit() ? '↓' : '↑' }}
            </div>
            <div class="transaction-details">
                <div class="transaction-title">{{ $txn->description ?: __(ucfirst(str_replace('_', ' ', $txn->type))) }}</div>
                <div class="transaction-meta">
                    {{ $txn->created_at->format('M d, Y · h:i A') }} · {{ $txn->reference_number }}
                    @if($txn->recipient_name)
                        · {{ __('To') }}: {{ $txn->recipient_name }}
                    @endif
                </div>
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
            <p class="empty-state-title">{{ __('No transactions found') }}</p>
            <p class="empty-state-text">{{ __('Try adjusting your filters or make your first transaction.') }}</p>
        </div>
    @endforelse

    @if($transactions->hasPages())
        <div class="pagination-wrapper">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
@endsection
