@extends('layouts.app')
@section('title', $ticket->subject)
@section('page-title', '🎫 ' . $ticket->subject)
@section('page-subtitle', $ticket->ticket_number)

@section('content')
@php
    $statusColors = [
        'open' => '#3b82f6',
        'in_progress' => '#f59e0b',
        'awaiting_response' => '#8b5cf6',
        'resolved' => '#22c55e',
        'closed' => '#6b7280',
    ];
    $priorityColors = [
        'urgent' => '#ef4444',
        'high' => '#f97316',
        'medium' => '#f59e0b',
        'low' => '#22c55e',
    ];
    $catIcons = [
        'account' => '🏦', 'transaction' => '💸', 'card' => '💳',
        'loan' => '📈', 'technical' => '🔧', 'complaint' => '📢',
        'general' => '📋',
    ];
    $statusColor = $statusColors[$ticket->status] ?? '#6b7280';
    $priorityColor = $priorityColors[$ticket->priority] ?? '#6b7280';
    $catIcon = $catIcons[$ticket->category] ?? '📋';
@endphp

<div style="max-width: 800px;">
    {{-- Back --}}
    <a href="{{ route('support.index') }}" class="btn btn-ghost btn-sm mb-4">← {{ __('Back to Tickets') }}</a>

    {{-- Ticket Info --}}
    <div class="card p-6 mb-4 animate-fade-in-up" style="border-left: 3px solid {{ $statusColor }};">
        <div class="flex-between mb-4">
            <div>
                <h2 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">{{ $ticket->subject }}</h2>
                <span class="text-muted text-sm" style="font-family: monospace;">{{ $ticket->ticket_number }}</span>
            </div>
            <div class="flex flex-gap-2">
                <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: {{ $priorityColor }}20; color: {{ $priorityColor }};">
                    {{ __(ucfirst($ticket->priority)) }} {{ __('Priority') }}
                </span>
                <span style="padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: {{ $statusColor }}20; color: {{ $statusColor }};">
                    {{ __(ucwords(str_replace('_', ' ', $ticket->status))) }}
                </span>
            </div>
        </div>

        <div class="flex flex-gap-3 text-sm text-muted">
            <span>{{ $catIcon }} {{ __(ucfirst($ticket->category)) }}</span>
            <span>📅 {{ $ticket->created_at->format('M d, Y h:i A') }}</span>
            @if($ticket->assignee)
                <span>👤 {{ $ticket->assignee->name }}</span>
            @endif
        </div>
    </div>

    {{-- Conversation --}}
    <div class="mb-4">
        {{-- Original Message --}}
        <div class="card mb-3 animate-fade-in-up" style="border-left: 3px solid #3b82f6;">
            <div class="flex align-items-center flex-gap-3 p-4" style="border-bottom: 1px solid var(--border);">
                <div class="avatar-initials">{{ $ticket->user->initials }}</div>
                <div style="flex: 1;">
                    <div class="font-semibold text-sm">{{ $ticket->user->name }}</div>
                    <div class="text-xs text-muted">{{ $ticket->created_at->diffForHumans() }}</div>
                </div>
                <span class="badge badge-info" style="font-size: 10px;">{{ __('Author') }}</span>
            </div>
            <div class="p-4" style="white-space: pre-wrap; line-height: 1.6;">{{ $ticket->message }}</div>
        </div>

        {{-- Replies --}}
        @foreach($ticket->publicReplies as $reply)
            @php
                $isStaff = $reply->is_staff_reply;
            @endphp
            <div class="card mb-3 animate-fade-in-up" style="border-left: 3px solid {{ $isStaff ? '#8b5cf6' : '#3b82f6' }};">
                <div class="flex align-items-center flex-gap-3 p-4" style="border-bottom: 1px solid var(--border);">
                    <div class="avatar-initials" style="{{ $isStaff ? 'background: linear-gradient(135deg, #8b5cf6, #6366f1);' : '' }}">
                        {{ $reply->user->initials ?? '?' }}
                    </div>
                    <div style="flex: 1;">
                        <div class="font-semibold text-sm">{{ $reply->user->name ?? __('Staff') }}</div>
                        <div class="text-xs text-muted">{{ $reply->created_at->diffForHumans() }}</div>
                    </div>
                    @if($isStaff)
                        <span class="badge badge-purple" style="font-size: 10px;">{{ __('Staff') }}</span>
                    @endif
                </div>
                <div class="p-4" style="white-space: pre-wrap; line-height: 1.6;">{{ $reply->message }}</div>
                @if($reply->hasAttachment())
                    <div class="px-4 pb-4">
                        <a href="{{ $reply->attachment_url }}" target="_blank" class="btn btn-ghost btn-sm">📎 {{ __('View Attachment') }}</a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Close Ticket --}}
    @if($ticket->status !== 'closed')
        <div class="flex justify-content-end mb-4">
            <form method="POST" action="{{ route('support.close', $ticket) }}" onsubmit="return confirm('{{ __('Are you sure you want to close this ticket?') }}')">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">🔒 {{ __('Close Ticket') }}</button>
            </form>
        </div>
    @endif

    {{-- Reply Form --}}
    @if($ticket->status !== 'closed')
        <div class="card p-6 animate-fade-in-up">
            <h3 class="section-title mb-4">💬 {{ __('Reply') }}</h3>
            <form method="POST" action="{{ route('support.reply', $ticket) }}" enctype="multipart/form-data" data-loading>
                @csrf
                <div class="form-group">
                    <textarea name="message" class="form-textarea" rows="4" placeholder="{{ __('Type your reply...') }}" required maxlength="5000">{{ old('message') }}</textarea>
                    @error('message')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Attachment') }} <span class="text-muted text-xs">({{ __('Optional') }})</span></label>
                    <input type="file" name="attachment" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                    <div class="form-hint">{{ __('Max 5MB. Allowed: JPG, PNG, GIF, PDF, DOC, TXT') }}</div>
                    @error('attachment')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">📩 {{ __('Send Reply') }}</button>
            </form>
        </div>
    @else
        <div class="card p-6 animate-fade-in-up" style="text-align: center;">
            <div style="font-size: 32px; margin-bottom: 8px;">🔒</div>
            <p class="text-muted">{{ __('This ticket is closed and no longer accepts replies.') }}</p>
        </div>
    @endif
</div>
@endsection
