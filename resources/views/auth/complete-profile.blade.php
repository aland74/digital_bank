@extends('layouts.guest')
@section('title', 'Complete Profile')

@section('content')
<div class="auth-wrapper">
    <div class="auth-left">
        <div class="auth-form-container animate-fade-in-up">
            <div class="auth-logo">
                <div class="auth-logo-icon">N</div>
                <span class="auth-logo-text">Distributed Bank</span>
            </div>

            <h1 class="auth-title">{{ __('Select Your Branch') }}</h1>
            <p class="auth-subtitle">{{ __('You are authenticated! Choose your local city branch to create and configure your secure ledger nodes.') }}</p>

            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom: 24px;">
                    ⚠️ {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('auth.google.complete-profile') }}" data-loading>
                @csrf
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" style="color: var(--text-secondary); font-size: 13px;">Google Account</label>
                    <div class="google-profile-card">
                        <div class="google-profile-avatar">
                            @if(session('google_user_avatar'))
                                <img src="{{ session('google_user_avatar') }}" alt="Avatar">
                            @else
                                <span style="font-size: 16px; font-weight:700; color:var(--text-primary);">{{ strtoupper(substr(session('google_user_name', 'G'), 0, 1)) }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="google-profile-name">{{ session('google_user_name') }}</div>
                            <div class="google-profile-email">{{ session('google_user_email') }}</div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 28px;">
                    <label class="form-label" for="branch">{{ __('Select City / Branch') }} <span style="color: var(--accent); font-weight: 600;">*</span></label>
                    <div>
                        <select id="branch" name="branch" class="form-select" required>
                            <option value="" disabled selected>{{ __('Select your city…') }}</option>
                            <option value="erbil">🏙️ {{ __('Erbil') }}</option>
                            <option value="sulaimaniyah">🏔️ {{ __('Sulaimaniyah') }}</option>
                            <option value="duhok">🌄 {{ __('Duhok') }}</option>
                        </select>
                    </div>
                    <small style="color:var(--text-muted);font-size:12px;margin-top:6px;display:block;">{{ __('This binds your distributed digital ledger partition to the chosen regional node.') }}</small>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <span class="btn-text">{{ __('Complete Profile & Sign In →') }}</span>
                </button>
            </form>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-promo">
            <h2 class="auth-promo-title">{{ __('Choose') }}<br><span class="text-gradient">{{ __('Your Node') }}</span></h2>
            <p class="auth-promo-text">{{ __('Your banking records are secure, synchronized globally, and accessed through local lightning-fast SQLite partitions.') }}</p>

            <div class="auth-promo-features">
                <div class="auth-promo-feature">
                    <div class="auth-promo-feature-icon">🏙️</div>
                    <span>{{ __('Erbil Branch Node') }}</span>
                </div>
                <div class="auth-promo-feature">
                    <div class="auth-promo-feature-icon">🏔️</div>
                    <span>{{ __('Sulaimaniyah Branch Node') }}</span>
                </div>
                <div class="auth-promo-feature">
                    <div class="auth-promo-feature-icon">🌄</div>
                    <span>{{ __('Duhok Branch Node') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
