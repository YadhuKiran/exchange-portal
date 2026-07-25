<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);

$rows = db()->query(
    "SELECT c.*, u.name AS university_name, u.city, u.country, u.logo_path
     FROM courses c JOIN universities u ON u.id = c.university_id
     WHERE c.status='open' ORDER BY c.title"
)->fetchAll();

$pageTitle = 'Browse Courses';
$activeNav = 'courses';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><h1 class="text-lg font-bold text-slate-900">Browse Courses</h1><p class="text-xs text-slate-500">Explore available exchange courses</p></div>
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" data-table-search="stuCoursesTable" placeholder="Search courses..." class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
    </div>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="stuCoursesTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="code">Code</th><th class="px-6 py-3.5 text-left" data-sort="title">Title</th><th class="px-6 py-3.5 text-left" data-sort="university">University</th><th class="px-6 py-3.5 text-left" data-sort="semester">Semester</th><th class="px-6 py-3.5 text-left" data-sort="credits">Credits</th><th class="px-6 py-3.5 text-left" data-sort="capacity">Cap.</th><th class="px-6 py-3.5 text-right">Action</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4"><span class="font-mono text-xs font-semibold text-indigo-600"><?= e($r['code']) ?></span></td>
                <td class="px-6 py-4 font-medium text-slate-700"><?= e($r['title']) ?></td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <?php if (!empty($r['logo_path'])): ?>
                            <img src="<?= url('/uploads/' . $r['logo_path']) ?>" class="w-6 h-6 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-500"><?= substr($r['university_name'], 0, 2) ?></div>
                        <?php endif; ?>
                        <div>
                            <span class="block text-slate-700 text-xs font-medium"><?= e($r['university_name']) ?></span>
                            <span class="block text-slate-400 text-[10px]"><?= e($r['city']) ?></span>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['semester']) ?></td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['credits']) ?></td>
                <td class="px-6 py-4 text-slate-600"><?= (int) $r['capacity'] ?></td>
                <td class="px-6 py-4 text-right">
                    <a href="<?= url('/student/application-form.php?course_id=' . $r['id'] . '&uni_id=' . $r['university_id'] . '&sem=' . urlencode($r['semester'])) ?>" class="btn-hover bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-medium px-3 py-1.5 rounded-lg text-xs whitespace-nowrap border border-indigo-200">1-Tap Apply</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No courses available at the moment.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
