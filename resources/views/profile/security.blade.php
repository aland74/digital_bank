@extends('layouts.app')
@section('title', __('Security'))
@section('page-title', __('Security Center'))
@section('page-subtitle', __('Monitor your account security'))

@section('content')
<div class="grid-2" style="align-items:start;">
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">🔐 {{ __('Security Overview') }}</h2>
        <div style="display:grid;gap:16px;">
            <div class="card p-4">
                <div class="flex-between">
                    <div><div class="font-medium">{{ __('Two-Factor Authentication') }}</div><div class="text-sm text-muted">{{ __('Extra layer of security for your account') }}</div></div>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span class="badge {{ $user->two_factor_enabled ? 'badge-success' : 'badge-danger' }}">
                            {{ $user->two_factor_enabled ? __('Enabled') : __('Disabled') }}
                        </span>
                        <a href="{{ route('profile.two-factor') }}" class="btn btn-sm btn-secondary">
                            {{ $user->two_factor_enabled ? __('Manage') : __('Enable') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="card p-4">
                <div class="flex-between">
                    <div><div class="font-medium">{{ __('Email Verified') }}</div><div class="text-sm text-muted">{{ $user->email }}</div></div>
                    <span class="badge {{ $user->email_verified_at ? 'badge-success' : 'badge-warning' }}">
                        {{ $user->email_verified_at ? __('Verified') : __('Pending') }}
                    </span>
                </div>
            </div>
            <div class="card p-4">
                <div class="flex-between">
                    <div><div class="font-medium">{{ __('Last Login') }}</div><div class="text-sm text-muted">{{ $user->last_login_at?->format('M d, Y h:i A') ?? __('Never') }}</div></div>
                    <span class="text-sm text-muted">{{ $user->last_login_ip }}</span>
                </div>
            </div>
            <div class="card p-4">
                <div class="flex-between">
                    <div><div class="font-medium">{{ __('Account Status') }}</div></div>
                    <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'warning' }}">{{ __(ucfirst($user->status)) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-6 animate-fade-in-up delay-100">
        <h2 class="section-title mb-4">📋 {{ __('Recent Activity') }}</h2>
        <div style="max-height:400px;overflow-y:auto;">
            @forelse($recentLogs as $log)
                <div style="padding:10px 0;border-bottom:1px solid var(--border);">
                    <div class="flex-between">
                        <span class="text-sm font-medium">{{ $log->action }}</span>
                        <span class="badge badge-{{ $log->severity === 'critical' ? 'danger' : ($log->severity === 'high' ? 'warning' : 'neutral') }}" style="font-size:9px;">
                            {{ __(ucfirst($log->severity)) }}
                        </span>
                    </div>
                    <div class="text-xs text-muted mt-1">
                        {{ $log->created_at->format('M d, Y h:i A') }} · {{ $log->ip_address }}
                    </div>
                </div>
            @empty
                <p class="text-muted text-sm">{{ __('No recent activity.') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
