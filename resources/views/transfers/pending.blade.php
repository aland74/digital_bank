@extends('layouts.app')
@section('title', 'Pending Transfers')
@section('page-title', 'Pending Transfers')
@section('page-subtitle', 'Accept or decline incoming transfers')

@section('content')
{{-- Helper: resolve user from HQ if not on local branch --}}
@php
    $resolveUser = function ($localUser, int $userId) {
        if ($localUser) return $localUser;
        return \App\Models\User::on(\App\Services\DistributedDatabaseService::getHqConnection())->find($userId)
            ?? (object)['name' => 'Unknown User', 'initials' => '??'];
    };
    $resolveAccount = function ($localAccount, int $accountId) {
        if ($localAccount) return $localAccount;
        return \App\Models\Account::on(\App\Services\DistributedDatabaseService::getHqConnection())->find($accountId)
            ?? (object)['account_number' => 'N/A'];
    };
@endphp

{{-- Tab Navigation --}}
@php
    $pendingIncoming = $incoming->where('status', 'pending')->filter(fn($t) => $t->expires_at->isFuture());
    $pendingTotal = $pendingIncoming->count() + $outgoing->where('status', 'pending')->filter(fn($t) => $t->expires_at->isFuture())->count();
@endphp
<div class="filter-tabs animate-fade-in-up" style="margin-bottom: 20px;">
    <a href="{{ route('transfers.create') }}" class="filter-tab">
        💸 {{ __('New Transfer') }}
    </a>
    <a href="{{ route('transfers.pending') }}" class="filter-tab active">
        ⏳ {{ __('Pending Transfers') }}
        @if($pendingTotal > 0)
            <span class="badge badge-info" style="margin-left: 6px; font-size: 10px;">{{ $pendingTotal }}</span>
        @endif
    </a>
</div>

{{-- Incoming Transfers --}}
<div class="card p-6 mb-6 animate-fade-in-up">
    <h2 class="section-title mb-4">📨 {{ __('Incoming Transfers') }}</h2>

    @if($pendingIncoming->count() > 0)
        @foreach($pendingIncoming as $transfer)
            @php
                $cur = \App\Models\Currency::where('code', $transfer->currency)->first();
                $sym = $cur?->symbol ?? $transfer->currency;
                $dec = $cur?->decimal_places ?? 2;
                $sender = $resolveUser($transfer->senderUser, $transfer->sender_user_id);
                $senderAcc = $resolveAccount($transfer->senderAccount, $transfer->sender_account_id);
            @endphp
            <div class="card p-4 mb-3" style="border-left:3px solid #7c3aed;">
                <div class="flex-between mb-3">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="avatar-initials">
                            {{ $sender->initials ?? strtoupper(substr($sender->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-semibold">{{ $sender->name }}</div>
                            <div class="text-xs text-muted">{{ $senderAcc->account_number }}</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:24px;font-weight:700;color:var(--success);">+{{ $sym }} {{ number_format($transfer->amount, $dec) }}</div>
                        <div class="text-xs text-muted">{{ __('Expires') }} {{ $transfer->time_remaining }}</div>
                    </div>
                </div>

                @if($transfer->description)
                    <div class="card p-3 mb-3" style="font-size:13px;">
                        <span class="text-muted">Note:</span> {{ $transfer->description }}
                    </div>
                @endif

                @php $recvAcc = $resolveAccount($transfer->receiverAccount, $transfer->receiver_account_id); @endphp
                <div class="flex-between text-xs text-muted mb-3">
                    <span>To: {{ $recvAcc->account_number }}</span>
                    <span>Ref: {{ $transfer->reference_number }}</span>
                </div>
                <div class="text-xs text-muted mb-3">
                    Sent: {{ $transfer->created_at->format('M d, Y h:i A') }}
                </div>

                <div style="display:flex;gap:8px;">
                    <form method="POST" action="{{ route('transfers.accept', $transfer) }}" style="flex:1;" data-loading>
                        @csrf
                        <button class="btn btn-success w-full"><span class="btn-text">✓ Accept</span></button>
                    </form>
                    <form method="POST" action="{{ route('transfers.decline', $transfer) }}" style="flex:1;" data-loading>
                        @csrf
                        <button class="btn btn-danger w-full"><span class="btn-text">✕ Decline</span></button>
                    </form>
                </div>
            </div>
        @endforeach
    @else
        <div class="text-muted text-sm" style="text-align:center;padding:20px;">No pending incoming transfers.</div>
    @endif

    {{-- Past incoming --}}
    @php $pastIncoming = $incoming->where('status', '!=', 'pending'); @endphp
    @if($pastIncoming->count() > 0)
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
            <div class="text-muted text-xs mb-3" style="text-transform:uppercase;letter-spacing:0.5px;">History</div>
            @foreach($pastIncoming->take(5) as $transfer)
                @php $pastSender = $resolveUser($transfer->senderUser, $transfer->sender_user_id); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                    <div>
                        <span class="text-sm font-medium">{{ $pastSender->name }}</span>
                        <span class="text-xs text-muted">— {{ \App\Models\Currency::where('code', $transfer->currency)->first()?->symbol ?? $transfer->currency }} {{ number_format($transfer->amount, \App\Models\Currency::where('code', $transfer->currency)->first()?->decimal_places ?? 2) }}</span>
                        <div class="text-xs text-muted mt-1">{{ $transfer->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge badge-{{ $transfer->status === 'accepted' ? 'success' : ($transfer->status === 'declined' ? 'danger' : 'neutral') }}" style="font-size:10px;">
                            {{ ucfirst($transfer->status) }}
                        </span>
                        @if($transfer->accepted_at)
                            <div class="text-xs text-muted mt-1">{{ $transfer->accepted_at->format('M d, Y h:i A') }}</div>
                        @elseif($transfer->declined_at)
                            <div class="text-xs text-muted mt-1">{{ $transfer->declined_at->format('M d, Y h:i A') }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Outgoing Transfers --}}
<div class="card p-6 animate-fade-in-up delay-100">
    <h2 class="section-title mb-4">📤 Outgoing Transfers</h2>

    @php $pendingOutgoing = $outgoing->where('status', 'pending')->filter(fn($t) => $t->expires_at->isFuture()); @endphp

    @if($pendingOutgoing->count() > 0)
        @foreach($pendingOutgoing as $transfer)
            @php $receiver = $resolveUser($transfer->receiverUser, $transfer->receiver_user_id); @endphp
            <div class="card p-4 mb-3" style="border-left:3px solid var(--info);">
                <div class="flex-between mb-3">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div class="avatar-initials">
                            {{ $receiver->initials ?? strtoupper(substr($receiver->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-semibold">To: {{ $receiver->name }}</div>
                            <div class="text-xs text-muted">Waiting for acceptance</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        @php
                            $outCur = \App\Models\Currency::where('code', $transfer->currency)->first();
                            $outSym = $outCur?->symbol ?? $transfer->currency;
                            $outDec = $outCur?->decimal_places ?? 2;
                        @endphp
                        <div style="font-size:20px;font-weight:700;color:var(--warning);">-{{ $outSym }} {{ number_format($transfer->amount, $outDec) }}</div>
                        <span class="badge badge-warning" style="font-size:10px;">Pending</span>
                    </div>
                </div>

                <div class="flex-between text-xs text-muted mb-3">
                    <span>Expires {{ $transfer->time_remaining }}</span>
                    <span>Ref: {{ $transfer->reference_number }}</span>
                </div>
                <div class="text-xs text-muted mb-3">
                    Sent: {{ $transfer->created_at->format('M d, Y h:i A') }}
                </div>

                <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" data-loading>
                    @csrf
                    <button class="btn btn-ghost btn-sm w-full"><span class="btn-text">Cancel Transfer</span></button>
                </form>
            </div>
        @endforeach
    @else
        <div class="text-muted text-sm" style="text-align:center;padding:20px;">No pending outgoing transfers.</div>
    @endif

    {{-- Past outgoing --}}
    @php $pastOutgoing = $outgoing->where('status', '!=', 'pending'); @endphp
    @if($pastOutgoing->count() > 0)
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);">
            <div class="text-muted text-xs mb-3" style="text-transform:uppercase;letter-spacing:0.5px;">History</div>
            @foreach($pastOutgoing->take(5) as $transfer)
                @php $pastReceiver = $resolveUser($transfer->receiverUser, $transfer->receiver_user_id); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border);">
                    <div>
                        <span class="text-sm font-medium">{{ $pastReceiver->name }}</span>
                        <span class="text-xs text-muted">— {{ \App\Models\Currency::where('code', $transfer->currency)->first()?->symbol ?? $transfer->currency }} {{ number_format($transfer->amount, \App\Models\Currency::where('code', $transfer->currency)->first()?->decimal_places ?? 2) }}</span>
                        <div class="text-xs text-muted mt-1">{{ $transfer->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge badge-{{ $transfer->status === 'accepted' ? 'success' : ($transfer->status === 'cancelled' ? 'neutral' : ($transfer->status === 'expired' ? 'warning' : 'danger')) }}" style="font-size:10px;">
                            {{ ucfirst($transfer->status) }}
                        </span>
                        @if($transfer->cancelled_at)
                            <div class="text-xs text-muted mt-1">{{ $transfer->cancelled_at->format('M d, Y h:i A') }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
