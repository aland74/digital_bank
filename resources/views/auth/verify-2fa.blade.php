@extends('layouts.guest')
@section('title', 'Two-Factor Verification')

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
                <span style="font-size:28px;">🔒</span>
            </div>

            <h2 style="font-size:20px;font-weight:700;color:var(--text-primary);margin:0 0 8px;">{{ __('Two-Factor Verification') }}</h2>
            <p style="font-size:14px;color:var(--text-muted);margin:0 0 24px;line-height:1.5;">
                {{ __('Enter the 6-digit code from your authenticator app') }}
            </p>

            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom:16px;text-align:left;">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('auth.2fa.verify.submit') }}">
                @csrf

                <div class="form-group" style="margin-bottom:24px;">
                    <div style="display:flex;justify-content:center;gap:8px;">
                        <input type="text" name="code" class="form-input" style="text-align:center;font-size:28px;font-weight:700;letter-spacing:8px;width:200px;" maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" autofocus required placeholder="000000">
                    </div>
                    @error('code')
                        <span class="form-error" style="display:block;margin-top:8px;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full" style="padding:12px;">
                    {{ __('Verify') }}
                </button>
            </form>

            <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);">
                <a href="{{ route('login') }}" style="font-size:13px;color:var(--text-muted);text-decoration:none;">
                    ← {{ __('Back to login') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
