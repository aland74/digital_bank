@extends('layouts.guest')
@section('title', 'Verify Email')

@section('content')
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:var(--bg-primary);">
    <div style="width:100%;max-width:420px;">
        {{-- Logo --}}
        <div style="text-align:center;margin-bottom:32px;">
            <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,var(--primary),#6366f1);display:inline-flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:24px;margin-bottom:12px;">N</div>
            <h1 style="font-size:24px;font-weight:700;color:var(--text-primary);margin:0;">NexusBank</h1>
        </div>

        {{-- Card --}}
        <div class="card" style="padding:32px;text-align:center;">
            {{-- Icon --}}
            <div style="width:64px;height:64px;border-radius:50%;background:rgba(var(--primary-rgb),0.1);display:inline-flex;align-items:center;justify-content:center;margin-bottom:20px;">
                <span style="font-size:28px;">📧</span>
            </div>

            <h2 style="font-size:20px;font-weight:700;color:var(--text-primary);margin:0 0 8px;">{{ __('Verify your email') }}</h2>
            <p style="font-size:14px;color:var(--text-muted);margin:0 0 24px;line-height:1.5;">
                {{ __('We sent a 6-digit code to') }}<br>
                <strong style="color:var(--text-primary);">{{ $user->email }}</strong>
            </p>

            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom:16px;text-align:left;">{{ session('success') }}</div>
            @endif

            {{-- Dev: Show OTP for testing (debug mode only) --}}
            @if(session('debug_otp'))
                <div style="margin-bottom:16px;padding:12px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:var(--radius);text-align:center;">
                    <div style="font-size:11px;color:var(--text-muted);margin-bottom:4px;">🔧 {{ __('Test Mode — Your Code') }}</div>
                    <div style="font-size:32px;font-weight:800;letter-spacing:8px;color:#22c55e;">{{ session('debug_otp') }}</div>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom:16px;text-align:left;">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('auth.verify-otp.submit') }}">
                @csrf

                {{-- OTP Input --}}
                <div class="form-group">
                    <label class="form-label" style="text-align:left;">{{ __('Verification Code') }}</label>
                    <input
                        type="text"
                        name="otp"
                        class="form-input"
                        style="text-align:center;font-size:28px;font-weight:700;letter-spacing:12px;padding:16px;"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        placeholder="000000"
                        autocomplete="one-time-code"
                        required
                        autofocus
                    >
                    <div style="font-size:12px;color:var(--text-muted);margin-top:8px;text-align:left;">
                        {{ __('Enter the 6-digit code from your email') }}
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px;">
                    <span class="btn-text">{{ __('Verify Email') }}</span>
                </button>
            </form>

            {{-- Resend --}}
            <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
                <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;">{{ __("Didn't receive the code?") }}</p>
                <form method="POST" action="{{ route('auth.verify-otp.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">
                        {{ __('Resend Code') }}
                    </button>
                </form>
            </div>

            {{-- Sandbox Info --}}
            <div style="margin-top:20px;padding:12px;background:rgba(var(--primary-rgb),0.05);border-radius:var(--radius);border:1px dashed rgba(var(--primary-rgb),0.2);">
                <p style="font-size:12px;color:var(--text-muted);margin:0;">
                    🔧 <strong>{{ __('Dev Mode') }}:</strong> {{ __('Code is logged to') }} <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:11px;">storage/logs/laravel.log</code>
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <p style="text-align:center;font-size:14px;color:var(--text-muted);margin-top:20px;">
            <a href="{{ route('login') }}" style="color:var(--primary);text-decoration:none;font-weight:500;">← {{ __('Back to sign in') }}</a>
        </p>
    </div>
</div>
@endsection
