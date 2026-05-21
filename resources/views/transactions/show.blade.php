@extends('layouts.app')
@section('title', __('Transaction Details'))
@section('page-title', '🧾 ' . __('Transaction Receipt'))
@section('page-subtitle', $transaction->reference_number)

@section('content')
@php
    $cur = \App\Models\Currency::where('code', $transaction->currency)->first();
    $sym = $cur?->symbol ?? $transaction->currency;
    $dec = $cur?->decimal_places ?? 2;
    $isCredit = $transaction->isCredit();
    $statusColors = [
        'completed' => '#22c55e',
        'pending' => '#f59e0b',
        'failed' => '#ef4444',
        'cancelled' => '#6b7280',
        'reversed' => '#ef4444',
    ];
    $statusColor = $statusColors[$transaction->status] ?? '#6b7280';
@endphp

<div style="max-width: 600px;">

    {{-- Receipt Card --}}
    <div class="card animate-fade-in-up" style="overflow: hidden;">

        {{-- Header --}}
        <div style="padding: 32px 24px; background: linear-gradient(135deg, {{ $isCredit ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}, transparent); text-align: center; border-bottom: 1px solid var(--border);">
            <div style="width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 12px; {{ $isCredit ? 'background: rgba(34, 197, 94, 0.2); color: #22c55e;' : 'background: rgba(239, 68, 68, 0.2); color: #ef4444;' }}">
                {{ $isCredit ? '📥' : '📤' }}
            </div>
            <div style="font-size: 36px; font-weight: 800; color: {{ $isCredit ? '#22c55e' : '#ef4444' }}; margin-bottom: 4px;">
                {{ $isCredit ? '+' : '-' }}{{ $sym }}{{ number_format($transaction->amount, $dec) }}
            </div>
            <div class="text-muted text-sm">{{ $transaction->currency }} · {{ __(ucfirst(str_replace('_', ' ', $transaction->type))) }}</div>
            <div style="margin-top: 12px;">
                <span style="padding: 4px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; background: {{ $statusColor }}15; color: {{ $statusColor }};">
                    {{ $transaction->status === 'completed' ? '✅' : ($transaction->status === 'pending' ? '⏳' : '❌') }} {{ __(ucfirst($transaction->status)) }}
                </span>
            </div>
        </div>

        {{-- Status Timeline --}}
        <div style="padding: 20px 24px; border-bottom: 1px solid var(--border);">
            <div style="display: flex; align-items: center; justify-content: center; gap: 0;">
                {{-- Step 1: Initiated --}}
                <div style="text-align: center;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #22c55e; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; margin: 0 auto 4px;">✓</div>
                    <div class="text-xs text-muted">{{ __('Initiated') }}</div>
                </div>
                <div style="flex: 1; height: 2px; background: {{ in_array($transaction->status, ['completed', 'pending']) ? '#22c55e' : 'var(--border)' }}; margin: 0 -4px; margin-bottom: 18px;"></div>

                {{-- Step 2: Processing --}}
                <div style="text-align: center;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: {{ $transaction->status === 'completed' ? '#22c55e' : ($transaction->status === 'pending' ? '#f59e0b' : ($transaction->status === 'failed' ? '#ef4444' : 'var(--border)')) }}; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; margin: 0 auto 4px;">
                        {{ $transaction->status === 'completed' ? '✓' : ($transaction->status === 'failed' ? '✕' : '○') }}
                    </div>
                    <div class="text-xs text-muted">{{ __('Processing') }}</div>
                </div>
                <div style="flex: 1; height: 2px; background: {{ $transaction->status === 'completed' ? '#22c55e' : 'var(--border)' }}; margin: 0 -4px; margin-bottom: 18px;"></div>

                {{-- Step 3: Completed --}}
                <div style="text-align: center;">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: {{ $transaction->status === 'completed' ? '#22c55e' : ($transaction->status === 'failed' ? '#ef4444' : 'var(--border)') }}; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; margin: 0 auto 4px;">
                        {{ $transaction->status === 'completed' ? '✓' : ($transaction->status === 'failed' ? '✕' : '○') }}
                    </div>
                    <div class="text-xs text-muted">{{ $transaction->status === 'failed' ? __('Failed') : __('Completed') }}</div>
                </div>
            </div>
        </div>

        {{-- Details --}}
        <div style="padding: 0;">
            {{-- Reference --}}
            <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                <span class="text-sm text-muted">{{ __('Reference') }}</span>
                <span class="text-sm font-semibold" style="font-family: monospace;">{{ $transaction->reference_number }}</span>
            </div>

            {{-- Type --}}
            <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                <span class="text-sm text-muted">{{ __('Type') }}</span>
                <span class="text-sm font-semibold">{{ __(ucfirst(str_replace('_', ' ', $transaction->type))) }}</span>
            </div>

            {{-- Description --}}
            @if($transaction->description)
                <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                    <span class="text-sm text-muted">{{ __('Description') }}</span>
                    <span class="text-sm font-semibold">{{ $transaction->description }}</span>
                </div>
            @endif

            {{-- Account --}}
            <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                <span class="text-sm text-muted">{{ __('Account') }}</span>
                <span class="text-sm font-semibold" style="font-family: monospace;">{{ $transaction->account->account_number }}</span>
            </div>

            {{-- Recipient --}}
            @if($transaction->recipient_name)
                <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                    <span class="text-sm text-muted">{{ __('Recipient') }}</span>
                    <span class="text-sm font-semibold">{{ $transaction->recipient_name }}</span>
                </div>
            @endif

            {{-- Balance Before --}}
            <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                <span class="text-sm text-muted">{{ __('Balance Before') }}</span>
                <span class="text-sm">{{ $sym }}{{ number_format($transaction->balance_before, $dec) }}</span>
            </div>

            {{-- Balance After --}}
            <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                <span class="text-sm text-muted">{{ __('Balance After') }}</span>
                <span class="text-sm font-semibold" style="color: {{ $isCredit ? '#22c55e' : '#ef4444' }};">{{ $sym }}{{ number_format($transaction->balance_after, $dec) }}</span>
            </div>

            {{-- Channel --}}
            <div style="display: flex; justify-content: space-between; padding: 14px 24px; border-bottom: 1px solid var(--border);">
                <span class="text-sm text-muted">{{ __('Channel') }}</span>
                <span class="text-sm">{{ __(ucfirst($transaction->channel)) }}</span>
            </div>

            {{-- Date --}}
            <div style="display: flex; justify-content: space-between; padding: 14px 24px;">
                <span class="text-sm text-muted">{{ __('Date & Time') }}</span>
                <span class="text-sm font-semibold">{{ $transaction->created_at->format('F d, Y \a\t h:i:s A') }}</span>
            </div>
        </div>

        {{-- Actions --}}
        <div style="padding: 20px 24px; border-top: 1px solid var(--border); display: flex; gap: 12px;">
            <a href="{{ route('transactions.index') }}" class="btn btn-secondary flex-1">← {{ __('Back') }}</a>
            <button onclick="copyReceipt()" class="btn btn-ghost flex-1">📋 {{ __('Copy Receipt') }}</button>
        </div>
    </div>
</div>

<script>
function copyReceipt() {
    const text = `Transaction Receipt
Reference: {{ $transaction->reference_number }}
Type: {{ __(ucfirst(str_replace('_', ' ', $transaction->type))) }}
Amount: {{ $isCredit ? '+' : '-' }}{{ $sym }}{{ number_format($transaction->amount, $dec) }}
Status: {{ __(ucfirst($transaction->status)) }}
Account: {{ $transaction->account->account_number }}
{{ $transaction->recipient_name ? 'Recipient: ' . $transaction->recipient_name . '\n' : '' }}Balance Before: {{ $sym }}{{ number_format($transaction->balance_before, $dec) }}
Balance After: {{ $sym }}{{ number_format($transaction->balance_after, $dec) }}
Date: {{ $transaction->created_at->format('F d, Y \a\t h:i:s A') }}`;

    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const original = btn.textContent;
        btn.textContent = '✅ Copied!';
        setTimeout(() => btn.textContent = original, 1500);
    });
}
</script>
@endsection
