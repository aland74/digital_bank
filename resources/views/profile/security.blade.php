@extends('layouts.app')
@section('title', __('Security Center'))
@section('page-title', '🛡️ ' . __('Security Center'))
@section('page-subtitle', __('Monitor and protect your account'))

@section('content')
<div class="grid-2" style="align-items: start;">

    {{-- LEFT: Security Overview --}}
    <div>
        <div class="card p-6 mb-4 animate-fade-in-up">
            <h2 class="section-title mb-4">🔐 {{ __('Security Status') }}</h2>

            {{-- Two-Factor --}}
            <div style="padding: 16px; background: {{ $user->two_factor_enabled ? 'rgba(34, 197, 94, 0.05)' : 'rgba(239, 68, 68, 0.05)' }}; border-radius: var(--radius-md); border-left: 3px solid {{ $user->two_factor_enabled ? '#22c55e' : '#ef4444' }}; margin-bottom: 12px;">
                <div class="flex-between">
                    <div>
                        <div class="font-semibold text-sm">🔐 {{ __('Two-Factor Authentication') }}</div>
                        <div class="text-xs text-muted">{{ $user->two_factor_enabled ? __('Your account is protected with 2FA') : __('Add an extra layer of security') }}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge {{ $user->two_factor_enabled ? 'badge-success' : 'badge-danger' }}">
                            {{ $user->two_factor_enabled ? '✅ ' . __('Enabled') : '❌ ' . __('Disabled') }}
                        </span>
                        <a href="{{ route('profile.two-factor') }}" class="btn btn-sm btn-secondary">
                            {{ $user->two_factor_enabled ? __('Manage') : __('Enable') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- Email --}}
            <div style="padding: 16px; background: var(--bg-secondary); border-radius: var(--radius-md); margin-bottom: 12px;">
                <div class="flex-between">
                    <div>
                        <div class="font-semibold text-sm">📧 {{ __('Email Address') }}</div>
                        <div class="text-xs text-muted">{{ $user->email }}</div>
                    </div>
                    <span class="badge {{ $user->email_verified_at ? 'badge-success' : 'badge-warning' }}">
                        {{ $user->email_verified_at ? '✅ ' . __('Verified') : '⏳ ' . __('Pending') }}
                    </span>
                </div>
            </div>

            {{-- Password --}}
            <div style="padding: 16px; background: var(--bg-secondary); border-radius: var(--radius-md); margin-bottom: 12px;">
                <div class="flex-between">
                    <div>
                        <div class="font-semibold text-sm">🔑 {{ __('Password') }}</div>
                        <div class="text-xs text-muted">{{ __('Last changed') }}: {{ $user->updated_at->format('M d, Y') }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-ghost" onclick="toggleSettings()">{{ __('Change') }}</a>
                </div>
            </div>

            {{-- Last Login --}}
            <div style="padding: 16px; background: var(--bg-secondary); border-radius: var(--radius-md); margin-bottom: 12px;">
                <div class="flex-between">
                    <div>
                        <div class="font-semibold text-sm">🖥️ {{ __('Last Login') }}</div>
                        <div class="text-xs text-muted">{{ $user->last_login_at?->format('M d, Y h:i A') ?? __('Never') }}</div>
                    </div>
                    <span class="text-xs text-muted">{{ $user->last_login_ip }}</span>
                </div>
            </div>

            {{-- Account Status --}}
            <div style="padding: 16px; background: var(--bg-secondary); border-radius: var(--radius-md);">
                <div class="flex-between">
                    <div>
                        <div class="font-semibold text-sm">👤 {{ __('Account Status') }}</div>
                        <div class="text-xs text-muted">{{ __('Your account is') }} {{ $user->status }}</div>
                    </div>
                    <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'warning' }}">
                        {{ __(ucfirst($user->status)) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card p-6 animate-fade-in-up">
            <h2 class="section-title mb-4">⚡ {{ __('Quick Actions') }}</h2>
            <div style="display: grid; gap: 8px;">
                <a href="{{ route('profile.two-factor') }}" class="btn btn-secondary w-full" style="justify-content: flex-start;">🔐 {{ __('Manage Two-Factor Authentication') }}</a>
                <a href="{{ route('profile.kyc') }}" class="btn btn-secondary w-full" style="justify-content: flex-start;">📄 {{ __('Upload Identity Documents') }}</a>
                <a href="{{ route('profile.edit') }}" class="btn btn-secondary w-full" style="justify-content: flex-start;">👤 {{ __('Edit Personal Information') }}</a>
            </div>
        </div>
    </div>

    {{-- RIGHT: Recent Activity --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-4">📋 {{ __('Recent Activity') }}</h2>
        <div style="max-height: 500px; overflow-y: auto;">
            @forelse($recentLogs as $log)
                @php
                    $severityColors = [
                        'critical' => '#ef4444',
                        'high' => '#f97316',
                        'medium' => '#f59e0b',
                        'low' => '#22c55e',
                    ];
                    $severityIcons = [
                        'critical' => '🚨',
                        'high' => '⚠️',
                        'medium' => '📋',
                        'low' => 'ℹ️',
                    ];
                    $color = $severityColors[$log->severity] ?? '#6b7280';
                    $icon = $severityIcons[$log->severity] ?? '📋';
                @endphp
                <div style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--border);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; background: {{ $color }}15; color: {{ $color }};">
                        {{ $icon }}
                    </div>
                    <div style="flex: 1;">
                        <div class="flex-between mb-1">
                            <span class="text-sm font-semibold">{{ __(ucfirst(str_replace('_', ' ', $log->action))) }}</span>
                            <span style="padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; background: {{ $color }}15; color: {{ $color }};">
                                {{ __(ucfirst($log->severity)) }}
                            </span>
                        </div>
                        <div class="text-xs text-muted">
                            {{ $log->created_at->format('M d, Y h:i A') }} · {{ $log->ip_address }}
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; padding: 40px 0;">
                    <div style="font-size: 48px; margin-bottom: 12px;">📋</div>
                    <p class="text-muted">{{ __('No recent activity.') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Back --}}
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
            <a href="{{ route('profile.edit') }}" class="btn btn-ghost w-full">← {{ __('Back to Profile') }}</a>
        </div>
    </div>
</div>
@endsection
