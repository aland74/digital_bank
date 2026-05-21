@extends('layouts.app')
@section('title', __('My Account'))
@section('page-title', '👤 ' . __('My Account'))
@section('page-subtitle', __('View and manage your profile'))

@section('content')

{{-- KYC Banner --}}
@if(!auth()->user()->isKycVerified())
    <div class="alert alert-warning mb-6 animate-fade-in-up">
        🔒 <strong>{{ __('Account Pending Verification') }}</strong> — {{ __('Upload your Passport and National ID to unlock all features and activate cards.') }}
        <a href="{{ route('profile.kyc') }}" class="btn btn-ghost btn-sm" style="margin-left: 8px; vertical-align: middle;">{{ __('Upload Documents →') }}</a>
    </div>
@endif

{{-- Profile Header --}}
<div class="card p-6 mb-6 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));">
    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 20px;">
        {{-- Avatar --}}
        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold; color: white; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); flex-shrink: 0;">
            {{ auth()->user()->initials }}
        </div>

        {{-- Info --}}
        <div style="flex: 1; min-width: 250px;">
            <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px; color: var(--text-primary);">{{ auth()->user()->name }}</h1>
            <div class="text-muted text-sm mb-3" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
                <span>📧 {{ auth()->user()->email }}</span>
                <span>📱 {{ auth()->user()->phone ?? __('No phone added') }}</span>
                <span>🏦 {{ auth()->user()->branch_display_name }}</span>
            </div>

            {{-- Primary Account --}}
            @if($primaryAccount = auth()->user()->primaryAccount())
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; background: var(--bg-primary); border-radius: 20px; border: 1px solid var(--border); margin-bottom: 12px;">
                    <span class="text-sm">💳 {{ __('Primary') }}: <strong>{{ $primaryAccount->account_number }}</strong></span>
                    <button type="button" class="btn btn-ghost btn-sm" style="padding: 2px 6px;" onclick="copyToClipboard('{{ $primaryAccount->account_number }}')" title="{{ __('Copy') }}">📋</button>
                </div>
            @endif

            {{-- Badges --}}
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <span class="badge {{ auth()->user()->isKycVerified() ? 'badge-success' : 'badge-warning' }}" style="padding: 6px 12px;">
                    {{ auth()->user()->isKycVerified() ? '✅ ' . __('Verified') : '⏳ ' . __('Pending Verification') }}
                </span>
                <span class="badge badge-info" style="padding: 6px 12px;">
                    {{ __(ucfirst(auth()->user()->role)) }}
                </span>
            </div>
        </div>

        {{-- Settings Toggle --}}
        <button onclick="toggleSettings()" class="btn btn-secondary" id="settings-btn">
            ⚙️ <span id="settings-btn-text">{{ __('Edit Profile') }}</span>
        </button>
    </div>
</div>

{{-- Quick Links --}}
<div class="stats-grid mb-6 animate-fade-in-up">
    <a href="{{ route('profile.security') }}" class="card p-4" style="text-decoration: none; text-align: center; border-left: 3px solid #3b82f6;">
        <div style="font-size: 24px; margin-bottom: 8px;">🛡️</div>
        <div class="font-semibold text-sm">{{ __('Security') }}</div>
        <div class="text-xs text-muted">{{ __('Password, 2FA, logs') }}</div>
    </a>
    <a href="{{ route('profile.kyc') }}" class="card p-4" style="text-decoration: none; text-align: center; border-left: 3px solid #f59e0b;">
        <div style="font-size: 24px; margin-bottom: 8px;">📄</div>
        <div class="font-semibold text-sm">{{ __('KYC Documents') }}</div>
        <div class="text-xs text-muted">{{ auth()->user()->isKycVerified() ? '✅ ' . __('Verified') : '⏳ ' . __('Pending') }}</div>
    </a>
    <a href="{{ route('profile.two-factor') }}" class="card p-4" style="text-decoration: none; text-align: center; border-left: 3px solid #8b5cf6;">
        <div style="font-size: 24px; margin-bottom: 8px;">🔐</div>
        <div class="font-semibold text-sm">{{ __('Two-Factor') }}</div>
        <div class="text-xs text-muted">{{ auth()->user()->two_factor_enabled ? '✅ ' . __('Enabled') : '❌ ' . __('Disabled') }}</div>
    </a>
    <a href="{{ route('cards.index') }}" class="card p-4" style="text-decoration: none; text-align: center; border-left: 3px solid #22c55e;">
        <div style="font-size: 24px; margin-bottom: 8px;">💳</div>
        <div class="font-semibold text-sm">{{ __('My Cards') }}</div>
        <div class="text-xs text-muted">{{ auth()->user()->cards()->count() }} {{ __('cards') }}</div>
    </a>
</div>

{{-- Account Summary --}}
<div class="grid-2 mb-6" style="align-items: start;">

    {{-- Identity Documents --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-4">📄 {{ __('Identity Documents') }}</h2>
        @php
            $kycStatus = auth()->user()->isKycVerified();
        @endphp
        <div class="flex-between mb-4">
            <span class="text-sm text-muted">{{ __('KYC Status') }}</span>
            <span class="badge {{ $kycStatus ? 'badge-success' : 'badge-warning' }}">{{ $kycStatus ? '✅ ' . __('Verified') : '⏳ ' . __('Pending') }}</span>
        </div>
        @if($kycDocuments->count() > 0)
            @foreach($kycDocuments->take(4) as $doc)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
                    <div>
                        <span class="text-sm font-medium">{{ $doc->document_type_label }}</span>
                        <span class="text-xs text-muted"> — {{ $doc->created_at->format('M d, Y') }}</span>
                    </div>
                    <span class="badge badge-{{ $doc->status === 'verified' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }}" style="font-size: 10px;">
                        {{ __(ucfirst($doc->status)) }}
                    </span>
                </div>
            @endforeach
        @endif
        <a href="{{ route('profile.kyc') }}" class="btn btn-secondary w-full" style="margin-top: 12px;">📤 {{ __('Upload / Manage Documents') }}</a>
    </div>

    {{-- Card PIN Management --}}
    <div>
        @if($cards->count() > 0)
            <div class="card p-6 mb-4 animate-fade-in-up">
                <h2 class="section-title mb-4">🔐 {{ __('Card PIN Management') }}</h2>
                <p class="text-sm text-muted mb-4">{{ __('For security, PIN changes are processed by an admin.') }}</p>
                @foreach($cards as $card)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
                        <div>
                            <span class="text-sm font-medium">{{ ucfirst($card->card_brand) }} •••• {{ $card->card_number_last4 }}</span>
                            <span class="badge {{ $card->status_badge_class }}" style="font-size: 9px; margin-left: 6px;">{{ $card->status_label }}</span>
                        </div>
                        <a href="{{ route('cards.request-pin-change', $card) }}" class="btn btn-ghost btn-sm">{{ __('Request PIN Change') }}</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Settings Section (Hidden by Default) --}}
<div id="settings-section" style="display: {{ $errors->any() ? 'block' : 'none' }};">
    <h2 class="section-title mb-4">⚙️ {{ __('Account Settings') }}</h2>

    <div class="grid-2" style="align-items: start;">
        {{-- Personal Information --}}
        <div class="card p-6 animate-fade-in-up">
            <h3 class="section-title mb-4">{{ __('Personal Information') }}</h3>
            <form method="POST" action="{{ route('profile.update') }}" data-loading>
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">{{ __('Full Name') }}</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required data-validate>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" class="form-input" value="{{ $user->email }}" disabled style="opacity: 0.6;">
                    <div class="form-hint">{{ __('Email cannot be changed.') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input type="tel" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Address') }}</label>
                    <input type="text" name="address_line_1" class="form-input" value="{{ old('address_line_1', $user->address_line_1) }}" placeholder="{{ __('Street address') }}">
                </div>
                <div class="form-group">
                    <input type="text" name="address_line_2" class="form-input" value="{{ old('address_line_2', $user->address_line_2) }}" placeholder="{{ __('Apt, suite, etc.') }}">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">{{ __('City') }}</label>
                        <input type="text" name="city" class="form-input" value="{{ old('city', $user->city) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('State') }}</label>
                        <input type="text" name="state" class="form-input" value="{{ old('state', $user->state) }}">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">{{ __('Country') }}</label>
                        <input type="text" name="country" class="form-input" value="{{ old('country', $user->country) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Postal Code') }}</label>
                        <input type="text" name="postal_code" class="form-input" value="{{ old('postal_code', $user->postal_code) }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full">{{ __('Save Changes') }}</button>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="card p-6 animate-fade-in-up">
            <h3 class="section-title mb-4">{{ __('Change Password') }}</h3>
            <form method="POST" action="{{ route('profile.update-password') }}" data-loading>
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">{{ __('Current Password') }}</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" class="form-input" required>
                        <button type="button" class="password-toggle" aria-label="Toggle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('New Password') }}</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" class="form-input" required data-strength="pw-strength">
                        <button type="button" class="password-toggle" aria-label="Toggle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div id="pw-strength"></div>
                    <div class="form-hint">{{ __('Min. 8 characters with uppercase, number, and symbol.') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Confirm New Password') }}</label>
                    <div class="password-wrapper">
                        <input type="password" name="password_confirmation" class="form-input" required>
                        <button type="button" class="password-toggle" aria-label="Toggle">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full">{{ __('Update Password') }}</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleSettings() {
    const settings = document.getElementById('settings-section');
    const btnText = document.getElementById('settings-btn-text');

    if (settings.style.display === 'none') {
        settings.style.display = 'block';
        btnText.textContent = '{{ __("Hide Settings") }}';
        settings.scrollIntoView({ behavior: 'smooth' });
    } else {
        settings.style.display = 'none';
        btnText.textContent = '{{ __("Edit Profile") }}';
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const original = btn.textContent;
        btn.textContent = '✅';
        setTimeout(() => btn.textContent = original, 1500);
    });
}
</script>
@endsection
