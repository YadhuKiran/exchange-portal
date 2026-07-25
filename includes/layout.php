<?php
$loadCharts = $loadCharts ?? false;
require_once __DIR__ . '/header.php';
$activeNav = $activeNav ?? '';
$user = current_user();
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$notifCount = unread_notification_count((int) $user['id']);

require_once __DIR__ . '/nav-config.php';
$navGroups = get_enterprise_nav($user['role']);
$uniId = null;
if ($user['role'] === 'coordinator') {
    $cp = coordinator_profile((int) $user['id']);
    $uniId = $cp ? (int) $cp['university_id'] : null;
}
$navBadges = nav_badge_counts($user['role'], $uniId);
$mobileNav = flatten_nav_for_mobile($navGroups);

$notifUrl = match ($user['role']) {
    'admin' => '/admin/notifications.php',
    'coordinator' => '/coordinator/notifications.php',
    default => '/student/notifications.php',
};

$profileUrl = match ($user['role']) {
    'admin' => '/admin/profile.php',
    'coordinator' => '/coordinator/profile.php',
    default => '/student/profile.php',
};
$roleLabel = match ($user['role']) {
    'admin' => 'Administrator',
    'coordinator' => 'Coordinator',
    default => 'Student',
};

$sessionTimeRemaining = 0;
if (in_array($user['role'], ['student', 'coordinator']) && isset($_SESSION['login_time'])) {
    $sessionTimeRemaining = 1800 - (time() - $_SESSION['login_time']);
    if ($sessionTimeRemaining < 0) $sessionTimeRemaining = 0;
}
?>
<div class="flex h-full min-h-screen bg-slate-50">
<aside id="mainSidebar" class="hidden lg:flex lg:flex-col w-72 glass-sidebar text-white shrink-0 shadow-2xl">
        <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-800/50">
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 shrink-0">
                <svg viewBox="0 0 36 36" class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="18" r="14"/>
                    <ellipse cx="18" cy="18" rx="6" ry="14"/>
                    <line x1="4" y1="18" x2="32" y2="18"/>
                    <path d="M10 14l8-5 8 5v4"/>
                    <path d="M10 18v4l8 4 8-4v-4"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-sm leading-tight truncate"><?= e(APP_NAME) ?></p>
                <p class="text-[10px] uppercase tracking-wider text-slate-500 font-medium"><?= e($roleLabel) ?> Portal</p>
            </div>
        </div>
        <nav class="flex-1 px-3 py-5 overflow-y-auto scrollbar-thin space-y-6">
            <?php foreach ($navGroups as $group): ?>
            <div>
                <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-slate-500/80"><?= e($group['group']) ?></p>
                <div class="space-y-0.5">
                    <?php foreach ($group['items'] as $item):
                        $isActive = ($activeNav === $item['key']);
                        $badgeKey = $item['badge'] ?? null;
                        $badge = $badgeKey && !empty($navBadges[$badgeKey]) ? $navBadges[$badgeKey] : 0;
                    ?>
                    <a href="<?= url($item['href']) ?>"
                       class="sidebar-link flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                              <?= $isActive ? 'bg-brand-600/20 text-white border-l-[3px] border-brand-400 ml-0 pl-[calc(0.75rem-3px)]' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                        <span class="truncate"><?= e($item['label']) ?></span>
                        <?php if ($badge > 0): ?>
                        <span class="nav-badge shrink-0 min-w-[1.375rem] text-center text-[10px] font-bold bg-amber-500/90 text-slate-900 px-1.5 py-0.5 rounded-full shadow-sm shadow-amber-500/20"><?= $badge > 99 ? '99+' : $badge ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </nav>
        <div class="p-4 border-t border-slate-800/50">
            <button onclick="toggleDarkMode()" class="flex items-center gap-3 w-full px-2 py-2 rounded-lg text-xs text-slate-500 hover:text-white hover:bg-slate-800/60 transition mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="sidebarDmIcon">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span>Dark Mode</span>
            </button>
            <div class="flex items-center gap-3 px-2">
                <?= avatar_icon($user['first_name'].' '.$user['last_name'], 'md', $user['profile_picture'] ?? null) ?>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate text-slate-200"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
                    <p class="text-xs text-slate-500 truncate"><?= e($user['email']) ?></p>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-1.5">
                <?php if ($profileUrl !== '#'): ?>
                <a href="<?= url($profileUrl) ?>" class="text-center text-xs text-slate-500 hover:text-white py-1.5 rounded-lg hover:bg-slate-800/60 transition">Profile</a>
                <?php endif; ?>
                <a href="<?= url('/logout.php') ?>" class="text-center text-xs text-slate-500 hover:text-white py-1.5 rounded-lg hover:bg-slate-800/60 transition">Sign out</a>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200/60 px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" class="lg:hidden p-2 -ml-2 rounded-lg text-slate-500 hover:bg-slate-100 transition" onclick="document.querySelector('aside')?.classList.toggle('hidden')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 hidden sm:block"><?= e(APP_NAME) ?></p>
                        <h1 class="text-lg font-bold text-slate-900 tracking-tight"><?= e($pageTitle) ?></h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?php if (in_array($user['role'], ['student', 'coordinator'])): ?>
                    <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 font-medium text-xs dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700" id="session-timer-container">
                        <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="session-timer"><?= sprintf('%02d:%02d', floor($sessionTimeRemaining / 60), $sessionTimeRemaining % 60) ?></span>
                    </div>
                    <?php endif; ?>
                    <button onclick="toggleDarkMode()" class="p-2.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all" title="Toggle dark mode">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="dmIcon">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    <a href="<?= url($notifUrl) ?>" class="relative p-2.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <?php if ($notifCount > 0): ?>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-white"></span>
                        <?php endif; ?>
                    </a>
                    <div class="hidden sm:flex items-center gap-2 pl-2 border-l border-slate-200">
                        <?= avatar_icon($user['first_name'].' '.$user['last_name'], 'sm', $user['profile_picture'] ?? null) ?>
                        <div class="text-xs">
                            <p class="font-medium text-slate-700 leading-tight"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></p>
                            <p class="text-slate-400 leading-tight"><?= e($roleLabel) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:hidden mt-2 flex gap-1.5 overflow-x-auto pb-1 scrollbar-thin -mx-1 px-1">
                <?php foreach ($mobileNav as [$key, $label, $href]): ?>
                <a href="<?= url($href) ?>" class="shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-medium transition-all <?= $activeNav === $key ? 'bg-brand-600 text-white shadow-sm shadow-brand-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </header>
        <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-auto page-transition">
<script>
(function() {
    const isDark = document.documentElement.classList.contains('dark');
    const icons = [document.getElementById('dmIcon'), document.getElementById('sidebarDmIcon')];
    const sun = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>';
    const moon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>';
    icons.forEach(function(el) { if (el) el.innerHTML = isDark ? sun : moon; });
})();
</script>

<?php if (in_array($user['role'], ['student', 'coordinator'])): ?>
<script>
(function() {
    let timeLeft = <?= (int)$sessionTimeRemaining ?>;
    const timerEl = document.getElementById('session-timer');
    const containerEl = document.getElementById('session-timer-container');
    
    if (timerEl && timeLeft > 0) {
        const interval = setInterval(function() {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(interval);
                window.location.href = '<?= url('/login.php') ?>';
            } else {
                let m = Math.floor(timeLeft / 60);
                let s = timeLeft % 60;
                timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
                
                if (timeLeft < 300) {
                    containerEl.classList.remove('text-slate-600', 'dark:text-slate-300');
                    containerEl.classList.add('text-red-600', 'bg-red-50', 'border-red-200', 'dark:bg-red-900/30', 'dark:text-red-400', 'dark:border-red-800/50');
                    const svg = containerEl.querySelector('svg');
                    if (svg && svg.classList.contains('text-brand-500')) {
                        svg.classList.replace('text-brand-500', 'text-red-500');
                    }
                }
            }
        }, 1000);
    }
})();
</script>
<?php endif; ?>
