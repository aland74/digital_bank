@extends('layouts.app')
@section('title', __('KYC Review'))
@section('page-title', '🪪 ' . __('KYC Document Review'))
@section('page-subtitle', $documents->count() . ' ' . __('pending documents'))

@section('content')
@forelse($documents as $doc)
    <div class="card p-6 mb-4 animate-fade-in-up">
        <div class="flex-between mb-4">
            <div class="flex items-center gap-3">
                <div class="avatar-initials-medium">
                    {{ $doc->user->initials }}
                </div>
                <div>
                    <div class="font-semibold">{{ $doc->user->name }}</div>
                    <div class="text-sm text-muted">{{ $doc->user->email }}</div>
                </div>
            </div>
            <span class="badge badge-warning">{{ __(ucfirst($doc->status)) }}</span>
        </div>

        <div class="grid-3 mb-4">
            <div><div class="text-muted text-xs">{{ __('Document Type') }}</div><div class="font-medium mt-1">{{ $doc->document_type_label }}</div></div>
            <div><div class="text-muted text-xs">{{ __('Document #') }}</div><div class="font-medium mt-1">{{ $doc->document_number ?: '—' }}</div></div>
            <div><div class="text-muted text-xs">{{ __('Uploaded') }}</div><div class="font-medium mt-1">{{ $doc->created_at->diffForHumans() }}</div></div>
        </div>

        <div class="card p-4 mb-4 bg-black/20">
            <div class="text-muted text-xs mb-2">{{ __('Document Preview') }}</div>
            <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="block text-center">
                <img src="{{ Storage::url($doc->file_path) }}" alt="{{ __('KYC Document') }}" class="max-w-full max-h-[250px] rounded object-contain border border-primary mx-auto" onerror="this.outerHTML='<div class=\'p-5 bg-black/20 rounded text-center\'>📄 {{ __('View Document File') }}</div>'">
            </a>
            <div class="text-xs text-muted mt-2 text-center">{{ __('Click image to open full size in new tab') }}</div>
        </div>

        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.kyc.verify', $doc) }}">@csrf<button class="btn btn-success btn-sm">✓ {{ __('Verify') }}</button></form>
            <form method="POST" action="{{ route('admin.kyc.reject', $doc) }}" class="flex gap-2 flex-1">
                @csrf
                <input type="text" name="rejection_reason" class="form-input flex-1 py-1.5 px-3 text-[13px]" placeholder="{{ __('Rejection reason...') }}" required>
                <button class="btn btn-danger btn-sm">✕ {{ __('Reject') }}</button>
            </form>
        </div>
    </div>
@empty
    <div class="card p-6"><div class="empty-state"><div class="empty-state-icon">✅</div><p class="empty-state-title">{{ __('No pending KYC documents') }}</p></div></div>
@endforelse
@endsection
