@extends('layouts.app')
@section('title', __('Edit Beneficiary'))
@section('page-title', '✏️ ' . __('Edit Beneficiary'))
@section('page-subtitle', $beneficiary->name)

@section('content')
<div style="max-width: 640px;">
    {{-- Current Info --}}
    <div class="card p-4 mb-4 animate-fade-in-up" style="border-left: 3px solid {{ $beneficiary->type === 'internal' ? '#22c55e' : ($beneficiary->type === 'domestic' ? '#3b82f6' : '#8b5cf6') }};">
        <div class="flex align-items-center flex-gap-3">
            <div style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; font-size: 16px; background: {{ $beneficiary->type === 'internal' ? '#22c55e' : ($beneficiary->type === 'domestic' ? '#3b82f6' : '#8b5cf6') }};">
                {{ strtoupper(substr($beneficiary->name, 0, 2)) }}
            </div>
            <div>
                <div class="font-semibold">{{ $beneficiary->name }}</div>
                <div class="text-xs text-muted">{{ ucfirst($beneficiary->type) }} · {{ $beneficiary->masked_account }}</div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">✏️ {{ __('Edit Details') }}</h2>

        <form method="POST" action="{{ route('beneficiaries.update', $beneficiary) }}" data-loading>
            @csrf @method('PUT')

            {{-- Name & Nickname --}}
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">{{ __('Full Name') }}</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $beneficiary->name) }}" required>
                    @error('name')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Nickname') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <input type="text" name="nickname" class="form-input" value="{{ old('nickname', $beneficiary->nickname) }}" placeholder="{{ __('e.g. Mom, Work') }}">
                </div>
            </div>

            {{-- Type --}}
            <div class="form-group">
                <label class="form-label">{{ __('Type') }}</label>
                <select name="type" class="form-select" id="ben-type" required onchange="updateTypeFields()">
                    <option value="internal" {{ $beneficiary->type === 'internal' ? 'selected' : '' }}>🏦 {{ __('Internal (NexusBank)') }}</option>
                    <option value="domestic" {{ $beneficiary->type === 'domestic' ? 'selected' : '' }}>🏛️ {{ __('Domestic (Other Bank)') }}</option>
                    <option value="international" {{ $beneficiary->type === 'international' ? 'selected' : '' }}>🌍 {{ __('International') }}</option>
                </select>
            </div>

            {{-- Account Number --}}
            <div class="form-group">
                <label class="form-label" id="account-label">{{ __('Account Number') }}</label>
                <input type="text" name="account_number" class="form-input" id="account-input" value="{{ old('account_number', $beneficiary->account_number) }}" required style="font-family: monospace; letter-spacing: 1px;">
                @error('account_number')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Bank Name (external only) --}}
            <div class="form-group" id="bank-name-group" style="display: {{ $beneficiary->type === 'internal' ? 'none' : 'block' }};">
                <label class="form-label">{{ __('Bank Name') }}</label>
                <input type="text" name="bank_name" class="form-input" value="{{ old('bank_name', $beneficiary->bank_name) }}" placeholder="{{ __('e.g. Trade Bank of Iraq') }}">
            </div>

            {{-- SWIFT Code (international only) --}}
            <div class="form-group" id="swift-group" style="display: {{ $beneficiary->type === 'international' ? 'block' : 'none' }};">
                <label class="form-label">{{ __('SWIFT / BIC Code') }}</label>
                <input type="text" name="swift_code" class="form-input" value="{{ old('swift_code', $beneficiary->swift_code) }}" placeholder="e.g. BOFAUS3N" style="text-transform: uppercase;">
            </div>

            {{-- Currency --}}
            <div class="form-group">
                <label class="form-label">{{ __('Currency') }}</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <label style="display: flex; align-items: center; gap: 10px; padding: 14px; background: {{ $beneficiary->currency === 'USD' ? 'rgba(59,130,246,0.1)' : 'var(--bg-secondary)' }}; border: 2px solid {{ $beneficiary->currency === 'USD' ? '#3b82f6' : 'var(--border)' }}; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s;" id="currency-usd-label">
                        <input type="radio" name="currency" value="USD" {{ $beneficiary->currency === 'USD' ? 'checked' : '' }} style="display: none;">
                        <span style="font-size: 20px;">🇺🇸</span>
                        <div>
                            <div class="font-semibold text-sm">USD</div>
                            <div class="text-xs text-muted">US Dollar</div>
                        </div>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; padding: 14px; background: {{ $beneficiary->currency === 'IQD' ? 'rgba(245,158,11,0.1)' : 'var(--bg-secondary)' }}; border: 2px solid {{ $beneficiary->currency === 'IQD' ? '#f59e0b' : 'var(--border)' }}; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s;" id="currency-iqd-label">
                        <input type="radio" name="currency" value="IQD" {{ $beneficiary->currency === 'IQD' ? 'checked' : '' }} style="display: none;">
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
                <button type="submit" class="btn btn-primary btn-lg flex-1">💾 {{ __('Update Beneficiary') }}</button>
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

    if (type === 'internal') {
        bankGroup.style.display = 'none';
        swiftGroup.style.display = 'none';
        accountLabel.textContent = '{{ __("Account Number") }}';
    } else if (type === 'domestic') {
        bankGroup.style.display = 'block';
        swiftGroup.style.display = 'none';
        accountLabel.textContent = '{{ __("Account Number") }}';
    } else {
        bankGroup.style.display = 'block';
        swiftGroup.style.display = 'block';
        accountLabel.textContent = '{{ __("Account Number / IBAN") }}';
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
</script>
@endsection
