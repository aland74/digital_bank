@extends('layouts.app')
@section('title', __('Request New Card'))
@section('page-title', '💳 ' . __('Request New Card'))
@section('page-subtitle', __('Create a new card with a linked bank account'))

@section('content')
<div style="max-width: 640px;">

    {{-- ── KYC HARD BLOCK (shown when user is NOT verified) ────────────── --}}
    @if(!$kycVerified)
        <div class="card p-6 animate-fade-in-up" style="
            border: 2px solid rgba(239, 68, 68, 0.4);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(245, 158, 11, 0.05));
            text-align: center;
        ">
            <div style="font-size: 64px; margin-bottom: 16px; line-height: 1;">🔒</div>

            <h2 style="font-size: 1.4rem; font-weight: 700; color: var(--danger); margin-bottom: 8px;">
                {{ __('KYC Verification Required') }}
            </h2>

            <p class="text-muted mb-2" style="font-size: 0.95rem; max-width: 440px; margin: 0 auto 20px;">
                {{ __('You must complete identity verification before you can create or activate a card. This is required by banking regulations to protect your account.') }}
            </p>

            <div style="
                display: inline-flex;
                gap: 24px;
                padding: 16px 24px;
                background: rgba(239, 68, 68, 0.06);
                border-radius: var(--radius-md);
                margin-bottom: 24px;
                flex-wrap: wrap;
                justify-content: center;
            ">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--danger); font-size: 1.2rem;">✗</span>
                    <span class="text-sm font-semibold">{{ __('Passport') }}</span>
                    <span class="badge badge-danger" style="font-size: 0.7rem; padding: 2px 8px;">{{ __('Missing') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--danger); font-size: 1.2rem;">✗</span>
                    <span class="text-sm font-semibold">{{ __('National ID') }}</span>
                    <span class="badge badge-danger" style="font-size: 0.7rem; padding: 2px 8px;">{{ __('Missing') }}</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px; align-items: center;">
                <a href="{{ route('profile.kyc') }}" class="btn btn-primary btn-lg" style="min-width: 260px;">
                    📄 {{ __('Upload KYC Documents Now →') }}
                </a>
                <a href="{{ route('cards.index') }}" class="text-sm text-muted">
                    ← {{ __('Back to My Cards') }}
                </a>
            </div>
        </div>

    {{-- ── CARD CREATION FORM (KYC verified users only) ────────────────── --}}
    @else

        {{-- Info Card --}}
        <div class="card p-4 mb-4 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(245, 158, 11, 0.05));">
            <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('What You Get') }}</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--success);">✓</span>
                    <span class="text-sm">{{ __('Both USD & IQD accounts') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--success);">✓</span>
                    <span class="text-sm">{{ __('Auto-generated PIN') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--success);">✓</span>
                    <span class="text-sm">{{ __('Contactless enabled') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--success);">✓</span>
                    <span class="text-sm">{{ __('Online payments on') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--success);">✓</span>
                    <span class="text-sm">{{ __('$5K daily / $25K monthly') }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: var(--success);">✓</span>
                    <span class="text-sm">{{ __('Valid for 5 years') }}</span>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <div class="card p-6 animate-fade-in-up">
            <h2 class="section-title mb-6">💳 {{ __('Card & Account Details') }}</h2>

            <form method="POST" action="{{ route('cards.store') }}" data-loading>
                @csrf

                {{-- Card Type & Brand --}}
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

                {{-- Cardholder Name --}}
                <div class="form-group">
                    <label class="form-label">{{ __('Cardholder Name') }}</label>
                    <input type="text" name="cardholder_name" class="form-input" value="{{ old('cardholder_name', strtoupper($user->name)) }}" required style="text-transform: uppercase;" data-validate>
                    <div class="form-hint">{{ __('Name as it will appear on the card (uppercase).') }}</div>
                </div>

                {{-- Account Type --}}
                <div class="form-group">
                    <label class="form-label">{{ __('Account Type') }}</label>
                    <select name="account_type" class="form-select" required>
                        <option value="savings">💰 {{ __('Savings Account') }} — 2.50% APY</option>
                        <option value="checking">🏦 {{ __('Checking Account') }} — 0.10% APY</option>
                        <option value="business">💼 {{ __('Business Account') }} — 0.50% APY</option>
                        <option value="fixed_deposit">🔒 {{ __('Fixed Deposit') }} — 4.50% APY</option>
                    </select>
                    <div class="form-hint">{{ __('Both USD and IQD accounts will be created automatically.') }}</div>
                </div>

                {{-- Currency Info --}}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div style="padding: 16px; background: rgba(59, 130, 246, 0.05); border-radius: var(--radius-md); border-left: 3px solid #3b82f6;">
                        <div style="font-size: 20px; margin-bottom: 8px;">🇺🇸</div>
                        <div class="font-semibold">USD Account</div>
                        <div class="text-xs text-muted">Dollars • 2 decimal places</div>
                    </div>
                    <div style="padding: 16px; background: rgba(245, 158, 11, 0.05); border-radius: var(--radius-md); border-left: 3px solid #f59e0b;">
                        <div style="font-size: 20px; margin-bottom: 8px;">🇮🇶</div>
                        <div class="font-semibold">IQD Account</div>
                        <div class="text-xs text-muted">Dinars • Whole numbers</div>
                    </div>
                </div>

                <input type="hidden" name="currency" value="USD">

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary btn-lg w-full">
                    💳 {{ __('Create Card & Account →') }}
                </button>
            </form>
        </div>

        {{-- Back --}}
        <div class="mt-4 text-center">
            <a href="{{ route('cards.index') }}" class="text-sm text-muted">← {{ __('Back to My Cards') }}</a>
        </div>

    @endif

</div>
@endsection
