<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ckb' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Distributed Bank — Premium Digital Banking</title>
    <meta name="description" content="Distributed Bank — Your Secure Digital Banking Platform in Kurdistan Region. Escrow transfers, smart cards, and intelligent loan management.">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            if (saved === 'light') document.documentElement.setAttribute('data-theme', 'light');
            else if (saved === 'dark') document.documentElement.removeAttribute('data-theme');
            else if (window.matchMedia('(prefers-color-scheme: light)').matches) document.documentElement.setAttribute('data-theme', 'light');
        })();
    </script>
    <style>
        :root {
            --bg: #030712;
            --bg-card: rgba(17, 24, 39, 0.6);
            --bg-card-solid: rgba(17, 24, 39, 0.92);
            --bg-card-hover: rgba(31, 41, 55, 0.7);
            --cyan: #06b6d4;
            --purple: #a855f7;
            --emerald: #10b981;
            --rose: #f43f5e;
            --amber: #f59e0b;
            --text: #f9fafb;
            --text-muted: #9ca3af;
            --border: rgba(255,255,255,0.08);
            --border-hover: rgba(6,182,212,0.5);
            --glass: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.01));
            --glass-border: rgba(255,255,255,0.12);
            --glass-text: #fff;
            --glass-muted: rgba(255,255,255,0.7);
            --icon-bg: rgba(255,255,255,0.03);
            --nav-h: 72px;
        }
        [data-theme="light"] {
            --bg: #f0f4f8;
            --bg-card: rgba(255,255,255,0.8);
            --bg-card-solid: rgba(255,255,255,0.96);
            --bg-card-hover: rgba(255,255,255,0.95);
            --text: #0f172a;
            --text-muted: #64748b;
            --border: rgba(0,0,0,0.06);
            --border-hover: rgba(6,182,212,0.6);
            --glass: linear-gradient(135deg, rgba(0,0,0,0.03), rgba(0,0,0,0.01));
            --glass-border: rgba(0,0,0,0.08);
            --glass-text: #0f172a;
            --glass-muted: rgba(15,23,42,0.7);
            --icon-bg: rgba(0,0,0,0.03);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Outfit',sans-serif; }
        body { background:var(--bg); color:var(--text); overflow-x:hidden; line-height:1.6; scroll-behavior:smooth; }

        /* ── Ambient Background ── */
        .ambient { position:fixed; inset:0; z-index:-1; pointer-events:none; overflow:hidden; }
        .ambient .orb { position:absolute; border-radius:50%; filter:blur(80px); opacity:0.5; }
        .ambient .orb-1 { width:600px; height:600px; top:-200px; left:-150px; background:radial-gradient(circle, rgba(168,85,247,0.2), transparent 70%); animation:drift 20s ease-in-out infinite alternate; }
        .ambient .orb-2 { width:700px; height:700px; bottom:-300px; right:-200px; background:radial-gradient(circle, rgba(6,182,212,0.15), transparent 70%); animation:drift 25s ease-in-out infinite alternate-reverse; }
        .ambient .orb-3 { width:500px; height:500px; top:40%; left:40%; background:radial-gradient(circle, rgba(16,185,129,0.08), transparent 70%); animation:drift 30s ease-in-out infinite alternate; }
        @keyframes drift { 0%{transform:translate(0,0) scale(1)} 100%{transform:translate(40px,60px) scale(1.15)} }

        /* ── Navbar ── */
        .nav { position:fixed; top:0; width:100%; z-index:100; height:var(--nav-h); padding:0 5%; display:flex; justify-content:space-between; align-items:center; transition:all .4s cubic-bezier(.4,0,.2,1); }
        .nav.scrolled { background:var(--bg-card-solid); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border-bottom:1px solid var(--border); box-shadow:0 4px 30px rgba(0,0,0,.12); }
        .logo { font-size:22px; font-weight:800; letter-spacing:-.5px; display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--text); transition:transform .3s; }
        .logo:hover { transform:scale(1.03); }
        .logo-icon { width:38px; height:38px; background:linear-gradient(135deg, var(--cyan), var(--purple)); border-radius:11px; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#fff; font-size:17px; box-shadow:0 2px 12px rgba(6,182,212,.35); }
        .nav-links { display:flex; gap:6px; }
        .nav-links a { color:var(--text-muted); text-decoration:none; font-weight:500; font-size:14px; padding:8px 16px; border-radius:999px; transition:all .3s; }
        .nav-links a:hover { color:var(--text); background:rgba(255,255,255,.05); }
        .nav-actions { display:flex; gap:12px; align-items:center; }
        .lang { display:flex; align-items:center; gap:6px; font-size:13px; background:var(--bg-card); border-radius:999px; padding:4px 12px; border:1px solid var(--border); }
        .lang a { text-decoration:none; color:var(--text-muted); font-weight:500; padding:2px 4px; transition:color .3s; }
        .lang a:hover, .lang a.on { color:var(--text); font-weight:700; }
        .lang .s { color:var(--border); }
        .theme-btn { width:36px; height:36px; border-radius:50%; border:1px solid var(--border); background:var(--bg-card); color:var(--text); display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all .3s; font-size:16px; }
        .theme-btn:hover { border-color:var(--border-hover); background:var(--bg-card-hover); transform:rotate(30deg); }
        .mob-btn { display:none; width:40px; height:40px; border-radius:12px; border:1px solid var(--border); background:var(--bg-card); color:var(--text); cursor:pointer; flex-direction:column; align-items:center; justify-content:center; gap:5px; transition:all .3s; }
        .mob-btn span { display:block; width:18px; height:2px; background:var(--text); border-radius:2px; transition:all .3s; }
        .mob-btn.on span:nth-child(1) { transform:rotate(45deg) translate(5px,5px); }
        .mob-btn.on span:nth-child(2) { opacity:0; }
        .mob-btn.on span:nth-child(3) { transform:rotate(-45deg) translate(5px,-5px); }
        .mob-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:90; opacity:0; transition:opacity .3s; }
        .mob-overlay.on { opacity:1; }
        .mob-nav { position:fixed; top:0; right:-320px; width:300px; height:100%; background:var(--bg-card-solid); backdrop-filter:blur(24px); z-index:95; padding:100px 24px 40px; transition:right .4s cubic-bezier(.4,0,.2,1); border-left:1px solid var(--border); overflow-y:auto; }
        .mob-nav.on { right:0; }
        .mob-nav a { display:block; padding:14px 16px; color:var(--text-muted); text-decoration:none; font-weight:500; font-size:16px; border-radius:12px; transition:all .3s; margin-bottom:4px; }
        .mob-nav a:hover { color:var(--text); background:rgba(255,255,255,.05); }
        .mob-nav .divider { height:1px; background:var(--border); margin:16px 0; }
        .mob-nav .mob-cta { margin-top:24px; display:flex; flex-direction:column; gap:12px; }

        /* ── Buttons ── */
        .btn { padding:10px 24px; border-radius:999px; font-weight:600; text-decoration:none; transition:all .3s ease; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; border:none; font-size:15px; gap:8px; }
        .btn-ghost { color:var(--text); background:transparent; }
        .btn-ghost:hover { background:rgba(255,255,255,.05); }
        .btn-outline { color:var(--text); background:transparent; border:1px solid var(--border); }
        .btn-outline:hover { border-color:var(--border-hover); background:rgba(6,182,212,.05); }
        .btn-primary { background:linear-gradient(135deg, var(--cyan), var(--purple)); color:#fff; box-shadow:0 4px 15px rgba(6,182,212,.3); position:relative; overflow:hidden; }
        .btn-primary::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,.2), transparent); transition:left .5s; }
        .btn-primary:hover::before { left:100%; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(168,85,247,.4); }
        .btn-sm { padding:8px 18px; font-size:13px; }
        .btn-lg { font-size:17px; padding:14px 36px; }

        /* ── Hero ── */
        .hero { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:140px 5% 60px; text-align:center; position:relative; }
        .hero-inner { max-width:820px; margin:0 auto; z-index:1; }
        .badge { display:inline-flex; align-items:center; gap:8px; padding:8px 20px; border-radius:999px; background:rgba(168,85,247,.1); border:1px solid rgba(168,85,247,.3); color:var(--purple); font-size:13px; font-weight:600; margin-bottom:32px; animation:slideDown .8s ease-out; }
        .badge-dot { width:8px; height:8px; background:var(--cyan); border-radius:50%; box-shadow:0 0 10px var(--cyan); animation:pulse 2s infinite; }
        .hero h1 { font-size:clamp(44px,5.5vw,72px); font-weight:900; line-height:1.08; margin-bottom:24px; letter-spacing:-2px; animation:fadeUp 1s ease-out .2s both; }
        .grad { background:linear-gradient(135deg, var(--cyan), var(--purple)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
        .hero p { font-size:clamp(16px,1.8vw,19px); color:var(--text-muted); max-width:580px; margin:0 auto 40px; animation:fadeUp 1s ease-out .4s both; line-height:1.7; }
        .hero-cta { animation:fadeUp 1s ease-out .6s both; display:flex; gap:16px; justify-content:center; flex-wrap:wrap; }

        /* 3D Card */
        .card-wrap { margin-top:64px; perspective:1200px; display:flex; justify-content:center; animation:fadeUp 1s ease-out .8s both; padding-bottom:20px; }
        .card3d { width:400px; height:245px; border-radius:22px; background:var(--glass); border:1px solid var(--glass-border); backdrop-filter:blur(20px); padding:30px; position:relative; display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 25px 50px -12px rgba(0,0,0,.6), 0 0 40px rgba(6,182,212,.12); transform:rotateX(15deg) rotateY(-15deg); transition:transform .6s cubic-bezier(.4,0,.2,1), box-shadow .6s; transform-style:preserve-3d; text-align:left; }
        .card-wrap:hover .card3d { transform:rotateX(0) rotateY(0) translateY(-15px) scale(1.05); box-shadow:0 35px 60px -12px rgba(0,0,0,.7), 0 0 60px rgba(168,85,247,.2); }
        .card3d::before { content:''; position:absolute; inset:0; border-radius:22px; background:linear-gradient(135deg, transparent 40%, var(--glass-border) 50%, transparent 60%); background-size:200% 200%; animation:shimmer 4s infinite linear; pointer-events:none; }
        .card-chip { width:46px; height:34px; background:linear-gradient(135deg, #d1d5db, #9ca3af); border-radius:6px; position:relative; overflow:hidden; }
        .card-chip::after { content:''; position:absolute; width:100%; height:1px; background:rgba(0,0,0,.1); top:50%; }
        .card-chip::before { content:''; position:absolute; width:1px; height:100%; background:rgba(0,0,0,.1); left:50%; }
        .card-brand { font-size:22px; font-weight:900; font-style:italic; color:var(--glass-text); letter-spacing:1px; }
        .card-number { font-family:'Courier New', monospace; font-size:22px; letter-spacing:4px; color:var(--glass-text); margin-bottom:12px; }
        .card-meta { display:flex; justify-content:space-between; font-size:12px; color:var(--glass-muted); text-transform:uppercase; letter-spacing:1px; font-weight:600; }
        .card-contactless { position:absolute; top:30px; right:80px; opacity:.6; }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

        /* ── Stats ── */
        .stats { display:flex; justify-content:center; gap:8%; padding:48px 5%; border-top:1px solid var(--border); border-bottom:1px solid var(--border); background:var(--bg-card); position:relative; z-index:10; backdrop-filter:blur(10px); }
        .stat { text-align:center; }
        .stat-val { font-size:clamp(32px,4vw,44px); font-weight:800; display:block; line-height:1.2; }
        .stat-label { font-size:13px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:1.5px; }

        /* ── Features ── */
        .features { padding:120px 5%; position:relative; z-index:5; }
        .sec-head { text-align:center; margin-bottom:80px; }
        .sec-head h2 { font-size:clamp(36px,4vw,48px); font-weight:800; margin-bottom:16px; letter-spacing:-1px; }
        .sec-head p { color:var(--text-muted); font-size:18px; max-width:600px; margin:0 auto; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px,1fr)); gap:24px; max-width:1200px; margin:0 auto; }
        .feat { background:var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:36px; transition:all .4s cubic-bezier(.4,0,.2,1); position:relative; overflow:hidden; backdrop-filter:blur(10px); }
        .feat:hover { border-color:var(--border-hover); transform:translateY(-8px); box-shadow:0 20px 40px -10px rgba(6,182,212,.12); }
        .feat::before { content:''; position:absolute; inset:0; background:radial-gradient(800px circle at var(--mx) var(--my), rgba(6,182,212,.08), transparent 40%); opacity:0; transition:opacity .3s; pointer-events:none; }
        .feat:hover::before { opacity:1; }
        .feat > * { position:relative; z-index:1; }
        .feat-icon { width:56px; height:56px; border-radius:14px; background:var(--icon-bg); display:flex; align-items:center; justify-content:center; font-size:28px; margin-bottom:20px; border:1px solid var(--border); transition:all .4s; }
        .feat:hover .feat-icon { background:rgba(6,182,212,.1); border-color:rgba(6,182,212,.4); transform:scale(1.1) rotate(5deg); }
        .feat h3 { font-size:20px; font-weight:700; margin-bottom:10px; }
        .feat p { color:var(--text-muted); font-size:15px; line-height:1.7; }

        /* ── Branches ── */
        .branches { padding:120px 5%; position:relative; z-index:5; }
        .branch-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; max-width:1000px; margin:0 auto; }
        .branch-card { background:var(--bg-card); border:1px solid var(--border); border-radius:20px; padding:32px; text-align:center; transition:all .3s; backdrop-filter:blur(10px); }
        .branch-card:hover { border-color:var(--border-hover); transform:translateY(-4px); }
        .branch-icon { font-size:40px; margin-bottom:16px; }
        .branch-card h4 { font-size:18px; font-weight:700; margin-bottom:6px; }
        .branch-card .loc { color:var(--cyan); font-size:13px; font-weight:600; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; }
        .branch-card p { color:var(--text-muted); font-size:14px; line-height:1.6; }

        /* ── CTA ── */
        .cta { padding:120px 5%; position:relative; z-index:5; text-align:center; }
        .cta-box { max-width:700px; margin:0 auto; background:var(--glass); border:1px solid var(--glass-border); border-radius:28px; padding:64px 48px; backdrop-filter:blur(20px); position:relative; overflow:hidden; }
        .cta-box::before { content:''; position:absolute; top:-50%; left:-50%; width:200%; height:200%; background:radial-gradient(circle at 30% 30%, rgba(6,182,212,.08), transparent 50%), radial-gradient(circle at 70% 70%, rgba(168,85,247,.08), transparent 50%); pointer-events:none; }
        .cta-box > * { position:relative; z-index:1; }
        .cta-box h2 { font-size:clamp(28px,3.5vw,40px); font-weight:800; margin-bottom:16px; letter-spacing:-1px; }
        .cta-box p { color:var(--text-muted); font-size:17px; margin-bottom:32px; line-height:1.7; }

        /* ── Footer ── */
        footer { padding:80px 5% 40px; border-top:1px solid var(--border); background:var(--bg-card); }
        .f-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:60px; max-width:1200px; margin:0 auto; }
        .f-col h4 { font-size:16px; font-weight:700; margin-bottom:20px; }
        .f-links { list-style:none; }
        .f-links li { margin-bottom:10px; }
        .f-links a { color:var(--text-muted); text-decoration:none; transition:all .2s; font-size:14px; display:inline-flex; align-items:center; gap:6px; }
        .f-links a:hover { color:var(--cyan); padding-left:4px; }
        .f-links .arr { opacity:0; transform:translateX(-4px); transition:all .2s; font-size:12px; }
        .f-links a:hover .arr { opacity:1; transform:translateX(0); }
        .f-desc { color:var(--text-muted); font-size:14px; margin-top:16px; line-height:1.7; }
        .f-social { display:flex; gap:12px; margin-top:20px; }
        .f-social a { width:36px; height:36px; border-radius:10px; border:1px solid var(--border); background:var(--bg-card); display:flex; align-items:center; justify-content:center; text-decoration:none; font-size:16px; transition:all .3s; }
        .f-social a:hover { border-color:var(--border-hover); transform:translateY(-2px); background:var(--bg-card-hover); }
        .copy { text-align:center; padding-top:48px; color:var(--text-muted); font-size:13px; margin-top:40px; border-top:1px solid rgba(255,255,255,.04); }

        /* ── Toast ── */
        .toast { position:fixed; bottom:30px; left:50%; transform:translateX(-50%) translateY(100px); background:var(--bg-card-solid); backdrop-filter:blur(16px); border:1px solid var(--border); border-radius:12px; padding:12px 24px; font-size:14px; font-weight:500; color:var(--text); z-index:200; box-shadow:0 10px 30px rgba(0,0,0,.3); transition:transform .4s cubic-bezier(.4,0,.2,1); pointer-events:none; }
        .toast.show { transform:translateX(-50%) translateY(0); }

        /* ── Animations ── */
        @keyframes slideDown { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        @keyframes pulse { 0%{box-shadow:0 0 0 0 rgba(6,182,212,.7)} 70%{box-shadow:0 0 0 10px rgba(6,182,212,0)} 100%{box-shadow:0 0 0 0 rgba(6,182,212,0)} }

        /* ── Responsive ── */
        @media(max-width:768px) {
            .nav-links { display:none; }
            .nav-actions .btn { display:none; }
            .mob-btn { display:flex; }
            .mob-overlay { display:block; }
            .hero h1 { font-size:36px; letter-spacing:-1px; }
            .hero-cta { flex-direction:column; gap:12px; align-items:center; }
            .hero-cta .btn { width:100%; max-width:280px; }
            .stats { flex-direction:column; gap:32px; }
            .f-grid { grid-template-columns:1fr 1fr; gap:40px; }
            .card3d { width:100%; max-width:340px; height:210px; }
            .branch-grid { grid-template-columns:1fr; }
        }
        @media(max-width:480px) {
            .f-grid { grid-template-columns:1fr; gap:32px; }
            .lang { display:none; }
        }
    </style>
</head>
<body>
    <!-- Ambient Background -->
    <div class="ambient">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <!-- Navigation -->
    <nav class="nav" id="navbar">
        <a href="/" class="logo">
            <div class="logo-icon">N</div>
            Distributed Bank
        </a>
        <div class="nav-links">
            <a href="#features">{{ __('Features') }}</a>
            <a href="#cards">{{ __('Smart Cards') }}</a>
            <a href="#loans">{{ __('Loans') }}</a>
            <a href="#branches">{{ __('Branches') }}</a>
            <a href="#about">{{ __('About') }}</a>
        </div>
        <div class="nav-actions">
            <div class="lang">
                <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'on' : '' }}">EN</a>
                <span class="s">|</span>
                <a href="{{ route('lang.switch', 'ckb') }}" class="{{ app()->getLocale() === 'ckb' ? 'on' : '' }}">CKB</a>
            </div>
            <button id="theme-toggle" class="theme-btn" aria-label="Toggle Theme">
                <span class="t-dark">☀️</span>
                <span class="t-light" style="display:none">🌙</span>
            </button>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">{{ __('Sign In') }}</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">{{ __('Open Account') }}</a>
            @endauth
            <button class="mob-btn" id="mob-btn" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
    </nav>

    <!-- Mobile Nav -->
    <div class="mob-overlay" id="mob-overlay"></div>
    <div class="mob-nav" id="mob-nav">
        <a href="#features">{{ __('Features') }}</a>
        <a href="#cards">{{ __('Smart Cards') }}</a>
        <a href="#loans">{{ __('Loans') }}</a>
        <a href="#branches">{{ __('Branches') }}</a>
        <a href="#about">{{ __('About') }}</a>
        <div class="divider"></div>
        <div class="mob-cta">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary" style="text-align:center">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline" style="text-align:center">{{ __('Sign In') }}</a>
                <a href="{{ route('register') }}" class="btn btn-primary" style="text-align:center">{{ __('Open Account') }}</a>
            @endauth
        </div>
    </div>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-inner">
            <div class="badge">
                <span class="badge-dot"></span>
                {{ __('Distributed Banking Architecture') }}
            </div>
            <h1>{{ __('Experience the') }} <span class="grad">{{ __('Future of Banking') }}</span></h1>
            <p>{{ __('A premium digital banking platform built for Kurdistan Region. Escrow transfers, secure smart cards, and intelligent loan management — all at your fingertips.') }}</p>
            <div class="hero-cta">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">{{ __('Get Started Now') }}</a>
                <a href="#features" class="btn btn-outline btn-lg">{{ __('Explore Features') }}</a>
            </div>

            <!-- 3D Card -->
            <div class="card-wrap">
                <div class="card3d">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start">
                        <div class="card-chip"></div>
                        <div class="card-contactless">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="color:var(--glass-muted)">
                                <path d="M6 18C8.5 14 8.5 10 6 6"/><path d="M10 18C12.5 14 12.5 10 10 6"/><path d="M14 18C16.5 14 16.5 10 14 6"/>
                            </svg>
                        </div>
                        <span class="card-brand">DISTRIBUTED</span>
                    </div>
                    <div>
                        <div class="card-number">•••• •••• •••• 4289</div>
                        <div class="card-meta">
                            <span>{{ __('Premium Member') }}</span>
                            <span>12/28</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats">
        <div class="stat">
            <span class="stat-val grad">$2.5B+</span>
            <span class="stat-label">{{ __('Processed Safely') }}</span>
        </div>
        <div class="stat">
            <span class="stat-val">3</span>
            <span class="stat-label">{{ __('Branch Cities') }}</span>
        </div>
        <div class="stat">
            <span class="stat-val">0%</span>
            <span class="stat-label">{{ __('Hidden Fees') }}</span>
        </div>
        <div class="stat">
            <span class="stat-val">99.9%</span>
            <span class="stat-label">{{ __('Uptime Guarantee') }}</span>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="features">
        <div class="sec-head">
            <h2>{{ __('Designed for') }} <span class="grad">{{ __('Excellence') }}</span></h2>
            <p>{{ __('Every feature has been meticulously crafted to provide you with the most seamless and secure financial experience.') }}</p>
        </div>
        <div class="grid">
            <div class="feat" id="cards">
                <div class="feat-icon">💳</div>
                <h3>{{ __('Smart Cards') }}</h3>
                <p>{{ __('Generate secure virtual and physical cards instantly. Auto-generated PINs, custom spending limits, and one-tap freeze controls.') }}</p>
            </div>
            <div class="feat">
                <div class="feat-icon">💸</div>
                <h3>{{ __('Escrow Transfers') }}</h3>
                <p>{{ __('Send money with confidence. Funds are held securely until the recipient accepts — preventing accidental or fraudulent transfers.') }}</p>
            </div>
            <div class="feat" id="loans">
                <div class="feat-icon">🏦</div>
                <h3>{{ __('Dynamic Loans') }}</h3>
                <p>{{ __('Competitive loans backed by real-time bank reserve limits. Instant approvals driven by an intelligent capacity system.') }}</p>
            </div>
            <div class="feat">
                <div class="feat-icon">🛡️</div>
                <h3>{{ __('Fraud Detection') }}</h3>
                <p>{{ __('6-layer heuristic fraud engine monitors velocity, time anomalies, and transaction deviations 24/7.') }}</p>
            </div>
            <div class="feat">
                <div class="feat-icon">🪪</div>
                <h3>{{ __('KYC Verification') }}</h3>
                <p>{{ __('Mandatory passport and national ID verification ensures platform integrity and full regulatory compliance.') }}</p>
            </div>
            <div class="feat">
                <div class="feat-icon">🌍</div>
                <h3>{{ __('Dual Currency') }}</h3>
                <p>{{ __('Full USD and IQD support with real-time exchange rates. Convert between currencies instantly with transparent pricing.') }}</p>
            </div>
        </div>
    </section>

    <!-- Branches -->
    <section id="branches" class="branches">
        <div class="sec-head">
            <h2>{{ __('Our') }} <span class="grad">{{ __('Branches') }}</span></h2>
            <p>{{ __('Three strategic locations across Kurdistan Region, fully synchronized with headquarters.') }}</p>
        </div>
        <div class="branch-grid">
            <div class="branch-card">
                <div class="branch-icon">🏛️</div>
                <h4>{{ __('Erbil Branch') }}</h4>
                <div class="loc">{{ __('Headquarters') }}</div>
                <p>{{ __('60m Street, Ankawa — The central hub managing all branch synchronization and admin operations.') }}</p>
            </div>
            <div class="branch-card">
                <div class="branch-icon">🏢</div>
                <h4>{{ __('Sulaimaniyah Branch') }}</h4>
                <div class="loc">{{ __('Salim Street') }}</div>
                <p>{{ __('Full-service branch serving Sulaimaniyah province with dedicated local support and teller services.') }}</p>
            </div>
            <div class="branch-card">
                <div class="branch-icon">🏗️</div>
                <h4>{{ __('Duhok Branch') }}</h4>
                <div class="loc">{{ __('Duhok City') }}</div>
                <p>{{ __('Serving Duhok province with complete banking services and local KYC verification.') }}</p>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section id="about" class="cta">
        <div class="cta-box">
            <h2>{{ __('Ready to') }} <span class="grad">{{ __('Start') }}</span>?</h2>
            <p>{{ __('Join Distributed Bank today. Open your account in minutes with dual-currency support, smart cards, and access to our full suite of banking tools.') }}</p>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">{{ __('Open Your Account Today') }}</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="f-grid">
            <div class="f-col">
                <a href="/" class="logo" style="margin-bottom:12px; display:inline-flex">
                    <div class="logo-icon">N</div>
                    Distributed Bank
                </a>
                <p class="f-desc">{{ __('Elevating digital banking with state-of-the-art security, stunning design, and powerful features for Kurdistan Region.') }}</p>
                <div class="f-social">
                    <a href="#" onclick="toast('{{ __('Coming soon') }}'); return false" title="Twitter">𝕏</a>
                    <a href="#" onclick="toast('{{ __('Coming soon') }}'); return false" title="LinkedIn">in</a>
                    <a href="#" onclick="toast('{{ __('Coming soon') }}'); return false" title="GitHub">⌨</a>
                </div>
            </div>
            <div class="f-col">
                <h4>{{ __('Platform') }}</h4>
                <ul class="f-links">
                    <li><a href="#cards">{{ __('Smart Cards') }} <span class="arr">→</span></a></li>
                    <li><a href="#features">{{ __('Escrow Transfers') }} <span class="arr">→</span></a></li>
                    <li><a href="#loans">{{ __('Personal Loans') }} <span class="arr">→</span></a></li>
                    <li><a href="#features">{{ __('Fraud Security') }} <span class="arr">→</span></a></li>
                </ul>
            </div>
            <div class="f-col">
                <h4>{{ __('Company') }}</h4>
                <ul class="f-links">
                    <li><a href="#about">{{ __('About Distributed Bank') }} <span class="arr">→</span></a></li>
                    <li><a href="#branches">{{ __('Our Branches') }} <span class="arr">→</span></a></li>
                    <li><a href="{{ route('register') }}">{{ __('Open Account') }} <span class="arr">→</span></a></li>
                    <li><a href="#" onclick="toast('{{ __('Coming soon') }}'); return false">{{ __('Careers') }} <span class="arr">→</span></a></li>
                </ul>
            </div>
            <div class="f-col">
                <h4>{{ __('Legal') }}</h4>
                <ul class="f-links">
                    <li><a href="{{ route('legal.privacy') }}">{{ __('Privacy Policy') }} <span class="arr">→</span></a></li>
                    <li><a href="{{ route('legal.terms') }}">{{ __('Terms of Service') }} <span class="arr">→</span></a></li>
                    <li><a href="{{ route('legal.cookies') }}">{{ __('Cookie Policy') }} <span class="arr">→</span></a></li>
                    <li><a href="{{ route('legal.compliance') }}">{{ __('Compliance') }} <span class="arr">→</span></a></li>
                </ul>
            </div>
        </div>
        <div class="copy">
            &copy; {{ date('Y') }} {{ __('Distributed Bank Platform. All rights reserved. Banking services are simulated for demonstration purposes.') }}
        </div>
    </footer>

    <div class="toast" id="toast"></div>

    <script>
        // Navbar scroll
        const nav = document.getElementById('navbar');
        window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 50));
        if (window.scrollY > 50) nav.classList.add('scrolled');

        // Mobile menu
        const mobBtn = document.getElementById('mob-btn');
        const mobNav = document.getElementById('mob-nav');
        const mobOvl = document.getElementById('mob-overlay');
        function toggleMob() {
            mobBtn.classList.toggle('on');
            mobNav.classList.toggle('on');
            mobOvl.classList.toggle('on');
            document.body.style.overflow = mobNav.classList.contains('on') ? 'hidden' : '';
        }
        mobBtn.addEventListener('click', toggleMob);
        mobOvl.addEventListener('click', toggleMob);
        document.querySelectorAll('.mob-nav a').forEach(a => a.addEventListener('click', () => { if (mobNav.classList.contains('on')) toggleMob(); }));

        // Feature glow
        document.querySelectorAll('.feat').forEach(el => {
            el.addEventListener('mousemove', e => {
                const r = el.getBoundingClientRect();
                el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                el.style.setProperty('--my', (e.clientY - r.top) + 'px');
            });
            el.addEventListener('mouseleave', () => { el.style.setProperty('--mx','50%'); el.style.setProperty('--my','50%'); });
        });

        // Theme toggle
        const tgl = document.getElementById('theme-toggle');
        const tDark = document.querySelector('.t-dark');
        const tLight = document.querySelector('.t-light');
        function updTheme() {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            tDark.style.display = isLight ? 'none' : 'inline';
            tLight.style.display = isLight ? 'inline' : 'none';
        }
        if (tgl) { updTheme(); tgl.addEventListener('click', () => {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            if (isLight) { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme','dark'); }
            else { document.documentElement.setAttribute('data-theme','light'); localStorage.setItem('theme','light'); }
            updTheme();
        });}

        // Toast
        function toast(msg) { const t=document.getElementById('toast'); t.textContent=msg; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),2500); }
    </script>
</body>
</html>
