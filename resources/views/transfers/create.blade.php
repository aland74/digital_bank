@extends('layouts.app')
@section('title', __('Transfer Money'))
@section('page-title', '💸 ' . __('Transfer Money'))
@section('page-subtitle', __('Send funds to any Distributed Bank account'))

@section('content')
@php
    $pendingIncoming = \App\Models\PendingTransfer::where('receiver_user_id', auth()->id())->pending()->count();
    $pendingOutgoing = \App\Models\PendingTransfer::where('sender_user_id', auth()->id())->pending()->count();
    $pendingTotal = $pendingIncoming + $pendingOutgoing;
@endphp

{{-- Tabs --}}
<div class="filter-tabs mb-6 animate-fade-in-up">
    <a href="{{ route('transfers.create') }}" class="filter-tab active">💸 {{ __('New Transfer') }}</a>
    <a href="{{ route('transfers.pending') }}" class="filter-tab">
        ⏳ {{ __('Pending') }}
        @if($pendingTotal > 0)
            <span class="badge badge-info" style="margin-left: 6px;">{{ $pendingTotal }}</span>
        @endif
    </a>
</div>

<div style="max-width: 640px;">
    {{-- Step Indicator --}}
    <div class="step-indicator mb-6 animate-fade-in-up">
        <div class="step">
            <div class="step-circle active">1<span class="step-label">{{ __('Details') }}</span></div>
        </div>
        <div class="step-connector"></div>
        <div class="step">
            <div class="step-circle pending">2<span class="step-label">{{ __('Review') }}</span></div>
        </div>
        <div class="step-connector"></div>
        <div class="step">
            <div class="step-circle pending">3<span class="step-label">{{ __('Done') }}</span></div>
        </div>
    </div>

    {{-- Pending Incoming Alert --}}
    @if($pendingIncoming > 0)
        <div class="alert alert-info mb-4 animate-fade-in-up">
            📨 {{ __('You have') }} <strong>{{ $pendingIncoming }}</strong> {{ __('incoming transfer(s) waiting for your response.') }}
            <a href="{{ route('transfers.pending') }}" style="text-decoration: underline;">{{ __('View') }}</a>
        </div>
    @endif

    {{-- Accounts Overview --}}
    <div class="card p-4 mb-4 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(245, 158, 11, 0.05));">
        <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Your Accounts') }}</div>
        <div class="flex flex-gap-3">
            @foreach($accounts as $acc)
                @php
                    $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                    $sym = $cur?->symbol ?? $acc->currency;
                    $dec = $cur?->decimal_places ?? 2;
                @endphp
                <div style="flex: 1; padding: 12px; background: var(--bg-primary); border-radius: var(--radius-md); border-left: 3px solid {{ $acc->currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">
                    <div class="text-xs text-muted">{{ $acc->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ $acc->currency }}</div>
                    <div class="font-bold" style="font-size: 18px; color: {{ $acc->currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">{{ $sym }}{{ number_format($acc->balance, $dec) }}</div>
                    <div class="text-xs text-muted">{{ __('Available') }}: {{ $sym }}{{ number_format($acc->available_balance, $dec) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Transfer Form --}}
    <div class="card p-6 animate-fade-in-up">
        <form method="POST" action="{{ route('transfers.confirm') }}" id="transfer-form" data-loading>
            @csrf

            {{-- From Account --}}
            <div class="form-group">
                <label class="form-label">{{ __('From Account') }}</label>
                <select name="from_account_id" class="form-select" id="from-account" required onchange="updateFromAccount()">
                    <option value="">{{ __('Select account...') }}</option>
                    @foreach($accounts as $acc)
                        @php
                            $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                            $sym = $cur?->symbol ?? $acc->currency;
                            $dec = $cur?->decimal_places ?? 2;
                        @endphp
                        <option value="{{ $acc->id }}" data-currency="{{ $acc->currency }}" data-symbol="{{ $sym }}" data-decimals="{{ $dec }}" data-balance="{{ $acc->available_balance }}" {{ old('from_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ ucfirst($acc->account_type) }} — {{ $acc->account_number }} ({{ $sym }}{{ number_format($acc->available_balance, $dec) }})
                        </option>
                    @endforeach
                </select>
                <div class="form-hint" id="from-account-hint"></div>
            </div>

            {{-- To Account --}}
            <div class="form-group">
                <label class="form-label">{{ __('To Account Number') }}</label>
                <div class="flex flex-gap-2">
                    <input type="text" name="to_account_number" class="form-input" id="to-account" placeholder="{{ __('e.g. NXB1234567890') }}" value="{{ old('to_account_number') }}" required style="flex: 1; font-family: monospace; letter-spacing: 1px;">
                    <button type="button" class="btn btn-ghost" onclick="pasteFromClipboard()" title="{{ __('Paste from clipboard') }}">📋</button>
                </div>
                <div class="form-hint">{{ __('Enter the recipient\'s full account number') }}</div>
                @error('to_account_number')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Beneficiaries --}}
            @if($beneficiaries->count() > 0)
                <div class="form-group">
                    <label class="form-label">{{ __('Or Select Beneficiary') }}</label>
                    <select class="form-select" onchange="selectBeneficiary(this.value)">
                        <option value="">{{ __('Choose a saved beneficiary...') }}</option>
                        @foreach($beneficiaries as $b)
                            <option value="{{ $b->account_number }}" {{ old('to_account_number') == $b->account_number ? 'selected' : '' }}>
                                {{ $b->is_favorite ? '⭐ ' : '' }}{{ $b->nickname ?? $b->name }} — {{ $b->account_number }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Amount --}}
            <div class="form-group">
                <label class="form-label" id="amount-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" class="form-input" id="amount-input" min="0.01" step="0.01" placeholder="0.00" value="{{ old('amount') }}" required style="font-size: 28px; font-weight: 700; text-align: center;">
                <div class="form-hint" id="amount-hint">{{ __('Select an account first') }}</div>
                @error('amount')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Quick Amount Buttons --}}
            <div class="form-group" id="quick-amounts" style="display: none;">
                <div class="flex flex-gap-2 flex-wrap">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(50)">$50</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(100)">$100</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(500)">$500</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(1000)">$1,000</button>
                </div>
            </div>

            {{-- Description --}}
            <div class="form-group">
                <label class="form-label">{{ __('Description (optional)') }}</label>
                <input type="text" name="description" class="form-input" placeholder="{{ __('e.g. Payment for services') }}" value="{{ old('description') }}" maxlength="255">
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn btn-primary btn-lg w-full" id="submit-btn">
                {{ __('Continue to Review →') }}
            </button>
        </form>
    </div>
</div>

<script>
function updateFromAccount() {
    const select = document.getElementById('from-account');
    const option = select.options[select.selectedIndex];
    const label = document.getElementById('amount-label');
    const hint = document.getElementById('amount-hint');
    const quickAmounts = document.getElementById('quick-amounts');
    const input = document.getElementById('amount-input');

    if (option.value) {
        const currency = option.dataset.currency;
        const symbol = option.dataset.symbol;
        const decimals = parseInt(option.dataset.decimals);
        const balance = parseFloat(option.dataset.balance) || 0;

        label.textContent = '{{ __("Amount") }} (' + currency + ')';
        hint.textContent = '{{ __("Available") }}: ' + symbol + balance.toLocaleString(undefined, {minimumFractionDigits: decimals, maximumFractionDigits: decimals}) + ' · {{ __("Minimum") }}: ' + symbol + (decimals === 0 ? '1' : '0.01');

        input.step = decimals === 0 ? '1' : '0.01';
        input.min = decimals === 0 ? '1' : '0.01';
        input.max = balance;
        input.placeholder = decimals === 0 ? '0' : '0.00';

        // Update quick amount buttons
        if (currency === 'IQD') {
            quickAmounts.innerHTML = `
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(100000)">100K</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(500000)">500K</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(1000000)">1M</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(5000000)">5M</button>
            `;
        } else {
            quickAmounts.innerHTML = `
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(50)">$50</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(100)">$100</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(500)">$500</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAmount(1000)">$1,000</button>
            `;
        }
        quickAmounts.style.display = 'block';

        // Validate amount on input
        input.oninput = function() {
            const val = parseFloat(this.value);
            if (val > balance) {
                hint.innerHTML = '<span style="color: var(--danger);">{{ __("Exceeds available balance!") }}</span>';
                document.getElementById('submit-btn').disabled = true;
            } else {
                hint.textContent = '{{ __("Available") }}: ' + symbol + balance.toLocaleString(undefined, {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
                document.getElementById('submit-btn').disabled = false;
            }
        };
    } else {
        label.textContent = '{{ __("Amount") }}';
        hint.textContent = '{{ __("Select an account first") }}';
        quickAmounts.style.display = 'none';
        input.step = '0.01';
        input.min = '0.01';
        input.max = '';
        input.oninput = null;
    }
}

function setAmount(amount) {
    document.getElementById('amount-input').value = amount;
    document.getElementById('amount-input').dispatchEvent(new Event('input'));
}

function selectBeneficiary(accountNumber) {
    if (accountNumber) {
        document.getElementById('to-account').value = accountNumber;
    }
}

async function pasteFromClipboard() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('to-account').value = text.trim();
    } catch (e) {
        // Clipboard API not available
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFromAccount();
});
</script>
@endsection
