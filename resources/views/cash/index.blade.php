@extends('layouts.app')
@section('title', __('ATM / Cash Out'))
@section('page-title', '🏧 ' . __('ATM / Cash Out'))
@section('page-subtitle', __('Withdraw cash from your digital accounts'))

@section('content')
<div style="max-width: 560px;">

    {{-- ATM Card --}}
    <div class="card animate-fade-in-up" style="overflow: hidden;">
        {{-- ATM Header --}}
        <div style="padding: 32px 24px; background: linear-gradient(135deg, #1e293b, #0f172a); text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">🏧</div>
            <h2 style="font-size: 24px; font-weight: 700; color: white; margin: 0 0 4px;">{{ __('ATM Cash Withdrawal') }}</h2>
            <p style="font-size: 14px; color: rgba(255,255,255,0.6); margin: 0;">{{ __('Convert digital funds to physical cash') }}</p>
        </div>

        {{-- ATM Screen --}}
        <div style="padding: 24px;">

            {{-- Account Balances --}}
            <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Your Accounts') }}</div>
            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px;">
                @foreach($accounts as $acc)
                    @php
                        $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                        $sym = $cur?->symbol ?? $acc->currency;
                        $dec = $cur?->decimal_places ?? 2;
                        $isUsd = $acc->currency === 'USD';
                    @endphp
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--bg-secondary); border-radius: var(--radius-md); border-left: 3px solid {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
                        <div>
                            <div class="text-xs text-muted">{{ $isUsd ? '🇺🇸' : '🇮🇶' }} {{ $acc->currency }} {{ __('Account') }}</div>
                            <div class="text-xs text-muted" style="font-family: monospace;">{{ $acc->account_number }}</div>
                        </div>
                        <div class="font-bold" style="color: {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
                            {{ $sym }}{{ number_format($acc->available_balance, $dec) }}
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Withdrawal Form --}}
            <form method="POST" action="{{ route('cash.out') }}" id="atm-form" data-loading>
                @csrf

                {{-- Account Selection --}}
                <div class="form-group">
                    <label class="form-label">{{ __('Withdraw From') }}</label>
                    <select name="account_id" class="form-select" id="atm-account" required onchange="updateAtmCurrency()">
                        <option value="">{{ __('Select account...') }}</option>
                        @foreach($accounts as $acc)
                            @php
                                $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                                $sym = $cur?->symbol ?? $acc->currency;
                                $dec = $cur?->decimal_places ?? 2;
                            @endphp
                            <option value="{{ $acc->id }}"
                                    data-currency="{{ $acc->currency }}"
                                    data-symbol="{{ $sym }}"
                                    data-decimals="{{ $dec }}"
                                    data-balance="{{ $acc->available_balance }}">
                                {{ $acc->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ ucfirst($acc->account_type) }} — {{ $acc->currency }} ({{ $sym }}{{ number_format($acc->available_balance, $dec) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Amount --}}
                <div class="form-group">
                    <label class="form-label" id="amount-label">{{ __('Amount') }}</label>
                    <input type="number" name="amount" class="form-input" id="atm-amount"
                           min="10" max="5000" step="0.01"
                           placeholder="0.00" required
                           style="font-size: 32px; font-weight: 700; text-align: center;">
                    <div class="form-hint" id="amount-hint">{{ __('Select an account first') }}</div>
                    <div class="form-error" id="amount-error" style="display: none;"></div>
                    @error('amount')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Quick Amounts --}}
                <div class="form-group" id="quick-amounts" style="display: none;">
                    <div class="flex flex-gap-2 flex-wrap">
                        <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(20)">$20</button>
                        <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(50)">$50</button>
                        <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(100)">$100</button>
                        <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(500)">$500</button>
                        <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(1000)">$1,000</button>
                    </div>
                </div>

                {{-- PIN --}}
                <div class="form-group">
                    <label class="form-label">{{ __('Card PIN') }}</label>
                    <input type="password" name="pin" class="form-input" id="atm-pin"
                           placeholder="••••" maxlength="4" pattern="\d{4}" required
                           style="text-align: center; font-size: 24px; letter-spacing: 12px; font-weight: 700;">
                    <div class="form-hint">{{ __('Enter your 4-digit card PIN to authorize this withdrawal') }}</div>
                    @error('pin')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary btn-lg w-full" id="atm-submit" disabled>
                    🏧 {{ __('Withdraw Cash') }}
                </button>
            </form>

            {{-- Info --}}
            <div style="margin-top: 20px; padding: 12px; background: var(--bg-secondary); border-radius: var(--radius-md);">
                <div class="text-xs text-muted" style="display: flex; flex-direction: column; gap: 4px;">
                    <div>💡 {{ __('Withdrawal limits') }}: USD $10 — $5,000 · IQD 10,000 — 5,000,000</div>
                    <div>🔒 {{ __('PIN is verified against your active card. 3 failed attempts will freeze your card.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Back --}}
    <div class="mt-4 text-center">
        <a href="{{ route('dashboard') }}" class="text-sm text-muted">← {{ __('Back to Dashboard') }}</a>
    </div>
</div>

<script>
let currentBalance = 0;

function updateAtmCurrency() {
    const select = document.getElementById('atm-account');
    const option = select.options[select.selectedIndex];
    const label = document.getElementById('amount-label');
    const hint = document.getElementById('amount-hint');
    const input = document.getElementById('atm-amount');
    const quickAmounts = document.getElementById('quick-amounts');
    const error = document.getElementById('amount-error');

    if (option.value) {
        const currency = option.dataset.currency;
        const symbol = option.dataset.symbol;
        const decimals = parseInt(option.dataset.decimals);
        currentBalance = parseFloat(option.dataset.balance) || 0;

        label.textContent = '{{ __("Amount") }} (' + currency + ')';

        if (currency === 'IQD') {
            input.min = 10000;
            input.max = 5000000;
            input.step = '1';
            input.placeholder = '0';
            hint.textContent = '{{ __("Min") }}: 10,000 IQD — {{ __("Max") }}: 5,000,000 IQD · {{ __("Available") }}: ' + symbol + currentBalance.toLocaleString();

            quickAmounts.innerHTML = `
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(50000)">50K</button>
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(100000)">100K</button>
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(500000)">500K</button>
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(1000000)">1M</button>
            `;
        } else {
            input.min = 10;
            input.max = 5000;
            input.step = '0.01';
            input.placeholder = '0.00';
            hint.textContent = '{{ __("Min") }}: $10 — {{ __("Max") }}: $5,000 · {{ __("Available") }}: ' + symbol + currentBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

            quickAmounts.innerHTML = `
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(20)">$20</button>
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(50)">$50</button>
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(100)">$100</button>
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(500)">$500</button>
                <button type="button" class="btn btn-ghost btn-sm quick-btn" onclick="setAtmAmount(1000)">$1,000</button>
            `;
        }
        quickAmounts.style.display = 'flex';

        // Validate on input
        input.oninput = function() {
            const val = parseFloat(this.value);
            if (val > currentBalance) {
                error.textContent = '{{ __("Exceeds available balance!") }}';
                error.style.display = 'block';
                document.getElementById('atm-submit').disabled = true;
            } else {
                error.style.display = 'none';
                document.getElementById('atm-submit').disabled = false;
            }
        };

        document.getElementById('atm-submit').disabled = false;
    } else {
        label.textContent = '{{ __("Amount") }}';
        hint.textContent = '{{ __("Select an account first") }}';
        input.min = 10;
        input.max = 5000;
        input.step = '0.01';
        input.placeholder = '0.00';
        quickAmounts.style.display = 'none';
        input.oninput = null;
        error.style.display = 'none';
        document.getElementById('atm-submit').disabled = true;
    }
}

function setAtmAmount(amount) {
    const input = document.getElementById('atm-amount');
    const max = parseFloat(input.max);
    input.value = Math.min(amount, max);
    input.dispatchEvent(new Event('input'));
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateAtmCurrency();
});
</script>
@endsection
