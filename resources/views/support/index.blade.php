@extends('layouts.app')
@section('title', __('Support Center'))
@section('page-title', '🎧 ' . __('Support Center'))
@section('page-subtitle', __('Get help and track your support requests'))

@section('content')

{{-- Stats --}}
<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">🎫</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">{{ __('Total Tickets') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #f59e0b;">
        <div class="stat-icon orange">📂</div>
        <div class="stat-value">{{ $stats['open'] + $stats['in_progress'] }}</div>
        <div class="stat-label">{{ __('Open') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #22c55e;">
        <div class="stat-icon green">✅</div>
        <div class="stat-value">{{ $stats['resolved'] }}</div>
        <div class="stat-label">{{ __('Resolved') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #8b5cf6;">
        <div class="stat-icon purple">🔒</div>
        <div class="stat-value">{{ $stats['closed'] }}</div>
        <div class="stat-label">{{ __('Closed') }}</div>
    </div>
</div>

{{-- Header --}}
<div class="flex-between mb-4">
    <h2 class="section-title">{{ __('My Tickets') }}</h2>
    <a href="{{ route('support.create') }}" class="btn btn-primary">🎫 {{ __('New Ticket') }}</a>
</div>

{{-- Search & Filter --}}
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
<div class="filter-tabs mb-4 animate-fade-in-up">
    <a href="{{ route('support.index') }}" class="filter-tab {{ !request('status') ? 'active' : '' }}">{{ __('All') }}</a>
    <a href="{{ route('support.index', ['status' => 'open']) }}" class="filter-tab {{ request('status') === 'open' ? 'active' : '' }}">{{ __('Open') }}</a>
    <a href="{{ route('support.index', ['status' => 'in_progress']) }}" class="filter-tab {{ request('status') === 'in_progress' ? 'active' : '' }}">{{ __('In Progress') }}</a>
    <a href="{{ route('support.index', ['status' => 'resolved']) }}" class="filter-tab {{ request('status') === 'resolved' ? 'active' : '' }}">{{ __('Resolved') }}</a>
    <a href="{{ route('support.index', ['status' => 'closed']) }}" class="filter-tab {{ request('status') === 'closed' ? 'active' : '' }}">{{ __('Closed') }}</a>
</div>

{{-- Ticket List --}}
@forelse($tickets as $ticket)
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
    <a href="{{ route('support.show', $ticket) }}" class="card p-4 mb-3 animate-fade-in-up" style="display: block; text-decoration: none; border-left: 3px solid {{ $statusColor }}; animation-delay: {{ $loop->index * 0.05 }}s; transition: all 0.2s;">
        <div class="flex-between mb-2">
            <div class="flex align-items-center flex-gap-2">
                <span style="font-size: 18px;">{{ $catIcon }}</span>
                <div>
                    <div class="font-semibold">{{ $ticket->subject }}</div>
                    <div class="text-xs text-muted" style="font-family: monospace;">{{ $ticket->ticket_number }}</div>
                </div>
            </div>
            <div class="flex flex-gap-2">
                <span style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: {{ $priorityColor }}20; color: {{ $priorityColor }};">
                    {{ __(ucfirst($ticket->priority)) }}
                </span>
                <span style="padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: {{ $statusColor }}20; color: {{ $statusColor }};">
                    {{ __(ucwords(str_replace('_', ' ', $ticket->status))) }}
                </span>
            </div>
        </div>
        <div class="flex-between text-xs text-muted">
            <div class="flex flex-gap-3">
                <span>{{ $catIcon }} {{ __(ucfirst($ticket->category)) }}</span>
                <span>💬 {{ $ticket->replies->count() }} {{ __('replies') }}</span>
            </div>
            <span>{{ $ticket->created_at->diffForHumans() }}</span>
        </div>
    </a>
@empty
    <div class="card p-6 animate-fade-in-up">
        <div class="empty-state">
            <div style="font-size: 64px; margin-bottom: 16px;">🎧</div>
            <p class="empty-state-title">{{ __('No tickets found') }}</p>
            <p class="empty-state-text">{{ __('You haven\'t submitted any support tickets yet.') }}</p>
            <a href="{{ route('support.create') }}" class="btn btn-primary mt-4">🎫 {{ __('Submit a Ticket') }}</a>
        </div>
    </div>
@endforelse

@if($tickets->hasPages())
    <div class="mt-4">{{ $tickets->links() }}</div>
@endif
@endsection
