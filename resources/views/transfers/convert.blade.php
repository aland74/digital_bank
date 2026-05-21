@extends('layouts.app')
@section('title', __('Convert Currency'))
@section('page-title', '🔄 ' . __('Convert Currency'))
@section('page-subtitle', __('Exchange between your USD and IQD accounts'))

@section('content')

{{-- Exchange Rate Info --}}
<div class="card p-4 mb-6 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(168, 85, 247, 0.1));">
    <div class="flex-between">
        <div>
            <div class="font-semibold">{{ __('Live Rate') }}</div>
            <div class="text-muted text-xs">{{ $exchangeRate['formatted'] }}</div>
        </div>
        <div class="text-right">
            <div class="text-sm" style="color:var(--info);">{{ $exchangeRate['inverse_formatted'] }}</div>
            <div class="text-xs text-muted">{{ __('Updated') }}: {{ $exchangeRate['updated_at'] }}</div>
        </div>
    </div>
</div>

<div class="grid-2 align-items-start">
    {{-- Your Accounts --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-4">🏦 {{ __('Your Accounts') }}</h2>

        <div class="card p-4 mb-3" style="border-left:3px solid #3b82f6;">
            <div class="flex-between">
                <div>
                    <div class="text-xs text-muted">🇺🇸 USD Account</div>
                    <div class="font-semibold text-lg">${{ number_format($usdAccount->balance, 2) }}</div>
                    <div class="text-xs text-muted">{{ __('Available') }}: ${{ number_format($usdAccount->available_balance, 2) }}</div>
                </div>
                <div class="text-2xl">💵</div>
            </div>
            <div class="text-xs text-muted mt-2">≈ د.ع {{ number_format($usdAccount->balance * ($exchangeRate['rate'] ?? 1310), 0) }}</div>
        </div>

        <div class="card p-4" style="border-left:3px solid #f59e0b;">
            <div class="flex-between">
                <div>
                    <div class="text-xs text-muted">🇮🇶 IQD Account</div>
                    <div class="font-semibold text-lg">د.ع {{ number_format($iqdAccount->balance, 0) }}</div>
                    <div class="text-xs text-muted">{{ __('Available') }}: د.ع {{ number_format($iqdAccount->available_balance, 0) }}</div>
                </div>
                <div class="text-2xl">💰</div>
            </div>
            <div class="text-xs text-muted mt-2">≈ ${{ number_format($iqdAccount->balance / ($exchangeRate['rate'] ?? 1310), 2) }}</div>
        </div>
    </div>

    {{-- Conversion Form --}}
    <div class="card p-6 animate-fade-in-up delay-100">
        <h2 class="section-title mb-4">🔄 {{ __('Convert') }}</h2>

        <form method="POST" action="{{ route('transfers.convert.execute') }}" id="convertForm" data-loading>
            @csrf

            <div class="form-group">
                <label class="form-label">{{ __('From') }}</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <label class="card p-3" style="cursor:pointer;text-align:center;transition:all 0.2s;{{ old('from_currency', 'USD') === 'USD' ? 'border:2px solid var(--primary);background:rgba(var(--primary-rgb),0.05);' : 'border:2px solid var(--border);' }}" id="from-usd">
                        <input type="radio" name="from_currency" value="USD" {{ old('from_currency', 'USD') === 'USD' ? 'checked' : '' }} style="display:none;">
                        <div class="font-semibold">🇺🇸 USD</div>
                        <div class="text-xs text-muted">${{ number_format($usdAccount->available_balance, 2) }}</div>
                    </label>
                    <label class="card p-3" style="cursor:pointer;text-align:center;transition:all 0.2s;{{ old('from_currency') === 'IQD' ? 'border:2px solid var(--primary);background:rgba(var(--primary-rgb),0.05);' : 'border:2px solid var(--border);' }}" id="from-iqd">
                        <input type="radio" name="from_currency" value="IQD" {{ old('from_currency') === 'IQD' ? 'checked' : '' }} style="display:none;">
                        <div class="font-semibold">🇮🇶 IQD</div>
                        <div class="text-xs text-muted">د.ع {{ number_format($iqdAccount->available_balance, 0) }}</div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" class="form-input" style="font-size:20px;font-weight:600;" step="0.01" min="0.01" value="{{ old('amount') }}" required placeholder="0.00" id="amountInput">
                <div class="form-hint" id="amountHint">{{ __('Enter amount in USD to convert to IQD') }}</div>
            </div>

            {{-- Preview --}}
            <div class="card p-4 mb-4" style="background:var(--bg-secondary);border:1px dashed var(--border);" id="preview">
                <div class="text-center">
                    <div class="text-xs text-muted mb-1">{{ __('You will receive') }}</div>
                    <div class="font-semibold" style="font-size:24px;color:var(--success);" id="previewAmount">—</div>
                    <div class="text-xs text-muted mt-1" id="previewRate"></div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full">
                <span class="btn-text">🔄 {{ __('Convert Now') }}</span>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rate = {{ $exchangeRate['rate'] ?? 1310 }};
    const fromUsd = document.getElementById('from-usd');
    const fromIqd = document.getElementById('from-iqd');
    const amountInput = document.getElementById('amountInput');
    const previewAmount = document.getElementById('previewAmount');
    const previewRate = document.getElementById('previewRate');
    const amountHint = document.getElementById('amountHint');

    function getFromCurrency() {
        return document.querySelector('input[name="from_currency"]:checked')?.value || 'USD';
    }

    function updatePreview() {
        const from = getFromCurrency();
        const amount = parseFloat(amountInput.value) || 0;

        if (from === 'USD') {
            const converted = Math.round(amount * rate);
            previewAmount.textContent = 'د.ع ' + converted.toLocaleString();
            previewRate.textContent = '1 USD = ' + rate.toLocaleString() + ' IQD';
            amountHint.textContent = 'Enter amount in USD to convert to IQD';
        } else {
            const converted = (amount / rate).toFixed(2);
            previewAmount.textContent = '$' + parseFloat(converted).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            previewRate.textContent = '1 IQD = $' + (1/rate).toFixed(6) + ' USD';
            amountHint.textContent = 'Enter amount in IQD to convert to USD';
        }
    }

    function updateSelection() {
        const from = getFromCurrency();
        fromUsd.style.border = from === 'USD' ? '2px solid var(--primary)' : '2px solid var(--border)';
        fromUsd.style.background = from === 'USD' ? 'rgba(var(--primary-rgb),0.05)' : '';
        fromIqd.style.border = from === 'IQD' ? '2px solid var(--primary)' : '2px solid var(--border)';
        fromIqd.style.background = from === 'IQD' ? 'rgba(var(--primary-rgb),0.05)' : '';
        updatePreview();
    }

    document.querySelectorAll('input[name="from_currency"]').forEach(r => r.addEventListener('change', updateSelection));
    amountInput.addEventListener('input', updatePreview);
    updateSelection();
});
</script>
@endsection
