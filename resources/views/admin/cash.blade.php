@extends('layouts.app')
@section('title', __('Branch Cash Management'))
@section('page-title', '🏦 ' . __('Branch Cash Management'))
@section('page-subtitle', __('Process physical cash deposits and withdrawals for users'))

@section('content')
{{-- User Search --}}
<div class="card p-6 mb-6 animate-fade-in-up">
    <h2 class="section-title mb-4">🔍 {{ __('Find Customer') }}</h2>
    <form method="GET" action="{{ route('admin.cash') }}" class="flex flex-gap-2 align-items-end">
        <div class="form-group mb-0 flex-1">
            <label class="form-label">{{ __('Search by name, email, account number, phone, or national ID') }}</label>
            <input type="text" name="search" class="form-input" placeholder="{{ __('e.g. Karwan, NXB4176795571, 0770...') }}" value="{{ request('search') }}">
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
        @if(request('search'))
            <a href="{{ route('admin.cash') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
        @endif
    </form>
</div>

{{-- Customer Info (if search results exist) --}}
@if(request('search') && $users->count() > 0)
<div class="card p-6 mb-6 animate-fade-in-up">
    <h3 class="section-title mb-4">👥 {{ __('Search Results') }} ({{ $users->total() }})</h3>
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Contact') }}</th>
                    <th>{{ __('Accounts') }}</th>
                    <th>{{ __('Branch') }}</th>
                    <th>{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        <div class="flex align-items-center flex-gap-2">
                            <div class="avatar-initials-small">{{ $user->initials }}</div>
                            <div>
                                <div class="font-semibold">{{ $user->name }}</div>
                                <div class="text-xs text-muted">ID: {{ $user->national_id ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm">{{ $user->email }}</div>
                        <div class="text-xs text-muted">{{ $user->phone ?? 'No phone' }}</div>
                    </td>
                    <td>
                        @foreach($user->accounts as $acc)
                            <div class="text-sm" style="margin-bottom: 4px;">
                                <span class="badge {{ $acc->status === 'active' ? 'badge-success' : 'badge-danger' }}">{{ ucfirst($acc->account_type) }}</span>
                                <span class="text-muted text-xs">{{ $acc->account_number }}</span>
                                <span class="font-semibold">{{ $acc->formatted_balance }}</span>
                            </div>
                        @endforeach
                    </td>
                    <td><span class="badge badge-info">{{ ucfirst($user->branch) }}</span></td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm" onclick="selectCustomer({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}')">
                            {{ __('Select') }}
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endif

@if(request('search') && $users->count() === 0)
<div class="card p-6 mb-6 animate-fade-in-up">
    <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <p class="empty-state-title">{{ __('No customers found') }}</p>
        <p class="empty-state-text">{{ __('Try a different search term.') }}</p>
    </div>
</div>
@endif

{{-- Deposit & Withdrawal Forms --}}
<div class="grid-2">
    {{-- Cash In (Deposit) --}}
    <div class="card p-6 animate-fade-in-up">
        <h2 class="section-title mb-4" style="color: var(--success);">⬇️ {{ __('Cash Deposit (Cash In)') }}</h2>
        <p class="text-sm text-muted mb-6">{{ __('User hands you physical cash. You deposit it into their digital account.') }}</p>

        <form method="POST" action="{{ route('admin.cash.deposit') }}" data-loading>
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('Customer') }}</label>
                <input type="text" id="deposit-customer-display" class="form-input" placeholder="{{ __('Search and select a customer above...') }}" readonly style="background:var(--bg-secondary);">
                <input type="hidden" name="user_id" id="deposit-user-id" required>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Account') }}</label>
                <select name="account_id" id="deposit-account-select" class="form-select" required disabled>
                    <option value="">{{ __('Select customer first...') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" id="deposit-amount-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" class="form-input" id="deposit-amount-input" min="1" step="0.01" required placeholder="e.g. 500">
                <p class="form-hint" id="deposit-amount-hint">{{ __('Select an account to see currency details.') }}</p>
            </div>

            <button type="submit" class="btn btn-success btn-lg w-full" onclick="return confirm('{{ __('Confirm cash deposit?') }}')">
                <span class="btn-text">{{ __('Process Deposit') }}</span>
            </button>
        </form>
    </div>

    {{-- Cash Out (Withdrawal) --}}
    <div class="card p-6 animate-fade-in-up delay-100">
        <h2 class="section-title mb-4" style="color: var(--danger);">⬆️ {{ __('Cash Withdrawal (Cash Out)') }}</h2>
        <p class="text-sm text-muted mb-6">{{ __('User requests physical cash. You deduct it from their digital account and hand them the cash.') }}</p>

        <form method="POST" action="{{ route('admin.cash.withdraw') }}" data-loading>
            @csrf
            <div class="form-group">
                <label class="form-label">{{ __('Customer') }}</label>
                <input type="text" id="withdraw-customer-display" class="form-input" placeholder="{{ __('Search and select a customer above...') }}" readonly style="background:var(--bg-secondary);">
                <input type="hidden" name="user_id" id="withdraw-user-id" required>
            </div>

            <div class="form-group">
                <label class="form-label">{{ __('Account') }}</label>
                <select name="account_id" id="withdraw-account-select" class="form-select" required disabled>
                    <option value="">{{ __('Select customer first...') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" id="withdraw-amount-label">{{ __('Amount') }}</label>
                <input type="number" name="amount" class="form-input" id="withdraw-amount-input" min="1" step="0.01" required placeholder="e.g. 200">
                <p class="form-hint" id="withdraw-amount-hint">{{ __('Select an account to see currency details.') }}</p>
            </div>

            <button type="submit" class="btn btn-danger btn-lg w-full" onclick="return confirm('{{ __('Confirm cash withdrawal?') }}')">
                <span class="btn-text">{{ __('Process Withdrawal') }}</span>
            </button>
        </form>
    </div>
</div>

<script>
    // Store accounts data for currency lookup
    let userAccounts = [];

    // Fetch user accounts via AJAX when customer is selected
    function selectCustomer(userId, name, email) {
        // Set both forms
        document.getElementById('deposit-user-id').value = userId;
        document.getElementById('deposit-customer-display').value = name + ' — ' + email;
        document.getElementById('withdraw-user-id').value = userId;
        document.getElementById('withdraw-customer-display').value = name + ' — ' + email;

        // Fetch accounts for this user
        fetch('/admin/cash/accounts?user_id=' + userId)
            .then(r => r.json())
            .then(accounts => {
                userAccounts = accounts;
                ['deposit-account-select', 'withdraw-account-select'].forEach(selectId => {
                    const select = document.getElementById(selectId);
                    select.innerHTML = '<option value="">{{ __("Select an account...") }}</option>';
                    if (accounts.length > 0) {
                        select.disabled = false;
                        accounts.forEach(acc => {
                            const opt = document.createElement('option');
                            opt.value = acc.id;
                            opt.dataset.currency = acc.currency;
                            opt.dataset.symbol = acc.symbol;
                            opt.dataset.decimals = acc.decimals;
                            opt.textContent = acc.text;
                            select.appendChild(opt);
                        });
                    } else {
                        select.disabled = true;
                        select.innerHTML = '<option value="">{{ __("No accounts found") }}</option>';
                    }
                });

                // Add change listeners to update amount input
                document.getElementById('deposit-account-select').addEventListener('change', function() {
                    updateCashAmount('deposit', this);
                });
                document.getElementById('withdraw-account-select').addEventListener('change', function() {
                    updateCashAmount('withdraw', this);
                });
            })
            .catch(() => {
                ['deposit-account-select', 'withdraw-account-select'].forEach(selectId => {
                    const select = document.getElementById(selectId);
                    select.disabled = true;
                    select.innerHTML = '<option value="">{{ __("Error loading accounts") }}</option>';
                });
            });

        // Scroll to forms
        document.querySelector('.grid-2').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Update amount input based on selected account currency
    function updateCashAmount(type, select) {
        const option = select.options[select.selectedIndex];
        const label = document.getElementById(type + '-amount-label');
        const hint = document.getElementById(type + '-amount-hint');
        const input = document.getElementById(type + '-amount-input');

        if (option.value) {
            const currency = option.dataset.currency;
            const symbol = option.dataset.symbol;
            const decimals = parseInt(option.dataset.decimals);

            label.textContent = '{{ __("Amount") }} (' + currency + ')';
            hint.textContent = '{{ __("Amount in") }} ' + currency + '. {{ __("Minimum") }}: ' + symbol + (currency === 'IQD' ? '1' : '0.01');

            input.step = decimals === 0 ? '1' : '0.01';
            input.min = decimals === 0 ? '1' : '0.01';
            input.placeholder = currency === 'IQD' ? 'e.g. 500000' : 'e.g. 500';
        } else {
            label.textContent = '{{ __("Amount") }}';
            hint.textContent = '{{ __("Select an account to see currency details.") }}';
            input.step = '0.01';
            input.min = '1';
        }
    }
</script>
@endsection
