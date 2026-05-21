@extends('layouts.app')
@section('title', __('Support Center'))
@section('page-title', __('Support Center'))
@section('page-subtitle', __('Get help and track your support requests'))

@section('content')
{{-- Stats Cards --}}
<div class="grid-fit-160 mb-6 animate-fade-in-up">
    <div class="stat-card">
        <div class="stat-card-value" style="color: var(--info);">{{ $stats['total'] }}</div>
        <div class="stat-card-label">{{ __('Total Tickets') }}</div>
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
</div>

{{-- Header --}}
<div class="section-header animate-fade-in-up">
    <h2 class="section-title">{{ __('My Tickets') }}</h2>
    <a href="{{ route('support.create') }}" class="btn btn-primary">🎫 {{ __('New Ticket') }}</a>
</div>

{{-- Search --}}
<div class="card p-4 mb-4 animate-fade-in-up">
    <form method="GET" action="{{ route('support.index') }}" class="flex flex-gap-2 align-items-end">
        <div class="form-group" style="margin-bottom: 0; flex: 1;">
            <input type="text" name="search" class="form-input" placeholder="{{ __('Search by subject, ticket number...') }}" value="{{ request('search') }}">
        </div>
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <button type="submit" class="btn btn-primary btn-sm">{{ __('Search') }}</button>
        @if(request('search'))
            <a href="{{ route('support.index', request()->except('search')) }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
        @endif
    </form>
</div>

{{-- Status Filter Tabs --}}
<div class="support-filter-tabs animate-fade-in-up">
    <a href="{{ route('support.index') }}" class="support-filter-tab {{ !request('status') ? 'active' : '' }}">
        {{ __('All') }}
    </a>
    <a href="{{ route('support.index', ['status' => 'open']) }}" class="support-filter-tab {{ request('status') === 'open' ? 'active' : '' }}">
        {{ __('Open') }}
    </a>
    <a href="{{ route('support.index', ['status' => 'in_progress']) }}" class="support-filter-tab {{ request('status') === 'in_progress' ? 'active' : '' }}">
        {{ __('In Progress') }}
    </a>
    <a href="{{ route('support.index', ['status' => 'resolved']) }}" class="support-filter-tab {{ request('status') === 'resolved' ? 'active' : '' }}">
        {{ __('Resolved') }}
    </a>
    <a href="{{ route('support.index', ['status' => 'closed']) }}" class="support-filter-tab {{ request('status') === 'closed' ? 'active' : '' }}">
        {{ __('Closed') }}
    </a>
</div>

{{-- Ticket List --}}
@forelse($tickets as $ticket)
    <a href="{{ route('support.show', $ticket) }}" class="support-ticket-card animate-fade-in-up" style="animation-delay: {{ $loop->index * 0.05 }}s;">
        <div class="support-ticket-header">
            <div class="support-ticket-number">{{ $ticket->ticket_number }}</div>
            <div class="support-ticket-badges">
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
                @endphp
                <span class="badge {{ $priorityClass }}">{{ __(ucfirst($ticket->priority)) }}</span>
                <span class="badge {{ $statusClass }}">{{ __(ucwords(str_replace('_', ' ', $ticket->status))) }}</span>
            </div>
        </div>
        <div class="support-ticket-subject">{{ $ticket->subject }}</div>
        <div class="support-ticket-footer">
            <span class="support-ticket-category">
                @php
                    $catIcon = match($ticket->category) {
                        'account' => '🏦', 'transaction' => '💸', 'card' => '💳',
                        'loan' => '📈', 'technical' => '🔧', 'complaint' => '📢',
                        default => '📋',
                    };
                @endphp
                {{ $catIcon }} {{ __(ucfirst($ticket->category)) }}
            </span>
            <span class="support-ticket-meta">
                💬 {{ $ticket->replies->count() }} {{ __('replies') }}
            </span>
            <span class="support-ticket-meta">
                {{ $ticket->created_at->diffForHumans() }}
            </span>
        </div>
    </a>
@empty
    <div class="card p-6 animate-fade-in-up">
        <div class="empty-state">
            <div class="empty-state-icon">🎫</div>
            <p class="empty-state-title">{{ __('No tickets found') }}</p>
            <p class="empty-state-text">{{ __('You haven\'t submitted any support tickets yet.') }}</p>
            <a href="{{ route('support.create') }}" class="btn btn-primary">{{ __('Submit a Report') }}</a>
        </div>
    </div>
@endforelse

@if($tickets->hasPages())
    <div class="pagination-wrapper animate-fade-in-up">{{ $tickets->links() }}</div>
@endif
@endsection
