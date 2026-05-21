@extends('layouts.app')
@section('title', __('Transaction Details'))
@section('page-title', __('Transaction Details'))
@section('page-subtitle', $transaction->reference_number)

@section('content')
<div style="max-width:700px;">
    <div class="card p-6 animate-fade-in-up">
        {{-- Amount & Status Header --}}
        <div class="flex-between mb-6">
            <div>
                <div class="transaction-amount {{ $transaction->isCredit() ? 'credit' : 'debit' }}" style="font-size:36px;font-weight:800;">
                    {{ $transaction->formatted_amount }}
                </div>
                <span class="text-muted text-sm">{{ $transaction->currency }}</span>
            </div>
            <span class="badge badge-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }}" style="font-size:13px;padding:6px 16px;">
                {{ __(ucfirst($transaction->status)) }}
            </span>
        </div>

        {{-- Status Timeline --}}
        <div class="status-timeline">
            <div class="status-step">
                <div class="status-dot completed"></div>
                <div class="status-step-label">{{ __('Initiated') }}</div>
            </div>
            <div class="status-line {{ in_array($transaction->status, ['completed', 'pending']) ? 'completed' : '' }}"></div>
            <div class="status-step">
                <div class="status-dot {{ $transaction->status === 'completed' ? 'completed' : ($transaction->status === 'pending' ? 'active' : ($transaction->status === 'failed' ? 'failed' : '')) }}"></div>
                <div class="status-step-label">{{ __('Processing') }}</div>
            </div>
            <div class="status-line {{ $transaction->status === 'completed' ? 'completed' : '' }}"></div>
            <div class="status-step">
                <div class="status-dot {{ $transaction->status === 'completed' ? 'completed' : ($transaction->status === 'failed' ? 'failed' : '') }}"></div>
                <div class="status-step-label">{{ $transaction->status === 'failed' ? __('Failed') : __('Completed') }}</div>
            </div>
        </div>

        {{-- Transaction Details (Receipt Style) --}}
        <div style="display:grid;gap:0;">
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Reference') }}</span>
                <span class="receipt-value" style="font-family:monospace;">{{ $transaction->reference_number }}</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Type') }}</span>
                <span class="receipt-value">
                    <span class="transaction-icon {{ $transaction->isCredit() ? 'credit' : 'debit' }}" style="width:24px;height:24px;font-size:12px;display:inline-flex;margin-right:6px;vertical-align:middle;">
                        {{ $transaction->isCredit() ? '↓' : '↑' }}
                    </span>
                    {{ __(ucfirst(str_replace('_', ' ', $transaction->type))) }}
                </span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Description') }}</span>
                <span class="receipt-value">{{ $transaction->description ?: '—' }}</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Account') }}</span>
                <span class="receipt-value" style="font-family:monospace;">{{ $transaction->account->account_number }}</span>
            </div>
            @if($transaction->recipient_name)
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Recipient') }}</span>
                <span class="receipt-value">{{ $transaction->recipient_name }}</span>
            </div>
            @endif
            @php $txnCurrency = $transaction->getCurrencyModel(); @endphp
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Balance Before') }}</span>
                <span class="receipt-value">{{ $txnCurrency?->symbol ?? $transaction->currency }} {{ number_format($transaction->balance_before, $txnCurrency?->decimal_places ?? 2) }}</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Balance After') }}</span>
                <span class="receipt-value">{{ $txnCurrency?->symbol ?? $transaction->currency }} {{ number_format($transaction->balance_after, $txnCurrency?->decimal_places ?? 2) }}</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">{{ __('Channel') }}</span>
                <span class="receipt-value">{{ __(ucfirst($transaction->channel)) }}</span>
            </div>
            <div class="receipt-row" style="border-bottom:none;">
                <span class="receipt-label">{{ __('Date & Time') }}</span>
                <span class="receipt-value">{{ $transaction->created_at->format('F d, Y \a\t h:i:s A') }}</span>
            </div>
        </div>

        <div style="margin-top:24px;display:flex;gap:12px;">
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary">← {{ __('Back to Transactions') }}</a>
            <button onclick="copyToClipboard('{{ $transaction->reference_number }}')" class="btn btn-ghost">📋 {{ __('Copy Reference') }}</button>
        </div>
    </div>
</div>
@endsection
