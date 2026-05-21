@extends('layouts.app')
@section('title', __('Confirm Transfer'))
@section('page-title', __('Confirm Transfer'))
@section('page-subtitle', __('Review the details before sending'))

@php
    $cur = \App\Models\Currency::where('code', $fromAccount->currency)->first();
    $sym = $cur?->symbol ?? $fromAccount->currency;
    $dec = $cur?->decimal_places ?? 2;
@endphp

@section('content')
<div style="max-width:600px;">
    {{-- Step Indicator --}}
    <div class="step-indicator animate-fade-in-up">
        <div class="step">
            <div class="step-circle completed">✓<span class="step-label">{{ __('Details') }}</span></div>
        </div>
        <div class="step-connector completed"></div>
        <div class="step">
            <div class="step-circle active">2<span class="step-label">{{ __('Review') }}</span></div>
        </div>
        <div class="step-connector"></div>
        <div class="step">
            <div class="step-circle pending">3<span class="step-label">{{ __('Sent') }}</span></div>
        </div>
    </div>

    <div class="card p-6 animate-fade-in-up" style="margin-top:16px;">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-size:48px;margin-bottom:12px;">💸</div>
            <div style="font-size:40px;font-weight:800;color:var(--text-primary);">{{ $sym }}{{ number_format($amount, $dec) }}</div>
            <div class="text-muted text-sm mt-2">{{ __('Transfer Amount') }} ({{ $fromAccount->currency }})</div>
        </div>

        <div class="card p-4 mb-4">
            <div class="text-muted text-xs mb-2" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('From') }}</div>
            <div class="font-semibold">{{ $fromAccount->account_name }}</div>
            <div class="text-sm text-muted" style="font-family:monospace;">{{ $fromAccount->account_number }}</div>
            <div class="text-sm text-secondary">{{ __('Balance:') }} {{ $sym }}{{ number_format($fromAccount->balance, $dec) }}</div>
        </div>

        <div style="text-align:center;margin:8px 0;">
            <span style="font-size:20px;">⬇️</span>
        </div>

        <div class="card p-4 mb-4">
            <div class="text-muted text-xs mb-2" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('To') }}</div>
            <div class="font-semibold">{{ $toAccount->user->name }}</div>
            <div class="text-sm text-muted" style="font-family:monospace;">{{ $toAccount->account_number }}</div>
            @if($toAccount->currency !== $fromAccount->currency)
                <div class="text-xs text-muted mt-1">{{ __('Recipient receives') }}: {{ \App\Models\Currency::where('code', $toAccount->currency)->first()?->symbol ?? $toAccount->currency }}{{ number_format($convertedAmount ?? $amount, \App\Models\Currency::where('code', $toAccount->currency)->first()?->decimal_places ?? 2) }}</div>
            @endif
        </div>

        @if($description)
            <div class="card p-4 mb-4">
                <div class="text-muted text-xs mb-2" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Description') }}</div>
                <div class="font-medium">{{ $description }}</div>
            </div>
        @endif

        {{-- Transfer Summary --}}
        <div class="card p-4 mb-4" style="border-color:rgba(0,212,255,0.15);">
            <div class="text-muted text-xs mb-2" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Summary') }}</div>
            <div class="receipt-row" style="padding:8px 0;">
                <span class="receipt-label">{{ __('Transfer Amount') }}</span>
                <span class="receipt-value">{{ $sym }}{{ number_format($amount, $dec) }}</span>
            </div>
            <div class="receipt-row" style="padding:8px 0;">
                <span class="receipt-label">{{ __('Fee') }}</span>
                <span class="receipt-value text-green">{{ $sym }}0.00</span>
            </div>
            <div class="receipt-row" style="padding:8px 0;border-bottom:none;">
                <span class="receipt-label font-semibold">{{ __('Held from Balance') }}</span>
                <span class="receipt-value font-bold text-cyan">{{ $sym }}{{ number_format($fromAccount->balance - $amount, $dec) }}</span>
            </div>
        </div>

        <div class="alert alert-info" style="font-size:13px;">
            📨 {{ __('After sending, the recipient must') }} <strong>{{ __('accept') }}</strong> {{ __('this transfer. Funds will be held from your account until they respond (up to') }} {{ \App\Models\BankSetting::transferExpiryHours() }} {{ __('hours).') }}
        </div>

        <div style="display:flex;gap:12px;margin-top:24px;">
            <form method="POST" action="{{ route('transfers.store') }}" style="flex:1;" data-loading>
                @csrf
                <input type="hidden" name="from_account_id" value="{{ $fromAccount->id }}">
                <input type="hidden" name="to_account_id" value="{{ $toAccount->id }}">
                <input type="hidden" name="amount" value="{{ $amount }}">
                <input type="hidden" name="description" value="{{ $description }}">
                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <span class="btn-text">📤 {{ __('Send Transfer Request') }}</span>
                </button>
            </form>
            <a href="{{ route('transfers.create') }}" class="btn btn-secondary btn-lg">{{ __('Cancel') }}</a>
        </div>
    </div>
</div>
@endsection
