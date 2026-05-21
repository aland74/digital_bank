@extends('layouts.app')
@section('title', 'Two-Factor Authentication')

@section('content')
<div class="animate-fade-in-up">
    <div class="section-header">
        <div>
            <h1 class="section-title">Two-Factor Authentication</h1>
            <p class="text-sm text-muted mt-1">Add an extra layer of security to your account</p>
        </div>
        <a href="{{ route('profile.security') }}" class="btn btn-secondary btn-sm">← Back to Security</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            @if($user->two_factor_enabled)
                {{-- 2FA is enabled --}}
                <div style="text-align:center;padding:24px 0;">
                    <div style="width:64px;height:64px;border-radius:50%;background:var(--success-50);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:28px;">🔒</div>
                    <h3 style="font-size:18px;font-weight:700;color:var(--text-primary);margin-bottom:8px;">Two-Factor Authentication is Enabled</h3>
                    <p style="font-size:14px;color:var(--text-muted);margin-bottom:24px;">Your account is protected with an authenticator app.</p>

                    <form method="POST" action="{{ route('profile.two-factor.disable') }}" style="display:inline-block;">
                        @csrf
                        <div class="form-group" style="text-align:left;max-width:300px;margin:0 auto 16px;">
                            <label class="form-label">Confirm your password to disable 2FA</label>
                            <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                            @error('password')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to disable two-factor authentication?')">
                            Disable Two-Factor Authentication
                        </button>
                    </form>
                </div>
            @else
                {{-- 2FA setup --}}
                <div>
                    <div style="text-align:center;margin-bottom:24px;">
                        <div style="width:64px;height:64px;border-radius:50%;background:var(--primary-50);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;font-size:28px;">📱</div>
                        <h3 style="font-size:18px;font-weight:700;color:var(--text-primary);margin-bottom:8px;">Set Up Two-Factor Authentication</h3>
                        <p style="font-size:14px;color:var(--text-muted);">Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.)</p>
                    </div>

                    {{-- QR Code --}}
                    <div style="text-align:center;padding:24px;background:var(--bg-page);border-radius:var(--radius-lg);margin-bottom:24px;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code" style="width: 200px; height: 200px; border-radius: 8px;">
                    </div>

                    {{-- Secret Key --}}
                    <div style="margin-bottom:24px;">
                        <label class="form-label">Or enter this secret key manually:</label>
                        <div style="padding:12px;background:var(--bg-page);border-radius:var(--radius-md);font-family:monospace;font-size:14px;letter-spacing:2px;text-align:center;color:var(--text-primary);">
                            {{ $secretKey }}
                        </div>
                    </div>

                    {{-- Verification Form --}}
                    <form method="POST" action="{{ route('profile.two-factor.enable') }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Enter the 6-digit code from your authenticator app</label>
                            <input type="text" name="code" class="form-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" required style="text-align:center;font-size:24px;letter-spacing:8px;font-weight:700;">
                            @error('code')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-full">
                            Verify and Enable Two-Factor Authentication
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
