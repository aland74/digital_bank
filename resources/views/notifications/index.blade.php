@extends('layouts.app')
@section('title', __('Notifications'))
@section('page-title', '🔔 ' . __('Notifications'))
@section('page-subtitle', __('Stay updated with your account activity'))

@section('content')

{{-- Stats --}}
<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">🔔</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">{{ __('Total') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #f59e0b;">
        <div class="stat-icon orange">📬</div>
        <div class="stat-value">{{ $stats['unread'] }}</div>
        <div class="stat-label">{{ __('Unread') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon red">⚡</div>
        <div class="stat-value">{{ $stats['actionable'] }}</div>
        <div class="stat-label">{{ __('Action Required') }}</div>
    </div>
</div>

{{-- Header --}}
<div class="flex-between mb-4">
    <h2 class="section-title">{{ __('All Notifications') }}</h2>
    @if($stats['unread'] > 0)
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button class="btn btn-ghost btn-sm">✓ {{ __('Mark all as read') }}</button>
        </form>
    @endif
</div>

{{-- Filter Tabs --}}
<div class="filter-tabs mb-4 animate-fade-in-up">
    <a href="{{ route('notifications.index') }}" class="filter-tab {{ !request()->hasAny(['unread', 'type']) ? 'active' : '' }}">
        {{ __('All') }}
    </a>
    <a href="{{ route('notifications.index', ['unread' => 1]) }}" class="filter-tab {{ request('unread') ? 'active' : '' }}">
        📬 {{ __('Unread') }}
        @if($stats['unread'] > 0)
            <span class="badge badge-warning" style="margin-left: 4px;">{{ $stats['unread'] }}</span>
        @endif
    </a>
    <a href="{{ route('notifications.index', ['type' => 'transfer_request']) }}" class="filter-tab {{ request('type') === 'transfer_request' ? 'active' : '' }}">
        💸 {{ __('Transfers') }}
    </a>
    <a href="{{ route('notifications.index', ['type' => 'transaction']) }}" class="filter-tab {{ request('type') === 'transaction' ? 'active' : '' }}">
        💳 {{ __('Transactions') }}
    </a>
    <a href="{{ route('notifications.index', ['type' => 'security']) }}" class="filter-tab {{ request('type') === 'security' ? 'active' : '' }}">
        🔒 {{ __('Security') }}
    </a>
</div>

{{-- Notification List --}}
<div class="card animate-fade-in-up" style="overflow: hidden;">
    @forelse($notifications as $notif)
        @php
            $typeColors = [
                'success' => '#22c55e',
                'danger' => '#ef4444',
                'warning' => '#f59e0b',
                'info' => '#3b82f6',
                'transaction' => '#8b5cf6',
                'security' => '#ef4444',
                'transfer_request' => '#3b82f6',
                'kyc_required' => '#f59e0b',
            ];
            $typeIcons = [
                'success' => '✅',
                'danger' => '❌',
                'warning' => '⚠️',
                'info' => 'ℹ️',
                'transaction' => '💳',
                'security' => '🔒',
                'transfer_request' => '💸',
                'kyc_required' => '📄',
                'card_activated' => '💳',
                'card_frozen' => '❄️',
                'loan_approved' => '✅',
                'loan_rejected' => '❌',
            ];
            $color = $typeColors[$notif->type] ?? '#6b7280';
            $icon = $typeIcons[$notif->type] ?? $notif->type_icon ?? '🔔';
        @endphp
        <div style="display: flex; align-items: flex-start; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); {{ !$notif->is_read ? 'background: rgba(59, 130, 246, 0.03);' : '' }} {{ $notif->requiresAction() ? 'border-left: 3px solid #8b5cf6;' : 'border-left: 3px solid transparent;' }} transition: all 0.2s;">

            {{-- Icon --}}
            <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; background: {{ $color }}15; color: {{ $color }};">
                {{ $icon }}
            </div>

            {{-- Content --}}
            <div style="flex: 1; min-width: 0;">
                <div class="flex-between mb-1">
                    <div class="font-semibold text-sm" style="{{ !$notif->is_read ? 'color: var(--text-primary);' : 'color: var(--text-secondary);' }}">
                        {{ $notif->translated_title }}
                    </div>
                    <div class="flex align-items-center flex-gap-2">
                        @if(!$notif->is_read)
                            <div style="width: 8px; height: 8px; border-radius: 50%; background: {{ $color }}; flex-shrink: 0;"></div>
                        @endif
                        <span class="text-xs text-muted" style="white-space: nowrap;">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                <div class="text-sm text-muted mb-2" style="line-height: 1.5;">{{ $notif->translated_message }}</div>

                <div class="flex align-items-center flex-gap-3">
                    <span class="text-xs text-muted">{{ $notif->created_at->format('M d, Y · h:i A') }}</span>

                    {{-- Type Badge --}}
                    <span style="padding: 1px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; background: {{ $color }}15; color: {{ $color }};">
                        {{ __(ucfirst(str_replace('_', ' ', $notif->type))) }}
                    </span>
                </div>

                {{-- Action Buttons --}}
                @if($notif->requiresAction() && $notif->type === 'transfer_request')
                    @php $ptId = $notif->getPendingTransferId(); @endphp
                    @if($ptId)
                        <div class="mt-3">
                            <a href="{{ route('transfers.pending') }}" class="btn btn-primary btn-sm">💸 {{ __('View & Respond') }}</a>
                        </div>
                    @endif
                @endif
            </div>

            {{-- Mark as Read --}}
            @if(!$notif->is_read)
                <form method="POST" action="{{ route('notifications.read', $notif) }}" style="flex-shrink: 0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" style="padding: 4px 8px; font-size: 11px;" title="{{ __('Mark as read') }}">
                        ✓
                    </button>
                </form>
            @endif
        </div>
    @empty
        <div style="padding: 60px 20px; text-align: center;">
            <div style="font-size: 64px; margin-bottom: 16px;">🔔</div>
            <p class="font-semibold" style="font-size: 18px; margin-bottom: 4px;">{{ __('No notifications') }}</p>
            <p class="text-muted text-sm">{{ __('You\'re all caught up! New notifications will appear here.') }}</p>
        </div>
    @endforelse
</div>

@if($notifications->hasPages())
    <div class="mt-4">{{ $notifications->links() }}</div>
@endif
@endsection
