@extends('layouts.app')
@section('title', __('Submit a Report'))
@section('page-title', __('Submit a Report'))
@section('page-subtitle', __('We\'re here to help'))

@section('content')
<div class="animate-fade-in-up" style="max-width: 720px;">
    <a href="{{ route('support.index') }}" class="btn btn-ghost btn-sm mb-4">← {{ __('Back to Tickets') }}</a>

    <div class="card p-6">
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="font-size: 48px; margin-bottom: 12px;">🎫</div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">{{ __('How can we help?') }}</h2>
            <p class="text-muted text-sm">{{ __('Describe your issue and our team will respond as soon as possible.') }}</p>
        </div>

        <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- Category --}}
            <div class="form-group">
                <label class="form-label" for="category">{{ __('Category') }}</label>
                <select name="category" id="category" class="form-input" required>
                    <option value="">{{ __('Select a category...') }}</option>
                    <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>🏦 {{ __('Account Issues') }} — {{ __('Balance, access, verification') }}</option>
                    <option value="transaction" {{ old('category') === 'transaction' ? 'selected' : '' }}>💸 {{ __('Transaction Issues') }} — {{ __('Failed, pending, or incorrect transfers') }}</option>
                    <option value="card" {{ old('category') === 'card' ? 'selected' : '' }}>💳 {{ __('Card Issues') }} — {{ __('PIN, activation, freezing') }}</option>
                    <option value="loan" {{ old('category') === 'loan' ? 'selected' : '' }}>📈 {{ __('Loan Issues') }} — {{ __('Application, payment, terms') }}</option>
                    <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>🔧 {{ __('Technical Issues') }} — {{ __('Bugs, errors, app problems') }}</option>
                    <option value="complaint" {{ old('category') === 'complaint' ? 'selected' : '' }}>📢 {{ __('Complaint') }} — {{ __('Service quality, feedback') }}</option>
                    <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>📋 {{ __('General Inquiry') }} — {{ __('Other questions or requests') }}</option>
                </select>
                @error('category')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Subject --}}
            <div class="form-group">
                <label class="form-label" for="subject">{{ __('Subject') }}</label>
                <input type="text" name="subject" id="subject" class="form-input" value="{{ old('subject') }}" placeholder="{{ __('Brief summary of your issue') }}" required maxlength="255">
                @error('subject')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Message --}}
            <div class="form-group">
                <label class="form-label" for="message">{{ __('Message') }}</label>
                <textarea name="message" id="message" class="form-textarea" rows="6" placeholder="{{ __('Describe your issue in detail. Include any relevant information like dates, amounts, or error messages...') }}" required maxlength="5000">{{ old('message') }}</textarea>
                @error('message')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Priority --}}
            <div class="form-group">
                <label class="form-label">{{ __('Priority') }}</label>
                <div class="support-priority-grid">
                    <label class="support-priority-card {{ old('priority', 'low') === 'low' ? 'selected' : '' }}">
                        <input type="radio" name="priority" value="low" {{ old('priority', 'low') === 'low' ? 'checked' : '' }} class="support-priority-radio">
                        <div class="support-priority-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">🟢</div>
                        <div class="support-priority-label">{{ __('Low') }}</div>
                        <div class="support-priority-desc">{{ __('General question') }}</div>
                    </label>
                    <label class="support-priority-card {{ old('priority') === 'medium' ? 'selected' : '' }}">
                        <input type="radio" name="priority" value="medium" {{ old('priority') === 'medium' ? 'checked' : '' }} class="support-priority-radio">
                        <div class="support-priority-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">🟡</div>
                        <div class="support-priority-label">{{ __('Medium') }}</div>
                        <div class="support-priority-desc">{{ __('Needs attention') }}</div>
                    </label>
                    <label class="support-priority-card {{ old('priority') === 'high' ? 'selected' : '' }}">
                        <input type="radio" name="priority" value="high" {{ old('priority') === 'high' ? 'checked' : '' }} class="support-priority-radio">
                        <div class="support-priority-icon" style="background: rgba(249, 115, 22, 0.1); color: #f97316;">🟠</div>
                        <div class="support-priority-label">{{ __('High') }}</div>
                        <div class="support-priority-desc">{{ __('Affecting service') }}</div>
                    </label>
                    <label class="support-priority-card {{ old('priority') === 'urgent' ? 'selected' : '' }}">
                        <input type="radio" name="priority" value="urgent" {{ old('priority') === 'urgent' ? 'checked' : '' }} class="support-priority-radio">
                        <div class="support-priority-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">🔴</div>
                        <div class="support-priority-label">{{ __('Urgent') }}</div>
                        <div class="support-priority-desc">{{ __('Critical issue') }}</div>
                    </label>
                </div>
                @error('priority')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Attachment --}}
            <div class="form-group">
                <label class="form-label" for="attachment">{{ __('Attachment') }} <span class="text-muted text-xs">({{ __('Optional') }})</span></label>
                <input type="file" name="attachment" id="attachment" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                <p class="text-muted text-xs" style="margin-top: 4px;">{{ __('Max 5MB. Allowed: JPG, PNG, GIF, PDF, DOC, TXT') }}</p>
                @error('attachment')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full" style="margin-top: 8px;">📩 {{ __('Submit Ticket') }}</button>
        </form>
    </div>
</div>
@endsection
