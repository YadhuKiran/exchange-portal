<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

$rows = db()->prepare(
    "SELECT e.*, c.code AS course_code, c.title AS course_title, c.semester, u.name AS university_name
     FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     JOIN universities u ON u.id = c.university_id
     WHERE e.student_id = ?
     ORDER BY e.enrolled_at DESC"
);
$rows->execute([$stuId]);
$rows = $rows->fetchAll();

$pageTitle = 'My Enrollments';
$activeNav = 'enrollments';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><h1 class="text-lg font-bold text-slate-900">My Enrollments</h1><p class="text-xs text-slate-500">Course registrations</p></div>
    <a href="<?= url('/student/enroll.php') ?>" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-4 py-2 rounded-xl shadow-md shadow-indigo-500/20 text-sm whitespace-nowrap">+ Enroll</a>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="stuEnrollTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="course">Course</th><th class="px-6 py-3.5 text-left" data-sort="university">University</th><th class="px-6 py-3.5 text-left" data-sort="semester">Semester</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4"><span class="font-medium text-slate-700"><?= e($r['course_title']) ?></span><br><span class="text-xs text-slate-400"><?= e($r['course_code']) ?></span></td>
                <td class="px-6 py-4 text-slate-600 text-xs"><?= e($r['university_name']) ?></td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['semester']) ?></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status'], 'sm') ?></td>
                <td class="px-6 py-4 text-right">
                    <?php if ($r['status'] === 'approved'): ?>
                    <a href="<?= url('/student/enrollment-drop.php?id='.$r['id']) ?>" class="text-red-500 hover:text-red-600 text-xs font-medium" onclick="return confirm('Drop this enrollment?')">Drop</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No enrollments. <a href="<?= url('/student/enroll.php') ?>" class="text-indigo-600 hover:underline">Enroll now →</a></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
