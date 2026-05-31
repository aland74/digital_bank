<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ckb' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Legal' }} — Distributed Bank</title>
    <meta name="description" content="{{ $description ?? 'Distributed Bank legal information' }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            } else if (savedTheme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <style>
        :root {
            --bg-base: #030712;
            --bg-white: rgba(17, 24, 39, 0.92);
            --bg-white: rgba(31, 41, 55, 0.4);
            --info: #06b6d4;
            #7c3aed: #a855f7;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --border-light: rgba(255, 255, 255, 0.1);
        }
        [data-theme="light"] {
            --bg-base: #f0f4f8;
            --bg-white: rgba(255, 255, 255, 0.96);
            --bg-white: rgba(255, 255, 255, 0.8);
            --info: #0891b2;
            #7c3aed: #9333ea;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-light: rgba(0, 0, 0, 0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-base); color: var(--text-main); line-height: 1.8; }

        .legal-nav {
            position: fixed; top: 0; width: 100%; z-index: 100;
            background: var(--bg-white); backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-light);
            padding: 0 5%; height: 64px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .legal-nav a.logo {
            font-size: 20px; font-weight: 800; display: flex; align-items: center; gap: 8px;
            text-decoration: none; color: var(--text-main);
        }
        .logo-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--info), #7c3aed);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: white; font-size: 14px;
        }
        .legal-nav a.back-link {
            color: var(--info); text-decoration: none; font-weight: 500; font-size: 14px;
            transition: opacity 0.3s;
        }
        .legal-nav a.back-link:hover { opacity: 0.8; }

        .legal-content {
            max-width: 800px; margin: 0 auto; padding: 120px 24px 80px;
        }
        .legal-content h1 {
            font-size: 36px; font-weight: 800; margin-bottom: 8px; letter-spacing: -1px;
        }
        .legal-content .last-updated {
            color: var(--text-muted); font-size: 14px; margin-bottom: 40px;
        }
        .legal-content h2 {
            font-size: 22px; font-weight: 700; margin-top: 40px; margin-bottom: 16px;
            color: var(--text-main);
        }
        .legal-content p, .legal-content li {
            color: var(--text-muted); font-size: 15px; line-height: 1.8; margin-bottom: 16px;
        }
        .legal-content ul {
            padding-left: 24px; margin-bottom: 16px;
        }
        .legal-content ul li { margin-bottom: 8px; }

        .legal-footer {
            text-align: center; padding: 40px 24px;
            border-top: 1px solid var(--border-light);
            color: var(--text-muted); font-size: 13px;
        }
    </style>
</head>
<body>
    <nav class="legal-nav">
        <a href="/" class="logo"><div class="logo-icon">N</div> Distributed Bank</a>
        <a href="/" class="back-link">← {{ __('Back to Home') }}</a>
    </nav>

    <div class="legal-content">
        @yield('legal-content')
    </div>

    <div class="legal-footer">
        &copy; {{ date('Y') }} {{ __('Distributed Bank Platform. All rights reserved. Banking services are simulated for demonstration purposes.') }}
    </div>
</body>
</html>
