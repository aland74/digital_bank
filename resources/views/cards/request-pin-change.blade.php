@extends('layouts.app')
@section('title', __('Request PIN Change'))
@section('page-title', __('Request PIN Change'))
@section('page-subtitle', __('Submit a request to change PIN for card ending in') . ' ' . $card->card_number_last4)

@section('content')
<div style="max-width:500px;">
    <div class="card p-6 animate-fade-in-up">
        {{-- Card Preview --}}
        <div class="bank-card {{ $card->card_brand }}" style="margin:0 auto 24px;pointer-events:none;transform:scale(0.85);">
            <div class="card-glow"></div>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;z-index:1;">
                <div class="bank-card-chip"></div>
            </div>
            <div class="bank-card-number">{{ $card->masked_number }}</div>
            <div class="bank-card-footer">
                <div>
                    <div class="bank-card-name">{{ $card->cardholder_name }}</div>
                    <div class="bank-card-expiry">{{ $card->expiry_date }}</div>
                </div>
            </div>
            <div class="bank-card-brand">{{ $card->card_brand }}</div>
        </div>

        @if($pendingRequest)
            {{-- Show pending request status --}}
            <div class="alert alert-warning" style="margin-bottom:20px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <span style="font-size:20px;">⏳</span>
                    <strong>{{ __('Pending Request') }}</strong>
                </div>
                <p class="text-sm" style="margin:0;">
                    {{ __('You already have a pending PIN change request for this card.') }}<br>
                    <span class="text-muted">{{ __('Submitted') }}: {{ $pendingRequest->created_at->diffForHumans() }}</span><br>
                    <span class="text-muted">{{ __('Reason') }}: {{ __($pendingRequest->reason_label) }}</span>
                </p>
            </div>

            <div class="alert alert-info" style="margin-bottom:16px;">
                🔔 {{ __('An admin will review your request and send you a notification with your new PIN once approved.') }}
            </div>

            <a href="{{ route('cards.index') }}" class="btn btn-secondary btn-lg w-full">
                {{ __('← Back to Cards') }}
            </a>
        @else
            <h2 class="section-title mb-2">🔐 {{ __('Request PIN Change') }}</h2>
            <p class="text-muted text-sm mb-6">{{ __('For security, only an admin can change your card PIN. Submit a request below and you\'ll receive your new PIN via notification.') }}</p>

            <form method="POST" action="{{ route('cards.request-pin-change.store', $card) }}" data-loading>
                @csrf

                <div class="form-group">
                    <label class="form-label">{{ __('Reason') }}</label>
                    <select name="reason" class="form-select" required id="pin-reason-select">
                        <option value="forgotten">🔑 {{ __('I forgot my PIN') }}</option>
                        <option value="stolen">🚨 {{ __('I think my PIN has been stolen') }}</option>
                        <option value="compromised">⚠️ {{ __('Security concern') }}</option>
                        <option value="other">📝 {{ __('Other reason') }}</option>
                    </select>
                </div>

                <div id="stolen-warning" style="display:none;">
                    <div class="alert alert-error" style="margin-bottom:16px;">
                        🚨 <strong>{{ __('Important') }}:</strong> {{ __('Selecting this option will immediately freeze your card for your protection. It will be unfrozen after the admin approves your request.') }}
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Additional Details') }} <span class="text-muted">({{ __('optional') }})</span></label>
                    <textarea name="description" class="form-input" rows="3" maxlength="500" placeholder="{{ __('Provide any additional context to help the admin process your request faster...') }}" style="resize:vertical;">{{ old('description') }}</textarea>
                </div>

                <div class="alert alert-info" style="margin-bottom:16px;">
                    🔒 {{ __('An admin will review your request and securely send you a new PIN via notification. Please check your notifications regularly.') }}
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn btn-primary btn-lg" style="flex:1;">
                        <span class="btn-text">{{ __('Submit Request') }}</span>
                    </button>
                    <a href="{{ route('cards.index') }}" class="btn btn-secondary btn-lg">{{ __('Cancel') }}</a>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('pin-reason-select');
    const warning = document.getElementById('stolen-warning');
    if (select && warning) {
        select.addEventListener('change', function() {
            warning.style.display = this.value === 'stolen' ? 'block' : 'none';
        });
    }
});
</script>
@endsection
