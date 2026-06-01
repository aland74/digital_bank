@extends('layouts.app')
@section('title', __('Transfer Sent'))
@section('page-title', __('Transfer Sent'))

@section('content')
<div style="max-width:500px;margin:0 auto;text-align:center;">
    {{-- Step Indicator --}}
    <div class="step-indicator animate-fade-in-up">
        <div class="step">
            <div class="step-circle completed">✓<span class="step-label">{{ __('Details') }}</span></div>
        </div>
        <div class="step-connector completed"></div>
        <div class="step">
            <div class="step-circle completed">✓<span class="step-label">{{ __('Review') }}</span></div>
        </div>
        <div class="step-connector completed"></div>
        <div class="step">
            <div class="step-circle completed">✓<span class="step-label">{{ __('Sent') }}</span></div>
        </div>
    </div>

    <div class="card p-6 animate-fade-in-up" style="margin-top:16px;">
        {{-- Animated Success --}}
        <div class="success-animation">
            <div class="success-circle">
                <svg viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25"/>
                    <path class="checkmark-check" d="M14 27l7 7 16-16"/>
                </svg>
            </div>
        </div>

        <h2 style="font-size:28px;font-weight:700;color:var(--text-primary);margin-bottom:8px;">{{ __('Transfer Request Sent!') }}</h2>
        <p class="text-secondary mb-4">{{ __('The recipient has been notified and must accept the transfer.') }}</p>

        @if($pendingTransfer)
            <div class="card p-4 mb-4" style="text-align:left;">
                <div class="receipt-row" style="padding:6px 0;">
                    <span class="receipt-label">{{ __('Amount') }}</span>
                    <span class="receipt-value font-bold">${{ number_format($pendingTransfer->amount, 2) }}</span>
                </div>
                <div class="receipt-row" style="padding:6px 0;">
                    <span class="receipt-label">{{ __('To') }}</span>
                    <span class="receipt-value">{{ optional($pendingTransfer->receiverUser)->name ?? __('Unknown Recipient') }}</span>
                </div>
                <div class="receipt-row" style="padding:6px 0;">
                    <span class="receipt-label">{{ __('Status') }}</span>
                    <span class="badge badge-warning" style="font-size:10px;">{{ __('Pending Acceptance') }}</span>
                </div>
                <div class="receipt-row" style="padding:6px 0;border-bottom:none;">
                    <span class="receipt-label">{{ __('Expires') }}</span>
                    <span class="receipt-value text-sm">{{ $pendingTransfer->expires_at->format('M d, Y h:i A') }}</span>
                </div>
            </div>
        @endif

        @if($reference)
            <div style="background:var(--bg-white);border:1px dashed var(--primary);border-radius:var(--radius-md);padding:16px;margin-bottom:24px;">
                <div class="text-muted text-xs mb-1" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Reference Number') }}</div>
                <div style="font-family:monospace;font-size:16px;color:var(--info);">{{ $reference }}</div>
            </div>
            <button onclick="copyToClipboard('{{ $reference }}')" class="btn btn-ghost btn-sm mb-4">📋 {{ __('Copy Reference') }}</button>
        @endif

        <div class="alert alert-info" style="text-align:left;font-size:13px;margin-bottom:16px;">
            ⏳ {{ __('Funds have been held from your account. If the recipient doesn\'t accept within') }} {{ \App\Models\BankSetting::transferExpiryHours() }} {{ __('hours, the transfer will auto-cancel and your funds will be released.') }}
        </div>

        <div style="display:flex;gap:12px;justify-content:center;margin-top:16px;">
            <a href="{{ route('transfers.pending') }}" class="btn btn-primary">{{ __('View Pending Transfers') }}</a>
            <a href="{{ route('transfers.create') }}" class="btn btn-secondary">{{ __('New Transfer') }}</a>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost">{{ __('Dashboard') }}</a>
        </div>
    </div>
</div>
@endsection
