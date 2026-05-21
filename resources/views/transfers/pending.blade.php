@extends('layouts.app')
@section('title', __('Pending Transfers'))
@section('page-title', '⏳ ' . __('Pending Transfers'))
@section('page-subtitle', __('Accept or decline incoming transfers'))

@section('content')
@php
    $resolveUser = function ($localUser, int $userId) {
        if ($localUser) return $localUser;
        return \App\Models\User::on(\App\Services\DistributedDatabaseService::getHqConnection())->find($userId)
            ?? (object)['name' => 'Unknown User', 'initials' => '??'];
    };
    $resolveAccount = function ($localAccount, int $accountId) {
        if ($localAccount) return $localAccount;
        return \App\Models\Account::on(\App\Services\DistributedDatabaseService::getHqConnection())->find($accountId)
            ?? (object)['account_number' => 'N/A', 'currency' => 'USD'];
    };

    $pendingIncoming = $incoming->where('status', 'pending')->filter(fn($t) => $t->expires_at->isFuture());
    $expiredIncoming = $incoming->where('status', 'pending')->filter(fn($t) => $t->expires_at->isPast());
    $processedIncoming = $incoming->where('status', '!=', 'pending');

    $pendingOutgoing = $outgoing->where('status', 'pending')->filter(fn($t) => $t->expires_at->isFuture());
    $expiredOutgoing = $outgoing->where('status', 'pending')->filter(fn($t) => $t->expires_at->isPast());
    $processedOutgoing = $outgoing->where('status', '!=', 'pending');

    $pendingTotal = $pendingIncoming->count() + $pendingOutgoing->count();
@endphp

{{-- Tabs --}}
<div class="filter-tabs mb-6 animate-fade-in-up">
    <a href="{{ route('transfers.create') }}" class="filter-tab">💸 {{ __('New Transfer') }}</a>
    <a href="{{ route('transfers.pending') }}" class="filter-tab active">
        ⏳ {{ __('Pending') }}
        @if($pendingTotal > 0)
            <span class="badge badge-info" style="margin-left: 6px;">{{ $pendingTotal }}</span>
        @endif
    </a>
</div>

{{-- Stats --}}
<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">📨</div>
        <div class="stat-value">{{ $pendingIncoming->count() }}</div>
        <div class="stat-label">{{ __('Incoming') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #f59e0b;">
        <div class="stat-icon orange">📤</div>
        <div class="stat-value">{{ $pendingOutgoing->count() }}</div>
        <div class="stat-label">{{ __('Outgoing') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon red">⏰</div>
        <div class="stat-value">{{ $expiredIncoming->count() + $expiredOutgoing->count() }}</div>
        <div class="stat-label">{{ __('Expired') }}</div>
    </div>
</div>

<div class="grid-2" style="gap: 24px; align-items: start;">

    {{-- LEFT: Incoming Transfers --}}
    <div>
        <h2 class="section-title mb-4 animate-fade-in-up">📨 {{ __('Incoming Transfers') }}</h2>

        {{-- Pending Incoming --}}
        @if($pendingIncoming->count() > 0)
            @foreach($pendingIncoming as $transfer)
                @php
                    $sender = $resolveUser($transfer->senderUser, $transfer->sender_user_id);
                    $senderAcc = $resolveAccount($transfer->senderAccount, $transfer->sender_account_id);
                    $receiverAcc = $resolveAccount($transfer->receiverAccount, $transfer->receiver_account_id);
                    $cur = \App\Models\Currency::where('code', $transfer->currency)->first();
                    $sym = $cur?->symbol ?? $transfer->currency;
                    $dec = $cur?->decimal_places ?? 2;
                @endphp
                <div class="card p-4 mb-4 animate-fade-in-up" style="border-left: 3px solid #3b82f6;">
                    <div class="flex-between mb-3">
                        <div class="flex align-items-center flex-gap-2">
                            <div class="avatar-initials">{{ $sender->initials ?? '??' }}</div>
                            <div>
                                <div class="font-semibold">{{ $sender->name }}</div>
                                <div class="text-xs text-muted">{{ __('From') }}: {{ $senderAcc->account_number }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold" style="font-size: 20px; color: {{ $transfer->currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">
                                {{ $sym }}{{ number_format($transfer->amount, $dec) }}
                            </div>
                            <div class="text-xs text-muted">{{ $transfer->currency }}</div>
                        </div>
                    </div>

                    @if($transfer->description)
                        <div class="text-sm text-muted mb-3" style="padding: 8px; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                            💬 {{ $transfer->description }}
                        </div>
                    @endif

                    <div class="flex-between text-xs text-muted mb-3">
                        <span>📋 {{ $transfer->reference_number }}</span>
                        <span>⏰ {{ $transfer->expires_at->diffForHumans() }}</span>
                    </div>

                    {{-- Currency Conversion Info --}}
                    @if($transfer->currency !== $receiverAcc->currency)
                        <div class="text-xs mb-3" style="padding: 8px; background: rgba(245, 158, 11, 0.1); border-radius: var(--radius-sm); color: #f59e0b;">
                            💱 {{ __('Currency conversion') }}: {{ $sym }}{{ number_format($transfer->amount, $dec) }} → {{ \App\Models\Currency::where('code', $receiverAcc->currency)->first()?->symbol ?? '' }}{{ number_format($transfer->amount * ($transfer->exchange_rate ?? 1), \App\Models\Currency::where('code', $receiverAcc->currency)->first()?->decimal_places ?? 2) }}
                            @if($transfer->exchange_rate)
                                (1 {{ $transfer->currency }} = {{ number_format($transfer->exchange_rate, 2) }} {{ $receiverAcc->currency }})
                            @endif
                        </div>
                    @endif

                    <div class="flex flex-gap-2">
                        <form method="POST" action="{{ route('transfers.accept', $transfer) }}" class="flex-1" data-loading>
                            @csrf
                            <button type="submit" class="btn btn-success w-full" onclick="return confirm('{{ __('Accept this transfer? Funds will be added to your account.') }}')">
                                ✅ {{ __('Accept') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('transfers.decline', $transfer) }}" class="flex-1" data-loading>
                            @csrf
                            <button type="submit" class="btn btn-danger w-full" onclick="return confirm('{{ __('Decline this transfer? Sender\'s funds will be released.') }}')">
                                ❌ {{ __('Decline') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Expired Incoming --}}
        @if($expiredIncoming->count() > 0)
            <div class="text-xs text-muted mb-2 mt-4">{{ __('Expired') }} ({{ $expiredIncoming->count() }})</div>
            @foreach($expiredIncoming as $transfer)
                @php
                    $sender = $resolveUser($transfer->senderUser, $transfer->sender_user_id);
                    $cur = \App\Models\Currency::where('code', $transfer->currency)->first();
                    $sym = $cur?->symbol ?? $transfer->currency;
                    $dec = $cur?->decimal_places ?? 2;
                @endphp
                <div class="card p-3 mb-2 animate-fade-in-up" style="opacity: 0.6; border-left: 3px solid #6b7280;">
                    <div class="flex-between">
                        <div>
                            <div class="font-semibold text-sm">{{ $sender->name }}</div>
                            <div class="text-xs text-muted">{{ $transfer->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold" style="text-decoration: line-through;">{{ $sym }}{{ number_format($transfer->amount, $dec) }}</div>
                            <span class="badge badge-danger">{{ __('Expired') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Processed Incoming --}}
        @if($processedIncoming->count() > 0)
            <div class="text-xs text-muted mb-2 mt-4">{{ __('Processed') }} ({{ $processedIncoming->count() }})</div>
            @foreach($processedIncoming as $transfer)
                @php
                    $sender = $resolveUser($transfer->senderUser, $transfer->sender_user_id);
                    $cur = \App\Models\Currency::where('code', $transfer->currency)->first();
                    $sym = $cur?->symbol ?? $transfer->currency;
                    $dec = $cur?->decimal_places ?? 2;
                    $statusColors = ['accepted' => 'success', 'completed' => 'success', 'declined' => 'danger', 'cancelled' => 'warning', 'expired' => 'danger'];
                @endphp
                <div class="card p-3 mb-2 animate-fade-in-up" style="border-left: 3px solid var(--{{ $statusColors[$transfer->status] ?? 'info' }});">
                    <div class="flex-between">
                        <div>
                            <div class="font-semibold text-sm">{{ $sender->name }}</div>
                            <div class="text-xs text-muted">{{ $transfer->updated_at->format('M d, Y h:i A') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">{{ $sym }}{{ number_format($transfer->amount, $dec) }}</div>
                            <span class="badge badge-{{ $statusColors[$transfer->status] ?? 'info' }}">{{ ucfirst($transfer->status) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Empty State --}}
        @if($incoming->count() === 0)
            <div class="card p-6 animate-fade-in-up">
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                    <p class="empty-state-title">{{ __('No incoming transfers') }}</p>
                    <p class="empty-state-text">{{ __('When someone sends you money, it will appear here.') }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- RIGHT: Outgoing Transfers --}}
    <div>
        <h2 class="section-title mb-4 animate-fade-in-up">📤 {{ __('Outgoing Transfers') }}</h2>

        {{-- Pending Outgoing --}}
        @if($pendingOutgoing->count() > 0)
            @foreach($pendingOutgoing as $transfer)
                @php
                    $receiver = $resolveUser($transfer->receiverUser, $transfer->receiver_user_id);
                    $receiverAcc = $resolveAccount($transfer->receiverAccount, $transfer->receiver_account_id);
                    $cur = \App\Models\Currency::where('code', $transfer->currency)->first();
                    $sym = $cur?->symbol ?? $transfer->currency;
                    $dec = $cur?->decimal_places ?? 2;
                @endphp
                <div class="card p-4 mb-4 animate-fade-in-up" style="border-left: 3px solid #f59e0b;">
                    <div class="flex-between mb-3">
                        <div class="flex align-items-center flex-gap-2">
                            <div class="avatar-initials">{{ $receiver->initials ?? '??' }}</div>
                            <div>
                                <div class="font-semibold">{{ $receiver->name }}</div>
                                <div class="text-xs text-muted">{{ __('To') }}: {{ $receiverAcc->account_number }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold" style="font-size: 20px; color: {{ $transfer->currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">
                                {{ $sym }}{{ number_format($transfer->amount, $dec) }}
                            </div>
                            <div class="text-xs text-muted">{{ $transfer->currency }}</div>
                        </div>
                    </div>

                    @if($transfer->description)
                        <div class="text-sm text-muted mb-3" style="padding: 8px; background: var(--bg-secondary); border-radius: var(--radius-sm);">
                            💬 {{ $transfer->description }}
                        </div>
                    @endif

                    <div class="flex-between text-xs text-muted mb-3">
                        <span>📋 {{ $transfer->reference_number }}</span>
                        <span>⏰ {{ $transfer->expires_at->diffForHumans() }}</span>
                    </div>

                    {{-- Cancel Button --}}
                    <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" data-loading>
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm w-full" onclick="return confirm('{{ __('Cancel this transfer? Your held funds will be released.') }}')">
                            🚫 {{ __('Cancel Transfer') }}
                        </button>
                    </form>
                </div>
            @endforeach
        @endif

        {{-- Expired Outgoing --}}
        @if($expiredOutgoing->count() > 0)
            <div class="text-xs text-muted mb-2 mt-4">{{ __('Expired') }} ({{ $expiredOutgoing->count() }})</div>
            @foreach($expiredOutgoing as $transfer)
                @php
                    $receiver = $resolveUser($transfer->receiverUser, $transfer->receiver_user_id);
                    $cur = \App\Models\Currency::where('code', $transfer->currency)->first();
                    $sym = $cur?->symbol ?? $transfer->currency;
                    $dec = $cur?->decimal_places ?? 2;
                @endphp
                <div class="card p-3 mb-2 animate-fade-in-up" style="opacity: 0.6; border-left: 3px solid #6b7280;">
                    <div class="flex-between">
                        <div>
                            <div class="font-semibold text-sm">{{ $receiver->name }}</div>
                            <div class="text-xs text-muted">{{ $transfer->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold" style="text-decoration: line-through;">{{ $sym }}{{ number_format($transfer->amount, $dec) }}</div>
                            <span class="badge badge-danger">{{ __('Expired') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Processed Outgoing --}}
        @if($processedOutgoing->count() > 0)
            <div class="text-xs text-muted mb-2 mt-4">{{ __('Processed') }} ({{ $processedOutgoing->count() }})</div>
            @foreach($processedOutgoing as $transfer)
                @php
                    $receiver = $resolveUser($transfer->receiverUser, $transfer->receiver_user_id);
                    $cur = \App\Models\Currency::where('code', $transfer->currency)->first();
                    $sym = $cur?->symbol ?? $transfer->currency;
                    $dec = $cur?->decimal_places ?? 2;
                    $statusColors = ['accepted' => 'success', 'completed' => 'success', 'declined' => 'danger', 'cancelled' => 'warning', 'expired' => 'danger'];
                @endphp
                <div class="card p-3 mb-2 animate-fade-in-up" style="border-left: 3px solid var(--{{ $statusColors[$transfer->status] ?? 'info' }});">
                    <div class="flex-between">
                        <div>
                            <div class="font-semibold text-sm">{{ $receiver->name }}</div>
                            <div class="text-xs text-muted">{{ $transfer->updated_at->format('M d, Y h:i A') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">{{ $sym }}{{ number_format($transfer->amount, $dec) }}</div>
                            <span class="badge badge-{{ $statusColors[$transfer->status] ?? 'info' }}">{{ ucfirst($transfer->status) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Empty State --}}
        @if($outgoing->count() === 0)
            <div class="card p-6 animate-fade-in-up">
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                    <p class="empty-state-title">{{ __('No outgoing transfers') }}</p>
                    <p class="empty-state-text">{{ __('Your sent transfers will appear here.') }}</p>
                    <a href="{{ route('transfers.create') }}" class="btn btn-primary mt-4">{{ __('Send a Transfer') }}</a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
