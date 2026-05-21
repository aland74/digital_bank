@extends('layouts.app')
@section('title', __('Submit a Ticket'))
@section('page-title', '🎫 ' . __('Submit a Ticket'))
@section('page-subtitle', __('We\'re here to help'))

@section('content')
<div style="max-width: 700px;">
    <a href="{{ route('support.index') }}" class="btn btn-ghost btn-sm mb-4">← {{ __('Back to Tickets') }}</a>

    {{-- Quick Help --}}
    <div class="card p-4 mb-4 animate-fade-in-up" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));">
        <div class="text-xs text-muted mb-3" style="text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Common Topics') }}</div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px;">
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🏦</div>
                <div class="text-xs font-semibold">{{ __('Account') }}</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">💸</div>
                <div class="text-xs font-semibold">{{ __('Transaction') }}</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">💳</div>
                <div class="text-xs font-semibold">{{ __('Card') }}</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">📈</div>
                <div class="text-xs font-semibold">{{ __('Loan') }}</div>
            </div>
            <div style="padding: 8px; background: var(--bg-primary); border-radius: var(--radius-sm); text-align: center;">
                <div style="font-size: 20px;">🔧</div>
                <div class="text-xs font-semibold">{{ __('Technical') }}</div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="card p-6 animate-fade-in-up">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="font-size: 48px; margin-bottom: 12px;">🎫</div>
            <h2 style="font-size: 22px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">{{ __('How can we help?') }}</h2>
            <p class="text-muted text-sm">{{ __('Describe your issue and our team will respond as soon as possible.') }}</p>
        </div>

        <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" data-loading>
            @csrf

            {{-- Category --}}
            <div class="form-group">
                <label class="form-label">{{ __('Category') }}</label>
                <select name="category" class="form-select" required>
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
                <label class="form-label">{{ __('Subject') }}</label>
                <input type="text" name="subject" class="form-input" value="{{ old('subject') }}" placeholder="{{ __('Brief summary of your issue') }}" required maxlength="255">
                @error('subject')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Message --}}
            <div class="form-group">
                <label class="form-label">{{ __('Message') }}</label>
                <textarea name="message" class="form-textarea" rows="6" placeholder="{{ __('Describe your issue in detail. Include any relevant information like dates, amounts, or error messages...') }}" required maxlength="5000">{{ old('message') }}</textarea>
                @error('message')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Priority --}}
            <div class="form-group">
                <label class="form-label">{{ __('Priority') }}</label>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                    @php
                        $priorities = [
                            'low' => ['icon' => '🟢', 'color' => '#22c55e', 'label' => 'Low', 'desc' => 'General question'],
                            'medium' => ['icon' => '🟡', 'color' => '#f59e0b', 'label' => 'Medium', 'desc' => 'Needs attention'],
                            'high' => ['icon' => '🟠', 'color' => '#f97316', 'label' => 'High', 'desc' => 'Affecting service'],
                            'urgent' => ['icon' => '🔴', 'color' => '#ef4444', 'label' => 'Urgent', 'desc' => 'Critical issue'],
                        ];
                    @endphp
                    @foreach($priorities as $key => $p)
                        <label style="display: flex; flex-direction: column; align-items: center; padding: 12px 8px; background: {{ old('priority', 'low') === $key ? $p['color'] . '15' : 'var(--bg-secondary)' }}; border: 2px solid {{ old('priority', 'low') === $key ? $p['color'] : 'var(--border)' }}; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="priority" value="{{ $key }}" {{ old('priority', 'low') === $key ? 'checked' : '' }} style="display: none;">
                            <div style="font-size: 20px; margin-bottom: 4px;">{{ $p['icon'] }}</div>
                            <div class="text-xs font-semibold" style="color: {{ $p['color'] }};">{{ __($p['label']) }}</div>
                            <div class="text-xs text-muted">{{ __($p['desc']) }}</div>
                        </label>
                    @endforeach
                </div>
                @error('priority')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Attachment --}}
            <div class="form-group">
                <label class="form-label">{{ __('Attachment') }} <span class="text-muted text-xs">({{ __('Optional') }})</span></label>
                <input type="file" name="attachment" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                <div class="form-hint">{{ __('Max 5MB. Allowed: JPG, PNG, GIF, PDF, DOC, TXT') }}</div>
                @error('attachment')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn btn-primary btn-lg w-full">📩 {{ __('Submit Ticket') }}</button>
        </form>
    </div>

    {{-- Back --}}
    <div class="mt-4 text-center">
        <a href="{{ route('support.index') }}" class="text-sm text-muted">← {{ __('Back to Support Center') }}</a>
    </div>
</div>

<script>
// Priority card selection
document.querySelectorAll('input[name="priority"]').forEach(radio => {
    radio.closest('label').addEventListener('click', function() {
        document.querySelectorAll('input[name="priority"]').forEach(r => {
            const label = r.closest('label');
            const color = label.querySelector('.font-semibold')?.style.color || '#6b7280';
            label.style.background = 'var(--bg-secondary)';
            label.style.borderColor = 'var(--border)';
        });
        this.style.background = this.querySelector('.font-semibold')?.style.color + '15' || 'rgba(59,130,246,0.1)';
        this.style.borderColor = this.querySelector('.font-semibold')?.style.color || '#3b82f6';
    });
});
</script>
@endsection
