<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            var stored = null;
            try { stored = localStorage.getItem('themeMode'); } catch (e) {}
            document.documentElement.classList.toggle('dark', stored === 'dark');
        })();
    </script>
    <title>Mirai Study Hub – ITPEC &amp; JLPT Prep</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        .welcome-page * { box-sizing: border-box; }
        body.welcome-page {
            transition: background-color 0.4s, color 0.3s;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f8faf9;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }
        .dark body.welcome-page { background-color: #0f1412; }

        .welcome-grid-bg {
            position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background-image: linear-gradient(rgba(5,150,105,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(5,150,105,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .dark .welcome-grid-bg {
            background-image: linear-gradient(rgba(5,150,105,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(5,150,105,0.06) 1px, transparent 1px);
        }

        .welcome-scroll-progress {
            position: fixed; top: 0; left: 0; height: 3px;
            background: linear-gradient(90deg, #059669, #10b981);
            z-index: 999; width: 0%; transition: width 0.08s linear;
        }

        .welcome-sidebar {
            position: fixed; top: 0; left: 0; width: 260px; height: 100vh;
            background: transparent;
            backdrop-filter: none; -webkit-backdrop-filter: none;
            border-right: 1px solid rgba(229,231,235,0.10);
            z-index: 60; display: flex; flex-direction: column;
            padding: 1.5rem 1.25rem;
            transition: background 0.4s, border-color 0.4s, transform 0.4s cubic-bezier(0.22,0.61,0.36,1);
            overflow-y: auto;
        }
        .dark .welcome-sidebar {
            background: transparent;
            backdrop-filter: none; -webkit-backdrop-filter: none;
            border-right-color: rgba(255,255,255,0.04);
        }

        .welcome-sidebar-logo {
            display: flex; align-items: center; gap: 0.6rem; margin-bottom: 2rem;
            padding-bottom: 0.5rem; border-bottom: 1px solid rgba(229,231,235,0.10);
            flex-shrink: 0; cursor: default; transition: border-color 0.3s ease;
        }
        .dark .welcome-sidebar-logo { border-bottom-color: rgba(255,255,255,0.06); }
        .welcome-sidebar-logo .logo-icon {
            width: 36px; height: 36px; background: #059669; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 1.1rem;
            transition: transform 0.3s ease, background 0.3s ease; flex-shrink: 0;
        }
        .welcome-sidebar-logo:hover .logo-icon { transform: rotate(12deg); }
        .dark .welcome-sidebar-logo .logo-icon { background: #059669; }
        .welcome-sidebar-logo .logo-text {
            font-size: 1.25rem; font-weight: 700; letter-spacing: -0.025em;
            color: #111827; transition: color 0.3s ease;
        }
        .welcome-sidebar-logo .logo-text span { color: #059669; transition: color 0.3s ease; }
        .dark .welcome-sidebar-logo .logo-text { color: #fff; }
        .dark .welcome-sidebar-logo .logo-text span { color: #34d399; }
        .welcome-sidebar-logo:hover .logo-text { color: #059669; }
        .welcome-sidebar-logo:hover .logo-text span { color: #059669; }

        .welcome-sidebar.nav-hover .logo-text { color: #059669 !important; }
        .welcome-sidebar.nav-hover .logo-text span { color: #059669 !important; }
        .dark .welcome-sidebar.nav-hover .logo-text { color: #34d399 !important; }
        .dark .welcome-sidebar.nav-hover .logo-text span { color: #34d399 !important; }

        .welcome-sidebar-nav {
            flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 0.35rem;
        }
        .welcome-sidebar-nav a {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 0.9rem; border-radius: 10px; font-size: 0.875rem;
            font-weight: 500; color: #6b7280;
            transition: color 0.25s ease, transform 0.2s ease;
            text-decoration: none; position: relative; cursor: pointer; background: transparent;
        }
        .welcome-sidebar-nav a:hover {
            background: transparent; color: #059669; transform: translateX(4px);
        }
        .welcome-sidebar-nav a:hover i { color: #059669; }
        .welcome-sidebar-nav a .nav-indicator {
            position: absolute; right: 0.75rem; width: 6px; height: 6px;
            border-radius: 50%; background: transparent !important; transition: none;
        }
        .dark .welcome-sidebar-nav a { color: #fff; }
        .dark .welcome-sidebar-nav a:hover { background: transparent; color: #34d399; transform: translateX(4px); }
        .dark .welcome-sidebar-nav a:hover i { color: #34d399; }
        .welcome-sidebar-nav a i {
            width: 20px; text-align: center; font-size: 0.95rem;
            transition: color 0.25s ease, transform 0.3s ease; color: #6b7280;
        }
        .dark .welcome-sidebar-nav a i { color: #fff; }
        .welcome-sidebar-nav a:hover i { transform: scale(1.1); }

        .welcome-sidebar-footer {
            margin-top: auto; padding-top: 1rem;
            border-top: 1px solid rgba(229,231,235,0.10);
            display: flex; flex-direction: column; gap: 0.75rem; flex-shrink: 0;
        }
        .dark .welcome-sidebar-footer { border-top-color: rgba(255,255,255,0.06); }

        .welcome-theme-toggle-row {
            display: flex; align-items: center; gap: 0.65rem;
            padding: 0.5rem 0.75rem; border-radius: 10px; cursor: pointer;
            transition: background 0.25s ease, transform 0.2s ease; user-select: none;
        }
        .welcome-theme-toggle-row:hover { background: rgba(5,150,105,0.08); transform: translateX(2px); }
        .dark .welcome-theme-toggle-row:hover { background: rgba(52,211,153,0.10); }
        .welcome-theme-toggle-row i {
            font-size: 1rem; color: #6b7280;
            transition: color 0.3s ease, transform 0.3s ease;
            width: 20px; text-align: center;
        }
        .welcome-theme-toggle-row:hover i { transform: scale(1.1); }
        .welcome-theme-toggle-row .theme-label {
            font-size: 0.8rem; font-weight: 500; color: #4b5563;
            transition: color 0.3s ease; flex: 1;
        }
        .dark .welcome-theme-toggle-row .theme-label { color: #fff; }
        .dark .welcome-theme-toggle-row i { color: #fff; }

        .welcome-theme-toggle-row .toggle-track {
            width: 40px; height: 22px; background: #d1d5db;
            border-radius: 9999px; position: relative;
            transition: background 0.3s ease; flex-shrink: 0;
        }
        .dark .welcome-theme-toggle-row .toggle-track { background: #374151; }
        .welcome-theme-toggle-row .toggle-thumb {
            width: 18px; height: 18px; background: #fff;
            border-radius: 50%; position: absolute; top: 2px; left: 2px;
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), background 0.3s ease;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        }
        .welcome-theme-toggle-row .toggle-thumb.dark-thumb { background: #34d399; transform: translateX(18px); }
        .dark .welcome-theme-toggle-row .toggle-thumb { background: #34d399; transform: translateX(18px); }

        .welcome-sidebar-footer .btn-get-started {
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            padding: 0.6rem 1rem; background: #059669; color: #fff; border: none;
            border-radius: 10px; font-weight: 600; font-size: 0.85rem;
            transition: background 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            cursor: pointer; box-shadow: 0 4px 14px rgba(5,150,105,0.30);
            text-decoration: none; text-align: center;
        }
        .welcome-sidebar-footer .btn-get-started:hover { background: #047857; transform: translateY(-2px); box-shadow: 0 8px 28px rgba(5,150,105,0.45); }
        .welcome-sidebar-footer .btn-get-started:active { transform: scale(0.96); }

        .welcome-main-content { margin-left: 260px; min-height: 100vh; transition: margin-left 0.4s cubic-bezier(0.22,0.61,0.36,1); }

        .reveal { opacity: 0; transform: translateY(40px); transition: opacity 0.9s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.9s cubic-bezier(0.25,0.46,0.45,0.94); will-change: opacity, transform; }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .reveal-stagger > * { opacity: 0; transform: translateY(30px); transition: opacity 0.7s cubic-bezier(0.25,0.46,0.45,0.94), transform 0.7s cubic-bezier(0.25,0.46,0.45,0.94); will-change: opacity, transform; }
        .reveal-stagger.active > *:nth-child(1) { transition-delay: 0.05s; }
        .reveal-stagger.active > *:nth-child(2) { transition-delay: 0.12s; }
        .reveal-stagger.active > *:nth-child(3) { transition-delay: 0.19s; }
        .reveal-stagger.active > *:nth-child(4) { transition-delay: 0.26s; }
        .reveal-stagger.active > *:nth-child(5) { transition-delay: 0.33s; }
        .reveal-stagger.active > *:nth-child(6) { transition-delay: 0.40s; }
        .reveal-stagger.active > *:nth-child(7) { transition-delay: 0.47s; }
        .reveal-stagger.active > *:nth-child(8) { transition-delay: 0.54s; }
        .reveal-stagger.active > *:nth-child(9) { transition-delay: 0.61s; }
        .reveal-stagger.active > *:nth-child(10) { transition-delay: 0.68s; }
        .reveal-stagger.active > *:nth-child(11) { transition-delay: 0.75s; }
        .reveal-stagger.active > *:nth-child(12) { transition-delay: 0.82s; }
        .reveal-stagger.active > * { opacity: 1; transform: translateY(0); }
        .reveal-hero { opacity: 0; transform: translateY(50px); transition: opacity 1.2s cubic-bezier(0.22,0.61,0.36,1), transform 1.2s cubic-bezier(0.22,0.61,0.36,1); will-change: opacity, transform; }
        .reveal-hero.active { opacity: 1; transform: translateY(0); }

        .btn-primary { background-color: #059669; color: #fff; border: none; transition: background-color 0.3s ease, transform 0.15s ease, box-shadow 0.3s ease; box-shadow: 0 4px 14px rgba(5,150,105,0.30); cursor: pointer; }
        .btn-primary:hover { background-color: #047857; box-shadow: 0 8px 28px rgba(5,150,105,0.45); transform: translateY(-2px); }
        .btn-primary:active { transform: scale(0.96); box-shadow: 0 2px 8px rgba(5,150,105,0.20); transition-duration: 0.06s; }
        .btn-secondary { background-color: transparent; color: #1f2937; border: 1.5px solid #d1d5db; transition: background-color 0.3s ease, transform 0.15s ease, box-shadow 0.3s ease, border-color 0.3s ease, color 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.04); cursor: pointer; }
        .btn-secondary:hover { background-color: #f9fafb; border-color: #059669; color: #059669; box-shadow: 0 6px 20px rgba(5,150,105,0.12); transform: translateY(-2px); }
        .btn-secondary:active { transform: scale(0.96); box-shadow: 0 2px 6px rgba(0,0,0,0.06); transition-duration: 0.06s; }
        .btn-white { background-color: #fff; color: #059669; border: none; transition: background-color 0.3s ease, transform 0.15s ease, box-shadow 0.3s ease, color 0.3s ease; box-shadow: 0 4px 16px rgba(0,0,0,0.08); cursor: pointer; }
        .btn-white:hover { background-color: #f0fdf4; box-shadow: 0 8px 30px rgba(5,150,105,0.18); transform: translateY(-2px); }
        .btn-white:active { transform: scale(0.96); box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition-duration: 0.06s; }
        .btn-dark { background-color: #1f2937; color: #fff; border: none; transition: background-color 0.3s ease, transform 0.15s ease, box-shadow 0.3s ease; box-shadow: 0 4px 14px rgba(31,41,55,0.25); cursor: pointer; }
        .btn-dark:hover { background-color: #111827; box-shadow: 0 8px 28px rgba(31,41,55,0.35); transform: translateY(-2px); }
        .btn-dark:active { transform: scale(0.96); box-shadow: 0 2px 8px rgba(31,41,55,0.15); transition-duration: 0.06s; }

        .feature-card, .stat-card, .resource-card, .testimonial-card, .why-card { transition: transform 0.35s cubic-bezier(0.22,0.61,0.36,1), box-shadow 0.35s cubic-bezier(0.22,0.61,0.36,1), border-color 0.35s cubic-bezier(0.22,0.61,0.36,1); cursor: pointer; border: 1px solid rgba(5,150,105,0.50); }
        .feature-card:hover, .stat-card:hover, .resource-card:hover, .testimonial-card:hover, .why-card:hover { transform: translateY(-8px); box-shadow: 0 16px 40px -12px rgba(5,150,105,0.18); border-color: #059669; }
        .dark .feature-card, .dark .stat-card, .dark .resource-card, .dark .testimonial-card, .dark .why-card { border-color: rgba(52,211,153,0.50); background: rgba(26,38,34,0.70); }
        .dark .feature-card:hover, .dark .stat-card:hover, .dark .resource-card:hover, .dark .testimonial-card:hover, .dark .why-card:hover { border-color: #34d399; box-shadow: 0 16px 40px -12px rgba(52,211,153,0.25); }

        .hub-item { transition: transform 0.3s ease, background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease; cursor: pointer; border: 1px solid rgba(5,150,105,0.50); }
        .hub-item:hover { transform: translateX(6px) translateY(-2px); border-color: #059669; background-color: rgba(5,150,105,0.06); box-shadow: 0 4px 12px rgba(5,150,105,0.08); }
        .dark .hub-item { border-color: rgba(52,211,153,0.50); }
        .dark .hub-item:hover { border-color: #34d399; background-color: rgba(52,211,153,0.10); }

        .step-group .step-circle { transition: transform 0.3s ease, background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease; }
        .step-group:hover .step-circle { transform: scale(1.12) rotate(-4deg); background-color: #059669; color: #fff; box-shadow: 0 8px 24px rgba(5,150,105,0.30); }
        .dark .step-circle { background: rgba(26,38,34,0.70); border-color: rgba(255,255,255,0.10); color: #fff; }
        .dark .step-group:hover .step-circle { background-color: #059669; color: #fff; border-color: #059669; }

        .glass-card { background: rgba(255,255,255,0.60); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(5,150,105,0.50); }
        .dark .glass-card { background: rgba(26,38,34,0.60); border-color: rgba(52,211,153,0.50); }

        .resource-tag { background: rgba(5,150,105,0.08); padding: 0.2rem 0.75rem; border-radius: 9999px; font-size: 0.6rem; font-weight: 600; color: #059669; letter-spacing: 0.03em; display: inline-flex; align-items: center; gap: 0.3rem; }
        .dark .resource-tag { background: rgba(52,211,153,0.15); color: #34d399; }

        .badge-pulse { animation: pulse-glow 2.8s ease-in-out infinite; }
        @keyframes pulse-glow { 0%,100% { box-shadow: 0 0 0 0 rgba(5,150,105,0.12); } 50% { box-shadow: 0 0 0 10px rgba(5,150,105,0); } }

        .stat-number { display: inline-block; transition: transform 0.45s cubic-bezier(0.34,1.56,0.64,1); }
        .stat-number.pop { transform: scale(1.15); }
        .dark .stat-number { color: #34d399 !important; }

        .cta-glow { transition: box-shadow 0.4s ease, transform 0.3s ease; }
        .cta-glow:hover { box-shadow: 0 20px 60px rgba(5,150,105,0.25); transform: translateY(-3px); }
        .cta-bg { background: linear-gradient(135deg, #059669 0%, #047857 100%); }

        .footer-link { position: relative; transition: color 0.3s ease; cursor: pointer; }
        .footer-link::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 1.5px; background: #059669; transition: width 0.3s ease; }
        .footer-link:hover { color: #059669; }
        .footer-link:hover::after { width: 100%; }
        .dark .footer-link { color: #fff; }
        .dark .footer-link:hover { color: #34d399; }

        .icon-spin { transition: transform 0.4s ease; }
        .group:hover .icon-spin { transform: rotate(12deg) scale(1.05); }

        .welcome-mobile-hamburger { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 70; background: rgba(255,255,255,0.30); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(229,231,235,0.10); border-radius: 12px; padding: 0.6rem 0.9rem; font-size: 1.25rem; color: #1f2937; cursor: pointer; transition: background 0.3s, box-shadow 0.3s; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .welcome-mobile-hamburger:hover { background: rgba(255,255,255,0.50); box-shadow: 0 4px 20px rgba(0,0,0,0.10); }
        .welcome-mobile-hamburger .label { font-size: 0.75rem; font-weight: 500; letter-spacing: 0.02em; color: #4b5563; }
        .dark .welcome-mobile-hamburger { background: rgba(15,20,18,0.30); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-color: rgba(255,255,255,0.06); color: #fff; }
        .dark .welcome-mobile-hamburger:hover { background: rgba(26,38,34,0.50); }
        .dark .welcome-mobile-hamburger .label { color: #fff; }

        .welcome-sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.25); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); z-index: 55; opacity: 0; transition: opacity 0.35s ease; pointer-events: none; }
        .welcome-sidebar-overlay.open { opacity: 1; pointer-events: all; }

        @media (max-width: 1024px) {
            .welcome-sidebar { transform: translateX(-100%); width: 280px; border-right: none; box-shadow: 4px 0 40px rgba(0,0,0,0.08); background: transparent; backdrop-filter: none; -webkit-backdrop-filter: none; }
            .dark .welcome-sidebar { background: transparent; backdrop-filter: none; -webkit-backdrop-filter: none; }
            .welcome-sidebar.open { transform: translateX(0); }
            .welcome-sidebar-overlay { display: block; }
            .welcome-main-content { margin-left: 0; }
            .welcome-mobile-hamburger { display: flex; align-items: center; gap: 0.5rem; }
            header { padding-top: 0 !important; min-height: 90vh !important; }
        }
        @media (max-width: 640px) {
            .welcome-sidebar { width: 260px; }
            .welcome-mobile-hamburger .label { display: none; }
            .welcome-mobile-hamburger { padding: 0.5rem 0.7rem; font-size: 1.1rem; }
            header { min-height: 92vh !important; padding-top: 0 !important; }
        }

        .welcome-sidebar::-webkit-scrollbar { width: 3px; }
        .welcome-sidebar::-webkit-scrollbar-track { background: transparent; }
        .welcome-sidebar::-webkit-scrollbar-thumb { background: rgba(5,150,105,0.2); border-radius: 10px; }
        .welcome-sidebar::-webkit-scrollbar-thumb:hover { background: rgba(5,150,105,0.4); }
        .dark .welcome-sidebar::-webkit-scrollbar-thumb { background: rgba(52,211,153,0.15); }
        .dark .welcome-sidebar::-webkit-scrollbar-thumb:hover { background: rgba(52,211,153,0.3); }

        .dark .text-gray-900, .dark .text-gray-800, .dark .text-gray-700, .dark .text-gray-600, .dark .text-gray-500, .dark .text-gray-400, .dark .text-gray-300, .dark .text-gray-200, .dark .text-gray-100, .dark .text-white { color: #fff !important; }
        .dark .text-emerald-600, .dark .text-emerald-700, .dark .text-emerald-500, .dark .text-emerald-400, .dark .text-emerald-300 { color: #34d399 !important; }
        .dark .text-emerald-200 { color: #6ee7b7 !important; }
        .dark .bg-emerald-50\/60 { background: rgba(52,211,153,0.10); }
        .dark .border-green-100\/60 { border-color: rgba(52,211,153,0.15); }
        .dark .text-emerald-700 { color: #34d399 !important; }
        .dark .bg-green-50\/60 { background: rgba(52,211,153,0.08); }
        .dark .bg-emerald-50\/20 { background: rgba(52,211,153,0.10); }
        .dark .bg-gray-50\/30 { background: rgba(255,255,255,0.05); }
        .dark .border-gray-100\/50 { border-color: rgba(255,255,255,0.06); }
        .dark .bg-emerald-50\/10 { background: rgba(52,211,153,0.05); }
        .dark .bg-emerald-50\/5 { background: rgba(52,211,153,0.03); }
        .dark .bg-white\/70 { background: rgba(26,38,34,0.70); }
        .dark .bg-white\/40 { background: rgba(15,20,18,0.50); }
        .dark .btn-secondary { color: #fff; border-color: rgba(255,255,255,0.3); }
        .dark .btn-secondary:hover { background-color: rgba(255,255,255,0.05); border-color: #34d399; color: #34d399; }
        .dark .btn-white { background-color: #fff; color: #059669; }
        .dark .btn-white:hover { background-color: #e5e7eb; color: #047857; }
        .dark .btn-dark { background-color: #1f2937; color: #fff; }
        .dark .btn-dark:hover { background-color: #374151; }
        .dark .btn-primary { background-color: #059669; color: #fff; }
        .dark .btn-primary:hover { background-color: #047857; }
        .dark .hub-item .text-gray-500 { color: #fff !important; }
        .dark .hub-item .text-gray-800 { color: #fff !important; }
        .dark footer .text-gray-500 { color: #fff !important; }
        .dark footer .text-gray-800 { color: #fff !important; }
        .dark footer .border-t { border-color: rgba(255,255,255,0.06); }
        .dark footer .bg-white\/40 { background: rgba(15,20,18,0.50); }
    </style>
</head>
<body class="welcome-page">
    <div class="welcome-grid-bg"></div>
    <div class="welcome-scroll-progress" id="scrollProgress"></div>

    <button class="welcome-mobile-hamburger" id="hamburgerBtn" aria-label="Toggle menu">
        <i data-lucide="menu"></i>
        <span class="label">Menu</span>
    </button>

    <div class="welcome-sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="welcome-sidebar" id="sidebar">
        {{-- <div class="welcome-sidebar-logo" id="sidebarLogo">
            <img src="{{ asset('images/logo.png') }}" alt="MiraiStudy" class="w-12 h-12 object-contain">
            <div class="logo-text">Mirai<span>Study</span></div>
        </div> --}}
        {{-- Logo --}}
        <a href="/" class="mb-7 flex items-center justify-center gap-2.5">
            <span class="h-9 w-9 bg-gradient-to-br from-mirai-lime to-mirai-dark" role="img" aria-label="MiraiStudy"
                    style="-webkit-mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;
                                    mask: url('{{ asset('images/logo-mask.png') }}') center / contain no-repeat;"></span>
            <span class="bg-gradient-to-r from-mirai-lime to-mirai-dark bg-clip-text text-2xl font-semibold text-transparent">MiraiStudy</span>
        </a>

        <nav class="welcome-sidebar-nav" id="sidebarNav">
            <a href="{{ route('feed.index') }}"><i data-lucide="home"></i><span>Feed</span><span class="nav-indicator"></span></a>
            <a href="{{ route('exams.index') }}"><i data-lucide="file-text"></i><span>Papers</span><span class="nav-indicator"></span></a>
            <a href="{{ route('quiz.index') }}"><i data-lucide="brain"></i><span>Quiz</span><span class="nav-indicator"></span></a>
            <a href="{{ route('timer.index') }}"><i data-lucide="clock"></i><span>Focus</span><span class="nav-indicator"></span></a>
            <a href="https://github.com/kokaunghtet/mirai-study.git" target="_blank"><i data-lucide="git-branch"></i><span>GitHub</span><span class="nav-indicator"></span></a>
        </nav>

        <div class="welcome-sidebar-footer">
            <div class="welcome-theme-toggle-row" id="themeToggleRow">
                <i data-lucide="moon" id="themeIcon"></i>
                <span class="theme-label" id="themeLabel">Dark Mode</span>
                <div class="toggle-track">
                    <div class="toggle-thumb" id="toggleThumb"></div>
                </div>
            </div>

            @auth
                <a href="{{ route('feed.index') }}" class="btn-get-started">
                    <i data-lucide="rocket"></i> Go to Feed
                </a>
            @else
                <a href="{{ route('register') }}" class="btn-get-started">
                    <i data-lucide="rocket"></i> Get Started
                </a>
            @endauth
        </div>
    </aside>

    <main class="welcome-main-content">

        {{-- HERO --}}
        <header class="max-w-7xl mx-auto px-6 min-h-screen lg:min-h-[calc(100vh-20px)] grid grid-cols-1 lg:grid-cols-12 gap-12 items-center py-0">
            <div class="lg:col-span-7 space-y-6 reveal-hero self-center" id="hero-reveal">
                <div class="inline-flex items-center space-x-2 bg-green-50/60 backdrop-blur-sm border border-green-100/60 rounded-full px-3 py-1 text-xs font-medium text-emerald-700 transition-transform duration-300 hover:scale-105 badge-pulse">
                    <i data-lucide="circle-check" class="w-3 h-3 animate-pulse"></i>
                    <span>Smart Study Router – IT &amp; Japanese</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight leading-tight">
                    <span class="text-gray-900">One Platform.</span><br />
                    <span class="text-emerald-600">ITPEC &amp; JLPT.</span><br />
                    <span class="text-gray-900">Zero Obstacles.</span>
                </h1>

                <p class="text-base sm:text-lg text-gray-600 max-w-xl leading-relaxed">
                    A unified hub for ITPEC (IP / FE) and JLPT (N5 – N1) past papers,
                    smart analytics, and structured practice — all completely free.
                    Start your IT and Japanese journey today.
                </p>

                <div class="flex flex-wrap gap-4 pt-2">
                    <a href="{{ route('register') }}" class="btn-primary text-white font-medium px-6 py-3.5 rounded-xl inline-flex items-center gap-2 text-sm sm:text-base">
                        <i data-lucide="graduation-cap" class="w-5 h-5 icon-spin"></i>
                        <span>Start Learning Free</span>
                    </a>
                    <a href="https://github.com/kokaunghtet/mirai-study.git" target="_blank" class="btn-secondary font-medium px-6 py-3.5 rounded-xl inline-flex items-center gap-2 text-sm sm:text-base">
                        <i data-lucide="git-branch" class="w-5 h-5"></i>
                        <span>View on GitHub</span>
                    </a>
                </div>

                <div class="flex items-center gap-6 pt-2 text-xs text-gray-500 flex-wrap">
                    <span class="flex items-center gap-1.5"><i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500"></i> 12K+ students</span>
                    <span class="flex items-center gap-1.5"><i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500"></i> 240+ past exams</span>
                    <span class="flex items-center gap-1.5"><i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-500"></i> 97% satisfaction</span>
                </div>
            </div>

            <div class="lg:col-span-5 glass-card rounded-3xl p-8 shadow-lg relative overflow-hidden group transition-all duration-500 hover:shadow-xl hover:-translate-y-1 reveal-hero self-center" style="transition-delay:0.2s;">
                <div class="absolute inset-0 opacity-[0.04] bg-[linear-gradient(to_right,#059669_1px,transparent_1px),linear-gradient(to_bottom,#059669_1px,transparent_1px)] bg-[size:24px_24px]"></div>

                <div class="relative z-10 space-y-6">
                    <div class="text-center font-bold text-emerald-600 uppercase tracking-widest text-xs transition-all duration-300 group-hover:tracking-xl">Available Core Hubs</div>

                    <div class="hub-item p-4 bg-green-50/40 backdrop-blur-sm border border-green-100/60 rounded-2xl flex items-center justify-between transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-emerald-600 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-sm transition-transform duration-300">
                                <i data-lucide="code" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">ITPEC Prep</h4>
                                <p class="text-xs text-gray-500">IP Passport &amp; FE Fundamental</p>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-emerald-600 transform transition-transform duration-300 group-hover:translate-x-1"></i>
                    </div>

                    <div class="hub-item p-4 bg-emerald-50/20 backdrop-blur-sm border border-emerald-100/60 rounded-2xl flex items-center justify-between transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-emerald-700 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-sm transition-transform duration-300">
                                <span class="text-lg font-bold">あ</span>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">JLPT Bank</h4>
                                <p class="text-xs text-gray-500">From N5 up to N1</p>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-emerald-600 transform transition-transform duration-300 group-hover:translate-x-1"></i>
                    </div>

                    <div class="hub-item p-4 bg-gray-50/30 backdrop-blur-sm border border-gray-100/60 rounded-2xl flex items-center justify-between transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gray-700 text-white rounded-xl flex items-center justify-center text-xl font-bold shadow-sm transition-transform duration-300">
                                <i data-lucide="monitor" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Analytics Hub</h4>
                                <p class="text-xs text-gray-500">Progress &amp; performance insights</p>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-emerald-600 transform transition-transform duration-300 group-hover:translate-x-1"></i>
                    </div>
                </div>
            </div>
        </header>

        {{-- WHY CHOOSE US --}}
        <section id="why-choose" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100/30 reveal">
            <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                <span class="text-emerald-600 text-xs font-semibold tracking-widest uppercase">Why Mirai Study</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                    Designed for <span class="text-emerald-600">Serious Learners</span>
                </h2>
                <p class="text-gray-500 text-sm">Every feature is built to help you master ITPEC and JLPT with confidence</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 reveal-stagger" id="why-grid">
                <div class="why-card glass-card p-6 rounded-2xl shadow-sm text-center transition-all duration-300">
                    <div class="w-14 h-14 bg-emerald-50/60 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4"><i data-lucide="brain" class="w-7 h-7"></i></div>
                    <h4 class="font-bold text-gray-800">Smart Practice</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Adaptive question selection that targets your weak areas for efficient study.</p>
                </div>
                <div class="why-card glass-card p-6 rounded-2xl shadow-sm text-center transition-all duration-300">
                    <div class="w-14 h-14 bg-emerald-50/60 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4"><i data-lucide="clock" class="w-7 h-7"></i></div>
                    <h4 class="font-bold text-gray-800">Real Timers</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Simulate exam conditions with built-in timers for every section and level.</p>
                </div>
                <div class="why-card glass-card p-6 rounded-2xl shadow-sm text-center transition-all duration-300">
                    <div class="w-14 h-14 bg-emerald-50/60 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4"><i data-lucide="file-text" class="w-7 h-7"></i></div>
                    <h4 class="font-bold text-gray-800">Detailed Explanations</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Every question comes with a clear explanation and reference to study materials.</p>
                </div>
                <div class="why-card glass-card p-6 rounded-2xl shadow-sm text-center transition-all duration-300">
                    <div class="w-14 h-14 bg-emerald-50/60 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4"><i data-lucide="users" class="w-7 h-7"></i></div>
                    <h4 class="font-bold text-gray-800">Community Driven</h4>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">Join a growing community of learners sharing tips, resources, and motivation.</p>
                </div>
            </div>
        </section>

        {{-- FEATURES --}}
        <section id="features" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100/30 reveal">
            <div class="text-center max-w-xl mx-auto space-y-3 mb-16">
                <span class="text-emerald-600 text-xs font-semibold tracking-widest uppercase">Core Features</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                    Everything You <span class="text-emerald-600">Need to Succeed</span>
                </h2>
                <p class="text-gray-500 text-sm">Free tools and resources carefully curated for ITPEC &amp; JLPT candidates</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 reveal-stagger" id="features-grid">
                <div class="feature-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300 space-y-3">
                    <div class="w-10 h-10 bg-emerald-50/60 text-emerald-600 rounded-xl flex items-center justify-center text-lg"><i data-lucide="book-open" class="w-5 h-5"></i></div>
                    <h3 class="font-bold text-gray-800">Past Paper Engine</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Browse and practice ITPEC &amp; JLPT past questions organized by year and level.</p>
                    <span class="inline-block text-[10px] font-semibold text-emerald-600 bg-emerald-50/60 px-2 py-0.5 rounded-full">1,200+ questions</span>
                </div>
                <div class="feature-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300 space-y-3">
                    <div class="w-10 h-10 bg-emerald-50/60 text-emerald-600 rounded-xl flex items-center justify-center text-lg"><i data-lucide="clock" class="w-5 h-5"></i></div>
                    <h3 class="font-bold text-gray-800">Exam Simulation</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Take timed practice tests that mirror the real exam environment — pressure and all.</p>
                    <span class="inline-block text-[10px] font-semibold text-emerald-600 bg-emerald-50/60 px-2 py-0.5 rounded-full">Full-length mocks</span>
                </div>
                <div class="feature-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300 space-y-3">
                    <div class="w-10 h-10 bg-emerald-50/60 text-emerald-600 rounded-xl flex items-center justify-center text-lg"><i data-lucide="languages" class="w-5 h-5"></i></div>
                    <h3 class="font-bold text-gray-800">JLPT Vocabulary Bank</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Master essential vocabulary from N5 to N1 with curated word lists and quizzes.</p>
                    <span class="inline-block text-[10px] font-semibold text-emerald-600 bg-emerald-50/60 px-2 py-0.5 rounded-full">2,500+ words</span>
                </div>
                <div class="feature-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300 space-y-3">
                    <div class="w-10 h-10 bg-emerald-50/60 text-emerald-600 rounded-xl flex items-center justify-center text-lg"><i data-lucide="bar-chart-2" class="w-5 h-5"></i></div>
                    <h3 class="font-bold text-gray-800">Performance Analytics</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Track your progress, identify weak areas, and get personalized improvement tips.</p>
                    <span class="inline-block text-[10px] font-semibold text-emerald-600 bg-emerald-50/60 px-2 py-0.5 rounded-full">Visual dashboards</span>
                </div>
                <div class="feature-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300 space-y-3">
                    <div class="w-10 h-10 bg-emerald-50/60 text-emerald-600 rounded-xl flex items-center justify-center text-lg"><i data-lucide="rotate-ccw" class="w-5 h-5"></i></div>
                    <h3 class="font-bold text-gray-800">Instant Feedback</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Get detailed answer explanations and references to study materials instantly.</p>
                    <span class="inline-block text-[10px] font-semibold text-emerald-600 bg-emerald-50/60 px-2 py-0.5 rounded-full">Learn as you go</span>
                </div>
                <div class="feature-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300 space-y-3">
                    <div class="w-10 h-10 bg-emerald-50/60 text-emerald-600 rounded-xl flex items-center justify-center text-lg"><i data-lucide="smartphone" class="w-5 h-5"></i></div>
                    <h3 class="font-bold text-gray-800">Mobile Friendly</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Study on the go with a fully responsive design that works on any device.</p>
                    <span class="inline-block text-[10px] font-semibold text-emerald-600 bg-emerald-50/60 px-2 py-0.5 rounded-full">Anywhere, anytime</span>
                </div>
            </div>
        </section>

        {{-- HOW IT WORKS --}}
        <section id="how-it-works" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100/30 bg-emerald-50/10 rounded-3xl mb-8 reveal">
            <div class="max-w-xl mx-auto space-y-3 mb-14 text-center">
                <span class="text-emerald-600 text-xs font-semibold tracking-widest uppercase">Getting Started</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                    How to <span class="text-emerald-600">Get Started</span>
                </h2>
                <p class="text-gray-500 text-sm">Three simple steps to begin your preparation journey</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 reveal-stagger" id="steps-grid">
                <div class="step-group space-y-3 group cursor-pointer text-center">
                    <div class="step-circle w-14 h-14 bg-white/70 text-emerald-600 border-2 border-emerald-300/60 shadow-sm rounded-full flex items-center justify-center font-bold text-xl mx-auto transition-all duration-300">1</div>
                    <h4 class="font-bold text-gray-800 transition-colors group-hover:text-emerald-600">Choose Your Path</h4>
                    <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">Pick ITPEC (IP / FE) or JLPT (N5 – N1) — whatever fits your goal.</p>
                </div>
                <div class="step-group space-y-3 group cursor-pointer text-center">
                    <div class="step-circle w-14 h-14 bg-white/70 text-emerald-600 border-2 border-emerald-300/60 shadow-sm rounded-full flex items-center justify-center font-bold text-xl mx-auto transition-all duration-300">2</div>
                    <h4 class="font-bold text-gray-800 transition-colors group-hover:text-emerald-600">Practice Past Exams</h4>
                    <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">Work through real past papers with timers and instant grading.</p>
                </div>
                <div class="step-group space-y-3 group cursor-pointer text-center">
                    <div class="step-circle w-14 h-14 bg-white/70 text-emerald-600 border-2 border-emerald-300/60 shadow-sm rounded-full flex items-center justify-center font-bold text-xl mx-auto transition-all duration-300">3</div>
                    <h4 class="font-bold text-gray-800 transition-colors group-hover:text-emerald-600">Analyze &amp; Improve</h4>
                    <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">Review your scores, dive into explanations, and sharpen your weak spots.</p>
                </div>
            </div>
        </section>

        {{-- RESOURCES --}}
        <section id="resources" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100/30 reveal">
            <div class="text-center max-w-xl mx-auto space-y-3 mb-14">
                <span class="text-emerald-600 text-xs font-semibold tracking-widest uppercase">Featured Resources</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                    Latest <span class="text-emerald-600">Exam Updates</span>
                </h2>
                <p class="text-gray-500 text-sm">Freshly added past papers and study materials to keep you ahead</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 reveal-stagger" id="resources-grid">
                <div class="resource-card glass-card rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                    <div class="p-5 space-y-3">
                        <span class="resource-tag"><i data-lucide="calendar" class="w-3 h-3"></i> March 2026</span>
                        <h4 class="font-bold text-gray-800">ITPEC IP Passport – Spring 2026</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">Full set of 80 questions with detailed answer keys and explanations.</p>
                        <a href="#" class="inline-flex items-center text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors gap-1 group">Start Practice <i data-lucide="arrow-right" class="w-3 h-3 transition-transform duration-300 group-hover:translate-x-1"></i></a>
                    </div>
                </div>
                <div class="resource-card glass-card rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                    <div class="p-5 space-y-3">
                        <span class="resource-tag"><i data-lucide="calendar" class="w-3 h-3"></i> February 2026</span>
                        <h4 class="font-bold text-gray-800">JLPT N3 – Vocabulary Drill</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">300 essential N3 words with example sentences and audio pronunciation.</p>
                        <a href="#" class="inline-flex items-center text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors gap-1 group">Start Practice <i data-lucide="arrow-right" class="w-3 h-3 transition-transform duration-300 group-hover:translate-x-1"></i></a>
                    </div>
                </div>
                <div class="resource-card glass-card rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                    <div class="p-5 space-y-3">
                        <span class="resource-tag"><i data-lucide="calendar" class="w-3 h-3"></i> January 2026</span>
                        <h4 class="font-bold text-gray-800">ITPEC FE – Algorithm Practice</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">50 algorithm and data structure questions with step-by-step solutions.</p>
                        <a href="#" class="inline-flex items-center text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors gap-1 group">Start Practice <i data-lucide="arrow-right" class="w-3 h-3 transition-transform duration-300 group-hover:translate-x-1"></i></a>
                    </div>
                </div>
                <div class="resource-card glass-card rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                    <div class="p-5 space-y-3">
                        <span class="resource-tag"><i data-lucide="calendar" class="w-3 h-3"></i> December 2025</span>
                        <h4 class="font-bold text-gray-800">JLPT N2 – Reading Comp.</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">20 long-form reading passages with question sets and grammar notes.</p>
                        <a href="#" class="inline-flex items-center text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors gap-1 group">Start Practice <i data-lucide="arrow-right" class="w-3 h-3 transition-transform duration-300 group-hover:translate-x-1"></i></a>
                    </div>
                </div>
                <div class="resource-card glass-card rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                    <div class="p-5 space-y-3">
                        <span class="resource-tag"><i data-lucide="calendar" class="w-3 h-3"></i> November 2025</span>
                        <h4 class="font-bold text-gray-800">ITPEC IP – Mock Exam #3</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">Full 90-minute simulation with score breakdown and time analysis.</p>
                        <a href="#" class="inline-flex items-center text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors gap-1 group">Start Practice <i data-lucide="arrow-right" class="w-3 h-3 transition-transform duration-300 group-hover:translate-x-1"></i></a>
                    </div>
                </div>
                <div class="resource-card glass-card rounded-2xl shadow-sm overflow-hidden transition-all duration-300">
                    <div class="p-5 space-y-3">
                        <span class="resource-tag"><i data-lucide="calendar" class="w-3 h-3"></i> October 2025</span>
                        <h4 class="font-bold text-gray-800">JLPT N5 – Grammar Mastery</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">100 essential N5 grammar points with quizzes and example sentences.</p>
                        <a href="#" class="inline-flex items-center text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors gap-1 group">Start Practice <i data-lucide="arrow-right" class="w-3 h-3 transition-transform duration-300 group-hover:translate-x-1"></i></a>
                    </div>
                </div>
            </div>
        </section>

        {{-- TESTIMONIALS --}}
        <section id="testimonials" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100/30 bg-emerald-50/5 rounded-3xl mb-8 reveal">
            <div class="text-center max-w-xl mx-auto space-y-3 mb-14">
                <span class="text-emerald-600 text-xs font-semibold tracking-widest uppercase">Testimonials</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                    What Our <span class="text-emerald-600">Learners Say</span>
                </h2>
                <p class="text-gray-500 text-sm">Real stories from students who achieved their goals with Mirai Study</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 reveal-stagger" id="testimonials-grid">
                <div class="testimonial-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300">
                    <div class="flex items-center gap-1 text-emerald-500 mb-3">
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">"Mirai Study Hub helped me pass the ITPEC FE on my first try. The past papers and explanations were incredibly clear and well-organized."</p>
                    <div class="flex items-center gap-3 mt-4">
                        <div class="w-10 h-10 bg-emerald-100/60 rounded-full flex items-center justify-center text-emerald-700 font-bold text-sm">TK</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Thura Kyaw</p>
                            <p class="text-xs text-gray-400">ITPEC FE Passed, 2025</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300">
                    <div class="flex items-center gap-1 text-emerald-500 mb-3">
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">"I used the JLPT N2 vocabulary bank and reading comprehension drills. Scored 142/180 — way beyond my expectation. Highly recommend!"</p>
                    <div class="flex items-center gap-3 mt-4">
                        <div class="w-10 h-10 bg-emerald-100/60 rounded-full flex items-center justify-center text-emerald-700 font-bold text-sm">YN</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Yuki Nakamura</p>
                            <p class="text-xs text-gray-400">JLPT N2 Passed, 2025</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300">
                    <div class="flex items-center gap-1 text-emerald-500 mb-3">
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                        <i data-lucide="star" class="w-3 h-3 fill-current"></i>
                    </div>
                    <p class="text-sm text-gray-600 leading-relaxed">"The exam simulation mode is a game-changer. I felt fully prepared for the time pressure and question formats. Absolutely love this platform."</p>
                    <div class="flex items-center gap-3 mt-4">
                        <div class="w-10 h-10 bg-emerald-100/60 rounded-full flex items-center justify-center text-emerald-700 font-bold text-sm">AM</div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Aung Myat</p>
                            <p class="text-xs text-gray-400">ITPEC IP &amp; JLPT N4</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- STATS --}}
        <section id="stats" class="max-w-7xl mx-auto px-6 py-20 border-t border-gray-100/30 reveal">
            <div class="text-center max-w-xl mx-auto space-y-3 mb-14">
                <span class="text-emerald-600 text-xs font-semibold tracking-widest uppercase">Community Impact</span>
                <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                    Trusted by <span class="text-emerald-600">Learners Worldwide</span>
                </h2>
                <p class="text-gray-500 text-sm">Real numbers from our growing community</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center reveal-stagger" id="stats-grid">
                <div class="stat-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300">
                    <div class="stat-number text-3xl font-extrabold text-emerald-600">12K+</div>
                    <p class="text-sm text-gray-500 mt-1">Active Students</p>
                </div>
                <div class="stat-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300">
                    <div class="stat-number text-3xl font-extrabold text-emerald-600">240+</div>
                    <p class="text-sm text-gray-500 mt-1">Past Exams</p>
                </div>
                <div class="stat-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300">
                    <div class="stat-number text-3xl font-extrabold text-emerald-600">97%</div>
                    <p class="text-sm text-gray-500 mt-1">Satisfaction Rate</p>
                </div>
                <div class="stat-card glass-card p-6 rounded-2xl shadow-sm transition-all duration-300">
                    <div class="stat-number text-3xl font-extrabold text-emerald-600">4.9★</div>
                    <p class="text-sm text-gray-500 mt-1">Average Rating</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="max-w-7xl mx-auto px-6 pb-20 reveal">
            <div class="cta-bg rounded-3xl px-8 py-16 text-center text-white shadow-xl transition-all duration-300 cta-glow">
                <h2 class="text-3xl font-bold tracking-tight text-white">
                    Ready to Take <span class="text-emerald-200">the Next Step?</span>
                </h2>
                <p class="text-emerald-50/80 max-w-md mx-auto mt-3 text-sm leading-relaxed">Join thousands of learners and start mastering ITPEC &amp; JLPT today — completely free.</p>
                <div class="flex flex-wrap justify-center gap-4 mt-8">
                    <a href="{{ route('register') }}" class="btn-white font-semibold px-8 py-3.5 rounded-xl inline-flex items-center gap-2 text-sm sm:text-base">
                        <i data-lucide="rocket" class="w-5 h-5"></i>Launch Hub
                    </a>
                    <a href="#" class="btn-dark bg-emerald-700/40 text-white font-semibold px-8 py-3.5 rounded-xl inline-flex items-center gap-2 border border-white/20 text-sm sm:text-base hover:bg-emerald-700/60">
                        <i data-lucide="circle-help" class="w-5 h-5"></i>Learn More
                    </a>
                </div>
            </div>
        </section>

        {{-- BUILDERS --}}
        <section class="py-16 px-6">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">Builders</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-8">The people behind MiraiStudy.</p>
                <div class="flex flex-wrap justify-center gap-3">
                    @php
                        $builders = [
                            'Kaung Htet',
                            'Zwe Nyi Nyi Naing',
                            'Aent Zin Ko',
                            'Soe Yi Naing',
                            'Arkar Moe Myint',
                            'Shoon Lae Myint Myat',
                            'Lynn Latt Khay',
                            'Su Hanni Thit',
                            'Ei Thandar Aung',
                            'Su Ya Da Nar',
                            'Wutt Yee Thin',
                        ];
                    @endphp
                    @foreach ($builders as $builder)
                        <span class="inline-flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700/40 rounded-full px-5 py-2.5 text-sm font-medium text-emerald-800 dark:text-emerald-200 hover:bg-emerald-100 dark:hover:bg-emerald-800/40 transition-colors">
                            <i data-lucide="user" class="w-4 h-4 text-emerald-500"></i>{{ $builder }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- FOOTER --}}
        <footer class="border-t border-gray-200/30">
            <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8 text-sm">
                <div>
                    <div class="flex items-center space-x-2 group cursor-pointer mb-3">
                        <div class="w-7 h-7 bg-emerald-600 rounded-lg flex items-center justify-center text-white font-bold text-xs transform transition-transform duration-300 group-hover:rotate-12">M</div>
                        <span class="font-bold tracking-tight text-gray-900">Mirai<span class="text-emerald-600">Study</span></span>
                    </div>
                    <p class="text-gray-500 text-xs max-w-xs leading-relaxed">Free, open-source platform for ITPEC and JLPT exam preparation.</p>
                    <div class="flex space-x-4 mt-4">
                        <a href="#" class="text-gray-400 hover:text-emerald-600 transition-colors text-lg"><i data-lucide="git-branch" class="w-5 h-5"></i></a>
                        <a href="#" class="text-gray-400 hover:text-emerald-600 transition-colors text-lg"><i data-lucide="globe" class="w-5 h-5"></i></a>
                        <a href="#" class="text-gray-400 hover:text-emerald-600 transition-colors text-lg"><i data-lucide="play-circle" class="w-5 h-5"></i></a>
                        <a href="#" class="text-gray-400 hover:text-emerald-600 transition-colors text-lg"><i data-lucide="message-circle" class="w-5 h-5"></i></a>
                    </div>
                </div>
                <div>
                    <h5 class="font-bold text-gray-800 mb-3">Platform</h5>
                    <ul class="space-y-2 text-gray-500">
                        <li><a href="#" class="footer-link">ITPEC Prep</a></li>
                        <li><a href="#" class="footer-link">JLPT Bank</a></li>
                        <li><a href="#" class="footer-link">Vocabulary</a></li>
                        <li><a href="#" class="footer-link">Simulator</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-gray-800 mb-3">Resources</h5>
                    <ul class="space-y-2 text-gray-500">
                        <li><a href="#" class="footer-link">Blog</a></li>
                        <li><a href="#" class="footer-link">Study Guides</a></li>
                        <li><a href="#" class="footer-link">Community</a></li>
                        <li><a href="#" class="footer-link">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold text-gray-800 mb-3">Company</h5>
                    <ul class="space-y-2 text-gray-500">
                        <li><a href="#" class="footer-link">About</a></li>
                        <li><a href="#" class="footer-link">Contact</a></li>
                        <li><a href="#" class="footer-link">Privacy</a></li>
                        <li><a href="#" class="footer-link">Terms</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-100/30 max-w-7xl mx-auto px-6 py-4 text-center text-xs text-gray-400">
                &copy; 2026 Mirai Study Hub. Built with <i data-lucide="heart" class="w-3 h-3 text-emerald-500 inline mx-1"></i> for learners everywhere.
            </div>
        </footer>

    </main>

    <script>
    (function() {
        'use strict';

        // Scroll progress
        var progressBar = document.getElementById('scrollProgress');
        window.addEventListener('scroll', function() {
            var scrollTop = window.scrollY;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            progressBar.style.width = (docHeight > 0 ? (scrollTop / docHeight) * 100 : 0) + '%';
        });

        // Mobile sidebar toggle
        var hamburger = document.getElementById('hamburgerBtn');
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        var sidebarOpen = false;

        function toggleSidebar(open) {
            sidebarOpen = (open !== undefined) ? open : !sidebarOpen;
            if (sidebarOpen) {
                sidebar.classList.add('open');
                overlay.classList.add('open');
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
                document.body.style.overflow = '';
            }
        }

        hamburger.addEventListener('click', function() { toggleSidebar(); });
        overlay.addEventListener('click', function() { toggleSidebar(false); });

        document.querySelectorAll('.welcome-sidebar-nav a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) toggleSidebar(false);
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebarOpen) toggleSidebar(false);
        });

        // Theme toggle
        var themeRow = document.getElementById('themeToggleRow');
        var themeIcon = document.getElementById('themeIcon');
        var themeLabel = document.getElementById('themeLabel');
        var toggleThumb = document.getElementById('toggleThumb');

        function updateThemeUI(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
                themeLabel.textContent = 'Light Mode';
                toggleThumb.classList.add('dark-thumb');
                sidebar.classList.remove('nav-hover');
            } else {
                document.documentElement.classList.remove('dark');
                themeLabel.textContent = 'Dark Mode';
                toggleThumb.classList.remove('dark-thumb');
            }
            // Re-insert fresh <i> so Lucide can re-render (Lucide replaces <i> with <svg>,
            // making the captured themeIcon ref stale after first render)
            var cur = document.getElementById('themeIcon');
            if (cur) {
                var fresh = document.createElement('i');
                fresh.id = 'themeIcon';
                fresh.setAttribute('data-lucide', isDark ? 'sun' : 'moon');
                cur.parentNode.replaceChild(fresh, cur);
                if (window.renderIcons) {
                    window.renderIcons(document.getElementById('themeToggleRow'));
                } else if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
        }

        var isDark = document.documentElement.classList.contains('dark');
        updateThemeUI(isDark);

        function toggleTheme() {
            isDark = !isDark;
            updateThemeUI(isDark);
            try { localStorage.setItem('themeMode', isDark ? 'dark' : 'light'); } catch (e) {}
        }

        themeRow.addEventListener('click', toggleTheme);

        // Coordinated hover
        var navLinks = document.querySelectorAll('.welcome-sidebar-nav a');
        navLinks.forEach(function(link) {
            link.addEventListener('mouseenter', function() { sidebar.classList.add('nav-hover'); });
            link.addEventListener('mouseleave', function() { sidebar.classList.remove('nav-hover'); });
        });

        // Bidirectional scroll reveal
        var revealObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                var el = entry.target;
                if (entry.isIntersecting) el.classList.add('active');
                else el.classList.remove('active');
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -30px 0px' });

        document.querySelectorAll('.reveal, .reveal-hero, .reveal-stagger').forEach(function(el) {
            revealObserver.observe(el);
        });

        // Stat counter pop
        var statObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var num = entry.target.querySelector('.stat-number');
                    if (num) {
                        num.classList.add('pop');
                        setTimeout(function() { num.classList.remove('pop'); }, 500);
                    }
                }
            });
        }, { threshold: 0.3 });

        document.querySelectorAll('#stats-grid .stat-card').forEach(function(card) { statObserver.observe(card); });

        // Smooth scroll
        document.querySelectorAll('.welcome-sidebar-nav a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var targetId = this.getAttribute('href');
                if (targetId === '#') return;
                var targetEl = document.querySelector(targetId);
                if (targetEl) {
                    e.preventDefault();
                    var targetPosition = targetEl.getBoundingClientRect().top + window.scrollY - 16;
                    window.scrollTo({ top: targetPosition, behavior: 'smooth' });
                }
            });
        });

        // Hero reveal on load
        setTimeout(function() {
            document.querySelectorAll('.reveal-hero, .reveal').forEach(function(el) {
                var rect = el.getBoundingClientRect();
                if (rect.top < window.innerHeight * 0.9) el.classList.add('active');
            });
        }, 200);

        // Close sidebar on resize > 1024
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024 && sidebarOpen) toggleSidebar(false);
        });
    })();
    </script>

</body>
</html>
