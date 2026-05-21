@extends('layouts.app')
@section('title', 'Loan Details')
@section('page-title', 'Loan #' . $loan->loan_number)
@section('page-subtitle', ucfirst($loan->loan_type) . ' Loan')

@section('content')
<div class="grid-2" style="align-items:start;">
    <div class="card p-6 animate-fade-in-up">
        <div class="flex-between mb-6">
            <span class="badge badge-{{ $loan->status === 'active' ? 'success' : ($loan->status === 'pending' ? 'warning' : 'danger') }}" style="font-size:13px;padding:6px 16px;">
                {{ ucfirst($loan->status) }}
            </span>
            <span class="text-muted text-sm">{{ $loan->loan_number }}</span>
        </div>

        <div style="text-align:center;margin-bottom:24px;">
            <div style="font-size:40px;font-weight:800;color:var(--text-primary);">${{ number_format($loan->amount, 2) }}</div>
            <div class="text-muted">Loan Amount</div>
        </div>

        @if($loan->status === 'active')
            <div class="progress-bar mb-2" style="height:12px;">
                <div class="progress-bar-fill" style="width:{{ $loan->progress_percentage }}%"></div>
            </div>
            <div class="flex-between text-sm text-muted mb-6">
                <span>{{ $loan->progress_percentage }}% paid</span>
                <span>${{ number_format($loan->remaining_balance, 2) }} left</span>
            </div>
        @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="card p-4"><div class="text-muted text-xs">Interest Rate</div><div class="font-semibold mt-1">{{ $loan->interest_rate }}%</div></div>
            <div class="card p-4"><div class="text-muted text-xs">Monthly Payment</div><div class="font-semibold mt-1">${{ number_format($loan->monthly_payment, 2) }}</div></div>
            <div class="card p-4"><div class="text-muted text-xs">Term</div><div class="font-semibold mt-1">{{ $loan->term_months }} months</div></div>
            <div class="card p-4"><div class="text-muted text-xs">Total Interest</div><div class="font-semibold mt-1">${{ number_format($loan->total_interest, 2) }}</div></div>
            <div class="card p-4"><div class="text-muted text-xs">Total Paid</div><div class="font-semibold mt-1 text-green">${{ number_format($loan->total_paid, 2) }}</div></div>
            <div class="card p-4"><div class="text-muted text-xs">Remaining</div><div class="font-semibold mt-1 text-cyan">${{ number_format($loan->remaining_balance, 2) }}</div></div>
        </div>

        @if($loan->rejection_reason)
            <div class="alert alert-error mt-4">
                ❌ Rejection reason: {{ $loan->rejection_reason }}
            </div>
        @endif

        @if($loan->status === 'active' && $loan->remaining_balance > 0)
            <div style="margin-top:24px; padding-top:24px; border-top:1px solid var(--border);">
                <h4 class="font-semibold mb-3">Make a Payment</h4>
                <form method="POST" action="{{ route('loans.pay', $loan) }}">
                    @csrf
                    <div class="flex-between gap-3">
                        <div style="flex:1;">
                            <input type="number" name="amount" class="form-input" 
                                   min="{{ min($loan->remaining_balance, $loan->monthly_payment) }}" 
                                   max="{{ $loan->remaining_balance }}" 
                                   step="0.01" 
                                   value="{{ min($loan->remaining_balance, $loan->monthly_payment) }}" 
                                   required>
                            <div class="text-xs text-muted mt-1">Min: ${{ number_format(min($loan->remaining_balance, $loan->monthly_payment), 2) }} | Max: ${{ number_format($loan->remaining_balance, 2) }}</div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Pay Now</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    {{-- Repayment Schedule --}}
    <div class="card p-6 animate-fade-in-up delay-100">
        <h3 class="section-title mb-4">Repayment Schedule</h3>
        @if($repayments->count() > 0)
            <div style="max-height:400px;overflow-y:auto;">
                @foreach($repayments as $rep)
                    <div class="flex-between" style="padding:10px 0;border-bottom:1px solid var(--border);">
                        <div>
                            <div class="text-sm font-medium">#{{ $rep->installment_number }} — ${{ number_format($rep->amount, 2) }}</div>
                            <div class="text-xs text-muted">Due: {{ $rep->due_date->format('M d, Y') }}</div>
                        </div>
                        <span class="badge badge-{{ $rep->status === 'paid' ? 'success' : ($rep->status === 'overdue' ? 'danger' : 'neutral') }}">
                            {{ ucfirst($rep->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted text-sm">Repayment schedule will be generated once the loan is disbursed.</p>
        @endif
    </div>
</div>
@endsection
