@extends('layouts.guest')
@section('title', 'Sign In')

@section('content')
<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: var(--bg-primary);">
    <div style="width: 100%; max-width: 420px;">

        {{-- Logo --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="width: 64px; height: 64px; border-radius: 20px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: inline-flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 28px; margin-bottom: 16px; box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);">
                N
            </div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px;">Distributed Bank</h1>
            <p style="font-size: 14px; color: var(--text-muted); margin: 0;">{{ __('Secure Digital Banking') }}</p>
        </div>

        {{-- Card --}}
        <div class="card" style="padding: 32px;">
            <h2 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin: 0 0 4px;">{{ __('Welcome back') }} 👋</h2>
            <p style="font-size: 14px; color: var(--text-muted); margin: 0 0 24px;">{{ __('Sign in to access your account') }}</p>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning" style="margin-bottom: 16px;">{{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom: 16px;">{{ $errors->first() }}</div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" data-loading>
                @csrf

                <div class="form-group">
                    <label class="form-label">{{ __('Email Address') }}</label>
                    <input type="email" name="email" class="form-input" placeholder="you@example.com" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Password') }}</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" class="form-input" placeholder="{{ __('Enter your password') }}" required>
                        <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); cursor: pointer;">
                        <input type="checkbox" name="remember" style="accent-color: var(--primary);"> {{ __('Remember me') }}
                    </label>
                    <a href="#" style="font-size: 13px; color: var(--primary); font-weight: 500; text-decoration: none;">{{ __('Forgot password?') }}</a>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <span class="btn-text">{{ __('Sign In') }}</span>
                </button>
            </form>

            {{-- Divider --}}
            <div style="display: flex; align-items: center; gap: 12px; margin: 24px 0;">
                <div style="flex: 1; height: 1px; background: var(--border);"></div>
                <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;">{{ __('OR') }}</span>
                <div style="flex: 1; height: 1px; background: var(--border);"></div>
            </div>

            {{-- Google --}}
            @php
                $hasGoogleConfig = !empty(config('services.google.client_id')) && !empty(config('services.google.client_secret'));
            @endphp
            @if($hasGoogleConfig)
                <a href="{{ route('auth.google.redirect') }}" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px; font-weight: 500; color: var(--text-primary); text-decoration: none; transition: all 0.2s;">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    {{ __('Continue with Google') }}
                </a>
            @else
                <button type="button" onclick="document.getElementById('google-sandbox-modal').style.display='flex'" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px 16px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px; font-weight: 500; color: var(--text-primary); background: var(--bg-primary); cursor: pointer; transition: all 0.2s;">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    {{ __('Continue with Google') }}
                </button>
            @endif
        </div>

        {{-- Footer --}}
        <p style="text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 24px;">
            {{ __("Don't have an account?") }} <a href="{{ route('register') }}" style="font-weight: 600; color: var(--primary); text-decoration: none;">{{ __('Create one') }}</a>
        </p>
    </div>
</div>

{{-- Google Sandbox Modal --}}
<div id="google-sandbox-modal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
    <div style="background: var(--bg-white, #fff); border-radius: 16px; padding: 28px; width: 100%; max-width: 380px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px; font-weight: 700; margin: 0;">{{ __('Google Sign-In') }}</h3>
            <button type="button" onclick="document.getElementById('google-sandbox-modal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
            <button type="button" onclick="selectSandbox('aland.developer@gmail.com','Aland Developer','erbil')" style="display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid var(--border); border-radius: var(--radius); background: var(--bg-primary); cursor: pointer; text-align: left;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">AD</div>
                <div>
                    <div style="font-weight: 600; font-size: 14px;">Aland Developer</div>
                    <div style="font-size: 12px; color: var(--text-muted);">aland.developer@gmail.com</div>
                </div>
            </button>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; margin: 16px 0;">
            <div style="flex: 1; height: 1px; background: var(--border);"></div>
            <span style="font-size: 12px; color: var(--text-muted);">{{ __('or custom') }}</span>
            <div style="flex: 1; height: 1px; background: var(--border);"></div>
        </div>

        <form id="sandbox-form" method="POST" action="{{ route('auth.google.callback.simulated') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('Name') }}</label>
                <input type="text" name="name" class="form-input" placeholder="Your Name" required>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('Email') }}</label>
                <input type="email" name="email" class="form-input" placeholder="you@gmail.com" required>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('Branch') }}</label>
                <select name="branch" class="form-select" required>
                    <option value="">{{ __('Select...') }}</option>
                    <option value="erbil">🏙️ Erbil</option>
                    <option value="sulaimaniyah">🏔️ Sulaimaniyah</option>
                    <option value="duhok">🌄 Duhok</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-full">{{ __('Sign In') }}</button>
        </form>
    </div>
</div>

<script>
function selectSandbox(email, name, branch) {
    const form = document.getElementById('sandbox-form');
    form.querySelector('[name=email]').value = email;
    form.querySelector('[name=name]').value = name;
    form.querySelector('[name=branch]').value = branch;
    form.submit();
}
</script>
@endsection
