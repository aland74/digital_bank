@extends('layouts.app')
@section('title', __('Apply for Loan'))
@section('page-title', __('Apply for Loan'))
@section('page-subtitle', __('Get competitive rates for all your needs'))

@section('content')
<div style="max-width:600px;">
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">📋 {{ __('Loan Application') }}</h2>

        <form method="POST" action="{{ route('loans.store') }}" data-loading>
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('Loan Type') }}</label>
                <select name="loan_type" class="form-select" required>
                    <option value="personal">💰 Personal Loan — 8.50% APR</option>
                    <option value="home">🏠 Home Loan — 4.25% APR</option>
                    <option value="auto">🚗 Auto Loan — 5.75% APR</option>
                    <option value="business">💼 Business Loan — 7.00% APR</option>
                    <option value="education">🎓 Education Loan — 3.50% APR</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Linked Card (Required)') }}</label>
                <select name="card_id" class="form-select" required id="loan-card-select" onchange="updateLoanCurrency()">
                    @foreach($cards as $card)
                        @php
                            $cardCur = $card->account ? \App\Models\Currency::where('code', $card->account->currency)->first() : null;
                            $cardSym = $cardCur?->symbol ?? '$';
                            $cardDec = $cardCur?->decimal_places ?? 2;
                        @endphp
                        <option value="{{ $card->id }}" data-currency="{{ $card->account->currency ?? 'USD' }}" data-symbol="{{ $cardSym }}" data-decimals="{{ $cardDec }}">
                            **** **** **** {{ $card->card_number_last4 }} — {{ ucfirst($card->card_brand) }} ({{ $card->account->currency ?? 'USD' }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted" style="display:block; margin-top:4px;">{{ __('The loan will be disbursed to the account linked to this card, and monthly payments will be automatically deducted from it.') }}</small>
            </div>

            <div class="form-group">
                <label class="form-label" id="loan-amount-label">{{ __('Loan Amount') }}</label>
                <input type="number" name="amount" class="form-input" placeholder="50000" min="1000" max="1000000" step="100" value="{{ old('amount') }}" required id="loan-amount-input">
                <div class="form-hint" id="loan-amount-hint">{{ __('Select a card to see currency details.') }}</div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Term (months)') }}</label>
                <select name="term_months" class="form-select" required>
                    @foreach([6,12,24,36,48,60,84,120,180,240,360] as $months)
                        <option value="{{ $months }}">{{ $months }} {{ __('months') }} ({{ round($months/12, 1) }} {{ __('years') }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Purpose') }} <span class="text-muted">({{ __('optional') }})</span></label>
                <textarea name="purpose" class="form-input" rows="3" placeholder="{{ __('What will you use this loan for?') }}">{{ old('purpose') }}</textarea>
            </div>

            <div class="alert alert-info">
                ℹ️ {{ __('Loan applications are typically reviewed within 24-48 hours. You will receive a notification once a decision is made.') }}
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px;">
                {{ __('Submit Application →') }}
            </button>
        </form>
    </div>
</div>
<script>
function updateLoanCurrency() {
    const select = document.getElementById('loan-card-select');
    const option = select.options[select.selectedIndex];
    const label = document.getElementById('loan-amount-label');
    const hint = document.getElementById('loan-amount-hint');

    if (option.value) {
        const currency = option.dataset.currency;
        const symbol = option.dataset.symbol;
        label.textContent = '{{ __("Loan Amount") }} (' + currency + ')';
        if (currency === 'IQD') {
            hint.textContent = '{{ __("Amount in") }} IQD. {{ __("Minimum") }} 1,000,000 IQD {{ __("— Maximum") }} 1,000,000,000 IQD';
            document.getElementById('loan-amount-input').min = 1000000;
            document.getElementById('loan-amount-input').max = 1000000000;
            document.getElementById('loan-amount-input').step = 100000;
            document.getElementById('loan-amount-input').placeholder = '50000000';
        } else {
            hint.textContent = '{{ __("Amount in") }} USD. {{ __("Minimum") }} $1,000 {{ __("— Maximum") }} $1,000,000';
            document.getElementById('loan-amount-input').min = 1000;
            document.getElementById('loan-amount-input').max = 1000000;
            document.getElementById('loan-amount-input').step = 100;
            document.getElementById('loan-amount-input').placeholder = '50000';
        }
    } else {
        label.textContent = '{{ __("Loan Amount") }}';
        hint.textContent = '{{ __("Select a card to see currency details.") }}';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateLoanCurrency();
});
</script>
@endsection
