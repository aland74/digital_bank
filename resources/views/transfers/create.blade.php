@extends('layouts.app')
@section('title', __('Transfer Money'))
@section('page-title', __('Transfer Money'))
@section('page-subtitle', __('Send funds to any NexusBank account'))

@section('content')
<div style="max-width:600px;">
    {{-- Tab Navigation --}}
    @php
        $pendingIncoming = \App\Models\PendingTransfer::where('receiver_user_id', auth()->id())->pending()->count();
        $pendingOutgoing = \App\Models\PendingTransfer::where('sender_user_id', auth()->id())->pending()->count();
        $pendingTotal = $pendingIncoming + $pendingOutgoing;
    @endphp
    <div class="filter-tabs animate-fade-in-up" style="margin-bottom: 20px;">
        <a href="{{ route('transfers.create') }}" class="filter-tab active">
            💸 {{ __('New Transfer') }}
        </a>
        <a href="{{ route('transfers.pending') }}" class="filter-tab">
            ⏳ {{ __('Pending Transfers') }}
            @if($pendingTotal > 0)
                <span class="badge badge-info" style="margin-left: 6px; font-size: 10px;">{{ $pendingTotal }}</span>
            @endif
        </a>
    </div>

    {{-- Step Indicator --}}
    <div class="step-indicator animate-fade-in-up">
        <div class="step">
            <div class="step-circle active">1<span class="step-label">{{ __('Details') }}</span></div>
        </div>
        <div class="step-connector"></div>
        <div class="step">
            <div class="step-circle pending">2<span class="step-label">{{ __('Review') }}</span></div>
        </div>
        <div class="step-connector"></div>
        <div class="step">
            <div class="step-circle pending">3<span class="step-label">{{ __('Sent') }}</span></div>
        </div>
    </div>

    {{-- Pending Incoming Alert --}}
    @if($pendingIncoming > 0)
        <div class="alert alert-info animate-fade-in-up" style="margin-top:16px;">
            📨 {{ __('You have') }} <strong>{{ $pendingIncoming }}</strong> {{ __('pending incoming transfer(s).') }}
            <a href="{{ route('transfers.pending') }}" style="color:var(--info);text-decoration:underline;margin-left:4px;">{{ __('View & Accept →') }}</a>
        </div>
    @endif

    <div class="card p-6 animate-fade-in-up" style="margin-top:16px;">
        <h2 class="section-title mb-6">💸 {{ __('New Transfer') }}</h2>

        <div class="alert alert-info" style="margin-bottom:20px;font-size:13px;">
            ℹ️ {{ __('Transfers require the recipient to accept before funds are moved. The recipient has') }} {{ \App\Models\BankSetting::transferExpiryHours() }} {{ __('hours to accept or the transfer will auto-cancel.') }}
        </div>

        <form method="POST" action="{{ route('transfers.confirm') }}" data-loading>
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('Send As') }}</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;" id="currency-selector">
                    @foreach($accounts as $account)
                        <label class="card p-3" style="cursor:pointer;text-align:center;transition:all 0.2s;{{ $loop->first ? 'border:2px solid var(--primary);background:rgba(var(--primary-rgb),0.05);' : 'border:2px solid var(--border);' }}" id="cur-{{ $account->id }}">
                            <input type="radio" name="from_account_id" value="{{ $account->id }}" {{ $loop->first ? 'checked' : '' }} style="display:none;">
                            <div class="font-semibold">{{ $account->currency === 'USD' ? '🇺🇸' : '🇮🇶' }} {{ $account->currency }}</div>
                            <div class="text-xs text-muted">{{ $account->formatted_balance }}</div>
                        </label>
                    @endforeach
                </div>
                <div class="form-hint">{{ __('Choose which currency to send from. Cross-currency transfers use live exchange rates.') }}</div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Recipient Account Number') }}</label>
                <input type="text" name="to_account_number" class="form-input" placeholder="NXB0000000000" value="{{ old('to_account_number') }}" required data-validate>
                @if($beneficiaries->count() > 0)
                    <div style="margin-top:12px;">
                        <span class="text-muted text-xs" style="display:block;margin-bottom:8px;">{{ __('Quick select from beneficiaries') }}:</span>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach($beneficiaries->take(5) as $ben)
                                <button type="button" class="btn btn-ghost btn-sm"
                                    onclick="document.querySelector('[name=to_account_number]').value='{{ $ben->account_number }}'"
                                    style="display:flex;align-items:center;gap:6px;">
                                    <span style="width:22px;height:22px;border-radius:50%;background:var(--gradient-primary);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:white;">{{ strtoupper(substr($ben->display_name, 0, 1)) }}</span>
                                    {{ $ben->display_name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" class="form-input" placeholder="0.00" step="0.01" min="0.01" value="{{ old('amount') }}" required style="font-size:24px;font-weight:700;text-align:center;padding:20px;">
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Description') }} <span class="text-muted">({{ __('optional') }})</span></label>
                <input type="text" name="description" class="form-input" placeholder="{{ __('What is this transfer for?') }}" value="{{ old('description') }}">
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px;">
                <span class="btn-text">{{ __('Continue to Review →') }}</span>
            </button>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('input[name="from_account_id"]');
    radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('#currency-selector label').forEach(function(label) {
                label.style.border = '2px solid var(--border)';
                label.style.background = '';
            });
            const selected = document.getElementById('cur-' + this.value);
            if (selected) {
                selected.style.border = '2px solid var(--primary)';
                selected.style.background = 'rgba(var(--primary-rgb),0.05)';
            }
        });
    });
});
</script>
@endsection
