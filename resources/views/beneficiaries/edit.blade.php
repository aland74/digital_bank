@extends('layouts.app')
@section('title', 'Edit Beneficiary')
@section('page-title', 'Edit Beneficiary')

@section('content')
<div style="max-width:600px;">
    <div class="card p-6 animate-fade-in-up">
        <form method="POST" action="{{ route('beneficiaries.update', $beneficiary) }}">
            @csrf @method('PUT')
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $beneficiary->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nickname</label>
                    <input type="text" name="nickname" class="form-input" value="{{ old('nickname', $beneficiary->nickname) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                    <option value="internal" {{ $beneficiary->type === 'internal' ? 'selected' : '' }}>Internal</option>
                    <option value="domestic" {{ $beneficiary->type === 'domestic' ? 'selected' : '' }}>Domestic</option>
                    <option value="international" {{ $beneficiary->type === 'international' ? 'selected' : '' }}>International</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Account Number</label>
                <input type="text" name="account_number" class="form-input" value="{{ old('account_number', $beneficiary->account_number) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-input" value="{{ old('bank_name', $beneficiary->bank_name) }}">
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn btn-primary btn-lg">Update</button>
                <a href="{{ route('beneficiaries.index') }}" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
