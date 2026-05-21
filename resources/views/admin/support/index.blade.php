@extends('layouts.app')
@section('title', __('Support Tickets'))
@section('page-title', '🎫 ' . __('Support Tickets'))
@section('page-subtitle', $stats['total'] . ' ' . __('total tickets'))

@section('content')
{{-- Stats Cards --}}
<div class="grid-fit-160 mb-6 animate-fade-in-up">
    <div class="stat-card">
        <div class="stat-card-value" style="color: var(--info);">{{ $stats['total'] }}</div>
        <div class="stat-card-label">{{ __('Total') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value" style="color: var(--primary);">{{ $stats['open'] }}</div>
        <div class="stat-card-label">{{ __('Open') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value" style="color: var(--warning-light);">{{ $stats['in_progress'] }}</div>
        <div class="stat-card-label">{{ __('In Progress') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value" style="color: var(--success);">{{ $stats['resolved'] }}</div>
        <div class="stat-card-label">{{ __('Resolved') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value" style="color: var(--text-muted);">{{ $stats['closed'] }}</div>
        <div class="stat-card-label">{{ __('Closed') }}</div>
    </div>
</div>

{{-- Filters --}}
<div class="card p-4 mb-4 animate-fade-in-up">
    <form method="GET" action="{{ route('admin.support.index') }}" class="flex flex-wrap flex-gap-2 align-items-end">
        <div class="form-group" style="margin-bottom: 0; min-width: 200px; flex: 2;">
            <label class="form-label text-xs">{{ __('Search') }}</label>
            <input type="text" name="search" class="form-input" style="padding: 8px 12px; font-size: 13px;" placeholder="{{ __('Subject, ticket #, customer...') }}" value="{{ request('search') }}">
        </div>
        <div class="form-group" style="margin-bottom: 0; min-width: 150px; flex: 1;">
            <label class="form-label text-xs">{{ __('Status') }}</label>
            <select name="status" class="form-input" style="padding: 8px 12px; font-size: 13px;">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                <option value="awaiting_response" {{ request('status') === 'awaiting_response' ? 'selected' : '' }}>{{ __('Awaiting Response') }}</option>
                <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0; min-width: 150px; flex: 1;">
            <label class="form-label text-xs">{{ __('Priority') }}</label>
            <select name="priority" class="form-input" style="padding: 8px 12px; font-size: 13px;">
                <option value="">{{ __('All Priorities') }}</option>
                <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>{{ __('Urgent') }}</option>
                <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>{{ __('High') }}</option>
                <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>{{ __('Medium') }}</option>
                <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>{{ __('Low') }}</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0; min-width: 150px; flex: 1;">
            <label class="form-label text-xs">{{ __('Category') }}</label>
            <select name="category" class="form-input" style="padding: 8px 12px; font-size: 13px;">
                <option value="">{{ __('All Categories') }}</option>
                <option value="account" {{ request('category') === 'account' ? 'selected' : '' }}>{{ __('Account') }}</option>
                <option value="transaction" {{ request('category') === 'transaction' ? 'selected' : '' }}>{{ __('Transaction') }}</option>
                <option value="card" {{ request('category') === 'card' ? 'selected' : '' }}>{{ __('Card') }}</option>
                <option value="loan" {{ request('category') === 'loan' ? 'selected' : '' }}>{{ __('Loan') }}</option>
                <option value="technical" {{ request('category') === 'technical' ? 'selected' : '' }}>{{ __('Technical') }}</option>
                <option value="complaint" {{ request('category') === 'complaint' ? 'selected' : '' }}>{{ __('Complaint') }}</option>
                <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>{{ __('General') }}</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="height: 40px;">🔍 {{ __('Filter') }}</button>
        @if(request()->hasAny(['status', 'priority', 'category', 'search']))
            <a href="{{ route('admin.support.index') }}" class="btn btn-ghost btn-sm" style="height: 40px;">✕ {{ __('Clear') }}</a>
        @endif
    </form>
</div>

{{-- Ticket Table --}}
<div class="card animate-fade-in-up">
    <div class="data-table-wrapper">
        @forelse($tickets as $ticket)
            <a href="{{ route('admin.support.show', $ticket) }}" class="support-admin-row" style="animation-delay: {{ $loop->index * 0.03 }}s;">
                <div class="support-admin-row-main">
                    <div class="flex align-items-center flex-gap-2" style="min-width: 180px;">
                        <div class="avatar-initials-normal">{{ $ticket->user->initials }}</div>
                        <div>
                            <div class="font-weight-600" style="color: var(--text-primary); font-size: 13px;">{{ $ticket->user->name }}</div>
                            <div class="text-muted" style="font-size: 11px;">{{ $ticket->user->branch ?? __('N/A') }}</div>
                        </div>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <div style="font-size: 13px; color: var(--text-primary); font-weight: 500;">{{ Str::limit($ticket->subject, 50) }}</div>
                        <div class="text-muted" style="font-size: 11px; font-family: monospace;">{{ $ticket->ticket_number }}</div>
                    </div>
                    @if($ticket->assignee)
                        <div class="text-muted" style="font-size: 11px; min-width: 100px;">
                            <span style="color: var(--info);">{{ __('Assigned') }}:</span> {{ $ticket->assignee->name }}
                        </div>
                    @endif
                    <div class="support-admin-row-badges">
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
                        <span class="badge {{ $priorityClass }}" style="font-size: 10px;">{{ __(ucfirst($ticket->priority)) }}</span>
                        <span class="badge {{ $statusClass }}" style="font-size: 10px;">{{ __(ucwords(str_replace('_', ' ', $ticket->status))) }}</span>
                        <span class="badge badge-neutral" style="font-size: 10px;">{{ $catIcon }} {{ __(ucfirst($ticket->category)) }}</span>
                    </div>
                    <div class="text-muted" style="font-size: 11px; min-width: 100px; text-align: right;">
                        {{ $ticket->created_at->diffForHumans() }}
                    </div>
                </div>
            </a>
        @empty
            <div class="empty-state empty-state-padding">
                <div class="empty-state-icon">🎫</div>
                <p class="empty-state-title">{{ __('No tickets found') }}</p>
                <p class="empty-state-text">{{ __('No support tickets match your filters.') }}</p>
            </div>
        @endforelse
    </div>

    @if($tickets->hasPages())
        <div class="pagination-wrapper">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
