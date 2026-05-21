@extends('layouts.app')
@section('title', 'User Detail')
@section('page-title', $user->name)
@section('page-subtitle', 'Customer #' . $user->id . ' · Member since ' . $user->created_at->format('M Y'))

@section('content')
<div class="mb-4 animate-fade-in-up">
    <a href="{{ route('admin.users') }}" class="btn btn-ghost btn-sm">← {{ __('Back to Users') }}</a>
</div>

{{-- Top Profile Card --}}
<div class="card p-6 mb-6 animate-fade-in-up">
    <div class="flex-between align-items-start flex-wrap flex-gap-2">
        <div class="flex align-items-center flex-gap-2">
            <div class="avatar-initials-large">{{ $user->initials }}</div>
            <div>
                <h2 class="font-size-22 font-weight-800 text-white m-0">{{ $user->name }}</h2>
                <div class="text-muted mt-1">{{ $user->email }}</div>
                <div class="flex flex-gap-2 mt-2">
                    @php $sc = match($user->status) { 'active'=>'success','pending_verification'=>'warning',default=>'danger' }; @endphp
                    <span class="badge badge-{{ $sc }} badge-pad-large">{{ ucfirst(str_replace('_',' ',$user->status)) }}</span>
                    <span class="badge badge-{{ $user->isKycVerified() ? 'success' : 'warning' }} badge-pad-large">{{ $user->isKycVerified() ? '✅ KYC Verified' : '⏳ KYC Pending' }}</span>
                </div>
            </div>
        </div>
        <div class="w-72 max-w-full">
            <div class="text-xs text-muted mb-2 font-weight-600">{{ __('CHANGE STATUS') }}</div>
            <form method="POST" action="{{ route('admin.users.update-status', $user) }}" class="flex flex-gap-2">
                @csrf @method('PUT')
                <select name="status" class="form-select flex-1 font-size-13">
                    @foreach(['active','inactive','suspended','frozen','pending_verification'] as $s)
                        <option value="{{ $s }}" {{ $user->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm">{{ __('Update') }}</button>
            </form>
        </div>
    </div>
    <div class="profile-info-grid">
        <div class="card p-4"><div class="text-muted text-xs">📱 {{ __('Phone') }}</div><div class="font-medium mt-1">{{ $user->phone ?: '—' }}</div></div>
        <div class="card p-4"><div class="text-muted text-xs">🪪 {{ __('National ID') }}</div><div class="font-medium mt-1">{{ $user->national_id ?: '—' }}</div></div>
        <div class="card p-4"><div class="text-muted text-xs">🕐 {{ __('Last Login') }}</div><div class="font-medium mt-1">{{ $user->last_login_at?->diffForHumans() ?? __('Never') }}</div></div>
        <div class="card p-4"><div class="text-muted text-xs">📅 {{ __('Joined') }}</div><div class="font-medium mt-1">{{ $user->created_at->format('M d, Y') }}</div></div>
    </div>
</div>

{{-- Financial Summary --}}
<div class="grid-fit-160 mb-6 animate-fade-in-up delay-50">
    <div class="card p-4 text-center"><div class="font-size-22 font-weight-800 text-cyan">${{ number_format($user->accounts->sum('balance'), 2) }}</div><div class="text-xs text-muted">{{ __('Total Balance') }}</div></div>
    <div class="card p-4 text-center"><div class="font-size-22 font-weight-800 text-white">{{ $user->accounts->count() }}</div><div class="text-xs text-muted">{{ __('Accounts') }}</div></div>
    <div class="card p-4 text-center"><div class="font-size-22 font-weight-800 text-white">{{ $user->cards->count() }}</div><div class="text-xs text-muted">{{ __('Cards') }}</div></div>
    <div class="card p-4 text-center"><div class="font-size-22 font-weight-800 text-purple">{{ $user->loans->count() }}</div><div class="text-xs text-muted">{{ __('Loans') }}</div></div>
    <div class="card p-4 text-center"><div class="font-size-22 font-weight-800 text-orange">${{ number_format($user->loans->whereIn('status',['approved'])->sum('remaining_balance'), 2) }}</div><div class="text-xs text-muted">{{ __('Loan Outstanding') }}</div></div>
</div>

<div class="grid-2 align-items-start">
    <div>
        {{-- Accounts --}}
        <div class="card p-6 mb-6 animate-fade-in-up delay-100">
            <h3 class="section-title mb-4">🏦 {{ __('Accounts') }} ({{ $user->accounts->count() }})</h3>
            @forelse($user->accounts as $acc)
                <div class="padding-y-12 border-bottom-divider">
                    <div class="flex-between">
                        <div><div class="font-medium text-white">{{ $acc->account_number }}</div><div class="text-xs text-muted">{{ ucfirst($acc->account_type) }} · {{ $acc->currency }}</div></div>
                        <div class="text-right"><div class="font-semibold text-cyan">${{ number_format($acc->balance, 2) }}</div><div class="text-xs text-muted">{{ __('Avail') }}: ${{ number_format($acc->available_balance, 2) }}</div></div>
                    </div>
                </div>
            @empty
                <p class="text-muted text-sm">{{ __('No accounts.') }}</p>
            @endforelse
        </div>

        {{-- Cards --}}
        <div class="card p-6 mb-6 animate-fade-in-up delay-200">
            <h3 class="section-title mb-4">💳 {{ __('Cards') }} ({{ $user->cards->count() }})</h3>
            @forelse($user->cards as $card)
                <div class="padding-y-12 border-bottom-divider">
                    <div class="flex-between">
                        <div><div class="font-medium text-white">**** {{ $card->card_number_last4 }}</div><div class="text-xs text-muted">{{ ucfirst($card->card_brand) }} · {{ ucfirst($card->card_type) }} · Exp {{ $card->expiry_month }}/{{ $card->expiry_year }}</div></div>
                        <span class="badge badge-{{ $card->status === 'active' ? 'success' : ($card->status === 'frozen' ? 'danger' : 'warning') }}">{{ ucfirst($card->status) }}</span>
                    </div>
                </div>
            @empty
                <p class="text-muted text-sm">{{ __('No cards.') }}</p>
            @endforelse
        </div>

        {{-- KYC --}}
        <div class="card p-6 animate-fade-in-up delay-300">
            <h3 class="section-title mb-4">🪪 {{ __('KYC Documents') }} ({{ $user->kycDocuments->count() }})</h3>
            @forelse($user->kycDocuments as $doc)
                <div class="padding-y-12 border-bottom-divider">
                    <div class="flex-between">
                        <div>
                            <div class="font-medium text-white">{{ $doc->document_type_label }}</div>
                            <div class="text-xs text-muted">{{ __('Doc #') }}: {{ $doc->document_number ?: '—' }} · {{ $doc->created_at->diffForHumans() }}</div>
                            @if($doc->rejection_reason)<div class="text-xs text-red mt-1">Reason: {{ $doc->rejection_reason }}</div>@endif
                        </div>
                        <span class="badge badge-{{ $doc->status === 'verified' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($doc->status) }}</span>
                    </div>
                    @if($doc->file_path)<a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="text-xs text-cyan mt-1 inline-block">📄 {{ __('View Document →') }}</a>@endif
                </div>
            @empty
                <p class="text-muted text-sm">{{ __('No KYC documents.') }}</p>
            @endforelse
        </div>
    </div>

    <div>
        {{-- Loans --}}
        <div class="card p-6 mb-6 animate-fade-in-up delay-100">
            <h3 class="section-title mb-4">📈 {{ __('Loans') }} ({{ $user->loans->count() }})</h3>
            @forelse($user->loans as $loan)
                <div class="padding-y-12 border-bottom-divider">
                    <div class="flex-between mb-2">
                        <div><div class="font-medium text-white">{{ $loan->loan_number }}</div><div class="text-xs text-muted">{{ ucfirst($loan->loan_type) }} · {{ $loan->term_months }}mo · {{ $loan->interest_rate }}%</div></div>
                        <span class="badge badge-{{ $loan->status === 'approved' ? 'success' : ($loan->status === 'pending' ? 'warning' : ($loan->status === 'completed' ? 'info' : 'danger')) }}">{{ ucfirst($loan->status) }}</span>
                    </div>
                    <div class="grid-3 flex-gap-2">
                        <div><div class="text-xs text-muted">{{ __('Amount') }}</div><div class="text-sm font-semibold text-white">${{ number_format($loan->amount, 2) }}</div></div>
                        <div><div class="text-xs text-muted">{{ __('Paid') }}</div><div class="text-sm font-semibold text-green">${{ number_format($loan->total_paid, 2) }}</div></div>
                        <div><div class="text-xs text-muted">{{ __('Remaining') }}</div><div class="text-sm font-semibold text-cyan">${{ number_format($loan->remaining_balance, 2) }}</div></div>
                    </div>
                </div>
            @empty
                <p class="text-muted text-sm">{{ __('No loans.') }}</p>
            @endforelse
        </div>

        {{-- Transactions --}}
        <div class="card animate-fade-in-up delay-200">
            <div class="section-header p-6 mb-0"><h3 class="section-title">📋 {{ __('Recent Transactions') }}</h3></div>
            @forelse($recentTransactions as $txn)
                <div class="transaction-item">
                    <div class="transaction-icon {{ $txn->isCredit() ? 'credit' : 'debit' }}">{{ $txn->isCredit() ? '↓' : '↑' }}</div>
                    <div class="transaction-details">
                        <div class="transaction-title">{{ $txn->description ?: ucfirst(str_replace('_', ' ', $txn->type)) }}</div>
                        <div class="transaction-meta">{{ $txn->reference_number }} · {{ $txn->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="transaction-amount {{ $txn->isCredit() ? 'credit' : 'debit' }}">{{ $txn->isCredit() ? '+' : '-' }}${{ number_format($txn->amount, 2) }}</div>
                </div>
            @empty
                <div class="text-center empty-state-padding"><div class="text-muted text-sm">{{ __('No transactions.') }}</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
