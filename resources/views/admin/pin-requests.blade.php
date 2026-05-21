@extends('layouts.app')
@section('title', __('PIN Change Requests'))
@section('page-title', '🔐 ' . __('PIN Change Requests'))
@section('page-subtitle', __('Review and process card PIN change requests'))

@section('content')
{{-- Pending Requests --}}
<div class="card p-6 mb-6 animate-fade-in-up">
    <div class="flex-between mb-4">
        <h2 class="section-title">⏳ {{ __('Pending Requests') }} ({{ $pendingRequests->count() }})</h2>
    </div>

    @forelse($pendingRequests as $req)
        <div class="padding-all-20 margin-bottom-16 card border {{ $req->reason === 'stolen' ? 'border-red-500/50 shadow-lg shadow-red-500/10' : 'border-cyan-500/30' }}">
            <div class="flex-between align-items-start flex-wrap flex-gap-2">
                {{-- User & Card Info --}}
                <div class="flex align-items-center flex-gap-2">
                    <div class="avatar-initials-medium">{{ $req->user->initials }}</div>
                    <div>
                        <div class="font-medium font-size-15 text-white">{{ $req->user->name }}</div>
                        <div class="text-xs text-muted">{{ $req->user->email }}</div>
                        <div class="flex flex-gap-2 mt-1">
                            <span class="badge badge-info badge-pad-medium">💳 **** {{ $req->card->card_number_last4 }}</span>
                            <span class="badge badge-{{ $req->card->status === 'frozen' ? 'danger' : ($req->card->status === 'active' ? 'success' : 'warning') }} badge-pad-medium">{{ ucfirst($req->card->status) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Request Details --}}
                <div class="text-right min-w-[200px]">
                    <div class="flex flex-gap-2 justify-end align-items-center mb-1">
                        <span class="text-base">{{ $req->reason_icon }}</span>
                        <span class="font-medium text-sm {{ $req->reason === 'stolen' ? 'text-red' : 'text-white' }}">{{ __($req->reason_label) }}</span>
                    </div>
                    <div class="text-xs text-muted">{{ __('Submitted') }}: {{ $req->created_at->diffForHumans() }}</div>
                    <div class="text-xs text-muted">{{ $req->created_at->format('M d, Y · h:i A') }}</div>
                </div>
            </div>

            @if($req->description)
                <div class="mt-3 p-3 bg-black/20 rounded border-l-2 border-cyan-500">
                    <div class="text-xs text-muted mb-1 font-weight-600">{{ __('User\'s Message') }}:</div>
                    <div class="text-sm">{{ $req->description }}</div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="flex flex-gap-2 mt-4 pt-4 border-top-divider">
                <form method="POST" action="{{ route('admin.pin-requests.approve', $req) }}" class="flex-1" data-loading>
                    @csrf
                    <input type="text" name="admin_note" class="form-input mb-2 text-xs py-1.5 px-2.5" placeholder="{{ __('Admin note (optional)...') }}">
                    <button type="submit" class="btn btn-success btn-sm w-full" onclick="return confirm('{{ __('Generate a new PIN and notify the user?') }}')">
                        <span class="btn-text">✅ {{ __('Approve & Generate New PIN') }}</span>
                    </button>
                </form>

                <div class="flex-1">
                    <form method="POST" action="{{ route('admin.pin-requests.reject', $req) }}" data-loading>
                        @csrf
                        <input type="text" name="rejection_reason" class="form-input mb-2 text-xs py-1.5 px-2.5" placeholder="{{ __('Rejection reason (required)...') }}" required>
                        <button type="submit" class="btn btn-danger btn-sm w-full" onclick="return confirm('{{ __('Reject this PIN change request?') }}')">
                            <span class="btn-text">❌ {{ __('Reject Request') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">✅</div>
            <p class="empty-state-title">{{ __('No pending requests') }}</p>
            <p class="empty-state-text{{ app()->getLocale() === 'ckb' ? ' text-center' : '' }}">{{ __('All PIN change requests have been processed.') }}</p>
        </div>
    @endforelse
</div>

{{-- Recently Processed --}}
@if($processedRequests->count() > 0)
<div class="card p-6 animate-fade-in-up delay-100">
    <h2 class="section-title mb-4">📋 {{ __('Recently Processed') }}</h2>

    <div class="data-table-wrapper">
        <table class="data-table data-table-full">
            <thead>
                <tr>
                    <th>{{ __('User') }}</th>
                    <th>{{ __('Card') }}</th>
                    <th>{{ __('Reason') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Processed By') }}</th>
                    <th>{{ __('Date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($processedRequests as $req)
                    <tr>
                        <td>
                            <div class="font-medium text-sm text-white">{{ $req->user->name }}</div>
                        </td>
                        <td>
                            <span class="text-sm text-muted">**** {{ $req->card->card_number_last4 }}</span>
                        </td>
                        <td>
                            <span class="text-sm text-white">{{ $req->reason_icon }} {{ __($req->reason_label) }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $req->status_badge_class }} badge-pad-medium">{{ __(ucfirst($req->status)) }}</span>
                        </td>
                        <td>
                            <span class="text-sm text-muted">{{ $req->processedBy?->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="text-xs text-muted">{{ $req->processed_at?->diffForHumans() }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
