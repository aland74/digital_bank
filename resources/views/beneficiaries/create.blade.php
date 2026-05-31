@extends('layouts.app')
@section('title', __('Add Beneficiary'))
@section('page-title', '➕ ' . __('Add Beneficiary'))
@section('page-subtitle', __('Save a recipient for quick transfers'))

@section('content')
<div style="max-width: 640px;">
    {{-- Info --}}
    <div class="card p-4 mb-4 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));">
        <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Beneficiary Types') }}</div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🏦</div>
                <div class="text-xs font-semibold">{{ __('Internal') }}</div>
                <div class="text-xs text-muted">{{ __('Distributed Bank') }}</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🏛️</div>
                <div class="text-xs font-semibold">{{ __('Domestic') }}</div>
                <div class="text-xs text-muted">{{ __('Other banks') }}</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🌍</div>
                <div class="text-xs font-semibold">{{ __('International') }}</div>
                <div class="text-xs text-muted">{{ __('SWIFT/IBAN') }}</div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">👤 {{ __('Beneficiary Details') }}</h2>

        <form method="POST" action="{{ route('beneficiaries.store') }}" data-loading>
            @csrf

            {{-- Name & Nickname --}}
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">{{ __('Full Name') }}</label>
                    <input type="text" name="name" class="form-input" placeholder="{{ __('Recipient name') }}" value="{{ old('name') }}" required>
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Nickname') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <input type="text" name="nickname" class="form-input" placeholder="{{ __('e.g. Mom, Work') }}" value="{{ old('nickname') }}">
                </div>
            </div>

            {{-- Type --}}
            <div class="form-group">
                <label class="form-label">{{ __('Type') }}</label>
                <select name="type" class="form-select" id="ben-type" required onchange="updateTypeFields()">
                    <option value="internal" {{ old('type') === 'internal' ? 'selected' : '' }}>🏦 {{ __('Internal (Distributed Bank)') }}</option>
                    <option value="domestic" {{ old('type') === 'domestic' ? 'selected' : '' }}>🏛️ {{ __('Domestic (Other Bank)') }}</option>
                    <option value="international" {{ old('type') === 'international' ? 'selected' : '' }}>🌍 {{ __('International') }}</option>
                </select>
            </div>

            {{-- Account Number --}}
            <div class="form-group">
                <label class="form-label" id="account-label">{{ __('Account Number') }}</label>
                <input type="text" name="account_number" class="form-input" id="account-input" placeholder="{{ __('Account number') }}" value="{{ old('account_number') }}" required style="font-family: monospace; letter-spacing: 1px;">
                @error('account_number')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Bank Name (external only) --}}
            <div class="form-group" id="bank-name-group" style="display: {{ old('type') === 'internal' ? 'none' : 'block' }};">
                <label class="form-label">{{ __('Bank Name') }}</label>
                <input type="text" name="bank_name" class="form-input" placeholder="{{ __('e.g. Trade Bank of Iraq, Cihan Bank') }}" value="{{ old('bank_name') }}">
            </div>

            {{-- SWIFT Code (international only) --}}
            <div class="form-group" id="swift-group" style="display: {{ old('type', 'internal') === 'international' ? 'block' : 'none' }};">
                <label class="form-label">{{ __('SWIFT / BIC Code') }}</label>
                <input type="text" name="swift_code" class="form-input" placeholder="e.g. BOFAUS3N" value="{{ old('swift_code') }}" style="text-transform: uppercase;">
            </div>

            {{-- Currency --}}
            <div class="form-group">
                <label class="form-label">{{ __('Currency') }}</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display: flex; align-items: center; gap: 10px; padding: 14px; background: {{ old('currency', 'USD') === 'USD' ? 'rgba(59,130,246,0.1)' : 'var(--bg-secondary)' }}; border: 2px solid {{ old('currency', 'USD') === 'USD' ? '#3b82f6' : 'var(--border)' }}; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s;" id="currency-usd-label">
                        <input type="radio" name="currency" value="USD" {{ old('currency', 'USD') === 'USD' ? 'checked' : '' }} style="display: none;" id="currency-usd">
                        <span style="font-size: 20px;">🇺🇸</span>
                        <div>
                            <div class="font-semibold text-sm">USD</div>
                            <div class="text-xs text-muted">US Dollar</div>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; padding: 14px; background: {{ old('currency') === 'IQD' ? 'rgba(245,158,11,0.1)' : 'var(--bg-secondary)' }}; border: 2px solid {{ old('currency') === 'IQD' ? '#f59e0b' : 'var(--border)' }}; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s;" id="currency-iqd-label">
                        <input type="radio" name="currency" value="IQD" {{ old('currency') === 'IQD' ? 'checked' : '' }} style="display: none;" id="currency-iqd">
                        <span style="font-size: 20px;">🇮🇶</span>
                        <div>
                            <div class="font-semibold text-sm">IQD</div>
                            <div class="text-xs text-muted">Iraqi Dinar</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Submit --}}
            <div style="display: flex; gap: 12px; margin-top: 8px;">
                <button type="submit" class="btn btn-primary btn-lg flex-1">💾 {{ __('Save Beneficiary') }}</button>
                <a href="{{ route('beneficiaries.index') }}" class="btn btn-secondary btn-lg">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateTypeFields() {
    const type = document.getElementById('ben-type').value;
    const bankGroup = document.getElementById('bank-name-group');
    const swiftGroup = document.getElementById('swift-group');
    const accountLabel = document.getElementById('account-label');
    const accountInput = document.getElementById('account-input');

    if (type === 'internal') {
        bankGroup.style.display = 'none';
        swiftGroup.style.display = 'none';
        accountLabel.textContent = '{{ __("Account Number") }}';
        accountInput.placeholder = 'e.g. NXB1234567890';
    } else if (type === 'domestic') {
        bankGroup.style.display = 'block';
        swiftGroup.style.display = 'none';
        accountLabel.textContent = '{{ __("Account Number") }}';
        accountInput.placeholder = '{{ __("Account number at the other bank") }}';
    } else {
        bankGroup.style.display = 'block';
        swiftGroup.style.display = 'block';
        accountLabel.textContent = '{{ __("Account Number / IBAN") }}';
        accountInput.placeholder = 'e.g. IQ98NBIQ8501234567890123';
    }
}

// Currency radio styling
document.querySelectorAll('input[name="currency"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('currency-usd-label').style.background = this.value === 'USD' ? 'rgba(59,130,246,0.1)' : 'var(--bg-secondary)';
        document.getElementById('currency-usd-label').style.borderColor = this.value === 'USD' ? '#3b82f6' : 'var(--border)';
        document.getElementById('currency-iqd-label').style.background = this.value === 'IQD' ? 'rgba(245,158,11,0.1)' : 'var(--bg-secondary)';
        document.getElementById('currency-iqd-label').style.borderColor = this.value === 'IQD' ? '#f59e0b' : 'var(--border)';
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateTypeFields);
</script>
@endsection
