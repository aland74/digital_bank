@extends('layouts.app')
@section('title', __('Branch Cash Management'))
@section('page-title', '🏦 ' . __('Branch Cash Management'))
@section('page-subtitle', __('Teller operations — deposits & withdrawals'))

@section('content')

{{-- Stats Row --}}
<div class="stats-grid mb-6 animate-fade-in-up">
    <div class="stat-card" style="border-left: 3px solid #22c55e;">
        <div class="stat-icon green">💵</div>
        <div class="stat-value">${{ number_format($stats['deposits_usd'], 2) }}</div>
        <div class="stat-label">{{ __('USD Deposits Today') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #22c55e;">
        <div class="stat-icon green">💰</div>
        <div class="stat-value">د.ع{{ number_format($stats['deposits_iqd'], 0) }}</div>
        <div class="stat-label">{{ __('IQD Deposits Today') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon red">💵</div>
        <div class="stat-value">${{ number_format($stats['withdrawals_usd'], 2) }}</div>
        <div class="stat-label">{{ __('USD Withdrawals Today') }}</div>
    </div>
    <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon red">💰</div>
        <div class="stat-value">د.ع{{ number_format($stats['withdrawals_iqd'], 0) }}</div>
        <div class="stat-label">{{ __('IQD Withdrawals Today') }}</div>
    </div>
</div>

<div class="grid-2" style="gap: 24px; align-items: start;">

    {{-- LEFT: Customer Search & Selection --}}
    <div>
        {{-- Search --}}
        <div class="card p-6 mb-4 animate-fade-in-up">
            <h2 class="section-title mb-4">🔍 {{ __('Find Customer') }}</h2>
            <form method="GET" action="{{ route('admin.cash') }}" id="search-form">
                <div class="form-group mb-0">
                    <input type="text" name="search" class="form-input" placeholder="{{ __('Name, email, phone, account number...') }}" value="{{ request('search') }}" autofocus>
                </div>
                <div class="flex flex-gap-2 mt-3">
                    <button type="submit" class="btn btn-primary flex-1">{{ __('Search') }}</button>
                    @if(request('search'))
                        <a href="{{ route('admin.cash') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Customer List --}}
        @if($users->count() > 0)
            <div class="card p-4 animate-fade-in-up" style="max-height: 500px; overflow-y: auto;">
                <div class="text-xs text-muted mb-3">{{ $users->total() }} {{ __('customers found') }}</div>
                @foreach($users as $user)
                    <div class="customer-row" onclick="selectCustomer({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->phone ?? '' }}')" style="padding: 12px; border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s; margin-bottom: 4px; border: 1px solid transparent;" id="customer-{{ $user->id }}">
                        <div class="flex align-items-center flex-gap-3">
                            <div class="avatar-initials">{{ $user->initials }}</div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="font-semibold text-sm">{{ $user->name }}</div>
                                <div class="text-xs text-muted">{{ $user->email }}</div>
                            </div>
                            <div style="text-align: right;">
                                @foreach($user->accounts as $acc)
                                    @php
                                        $cur = \App\Models\Currency::where('code', $acc->currency)->first();
                                        $sym = $cur?->symbol ?? $acc->currency;
                                        $dec = $cur?->decimal_places ?? 2;
                                    @endphp
                                    <div class="text-xs" style="color: {{ $acc->currency === 'USD' ? '#3b82f6' : '#f59e0b' }};">
                                        {{ $sym }}{{ number_format($acc->balance, $dec) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="mt-3">{{ $users->links() }}</div>
            </div>
        @elseif(request('search'))
            <div class="card p-6 animate-fade-in-up">
                <div class="empty-state">
                    <div class="empty-state-icon">🔍</div>
                    <p class="empty-state-title">{{ __('No customers found') }}</p>
                    <p class="empty-state-text">{{ __('Try a different search term.') }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- RIGHT: Selected Customer & Transaction Forms --}}
    <div>
        {{-- Selected Customer Info --}}
        <div class="card p-6 mb-4 animate-fade-in-up" id="selected-customer-card" style="display: none;">
            <div class="flex-between mb-4">
                <h2 class="section-title">👤 {{ __('Selected Customer') }}</h2>
                <button type="button" class="btn btn-ghost btn-sm" onclick="clearSelection()">{{ __('✕ Clear') }}</button>
            </div>
            <div class="flex align-items-center flex-gap-3 mb-4">
                <div class="avatar-initials" id="customer-avatar" style="width: 48px; height: 48px; font-size: 18px;"></div>
                <div>
                    <div class="font-bold" id="customer-name"></div>
                    <div class="text-sm text-muted" id="customer-email"></div>
                    <div class="text-xs text-muted" id="customer-phone"></div>
                </div>
            </div>

            {{-- Accounts --}}
            <div id="customer-accounts" style="margin-bottom: 16px;"></div>

            {{-- Action Tabs --}}
            <div class="filter-tabs mb-4">
                <button type="button" class="filter-tab active" id="tab-deposit" onclick="switchTab('deposit')">⬇️ {{ __('Deposit') }}</button>
                <button type="button" class="filter-tab" id="tab-withdraw" onclick="switchTab('withdraw')">⬆️ {{ __('Withdraw') }}</button>
            </div>

            {{-- Deposit Form --}}
            <form method="POST" action="{{ route('admin.cash.deposit') }}" id="form-deposit" data-loading>
                @csrf
                <input type="hidden" name="user_id" id="deposit-user-id">
                <div class="form-group">
                    <label class="form-label">{{ __('To Account') }}</label>
                    <select name="account_id" id="deposit-account" class="form-select" required disabled>
                        <option value="">{{ __('Select customer first...') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" id="deposit-amount-label">{{ __('Amount') }}</label>
                    <input type="number" name="amount" class="form-input" id="deposit-amount" min="1" step="0.01" required placeholder="0.00" style="font-size: 24px; font-weight: 700; text-align: center;">
                    <div class="form-hint" id="deposit-hint"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Note (optional)') }}</label>
                    <input type="text" name="description" class="form-input" placeholder="{{ __('e.g. Cash deposit from customer') }}">
                </div>
                <button type="submit" class="btn btn-success btn-lg w-full" id="deposit-btn" disabled>
                    <span class="btn-text">⬇️ {{ __('Process Deposit') }}</span>
                </button>
            </form>

            {{-- Withdraw Form --}}
            <form method="POST" action="{{ route('admin.cash.withdraw') }}" id="form-withdraw" style="display: none;" data-loading>
                @csrf
                <input type="hidden" name="user_id" id="withdraw-user-id">
                <div class="form-group">
                    <label class="form-label">{{ __('From Account') }}</label>
                    <select name="account_id" id="withdraw-account" class="form-select" required disabled>
                        <option value="">{{ __('Select customer first...') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" id="withdraw-amount-label">{{ __('Amount') }}</label>
                    <input type="number" name="amount" class="form-input" id="withdraw-amount" min="1" step="0.01" required placeholder="0.00" style="font-size: 24px; font-weight: 700; text-align: center;">
                    <div class="form-hint" id="withdraw-hint"></div>
                    <div class="form-error" id="withdraw-error" style="display: none;"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Note (optional)') }}</label>
                    <input type="text" name="description" class="form-input" placeholder="{{ __('e.g. Cash withdrawal at branch') }}">
                </div>
                <button type="submit" class="btn btn-danger btn-lg w-full" id="withdraw-btn" disabled>
                    <span class="btn-text">⬆️ {{ __('Process Withdrawal') }}</span>
                </button>
            </form>
        </div>

        {{-- Placeholder when no customer selected --}}
        <div class="card p-6 animate-fade-in-up" id="no-customer-card">
            <div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 16px;">👈</div>
                <p class="empty-state-title">{{ __('Select a Customer') }}</p>
                <p class="empty-state-text">{{ __('Search and click a customer to start a transaction.') }}</p>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="card p-4 animate-fade-in-up mt-4">
            <h3 class="section-title mb-3">📋 {{ __('Recent Branch Transactions') }}</h3>
            @if($recentTransactions->count() > 0)
                <div style="max-height: 300px; overflow-y: auto;">
                    @foreach($recentTransactions as $txn)
                        <div class="flex-between" style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                            <div class="flex align-items-center flex-gap-2">
                                <span style="font-size: 16px;">{{ $txn->type === 'deposit' ? '⬇️' : '⬆️' }}</span>
                                <div>
                                    <div class="text-sm font-semibold">{{ $txn->account->user->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-muted">{{ $txn->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                @php
                                    $cur = \App\Models\Currency::where('code', $txn->currency)->first();
                                    $sym = $cur?->symbol ?? $txn->currency;
                                    $dec = $cur?->decimal_places ?? 2;
                                @endphp
                                <div class="font-semibold" style="color: {{ $txn->type === 'deposit' ? 'var(--success)' : 'var(--danger)' }};">
                                    {{ $txn->type === 'deposit' ? '+' : '-' }}{{ $sym }}{{ number_format($txn->amount, $dec) }}
                                </div>
                                <div class="text-xs text-muted">{{ $txn->currency }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-muted text-center" style="padding: 16px;">{{ __('No transactions today.') }}</div>
            @endif
        </div>
    </div>
</div>

<style>
    .customer-row:hover {
        background: var(--bg-secondary) !important;
        border-color: var(--border) !important;
    }
    .customer-row.selected {
        background: rgba(var(--primary-rgb), 0.08) !important;
        border-color: var(--primary) !important;
    }
</style>

<script>
let currentAccounts = [];
let currentTab = 'deposit';

function selectCustomer(userId, name, email, phone) {
    // Highlight selected row
    document.querySelectorAll('.customer-row').forEach(r => r.classList.remove('selected'));
    const row = document.getElementById('customer-' + userId);
    if (row) row.classList.add('selected');

    // Show customer card, hide placeholder
    document.getElementById('selected-customer-card').style.display = 'block';
    document.getElementById('no-customer-card').style.display = 'none';

    // Fill customer info
    document.getElementById('customer-avatar').textContent = name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    document.getElementById('customer-name').textContent = name;
    document.getElementById('customer-email').textContent = email;
    document.getElementById('customer-phone').textContent = phone ? '📞 ' + phone : '';

    // Set user IDs
    document.getElementById('deposit-user-id').value = userId;
    document.getElementById('withdraw-user-id').value = userId;

    // Fetch accounts
    fetch('/admin/cash/accounts?user_id=' + userId)
        .then(r => r.json())
        .then(accounts => {
            currentAccounts = accounts;

            // Render account cards
            let accountsHtml = '';
            accounts.forEach(acc => {
                accountsHtml += `
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: var(--bg-secondary); border-radius: var(--radius-md); margin-bottom: 8px; border-left: 3px solid ${acc.currency === 'USD' ? '#3b82f6' : '#f59e0b'};">
                        <div>
                            <div class="text-xs text-muted">${acc.currency === 'USD' ? '🇺🇸' : '🇮🇶'} ${acc.currency} Account</div>
                            <div class="text-xs text-muted" style="font-family: monospace;">${acc.text.split('—')[1]?.split('(')[0]?.trim() || ''}</div>
                        </div>
                        <div class="font-bold" style="color: ${acc.currency === 'USD' ? '#3b82f6' : '#f59e0b'};">
                            ${acc.symbol}${Number(acc.text.match(/\((.+)\)/)?.[1]?.replace(/[^0-9.]/g, '') || 0).toLocaleString(undefined, {minimumFractionDigits: acc.decimals, maximumFractionDigits: acc.decimals})}
                        </div>
                    </div>
                `;
            });
            document.getElementById('customer-accounts').innerHTML = accountsHtml;

            // Populate selects
            ['deposit-account', 'withdraw-account'].forEach(selectId => {
                const select = document.getElementById(selectId);
                select.innerHTML = '<option value="">{{ __("Select account...") }}</option>';
                accounts.forEach(acc => {
                    const opt = document.createElement('option');
                    opt.value = acc.id;
                    opt.dataset.currency = acc.currency;
                    opt.dataset.symbol = acc.symbol;
                    opt.dataset.decimals = acc.decimals;
                    opt.dataset.balance = acc.text.match(/\((.+)\)/)?.[1]?.replace(/[^0-9.]/g, '') || '0';
                    opt.textContent = acc.text;
                    select.appendChild(opt);
                });
                select.disabled = false;

                // Add change listener
                select.onchange = function() { updateAmount(selectId.replace('-account', '')); };
            });

            // Enable buttons
            document.getElementById('deposit-btn').disabled = false;
            document.getElementById('withdraw-btn').disabled = false;
        })
        .catch(err => {
            console.error('Error fetching accounts:', err);
        });

    // Scroll to card
    document.getElementById('selected-customer-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function clearSelection() {
    document.querySelectorAll('.customer-row').forEach(r => r.classList.remove('selected'));
    document.getElementById('selected-customer-card').style.display = 'none';
    document.getElementById('no-customer-card').style.display = 'block';
}

function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tab-deposit').classList.toggle('active', tab === 'deposit');
    document.getElementById('tab-withdraw').classList.toggle('active', tab === 'withdraw');
    document.getElementById('form-deposit').style.display = tab === 'deposit' ? 'block' : 'none';
    document.getElementById('form-withdraw').style.display = tab === 'withdraw' ? 'block' : 'none';
}

function updateAmount(type) {
    const select = document.getElementById(type + '-account');
    const option = select.options[select.selectedIndex];
    const label = document.getElementById(type + '-amount-label');
    const hint = document.getElementById(type + '-hint');
    const input = document.getElementById(type + '-amount');
    const error = document.getElementById(type + '-error');

    if (option.value) {
        const currency = option.dataset.currency;
        const symbol = option.dataset.symbol;
        const decimals = parseInt(option.dataset.decimals);
        const balance = parseFloat(option.dataset.balance) || 0;

        label.textContent = '{{ __("Amount") }} (' + currency + ')';
        input.step = decimals === 0 ? '1' : '0.01';
        input.min = decimals === 0 ? '1' : '0.01';
        input.placeholder = decimals === 0 ? '0' : '0.00';

        if (type === 'withdraw') {
            hint.textContent = '{{ __("Available balance") }}: ' + symbol + balance.toLocaleString(undefined, {minimumFractionDigits: decimals, maximumFractionDigits: decimals});
            input.max = balance;
            input.oninput = function() {
                if (parseFloat(this.value) > balance) {
                    error.textContent = '{{ __("Amount exceeds available balance!") }}';
                    error.style.display = 'block';
                    document.getElementById('withdraw-btn').disabled = true;
                } else {
                    error.style.display = 'none';
                    document.getElementById('withdraw-btn').disabled = false;
                }
            };
        } else {
            hint.textContent = '{{ __("Enter amount in") }} ' + currency;
        }
    } else {
        label.textContent = '{{ __("Amount") }}';
        hint.textContent = '';
        input.step = '0.01';
        input.min = '1';
        input.placeholder = '0.00';
        if (error) error.style.display = 'none';
    }
}

// Auto-select if only one customer in search results
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.customer-row');
    if (rows.length === 1) {
        rows[0].click();
    }
});
</script>
@endsection
