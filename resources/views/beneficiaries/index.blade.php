@extends('layouts.app')
@section('title', __('Beneficiaries'))
@section('page-title', __('Beneficiaries'))
@section('page-subtitle', __('Manage your saved recipients'))

@section('content')
<div class="section-header">
    <h2 class="section-title">{{ $beneficiaries->count() }} {{ __('Saved Recipients') }}</h2>
    <a href="{{ route('beneficiaries.create') }}" class="btn btn-primary">➕ {{ __('Add Beneficiary') }}</a>
</div>

<div class="card animate-fade-in-up">
    @forelse($beneficiaries as $ben)
        <div class="transaction-item">
            <div style="width:44px;height:44px;border-radius:var(--radius-full);background:var(--gradient-primary);display:flex;align-items:center;justify-content:center;font-weight:600;color:white;font-size:16px;flex-shrink:0;">
                {{ strtoupper(substr($ben->name, 0, 1)) }}
            </div>
            <div class="transaction-details">
                <div class="transaction-title">
                    {{ $ben->name }}
                    @if($ben->nickname) <span class="text-muted">({{ $ben->nickname }})</span> @endif
                    @if($ben->is_favorite) ⭐ @endif
                </div>
                <div class="transaction-meta">
                    {{ $ben->bank_name ?: 'NexusBank' }} · {{ $ben->masked_account }} · {{ ucfirst($ben->type) }}
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <form method="POST" action="{{ route('beneficiaries.toggle-favorite', $ben) }}">
                    @csrf
                    <button class="btn btn-ghost btn-sm" title="Toggle favorite">{{ $ben->is_favorite ? '⭐' : '☆' }}</button>
                </form>
                <a href="{{ route('beneficiaries.edit', $ben) }}" class="btn btn-ghost btn-sm">✏️</a>
                <form method="POST" action="{{ route('beneficiaries.destroy', $ben) }}" onsubmit="return confirm('{{ __('Remove this beneficiary?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm" style="color:var(--danger);">🗑️</button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <p class="empty-state-title">{{ __('No beneficiaries saved') }}</p>
            <p class="empty-state-text">{{ __('Add a beneficiary to make transfers faster.') }}</p>
            <a href="{{ route('beneficiaries.create') }}" class="btn btn-primary btn-sm">{{ __('Add Beneficiary') }}</a>
        </div>
    @endforelse
</div>
@endsection
