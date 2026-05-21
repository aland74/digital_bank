@extends('layouts.app')
@section('title', __('Apply for Loan'))
@section('page-title', '📋 ' . __('Apply for Loan'))
@section('page-subtitle', __('Get competitive rates for all your needs'))

@section('content')
<div style="max-width: 700px;">

    {{-- Reserve Health Warning --}}
    @if(!$reserveHealth['healthy'])
        <div class="alert alert-error mb-4 animate-fade-in-up">
            ⚠️ {{ __('We are unable to process loan applications at this time. Our lending capacity has been temporarily reached. Please try again later.') }}
        </div>
    @endif

    {{-- Loan Types Info --}}
    <div class="card p-4 mb-4 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(168, 85, 247, 0.05));">
        <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Available Loan Types') }}</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px;">
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">💰</div>
                <div class="text-xs font-semibold">{{ __('Personal') }}</div>
                <div class="text-xs text-muted">8.50% APR</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🏠</div>
                <div class="text-xs font-semibold">{{ __('Home') }}</div>
                <div class="text-xs text-muted">4.25% APR</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🚗</div>
                <div class="text-xs font-semibold">{{ __('Auto') }}</div>
                <div class="text-xs text-muted">5.75% APR</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">💼</div>
                <div class="text-xs font-semibold">{{ __('Business') }}</div>
                <div class="text-xs text-muted">7.00% APR</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🎓</div>
                <div class="text-xs font-semibold">{{ __('Education') }}</div>
                <div class="text-xs text-muted">3.50% APR</div>
            </div>
        </div>
    </div>

    {{-- Application Form --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">📋 {{ __('Loan Application') }}</h2>

        <form method="POST" action="{{ route('loans.store') }}" id="loan-form" data-loading>
            @csrf

            {{-- Loan Type --}}
            <div class="form-group">
                <label class="form-label">{{ __('Loan Type') }}</label>
                <select name="loan_type" class="form-select" id="loan-type" required onchange="updateLoanInfo()">
                    <option value="">{{ __('Select loan type...') }}</option>
                    <option value="personal" {{ old('loan_type') === 'personal' ? 'selected' : '' }}>💰 {{ __('Personal Loan') }} — 8.50% APR</option>
                    <option value="home" {{ old('loan_type') === 'home' ? 'selected' : '' }}>🏠 {{ __('Home Loan') }} — 4.25% APR</option>
                    <option value="auto" {{ old('loan_type') === 'auto' ? 'selected' : '' }}>🚗 {{ __('Auto Loan') }} — 5.75% APR</option>
                    <option value="business" {{ old('loan_type') === 'business' ? 'selected' : '' }}>💼 {{ __('Business Loan') }} — 7.00% APR</option>
                    <option value="education" {{ old('loan_type') === 'education' ? 'selected' : '' }}>🎓 {{ __('Education Loan') }} — 3.50% APR</option>
                </select>
            </div>

            {{-- Linked Card --}}
            <div class="form-group">
                <label class="form-label">{{ __('Linked Card (Required)') }}</label>
                <select name="card_id" class="form-select" id="loan-card" required onchange="updateCardInfo()">
                    <option value="">{{ __('Select a card...') }}</option>
                    @foreach($cards as $card)
                        @php
                            $cardCur = $card->account ? \App\Models\Currency::where('code', $card->account->currency)->first() : null;
                            $cardSym = $cardCur?->symbol ?? '$';
                            $cardDec = $cardCur?->decimal_places ?? 2;
                            $cardCurrency = $card->account->currency ?? 'USD';
                        @endphp
                        <option value="{{ $card->id }}"
                                data-currency="{{ $cardCurrency }}"
                                data-symbol="{{ $cardSym }}"
                                data-decimals="{{ $cardDec }}"
                                {{ old('card_id') == $card->id ? 'selected' : '' }}>
                            {{ $cardCurrency === 'USD' ? '🇺🇸' : '🇮🇶' }} ****{{ $card->card_number_last4 }} — {{ ucfirst($card->card_brand) }} ({{ $cardCurrency }})
                        </option>
                    @endforeach
                </select>
                <div class="form-hint">{{ __('The loan will be disbursed to the account linked to this card.') }}</div>
            </div>

            {{-- Account Info (shown after card selection) --}}
            <div id="account-info" style="display: none; margin-bottom: 16px;">
                <div style="padding: 12px; background: var(--bg-secondary); border-radius: var(--radius-md); border-left: 3px solid #3b82f6;" id="account-info-card">
                    <div class="flex-between">
                        <div>
                            <div class="text-xs text-muted">{{ __('Disbursement Account') }}</div>
                            <div class="font-semibold" id="account-info-number"></div>
                        </div>
                        <div style="text-align: right;">
                            <div class="text-xs text-muted">{{ __('Available Balance') }}</div>
                            <div class="font-bold" id="account-info-balance"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Loan Amount --}}
            <div class="form-group">
                <label class="form-label" id="amount-label">{{ __('Loan Amount') }}</label>
                <input type="number" name="amount" class="form-input" id="loan-amount"
                       min="1000" max="1000000" step="100"
                       placeholder="50000" value="{{ old('amount') }}"
                       required style="font-size: 28px; font-weight: 700; text-align: center;">
                <div class="form-hint" id="amount-hint">{{ __('Select a card to see currency details.') }}</div>
                @error('amount')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Quick Amount Buttons --}}
            <div class="form-group" id="quick-amounts" style="display: none;">
                <div class="flex flex-gap-2 flex-wrap">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(10000)">$10K</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(25000)">$25K</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(50000)">$50K</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(100000)">$100K</button>
                </div>
            </div>

            {{-- Term --}}
            <div class="form-group">
                <label class="form-label">{{ __('Loan Term') }}</label>
                <select name="term_months" class="form-select" id="loan-term" required onchange="updateLoanEstimate()">
                    <option value="">{{ __('Select term...') }}</option>
                    @foreach([6, 12, 24, 36, 48, 60, 84, 120, 180, 240, 360] as $months)
                        <option value="{{ $months }}" {{ old('term_months') == $months ? 'selected' : '' }}>
                            {{ $months }} {{ __('months') }} ({{ round($months / 12, 1) }} {{ __('years') }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Loan Estimate --}}
            <div id="loan-estimate" style="display: none; margin-bottom: 16px;">
                <div style="padding: 16px; background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(168, 85, 247, 0.05)); border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Loan Estimate') }}</div>
                    <div class="flex-between mb-2">
                        <span class="text-sm text-muted">{{ __('Monthly Payment') }}</span>
                        <span class="font-bold" id="estimate-monthly" style="color: #3b82f6;">—</span>
                    </div>
                    <div class="flex-between mb-2">
                        <span class="text-sm text-muted">{{ __('Total Interest') }}</span>
                        <span class="font-semibold" id="estimate-interest">—</span>
                    </div>
                    <div class="flex-between">
                        <span class="text-sm text-muted">{{ __('Total Repayment') }}</span>
                        <span class="font-semibold" id="estimate-total">—</span>
                    </div>
                </div>
            </div>

            {{-- Purpose --}}
            <div class="form-group">
                <label class="form-label">{{ __('Purpose (optional)') }}</label>
                <textarea name="purpose" class="form-input" rows="3" placeholder="{{ __('Briefly describe what you will use the loan for...') }}" maxlength="500">{{ old('purpose') }}</textarea>
                <div class="form-hint">{{ __('Max 500 characters') }}</div>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn btn-primary btn-lg w-full" id="submit-btn" {{ !$reserveHealth['healthy'] ? 'disabled' : '' }}>
                {{ !$reserveHealth['healthy'] ? '⚠️ ' . __('Applications Temporarily Unavailable') : '📋 ' . __('Submit Application') }}
            </button>
        </form>
    </div>

    {{-- Back --}}
    <div class="mt-4 text-center">
        <a href="{{ route('loans.index') }}" class="text-sm text-muted">← {{ __('Back to My Loans') }}</a>
    </div>
</div>

<script>
const interestRates = {
    personal: 8.50,
    home: 4.25,
    auto: 5.75,
    business: 7.00,
    education: 3.50
};

let currentCurrency = 'USD';
let currentSymbol = '$';
let currentDecimals = 2;

function updateCardInfo() {
    const select = document.getElementById('loan-card');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('account-info');

    if (option.value) {
        currentCurrency = option.dataset.currency;
        currentSymbol = option.dataset.symbol;
        currentDecimals = parseInt(option.dataset.decimals);

        // Update amount label and constraints
        document.getElementById('amount-label').textContent = '{{ __("Loan Amount") }} (' + currentCurrency + ')';

        if (currentCurrency === 'IQD') {
            document.getElementById('loan-amount').min = 1000000;
            document.getElementById('loan-amount').max = 1000000000;
            document.getElementById('loan-amount').step = 100000;
            document.getElementById('loan-amount').placeholder = '50000000';
            document.getElementById('amount-hint').textContent = '{{ __("Min") }}: 1,000,000 IQD — {{ __("Max") }}: 1,000,000,000 IQD';

            // Update quick amounts
            document.getElementById('quick-amounts').innerHTML = `
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(10000000)">10M</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(50000000)">50M</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(100000000)">100M</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(500000000)">500M</button>
            `;
        } else {
            document.getElementById('loan-amount').min = 1000;
            document.getElementById('loan-amount').max = 1000000;
            document.getElementById('loan-amount').step = 100;
            document.getElementById('loan-amount').placeholder = '50000';
            document.getElementById('amount-hint').textContent = '{{ __("Min") }}: $1,000 — {{ __("Max") }}: $1,000,000';

            // Update quick amounts
            document.getElementById('quick-amounts').innerHTML = `
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(10000)">$10K</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(25000)">$25K</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(50000)">$50K</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setLoanAmount(100000)">$100K</button>
            `;
        }
        document.getElementById('quick-amounts').style.display = 'flex';

        infoDiv.style.display = 'block';
        document.getElementById('account-info-card').style.borderLeftColor = currentCurrency === 'USD' ? '#3b82f6' : '#f59e0b';
        document.getElementById('account-info-balance').textContent = currentSymbol + '{{ __("Select a card") }}';
        document.getElementById('account-info-balance').style.color = currentCurrency === 'USD' ? '#3b82f6' : '#f59e0b';
    } else {
        infoDiv.style.display = 'none';
        document.getElementById('quick-amounts').style.display = 'none';
    }

    updateLoanEstimate();
}

function updateLoanInfo() {
    updateLoanEstimate();
}

function updateLoanEstimate() {
    const loanType = document.getElementById('loan-type').value;
    const amount = parseFloat(document.getElementById('loan-amount').value);
    const term = parseInt(document.getElementById('loan-term').value);
    const estimateDiv = document.getElementById('loan-estimate');

    if (loanType && amount > 0 && term > 0) {
        const rate = interestRates[loanType] / 100 / 12;
        let monthly, totalInterest, total;

        if (rate === 0) {
            monthly = amount / term;
            totalInterest = 0;
            total = amount;
        } else {
            monthly = amount * (rate * Math.pow(1 + rate, term)) / (Math.pow(1 + rate, term) - 1);
            totalInterest = (monthly * term) - amount;
            total = monthly * term;
        }

        document.getElementById('estimate-monthly').textContent = currentSymbol + monthly.toLocaleString(undefined, {minimumFractionDigits: currentDecimals, maximumFractionDigits: currentDecimals});
        document.getElementById('estimate-monthly').style.color = currentCurrency === 'USD' ? '#3b82f6' : '#f59e0b';
        document.getElementById('estimate-interest').textContent = currentSymbol + totalInterest.toLocaleString(undefined, {minimumFractionDigits: currentDecimals, maximumFractionDigits: currentDecimals});
        document.getElementById('estimate-total').textContent = currentSymbol + total.toLocaleString(undefined, {minimumFractionDigits: currentDecimals, maximumFractionDigits: currentDecimals});

        estimateDiv.style.display = 'block';
    } else {
        estimateDiv.style.display = 'none';
    }
}

function setLoanAmount(amount) {
    const input = document.getElementById('loan-amount');
    const max = parseFloat(input.max);
    input.value = Math.min(amount, max);
    updateLoanEstimate();
}

// Add event listener to amount input
document.getElementById('loan-amount').addEventListener('input', updateLoanEstimate);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCardInfo();
});
</script>
@endsection
