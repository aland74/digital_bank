@extends('layouts.app')
@section('title', $ticket->subject)
@section('page-title', $ticket->subject)
@section('page-subtitle', $ticket->ticket_number)

@section('content')
<div class="animate-fade-in-up" style="max-width: 800px;">
    <a href="{{ route('support.index') }}" class="btn btn-ghost btn-sm mb-4">← {{ __('Back to Tickets') }}</a>

    {{-- Ticket Info Header --}}
    <div class="card p-6 mb-4">
        <div class="flex-between mb-4">
            <div>
                <h2 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">{{ $ticket->subject }}</h2>
                <span class="text-muted text-sm" style="font-family: monospace;">{{ $ticket->ticket_number }}</span>
            </div>
        </div>
        <div class="flex flex-wrap flex-gap-2">
            @php
                $statusClass = match($ticket->status) {
                    'open' => 'badge-info',
                    'in_progress' => 'badge-warning',
                    'awaiting_response' => 'badge-purple',
                    'resolved' => 'badge-success',
                    'closed' => 'badge-neutral',
                    default => 'badge-neutral',
                };
                $priorityClass = match($ticket->priority) {
                    'urgent' => 'badge-danger',
                    'high' => 'badge-warning',
                    'medium' => 'badge-gold',
                    'low' => 'badge-success',
                    default => 'badge-neutral',
                };
                $catIcon = match($ticket->category) {
                    'account' => '🏦', 'transaction' => '💸', 'card' => '💳',
                    'loan' => '📈', 'technical' => '🔧', 'complaint' => '📢',
                    default => '📋',
                };
            @endphp
            <span class="badge {{ $statusClass }}">{{ __(ucwords(str_replace('_', ' ', $ticket->status))) }}</span>
            <span class="badge {{ $priorityClass }}">{{ __(ucfirst($ticket->priority)) }} {{ __('Priority') }}</span>
            <span class="badge badge-neutral">{{ $catIcon }} {{ __(ucfirst($ticket->category)) }}</span>
            <span class="badge badge-neutral">{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
        </div>
    </div>

    {{-- Conversation Thread --}}
    <div class="support-thread">
        {{-- Original message --}}
        <div class="support-message support-message-user">
            <div class="support-message-header">
                <div class="flex align-items-center flex-gap-2">
                    <div class="avatar-initials-normal">{{ $ticket->user->initials }}</div>
                    <div>
                        <div class="support-message-name">{{ $ticket->user->name }}</div>
                        <div class="support-message-time">{{ $ticket->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                <span class="badge badge-neutral" style="font-size: 10px;">{{ __('Author') }}</span>
            </div>
            <div class="support-message-body">{{ $ticket->message }}</div>
        </div>

        {{-- Replies --}}
        @foreach($ticket->publicReplies as $reply)
            <div class="support-message {{ $reply->is_staff_reply ? 'support-message-staff' : 'support-message-user' }}">
                <div class="support-message-header">
                    <div class="flex align-items-center flex-gap-2">
                        <div class="avatar-initials-normal" style="{{ $reply->is_staff_reply ? 'background: var(--gradient-primary);' : '' }}">
                            {{ $reply->user->initials ?? '?' }}
                        </div>
                        <div>
                            <div class="support-message-name">{{ $reply->user->name ?? __('Staff') }}</div>
                            <div class="support-message-time">{{ $reply->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @if($reply->is_staff_reply)
                        <span class="badge badge-purple" style="font-size: 10px;">{{ __('Staff') }}</span>
                    @endif
                </div>
                <div class="support-message-body">
                    {{ $reply->message }}
                    @if($reply->hasAttachment())
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-glass);">
                            <a href="{{ $reply->attachment_url }}" target="_blank" class="btn btn-ghost btn-sm">📎 {{ __('View Attachment') }}</a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Close Ticket --}}
    @if($ticket->status !== 'closed')
        <div class="flex justify-content-between align-items-center mt-4 mb-2">
            <span></span>
            <form method="POST" action="{{ route('support.close', $ticket) }}" onsubmit="return confirm('{{ __('Are you sure you want to close this ticket?') }}')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">🔒 {{ __('Close Ticket') }}</button>
            </form>
        </div>
    @endif

    {{-- Reply Form --}}
    @if($ticket->status !== 'closed')
        <div class="card p-6">
            <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 16px;">💬 {{ __('Reply') }}</h3>
            <form method="POST" action="{{ route('support.reply', $ticket) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <textarea name="message" class="form-textarea" rows="4" placeholder="{{ __('Type your reply...') }}" required maxlength="5000">{{ old('message') }}</textarea>
                    @error('message')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="attachment">{{ __('Attachment') }} <span class="text-muted text-xs">({{ __('Optional') }})</span></label>
                    <input type="file" name="attachment" id="attachment" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                    <p class="text-muted text-xs" style="margin-top: 4px;">{{ __('Max 5MB. Allowed: JPG, PNG, GIF, PDF, DOC, TXT') }}</p>
                    @error('attachment')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">📩 {{ __('Send Reply') }}</button>
            </form>
        </div>
    @else
        <div class="card p-6 mt-4 text-center">
            <p class="text-muted">🔒 {{ __('This ticket is closed and no longer accepts replies.') }}</p>
        </div>
    @endif
</div>
@endsection
