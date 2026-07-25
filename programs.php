<?php
require_once __DIR__ . '/includes/init.php';

$courses = db()->query(
    "SELECT c.*, u.name AS uni_name, u.country AS uni_country, u.city AS uni_city
     FROM courses c JOIN universities u ON u.id = c.university_id
     WHERE c.status='open' ORDER BY c.semester DESC"
)->fetchAll();

$pageTitle = 'Exchange Programs — ' . APP_NAME;
require __DIR__ . '/includes/landing-header.php';
?>
<section class="min-h-screen pt-28 pb-20 bg-gradient-to-br from-slate-50 via-white to-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <a href="<?= url('/') ?>" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-indigo-600 transition-colors mb-4">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                Back to Home
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-50 text-violet-700 text-xs font-semibold mb-4">Academic Programs</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">All Exchange Programs</h1>
            <p class="mt-3 text-slate-500 text-lg"><?= count($courses) ?> courses available</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($courses as $c): ?>
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 hover:border-violet-200/60 transition-all hover:shadow-md">
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
    </div>
</section>
<?php require __DIR__ . '/includes/landing-footer.php'; ?>
