@extends('layouts.app')
@section('title', __('System Audit Logs'))
@section('page-title', '🔍 ' . __('System Audit Logs'))
@section('page-subtitle', __('Inspect system-wide action histories, status changes, and critical operations'))

@section('content')

@php
    $totalLogs = $logs->total();
    $criticalCount = \App\Models\AuditLog::whereIn('severity', ['critical', 'high'])->count();
    $activeUsersCount = \App\Models\AuditLog::whereNotNull('user_id')->distinct('user_id')->count();
    $logs24h = \App\Models\AuditLog::where('created_at', '>=', now()->subDay())->count();
@endphp

{{-- Stats Summary --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 animate-fade-in-up">
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-[var(--info)]">{{ number_format($totalLogs) }}</div>
        <div class="text-xs text-muted">{{ __('Total Logged Actions') }}</div>
    </div>
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-[var(--warning)]">{{ number_format($criticalCount) }}</div>
        <div class="text-xs text-muted">{{ __('High / Critical Events') }}</div>
    </div>
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-[#7c3aed]">{{ number_format($activeUsersCount) }}</div>
        <div class="text-xs text-muted">{{ __('Admins/Users Tracked') }}</div>
    </div>
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-[var(--success)]">{{ number_format($logs24h) }}</div>
        <div class="text-xs text-muted">{{ __('Events (Last 24h)') }}</div>
    </div>
</div>

{{-- Search & Filters --}}
<div class="card p-6 mb-6 animate-fade-in-up delay-50">
    <form method="GET" class="flex gap-3 items-end flex-wrap">
        <div class="form-group mb-0 flex-1 min-w-[200px]">
            <label class="form-label">{{ __('Search keyword') }}</label>
            <input type="text" name="search" class="form-input" placeholder="{{ __('Search action, IP, user name or email...') }}" value="{{ request('search') }}">
        </div>
        <div class="form-group mb-0 min-w-[140px]">
            <label class="form-label">{{ __('Severity') }}</label>
            <select name="severity" class="form-select">
                <option value="">{{ __('All Severities') }}</option>
                @foreach(['critical', 'high', 'medium', 'low', 'info'] as $sev)
                    <option value="{{ $sev }}" {{ request('severity') === $sev ? 'selected' : '' }}>{{ ucfirst($sev) }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group mb-0 min-w-[180px]">
            <label class="form-label">{{ __('Action Type') }}</label>
            <select name="action" class="form-select">
                <option value="">{{ __('All Actions') }}</option>
                @foreach($availableActions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ __(ucfirst(str_replace('_', ' ', $act))) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary h-[42px]">🔍 {{ __('Filter') }}</button>
        @if(request('search') || request('severity') || request('action'))
            <a href="{{ route('admin.audit-logs') }}" class="btn btn-ghost h-[42px]">✕ {{ __('Clear') }}</a>
        @endif
    </form>
</div>

{{-- Audit Logs Table --}}
<div class="card animate-fade-in-up delay-100">
    @if($logs->count() > 0)
    <div class="data-table-wrapper">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="w-[18%]">{{ __('Timestamp') }}</th>
                    <th class="w-[25%]">{{ __('Operator') }}</th>
                    <th class="w-[20%]">{{ __('Action') }}</th>
                    <th class="w-[12%]">{{ __('Severity') }}</th>
                    <th class="w-[13%]">{{ __('IP / Agent') }}</th>
                    <th class="w-[12%] text-right">{{ __('Details') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr class="border-b border-primary">
                        <td>
                            <div class="font-medium text-sm">{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                            <div class="text-xs text-muted mt-1">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            @if($log->user)
                                <div class="flex items-center gap-2">
                                    <div class="admin-avatar-mini">
                                        {{ $log->user->initials }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm">{{ $log->user->name }}</div>
                                        <div class="text-xs text-muted flex items-center gap-1">
                                            <span>{{ $log->user->email }}</span>
                                            <span class="text-[#7c3aed]">· {{ $log->user->branch_display_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-sm shrink-0">
                                        ⚙️
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-cyan">{{ __('System Action') }}</div>
                                        <div class="text-xs text-muted">{{ __('Automated Sync/Cron') }}</div>
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-neutral font-mono text-[11px] py-1 px-2">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusClass = match($log->severity) {
                                    'critical' => 'danger',
                                    'high' => 'warning',
                                    'medium' => 'info',
                                    'low' => 'neutral',
                                    default => 'neutral',
                                };
                                $statusIcon = match($log->severity) {
                                    'critical' => '🔴',
                                    'high' => '🟠',
                                    'medium' => '🟡',
                                    'low' => '🟢',
                                    default => '⚪',
                                };
                            @endphp
                            <span class="badge badge-{{ $statusClass }} text-[11px] font-semibold">
                                {{ $statusIcon }} {{ strtoupper($log->severity) }}
                            </span>
                        </td>
                        <td>
                            <div class="text-xs font-semibold text-primary font-mono">{{ $log->ip_address }}</div>
                            <div class="text-xs text-muted mt-1 max-w-[120px] overflow-hidden text-ellipsis whitespace-nowrap" title="{{ $log->user_agent }}">
                                {{ $log->user_agent }}
                            </div>
                        </td>
                        <td class="text-right">
                            <button class="btn btn-secondary btn-sm text-[11px] py-1 px-2.5" onclick="toggleDetails('details-{{ $log->id }}')">
                                📄 {{ __('Inspect') }}
                            </button>
                        </td>
                    </tr>
                    <tr id="details-{{ $log->id }}" style="display:none;" class="bg-white/[0.02]">
                        <td colspan="6" class="p-4 sm:p-5">
                            <div class="card p-4 text-left border border-primary">
                                <h4 class="text-xs text-muted uppercase font-bold tracking-wider mb-2 text-[var(--info)]">{{ __('Action Payload & State Diffs') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    @if($log->model_type)
                                        <div>
                                            <span class="text-xs text-muted">{{ __('Affected Resource') }}:</span>
                                            <span class="badge badge-neutral font-mono text-[11px] ml-1">{{ $log->model_type }} #{{ $log->model_id }}</span>
                                        </div>
                                    @endif
                                    @if($log->channel)
                                        <div>
                                            <span class="text-xs text-muted">{{ __('Channel') }}:</span>
                                            <span class="text-xs font-semibold text-primary ml-1">{{ ucfirst($log->channel) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-3">
                                    <div>
                                        <h5 class="text-xs text-muted font-semibold mb-1">⏮️ {{ __('Original State / Old Values') }}</h5>
                                        @if($log->old_values && count($log->old_values) > 0)
                                            <pre class="m-0 p-2.5 bg-black/20 rounded text-[11px] font-mono text-[var(--danger)] overflow-x-auto max-h-[180px]">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                        @else
                                            <div class="text-xs text-muted italic p-2.5 bg-black/10 rounded">{{ __('No original state recorded.') }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <h5 class="text-xs text-muted font-semibold mb-1">⏭️ {{ __('New State / New Values') }}</h5>
                                        @if($log->new_values && count($log->new_values) > 0)
                                            <pre class="m-0 p-2.5 bg-black/20 rounded text-[11px] font-mono text-[var(--success)] overflow-x-auto max-h-[180px]">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                        @else
                                            <div class="text-xs text-muted italic p-2.5 bg-black/10 rounded">{{ __('No state mutations recorded.') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="p-4 sm:p-5 border-t border-primary">{{ $logs->withQueryString()->links() }}</div>
    @endif
    @else
        <div class="empty-state p-10 sm:p-16">
            <div class="empty-state-icon">🔍</div>
            <p class="empty-state-title">{{ __('No audit logs found') }}</p>
            <p class="empty-state-text">{{ __('Try adjusting your filters or search keywords.') }}</p>
        </div>
    @endif
</div>
<script>
    function toggleDetails(id) {
        const el = document.getElementById(id);
        if (el.style.display === 'none') {
            el.style.display = 'table-row';
            el.classList.add('animate-fade-in-up');
        } else {
            el.style.display = 'none';
        }
    }
</script>
@endsection
