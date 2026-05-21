@extends('layouts.app')
@section('title', 'Ticket: ' . $ticket->ticket_number)
@section('page-title', 'Ticket: ' . $ticket->ticket_number)
@section('page-subtitle', $ticket->subject)

@section('content')
<div class="animate-fade-in-up" style="max-width: 900px;">
    <a href="{{ route('admin.support.index') }}" class="btn btn-ghost btn-sm mb-4">← {{ __('Back to Tickets') }}</a>

    <div class="grid-2" style="gap: 16px; margin-bottom: 20px;">
        {{-- User Info Card --}}
        <div class="card p-5">
            <div class="text-muted text-xs mb-3" style="text-transform: uppercase; letter-spacing: 1px;">{{ __('Customer Info') }}</div>
            <div class="flex align-items-center flex-gap-2 mb-3">
                <div class="avatar-initials-medium">{{ $ticket->user->initials }}</div>
                <div>
                    <div style="font-size: 15px; font-weight: 600; color: var(--text-primary);">{{ $ticket->user->name }}</div>
                    <div class="text-muted text-sm">{{ $ticket->user->email }}</div>
                </div>
            </div>
            <div class="flex flex-wrap flex-gap-2">
                <span class="badge badge-neutral" style="font-size: 10px;">{{ $ticket->user->branch ?? __('N/A') }}</span>
                <span class="badge badge-{{ $ticket->user->status === 'active' ? 'success' : 'warning' }}" style="font-size: 10px;">{{ __(ucfirst($ticket->user->status)) }}</span>
            </div>
            <div style="margin-top: 12px;">
                <a href="{{ route('admin.users.show', $ticket->user) }}" class="text-xs" style="color: var(--info);">{{ __('View Full Profile →') }}</a>
            </div>
        </div>

        {{-- Ticket Metadata --}}
        <div class="card p-5">
            <div class="text-muted text-xs mb-3" style="text-transform: uppercase; letter-spacing: 1px;">{{ __('Ticket Details') }}</div>
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
            <div class="flex flex-wrap flex-gap-2 mb-3">
                <span class="badge {{ $statusClass }}">{{ __(ucwords(str_replace('_', ' ', $ticket->status))) }}</span>
                <span class="badge {{ $priorityClass }}">{{ __(ucfirst($ticket->priority)) }}</span>
                <span class="badge badge-neutral">{{ $catIcon }} {{ __(ucfirst($ticket->category)) }}</span>
            </div>
            <div class="grid-2" style="gap: 8px; font-size: 12px;">
                <div><span class="text-muted">{{ __('Created:') }}</span> <span style="color: var(--text-primary);">{{ $ticket->created_at->format('M d, Y h:i A') }}</span></div>
                @if($ticket->resolved_at)
                    <div><span class="text-muted">{{ __('Resolved:') }}</span> <span style="color: var(--text-primary);">{{ $ticket->resolved_at->format('M d, Y h:i A') }}</span></div>
                @endif
            </div>

            {{-- Status Update Form --}}
            <form method="POST" action="{{ route('admin.support.update-status', $ticket) }}" class="flex flex-gap-2 align-items-end" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
                @csrf
                @method('PUT')
                <div class="form-group" style="margin-bottom: 0; flex: 1;">
                    <label class="form-label text-xs">{{ __('Update Status') }}</label>
                    <select name="status" class="form-input" style="padding: 8px 12px; font-size: 13px;">
                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                        <option value="awaiting_response" {{ $ticket->status === 'awaiting_response' ? 'selected' : '' }}>{{ __('Awaiting Response') }}</option>
                        <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                        <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">{{ __('Update') }}</button>
            </form>

            {{-- Assignment Form --}}
            <form method="POST" action="{{ route('admin.support.assign', $ticket) }}" class="flex flex-gap-2 align-items-end" style="margin-top: 12px;">
                @csrf
                <div class="form-group" style="margin-bottom: 0; flex: 1;">
                    <label class="form-label text-xs">{{ __('Assign To') }}</label>
                    <select name="assigned_to" class="form-input" style="padding: 8px 12px; font-size: 13px;">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}" {{ $ticket->assigned_to == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-ghost btn-sm" style="height: 40px;">{{ __('Assign') }}</button>
            </form>
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
                        <div class="support-message-time">{{ $ticket->created_at->diffForHumans() }} — {{ $ticket->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                </div>
                <span class="badge badge-neutral" style="font-size: 10px;">{{ __('Customer') }}</span>
            </div>
            <div class="support-message-body">{{ $ticket->message }}</div>
        </div>

        {{-- Replies --}}
        @foreach($ticket->replies as $reply)
            <div class="support-message {{ $reply->is_staff_reply ? 'support-message-staff' : 'support-message-user' }}">
                <div class="support-message-header">
                    <div class="flex align-items-center flex-gap-2">
                        <div class="avatar-initials-normal" style="{{ $reply->is_staff_reply ? 'background: var(--gradient-primary);' : '' }}">
                            {{ $reply->user->initials ?? '?' }}
                        </div>
                        <div>
                            <div class="support-message-name">{{ $reply->user->name ?? __('Staff') }}</div>
                            <div class="support-message-time">{{ $reply->created_at->diffForHumans() }} — {{ $reply->created_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                    @if($reply->is_staff_reply)
                        <span class="badge badge-purple" style="font-size: 10px;">{{ __('Staff') }}</span>
                    @else
                        <span class="badge badge-neutral" style="font-size: 10px;">{{ __('Customer') }}</span>
                    @endif
                    @if($reply->is_internal)
                        <span class="badge badge-warning" style="font-size: 10px;">{{ __('Internal Note') }}</span>
                    @endif
                </div>
                <div class="support-message-body">
                    @if($reply->is_internal)
                        <div style="background: rgba(245, 158, 11, 0.1); border-left: 3px solid var(--warning); padding: 8px 12px; margin-bottom: 8px; border-radius: 4px;">
                            <span class="text-xs" style="color: var(--warning);">{{ __('Internal note — not visible to customer') }}</span>
                        </div>
                    @endif
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

    {{-- Admin Reply Form --}}
    <div class="card p-6 mt-4">
        <h3 style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 16px;">👑 {{ __('Reply as Staff') }}</h3>
        <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <textarea name="message" class="form-textarea" rows="4" placeholder="{{ __('Type your staff reply...') }}" required maxlength="5000">{{ old('message') }}</textarea>
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
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_internal" value="1" style="width: 16px; height: 16px;">
                    <span style="font-size: 13px; color: var(--text-primary);">{{ __('Internal note (not visible to customer)') }}</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">👑 {{ __('Reply as Staff') }}</button>
        </form>
    </div>
</div>
@endsection
