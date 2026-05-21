@extends('layouts.app')
@section('title', __('Add Beneficiary'))
@section('page-title', __('Add Beneficiary'))

@section('content')
<div style="max-width:600px;">
    <div class="card p-6 animate-fade-in-up">
        <form method="POST" action="{{ route('beneficiaries.store') }}">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">{{ __('Full Name') }}</label>
                    <input type="text" name="name" class="form-input" placeholder="{{ __('Recipient name') }}" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Nickname') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <input type="text" name="nickname" class="form-input" placeholder="{{ __('e.g. Mom') }}" value="{{ old('nickname') }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('Type') }}</label>
                <select name="type" class="form-select" required>
                    <option value="internal">{{ __('Internal (NexusBank)') }}</option>
                    <option value="domestic">{{ __('Domestic (Other Bank)') }}</option>
                    <option value="international">{{ __('International') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('Account Number / IBAN') }}</label>
                <input type="text" name="account_number" class="form-input" placeholder="{{ __('Account number') }}" value="{{ old('account_number') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('Bank Name') }} <span class="text-muted">({{ __('for external') }})</span></label>
                <input type="text" name="bank_name" class="form-input" placeholder="{{ __('e.g. Chase, HSBC') }}" value="{{ old('bank_name') }}">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">{{ __('SWIFT Code') }}</label>
                    <input type="text" name="swift_code" class="form-input" placeholder="BOFAUS3N" value="{{ old('swift_code') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Currency') }}</label>
                    <select name="currency" class="form-select" required>
                        <option value="USD">USD — US Dollar</option>
                        <option value="IQD">IQD — Iraqi Dinar</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn btn-primary btn-lg">{{ __('Save Beneficiary') }}</button>
                <a href="{{ route('beneficiaries.index') }}" class="btn btn-secondary btn-lg">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
