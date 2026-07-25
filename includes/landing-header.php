<?php
require_once __DIR__ . '/../config.php';
$pageTitle = $pageTitle ?? APP_NAME;
$headExtra = $headExtra ?? '';
$hideNav = $hideNav ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <meta name="description" content="Global Exchange Portal — Connecting students and universities worldwide for international exchange programs, study abroad opportunities, and cross-cultural education.">
    <title><?= e($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                            400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                            800: '#3730a3', 900: '#312e81', 950: '#1e1b4b'
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'fade-in-up': 'fadeInUp 0.7s ease-out',
                        'fade-in-down': 'fadeInDown 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'scale-in': 'scaleIn 0.4s ease-out',
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                        'float': 'float 3s ease-in-out infinite',
                        'shimmer': 'shimmer 2s infinite linear',
                        'spin-slow': 'spin 8s linear infinite',
                        'count-up': 'countUp 2s ease-out',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        fadeInUp: { '0%': { opacity: '0', transform: 'translateY(30px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        fadeInDown: { '0%': { opacity: '0', transform: 'translateY(-15px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        scaleIn: { '0%': { opacity: '0', transform: 'scale(0.9)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
                        pulseSoft: { '0%, 100%': { opacity: '1' }, '50%': { opacity: '0.7' } },
                        float: { '0%, 100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-15px)' } },
                        shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #4f46e5; --brand-light: #818cf8; --brand-dark: #3730a3; }
        input, select, textarea { color-scheme: light; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal.visible { opacity: 1; transform: translateY(0); }
        .reveal-left { opacity: 0; transform: translateX(-40px); transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal-left.visible { opacity: 1; transform: translateX(0); }
        .reveal-right { opacity: 0; transform: translateX(40px); transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal-right.visible { opacity: 1; transform: translateX(0); }
        .reveal-scale { opacity: 0; transform: scale(0.85); transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1); }
        .reveal-scale.visible { opacity: 1; transform: scale(1); }
        .gradient-text { background: linear-gradient(135deg, #4f46e5, #7c3aed, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(100px); opacity: 0.15; pointer-events: none; }
        .nav-blur { backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        .btn-primary { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(79,70,229,0.3); }
        .btn-outline { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-outline:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(79,70,229,0.15); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 20px 60px rgba(79,70,229,0.08); }
        .partner-card { transition: all 0.3s ease; }
        .partner-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.08); }
        .timeline-line { position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; background: linear-gradient(180deg, #4f46e5, #a855f7); transform: translateX(-50%); }
        .map-pin { animation: pulsePin 2s ease-in-out infinite; }
        @keyframes pulsePin { 0%, 100% { opacity: 0.6; transform: scale(1); } 50% { opacity: 1; transform: scale(1.3); } }
        .counter-value { display: inline-block; }
        .testimonial-card { transition: all 0.3s ease; }
        .testimonial-card:hover { transform: translateY(-4px); }
        .section-divider { background: linear-gradient(90deg, transparent, #4f46e5, transparent); height: 1px; width: 80px; margin: 0 auto; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
            .reveal, .reveal-left, .reveal-right, .reveal-scale { opacity: 1; transform: none; }
        }
    </style>
    <link rel="stylesheet" href="<?= url('/assets/css/premium.css') ?>">
    <?= $headExtra ?>
</head>
<body class="bg-white text-slate-900 antialiased">
<?php if (!$hideNav): ?>
<nav id="navbar" class="fixed top-0 inset-x-0 z-50 transition-all duration-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <a href="<?= url('/') ?>" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:shadow-indigo-500/30 transition-all">
                    <svg viewBox="0 0 36 36" class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="18" r="14"/><ellipse cx="18" cy="18" rx="6" ry="14"/>
                        <line x1="4" y1="18" x2="32" y2="18"/>
                        <path d="M10 14l8-5 8 5v4"/><path d="M10 18v4l8 4 8-4v-4"/>
                    </svg>
                </div>
                <span class="font-bold text-lg tracking-tight text-slate-900">Global<span class="text-indigo-600">Exchange</span></span>
            </a>
            <div class="hidden md:flex items-center gap-8">
                <a href="<?= url('/') ?>#programs" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Programs</a>
                <a href="<?= url('/') ?>#universities" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Universities</a>
                <a href="<?= url('/') ?>#journey" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">How It Works</a>
                <a href="<?= url('/') ?>#testimonials" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Testimonials</a>
                <a href="<?= url('/') ?>#contact" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition-colors">Contact</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= url('/login.php') ?>" class="hidden sm:inline-flex text-sm font-medium text-slate-700 hover:text-indigo-600 transition-colors px-4 py-2">Sign In</a>
                <a href="<?= url('/register.php') ?>" class="btn-primary inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 px-5 py-2 rounded-xl shadow-md shadow-indigo-500/20">
                    Get Started
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>
<?php if (!$hideNav): ?>
<script>
window.addEventListener('scroll', function(){
    var nav = document.getElementById('navbar');
    if (window.scrollY > 50) {
        nav.classList.add('bg-white/90', 'nav-blur', 'shadow-sm', 'border-b', 'border-slate-200/40');
        nav.classList.remove('bg-transparent');
    } else {
        nav.classList.remove('bg-white/90', 'nav-blur', 'shadow-sm', 'border-b', 'border-slate-200/40');
        nav.classList.add('bg-transparent');
    }
});
</script>
<?php endif; ?>
