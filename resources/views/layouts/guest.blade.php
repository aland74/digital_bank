<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ckb' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NexusBank') — Secure Digital Banking</title>
    <meta name="description" content="NexusBank — Your Secure Digital Banking Platform. Open an account in minutes.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else if (savedTheme === 'light') {
                document.documentElement.removeAttribute('data-theme');
            }
        })();
    </script>
</head>
<body>
    <div class="guest-layout">
        @yield('content')
    </div>

    {{-- Language & Theme Switcher (top right) --}}
    <div style="position: fixed; top: 20px; right: 20px; z-index: 50; display: flex; align-items: center; gap: 8px;">
        <div class="lang-switcher">
            <a href="{{ route('lang.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
            <a href="{{ route('lang.switch', 'ckb') }}" class="lang-btn {{ app()->getLocale() === 'ckb' ? 'active' : '' }}">CKB</a>
        </div>
        <button id="theme-toggle" class="topbar-btn" aria-label="Toggle Theme">
            <span class="theme-icon-dark">☀️</span>
            <span class="theme-icon-light" style="display:none;">🌙</span>
        </button>
    </div>

    <script>
        // Theme toggle for guest pages
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const themeIconDark = document.querySelector('.theme-icon-dark');
            const themeIconLight = document.querySelector('.theme-icon-light');

            function isDarkMode() {
                return document.documentElement.getAttribute('data-theme') === 'dark';
            }

            function updateThemeIcons() {
                if (!themeIconDark || !themeIconLight) return;
                if (isDarkMode()) {
                    themeIconDark.style.display = 'none';
                    themeIconLight.style.display = 'inline';
                } else {
                    themeIconDark.style.display = 'inline';
                    themeIconLight.style.display = 'none';
                }
            }

            if (themeToggle) {
                updateThemeIcons();
                themeToggle.addEventListener('click', function() {
                    if (isDarkMode()) {
                        document.documentElement.removeAttribute('data-theme');
                        localStorage.setItem('theme', 'light');
                    } else {
                        document.documentElement.setAttribute('data-theme', 'dark');
                        localStorage.setItem('theme', 'dark');
                    }
                    updateThemeIcons();
                });
            }
        });
    </script>
</body>
</html>
