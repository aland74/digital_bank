<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ckb' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — NexusBank</title>
    <meta name="description" content="NexusBank — Your Secure Digital Banking Platform">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else if (savedTheme === 'light') {
                document.documentElement.removeAttribute('data-theme');
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar" role="navigation" aria-label="Main navigation">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">N</div>
            <span class="sidebar-brand-text">NexusBank</span>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-title">{{ __('Main') }}</div>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">📊</span> {{ __('Dashboard') }}
                </a>
                <a href="{{ route('transactions.index') }}" class="sidebar-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">📋</span> {{ __('Transactions') }}
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">{{ __('Payments') }}</div>
                <a href="{{ route('transfers.create') }}" class="sidebar-link {{ request()->routeIs('transfers.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">💸</span> {{ __('Transfer Money') }}
                </a>
                <a href="{{ route('beneficiaries.index') }}" class="sidebar-link {{ request()->routeIs('beneficiaries.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">👥</span> {{ __('Beneficiaries') }}
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">{{ __('Services') }}</div>
                <a href="{{ route('cards.index') }}" class="sidebar-link {{ request()->routeIs('cards.*') || request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">💳</span> {{ __('Cards & Accounts') }}
                </a>
                <a href="{{ route('loans.index') }}" class="sidebar-link {{ request()->routeIs('loans.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">📈</span> {{ __('Loans') }}
                </a>
                <a href="{{ route('cash.index') }}" class="sidebar-link {{ request()->routeIs('cash.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🏧</span> {{ __('ATM / Cash') }}
                </a>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-title">{{ __('Account') }}</div>
                <a href="{{ route('notifications.index') }}" class="sidebar-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🔔</span> {{ __('Notifications') }}
                    @if(auth()->user()->unreadNotificationsCount() > 0)
                        <span class="badge-count">{{ auth()->user()->unreadNotificationsCount() }}</span>
                    @endif
                </a>
                <a href="{{ route('support.index') }}" class="sidebar-link {{ request()->routeIs('support.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🎫</span> {{ __('Support') }}
                    @if(($openTickets = auth()->user()->supportTickets()->whereIn('status', ['open', 'in_progress', 'awaiting_response'])->count()) > 0)
                        <span class="badge-count">{{ $openTickets }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.edit') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">👤</span> {{ __('User Account') }}
                </a>
            </div>

            @if(auth()->check() && auth()->user()->isAdmin())
            <div class="sidebar-section">
                <div class="sidebar-section-title" style="color: #a78bfa;">{{ __('ADMIN PANEL') }}</div>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">👑</span> {{ __('Overview') }}
                </a>
                <a href="{{ route('admin.users') }}" class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">👨‍💻</span> {{ __('Manage Users') }}
                </a>
                <a href="{{ route('admin.cash') }}" class="sidebar-link {{ request()->routeIs('admin.cash*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🏦</span> {{ __('Branch Cash') }}
                </a>
                <a href="{{ route('admin.loans') }}" class="sidebar-link {{ request()->routeIs('admin.loans*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">💼</span> {{ __('Pending Loans') }}
                    @if(($pendingLoans = \App\Models\Loan::pending()->count()) > 0)
                        <span class="badge-count">{{ $pendingLoans }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.kyc') }}" class="sidebar-link {{ request()->routeIs('admin.kyc*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">📄</span> {{ __('KYC Documents') }}
                    @if(($pendingKyc = \App\Models\KycDocument::pending()->count()) > 0)
                        <span class="badge-count">{{ $pendingKyc }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.settings') }}" class="sidebar-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🔧</span> {{ __('Bank Settings') }}
                </a>
                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.audit-logs') }}" class="sidebar-link {{ request()->routeIs('admin.audit-logs*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🔍</span> {{ __('Audit Logs') }}
                </a>
                @endif
                <a href="{{ route('admin.pin-requests') }}" class="sidebar-link {{ request()->routeIs('admin.pin-requests*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🔐</span> {{ __('PIN Requests') }}
                    @if(($pendingPinRequests = \App\Models\PinChangeRequest::pending()->count()) > 0)
                        <span class="badge-count">{{ $pendingPinRequests }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.support.index') }}" class="sidebar-link {{ request()->routeIs('admin.support*') ? 'active' : '' }}">
                    <span class="sidebar-link-icon">🎫</span> {{ __('Support Tickets') }}
                    @if(($openAdminTickets = \App\Models\SupportTicket::open()->count()) > 0)
                        <span class="badge-count">{{ $openAdminTickets }}</span>
                    @endif
                </a>
            </div>
            @endif
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('{{ __('Are you sure you want to sign out?') }}');">
                @csrf
                <button type="submit" class="sidebar-link w-full" style="border:none;background:none;cursor:pointer;text-align:{{ app()->getLocale() === 'ckb' ? 'right' : 'left' }};">
                    <span class="sidebar-link-icon">🚪</span> {{ __('Sign Out') }}
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button id="mobile-menu-btn" class="mobile-menu-btn" aria-label="Toggle menu">☰</button>
                <div>
                    <h1 class="topbar-title">@yield('page-title', 'Dashboard')</h1>
                    <p class="topbar-subtitle">@yield('page-subtitle', date('l, F j, Y'))</p>
                </div>
            </div>
            <div class="topbar-right">
                {{-- Language Switcher --}}
                <div class="lang-switcher">
                    <a href="{{ route('lang.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                    <a href="{{ route('lang.switch', 'ckb') }}" class="lang-btn {{ app()->getLocale() === 'ckb' ? 'active' : '' }}">CKB</a>
                </div>

                {{-- Theme Switcher --}}
                <button id="theme-toggle" class="topbar-btn" aria-label="Toggle Theme">
                    <span class="theme-icon-dark">☀️</span>
                    <span class="theme-icon-light" style="display:none;">🌙</span>
                </button>

                {{-- Notification Bell --}}
                <div style="position:relative;">
                    <button id="notification-bell-btn" class="topbar-btn" aria-label="Notifications">
                        🔔
                        @if(auth()->user()->unreadNotificationsCount() > 0)
                            <span class="badge-dot"></span>
                        @endif
                    </button>
                    <div id="notification-dropdown" class="notification-dropdown" role="menu">
                        <div class="notification-dropdown-header">
                            <h3>{{ __('Notifications') }}</h3>
                            @if(auth()->user()->unreadNotificationsCount() > 0)
                                <form method="POST" action="{{ route('notifications.mark-all-read') }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm" style="padding:4px 8px;font-size:11px;">{{ __('Mark all read') }}</button>
                                </form>
                            @endif
                        </div>
                        <div class="notification-list">
                            @php
                                $recentNotifs = auth()->user()->notifications()->orderBy('created_at', 'desc')->limit(5)->get();
                            @endphp
                            @forelse($recentNotifs as $notif)
                                <a href="{{ route('notifications.read', $notif) }}" class="notification-item {{ !$notif->is_read ? 'unread' : '' }}">
                                    <div class="notif-icon">
                                        {{ $notif->type === 'transfer_request' ? '📨' : ($notif->type === 'transaction' ? '💸' : ($notif->type === 'security' ? '🔐' : ($notif->type === 'success' ? '✅' : '🔔'))) }}
                                    </div>
                                    <div class="notif-content">
                                        <div class="notif-title">{{ $notif->translated_title }}</div>
                                        <div class="notif-message">{{ Str::limit($notif->translated_message, 60) }}</div>
                                        <div class="notif-time">{{ $notif->created_at->diffForHumans() }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="empty-state" style="padding:40px 20px;">
                                    <div style="font-size:28px;margin-bottom:8px;opacity:0.5;">🔔</div>
                                    <div class="text-muted text-sm">{{ __('No notifications') }}</div>
                                </div>
                            @endforelse
                        </div>
                        <div style="padding:12px 20px;border-top:1px solid var(--border);text-align:center;">
                            <a href="{{ route('notifications.index') }}" style="font-size:13px;font-weight:600;">{{ __('View all notifications →') }}</a>
                        </div>
                    </div>
                </div>

                {{-- User Menu --}}
                <a href="{{ route('profile.edit') }}" class="user-menu">
                    <div class="user-avatar">{{ auth()->user()->initials }}</div>
                    <span class="user-menu-name">{{ auth()->user()->name }}</span>
                </a>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success animate-fade-in-up" data-auto-dismiss>
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error animate-fade-in-up" data-auto-dismiss>
                    ❌ {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-error animate-fade-in-up">
                    ⚠️ {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
