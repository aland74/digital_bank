@extends('layouts.app')
@section('title', __('Transactions'))
@section('page-title', '📊 ' . __('Transactions'))
@section('page-subtitle', __('View and filter your transaction history'))

@section('content')

{{-- Stats --}}
@php
    $creditTypes = ['deposit', 'transfer_in', 'refund', 'interest', 'loan_disbursement'];
    $totalIn = $transactions->whereIn('type', $creditTypes)->sum('amount');
    $totalOut = $transactions->whereNotIn('type', $creditTypes)->sum('amount');
@endphp

<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">📊</div>
        <div class="stat-value">{{ $transactions->total() }}</div>
        <div class="stat-label">{{ __('Total Transactions') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #22c55e;">
        <div class="stat-icon green">📥</div>
        <div class="stat-value">{{ $transactions->whereIn('type', $creditTypes)->count() }}</div>
        <div class="stat-label">{{ __('Incoming') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon red">📤</div>
        <div class="stat-value">{{ $transactions->whereNotIn('type', $creditTypes)->count() }}</div>
        <div class="stat-label">{{ __('Outgoing') }}</div>
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-4 animate-fade-in-up">
    <form method="GET" action="{{ route('transactions.index') }}" id="filter-form">
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            {{-- Search --}}
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label class="form-label text-xs">{{ __('Search') }}</label>
                <input type="text" name="search" class="form-input" placeholder="{{ __('Reference, description...') }}" value="{{ request('search') }}">
            </div>

            {{-- Account --}}
            <div class="form-group" style="margin-bottom: 0; min-width: 160px;">
                <label class="form-label text-xs">{{ __('Account') }}</label>
                <select name="account_id" class="form-select">
                    <option value="">{{ __('All Accounts') }}</option>
                    @foreach($accounts as $acc)
                        @php
                            $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                            $sym = $cur?->symbol ?? $acc->currency;
                        @endphp
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ $sym }} {{ $acc->account_number }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Type --}}
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label class="form-label text-xs">{{ __('Type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach(['deposit','withdrawal','transfer_in','transfer_out','payment','fee','interest'] as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ __(ucfirst(str_replace('_', ' ', $type))) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label class="form-label text-xs">{{ __('Status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('All Status') }}</option>
                    @foreach(['completed','pending','failed','cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ __(ucfirst($status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label text-xs">{{ __('From') }}</label>
                <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label text-xs">{{ __('To') }}</label>
                <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
            </div>

            {{-- Buttons --}}
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm">🔍 {{ __('Filter') }}</button>
                @if(request()->hasAny(['search', 'account_id', 'type', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('transactions.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Transaction List --}}
<div class="card animate-fade-in-up" style="overflow: hidden;">
    {{-- Header --}}
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <h2 class="section-title" style="margin: 0;">{{ $transactions->total() }} {{ __('Transaction') }}{{ $transactions->total() !== 1 ? 's' : '' }}</h2>
        @if(request()->hasAny(['search', 'account_id', 'type', 'status', 'date_from', 'date_to']))
            <span class="badge badge-info">{{ __('Filtered') }}</span>
        @endif
    </div>

    {{-- Transactions --}}
    @forelse($transactions as $txn)
        @php
            $cur = \App\Models\Currency::where('code', $txn->currency)->first();
            $sym = $cur?->symbol ?? $txn->currency;
            $dec = $cur?->decimal_places ?? 2;
            $isCredit = $txn->isCredit();
            $statusColors = [
                'completed' => '#22c55e',
                'pending' => '#f59e0b',
                'failed' => '#ef4444',
                'cancelled' => '#6b7280',
                'reversed' => '#ef4444',
            ];
            $typeIcons = [
                'deposit' => '📥',
                'withdrawal' => '📤',
                'transfer_in' => '📥',
                'transfer_out' => '📤',
                'payment' => '💳',
                'fee' => '💰',
                'interest' => '📈',
                'loan_disbursement' => '🏦',
                'loan_repayment' => '📋',
            ];
            $statusColor = $statusColors[$txn->status] ?? '#6b7280';
            $typeIcon = $typeIcons[$txn->type] ?? ($isCredit ? '📥' : '📤');
        @endphp
        <a href="{{ route('transactions.show', $txn) }}" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--border); text-decoration: none; transition: all 0.2s;">

            {{-- Icon --}}
            <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; {{ $isCredit ? 'background: rgba(34, 197, 94, 0.1); color: #22c55e;' : 'background: rgba(239, 68, 68, 0.1); color: #ef4444;' }}">
                {{ $typeIcon }}
            </div>

            {{-- Details --}}
            <div style="flex: 1; min-width: 0;">
                <div class="flex-between mb-1">
                    <span class="text-sm font-semibold" style="color: var(--text-primary);">{{ $txn->description ?: __(ucfirst(str_replace('_', ' ', $txn->type))) }}</span>
                    <span class="font-bold" style="color: {{ $isCredit ? '#22c55e' : '#ef4444' }}; font-size: 15px;">
                        {{ $isCredit ? '+' : '-' }}{{ $sym }}{{ number_format($txn->amount, $dec) }}
                    </span>
                </div>
                <div class="flex-between">
                    <div class="text-xs text-muted" style="display: flex; align-items: center; gap: 8px;">
                        <span>{{ $txn->created_at->format('M d, Y · h:i A') }}</span>
                        <span>·</span>
                        <span style="font-family: monospace;">{{ $txn->reference_number }}</span>
                        @if($txn->recipient_name)
                            <span>·</span>
                            <span>{{ __('To') }}: {{ $txn->recipient_name }}</span>
                        @endif
                    </div>
                    <span style="padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; background: {{ $statusColor }}15; color: {{ $statusColor }};">
                        {{ __(ucfirst($txn->status)) }}
                    </span>
                </div>
            </div>
        </a>
    @empty
        <div style="padding: 60px 20px; text-align: center;">
            <div style="font-size: 64px; margin-bottom: 16px;">📊</div>
            <p class="font-semibold" style="font-size: 18px; margin-bottom: 4px;">{{ __('No transactions found') }}</p>
            <p class="text-muted text-sm">{{ __('Try adjusting your filters or make your first transaction.') }}</p>
        </div>
    @endforelse
</div>

@if($transactions->hasPages())
    <div class="mt-4">{{ $transactions->links() }}</div>
@endif
@endsection
