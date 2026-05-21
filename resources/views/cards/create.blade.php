@extends('layouts.app')
@section('title', __('Request New Card'))
@section('page-title', __('Request New Card'))
@section('page-subtitle', __('Create a new card with a linked bank account'))

@section('content')
<div style="max-width:600px;">
    @if(!$kycVerified)
        <div class="alert alert-warning animate-fade-in-up" style="margin-bottom:20px;">
            ⚠️ <strong>{{ __('KYC Not Verified') }}</strong> — {{ __('Your card will be created but remain') }} <strong>{{ __('inactive') }}</strong> {{ __('until your Passport and National ID are verified.') }}
            <a href="{{ route('profile.kyc') }}" style="color:var(--info);text-decoration:underline;margin-left:4px;">{{ __('Upload Documents →') }}</a>
        </div>
    @endif

    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">💳 {{ __('Card & Account Details') }}</h2>

        <form method="POST" action="{{ route('cards.store') }}" data-loading>
            @csrf

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">{{ __('Card Type') }}</label>
                    <select name="card_type" class="form-select" required>
                        <option value="debit">💳 {{ __('Debit Card') }}</option>
                        <option value="credit">💎 {{ __('Credit Card') }}</option>
                        <option value="virtual">🌐 {{ __('Virtual Card') }}</option>
                        <option value="prepaid">🎫 {{ __('Prepaid Card') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Card Brand') }}</label>
                    <select name="card_brand" class="form-select" required>
                        <option value="visa">VISA</option>
                        <option value="mastercard">Mastercard</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Cardholder Name') }}</label>
                <input type="text" name="cardholder_name" class="form-input" value="{{ old('cardholder_name', strtoupper($user->name)) }}" required style="text-transform:uppercase;" data-validate>
                <div class="form-hint">{{ __('Name as it will appear on the card (uppercase).') }}</div>
            </div>

            {{-- Account section --}}
            <div style="margin-top:20px;margin-bottom:12px;padding:16px;border-radius:var(--radius-md);border:1px solid var(--primary);background:rgba(0,212,255,0.03);">
                <div class="text-muted text-xs mb-3" style="text-transform:uppercase;letter-spacing:0.5px;font-weight:600;">🏦 {{ __('Linked Account') }}</div>
                <div class="grid-2">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">{{ __('Account Type') }}</label>
                        <select name="account_type" class="form-select" required>
                            <option value="savings">💰 {{ __('Savings Account') }} — 2.50% APY</option>
                            <option value="checking">🏦 {{ __('Checking Account') }} — 0.10% APY</option>
                            <option value="business">💼 {{ __('Business Account') }} — 0.50% APY</option>
                            <option value="fixed_deposit">🔒 {{ __('Fixed Deposit') }} — 4.50% APY</option>
                        </select>
                    </div>
                    <input type="hidden" name="currency" value="USD">
                </div>
                <div class="form-hint" style="margin-top:8px;">{{ __('Both USD and IQD accounts will be created automatically. Your card works with both currencies.') }}</div>
            </div>

            <div class="card p-4" style="margin-top:16px;margin-bottom:20px;">
                <div class="text-muted text-xs mb-2" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Card Features') }}</div>
                <div style="display:grid;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="color:var(--success);">✓</span>
                        <span class="text-sm">{{ __('Unique 4-digit PIN generated automatically') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="color:var(--success);">✓</span>
                        <span class="text-sm">{{ __('Contactless payments enabled by default') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="color:var(--success);">✓</span>
                        <span class="text-sm">{{ __('Online payments enabled by default') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="color:var(--success);">✓</span>
                        <span class="text-sm">{{ __('$5,000 daily / $25,000 monthly limits') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="color:var(--success);">✓</span>
                        <span class="text-sm">{{ __('Valid for 5 years') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="color:var(--success);">✓</span>
                        <span class="text-sm">{{ __('Bank account created & linked automatically') }}</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full">
                <span class="btn-text">{{ __('Create Card & Account →') }}</span>
            </button>
        </form>
    </div>
</div>
@endsection
