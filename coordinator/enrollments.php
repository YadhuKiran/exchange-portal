<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['coordinator']);

$coord = coordinator_profile((int) current_user()['id']);
$uniId = (int) $coord['university_id'];

$rows = db()->prepare(
    "SELECT e.*, c.code AS course_code, c.title AS course_title, c.semester,
            u.first_name, u.last_name
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     JOIN students s ON s.id = e.student_id
     JOIN users u ON u.id = s.user_id
     WHERE s.university_id = ? AND c.university_id = ?
     ORDER BY e.enrolled_at DESC"
);
$rows->execute([$uniId, $uniId]);
$rows = $rows->fetchAll();

$pageTitle = 'Enrollments';
$activeNav = 'enrollments';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><h1 class="text-lg font-bold text-slate-900">Enrollments</h1><p class="text-xs text-slate-500">Course registrations at your institution</p></div>
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" data-table-search="coordEnrollTable" placeholder="Search enrollments..." class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
    </div>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="coordEnrollTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="student">Student</th><th class="px-6 py-3.5 text-left" data-sort="course">Course</th><th class="px-6 py-3.5 text-left" data-sort="semester">Semester</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-left" data-sort="date">Enrolled</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4"><div class="flex items-center gap-2"><?= avatar_icon($r['first_name'].' '.$r['last_name'], 'sm') ?><span class="font-medium text-slate-900"><?= e($r['first_name'].' '.$r['last_name']) ?></span></div></td>
                <td class="px-6 py-4"><span class="font-medium text-slate-700"><?= e($r['course_title']) ?></span><br><span class="text-xs text-slate-400"><?= e($r['course_code']) ?></span></td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['semester']) ?></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status'], 'sm') ?></td>
                <td class="px-6 py-4 text-xs text-slate-500"><?= date('M j, Y', strtotime($r['enrolled_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No enrollments yet.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
