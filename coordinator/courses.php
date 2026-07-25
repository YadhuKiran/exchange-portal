<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['coordinator']);

$coord = coordinator_profile((int) current_user()['id']);
$uniId = (int) $coord['university_id'];
$uni = db()->prepare('SELECT * FROM universities WHERE id = ?');
$uni->execute([$uniId]);
$uni = $uni->fetch();

$rows = db()->prepare("SELECT c.* FROM courses c WHERE c.university_id = ? ORDER BY c.title");
$rows->execute([$uniId]);
$rows = $rows->fetchAll();

$pageTitle = 'Courses';
$activeNav = 'courses';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><p class="text-xs text-slate-500 uppercase tracking-wide font-medium"><?= e($uni['name']) ?></p><h1 class="text-lg font-bold text-slate-900">Courses</h1></div>
    <div class="flex gap-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" data-table-search="coordCoursesTable" placeholder="Search courses..." class="w-full sm:w-56 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <a href="<?= url('/coordinator/course-form.php') ?>" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-4 py-2 rounded-xl shadow-md shadow-indigo-500/20 text-sm">+ Add</a>
    </div>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="coordCoursesTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="code">Code</th><th class="px-6 py-3.5 text-left" data-sort="title">Title</th><th class="px-6 py-3.5 text-left" data-sort="semester">Semester</th><th class="px-6 py-3.5 text-left" data-sort="credits">Credits</th><th class="px-6 py-3.5 text-left" data-sort="capacity">Cap.</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4"><span class="font-mono text-xs font-semibold text-indigo-600 uppercase"><?= e($r['code']) ?></span></td>
                <td class="px-6 py-4 text-slate-700 font-medium"><?= e($r['title']) ?></td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['semester']) ?></td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['credits']) ?></td>
                <td class="px-6 py-4 text-slate-600"><?= (int) $r['capacity'] ?></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status'], 'sm') ?></td>
                <td class="px-6 py-4 text-right"><a href="<?= url('/coordinator/course-form.php?id='.$r['id']) ?>" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No courses found.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
