@extends('layouts.app')
@section('title', __('My Cards'))
@section('page-title', __('My Cards'))
@section('page-subtitle', __('Manage your debit and credit cards'))

@section('content')
{{-- Action Bar --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;" class="animate-fade-in-up">
    <div>
        <span class="text-muted text-sm">{{ $cards->count() }} {{ __('card(s)') }}</span>
    </div>
    <a href="{{ route('cards.create') }}" class="btn btn-primary">
        <span class="btn-text">+ {{ __('Request New Card') }}</span>
    </a>
</div>

@if($cards->count() > 0)
    <div style="display:flex;flex-wrap:wrap;gap:24px;">
        @foreach($cards as $card)
            <div class="animate-fade-in-up" style="animation-delay:{{ $loop->index * 0.1 }}s">
                {{-- Visual Card with 3D Tilt --}}
                <div class="bank-card {{ $card->card_brand }} {{ $card->card_type }} {{ $card->isFrozen() ? 'frozen' : '' }}" data-tilt>
                    <div class="card-glow"></div>
                    <div class="bank-card-type-label">{{ ucfirst($card->card_type) }}</div>
                    @if($card->is_contactless ?? false)
                        <div class="contactless-icon">)))</div>
                    @endif
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;z-index:1;">
                        <div class="bank-card-chip"></div>
                        <span class="badge {{ $card->status_badge_class }}" style="font-size:10px;">
                            {{ __($card->status_label) }}
                        </span>
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
                <div class="card p-4 mt-4" style="width:340px;">
                    <div class="flex-between mb-4">
                        <span class="text-sm font-medium">{{ __(ucfirst($card->card_type)) }} {{ __('Card') }}</span>
                        <span class="text-sm text-muted">{{ $card->account->account_number }}</span>
                    </div>
                    <div class="flex-between text-xs text-muted mb-4">
                        <span>{{ __('Created') }}: {{ $card->created_at->format('M d, Y') }}</span>
                        <span>{{ __('Expires') }}: {{ $card->expiry_date }}</span>
                    </div>

                    {{-- Sensitive Details Reveal --}}
                    @if(session()->has('revealed_card_' . $card->id))
                        <div class="alert alert-success" style="font-family: monospace; font-size: 14px; margin-bottom: 16px; padding: 12px; word-break: break-all;">
                            <div style="margin-bottom:4px;"><span class="text-muted text-xs">{{ __('Card Number') }}</span><br>{{ session('revealed_card_' . $card->id)['number'] }}</div>
                            <div><span class="text-muted text-xs">{{ __('CVV') }}</span><br>{{ session('revealed_card_' . $card->id)['cvv'] }}</div>
                        </div>
                    @else
                        @if($card->isActive())
                            <div style="background: rgba(0,0,0,0.1); padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                                <div class="text-xs text-muted mb-2">{{ __('View Sensitive Details') }}</div>
                                <form method="POST" action="{{ route('cards.reveal', $card) }}" class="flex-between gap-2" style="align-items: center;">
                                    @csrf
                                    <input type="password" name="pin" placeholder="{{ __('Enter Card PIN') }}" class="form-input text-sm" style="flex:1; padding: 6px 10px;" required maxlength="4" pattern="\d{4}">
                                    <button class="btn btn-secondary btn-sm" type="submit">{{ __('Reveal') }}</button>
                                </form>
                            </div>
                        @endif
                    @endif

                    {{-- Pending Activation Warning --}}
                    @if($card->isPendingActivation())
                        <div class="alert alert-warning" style="margin-bottom:12px;font-size:12px;padding:8px 12px;">
                            🔒 {{ __('Card inactive') }} — <a href="{{ route('profile.kyc') }}" style="color:var(--info);text-decoration:underline;">{{ __('Upload KYC documents') }}</a> {{ __('to activate.') }}
                        </div>
                    @endif

                    {{-- Toggle Controls (only for active/frozen cards) --}}
                    @if(!$card->isPendingActivation())
                    <div style="display:grid;gap:12px;">
                        <div class="flex-between">
                            <span class="text-sm" data-tooltip="{{ __('Enable tap-to-pay') }}">{{ __('Contactless') }}</span>
                            <form id="toggle-contactless-{{ $card->id }}" method="POST" action="{{ route('cards.toggle-contactless', $card) }}">
                                @csrf
                                <div class="toggle {{ $card->is_contactless ? 'active' : '' }}" data-toggle-form="toggle-contactless-{{ $card->id }}"></div>
                            </form>
                        </div>
                        <div class="flex-between">
                            <span class="text-sm" data-tooltip="{{ __('Allow e-commerce payments') }}">{{ __('Online Payments') }}</span>
                            <form id="toggle-online-{{ $card->id }}" method="POST" action="{{ route('cards.toggle-online', $card) }}">
                                @csrf
                                <div class="toggle {{ $card->is_online_enabled ? 'active' : '' }}" data-toggle-form="toggle-online-{{ $card->id }}"></div>
                            </form>
                        </div>
                        <div class="flex-between">
                            <span class="text-sm" data-tooltip="{{ __('Use card outside your country') }}">{{ __('International') }}</span>
                            <form id="toggle-intl-{{ $card->id }}" method="POST" action="{{ route('cards.toggle-international', $card) }}">
                                @csrf
                                <div class="toggle {{ $card->is_international_enabled ? 'active' : '' }}" data-toggle-form="toggle-intl-{{ $card->id }}"></div>
                            </form>
                        </div>
                    </div>

                    {{-- Spending Limits --}}
                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                        <div class="flex-between text-xs text-muted mb-2">
                            <span>{{ __('Daily Spending') }}</span>
                            <span>${{ number_format($card->daily_spent, 2) }} / ${{ number_format($card->daily_limit, 2) }}</span>
                        </div>
                        <div class="progress-bar" style="margin-bottom:8px;">
                            <div class="progress-bar-fill" style="width:{{ $card->daily_limit > 0 ? min(($card->daily_spent / $card->daily_limit) * 100, 100) : 0 }}%"></div>
                        </div>
                    </div>
                    @endif

                    {{-- Actions --}}
                    <div style="display:flex;gap:8px;margin-top:16px;">
                        @if($card->isActive())
                            <form method="POST" action="{{ route('cards.freeze', $card) }}" style="flex:1;" data-loading>
                                @csrf
                                <button class="btn btn-danger btn-sm w-full"><span class="btn-text">❄️ {{ __('Freeze') }}</span></button>
                            </form>
                            <a href="{{ route('cards.request-pin-change', $card) }}" class="btn btn-secondary btn-sm" style="flex:1;">🔐 {{ __('Request PIN Change') }}</a>
                        @elseif($card->isFrozen())
                            <form method="POST" action="{{ route('cards.unfreeze', $card) }}" style="flex:1;" data-loading>
                                @csrf
                                <button class="btn btn-success btn-sm w-full"><span class="btn-text">🔓 {{ __('Unfreeze') }}</span></button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="card p-6">
        <div class="empty-state">
            <div class="empty-state-icon">💳</div>
            <p class="empty-state-title">{{ __('No cards yet') }}</p>
            <p class="empty-state-text">{{ __('Create your first card to start making payments.') }}</p>
            <a href="{{ route('cards.create') }}" class="btn btn-primary" style="margin-top:16px;">+ {{ __('Request New Card') }}</a>
        </div>
    </div>
@endif
@endsection
