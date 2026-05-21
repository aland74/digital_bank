@extends('layouts.app')
@section('title', __('Pending Loans'))
@section('page-title', '📋 ' . __('Loan Approvals'))
@section('page-subtitle', $loans->count() . ' ' . __('pending applications'))

@section('content')
{{-- Reserve Warning --}}
@if(!$reserveHealth['healthy'])
    <div class="alert alert-error animate-fade-in-up mb-4">
        ⚠️ <strong>{{ __('LOW RESERVES!') }}</strong> {{ __('Reserves at') }} {{ $reserveHealth['ratio'] }}%. {{ __('New loan approvals that breach the minimum') }} (${{ number_format($reserveHealth['minimum'], 2) }}) {{ __('will be blocked.') }}
    </div>
@endif

<div class="card p-4 mb-4 animate-fade-in-up flex-between align-items-center">
    <div>
        <span class="text-muted text-xs">{{ __('Bank Reserves') }}</span>
        <div class="font-semibold text-white">${{ number_format($reserveHealth['total_deposits'], 2) }}</div>
    </div>
    <div>
        <span class="text-muted text-xs">{{ __('Minimum Required') }}</span>
        <div class="font-semibold text-white">${{ number_format($reserveHealth['minimum'], 2) }}</div>
    </div>
    <div>
        <span class="text-muted text-xs">{{ __('Status') }}</span>
        <span class="badge badge-{{ $reserveHealth['healthy'] ? 'success' : 'danger' }}">{{ $reserveHealth['healthy'] ? __('Healthy') : __('Critical') }}</span>
    </div>
</div>

@forelse($loans as $loan)
    @php
        $wouldBreachReserve = ($reserveHealth['total_deposits'] - $loan->amount) < $reserveHealth['minimum'];
    @endphp
    <div class="card p-6 mb-4 animate-fade-in-up {{ $wouldBreachReserve ? 'border-l-3 border-red-500' : '' }}" style="animation-delay:{{ $loop->index * 0.1 }}s;">
        <div class="flex-between mb-4">
            <div class="flex align-items-center flex-gap-2">
                <div class="avatar-initials-normal">
                    {{ $loan->user->initials }}
                </div>
                <div>
                    <div class="font-semibold text-white">{{ $loan->user->name }}</div>
                    <div class="text-sm text-muted">{{ $loan->user->email }}</div>
                </div>
            </div>
            <span class="badge badge-warning">{{ __('Pending') }}</span>
        </div>

        <div class="grid-4 mb-4">
            <div><div class="text-muted text-xs">{{ __('Type') }}</div><div class="font-medium mt-1 text-white">{{ __(ucfirst($loan->loan_type)) }}</div></div>
            <div><div class="text-muted text-xs">{{ __('Amount') }}</div><div class="font-semibold mt-1 text-cyan">${{ number_format($loan->amount, 2) }}</div></div>
            <div><div class="text-muted text-xs">{{ __('Term') }}</div><div class="font-medium mt-1 text-white">{{ $loan->term_months }} {{ __('months') }}</div></div>
            <div><div class="text-muted text-xs">{{ __('Applied') }}</div><div class="font-medium mt-1 text-white">{{ $loan->applied_at?->diffForHumans() }}</div></div>
        </div>

        <div class="card p-4 mb-4 bg-black/20">
            <div class="grid-3 mb-3">
                <div><div class="text-muted text-xs">{{ __('Interest Rate') }}</div><div class="font-medium mt-1 text-white">{{ $loan->interest_rate }}% APR</div></div>
                <div><div class="text-muted text-xs">{{ __('Monthly Payment') }}</div><div class="font-medium mt-1 text-white">${{ number_format($loan->monthly_payment, 2) }}</div></div>
                <div><div class="text-muted text-xs">{{ __('Total Interest') }}</div><div class="font-medium mt-1 text-white">${{ number_format($loan->total_interest, 2) }}</div></div>
            </div>
            
            <div class="grid-2 mt-3 pt-3 border-top-divider">
                <div>
                    <div class="text-muted text-xs">{{ __('Linked User Accounts') }}</div>
                    <div class="font-medium mt-1 text-white">
                        @foreach($loan->user->accounts as $acc)
                            <div class="text-sm">{{ __(ucfirst($acc->account_type)) }}: ${{ number_format($acc->balance, 2) }}</div>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="text-muted text-xs">{{ __('Disbursement Target') }}</div>
                    <div class="font-medium mt-1 text-sm text-white">{{ $loan->account->account_number ?? __('Unknown') }}</div>
                </div>
            </div>

            @if($loan->purpose)
                <div class="mt-3 pt-3 border-top-divider">
                    <div class="text-muted text-xs mb-1">{{ __('Stated Purpose') }}</div>
                    <div class="text-sm text-white italic">"{{ $loan->purpose }}"</div>
                </div>
            @endif
            
            <div class="mt-3 text-right">
                <a href="{{ route('admin.users.show', $loan->user) }}" class="text-xs text-cyan hover:text-white transition">{{ __('View Full User Profile →') }}</a>
            </div>
        </div>

        @if($wouldBreachReserve)
            <div class="alert alert-error mb-2 text-xs py-2 px-3">
                ⚠️ {{ __('Approving this loan') }} (${{ number_format($loan->amount, 2) }}) {{ __('would drop reserves below the minimum. Projected reserves:') }} ${{ number_format($reserveHealth['total_deposits'] - $loan->amount, 2) }}
            </div>
        @endif

        <div class="flex flex-gap-2">
            <form method="POST" action="{{ route('admin.loans.approve', $loan) }}">
                @csrf
                <button class="btn btn-success btn-sm" {{ $wouldBreachReserve ? 'disabled title="Would breach reserve minimum"' : '' }}>✓ {{ __('Approve') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.loans.reject', $loan) }}" class="flex flex-gap-2 flex-1">
                @csrf
                <input type="text" name="rejection_reason" class="form-input flex-1 py-1.5 px-3 font-size-13" placeholder="{{ __('Rejection reason...') }}" required>
                <button class="btn btn-danger btn-sm">✕ {{ __('Reject') }}</button>
            </form>
        </div>
    </div>
@empty
    <div class="card p-6"><div class="empty-state"><div class="empty-state-icon">✅</div><p class="empty-state-title">{{ __('No pending loans') }}</p><p class="empty-state-text">{{ __('All loan applications have been processed.') }}</p></div></div>
@endforelse
@endsection
