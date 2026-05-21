@extends('layouts.app')
@section('title', __('Apply for Loan'))
@section('page-title', __('Apply for Loan'))
@section('page-subtitle', __('Get competitive rates for all your needs'))

@section('content')
<div style="max-width:600px;">
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">📋 {{ __('Loan Application') }}</h2>

        <form method="POST" action="{{ route('loans.store') }}" data-loading>
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('Loan Type') }}</label>
                <select name="loan_type" class="form-select" required>
                    <option value="personal">💰 Personal Loan — 8.50% APR</option>
                    <option value="home">🏠 Home Loan — 4.25% APR</option>
                    <option value="auto">🚗 Auto Loan — 5.75% APR</option>
                    <option value="business">💼 Business Loan — 7.00% APR</option>
                    <option value="education">🎓 Education Loan — 3.50% APR</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Loan Amount ($1,000 — $1,000,000)') }}</label>
                <input type="number" name="amount" class="form-input" placeholder="50000" min="1000" max="1000000" step="100" value="{{ old('amount') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Term (months)') }}</label>
                <select name="term_months" class="form-select" required>
                    @foreach([6,12,24,36,48,60,84,120,180,240,360] as $months)
                        <option value="{{ $months }}">{{ $months }} {{ __('months') }} ({{ round($months/12, 1) }} {{ __('years') }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Linked Card (Required)') }}</label>
                <select name="card_id" class="form-select" required>
                    @foreach($cards as $card)
                        <option value="{{ $card->id }}">
                            **** **** **** {{ $card->card_number_last4 }} — {{ ucfirst($card->card_brand) }} ({{ ucfirst($card->card_type) }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted" style="display:block; margin-top:4px;">{{ __('The loan will be disbursed to the account linked to this card, and monthly payments will be automatically deducted from it.') }}</small>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Purpose') }} <span class="text-muted">({{ __('optional') }})</span></label>
                <textarea name="purpose" class="form-input" rows="3" placeholder="{{ __('What will you use this loan for?') }}">{{ old('purpose') }}</textarea>
            </div>

            <div class="alert alert-info">
                ℹ️ {{ __('Loan applications are typically reviewed within 24-48 hours. You will receive a notification once a decision is made.') }}
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px;">
                {{ __('Submit Application →') }}
            </button>
        </form>
    </div>
</div>
@endsection
