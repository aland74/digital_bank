@extends('layouts.app')
@section('title', __('ATM / Cash Out'))
@section('page-title', '🏧 ' . __('ATM / Cash Out'))
@section('page-subtitle', __('Simulate an ATM cash withdrawal'))

@section('content')
<div style="max-width:500px; margin: 0 auto;">
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-4">{{ __('Digital to Physical Cash') }}</h2>
        <p class="text-sm text-muted mb-6">{{ __('Select your account and enter an amount to simulate withdrawing physical cash from an ATM. The funds will be deducted from your account instantly.') }}</p>

        <form method="POST" action="{{ route('cash.out') }}" data-loading>
            @csrf

            <div class="form-group">
                <label class="form-label">{{ __('From Account') }}</label>
                <select name="account_id" class="form-select" required id="atm-account-select" onchange="updateAtmCurrency()">
                    <option value="">{{ __('Select an account...') }}</option>
                    @foreach($accounts as $acc)
                        @php
                            $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                            $sym = $cur?->symbol ?? $acc->currency;
                            $dec = $cur?->decimal_places ?? 2;
                        @endphp
                        <option value="{{ $acc->id }}" data-currency="{{ $acc->currency }}" data-symbol="{{ $sym }}" data-decimals="{{ $dec }}">
                            {{ $acc->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ ucfirst($acc->account_type) }} - ****{{ substr($acc->account_number, -4) }} ({{ $sym }}{{ number_format($acc->available_balance, $dec) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" id="atm-amount-label">{{ __('Withdrawal Amount') }}</label>
                <input type="number" name="amount" class="form-input" min="10" max="5000" step="10" required placeholder="e.g. 100" id="atm-amount-input">
                <div class="form-hint" id="atm-amount-hint">{{ __('Select an account to see currency details.') }}</div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Card PIN') }}</label>
                <div class="password-wrapper">
                    <input type="password" name="pin" class="form-input" required maxlength="4" placeholder="****" pattern="[0-9]*" inputmode="numeric">
                    <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full">
                <span class="btn-text">💵 {{ __('Withdraw Cash') }}</span>
            </button>
        </form>
    </div>
</div>
<script>
function updateAtmCurrency() {
    const select = document.getElementById('atm-account-select');
    const option = select.options[select.selectedIndex];
    const label = document.getElementById('atm-amount-label');
    const hint = document.getElementById('atm-amount-hint');

    if (option.value) {
        const currency = option.dataset.currency;
        const symbol = option.dataset.symbol;
        label.textContent = '{{ __("Withdrawal Amount") }} (' + currency + ')';
        hint.textContent = '{{ __("Amount in") }} ' + currency + '. {{ __("Must be in multiples of 10.") }}';
    } else {
        label.textContent = '{{ __("Withdrawal Amount") }}';
        hint.textContent = '{{ __("Select an account to see currency details.") }}';
    }
}
</script>
@endsection
