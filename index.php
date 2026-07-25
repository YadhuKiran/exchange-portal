<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    $role = current_user()['role'];
    redirect(match ($role) {
        'admin'       => '/admin/index.php',
        'coordinator' => '/coordinator/index.php',
        'student'     => '/student/index.php',
        default       => '/login.php',
    });
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name  = trim($_POST['contact_name'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $msg   = trim($_POST['contact_message'] ?? '');
    if ($name && $email && $msg) {
        db()->prepare(
            'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, metadata, ip_address, created_at)
             VALUES (NULL, ?, ?, NULL, ?, ?, ?, NOW())'
        )->execute([
            'contact.submission',
            'contact',
            "From: $name <$email>",
            json_encode(['name' => $name, 'email' => $email, 'message' => $msg, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']),
            $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        $_SESSION['contact_sent'] = true;
        redirect('/#contact');
    }
}
$contactSent = !empty($_SESSION['contact_sent']);
unset($_SESSION['contact_sent']);

$stats = [
    'students'     => (int) db()->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'universities' => (int) db()->query("SELECT COUNT(*) FROM universities WHERE status='active'")->fetchColumn(),
    'countries'    => (int) db()->query("SELECT COUNT(DISTINCT country) FROM universities WHERE status='active'")->fetchColumn(),
    'applications' => (int) db()->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
];
$allUniversities = db()->query("SELECT * FROM universities WHERE status='active' ORDER BY name")->fetchAll();
$universities = array_slice($allUniversities, 0, 6);
$hasMoreUnis = count($allUniversities) > 6;
$allCourses = db()->query(
    "SELECT c.*, u.name AS uni_name, u.country AS uni_country, u.city AS uni_city
     FROM courses c JOIN universities u ON u.id = c.university_id
     WHERE c.status='open' ORDER BY c.semester DESC"
)->fetchAll();
$courses = array_slice($allCourses, 0, 6);
$hasMoreCourses = count($allCourses) > 6;
$countriesList = db()->query("SELECT DISTINCT country, city FROM universities WHERE status='active' ORDER BY country")->fetchAll();
$allActiveUnis = db()->query("SELECT name, country, city FROM universities WHERE status='active' ORDER BY country, name")->fetchAll();
$reviews = db()->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();

$uniDomains = [
    'Massachusetts Institute of Technology' => 'mit.edu',
    'University of Oxford' => 'ox.ac.uk',
    'National University of Singapore' => 'nus.edu.sg',
    'University of Toronto' => 'utoronto.ca',
    'Technical University of Munich' => 'tum.de',
    'University of Sydney' => 'sydney.edu.au',
    'Sciences Po Paris' => 'sciencespo.fr',
    'University of Tokyo' => 'u-tokyo.ac.jp',
    'University of Amsterdam' => 'uva.nl',
    'ETH Zurich' => 'ethz.ch',
    'Seoul National University' => 'snu.ac.kr',
    'University of Sao Paulo' => 'usp.br',
    'Complutense University of Madrid' => 'ucm.es',
    'Bocconi University' => 'unibocconi.it',
    'Stockholm University' => 'su.se',
    'Asia University' => 'asia.edu.tw',
    'Asia university' => 'asia.edu.tw',
    'Presidency University' => 'presiuniv.ac.in'
];

$pageTitle = APP_NAME . ' — Global Student Mobility & Exchange';
$headExtra = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
$showChat = false;
require __DIR__ . '/includes/landing-header.php';
?>

<!-- HERO -->
<section class="relative min-h-screen flex items-center overflow-hidden bg-slate-950">
    <div class="absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay" style="background-image: url('<?= url('/assets/img/hero.png') ?>');"></div>
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-950/90 via-slate-950/95 to-violet-950/90"></div>
    <div class="hero-glow bg-indigo-500 top-[-100px] left-[-100px]"></div>
    <div class="hero-glow bg-violet-500 bottom-[-100px] right-[-100px]"></div>
    <div class="hero-glow bg-cyan-500 top-1/2 left-1/3 opacity-10"></div>
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 40px 40px;"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-20 lg:pt-32 lg:pb-28">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <div class="reveal visible">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-indigo-300 text-xs font-medium mb-6">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-soft"></span>
                    Now accepting applications for Fall 2026
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold leading-[1.1] tracking-tight text-white">
                    Connect.<br>
                    <span class="gradient-text">Exchange.</span><br>
                    <span class="text-white">Thrive.</span>
                </h1>
                <p class="mt-6 text-lg sm:text-xl text-slate-400 leading-relaxed max-w-xl">
                    The unified global platform connecting students with partner universities worldwide.
                    Apply, manage documents, track visas, and embark on your international education journey.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="<?= url('/register.php') ?>" class="btn-primary inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-7 py-3.5 rounded-xl shadow-xl shadow-indigo-600/25 text-base">
                        Start Your Journey
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="<?= url('/login.php') ?>" class="btn-outline inline-flex items-center gap-2 text-slate-300 hover:text-white border border-slate-700 hover:border-indigo-500/50 font-medium px-7 py-3.5 rounded-xl text-base">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        Sign In
                    </a>
                </div>
                <div class="mt-10 flex items-center gap-8 text-sm text-slate-500">
                    <div class="flex -space-x-2">
                        <?php $avatars = ['bg-indigo-500','bg-violet-500','bg-emerald-500','bg-amber-500','bg-rose-500']; foreach ($avatars as $j=>$a): ?>
                        <div class="w-8 h-8 rounded-full border-2 border-slate-950 <?= $a ?> flex items-center justify-center text-white text-[10px] font-bold"><?= chr(65+$j) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <span>Trusted by <strong class="text-slate-300"><?= number_format($stats['students']) ?>+</strong> students across <strong class="text-slate-300"><?= $stats['countries'] ?></strong> countries</span>
                </div>
            </div>
            <div class="hidden lg:flex justify-center reveal-right visible relative">
                <img src="<?= url('/assets/img/dashboard.png') ?>" alt="Dashboard UI" class="rounded-2xl shadow-2xl shadow-indigo-500/20 border border-white/10 animate-float" style="max-height: 450px; object-fit: contain;">
                <div class="absolute -z-10 inset-0 rounded-full bg-gradient-to-br from-indigo-600/30 to-violet-600/30 blur-3xl animate-pulse-soft"></div>
            </div>
        </div>
    </div>
    <div class="absolute bottom-0 inset-x-0 h-32 bg-gradient-to-t from-white to-transparent"></div>
</section>

<!-- STATISTICS -->
<section class="relative -mt-16 z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-8 lg:p-10">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <?php
            $statItems = [
                ['value' => $stats['students'], 'label' => 'Active Students', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'color' => 'indigo'],
                ['value' => $stats['universities'], 'label' => 'Partner Universities', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color' => 'violet'],
                ['value' => $stats['countries'], 'label' => 'Countries', 'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald'],
                ['value' => $stats['applications'], 'label' => 'Applications Processed', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber'],
            ];
            foreach ($statItems as $s):
                $c = $s['color'];
            ?>
            <div class="stat-card text-center lg:text-left">
                <div class="w-12 h-12 rounded-xl bg-<?= $c ?>-50 flex items-center justify-center mx-auto lg:mx-0 mb-4">
                    <svg class="w-6 h-6 text-<?= $c ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $s['icon'] ?>"/></svg>
                </div>
                <div class="text-3xl lg:text-4xl font-extrabold text-slate-900 counter-value" data-target="<?= $s['value'] ?>">0</div>
                <div class="text-sm text-slate-500 mt-1.5 font-medium"><?= $s['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PARTNER UNIVERSITIES -->
<section id="universities" class="py-20 lg:py-28 bg-slate-50/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold mb-4">Our Network</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Global Partner Universities</h2>
            <p class="mt-4 text-slate-500 text-lg leading-relaxed">Collaborating with leading institutions across the world to provide students with transformative international education experiences.</p>
            <div class="section-divider mt-6"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($universities as $u): ?>
            <div class="partner-card bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 hover:border-indigo-200/60">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 shrink-0 flex items-center justify-center bg-white rounded-xl border border-slate-100 p-1 relative overflow-hidden">
                        <?php 
                        $domain = $uniDomains[$u['name']] ?? '';
                        if ($domain): 
                        ?>
                        <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128" alt="<?= e($u['name']) ?> Logo" class="w-full h-full object-contain rounded-xl" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-violet-50 flex items-center justify-center hidden">
                            <span class="text-lg font-bold text-indigo-600"><?= e(substr($u['name'], 0, 2)) ?></span>
                        </div>
                        <?php else: ?>
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-violet-50 flex items-center justify-center">
                            <span class="text-lg font-bold text-indigo-600"><?= e(substr($u['name'], 0, 2)) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-slate-900 text-sm leading-snug"><?= e($u['name'])?></h3>
                        <p class="text-xs text-slate-500 mt-1"><?= e($u['city'])?>, <?= e($u['country'])?></p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full"><?= e($u['code'])?></span>
                            <?php if ($u['status'] === 'active'): ?>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$universities): ?>
        <div class="text-center py-12 text-slate-500 text-sm">No universities available at this time.</div>
        <?php endif; ?>
        <?php if ($hasMoreUnis): ?>
        <div class="text-center mt-10">
            <a href="<?= url('/universities.php') ?>" class="btn-outline inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 border border-indigo-200 hover:border-indigo-300 font-medium px-6 py-2.5 rounded-xl text-sm transition-all">
                View All Partners
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- WORLD MAP -->
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold mb-4">Global Presence</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Where We Operate</h2>
            <p class="mt-4 text-slate-500 text-lg leading-relaxed">Our exchange network spans across continents, connecting students with top-tier universities worldwide.</p>
            <div class="section-divider mt-6"></div>
        </div>
        <div class="reveal-scale bg-slate-50 rounded-2xl border border-slate-200/60 overflow-hidden">
            <div id="worldMap" style="height:420px;"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mt-8 reveal">
            <?php
            $countryFlags = [];
            foreach ($countriesList as $c):
                $key = $c['country'];
                if (!isset($countryFlags[$key])) $countryFlags[$key] = 0;
                $countryFlags[$key]++;
            endforeach;
            
            $countryCodes = [
                'United States' => 'us', 'United Kingdom' => 'gb', 'Singapore' => 'sg',
                'Canada' => 'ca', 'Germany' => 'de', 'Australia' => 'au',
                'France' => 'fr', 'Japan' => 'jp', 'Netherlands' => 'nl',
                'Switzerland' => 'ch', 'South Korea' => 'kr', 'Brazil' => 'br',
                'Spain' => 'es', 'Italy' => 'it', 'Sweden' => 'se',
                'Taiwan' => 'tw', 'India' => 'in', 'Unknown' => 'tw'
            ];
            foreach ($countryFlags as $country => $count):
                $iso = $countryCodes[$country] ?? 'un';
            ?>
            <div class="flex items-center gap-2 bg-slate-50 rounded-xl px-3 py-2.5 border border-slate-100">
                <img src="https://flagcdn.com/w40/<?= $iso ?>.png" alt="<?= e($country) ?>" class="w-7 h-7 rounded bg-white object-cover border border-slate-200 shrink-0" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                <span class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-[10px] font-bold text-indigo-600 hidden"><?= e(substr($country, 0, 2))?></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-800 truncate"><?= e($country)?></p>
                    <p class="text-[10px] text-slate-400"><?= $count ?> universit<?= $count>1?'ies':'y' ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- EXCHANGE PROGRAMS -->
<section id="programs" class="py-20 lg:py-28 bg-slate-50/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-50 text-violet-700 text-xs font-semibold mb-4">Academic Programs</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Open Exchange Programs</h2>
            <p class="mt-4 text-slate-500 text-lg leading-relaxed">Browse currently available courses across our partner network. Apply through your coordinator.</p>
            <div class="section-divider mt-6"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($courses as $c): ?>
            <div class="partner-card bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-violet-600 bg-violet-50 px-2 py-0.5 rounded-full"><?= e($c['code'])?></span>
                    <span class="text-[11px] font-medium text-slate-500"><?= e($c['semester'])?></span>
                </div>
                <h3 class="font-semibold text-slate-900 text-sm leading-snug mb-2"><?= e($c['title'])?></h3>
                <p class="text-xs text-slate-500"><?= e($c['uni_name'])?> &middot; <?= e($c['uni_city'])?>, <?= e($c['uni_country'])?></p>
                <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100">
                    <span class="text-xs text-slate-500"><strong class="text-slate-700"><?= $c['credits']?></strong> credits</span>
                    <span class="text-xs text-slate-500">Capacity: <strong class="text-slate-700"><?= $c['capacity']?></strong></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$courses): ?>
        <div class="text-center py-12 text-slate-500 text-sm">No programs currently available.</div>
        <?php endif; ?>
        <?php if ($hasMoreCourses): ?>
        <div class="text-center mt-10">
            <a href="<?= url('/programs.php') ?>" class="btn-outline inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 border border-indigo-200 hover:border-indigo-300 font-medium px-6 py-2.5 rounded-xl text-sm transition-all">
                View All Programs
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- STUDENT JOURNEY -->
<section id="journey" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold mb-4">Student Journey</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Your Exchange Journey</h2>
            <p class="mt-4 text-slate-500 text-lg leading-relaxed">From registration to graduation — a seamless experience designed for international students.</p>
            <div class="section-divider mt-6"></div>
        </div>
        <div class="relative max-w-4xl mx-auto">
            <div class="timeline-line hidden lg:block"></div>
            <?php
            $steps = [
                ['num' => '01', 'title' => 'Create Your Profile', 'desc' => 'Sign up and connect to your home university. Submit your academic records and personal details to get started.', 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                ['num' => '02', 'title' => 'Browse Programs', 'desc' => 'Explore exchange programs, courses, and partner universities. Find the perfect fit for your academic goals.', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                ['num' => '03', 'title' => 'Submit Application', 'desc' => 'Apply to your chosen program with a personal statement. Track your application status in real time.', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['num' => '04', 'title' => 'Document Verification', 'desc' => 'Upload transcripts, passport, and visa documents. Coordinators review and verify everything digitally.', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['num' => '05', 'title' => 'Enroll & Go Abroad', 'desc' => 'Enroll in courses, get your visa, and embark on your international exchange experience.', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
            ];
            foreach ($steps as $i => $s):
                $side = $i % 2 === 0 ? 'reveal-left' : 'reveal-right';
            ?>
            <div class="relative flex items-start gap-6 lg:gap-0 lg:even:flex-row-reverse pb-12 last:pb-0">
                <div class="hidden lg:flex absolute left-1/2 top-0 -translate-x-1/2 w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white items-center justify-center text-sm font-bold shadow-lg shadow-indigo-500/20 z-10"><?= $s['num'] ?></div>
                <div class="lg:w-1/2 <?= $side ?>">
                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200/60 lg:mx-8">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="lg:hidden w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center text-xs font-bold"><?= $s['num'] ?></span>
                            <h3 class="font-bold text-slate-900 text-base"><?= $s['title'] ?></h3>
                        </div>
                        <p class="text-sm text-slate-500 leading-relaxed pl-0 lg:pl-0"><?= $s['desc'] ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section id="testimonials" class="py-20 lg:py-28 bg-slate-50/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-semibold mb-4">Testimonials</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">What Our Students Say</h2>
            <p class="mt-4 text-slate-500 text-lg leading-relaxed">Hear from students who have transformed their lives through our global exchange programs.</p>
            <div class="section-divider mt-6"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            foreach ($reviews as $t):
            ?>
            <div class="testimonial-card bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8">
                <svg class="w-8 h-8 text-indigo-200 mb-4" fill="currentColor" viewBox="0 0 32 32"><path d="M10 8c-3.3 0-6 2.7-6 6v10h10V14H8c0-1.1.9-2 2-2V8zm16 0c-3.3 0-6 2.7-6 6v10h10V14h-6c0-1.1.9-2 2-2V8z"/></svg>
                <p class="text-sm text-slate-600 leading-relaxed mb-6">"<?= e($t['text'])?>"</p>
                <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-100 to-violet-100 border border-indigo-200 flex items-center justify-center text-xs font-bold text-indigo-600"><?= $t['initials'] ?></div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900"><?= e($t['name'])?></p>
                        <p class="text-xs text-slate-500"><?= e($t['role'])?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CONTACT -->
<section id="contact" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-14 reveal">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-50 text-cyan-700 text-xs font-semibold mb-4">Get in Touch</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Send Us a Message</h2>
            <p class="mt-4 text-slate-500 text-lg leading-relaxed">Have a question about our programs, partnerships, or anything else? We'd love to hear from you.</p>
            <div class="section-divider mt-6"></div>
        </div>
        <div class="max-w-2xl mx-auto reveal">
            <?php if (!empty($contactSent)): ?>
            <div class="rounded-2xl bg-emerald-50 border border-emerald-200/60 text-emerald-800 px-6 py-5 text-sm flex items-center gap-3 mb-6">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span class="font-medium">Thank you! Your message has been received. Our team will get back to you via email.</span>
            </div>
            <?php endif; ?>
            <form method="post" class="bg-slate-50 rounded-2xl border border-slate-200/60 p-8 space-y-5">
                <input type="hidden" name="contact_submit" value="1">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Your Name</label>
                        <input type="text" name="contact_name" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="John Smith">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Your Email</label>
                        <input type="email" name="contact_email" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="john@university.edu">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Message</label>
                    <textarea name="contact_message" required rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all resize-none" placeholder="Tell us about your inquiry..."></textarea>
                </div>
                <button type="submit" class="btn-primary inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">
                    Send Message
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="py-20 lg:py-28 bg-slate-950 relative overflow-hidden">
    <div class="hero-glow bg-indigo-500 top-[-200px] right-[-200px]"></div>
    <div class="hero-glow bg-violet-500 bottom-[-200px] left-[-200px]"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 reveal">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight">Ready to Start Your<br><span class="gradient-text">Global Exchange Journey?</span></h2>
        <p class="mt-6 text-lg text-slate-400 max-w-2xl mx-auto">Join thousands of students who have expanded their horizons through international education. Your adventure starts here.</p>
        <div class="mt-10 flex flex-wrap justify-center gap-4">
            <a href="<?= url('/register.php') ?>" class="btn-primary inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-8 py-3.5 rounded-xl shadow-xl shadow-indigo-600/25 text-base">
                Get Started Free
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="<?= url('/login.php') ?>" class="btn-outline inline-flex items-center gap-2 text-slate-300 hover:text-white border border-slate-700 hover:border-indigo-500/50 font-medium px-8 py-3.5 rounded-xl text-base">
                Existing Member? Sign In
            </a>
        </div>
    </div>
</section>

<script>
// Scroll Reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(el => observer.observe(el));

// Counter Animation
function animateCounters() {
    document.querySelectorAll('.counter-value').forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        if (target === 0) { counter.textContent = '0'; return; }
        const duration = 2000;
        const start = performance.now();
        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            counter.textContent = Math.floor(eased * target).toLocaleString();
            if (progress < 1) requestAnimationFrame(update);
            else counter.textContent = target.toLocaleString();
        }
        requestAnimationFrame(update);
    });
}

const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            statsObserver.disconnect();
        }
    });
}, { threshold: 0.5 });
const statSection = document.querySelector('.stat-card');
if (statSection) statsObserver.observe(statSection.closest('.grid') || statSection);

// Leaflet Map
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('worldMap', { zoomControl: false, attributionControl: false, scrollWheelZoom: false }).setView([25, 0], 2);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    var unisData = <?= json_encode($allActiveUnis) ?>;
    var countryGroups = {};
    unisData.forEach(function(u) {
        var c = u.country === 'Unknown' ? 'Taiwan' : u.country; // Fix for Asia university Unknown entry
        if (!countryGroups[c]) countryGroups[c] = [];
        if (countryGroups[c].indexOf(u.name) === -1) {
            countryGroups[c].push(u.name);
        }
    });

    var coords = {
        'United States': [37.09, -95.71],
        'United Kingdom': [55.38, -3.44],
        'Singapore': [1.35, 103.82],
        'Canada': [56.13, -106.35],
        'Germany': [51.17, 10.45],
        'Australia': [-25.27, 133.77],
        'France': [46.22, 2.21],
        'Japan': [36.20, 138.25],
        'Netherlands': [52.13, 5.29],
        'Switzerland': [46.81, 8.22],
        'South Korea': [35.90, 127.76],
        'Brazil': [-14.23, -51.92],
        'Spain': [40.46, -3.74],
        'Italy': [41.87, 12.56],
        'Sweden': [60.12, 18.64],
        'Taiwan': [23.69, 120.96],
        'India': [20.59, 78.96]
    };
    
    Object.keys(countryGroups).forEach(function(country) {
        var pos = coords[country];
        if (pos) {
            var icon = L.divIcon({
                className: 'custom-marker',
                html: '<div style="width:16px;height:16px;background:#4f46e5;border:3px solid white;border-radius:50%;box-shadow:0 2px 8px rgba(79,70,229,0.5);animation:pulsePin 2s ease-in-out infinite;cursor:pointer;"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });
            
            var uniListHtml = '<div style="max-height: 200px; overflow-y: auto; padding: 2px;">';
            uniListHtml += '<strong style="color: #0f172a; font-size: 14px; border-bottom: 1px solid #e2e8f0; display: block; padding-bottom: 4px; margin-bottom: 6px;">' + country + '</strong>';
            uniListHtml += '<ul style="margin:0; padding-left:16px; font-size:13px; color: #475569; list-style-type: disc;">';
            countryGroups[country].forEach(function(n) { uniListHtml += '<li style="margin-bottom: 3px;">' + n + '</li>'; });
            uniListHtml += '</ul></div>';
            
            var marker = L.marker(pos, { icon: icon }).addTo(map);
            marker.bindTooltip(uniListHtml, { direction: 'top', offset: [0, -12], className: 'uni-map-tooltip' });
        }
    });
});
</script>

<?php require __DIR__ . '/includes/landing-footer.php'; ?>
