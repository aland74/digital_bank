@extends('layouts.app')
@section('title', 'Card Created')
@section('page-title', 'Card Created')

@section('content')
<div style="max-width:500px;margin:0 auto;text-align:center;">
    <div class="card p-6 animate-fade-in-up">
        {{-- Success Animation --}}
        <div class="success-animation">
            <div class="success-circle">
                <svg viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25"/>
                    <path class="checkmark-check" d="M14 27l7 7 16-16"/>
                </svg>
            </div>
        </div>

        <h2 style="font-size:28px;font-weight:700;color:var(--text-primary);margin-bottom:8px;">Card Created!</h2>
        <p class="text-secondary mb-6">Your new card has been generated successfully.</p>

        {{-- Card Preview --}}
        <div class="bank-card {{ $card->card_brand }}" style="margin:0 auto 24px;pointer-events:none;">
            <div class="card-glow"></div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;z-index:1;">
                <div class="bank-card-chip"></div>
                <span class="badge {{ $card->isActive() ? 'badge-success' : 'badge-warning' }}" style="font-size:10px;">
                    {{ $card->status_label }}
                </span>
            </div>
            <div class="bank-card-number">{{ $card->masked_number }}</div>
            <div class="bank-card-footer">
                <div>
                    <div class="bank-card-name">{{ $card->cardholder_name }}</div>
                    <div class="bank-card-expiry">{{ $card->expiry_date }}</div>
                </div>
            </div>
            <div class="bank-card-brand">{{ $card->card_brand }}</div>
        </div>

        {{-- PIN Display --}}
        @if($pin)
            <div style="background:linear-gradient(135deg,rgba(168,85,247,0.15),rgba(0,212,255,0.1));border:2px solid rgba(168,85,247,0.4);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;">
                <div style="font-size:14px;color:#7c3aed;font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;">Your Card PIN</div>
                <div style="font-size:48px;font-weight:800;letter-spacing:16px;color:var(--text-primary);font-family:monospace;">{{ $pin }}</div>
                <div style="margin-top:12px;padding:8px 16px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:var(--radius-md);">
                    <span style="color:#ef4444;font-size:12px;font-weight:600;">⚠️ IMPORTANT: Save this PIN now! It will NOT be shown again.</span>
                </div>
            </div>
        @endif

        @if(!$card->isActive())
            <div class="alert alert-warning" style="text-align:left;">
                🔒 <strong>Card Pending Activation</strong><br>
                <span class="text-sm">Your card is inactive until your identity documents (Passport + National ID) are verified by an admin.</span>
                <a href="{{ route('profile.kyc') }}" class="btn btn-ghost btn-sm" style="margin-top:8px;display:inline-block;">Upload Documents →</a>
            </div>
        @endif

        <div style="display:flex;gap:12px;justify-content:center;margin-top:16px;">
            <a href="{{ route('cards.index') }}" class="btn btn-primary">View My Cards</a>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Dashboard</a>
        </div>
    </div>
</div>
@endsection
