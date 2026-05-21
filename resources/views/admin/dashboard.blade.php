@extends('layouts.app')
@section('title', __('Admin Dashboard'))
@section('page-title', '🛡️ ' . __('Admin Dashboard'))
@section('page-subtitle', __('System overview and management') . ' · ' . now()->format('l, M d, Y'))

@section('content')
{{-- Reserve Warning --}}
@if(!$reserveHealth['healthy'])
    <div class="alert alert-error alert-low-reserves animate-fade-in-up">
        ⚠️ <strong>{{ __('LOW RESERVES!') }}</strong> {{ __('Bank reserves') }} (${{ number_format($reserveHealth['total_deposits'], 2) }}) {{ __('are below the minimum') }} (${{ number_format($reserveHealth['minimum'], 2) }}).
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.settings') }}" style="color:var(--info);text-decoration:underline;margin-left:4px;">{{ __('Adjust Settings →') }}</a>
        @endif
    </div>
@endif

<div class="grid-fit-240 mb-6 animate-fade-in-up">
    <div class="card stat-card-admin">
        <div class="flex-between grid-align-start z-2">
            <div>
                <div class="text-xs text-muted font-semibold text-uppercase-tracking">{{ __('Total Deposits') }}</div>
                <div class="admin-stat-value cyan">${{ number_format($stats['total_balance'], 0) }}</div>
                <div class="text-xs text-muted mt-1">د.ع {{ number_format($stats['total_balance'] * $iqdRate, 0) }}</div>
            </div>
            <div class="admin-stat-icon cyan">🏦</div>
        </div>
        <svg class="admin-stat-svg cyan" preserveAspectRatio="none" viewBox="0 0 100 100"><path d="M0,100 L0,50 Q25,30 50,60 T100,20 L100,100 Z"/></svg>
    </div>
    <div class="card stat-card-admin">
        <div class="flex-between grid-align-start z-2">
            <div>
                <div class="text-xs text-muted font-semibold text-uppercase-tracking">{{ __('Total Customers') }}</div>
                <div class="admin-stat-value">${{ number_format($stats['total_users']) }}</div>
                <div class="text-xs text-muted mt-1 flex flex-gap-1 align-items-center"><span style="color:var(--success);">+{{ $stats['new_users_week'] }}</span> {{ __('this week') }}</div>
            </div>
            <div class="admin-stat-icon neutral">👥</div>
        </div>
        <svg class="admin-stat-svg neutral" preserveAspectRatio="none" viewBox="0 0 100 100"><path d="M0,100 L0,70 Q25,80 50,40 T100,30 L100,100 Z"/></svg>
    </div>
    <div class="card stat-card-admin">
        <div class="flex-between grid-align-start z-2">
            <div>
                <div class="text-xs text-muted font-semibold text-uppercase-tracking">{{ __('Today\'s Volume') }}</div>
                <div class="admin-stat-value purple">${{ number_format($stats['volume_today'], 0) }}</div>
                <div class="text-xs text-muted mt-1">د.ع {{ number_format($stats['volume_today'] * $iqdRate, 0) }}</div>
            </div>
            <div class="admin-stat-icon purple">💸</div>
        </div>
        <svg class="admin-stat-svg purple" preserveAspectRatio="none" viewBox="0 0 100 100"><path d="M0,100 L0,80 Q25,50 50,70 T100,10 L100,100 Z"/></svg>
    </div>
    <div class="card stat-card-admin">
        <div class="flex-between grid-align-start z-2">
            <div>
                <div class="text-xs text-muted font-semibold text-uppercase-tracking">{{ __('Loan Outstanding') }}</div>
                <div class="admin-stat-value orange">${{ number_format($stats['total_loan_outstanding'], 0) }}</div>
                <div class="text-xs text-muted mt-1">د.ع {{ number_format($stats['total_loan_outstanding'] * $iqdRate, 0) }}</div>
            </div>
            <div class="admin-stat-icon orange">📈</div>
        </div>
        <svg class="admin-stat-svg orange" preserveAspectRatio="none" viewBox="0 0 100 100"><path d="M0,100 L0,20 Q25,60 50,40 T100,50 L100,100 Z"/></svg>
    </div>
</div>
</div>

<div class="grid-2 grid-align-stretch mb-6">
    {{-- Action Required Queue --}}
    <div class="card p-6 animate-fade-in-up delay-50">
        <div class="flex-between mb-4">
            <h2 class="section-title">⚠️ {{ __('Action Required Queue') }}</h2>
        </div>
        <div class="grid-fit-120">
            <a href="{{ route('admin.loans') }}" class="card admin-action-card p-4 {{ $stats['pending_loans'] > 0 ? 'pending-loans' : '' }}">
                <div style="font-size:28px;font-weight:800;color:{{ $stats['pending_loans'] > 0 ? 'var(--warning)' : 'var(--text-muted)' }};">{{ $stats['pending_loans'] }}</div>
                <div class="text-xs text-primary font-semibold mt-1">{{ __('Pending Loans') }}</div>
            </a>
            <a href="{{ route('admin.kyc') }}" class="card admin-action-card p-4 {{ $stats['pending_kyc'] > 0 ? 'pending-kyc' : '' }}">
                <div style="font-size:28px;font-weight:800;color:{{ $stats['pending_kyc'] > 0 ? '#7c3aed' : 'var(--text-muted)' }};">{{ $stats['pending_kyc'] }}</div>
                <div class="text-xs text-primary font-semibold mt-1">{{ __('Pending KYC') }}</div>
            </a>
            <a href="{{ route('admin.pin-requests') }}" class="card admin-action-card p-4 {{ $stats['pending_pin_requests'] > 0 ? 'pending-pins' : '' }}">
                <div style="font-size:28px;font-weight:800;color:{{ $stats['pending_pin_requests'] > 0 ? 'var(--info)' : 'var(--text-muted)' }};">{{ $stats['pending_pin_requests'] }}</div>
                <div class="text-xs text-primary font-semibold mt-1">{{ __('PIN Requests') }}</div>
            </a>
        </div>
    </div>

    {{-- Network & Branch Sync Status --}}
    <div class="card p-6 animate-fade-in-up delay-50">
        <h2 class="section-title mb-4">🌍 {{ __('Distributed Network Status') }}</h2>
        <div class="flex flex-column flex-gap-2">
            <div class="status-row-card">
                <div class="status-row-node">
                    <div class="status-row-dot pulsing"></div>
                    <span class="text-sm font-semibold">HQ Ledger (Global)</span>
                </div>
                <span class="badge badge-success" style="font-size:10px;">{{ __('SYNCED') }}</span>
            </div>
            <div class="status-row-card">
                <div class="status-row-node">
                    <div class="status-row-dot pulsing"></div>
                    <span class="text-sm font-semibold">Erbil Branch (Node 1)</span>
                </div>
                <span class="text-xs text-muted">12ms {{ __('latency') }}</span>
            </div>
            <div class="status-row-card">
                <div class="status-row-node">
                    <div class="status-row-dot pulsing"></div>
                    <span class="text-sm font-semibold">Sulaimaniyah Branch (Node 2)</span>
                </div>
                <span class="text-xs text-muted">18ms {{ __('latency') }}</span>
            </div>
            <div class="status-row-card">
                <div class="status-row-node">
                    <div class="status-row-dot pulsing"></div>
                    <span class="text-sm font-semibold">Duhok Branch (Node 3)</span>
                </div>
                <span class="text-xs text-muted">15ms {{ __('latency') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Reserve Health --}}
<div class="card p-6 mb-6 animate-fade-in-up delay-100">
    <div class="flex-between mb-4">
        <h2 class="section-title">🏦 {{ __('Reserve Health') }}</h2>
        <span class="badge badge-{{ $reserveHealth['status'] === 'excellent' ? 'success' : ($reserveHealth['status'] === 'good' ? 'info' : ($reserveHealth['status'] === 'warning' ? 'warning' : 'danger')) }}" style="font-size:12px;padding:5px 14px;">
            {{ __(ucfirst($reserveHealth['status'])) }} — {{ $reserveHealth['ratio'] }}%
        </span>
    </div>
    <div class="progress-bar">
        <div class="progress-bar-fill progress-fill-{{ $reserveHealth['status'] === 'excellent' ? 'excellent' : ($reserveHealth['status'] === 'good' ? 'good' : ($reserveHealth['status'] === 'warning' ? 'warning' : 'danger')) }}" style="width:{{ min($reserveHealth['ratio'], 100) }}%;"></div>
    </div>
    <div class="flex-between text-xs text-muted mt-2">
        <span>{{ __('Current') }}: ${{ number_format($reserveHealth['total_deposits'], 2) }}</span>
        <span>{{ __('Buffer') }}: ${{ number_format($reserveHealth['total_deposits'] - $reserveHealth['minimum'], 2) }}</span>
        <span>{{ __('Minimum') }}: ${{ number_format($reserveHealth['minimum'], 2) }}</span>
    </div>
    <div class="flex-between mt-3" style="border-top:1px solid var(--border);padding-top:12px;">
        <div>
            <div class="text-xs text-muted">{{ __('Lending Capacity') }}</div>
            <div class="font-semibold" style="color:{{ $loanCapacity > 0 ? 'var(--success)' : 'var(--danger)' }};">${{ number_format($loanCapacity, 2) }} <span class="text-xs text-muted">/ د.ع {{ number_format($loanCapacity * $iqdRate, 0) }}</span></div>
        </div>
        <div>
            <div class="text-xs text-muted">{{ __('IQD Rate') }}</div>
            <div class="font-semibold">1 USD = {{ number_format($iqdRate, 2) }} د.ع</div>
        </div>
    </div>
</div>

<div class="grid-2 grid-align-start mb-6">
    {{-- Left Column --}}
    <div>
        {{-- Pending Loans Quick View --}}
        @if($pendingLoans->count() > 0)
        <div class="card p-6 mb-6 animate-fade-in-up delay-200">
            <div class="flex-between mb-4">
                <h2 class="section-title">⏳ {{ __('Pending Loan Approvals') }}</h2>
                <a href="{{ route('admin.loans') }}" class="btn btn-ghost btn-sm">{{ __('View All →') }}</a>
            </div>
            @foreach($pendingLoans as $loan)
                <div class="admin-list-item">
                    <div class="flex-between">
                        <div class="flex flex-gap-2 align-items-center">
                            <div class="admin-avatar-mini">{{ $loan->user->initials }}</div>
                            <div>
                                <div class="text-sm font-medium">{{ $loan->user->name }}</div>
                                <div class="text-xs text-muted">{{ ucfirst($loan->loan_type) }} · {{ $loan->applied_at?->diffForHumans() }}</div>
                            </div>
                        </div>
                        <div class="font-semibold text-cyan">${{ number_format($loan->amount, 2) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- Recent Transactions --}}
        <div class="card animate-fade-in-up delay-200">
            <div class="section-header p-6" style="margin-bottom:0;">
                <h2 class="section-title">📋 {{ __('Recent Transactions') }}</h2>
            </div>
            @forelse($recentTransactions as $txn)
                <div class="transaction-item">
                    <div class="transaction-icon {{ $txn->isCredit() ? 'credit' : 'debit' }}">{{ $txn->isCredit() ? '↓' : '↑' }}</div>
                    <div class="transaction-details">
                        <div class="transaction-title">{{ $txn->account->user->name ?? __('System') }}</div>
                        <div class="transaction-meta">{{ $txn->description ?: __(ucfirst(str_replace('_', ' ', $txn->type))) }} · {{ $txn->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="transaction-amount {{ $txn->isCredit() ? 'credit' : 'debit' }}">${{ number_format($txn->amount, 2) }}</div>
                </div>
            @empty
                <div style="padding:40px 20px;text-align:center;"><div class="text-muted text-sm">{{ __('No transactions yet.') }}</div></div>
            @endforelse
        </div>
    </div>

    {{-- Right Column --}}
    <div>
        {{-- New Users --}}
        <div class="card p-6 mb-6 animate-fade-in-up delay-200">
            <div class="flex-between mb-4">
                <h2 class="section-title">🆕 {{ __('Recent Signups') }}</h2>
                <a href="{{ route('admin.users') }}" class="btn btn-ghost btn-sm">{{ __('All Users →') }}</a>
            </div>
            @foreach($recentUsers as $u)
                <div class="admin-list-item">
                    <div class="flex-between">
                        <div class="flex flex-gap-2 align-items-center">
                            <div class="admin-avatar-mini">{{ $u->initials }}</div>
                            <div>
                                <div class="text-sm font-medium">{{ $u->name }}</div>
                                <div class="text-xs text-muted">{{ $u->email }}</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            @php $sc = match($u->status) { 'active'=>'success','pending_verification'=>'warning',default=>'danger' }; @endphp
                            <span class="badge badge-{{ $sc }}" style="font-size:9px;">{{ __(ucfirst(str_replace('_',' ',$u->status))) }}</span>
                            <div class="text-xs text-muted mt-1">{{ $u->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Audit Log --}}
        @if(auth()->user()->isSuperAdmin())
        <div class="card animate-fade-in-up delay-300">
            <div class="section-header p-6" style="margin-bottom:0;">
                <h2 class="section-title">🔍 {{ __('Audit Log') }}</h2>
            </div>
            @foreach($recentAuditLogs as $log)
                <div class="admin-list-item" style="padding: 10px 20px;">
                    <div class="flex-between">
                        <span class="text-sm font-medium">{{ __(str_replace('_', ' ', ucfirst($log->action))) }}</span>
                        @php
                            $sevClass = match($log->severity) { 'critical'=>'danger','high'=>'warning','medium'=>'info',default=>'neutral' };
                            $sevIcon = match($log->severity) { 'critical'=>'🔴','high'=>'🟠','medium'=>'🟡',default=>'⚪' };
                        @endphp
                        <span class="badge badge-{{ $sevClass }}" style="font-size:9px;">{{ $sevIcon }} {{ $log->severity }}</span>
                    </div>
                    <div class="text-xs text-muted mt-1">{{ $log->user?->name ?? __('System') }} · {{ $log->created_at->diffForHumans() }}</div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- Quick Actions --}}
<div class="card p-6 mt-6 animate-fade-in-up delay-300">
    <h2 class="section-title mb-4">⚡ {{ __('Quick Actions') }}</h2>
    <div class="grid-fit-160">
        <a href="{{ route('admin.users') }}" class="quick-action"><div class="quick-action-icon">👥</div><span class="quick-action-label">{{ __('Manage Users') }}</span></a>
        <a href="{{ route('admin.loans') }}" class="quick-action"><div class="quick-action-icon">📋</div><span class="quick-action-label">{{ __('Loan Approvals') }}</span></a>
        <a href="{{ route('admin.kyc') }}" class="quick-action"><div class="quick-action-icon">🪪</div><span class="quick-action-label">{{ __('KYC Review') }}</span></a>
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.settings') }}" class="quick-action"><div class="quick-action-icon">⚙️</div><span class="quick-action-label">{{ __('Bank Settings') }}</span></a>
        @endif
        <a href="{{ route('admin.pin-requests') }}" class="quick-action"><div class="quick-action-icon">🔐</div><span class="quick-action-label">{{ __('PIN Requests') }}</span></a>
        <a href="{{ route('admin.cash') }}" class="quick-action"><div class="quick-action-icon">🏦</div><span class="quick-action-label">{{ __('Branch Cash') }}</span></a>
    </div>
</div>
@endsection
