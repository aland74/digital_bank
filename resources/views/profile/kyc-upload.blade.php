@extends('layouts.app')
@section('title', __('KYC Documents'))
@section('page-title', __('Identity Verification'))
@section('page-subtitle', __('Upload your identity documents to activate your account'))

@section('content')
<div style="max-width:700px;">
    {{-- Status Banner --}}
    @php
        $passportDoc = $documents->where('document_type', 'passport')->whereIn('status', ['pending', 'under_review', 'verified'])->first();
        $nationalIdDoc = $documents->where('document_type', 'national_id')->whereIn('status', ['pending', 'under_review', 'verified'])->first();
        $bothVerified = $passportDoc?->status === 'verified' && $nationalIdDoc?->status === 'verified';
    @endphp

    @if($bothVerified)
        <div class="alert alert-success animate-fade-in-up" style="margin-bottom:20px;">
            ✅ <strong>{{ __('Fully Verified!') }}</strong> {{ __('Both your Passport and National ID have been verified. Your account is active.') }}
        </div>
    @else
        <div class="alert alert-warning animate-fade-in-up" style="margin-bottom:20px;">
            ⚠️ <strong>{{ __('Verification Required') }}</strong> — {{ __('Please upload') }} {{ !$hasPassport ? __('your Passport') : '' }}{{ !$hasPassport && !$hasNationalId ? __(' and ') : '' }}{{ !$hasNationalId ? __('your National ID') : '' }} {{ __('to activate your account and cards.') }}
        </div>
    @endif

    {{-- Document Status Grid --}}
    <div class="grid-2 mb-6">
        {{-- Passport --}}
        <div class="card p-5 animate-fade-in-up">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="width:44px;height:44px;border-radius:12px;background:{{ $passportDoc ? ($passportDoc->status === 'verified' ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)') : 'rgba(107,114,128,0.15)' }};display:flex;align-items:center;justify-content:center;font-size:20px;">
                    🛂
                </div>
                <div>
                    <div class="font-semibold">{{ __('Passport') }}</div>
                    @if($passportDoc)
                        <span class="badge badge-{{ $passportDoc->status === 'verified' ? 'success' : ($passportDoc->status === 'rejected' ? 'danger' : 'warning') }}" style="font-size:10px;">
                            {{ __(ucfirst(str_replace('_', ' ', $passportDoc->status))) }}
                        </span>
                    @else
                        <span class="badge badge-neutral" style="font-size:10px;">{{ __('Not Uploaded') }}</span>
                    @endif
                </div>
            </div>
            @if($passportDoc)
                <div class="text-xs text-muted">
                    {{ __('Uploaded') }}: {{ $passportDoc->created_at->format('M d, Y') }}<br>
                    {{ __('File') }}: {{ $passportDoc->file_name }}
                </div>
                @if($passportDoc->status === 'rejected')
                    <div class="alert alert-error" style="margin-top:8px;font-size:11px;padding:6px 10px;">
                        {{ __('Reason') }}: {{ $passportDoc->rejection_reason }}
                    </div>
                @endif
            @endif
        </div>

        {{-- National ID --}}
        <div class="card p-5 animate-fade-in-up delay-100">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="width:44px;height:44px;border-radius:12px;background:{{ $nationalIdDoc ? ($nationalIdDoc->status === 'verified' ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)') : 'rgba(107,114,128,0.15)' }};display:flex;align-items:center;justify-content:center;font-size:20px;">
                    🪪
                </div>
                <div>
                    <div class="font-semibold">{{ __('National ID') }}</div>
                    @if($nationalIdDoc)
                        <span class="badge badge-{{ $nationalIdDoc->status === 'verified' ? 'success' : ($nationalIdDoc->status === 'rejected' ? 'danger' : 'warning') }}" style="font-size:10px;">
                            {{ __(ucfirst(str_replace('_', ' ', $nationalIdDoc->status))) }}
                        </span>
                    @else
                        <span class="badge badge-neutral" style="font-size:10px;">{{ __('Not Uploaded') }}</span>
                    @endif
                </div>
            </div>
            @if($nationalIdDoc)
                <div class="text-xs text-muted">
                    {{ __('Uploaded') }}: {{ $nationalIdDoc->created_at->format('M d, Y') }}<br>
                    {{ __('File') }}: {{ $nationalIdDoc->file_name }}
                </div>
                @if($nationalIdDoc->status === 'rejected')
                    <div class="alert alert-error" style="margin-top:8px;font-size:11px;padding:6px 10px;">
                        {{ __('Reason') }}: {{ $nationalIdDoc->rejection_reason }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    {{-- Upload Form --}}
    @if(!$bothVerified)
        <div class="card p-6 animate-fade-in-up delay-200">
            <h2 class="section-title mb-6">📤 {{ __('Upload Document') }}</h2>

            <form method="POST" action="{{ route('profile.kyc.upload') }}" enctype="multipart/form-data" data-loading>
                @csrf

                <div class="form-group">
                    <label class="form-label">{{ __('Document Type') }}</label>
                    <select name="document_type" class="form-select" required>
                        @if(!$hasPassport || ($passportDoc && $passportDoc->status === 'rejected'))
                            <option value="passport">🛂 {{ __('Passport') }}</option>
                        @endif
                        @if(!$hasNationalId || ($nationalIdDoc && $nationalIdDoc->status === 'rejected'))
                            <option value="national_id">🪪 {{ __('National ID Card') }}</option>
                        @endif
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Document Number') }}</label>
                    <input type="text" name="document_number" class="form-input" placeholder="{{ __('e.g. AB1234567') }}" required data-validate>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Expiry Date') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <input type="date" name="expiry_date" class="form-input" min="{{ date('Y-m-d') }}">
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Document File') }}</label>
                    <input type="file" name="document_file" class="form-input" accept=".jpg,.jpeg,.png,.pdf" required style="padding:12px;">
                    <div class="form-hint">{{ __('Accepted formats: JPG, PNG, PDF. Max size: 5MB.') }}</div>
                </div>

                <div class="alert alert-info" style="margin-bottom:16px;">
                    ℹ️ {{ __('Documents are typically reviewed within 24-48 hours. You\'ll receive a notification once verified.') }}
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <span class="btn-text">{{ __('Upload Document →') }}</span>
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
