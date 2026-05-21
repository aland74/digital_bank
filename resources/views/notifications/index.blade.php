@extends('layouts.app')
@section('title', __('Notifications'))
@section('page-title', __('Notifications'))

@section('content')
<div class="section-header">
    <h2 class="section-title">{{ __('Notifications') }}</h2>
    @if($notifications->total() > 0)
        <form method="POST" action="{{ route('notifications.mark-all-read') }}">
            @csrf
            <button class="btn btn-ghost btn-sm">✓ {{ __('Mark all as read') }}</button>
        </form>
    @endif
</div>

<div class="card animate-fade-in-up">
    @forelse($notifications as $notif)
        <div class="transaction-item" style="border:none;background:{{ $notif->is_read ? 'transparent' : 'rgba(0,212,255,0.03)' }};{{ $notif->requiresAction() ? 'border-left:3px solid #7c3aed;' : '' }}">
            <div class="transaction-icon" style="background:{{ $notif->type_color }}15;color:{{ $notif->type_color }};">
                {{ $notif->type_icon }}
            </div>
            <div class="transaction-details" style="flex:1;">
                <div class="transaction-title" style="{{ !$notif->is_read ? 'color:var(--text-primary);' : '' }}">{{ $notif->translated_title }}</div>
                <div class="transaction-meta">{{ $notif->translated_message }}</div>
                <div class="text-xs text-muted mt-1">{{ $notif->created_at->format('M d, Y h:i A') }} · {{ $notif->created_at->diffForHumans() }}</div>

                {{-- Actionable: Transfer Accept/Decline --}}
                @if($notif->requiresAction() && $notif->type === 'transfer_request')
                    @php $ptId = $notif->getPendingTransferId(); @endphp
                    @if($ptId)
                        <div style="display:flex;gap:8px;margin-top:8px;">
                            <a href="{{ route('transfers.pending') }}" class="btn btn-primary btn-sm">{{ __('View & Respond') }}</a>
                        </div>
                    @endif
                @endif
            </div>

            <div style="display:flex;align-items:center;gap:8px;">
                @if(!$notif->is_read)
                    <div style="width:8px;height:8px;border-radius:50%;background:var(--info);flex-shrink:0;"></div>
                @endif
                <form method="POST" action="{{ route('notifications.read', $notif) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" style="padding:4px 8px;font-size:11px;">
                        {{ $notif->is_read ? __('View') : __('Read') }}
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">🔔</div>
            <p class="empty-state-title">{{ __('No notifications') }}</p>
            <p class="empty-state-text">{{ __('You\'re all caught up!') }}</p>
        </div>
    @endforelse

    @if($notifications->hasPages())
        <div class="pagination-wrapper">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
