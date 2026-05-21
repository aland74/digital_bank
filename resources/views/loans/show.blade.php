@extends('layouts.app')
@section('title', __('Loan Details'))
@section('page-title', $loanIcons[$loan->loan_type] ?? '💰' . ' ' . __('Loan') . ' #' . $loan->loan_number)
@section('page-subtitle', __(ucfirst($loan->loan_type)) . ' ' . __('Loan'))

@section('content')
@php
    $cur = $loan->account ? \App\Models\Currency::where('code', $loan->account->currency)->first() : null;
    $sym = $cur?->symbol ?? '$';
    $dec = $cur?->decimal_places ?? 2;
    $currency = $loan->account->currency ?? 'USD';
    $loanIcons = [
        'personal' => '💰',
        'home' => '🏠',
        'auto' => '🚗',
        'business' => '💼',
        'education' => '🎓',
    ];
    $statusColors = [
        'pending' => 'warning',
        'approved' => 'success',
        'active' => 'success',
        'completed' => 'info',
        'rejected' => 'danger',
        'defaulted' => 'danger',
    ];
@endphp

<div class="grid-2" style="align-items: start;">

    {{-- LEFT: Loan Details --}}
    <div>
        {{-- Status Card --}}
        <div class="card p-6 mb-4 animate-fade-in-up" style="border-left: 3px solid {{ $currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">
            <div class="flex-between mb-4">
                <div class="flex flex-gap-2">
                    <span class="badge badge-{{ $statusColors[$loan->status] ?? 'neutral' }}" style="font-size: 13px; padding: 6px 16px;">
                        {{ __(ucfirst($loan->status)) }}
                    </span>
                </div>
                <span class="text-muted text-sm" style="font-family: monospace;">{{ $loan->loan_number }}</span>
            </div>

            <div style="text-align: center; margin-bottom: 24px;">
                <div style="font-size: 48px; font-weight: 800; color: {{ $currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">
                    {{ $sym }}{{ number_format($loan->amount, $dec) }}
                </div>
                <div class="text-muted">{{ __('Loan Amount') }} ({{ $currency }})</div>
            </div>

            @if(in_array($loan->status, ['approved', 'active']))
                <div class="progress-bar mb-2" style="height: 12px;">
                    <div class="progress-bar-fill" style="width: {{ $loan->progress_percentage }}%"></div>
                </div>
                <div class="flex-between text-sm text-muted mb-4">
                    <span>{{ $loan->progress_percentage }}% {{ __('paid') }}</span>
                    <span>{{ $sym }}{{ number_format($loan->remaining_balance, $dec) }} {{ __('left') }}</span>
                </div>
            @endif

            {{-- Info Grid --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Interest Rate') }}</div>
                    <div class="font-semibold mt-1">{{ $loan->interest_rate }}% {{ __('APR') }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Monthly Payment') }}</div>
                    <div class="font-semibold mt-1" style="color: {{ $currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">{{ $sym }}{{ number_format($loan->monthly_payment, $dec) }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Term') }}</div>
                    <div class="font-semibold mt-1">{{ $loan->term_months }} {{ __('months') }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Total Interest') }}</div>
                    <div class="font-semibold mt-1">{{ $sym }}{{ number_format($loan->total_interest, $dec) }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Total Paid') }}</div>
                    <div class="font-semibold mt-1 text-green">{{ $sym }}{{ number_format($loan->total_paid, $dec) }}</div>
                </div>
                <div class="card p-4">
                    <div class="text-muted text-xs">{{ __('Remaining') }}</div>
                    <div class="font-semibold mt-1" style="color: var(--info);">{{ $sym }}{{ number_format($loan->remaining_balance, $dec) }}</div>
                </div>
            </div>

            @if($loan->rejection_reason)
                <div class="alert alert-error mt-4">
                    ❌ {{ __('Rejection reason') }}: {{ $loan->rejection_reason }}
                </div>
            @endif

            @if($loan->purpose)
                <div class="mt-4" style="padding: 12px; background: var(--bg-secondary); border-radius: var(--radius-md);">
                    <div class="text-xs text-muted mb-1">{{ __('Purpose') }}</div>
                    <div class="text-sm">{{ $loan->purpose }}</div>
                </div>
            @endif
        </div>

        {{-- Make Payment --}}
        @if(in_array($loan->status, ['approved', 'active']) && $loan->remaining_balance > 0)
            <div class="card p-6 animate-fade-in-up">
                <h3 class="section-title mb-4">💳 {{ __('Make a Payment') }}</h3>
                <div class="mb-4" style="padding: 12px; background: rgba(59, 130, 246, 0.05); border-radius: var(--radius-md); border-left: 3px solid #3b82f6;">
                    <div class="text-xs text-muted">{{ __('Linked Account') }}</div>
                    <div class="font-semibold">{{ $loan->account->account_number ?? 'N/A' }} ({{ $currency }})</div>
                    <div class="text-xs text-muted">{{ __('Available') }}: {{ $sym }}{{ number_format($loan->account->available_balance ?? 0, $dec) }}</div>
                </div>

                <form method="POST" action="{{ route('loans.pay', $loan) }}" data-loading>
                    @csrf
                    <div class="form-group">
                        <label class="form-label">{{ __('Payment Amount') }} ({{ $currency }})</label>
                        <input type="number" name="amount" class="form-input"
                               min="{{ min($loan->remaining_balance, $loan->monthly_payment) }}"
                               max="{{ $loan->remaining_balance }}"
                               step="{{ $dec === 0 ? '1' : '0.01' }}"
                               value="{{ min($loan->remaining_balance, $loan->monthly_payment) }}"
                               required style="font-size: 24px; font-weight: 700; text-align: center;">
                        <div class="flex-between text-xs text-muted mt-1">
                            <span>{{ __('Min') }}: {{ $sym }}{{ number_format(min($loan->remaining_balance, $loan->monthly_payment), $dec) }}</span>
                            <span>{{ __('Max') }}: {{ $sym }}{{ number_format($loan->remaining_balance, $dec) }}</span>
                        </div>
                    </div>

                    {{-- Quick Amount Buttons --}}
                    <div class="flex flex-gap-2 mb-4">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanPayment({{ min($loan->remaining_balance, $loan->monthly_payment) }})">{{ __('Min') }}</button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanPayment({{ $loan->monthly_payment * 2 }})">{{ __('2x Payment') }}</button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanPayment({{ $loan->remaining_balance }})">{{ __('Pay Off') }}</button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-full" onclick="return confirm('{{ __('Confirm payment?') }}')">
                        💳 {{ __('Make Payment') }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- RIGHT: Repayment Schedule --}}
    <div class="card p-6 animate-fade-in-up">
        <h3 class="section-title mb-4">📅 {{ __('Repayment Schedule') }}</h3>
        @if($repayments->count() > 0)
            <div style="max-height: 600px; overflow-y: auto;">
                @foreach($repayments as $rep)
                    @php
                        $isPaid = $rep->status === 'paid';
                        $isOverdue = $rep->status === 'overdue';
                    @endphp
                    <div class="flex-between" style="padding: 12px; border-bottom: 1px solid var(--border); {{ $isOverdue ? 'background: rgba(239, 68, 68, 0.05);' : '' }}">
                        <div>
                            <div class="text-sm font-semibold">
                                #{{ $rep->installment_number }} — {{ $sym }}{{ number_format($rep->amount, $dec) }}
                            </div>
                            <div class="text-xs text-muted">{{ __('Due') }}: {{ $rep->due_date->format('M d, Y') }}</div>
                            @if($rep->paid_at)
                                <div class="text-xs text-green">{{ __('Paid') }}: {{ $rep->paid_at->format('M d, Y') }}</div>
                            @endif
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-{{ $isPaid ? 'success' : ($isOverdue ? 'danger' : 'neutral') }}">
                                {{ $isPaid ? '✅ ' : '' }}{{ __(ucfirst($rep->status)) }}
                            </span>
                            @if(!$isPaid && $rep->penalty_amount > 0)
                                <div class="text-xs text-red mt-1">+{{ $sym }}{{ number_format($rep->penalty_amount, $dec) }} {{ __('penalty') }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Schedule Summary --}}
            <div class="mt-4 pt-4" style="border-top: 2px solid var(--border);">
                <div class="flex-between text-sm mb-2">
                    <span class="text-muted">{{ __('Total Installments') }}</span>
                    <span class="font-semibold">{{ $repayments->count() }}</span>
                </div>
                <div class="flex-between text-sm mb-2">
                    <span class="text-muted">{{ __('Paid') }}</span>
                    <span class="font-semibold text-green">{{ $repayments->where('status', 'paid')->count() }}</span>
                </div>
                <div class="flex-between text-sm mb-2">
                    <span class="text-muted">{{ __('Remaining') }}</span>
                    <span class="font-semibold">{{ $repayments->where('status', '!=', 'paid')->count() }}</span>
                </div>
                @if($repayments->where('status', 'overdue')->count() > 0)
                    <div class="flex-between text-sm">
                        <span class="text-red">{{ __('Overdue') }}</span>
                        <span class="font-semibold text-red">{{ $repayments->where('status', 'overdue')->count() }}</span>
                    </div>
                @endif
            </div>
        @else
            <div class="empty-state" style="padding: 24px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📅</div>
                <p class="empty-state-title">{{ __('No schedule yet') }}</p>
                <p class="empty-state-text">{{ __('The repayment schedule will be generated once the loan is approved and disbursed.') }}</p>
            </div>
        @endif

        {{-- Back Button --}}
        <div class="mt-4 pt-4" style="border-top: 1px solid var(--border);">
            <a href="{{ route('loans.index') }}" class="btn btn-ghost w-full">← {{ __('Back to Loans') }}</a>
        </div>
    </div>
</div>

<script>
function setLoanPayment(amount) {
    const input = document.querySelector('input[name="amount"]');
    const max = parseFloat(input.max);
    input.value = Math.min(amount, max);
}
</script>
@endsection
