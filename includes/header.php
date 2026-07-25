<?php
$user = current_user();
$role = $user['role'] ?? 'guest';
$pageTitle = $pageTitle ?? APP_NAME;
$notifCount = $user ? unread_notification_count((int) $user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#4f46e5" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'slide-down': 'slideDown 0.3s ease-out',
                        'scale-in': 'scaleIn 0.2s ease-out',
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                        'shimmer': 'shimmer 1.5s infinite',
                        'spin-slow': 'spin 3s linear infinite',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { opacity: '0', transform: 'translateY(12px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        slideDown: { '0%': { opacity: '0', transform: 'translateY(-8px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        scaleIn: { '0%': { opacity: '0', transform: 'scale(0.95)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
                        pulseSoft: { '0%, 100%': { opacity: '1' }, '50%': { opacity: '0.7' } },
                        shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .dark { color-scheme: dark; }
        :root {
            --brand: #4f46e5;
            --brand-light: #818cf8;
            --brand-dark: #3730a3;
            --bg-base: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f8fafc;
            --bg-sidebar: #0f172a;
            --border: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
        }
        .dark {
            --bg-base: #0f172a;
            --bg-card: #1e293b;
            --bg-card-hover: #1e293b;
            --border: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
        }
        
        /* Auto dark mode for hardcoded Tailwind light classes */
        .dark .bg-white { background-color: var(--bg-card) !important; }
        .dark .bg-slate-50 { background-color: var(--bg-base) !important; }
        .dark .bg-slate-100 { background-color: #0f172a !important; }
        .dark .text-slate-900, .dark .text-slate-800, .dark .text-slate-700 { color: var(--text-primary) !important; }
        .dark .text-slate-600, .dark .text-slate-500 { color: var(--text-secondary) !important; }
        .dark .border-slate-200, .dark .border-slate-200\/60, .dark .border-slate-100 { border-color: var(--border) !important; }
        .dark .bg-white\/80 { background-color: rgba(30, 41, 59, 0.8) !important; border-color: var(--border) !important; }
        
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; background-color: var(--bg-base); color: var(--text-primary); }
        .chart-box { position: relative; height: 260px; }

        .glass { background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2); }
        .dark .glass { background: rgba(30,41,59,0.7); border-color: rgba(255,255,255,0.08); }
        .glass-dark { background: rgba(15,23,42,0.8); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); }

        .skeleton { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 0.5rem; }

        .card-hover { transition: all 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
        .dark .card-hover:hover { box-shadow: 0 12px 40px rgba(0,0,0,0.3); }

        .btn-hover { transition: all 0.2s ease; position: relative; overflow: hidden; }
        .btn-hover:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(79,70,229,0.3); }
        .btn-hover:active { transform: translateY(0); }

        .sidebar-link { transition: all 0.15s ease; position: relative; }
        .sidebar-link::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 0; background: #818cf8; border-radius: 0 3px 3px 0; transition: height 0.2s ease; }
        .sidebar-link:hover::before { height: 20px; }
        .sidebar-link.active::before { height: 24px; }

        .page-transition { animation: fadeIn 0.3s ease-out, slideUp 0.3s ease-out; }

        .table-row-hover { transition: background-color 0.15s ease; }
        .table-row-hover:hover { background-color: var(--bg-card-hover); }

        .badge { transition: all 0.2s ease; }

        input, select, textarea { transition: all 0.15s ease; background-color: var(--bg-card); color: var(--text-primary); border-color: var(--border); }
        input:focus, select:focus, textarea:focus { box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }

        .scrollbar-thin::-webkit-scrollbar { width: 4px; height: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .nav-badge { animation: scaleIn 0.2s ease-out; }

        .stat-card { transition: all 0.25s ease; }
        .stat-card:hover { transform: translateY(-3px); }

        .dark input.readonly, .dark input[readonly] { background-color: #1e293b; color: #94a3b8; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
    <script>
    (function() {
        const key = 'exchange-portal-dark-mode';
        const stored = localStorage.getItem(key);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (stored === 'true' || (stored === null && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
    function toggleDarkMode() {
        const html = document.documentElement;
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('exchange-portal-dark-mode', isDark ? 'true' : 'false');
        
        const sun = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>';
        const moon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>';
        
        const icons = [document.getElementById('dmIcon'), document.getElementById('sidebarDmIcon')];
        icons.forEach(function(el) { if (el) el.innerHTML = isDark ? sun : moon; });
    }
    </script>
    <link rel="stylesheet" href="<?= url('/assets/css/premium.css') ?>">
    <?php if (!empty($loadCharts)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <?php endif; ?>
</head>
<body class="h-full antialiased" style="background-color:var(--bg-base)">
<?php $flash = get_flash(); if ($flash): ?>
<div id="flash" class="fixed top-4 right-4 z-[100] max-w-sm animate-slide-down">
    <div class="rounded-xl px-5 py-3.5 shadow-xl text-sm font-medium backdrop-blur-sm <?= $flash['type']==='success'?'bg-emerald-600/95 text-white':($flash['type']==='error'?'bg-red-600/95 text-white':'bg-brand-600/95 text-white') ?> flex items-center gap-2.5 shadow-lg">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <?php if ($flash['type']==='success'): ?>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            <?php elseif ($flash['type']==='error'): ?>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            <?php else: ?>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            <?php endif; ?>
        </svg>
        <span><?= e($flash['message']) ?></span>
    </div>
</div>
<script>setTimeout(()=>{const e=document.getElementById('flash');if(e){e.style.transition='opacity 0.3s ease';e.style.opacity='0';setTimeout(()=>e.remove(),300)}},4000);</script>
<?php endif; ?>
