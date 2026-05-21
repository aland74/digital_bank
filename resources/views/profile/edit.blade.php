@extends('layouts.app')
@section('title', __('User Account'))
@section('page-title', '👤 ' . __('User Account'))
@section('page-subtitle', __('View and manage your account details'))

@section('content')
{{-- KYC Banner for unverified users --}}
@if(!auth()->user()->isKycVerified())
    <div class="alert alert-warning animate-fade-in-up" style="margin-bottom:24px;">
        🔒 <strong>{{ __('Account Pending Verification') }}</strong> — {{ __('Upload your Passport and National ID to unlock all features and activate cards.') }}
        <a href="{{ route('profile.kyc') }}" class="btn btn-ghost btn-sm" style="margin-left:8px;vertical-align:middle;">{{ __('Upload Documents →') }}</a>
    </div>
@endif

{{-- User Profile Header --}}
<div class="card p-6 mb-6 animate-fade-in-up">
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:20px;">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--gradient-primary);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:bold;color:white;box-shadow:0 0 15px rgba(59,130,246,0.3);flex-shrink:0;">
            {{ auth()->user()->initials }}
        </div>
        <div style="flex:1;min-width:250px;">
            <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;color:var(--text-primary);">{{ auth()->user()->name }}</h1>
            <div class="text-muted text-sm mb-3" style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
                <span>📧 {{ auth()->user()->email }}</span>
                <span>📱 {{ auth()->user()->phone ?? __('No phone added') }}</span>
                <span>🏦 {{ auth()->user()->branch_display_name }}</span>
            </div>
            @if($primaryAccount = auth()->user()->primaryAccount())
                <div class="text-sm font-medium mb-3" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.05);padding:6px 12px;border-radius:20px;border:1px solid var(--border);color:var(--text-primary);">
                    <span>💳 {{ __('Primary Account') }}: <strong>{{ $primaryAccount->account_number }}</strong></span>
                    <button type="button" class="btn btn-ghost btn-sm" style="padding:2px 6px;margin:0;min-width:auto;height:auto;line-height:1;display:inline-flex;align-items:center;justify-content:center;" onclick="copyToClipboard('{{ $primaryAccount->account_number }}')" title="{{ __('Copy Account Number') }}">
                        📋
                    </button>
                </div>
            @endif
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="badge {{ auth()->user()->isKycVerified() ? 'badge-success' : 'badge-warning' }}" style="padding:6px 12px;">
                    {{ auth()->user()->isKycVerified() ? __('Fully Verified Account') : __('Verification Pending') }}
                </span>
                <span class="badge badge-info" style="padding:6px 12px;text-transform:uppercase;">
                    {{ __(ucfirst(auth()->user()->role)) }}
                </span>
            </div>
        </div>
        <div>
             <button onclick="toggleSettings()" class="btn btn-secondary" style="padding:10px 20px;">⚙️ <span id="settings-btn-text">{{ __('Account Settings') }}</span></button>
        </div>
    </div>
</div>

{{-- Read-Only Account Summary --}}
<div class="grid-2" style="align-items:start; margin-bottom: 24px;" id="summary-section">
    {{-- KYC Documents --}}
    <div class="card p-6 animate-fade-in-up delay-100">
        <h2 class="section-title mb-4">📄 {{ __('Identity Documents') }}</h2>
        @php
            $kycStatus = auth()->user()->isKycVerified();
        @endphp
        <div class="flex-between mb-4">
            <span class="text-sm text-muted">{{ __('KYC Status') }}</span>
            <span class="badge {{ $kycStatus ? 'badge-success' : 'badge-warning' }}">{{ $kycStatus ? __('Verified') : __('Pending') }}</span>
        </div>
        @if($kycDocuments->count() > 0)
            @foreach($kycDocuments->take(4) as $doc)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                    <div>
                        <span class="text-sm font-medium">{{ $doc->document_type_label }}</span>
                        <span class="text-xs text-muted">— {{ $doc->created_at->format('M d, Y') }}</span>
                    </div>
                    <span class="badge badge-{{ $doc->status === 'verified' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }}" style="font-size:10px;">
                        {{ __(ucfirst($doc->status)) }}
                    </span>
                </div>
            @endforeach
        @endif
        <a href="{{ route('profile.kyc') }}" class="btn btn-secondary w-full" style="margin-top:12px;">📤 {{ __('Upload / Manage Documents') }}</a>
    </div>

    <div>
        {{-- Card PIN Management --}}
        @if($cards->count() > 0)
        <div class="card p-6 animate-fade-in-up delay-200 mb-6">
            <h2 class="section-title mb-4">🔐 {{ __('Card PIN Management') }}</h2>
            <p class="text-sm text-muted mb-4">{{ __('For security, PIN changes are processed by an admin. Submit a request below.') }}</p>
            @foreach($cards as $card)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
                    <div>
                        <span class="text-sm font-medium">{{ ucfirst($card->card_brand) }} •••• {{ $card->card_number_last4 }}</span>
                        <span class="badge {{ $card->status_badge_class }}" style="font-size:9px;margin-left:6px;">{{ $card->status_label }}</span>
                    </div>
                    <a href="{{ route('cards.request-pin-change', $card) }}" class="btn btn-ghost btn-sm">{{ __('Request PIN Change') }}</a>
                </div>
            @endforeach
        </div>
        @endif

        <div class="card p-6 animate-fade-in-up delay-300">
            <h2 class="section-title mb-4">{{ __('Security Links') }}</h2>
            <a href="{{ route('profile.security') }}" class="btn btn-secondary w-full">🛡️ {{ __('View Security & Audit Log') }}</a>
        </div>
    </div>
</div>

{{-- Hidden Settings Section --}}
<div id="settings-section" style="display:{{ $errors->any() ? 'block' : 'none' }};">
    <h2 class="section-title mb-6">⚙️ {{ __('Account Settings') }}</h2>
    
    <div class="grid-2" style="align-items:start;">
        {{-- Personal Information Form --}}
        <div class="card p-6">
            <h2 class="section-title mb-6">{{ __('Personal Information') }}</h2>
            <form method="POST" action="{{ route('profile.update') }}" data-loading>
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">{{ __('Full Name') }}</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required data-validate>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" class="form-input" value="{{ $user->email }}" disabled style="opacity:0.6;">
                    <div class="form-hint">{{ __('Email cannot be changed. Contact support if needed.') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Phone') }}</label>
                    <input type="tel" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Address Line 1') }}</label>
                    <input type="text" name="address_line_1" class="form-input" value="{{ old('address_line_1', $user->address_line_1) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Address Line 2') }}</label>
                    <input type="text" name="address_line_2" class="form-input" value="{{ old('address_line_2', $user->address_line_2) }}">
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
                <button type="submit" class="btn btn-primary"><span class="btn-text">{{ __('Save Changes') }}</span></button>
            </form>
        </div>

        {{-- Change Password Form --}}
        <div class="card p-6">
            <h2 class="section-title mb-6">{{ __('Change Password') }}</h2>
            <form method="POST" action="{{ route('profile.update-password') }}" data-loading>
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">{{ __('Current Password') }}</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" class="form-input" required>
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('New Password') }}</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" class="form-input" required data-strength="profile-password-strength">
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div id="profile-password-strength"></div>
                    <div class="form-hint">{{ __('Min. 8 characters with uppercase, number, and symbol.') }}</div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Confirm New Password') }}</label>
                    <div class="password-wrapper">
                        <input type="password" name="password_confirmation" class="form-input" required>
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><span class="btn-text">{{ __('Update Password') }}</span></button>
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
            btnText.textContent = '{{ __('Hide Settings') }}';
            settings.scrollIntoView({ behavior: 'smooth' });
        } else {
            settings.style.display = 'none';
            btnText.textContent = '{{ __('Account Settings') }}';
        }
    }
</script>
@endsection
