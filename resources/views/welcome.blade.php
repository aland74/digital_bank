<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ckb' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexusBank — Premium Digital Banking</title>
    <meta name="description" content="NexusBank — Your Secure Digital Banking Platform. Experience escrow transfers, smart cards, and intelligent loan management.">
    <!-- Modern Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            --bg-white: rgba(17, 24, 39, 0.7);
            --bg-white-solid: rgba(17, 24, 39, 0.92);
            --bg-white: rgba(31, 41, 55, 0.4);
            --bg-white-hover: rgba(31, 41, 55, 0.6);
            --info: #06b6d4;
            #7c3aed: #a855f7;
            --accent-emerald: #10b981;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --border-light: rgba(255, 255, 255, 0.1);
            --border-hover: rgba(6, 182, 212, 0.5);
            --card-mockup-bg: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.01));
            --card-mockup-border: rgba(255,255,255,0.15);
            --card-mockup-text: #ffffff;
            --card-mockup-text-muted: rgba(255,255,255,0.8);
            --icon-bg: rgba(255,255,255,0.03);
            --copyright-border: rgba(255,255,255,0.05);
            --nav-height: 72px;
        }

        [data-theme="light"] {
            --bg-base: #f0f4f8;
            --bg-white: rgba(255, 255, 255, 0.85);
            --bg-white-solid: rgba(255, 255, 255, 0.96);
            --bg-white: rgba(255, 255, 255, 0.8);
            --bg-white-hover: rgba(255, 255, 255, 0.95);
            --info: #0891b2;
            #7c3aed: #9333ea;
            --accent-emerald: #059669;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-light: rgba(0, 0, 0, 0.08);
            --border-hover: rgba(6, 182, 212, 0.6);
            --card-mockup-bg: linear-gradient(135deg, rgba(0,0,0,0.05), rgba(0,0,0,0.01));
            --card-mockup-border: rgba(0,0,0,0.1);
            --card-mockup-text: #0f172a;
            --card-mockup-text-muted: rgba(15, 23, 42, 0.7);
            --icon-bg: rgba(0,0,0,0.03);
            --copyright-border: rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
            scroll-behavior: smooth;
        }

        /* Gradient Orbs Background */
        .orb-1 {
            position: fixed; top: -10%; left: -10%; width: 50vw; height: 50vw;
            background: radial-gradient(circle, rgba(168,85,247,0.15) 0%, transparent 60%);
            z-index: -1; pointer-events: none; animation: floatOrb 15s infinite alternate;
        }
        .orb-2 {
            position: fixed; bottom: -20%; right: -10%; width: 60vw; height: 60vw;
            background: radial-gradient(circle, rgba(6,182,212,0.1) 0%, transparent 60%);
            z-index: -1; pointer-events: none; animation: floatOrb 20s infinite alternate-reverse;
        }
        .orb-3 {
            position: fixed; top: 40%; left: 50%; width: 40vw; height: 40vw;
            background: radial-gradient(circle, rgba(16,185,129,0.06) 0%, transparent 60%);
            z-index: -1; pointer-events: none; animation: floatOrb 25s infinite alternate;
            transform: translateX(-50%);
        }

        @keyframes floatOrb {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(5%, 10%) scale(1.1); }
        }

        /* ═══════════════════════════════════════════════════════
           NAVBAR — Premium glassmorphism with scroll effect
           ═══════════════════════════════════════════════════════ */
        .navbar {
            position: fixed; top: 0; width: 100%; z-index: 100;
            background: transparent;
            backdrop-filter: blur(0px); -webkit-backdrop-filter: blur(0px);
            border-bottom: 1px solid transparent;
            padding: 0 5%;
            height: var(--nav-height);
            display: flex; justify-content: space-between; align-items: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .navbar.scrolled {
            background: var(--bg-white-solid);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-light);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
        }

        .logo {
            font-size: 22px; font-weight: 800; letter-spacing: -0.5px;
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; color: var(--text-main);
            transition: transform 0.3s;
        }
        .logo:hover { transform: scale(1.03); }
        .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--info), #7c3aed);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: white; font-size: 16px;
            box-shadow: 0 2px 10px rgba(6, 182, 212, 0.3);
        }

        .nav-center { display: flex; gap: 8px; }
        .nav-center a {
            color: var(--text-muted); text-decoration: none; font-weight: 500;
            transition: all 0.3s; font-size: 14px; padding: 8px 16px;
            border-radius: 999px; position: relative;
        }
        .nav-center a:hover {
            color: var(--text-main);
            background: rgba(255,255,255,0.05);
        }

        .nav-right { display: flex; gap: 12px; align-items: center; }

        /* Language Switcher */
        .lang-switch {
            display: flex; align-items: center; gap: 6px; font-size: 13px;
            background: var(--bg-white); border-radius: 999px; padding: 4px 12px;
            border: 1px solid var(--border-light);
        }
        .lang-switch a {
            text-decoration: none; color: var(--text-muted); transition: color 0.3s;
            font-weight: 500; padding: 2px 4px;
        }
        .lang-switch a:hover, .lang-switch a.active { color: var(--text-main); font-weight: 700; }
        .lang-switch .sep { color: var(--border-light); }

        /* Theme Toggle */
        .theme-btn {
            width: 36px; height: 36px; border-radius: 50%;
            border: 1px solid var(--border-light);
            background: var(--bg-white); color: var(--text-main);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.3s; font-size: 16px;
        }
        .theme-btn:hover {
            border-color: var(--border-hover);
            background: var(--bg-white-hover);
            transform: rotate(30deg);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            width: 40px; height: 40px; border-radius: 12px;
            border: 1px solid var(--border-light); background: var(--bg-white);
            color: var(--text-main); cursor: pointer;
            flex-direction: column; align-items: center; justify-content: center; gap: 5px;
            transition: all 0.3s;
        }
        .mobile-menu-btn span {
            display: block; width: 18px; height: 2px;
            background: var(--text-main); border-radius: 2px;
            transition: all 0.3s;
        }
        .mobile-menu-btn.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
        .mobile-menu-btn.active span:nth-child(2) { opacity: 0; }
        .mobile-menu-btn.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

        /* Mobile Slide-out Panel */
        .mobile-nav-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 90;
            opacity: 0; transition: opacity 0.3s;
        }
        .mobile-nav-overlay.active { opacity: 1; }

        .mobile-nav {
            position: fixed; top: 0; right: -320px; width: 300px; height: 100%;
            background: var(--bg-white-solid); backdrop-filter: blur(24px);
            z-index: 95; padding: 100px 24px 40px;
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 1px solid var(--border-light);
            overflow-y: auto;
        }
        .mobile-nav.active { right: 0; }
        .mobile-nav a {
            display: block; padding: 14px 16px; color: var(--text-muted);
            text-decoration: none; font-weight: 500; font-size: 16px;
            border-radius: 12px; transition: all 0.3s; margin-bottom: 4px;
        }
        .mobile-nav a:hover {
            color: var(--text-main); background: rgba(255,255,255,0.05);
        }
        .mobile-nav .mobile-nav-divider {
            height: 1px; background: var(--border-light); margin: 16px 0;
        }
        .mobile-nav .mobile-nav-actions {
            margin-top: 24px; display: flex; flex-direction: column; gap: 12px;
        }

        /* Buttons */
        .btn {
            padding: 10px 24px; border-radius: 999px; font-weight: 600; text-decoration: none;
            transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; border: none; font-size: 15px; gap: 8px;
        }
        .btn-ghost { color: var(--text-main); background: transparent; }
        .btn-ghost:hover { background: rgba(255,255,255,0.05); }
        .btn-outline {
            color: var(--text-main); background: transparent;
            border: 1px solid var(--border-light);
        }
        .btn-outline:hover {
            border-color: var(--border-hover);
            background: rgba(6, 182, 212, 0.05);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--info), #7c3aed);
            color: white; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
            position: relative; overflow: hidden;
        }
        .btn-primary::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        .btn-primary:hover::before { left: 100%; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4); }

        .btn-sm { padding: 8px 18px; font-size: 13px; }

        /* ═══════════════════════════════════════════════════════
           HERO — Full-screen with 3D card
           ═══════════════════════════════════════════════════════ */
        .hero {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 140px 5% 60px; text-align: center; position: relative;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; border-radius: 999px;
            background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3);
            color: #7c3aed; font-size: 13px; font-weight: 600; margin-bottom: 32px;
            animation: slideDown 0.8s ease-out;
        }
        .hero-badge-dot {
            width: 8px; height: 8px; background: var(--info); border-radius: 50%;
            box-shadow: 0 0 10px var(--info);
            animation: pulseGlow 2s infinite;
        }
        .hero h1 {
            font-size: clamp(44px, 5.5vw, 72px); font-weight: 900; line-height: 1.08; margin-bottom: 24px;
            letter-spacing: -2px; animation: fadeInUp 1s ease-out 0.2s both;
        }
        .text-gradient {
            background: linear-gradient(135deg, var(--info), #7c3aed);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero p {
            font-size: clamp(16px, 1.8vw, 19px); color: var(--text-muted); max-width: 580px; margin: 0 auto 40px;
            animation: fadeInUp 1s ease-out 0.4s both; line-height: 1.7;
        }
        .hero-cta {
            animation: fadeInUp 1s ease-out 0.6s both;
            display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;
        }

        /* 3D Card Animation */
        .hero-card-wrapper {
            margin-top: 60px; perspective: 1200px; display: flex; justify-content: center;
            animation: fadeInUp 1s ease-out 0.8s both; padding-bottom: 20px;
        }
        .hero-card {
            width: 380px; height: 230px; border-radius: 20px;
            background: var(--card-mockup-bg);
            border: 1px solid var(--card-mockup-border); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            padding: 28px; position: relative; display: flex; flex-direction: column; justify-content: space-between;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(6, 182, 212, 0.15);
            transform: rotateX(15deg) rotateY(-15deg); transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
            text-align: left;
        }
        .hero-card-wrapper:hover .hero-card {
            transform: rotateX(0deg) rotateY(0deg) translateY(-15px) scale(1.05);
            box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.7), 0 0 60px rgba(168, 85, 247, 0.25);
        }
        .hero-card::before {
            content:''; position:absolute; top:0; left:0; right:0; bottom:0; border-radius: 20px;
            background: linear-gradient(135deg, transparent 40%, var(--card-mockup-border) 50%, transparent 60%);
            background-size: 200% 200%; animation: shimmer 4s infinite linear; pointer-events: none;
        }
        .card-chip {
            width: 44px; height: 32px; background: linear-gradient(135deg, #d1d5db, #9ca3af);
            border-radius: 6px; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .card-chip::after { content:''; position:absolute; width:100%; height:1px; background:rgba(0,0,0,0.1); top:50%; }
        .card-chip::before { content:''; position:absolute; width:1px; height:100%; background:rgba(0,0,0,0.1); left:50%; }

        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /* ═══════════════════════════════════════════════════════
           STATS STRIP
           ═══════════════════════════════════════════════════════ */
        .stats-strip {
            display: flex; justify-content: center; gap: 8%; padding: 48px 5%;
            border-top: 1px solid var(--border-light); border-bottom: 1px solid var(--border-light);
            background: var(--bg-white); position: relative; z-index: 10; backdrop-filter: blur(10px);
        }
        .stat-item { text-align: center; }
        .stat-val {
            font-size: clamp(32px, 4vw, 44px); font-weight: 800; color: var(--text-main);
            display: block; line-height: 1.2;
        }
        .stat-label {
            font-size: 13px; color: var(--text-muted); font-weight: 600;
            text-transform: uppercase; letter-spacing: 1.5px;
        }

        /* ═══════════════════════════════════════════════════════
           FEATURES GRID
           ═══════════════════════════════════════════════════════ */
        .features { padding: 120px 5%; position: relative; z-index: 5; }
        .section-header { text-align: center; margin-bottom: 80px; }
        .section-header h2 {
            font-size: clamp(36px, 4vw, 48px); font-weight: 800; margin-bottom: 16px; letter-spacing: -1px;
        }
        .section-header p { color: var(--text-muted); font-size: 18px; max-width: 600px; margin: 0 auto; }

        .grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px; max-width: 1200px; margin: 0 auto;
        }
        .feature-box {
            background: var(--bg-white); border: 1px solid var(--border-light); border-radius: 20px;
            padding: 36px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden; backdrop-filter: blur(10px);
        }
        .feature-box:hover {
            border-color: var(--border-hover); transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(6, 182, 212, 0.15);
        }
        .feature-box::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(800px circle at var(--mouse-x) var(--mouse-y), rgba(6, 182, 212, 0.1), transparent 40%);
            opacity: 0; transition: opacity 0.3s; pointer-events: none; z-index: 0;
        }
        .feature-box:hover::before { opacity: 1; }
        .feature-box > * { position: relative; z-index: 1; }
        .feature-icon {
            width: 56px; height: 56px; border-radius: 14px; background: var(--icon-bg);
            display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 20px;
            border: 1px solid var(--border-light); transition: all 0.4s;
        }
        .feature-box:hover .feature-icon {
            background: rgba(6, 182, 212, 0.1); border-color: rgba(6, 182, 212, 0.4);
            transform: scale(1.1) rotate(5deg);
        }
        .feature-box h3 { font-size: 20px; font-weight: 700; margin-bottom: 10px; color: var(--text-main); }
        .feature-box p { color: var(--text-muted); font-size: 15px; line-height: 1.7; }

        /* ═══════════════════════════════════════════════════════
           ABOUT / CTA SECTION
           ═══════════════════════════════════════════════════════ */
        .about-section {
            padding: 120px 5%; position: relative; z-index: 5;
        }
        .about-container {
            max-width: 1000px; margin: 0 auto; text-align: center;
        }
        .about-container h2 {
            font-size: clamp(36px, 4vw, 48px); font-weight: 800; margin-bottom: 24px; letter-spacing: -1px;
        }
        .about-container .about-text {
            color: var(--text-muted); font-size: 18px; line-height: 1.8;
            max-width: 700px; margin: 0 auto 48px;
        }
        .about-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
            margin-bottom: 48px;
        }
        .about-card {
            background: var(--bg-white); border: 1px solid var(--border-light);
            border-radius: 20px; padding: 32px; text-align: center;
            transition: all 0.3s;
        }
        .about-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-4px);
        }
        .about-card-icon { font-size: 36px; margin-bottom: 16px; }
        .about-card h4 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .about-card p { color: var(--text-muted); font-size: 14px; line-height: 1.6; }

        /* ═══════════════════════════════════════════════════════
           FOOTER
           ═══════════════════════════════════════════════════════ */
        footer {
            padding: 80px 5% 40px; border-top: 1px solid var(--border-light);
            background: var(--bg-white);
        }
        .footer-grid {
            display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 60px; max-width: 1200px; margin: 0 auto;
        }
        .footer-col h4 { color: var(--text-main); font-size: 16px; font-weight: 700; margin-bottom: 20px; }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a {
            color: var(--text-muted); text-decoration: none; transition: all 0.2s;
            font-size: 14px; display: inline-flex; align-items: center; gap: 6px;
        }
        .footer-links a:hover { color: var(--info); padding-left: 4px; }
        .footer-links a .link-arrow {
            opacity: 0; transform: translateX(-4px); transition: all 0.2s; font-size: 12px;
        }
        .footer-links a:hover .link-arrow { opacity: 1; transform: translateX(0); }

        .footer-description {
            color: var(--text-muted); font-size: 14px; margin-top: 16px; line-height: 1.7;
        }
        .footer-social {
            display: flex; gap: 12px; margin-top: 20px;
        }
        .footer-social a {
            width: 36px; height: 36px; border-radius: 10px;
            border: 1px solid var(--border-light); background: var(--bg-white);
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 16px; transition: all 0.3s;
        }
        .footer-social a:hover {
            border-color: var(--border-hover); transform: translateY(-2px);
            background: var(--bg-white-hover);
        }

        .copyright {
            text-align: center; padding-top: 48px; color: var(--text-muted);
            font-size: 13px; margin-top: 40px; border-top: 1px solid var(--copyright-border);
        }

        /* Toast Notification */
        .toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: var(--bg-white-solid); backdrop-filter: blur(16px);
            border: 1px solid var(--border-light); border-radius: 12px;
            padding: 12px 24px; font-size: 14px; font-weight: 500;
            color: var(--text-main); z-index: 200;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
        }
        .toast.show { transform: translateX(-50%) translateY(0); }

        /* Animations */
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(6, 182, 212, 0); } 100% { box-shadow: 0 0 0 0 rgba(6, 182, 212, 0); } }

        /* ═══════════════════════════════════════════════════════
           RESPONSIVE
           ═══════════════════════════════════════════════════════ */
        @media (max-width: 768px) {
            .nav-center { display: none; }
            .nav-right .btn { display: none; }
            .mobile-menu-btn { display: flex; }
            .mobile-nav-overlay { display: block; }

            .hero h1 { font-size: 36px; letter-spacing: -1px; }
            .hero-cta { flex-direction: column; gap: 12px; align-items: center; }
            .hero-cta .btn { width: 100%; max-width: 280px; }
            .stats-strip { flex-direction: column; gap: 32px; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 40px; }
            .hero-card { width: 100%; max-width: 340px; }
            .about-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr; gap: 32px; }
            .lang-switch { display: none; }
        }
    </style>
</head>
<body>
    <!-- Background Ambient Orbs -->
    <div class="orb-1"></div>
    <div class="orb-2"></div>
    <div class="orb-3"></div>

    <!-- ═══════════════════════════════════════════════════════
         NAVIGATION
         ═══════════════════════════════════════════════════════ -->
    <nav class="navbar" id="navbar">
        <a href="/" class="logo">
            <div class="logo-icon">N</div>
            NexusBank
        </a>

        <div class="nav-center">
            <a href="#features">{{ __('Features') }}</a>
            <a href="#cards">{{ __('Smart Cards') }}</a>
            <a href="#loans">{{ __('Loans') }}</a>
            <a href="#security">{{ __('Security') }}</a>
            <a href="#about">{{ __('About') }}</a>
        </div>

        <div class="nav-right">
            <div class="lang-switch">
                <a href="{{ route('lang.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
                <span class="sep">|</span>
                <a href="{{ route('lang.switch', 'ckb') }}" class="{{ app()->getLocale() === 'ckb' ? 'active' : '' }}">CKB</a>
            </div>

            <button id="theme-toggle" class="theme-btn" aria-label="Toggle Theme">
                <span class="theme-icon-dark">☀️</span>
                <span class="theme-icon-light" style="display:none;">🌙</span>
            </button>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">{{ __('Sign In') }}</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">{{ __('Open Account') }}</a>
            @endauth

            <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Open Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    <!-- Mobile Navigation Overlay -->
    <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>

    <!-- Mobile Navigation Panel -->
    <div class="mobile-nav" id="mobile-nav">
        <a href="#features" class="mobile-nav-link">{{ __('Features') }}</a>
        <a href="#cards" class="mobile-nav-link">{{ __('Smart Cards') }}</a>
        <a href="#loans" class="mobile-nav-link">{{ __('Loans') }}</a>
        <a href="#security" class="mobile-nav-link">{{ __('Security') }}</a>
        <a href="#about" class="mobile-nav-link">{{ __('About') }}</a>
        <div class="mobile-nav-divider"></div>
        <div class="mobile-nav-actions">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary" style="text-align:center;">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline" style="text-align:center;">{{ __('Sign In') }}</a>
                <a href="{{ route('register') }}" class="btn btn-primary" style="text-align:center;">{{ __('Open Account') }}</a>
            @endauth
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         HERO SECTION
         ═══════════════════════════════════════════════════════ -->
    <section class="hero">
        <div style="max-width: 800px; margin: 0 auto; z-index: 1;">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                {{ __('Distributed Banking Architecture') }}
            </div>
            <h1>{{ __('Experience the') }} <span class="text-gradient">{{ __('Future of Banking') }}</span></h1>
            <p>{{ __('A beautifully designed, premium digital banking platform. Escrow transfers, secure smart cards, and intelligent loan insights at your fingertips.') }}</p>
            <div class="hero-cta">
                <a href="{{ route('register') }}" class="btn btn-primary" style="font-size: 17px; padding: 14px 36px;">{{ __('Get Started Now') }}</a>
                <a href="#features" class="btn btn-outline" style="font-size: 17px; padding: 14px 36px;">{{ __('Explore Features') }}</a>
            </div>

            <!-- 3D Card Mockup -->
            <div class="hero-card-wrapper">
                <div class="hero-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div class="card-chip"></div>
                        <span style="font-size: 20px; font-weight: 900; font-style: italic; color: var(--card-mockup-text);">NEXUS</span>
                    </div>
                    <div>
                        <div style="font-family: monospace; font-size: 22px; letter-spacing: 4px; margin-bottom: 12px; color: var(--card-mockup-text);">•••• •••• •••• 4289</div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--card-mockup-text-muted); text-transform: uppercase; letter-spacing: 1px;">
                            <span style="font-weight: 600;">{{ __('Premium Member') }}</span>
                            <span style="font-weight: 600;">12/28</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════
         STATS STRIP
         ═══════════════════════════════════════════════════════ -->
    <section class="stats-strip">
        <div class="stat-item">
            <span class="stat-val">$2.5B+</span>
            <span class="stat-label">{{ __('Processed Safely') }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-val">150+</span>
            <span class="stat-label">{{ __('Countries Supported') }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-val">0%</span>
            <span class="stat-label">{{ __('Hidden Fees') }}</span>
        </div>
        <div class="stat-item">
            <span class="stat-val">99.9%</span>
            <span class="stat-label">{{ __('Uptime Guarantee') }}</span>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════
         FEATURES GRID
         ═══════════════════════════════════════════════════════ -->
    <section id="features" class="features">
        <div class="section-header">
            <h2>{{ __('Designed for') }} <span class="text-gradient">{{ __('Excellence') }}</span></h2>
            <p>{{ __('Every feature has been meticulously crafted to provide you with the most seamless and secure financial experience available.') }}</p>
        </div>

        <div class="grid">
            <div class="feature-box">
                <div class="feature-icon">💸</div>
                <h3>{{ __('Escrow Transfers') }}</h3>
                <p>{{ __('Send money with total confidence. Funds are held securely until the recipient explicitly accepts the transfer, preventing accidental sending.') }}</p>
            </div>
            <div class="feature-box" id="cards">
                <div class="feature-icon">💳</div>
                <h3>{{ __('Smart Cards') }}</h3>
                <p>{{ __('Generate secure virtual and physical cards instantly. Features auto-generated unique PINs, custom spending limits, and one-tap freeze controls.') }}</p>
            </div>
            <div class="feature-box" id="loans">
                <div class="feature-icon">🏦</div>
                <h3>{{ __('Dynamic Loans') }}</h3>
                <p>{{ __('Access highly competitive loans backed directly by real-time bank reserve limits. Instant approvals driven by an intelligent capacity system.') }}</p>
            </div>
            <div class="feature-box" id="security">
                <div class="feature-icon">🛡️</div>
                <h3>{{ __('Heuristic Fraud Engine') }}</h3>
                <p>{{ __('Sleep easy knowing our 6-layer heuristic fraud detection engine monitors velocity, time anomalies, and transaction deviations 24/7.') }}</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">🪪</div>
                <h3>{{ __('Strict KYC Protocol') }}</h3>
                <p>{{ __('We maintain a safe ecosystem. Mandatory passport and national ID verification ensures platform integrity and regulatory compliance.') }}</p>
            </div>
            <div class="feature-box">
                <div class="feature-icon">🔔</div>
                <h3>{{ __('Actionable Notifications') }}</h3>
                <p>{{ __('Never miss a beat. Real-time, actionable notifications keep you updated on transfer requests, card activations, and vital security alerts.') }}</p>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════
         ABOUT SECTION
         ═══════════════════════════════════════════════════════ -->
    <section id="about" class="about-section">
        <div class="about-container">
            <h2>{{ __('Why') }} <span class="text-gradient">NexusBank</span>?</h2>
            <p class="about-text">{{ __('NexusBank operates a distributed banking architecture across multiple branches in Iraq\'s Kurdistan Region. We combine modern technology with local presence to deliver banking that truly works for you.') }}</p>

            <div class="about-grid">
                <div class="about-card">
                    <div class="about-card-icon">🏛️</div>
                    <h4>{{ __('Multi-Branch Network') }}</h4>
                    <p>{{ __('Branches in Erbil, Sulaimaniyah, and Duhok with full HQ synchronization for seamless service.') }}</p>
                </div>
                <div class="about-card">
                    <div class="about-card-icon">🔒</div>
                    <h4>{{ __('Bank-Grade Security') }}</h4>
                    <p>{{ __('AES-256 encryption, bcrypt password hashing, real-time fraud detection, and comprehensive audit logging.') }}</p>
                </div>
                <div class="about-card">
                    <div class="about-card-icon">🎫</div>
                    <h4>{{ __('Dedicated Support') }}</h4>
                    <p>{{ __('Submit support tickets and get direct responses from our team. We\'re here to help with any banking concern.') }}</p>
                </div>
            </div>

            <a href="{{ route('register') }}" class="btn btn-primary" style="font-size: 17px; padding: 14px 40px;">{{ __('Open Your Account Today') }}</a>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════
         FOOTER
         ═══════════════════════════════════════════════════════ -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col" style="grid-column: span 1;">
                <a href="/" class="logo" style="margin-bottom: 12px; display: inline-flex;">
                    <div class="logo-icon">N</div>
                    NexusBank
                </a>
                <p class="footer-description">{{ __('Elevating digital banking with state-of-the-art security, stunning interactive design, and powerful next-gen features.') }}</p>
                <div class="footer-social">
                    <a href="#" onclick="showToast('{{ __('Coming soon') }}'); return false;" title="Twitter">𝕏</a>
                    <a href="#" onclick="showToast('{{ __('Coming soon') }}'); return false;" title="LinkedIn">in</a>
                    <a href="#" onclick="showToast('{{ __('Coming soon') }}'); return false;" title="GitHub">⌨</a>
                </div>
            </div>
            <div class="footer-col">
                <h4>{{ __('Platform') }}</h4>
                <ul class="footer-links">
                    <li><a href="#features">{{ __('Escrow Transfers') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="#cards">{{ __('Cards Management') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="#loans">{{ __('Personal Loans') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="#security">{{ __('Fraud Security') }} <span class="link-arrow">→</span></a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>{{ __('Company') }}</h4>
                <ul class="footer-links">
                    <li><a href="#about">{{ __('About NexusBank') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="{{ route('register') }}">{{ __('Open Account') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="#" onclick="showToast('{{ __('Coming soon') }}'); return false;">{{ __('Careers') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="{{ route('register') }}">{{ __('Contact Us') }} <span class="link-arrow">→</span></a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>{{ __('Legal') }}</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('legal.privacy') }}">{{ __('Privacy Policy') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="{{ route('legal.terms') }}">{{ __('Terms of Service') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="{{ route('legal.cookies') }}">{{ __('Cookie Policy') }} <span class="link-arrow">→</span></a></li>
                    <li><a href="{{ route('legal.compliance') }}">{{ __('Compliance') }} <span class="link-arrow">→</span></a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; {{ date('Y') }} {{ __('NexusBank Platform. All rights reserved. Banking services are simulated for demonstration purposes.') }}
        </div>
    </footer>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        // ── Scroll Effect for Navbar ──
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        // Trigger on load in case page is already scrolled
        if (window.scrollY > 50) navbar.classList.add('scrolled');

        // ── Mobile Menu ──
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileNav = document.getElementById('mobile-nav');
        const mobileOverlay = document.getElementById('mobile-nav-overlay');

        function toggleMobileMenu() {
            mobileMenuBtn.classList.toggle('active');
            mobileNav.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
            document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
        }

        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
        mobileOverlay.addEventListener('click', toggleMobileMenu);

        // Close mobile menu when clicking a link
        document.querySelectorAll('.mobile-nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (mobileNav.classList.contains('active')) {
                    toggleMobileMenu();
                }
            });
        });

        // ── Interactive glow effect for feature boxes ──
        document.querySelectorAll('.feature-box').forEach(box => {
            box.addEventListener('mousemove', e => {
                const rect = box.getBoundingClientRect();
                box.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
                box.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
            });
            box.addEventListener('mouseleave', () => {
                box.style.setProperty('--mouse-x', `50%`);
                box.style.setProperty('--mouse-y', `50%`);
            });
        });

        // ── Theme Toggle ──
        const themeToggle = document.getElementById('theme-toggle');
        const themeIconDark = document.querySelector('.theme-icon-dark');
        const themeIconLight = document.querySelector('.theme-icon-light');

        function updateThemeIcons() {
            if (!themeIconDark || !themeIconLight) return;
            if (document.documentElement.getAttribute('data-theme') === 'light') {
                themeIconDark.style.display = 'none';
                themeIconLight.style.display = 'inline';
            } else {
                themeIconDark.style.display = 'inline';
                themeIconLight.style.display = 'none';
            }
        }

        if (themeToggle) {
            updateThemeIcons();
            themeToggle.addEventListener('click', () => {
                if (document.documentElement.getAttribute('data-theme') === 'light') {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                    localStorage.setItem('theme', 'light');
                }
                updateThemeIcons();
            });
        }

        // ── Toast Notification ──
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }
    </script>
</body>
</html>
