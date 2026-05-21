@extends('layouts.app')
@section('title', __('Bank Settings'))
@section('page-title', '⚙️ ' . __('Bank Settings'))
@section('page-subtitle', $isSuperAdmin ? __('Financial overview and system configuration') : __('Financial overview (read-only)'))

@section('content')

{{-- Role Badge --}}
@if(!$isSuperAdmin)
<div class="card p-3 mb-4 animate-fade-in-up" style="border-left:3px solid var(--info);background:rgba(59,130,246,0.05);">
    <div style="display:flex;align-items:center;gap:8px;">
        <span>ℹ️</span>
        <span class="text-sm">{{ __('You are viewing in read-only mode. Only Super Admins can modify bank settings.') }}</span>
    </div>
</div>
@endif

{{-- Financial Overview Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px;" class="animate-fade-in-up">
    <div class="card p-4">
        <div class="flex-between">
            <div>
                <div class="text-muted text-xs">{{ __('Total Deposits') }}</div>
                <div class="font-semibold text-lg mt-1" style="color:var(--success);">${{ number_format($reserveHealth['total_deposits'], 2) }}</div>
                <div class="text-xs text-muted mt-1">د.ع {{ number_format($reserveHealth['total_deposits'] * $iqdRate, 0) }}</div>
            </div>
            <div class="text-2xl">💰</div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex-between">
            <div>
                <div class="text-muted text-xs">{{ __('Held Funds') }}</div>
                <div class="font-semibold text-lg mt-1" style="color:var(--warning);">${{ number_format($financialOverview['totalHeldFunds'], 2) }}</div>
                <div class="text-xs text-muted mt-1">د.ع {{ number_format($financialOverview['totalHeldFunds'] * $iqdRate, 0) }}</div>
            </div>
            <div class="text-2xl">🔒</div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex-between">
            <div>
                <div class="text-muted text-xs">{{ __('Available Balance') }}</div>
                <div class="font-semibold text-lg mt-1" style="color:var(--primary);">${{ number_format($financialOverview['totalAvailableBalance'], 2) }}</div>
                <div class="text-xs text-muted mt-1">د.ع {{ number_format($financialOverview['totalAvailableBalance'] * $iqdRate, 0) }}</div>
            </div>
            <div class="text-2xl">📊</div>
        </div>
    </div>
    <div class="card p-4">
        <div class="flex-between">
            <div>
                <div class="text-muted text-xs">{{ __('Active Loans') }}</div>
                <div class="font-semibold text-lg mt-1" style="color:var(--danger);">${{ number_format($financialOverview['totalLoanAmount'], 2) }}</div>
                <div class="text-xs text-muted mt-1">د.ع {{ number_format($financialOverview['totalLoanAmount'] * $iqdRate, 0) }}</div>
            </div>
            <div class="text-2xl">📋</div>
        </div>
    </div>
</div>

<div class="grid-2 align-items-start">
    {{-- Reserve Health & Loan Capacity --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-6">🏦 {{ __('Reserve Health') }}</h2>

        <div class="text-center mb-6">
            <div class="health-dial-outer" style="background: conic-gradient({{ $reserveHealth['status'] === 'excellent' ? 'var(--success)' : ($reserveHealth['status'] === 'good' ? 'var(--primary)' : ($reserveHealth['status'] === 'warning' ? 'var(--warning)' : 'var(--danger)')) }} {{ min($reserveHealth['ratio'], 100) * 3.6 }}deg, var(--bg-white) 0deg);">
                <div class="health-dial-inner">
                    <div class="dial-value">{{ $reserveHealth['ratio'] }}%</div>
                    <div class="dial-label">{{ __('Health') }}</div>
                </div>
            </div>

            <span class="badge badge-{{ $reserveHealth['status'] === 'excellent' ? 'success' : ($reserveHealth['status'] === 'good' ? 'info' : ($reserveHealth['status'] === 'warning' ? 'warning' : 'danger')) }} badge-pad-large">
                {{ __(ucfirst($reserveHealth['status'])) }}
            </span>
        </div>

        <div class="flex flex-column flex-gap-2">
            <div class="card p-4">
                <div class="flex-between">
                    <div>
                        <div class="text-muted text-xs">{{ __('Minimum Required') }}</div>
                        <div class="font-semibold text-lg mt-1 text-white">${{ number_format($reserveHealth['minimum'], 2) }}</div>
                        <div class="text-xs text-muted">د.ع {{ number_format($reserveHealth['minimum'] * $iqdRate, 0) }}</div>
                    </div>
                    <div class="text-2xl">🎯</div>
                </div>
            </div>
            <div class="card p-4">
                <div class="flex-between">
                    <div>
                        <div class="text-muted text-xs">{{ __('Buffer') }}</div>
                        <div class="font-semibold text-lg mt-1 {{ $reserveHealth['healthy'] ? 'text-green' : 'text-red' }}">${{ number_format($reserveHealth['total_deposits'] - $reserveHealth['minimum'], 2) }}</div>
                        <div class="text-xs text-muted">د.ع {{ number_format(($reserveHealth['total_deposits'] - $reserveHealth['minimum']) * $iqdRate, 0) }}</div>
                    </div>
                    <div class="text-2xl">{{ $reserveHealth['healthy'] ? '✅' : '⚠️' }}</div>
                </div>
            </div>
        </div>

        {{-- Loan Capacity --}}
        <h3 class="section-title mt-6 mb-4">💳 {{ __('Lending Capacity') }}</h3>
        <div class="card p-4" style="border-left:3px solid {{ $loanCapacity > 0 ? 'var(--success)' : 'var(--danger)' }};">
            <div class="text-muted text-xs mb-2">{{ __('Available to Lend') }}</div>
            <div class="font-semibold" style="font-size:28px;color:{{ $loanCapacity > 0 ? 'var(--success)' : 'var(--danger)' }};">
                ${{ number_format($loanCapacity, 2) }}
            </div>
            <div class="text-sm text-muted mt-1">د.ع {{ number_format($loanCapacity * $iqdRate, 0) }}</div>
            <div class="text-xs text-muted mt-3" style="border-top:1px solid var(--border);padding-top:8px;">
                {{ __('Formula') }}: {{ __('Total Deposits') }} (${{ number_format($reserveHealth['total_deposits'], 0) }})
                - {{ __('Reserve Minimum') }} (${{ number_format($reserveHealth['minimum'], 0) }})
                - {{ __('Outstanding Loans') }} (${{ number_format($financialOverview['totalLoanAmount'], 0) }})
            </div>
            @if($loanCapacity <= 0)
                <div class="text-xs mt-2" style="color:var(--danger);">⚠️ {{ __('No lending capacity available. Deposits must exceed reserve minimum + outstanding loans.') }}</div>
            @endif
        </div>

        {{-- Operations Summary --}}
        <h3 class="section-title mt-6 mb-3">📈 {{ __('Operations Summary') }}</h3>
        <div style="display:flex;flex-direction:column;gap:1px;background:var(--border);border-radius:var(--radius);overflow:hidden;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg-white);">
                <div class="text-sm text-muted">{{ __('Accounts') }}</div>
                <div><span class="font-semibold">{{ $financialOverview['activeAccounts'] }}</span><span class="text-xs text-muted"> active</span><span class="text-muted"> / </span><span class="font-semibold">{{ $financialOverview['totalAccounts'] }}</span><span class="text-xs text-muted"> total</span></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg-white);">
                <div class="text-sm text-muted">{{ __('Cards') }}</div>
                <div><span class="font-semibold">{{ $financialOverview['activeCards'] }}</span><span class="text-xs text-muted"> active</span><span class="text-muted"> · </span><span class="font-semibold">{{ $financialOverview['frozenCards'] }}</span><span class="text-xs text-muted"> frozen</span></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg-white);">
                <div class="text-sm text-muted">{{ __('Pending Transfers') }}</div>
                <div><span class="font-semibold">{{ $financialOverview['pendingTransfers'] }}</span><span class="text-xs text-muted"> · ${{ number_format($financialOverview['totalHeldFunds'], 2) }} held</span></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg-white);">
                <div class="text-sm text-muted">{{ __('Loans') }}</div>
                <div><span class="font-semibold">{{ $financialOverview['pendingLoans'] }}</span><span class="text-xs text-muted"> pending</span><span class="text-muted"> · </span><span class="font-semibold">{{ $financialOverview['totalLoans'] }}</span><span class="text-xs text-muted"> total</span></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg-white);">
                <div class="text-sm text-muted">{{ __('Total Users') }}</div>
                <div class="font-semibold">{{ $financialOverview['totalUsers'] }}</div>
            </div>
        </div>
    </div>

    {{-- Settings Form (Super Admin) or Read-Only View (Regular Admin) --}}
    <div class="card p-6 animate-fade-in-up delay-100">
        <h2 class="section-title mb-6">⚙️ {{ __('Configuration') }}</h2>

        @if($isSuperAdmin)
        {{-- Super Admin: Editable Form --}}
        <form method="POST" action="{{ route('admin.settings.update') }}" data-loading>
            @csrf @method('PUT')

            <h3 class="text-sm font-semibold text-muted mb-3" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Bank Identity') }}</h3>

            <div class="form-group">
                <label class="form-label">{{ __('Bank Name') }}</label>
                <input type="text" name="bank_name" class="form-input" value="{{ old('bank_name', $settings['bank_name']) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Support Email') }}</label>
                <input type="email" name="support_email" class="form-input" value="{{ old('support_email', $settings['support_email']) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Default Currency') }}</label>
                <input type="text" name="default_currency" class="form-input" value="{{ old('default_currency', $settings['default_currency']) }}" maxlength="3" required>
                <div class="form-hint">{{ __('ISO 4217 code (e.g. USD, IQD, EUR)') }}</div>
            </div>

            <h3 class="text-sm font-semibold text-muted mb-3 mt-4" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Reserve & Loans') }}</h3>

            <div class="form-group">
                <label class="form-label">{{ __('Loan Reserve Minimum ($)') }}</label>
                <input type="number" name="loan_reserve_minimum" class="form-input font-size-18 font-weight-600" value="{{ old('loan_reserve_minimum', $settings['loan_reserve_minimum']) }}" min="0" step="1000" required>
                <div class="form-hint">{{ __('Loan applications are auto-rejected when total deposits fall below this amount.') }}</div>
            </div>

            <h3 class="text-sm font-semibold text-muted mb-3 mt-4" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Transfer Limits') }}</h3>

            <div class="form-group">
                <label class="form-label">{{ __('Transfer Expiry (hours)') }}</label>
                <input type="number" name="transfer_expiry_hours" class="form-input" value="{{ old('transfer_expiry_hours', $settings['transfer_expiry_hours']) }}" min="1" max="168" required>
                <div class="form-hint">{{ __('How long a pending P2P transfer remains before auto-expiring. Max 168 hours (7 days).') }}</div>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Daily Transfer Limit ($)') }}</label>
                <input type="number" name="daily_transfer_limit" class="form-input" value="{{ old('daily_transfer_limit', $settings['daily_transfer_limit']) }}" min="0" step="100" required>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label class="form-label">{{ __('Min Amount ($)') }}</label>
                    <input type="number" name="min_transfer_amount" class="form-input" value="{{ old('min_transfer_amount', $settings['min_transfer_amount']) }}" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Max Amount ($)') }}</label>
                    <input type="number" name="max_transfer_amount" class="form-input" value="{{ old('max_transfer_amount', $settings['max_transfer_amount']) }}" min="0" step="100" required>
                </div>
            </div>

            <h3 class="text-sm font-semibold text-muted mb-3 mt-4" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Security') }}</h3>

            <div class="form-group">
                <label class="form-label">{{ __('Max PIN Attempts') }}</label>
                <input type="number" name="max_pin_attempts" class="form-input" value="{{ old('max_pin_attempts', $settings['max_pin_attempts']) }}" min="1" max="10" required>
                <div class="form-hint">{{ __('Number of wrong PIN attempts before a card is automatically frozen.') }}</div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-full mt-2">
                <span class="btn-text">{{ __('Save Settings') }}</span>
            </button>
        </form>

        @else
        {{-- Regular Admin: Read-Only View --}}
        <h3 class="text-sm font-semibold text-muted mb-3" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Bank Identity') }}</h3>

        <div class="card p-4 mb-3">
            <div class="text-muted text-xs mb-1">{{ __('Bank Name') }}</div>
            <div class="font-semibold">{{ $settings['bank_name'] }}</div>
        </div>
        <div class="card p-4 mb-3">
            <div class="text-muted text-xs mb-1">{{ __('Support Email') }}</div>
            <div class="font-semibold">{{ $settings['support_email'] }}</div>
        </div>
        <div class="card p-4 mb-3">
            <div class="text-muted text-xs mb-1">{{ __('Default Currency') }}</div>
            <div class="font-semibold">{{ $settings['default_currency'] }}</div>
        </div>

        <h3 class="text-sm font-semibold text-muted mb-3 mt-4" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Reserve & Loans') }}</h3>

        <div class="card p-4 mb-3">
            <div class="text-muted text-xs mb-1">{{ __('Loan Reserve Minimum') }}</div>
            <div class="font-semibold text-lg">${{ number_format($settings['loan_reserve_minimum'], 2) }}</div>
            <div class="text-xs text-muted">د.ع {{ number_format($settings['loan_reserve_minimum'] * $iqdRate, 0) }}</div>
        </div>

        <h3 class="text-sm font-semibold text-muted mb-3 mt-4" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Transfer Limits') }}</h3>

        <div class="card p-4 mb-3">
            <div class="text-muted text-xs mb-1">{{ __('Transfer Expiry') }}</div>
            <div class="font-semibold">{{ $settings['transfer_expiry_hours'] }} {{ __('hours') }}</div>
        </div>
        <div class="card p-4 mb-3">
            <div class="text-muted text-xs mb-1">{{ __('Daily Transfer Limit') }}</div>
            <div class="font-semibold">${{ number_format($settings['daily_transfer_limit'], 2) }}</div>
            <div class="text-xs text-muted">د.ع {{ number_format($settings['daily_transfer_limit'] * $iqdRate, 0) }}</div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;" class="mb-3">
            <div class="card p-4">
                <div class="text-muted text-xs mb-1">{{ __('Min Transfer') }}</div>
                <div class="font-semibold">${{ number_format($settings['min_transfer_amount'], 2) }}</div>
                <div class="text-xs text-muted">د.ع {{ number_format($settings['min_transfer_amount'] * $iqdRate, 0) }}</div>
            </div>
            <div class="card p-4">
                <div class="text-muted text-xs mb-1">{{ __('Max Transfer') }}</div>
                <div class="font-semibold">${{ number_format($settings['max_transfer_amount'], 2) }}</div>
                <div class="text-xs text-muted">د.ع {{ number_format($settings['max_transfer_amount'] * $iqdRate, 0) }}</div>
            </div>
        </div>

        <h3 class="text-sm font-semibold text-muted mb-3 mt-4" style="text-transform:uppercase;letter-spacing:0.5px;">{{ __('Security') }}</h3>

        <div class="card p-4 mb-3">
            <div class="text-muted text-xs mb-1">{{ __('Max PIN Attempts') }}</div>
            <div class="font-semibold">{{ $settings['max_pin_attempts'] }}</div>
            <div class="text-xs text-muted">{{ __('Card frozen after this many wrong attempts') }}</div>
        </div>

        <div class="card p-4 mt-4" style="border-left:3px solid var(--info);background:rgba(59,130,246,0.03);">
            <div class="text-sm text-muted">🔒 {{ __('To modify these settings, contact a Super Admin.') }}</div>
        </div>
        @endif
    </div>
</div>
@endsection
