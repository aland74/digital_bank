@extends('layouts.app')
@section('title', __('My Loans'))
@section('page-title', '📈 ' . __('My Loans'))
@section('page-subtitle', __('Manage your loan applications'))

@section('content')

{{-- Stats --}}
@php
    $activeLoans = $loans->where('status', 'approved')->count();
    $pendingLoans = $loans->where('status', 'pending')->count();
    $totalOutstanding = $loans->where('status', 'approved')->sum('remaining_balance');
    $totalPaid = $loans->sum('total_paid');
@endphp

<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #22c55e;">
        <div class="stat-icon green">✅</div>
        <div class="stat-value">{{ $activeLoans }}</div>
        <div class="stat-label">{{ __('Active Loans') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #f59e0b;">
        <div class="stat-icon orange">⏳</div>
        <div class="stat-value">{{ $pendingLoans }}</div>
        <div class="stat-label">{{ __('Pending') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">💰</div>
        <div class="stat-value">{{ $loans->count() }}</div>
        <div class="stat-label">{{ __('Total Loans') }}</div>
    </div>
</div>

{{-- Header --}}
<div class="flex-between mb-6">
    <h2 class="section-title">{{ $loans->count() }} {{ __('Loan') }}{{ $loans->count() !== 1 ? 's' : '' }}</h2>
    <a href="{{ route('loans.apply') }}" class="btn btn-primary">📋 {{ __('Apply for Loan') }}</a>
</div>

{{-- Loan Cards --}}
@forelse($loans as $loan)
    @php
        $cur = $loan->account ? \App\Models\Currency::where('code', $loan->account->currency)->first() : null;
        $sym = $cur?->symbol ?? '$';
        $dec = $cur?->decimal_places ?? 2;
        $currency = $loan->account->currency ?? 'USD';
        $statusColors = [
            'pending' => 'warning',
            'approved' => 'success',
            'active' => 'success',
            'completed' => 'info',
            'rejected' => 'danger',
            'defaulted' => 'danger',
        ];
        $loanIcons = [
            'personal' => '💰',
            'home' => '🏠',
            'auto' => '🚗',
            'business' => '💼',
            'education' => '🎓',
        ];
    @endphp
    <a href="{{ route('loans.show', $loan) }}" class="card p-6 animate-fade-in-up mb-4" style="display: block; text-decoration: none; border-left: 3px solid {{ $currency === 'USD' ? '#3b82f6' : '#f59e0b' }}; animation-delay: {{ $loop->index * 0.1 }}s;">
        <div class="flex-between mb-4">
            <div class="flex flex-gap-2">
                <span class="badge badge-purple">{{ $loanIcons[$loan->loan_type] ?? '💰' }} {{ __(ucfirst($loan->loan_type)) }}</span>
                <span class="badge badge-{{ $statusColors[$loan->status] ?? 'neutral' }}">{{ __(ucfirst($loan->status)) }}</span>
            </div>
            <span class="text-muted text-sm" style="font-family: monospace;">{{ $loan->loan_number }}</span>
        </div>

        <div class="flex-between mb-4">
            <div>
                <div style="font-size: 28px; font-weight: 700; color: {{ $currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">
                    {{ $sym }}{{ number_format($loan->amount, $dec) }}
                </div>
                <div class="text-muted text-sm">{{ __('Loan Amount') }} ({{ $currency }})</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 20px; font-weight: 600; color: var(--info);">
                    {{ $sym }}{{ number_format($loan->monthly_payment, $dec) }}
                </div>
                <div class="text-muted text-sm">{{ __('Monthly Payment') }}</div>
            </div>
        </div>

        @if(in_array($loan->status, ['approved', 'active']))
            <div class="progress-bar mb-2">
                <div class="progress-bar-fill" style="width: {{ $loan->progress_percentage }}%"></div>
            </div>
            <div class="flex-between text-sm text-muted">
                <span>{{ $loan->progress_percentage }}% {{ __('paid') }}</span>
                <span>{{ $sym }}{{ number_format($loan->remaining_balance, $dec) }} {{ __('remaining') }}</span>
            </div>
        @endif

        <div class="flex-between mt-4 text-sm text-muted" style="padding-top: 12px; border-top: 1px solid var(--border);">
            <span>{{ $loan->interest_rate }}% {{ __('APR') }}</span>
            <span>{{ $loan->term_months }} {{ __('months') }}</span>
            <span>{{ __('Applied') }} {{ $loan->applied_at?->format('M d, Y') }}</span>
        </div>
    </a>
@empty
    <div class="card p-6 animate-fade-in-up">
        <div class="empty-state">
            <div style="font-size: 64px; margin-bottom: 16px;">📈</div>
            <p class="empty-state-title">{{ __('No loans yet') }}</p>
            <p class="empty-state-text">{{ __('Apply for a personal, home, auto, business, or education loan.') }}</p>
            <a href="{{ route('loans.apply') }}" class="btn btn-primary mt-4">{{ __('Apply Now') }}</a>
        </div>
    </div>
@endforelse
@endsection
