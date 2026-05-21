@extends('layouts.app')
@section('title', __('My Loans'))
@section('page-title', __('Loans'))
@section('page-subtitle', __('Manage your loan applications'))

@section('content')
<div class="section-header">
    <h2 class="section-title">{{ $loans->count() }} {{ __('Loan') }}{{ $loans->count() !== 1 ? 's' : '' }}</h2>
    <a href="{{ route('loans.apply') }}" class="btn btn-primary">📋 {{ __('Apply for Loan') }}</a>
</div>

@forelse($loans as $loan)
    <a href="{{ route('loans.show', $loan) }}" class="card p-6 animate-fade-in-up mb-4" style="display:block;text-decoration:none;animation-delay:{{ $loop->index * 0.1 }}s">
        <div class="flex-between mb-4">
            <div>
                <span class="badge badge-purple">{{ __(ucfirst($loan->loan_type)) }}</span>
                <span class="badge badge-{{ $loan->status === 'active' ? 'success' : ($loan->status === 'pending' ? 'warning' : ($loan->status === 'rejected' ? 'danger' : 'neutral')) }}">
                    {{ __(ucfirst($loan->status)) }}
                </span>
            </div>
            <span class="text-muted text-sm">{{ $loan->loan_number }}</span>
        </div>
        <div class="flex-between mb-4">
            <div>
                <div style="font-size:28px;font-weight:700;color:var(--text-primary);">${{ number_format($loan->amount, 2) }}</div>
                <div class="text-muted text-sm">{{ __('Loan Amount') }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:20px;font-weight:600;color:var(--info);">${{ number_format($loan->monthly_payment, 2) }}</div>
                <div class="text-muted text-sm">{{ __('Monthly Payment') }}</div>
            </div>
        </div>
        @if($loan->status === 'active')
            <div class="progress-bar mb-2">
                <div class="progress-bar-fill" style="width:{{ $loan->progress_percentage }}%"></div>
            </div>
            <div class="flex-between text-sm text-muted">
                <span>{{ $loan->progress_percentage }}% {{ __('paid') }}</span>
                <span>${{ number_format($loan->remaining_balance, 2) }} {{ __('remaining') }}</span>
            </div>
        @endif
        <div class="flex-between mt-4 text-sm text-muted" style="padding-top:12px;border-top:1px solid var(--border);">
            <span>{{ $loan->interest_rate }}% {{ __('APR') }}</span>
            <span>{{ $loan->term_months }} {{ __('months') }}</span>
            <span>{{ __('Applied') }} {{ $loan->applied_at?->format('M d, Y') }}</span>
        </div>
    </a>
@empty
    <div class="card p-6">
        <div class="empty-state">
            <div class="empty-state-icon">📈</div>
            <p class="empty-state-title">{{ __('No loans yet') }}</p>
            <p class="empty-state-text">{{ __('Apply for a personal, home, or business loan today.') }}</p>
            <a href="{{ route('loans.apply') }}" class="btn btn-primary">{{ __('Apply Now') }}</a>
        </div>
    </div>
@endforelse
@endsection
