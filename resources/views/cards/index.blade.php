@extends('layouts.app')
@section('title', __('My Cards'))
@section('page-title', '💳 ' . __('My Cards'))
@section('page-subtitle', __('Manage your debit and credit cards'))

@section('content')

{{-- Stats --}}
@php
    $activeCards = $cards->where('status', 'active')->count();
    $frozenCards = $cards->where('status', 'frozen')->count();
@endphp

<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #22c55e;">
        <div class="stat-icon green">✅</div>
        <div class="stat-value">{{ $activeCards }}</div>
        <div class="stat-label">{{ __('Active Cards') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">💳</div>
        <div class="stat-value">{{ $cards->count() }}</div>
        <div class="stat-label">{{ __('Total Cards') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon red">❄️</div>
        <div class="stat-value">{{ $frozenCards }}</div>
        <div class="stat-label">{{ __('Frozen') }}</div>
    </div>
</div>

{{-- Header --}}
<div class="flex-between mb-6">
    <h2 class="section-title">{{ $cards->count() }} {{ __('Card') }}{{ $cards->count() !== 1 ? 's' : '' }}</h2>
    <a href="{{ route('cards.create') }}" class="btn btn-primary">+ {{ __('Request New Card') }}</a>
</div>

{{-- Cards --}}
@if($cards->count() > 0)
    <div style="display: flex; flex-wrap: wrap; gap: 32px;">
        @foreach($cards as $card)
            @php
                $cardCur = $card->account ? \App\Models\Currency::where('code', $card->account->currency)->first() : null;
                $cardSym = $cardCur?->symbol ?? '$';
                $cardDec = $cardCur?->decimal_places ?? 2;
                $cardCurrency = $card->account->currency ?? 'USD';
                $isUsd = $cardCurrency === 'USD';
            @endphp
            <div class="animate-fade-in-up" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                {{-- Visual Card --}}
                <div class="bank-card {{ $card->card_brand }} {{ $card->card_type }} {{ $card->isFrozen() ? 'frozen' : '' }}" data-tilt>
                    <div class="card-glow"></div>
                    <div class="bank-card-type-label">{{ ucfirst($card->card_type) }}</div>
                    @if($card->is_contactless ?? false)
                        <div class="contactless-icon">)))</div>
                    @endif
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; z-index: 1;">
                        <div class="bank-card-chip"></div>
                        <div class="flex flex-gap-2">
                            <span style="padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; {{ $isUsd ? 'background: rgba(59, 130, 246, 0.3); color: #93c5fd;' : 'background: rgba(245, 158, 11, 0.3); color: #fcd34d;' }}">
                                {{ $cardCurrency }}
                            </span>
                            <span class="badge {{ $card->status_badge_class }}" style="font-size: 10px;">
                                {{ __($card->status_label) }}
                            </span>
                        </div>
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

                {{-- Card Controls --}}
                <div class="card p-4 mt-4" style="width: 360px;">
                    <div class="flex-between mb-3">
                        <div>
                            <span class="font-semibold">{{ __(ucfirst($card->card_type)) }} {{ __('Card') }}</span>
                            <span class="text-xs text-muted" style="margin-left: 8px;">{{ $cardCurrency }}</span>
                        </div>
                        <span class="text-sm text-muted" style="font-family: monospace;">{{ $card->account->account_number ?? '' }}</span>
                    </div>

                    {{-- Balance --}}
                    @if($card->account)
                        <div style="padding: 12px; background: var(--bg-secondary); border-radius: var(--radius-md); margin-bottom: 12px; border-left: 3px solid {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
                            <div class="text-xs text-muted">{{ __('Account Balance') }}</div>
                            <div class="font-bold" style="font-size: 18px; color: {{ $isUsd ? '#3b82f6' : '#f59e0b' }};">
                                {{ $cardSym }}{{ number_format($card->account->available_balance, $cardDec) }}
                            </div>
                        </div>
                    @endif

                    {{-- Sensitive Details Reveal --}}
                    @if(session()->has('revealed_card_' . $card->id))
                        <div style="padding: 12px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: var(--radius-md); margin-bottom: 12px; font-family: monospace;">
                            <div class="text-xs text-muted mb-1">{{ __('Card Number') }}</div>
                            <div class="font-semibold" style="letter-spacing: 2px;">{{ session('revealed_card_' . $card->id)['number'] }}</div>
                            <div class="text-xs text-muted mt-2 mb-1">{{ __('CVV') }}</div>
                            <div class="font-semibold">{{ session('revealed_card_' . $card->id)['cvv'] }}</div>
                        </div>
                    @else
                        @if($card->isActive())
                            <div style="padding: 12px; background: var(--bg-secondary); border-radius: var(--radius-md); margin-bottom: 12px;">
                                <div class="text-xs text-muted mb-2">{{ __('View Sensitive Details') }}</div>
                                <form method="POST" action="{{ route('cards.reveal', $card) }}" class="flex flex-gap-2" style="align-items: center;">
                                    @csrf
                                    <input type="password" name="pin" placeholder="{{ __('Enter PIN') }}" class="form-input text-sm" style="flex: 1; padding: 6px 10px;" required maxlength="4" pattern="\d{4}">
                                    <button class="btn btn-secondary btn-sm" type="submit">{{ __('Reveal') }}</button>
                                </form>
                            </div>
                        @endif
                    @endif

                    {{-- Pending Activation Warning --}}
                    @if($card->isPendingActivation())
                        <div class="alert alert-warning" style="margin-bottom: 12px; font-size: 12px; padding: 8px 12px;">
                            🔒 {{ __('Card inactive') }} — <a href="{{ route('profile.kyc') }}" style="color: var(--info); text-decoration: underline;">{{ __('Upload KYC documents') }}</a> {{ __('to activate.') }}
                        </div>
                    @endif

                    {{-- Toggle Controls --}}
                    @if(!$card->isPendingActivation())
                        <div style="display: grid; gap: 10px; margin-bottom: 12px;">
                            <div class="flex-between">
                                <span class="text-sm">{{ __('Contactless') }}</span>
                                <form id="toggle-contactless-{{ $card->id }}" method="POST" action="{{ route('cards.toggle-contactless', $card) }}">
                                    @csrf
                                    <div class="toggle {{ $card->is_contactless ? 'active' : '' }}" data-toggle-form="toggle-contactless-{{ $card->id }}"></div>
                                </form>
                            </div>
                            <div class="flex-between">
                                <span class="text-sm">{{ __('Online Payments') }}</span>
                                <form id="toggle-online-{{ $card->id }}" method="POST" action="{{ route('cards.toggle-online', $card) }}">
                                    @csrf
                                    <div class="toggle {{ $card->is_online_enabled ? 'active' : '' }}" data-toggle-form="toggle-online-{{ $card->id }}"></div>
                                </form>
                            </div>
                            <div class="flex-between">
                                <span class="text-sm">{{ __('International') }}</span>
                                <form id="toggle-intl-{{ $card->id }}" method="POST" action="{{ route('cards.toggle-international', $card) }}">
                                    @csrf
                                    <div class="toggle {{ $card->is_international_enabled ? 'active' : '' }}" data-toggle-form="toggle-intl-{{ $card->id }}"></div>
                                </form>
                            </div>
                        </div>

                        {{-- Spending Limits --}}
                        <div style="padding-top: 12px; border-top: 1px solid var(--border); margin-bottom: 12px;">
                            <div class="flex-between text-xs text-muted mb-2">
                                <span>{{ __('Daily Spending') }}</span>
                                <span>{{ $cardSym }}{{ number_format($card->daily_spent, $cardDec) }} / {{ $cardSym }}{{ number_format($card->daily_limit, $cardDec) }}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-bar-fill" style="width: {{ $card->daily_limit > 0 ? min(($card->daily_spent / $card->daily_limit) * 100, 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex flex-gap-2">
                        @if($card->isActive())
                            <form method="POST" action="{{ route('cards.freeze', $card) }}" style="flex: 1;" data-loading>
                                @csrf
                                <button class="btn btn-danger btn-sm w-full">❄️ {{ __('Freeze') }}</button>
                            </form>
                            <a href="{{ route('cards.request-pin-change', $card) }}" class="btn btn-secondary btn-sm" style="flex: 1;">🔐 {{ __('Change PIN') }}</a>
                        @elseif($card->isFrozen())
                            <form method="POST" action="{{ route('cards.unfreeze', $card) }}" style="flex: 1;" data-loading>
                                @csrf
                                <button class="btn btn-success btn-sm w-full">🔓 {{ __('Unfreeze') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="card p-6 animate-fade-in-up">
        <div class="empty-state">
            <div style="font-size: 64px; margin-bottom: 16px;">💳</div>
            <p class="empty-state-title">{{ __('No cards yet') }}</p>
            <p class="empty-state-text">{{ __('Create your first card to start making payments.') }}</p>
            <a href="{{ route('cards.create') }}" class="btn btn-primary mt-4">+ {{ __('Request New Card') }}</a>
        </div>
    </div>
@endif
@endsection
