@extends('layouts.app')
@section('title', __('Beneficiaries'))
@section('page-title', '👥 ' . __('Beneficiaries'))
@section('page-subtitle', __('Manage your saved recipients'))

@section('content')

{{-- Stats --}}
@php
    $favorites = $beneficiaries->where('is_favorite', true)->count();
    $internal = $beneficiaries->where('type', 'internal')->count();
    $external = $beneficiaries->where('type', '!=', 'internal')->count();
@endphp

<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #3b82f6;">
        <div class="stat-icon blue">👥</div>
        <div class="stat-value">{{ $beneficiaries->count() }}</div>
        <div class="stat-label">{{ __('Total') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #f59e0b;">
        <div class="stat-icon orange">⭐</div>
        <div class="stat-value">{{ $favorites }}</div>
        <div class="stat-label">{{ __('Favorites') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #22c55e;">
        <div class="stat-icon green">🏦</div>
        <div class="stat-value">{{ $internal }}</div>
        <div class="stat-label">{{ __('Internal') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #8b5cf6;">
        <div class="stat-icon purple">🌍</div>
        <div class="stat-value">{{ $external }}</div>
        <div class="stat-label">{{ __('External') }}</div>
    </div>
</div>

{{-- Header --}}
<div class="flex-between mb-4">
    <h2 class="section-title">{{ $beneficiaries->count() }} {{ __('Beneficiary') }}{{ $beneficiaries->count() !== 1 ? 'ies' : '' }}</h2>
    <a href="{{ route('beneficiaries.create') }}" class="btn btn-primary">➕ {{ __('Add Beneficiary') }}</a>
</div>

{{-- Beneficiary List --}}
<div class="card animate-fade-in-up" style="overflow: hidden;">
    @forelse($beneficiaries as $ben)
        @php
            $typeIcons = ['internal' => '🏦', 'domestic' => '🏛️', 'international' => '🌍'];
            $typeColors = ['internal' => '#22c55e', 'domestic' => '#3b82f6', 'international' => '#8b5cf6'];
            $icon = $typeIcons[$ben->type] ?? '🏦';
            $color = $typeColors[$ben->type] ?? '#6b7280';
        @endphp
        <div style="display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); transition: all 0.2s;">

            {{-- Avatar --}}
            <div style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: white; font-size: 16px; flex-shrink: 0; background: {{ $color }};">
                {{ strtoupper(substr($ben->name, 0, 2)) }}
            </div>

            {{-- Info --}}
            <div style="flex: 1; min-width: 0;">
                <div class="flex align-items-center flex-gap-2 mb-1">
                    <span class="font-semibold">{{ $ben->name }}</span>
                    @if($ben->nickname)
                        <span class="text-xs text-muted">({{ $ben->nickname }})</span>
                    @endif
                    @if($ben->is_favorite)
                        <span style="color: #f59e0b;">⭐</span>
                    @endif
                </div>
                <div class="text-xs text-muted" style="display: flex; align-items: center; gap: 8px;">
                    <span>{{ $icon }} {{ ucfirst($ben->type) }}</span>
                    <span>·</span>
                    <span style="font-family: monospace;">{{ $ben->masked_account }}</span>
                    @if($ben->bank_name)
                        <span>·</span>
                        <span>{{ $ben->bank_name }}</span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div style="display: flex; align-items: center; gap: 4px; flex-shrink: 0;">
                {{-- Quick Transfer --}}
                <a href="{{ route('transfers.create', ['to' => $ben->account_number]) }}" class="btn btn-ghost btn-sm" title="{{ __('Send money') }}" style="padding: 4px 8px;">
                    💸
                </a>

                {{-- Toggle Favorite --}}
                <form method="POST" action="{{ route('beneficiaries.toggle-favorite', $ben) }}" style="display: inline;">
                    @csrf
                    <button class="btn btn-ghost btn-sm" title="{{ $ben->is_favorite ? __('Remove from favorites') : __('Add to favorites') }}" style="padding: 4px 8px;">
                        {{ $ben->is_favorite ? '⭐' : '☆' }}
                    </button>
                </form>

                {{-- Edit --}}
                <a href="{{ route('beneficiaries.edit', $ben) }}" class="btn btn-ghost btn-sm" title="{{ __('Edit') }}" style="padding: 4px 8px;">
                    ✏️
                </a>

                {{-- Delete --}}
                <form method="POST" action="{{ route('beneficiaries.destroy', $ben) }}" style="display: inline;" onsubmit="return confirm('{{ __('Remove this beneficiary?') }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost btn-sm" title="{{ __('Delete') }}" style="padding: 4px 8px; color: var(--danger);">
                        🗑️
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div style="padding: 60px 20px; text-align: center;">
            <div style="font-size: 64px; margin-bottom: 16px;">👥</div>
            <p class="font-semibold" style="font-size: 18px; margin-bottom: 4px;">{{ __('No beneficiaries saved') }}</p>
            <p class="text-muted text-sm" style="margin-bottom: 16px;">{{ __('Add a beneficiary to make transfers faster.') }}</p>
            <a href="{{ route('beneficiaries.create') }}" class="btn btn-primary">➕ {{ __('Add Beneficiary') }}</a>
        </div>
    @endforelse
</div>
@endsection
