<?php
require_once __DIR__ . '/../../includes/init.php';
require_role(['coordinator']);

$pageTitle = 'Reports Center';
$activeNav = 'reports';
require __DIR__ . '/../../includes/layout.php';
?>
<div class="mb-6"><h1 class="text-lg font-bold text-slate-900">Reports Center</h1><p class="text-xs text-slate-500">Download CSV reports for your institution</p></div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ([
        ['Applications', '/coordinator/reports/export-applications.php', 'Briefcase'],
        ['Students', '/coordinator/reports/export-students.php', 'Users'],
        ['Documents', '/coordinator/reports/export-documents.php', 'Folder'],
    ] as [$title, $href, $icon]): ?>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div><h2 class="font-semibold text-slate-900 text-sm"><?= e($title) ?></h2><p class="text-xs text-slate-500">CSV export</p></div>
        </div>
        <a href="<?= url($href) ?>" class="btn-hover flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-4 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm w-full">Download CSV</a>
    </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php';
