@extends('layouts.app')
@section('title', __('Manage Users'))
@section('page-title', '👥 ' . __('User Management'))
@section('page-subtitle', __('View and manage customer accounts'))

@section('content')

{{-- Stats Summary --}}
<div class="grid-fit-160 mb-6 animate-fade-in-up">
    @php
        $totalUsers = $users->total();
        $activeCount = \App\Models\User::customers()->where('status','active')->count();
        $pendingCount = \App\Models\User::customers()->where('status','pending_verification')->count();
        $suspendedCount = \App\Models\User::customers()->whereIn('status',['suspended','frozen','inactive'])->count();
    @endphp
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-white">{{ $totalUsers }}</div>
        <div class="text-xs text-muted">{{ __('Total Users') }}</div>
    </div>
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-green">{{ $activeCount }}</div>
        <div class="text-xs text-muted">{{ __('Active') }}</div>
    </div>
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-orange">{{ $pendingCount }}</div>
        <div class="text-xs text-muted">{{ __('Pending KYC') }}</div>
    </div>
    <div class="card p-4 text-center">
        <div class="font-size-28 font-weight-800 text-red">{{ $suspendedCount }}</div>
        <div class="text-xs text-muted">{{ __('Suspended/Frozen') }}</div>
    </div>
</div>

{{-- Search & Filter --}}
<div class="card p-6 mb-6 animate-fade-in-up delay-50">
    <form method="GET" class="flex flex-gap-2 align-items-end flex-wrap">
        <div class="form-group mb-0 flex-1 min-width-200">
            <label class="form-label">{{ __('Search') }}</label>
            <input type="text" name="search" class="form-input" placeholder="{{ __('Name, email, or national ID...') }}" value="{{ request('search') }}">
        </div>
        <div class="form-group mb-0 min-width-160">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach(['active','inactive','suspended','frozen','pending_verification'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ __(ucfirst(str_replace('_',' ',$s))) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary h-42">🔍 {{ __('Search') }}</button>
        @if(request('search') || request('status'))
            <a href="{{ route('admin.users') }}" class="btn btn-ghost h-42">✕ {{ __('Clear') }}</a>
        @endif
    </form>
</div>

{{-- User Table --}}
<div class="card animate-fade-in-up delay-100">
    @if($users->count() > 0)
    <div class="data-table-wrapper">
        <table class="data-table data-table-full">
            <thead>
                <tr>
                    <th class="w-3/12">{{ __('User') }}</th>
                    <th>{{ __('Accounts') }}</th>
                    <th>{{ __('Total Balance') }}</th>
                    <th>{{ __('Loans') }}</th>
                    <th>{{ __('Cards') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Joined') }}</th>
                    <th class="text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="flex align-items-center flex-gap-2">
                                <div class="avatar-initials-normal">
                                    {{ $user->initials }}
                                </div>
                                <div>
                                    <div class="font-medium text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-muted">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-medium">{{ $user->accounts_count }}</span>
                        </td>
                        <td>
                            <span class="font-semibold text-cyan">${{ number_format($user->accounts->sum('balance'), 2) }}</span>
                        </td>
                        <td>
                            <span class="font-medium">{{ $user->loans_count }}</span>
                        </td>
                        <td>
                            <span class="font-medium">{{ $user->cards_count ?? 0 }}</span>
                        </td>
                        <td>
                            @php
                                $statusClass = match($user->status) {
                                    'active' => 'success',
                                    'pending_verification' => 'warning',
                                    'suspended' => 'danger',
                                    'frozen' => 'danger',
                                    default => 'neutral',
                                };
                                $statusIcon = match($user->status) {
                                    'active' => '✅',
                                    'pending_verification' => '⏳',
                                    'suspended' => '🚫',
                                    'frozen' => '❄️',
                                    'inactive' => '💤',
                                    default => '❓',
                                };
                            @endphp
                            <span class="badge badge-{{ $statusClass }}">
                                {{ $statusIcon }} {{ __(ucfirst(str_replace('_',' ',$user->status))) }}
                            </span>
                        </td>
                        <td class="text-sm text-muted">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="text-right">
                            <div class="flex flex-gap-1 justify-end">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary btn-sm">👁️ {{ __('View') }}</a>
                                @if($user->status === 'active')
                                    <form method="POST" action="{{ route('admin.users.update-status', $user) }}" onsubmit="return confirm('{{ __('Suspend this user?') }}')">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="suspended">
                                        <button class="btn btn-danger btn-sm">🚫</button>
                                    </form>
                                @elseif($user->status === 'suspended' || $user->status === 'frozen')
                                    <form method="POST" action="{{ route('admin.users.update-status', $user) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="status" value="active">
                                        <button class="btn btn-success btn-sm">✅</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="padding-all-20 border-top-divider">{{ $users->withQueryString()->links() }}</div>
    @endif
    @else
        <div class="empty-state empty-state-padding">
            <div class="empty-state-icon">👥</div>
            <p class="empty-state-title">{{ __('No users found') }}</p>
            <p class="empty-state-text">{{ __('Try adjusting your search criteria.') }}</p>
        </div>
    @endif
</div>
@endsection
