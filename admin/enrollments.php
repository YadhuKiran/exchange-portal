<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'approved';
    db()->prepare('UPDATE enrollments SET status=?, approved_by=? WHERE id=?')
        ->execute([$status, current_user()['id'], $id]);
    log_activity('enrollment.updated', "Enrollment #{$id} set to {$status}", 'enrollment', $id);
    flash('success', 'Enrollment updated.');
    redirect('/admin/enrollments.php');
}

$pageTitle = 'Enrollments';
$activeNav = 'enrollments';
$rows = enterprise_tables_ready() ? db()->query(
    "SELECT e.*, c.code, c.title, u.first_name, u.last_name, uni.name AS uni_name
     FROM enrollments e JOIN courses c ON c.id=e.course_id JOIN students s ON s.id=e.student_id
     JOIN users u ON u.id=s.user_id JOIN universities uni ON uni.id=c.university_id
     ORDER BY e.enrolled_at DESC"
)->fetchAll() : [];
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex items-center justify-between gap-4">
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="searchEnrollments" data-table-search="enrollmentsTable" placeholder="Search enrollments..." class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
    </div>
    <span class="text-xs text-slate-500"><?= count($rows) ?> total</span>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="enrollmentsTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="student">Student</th><th class="px-6 py-3.5 text-left" data-sort="course">Course</th>
                <th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2"><?= avatar_icon($r['first_name'].' '.$r['last_name'], 'sm') ?><span class="font-medium text-slate-900"><?= e($r['first_name'].' '.$r['last_name']) ?></span></div>
                </td>
                <td class="px-6 py-4"><span class="font-mono text-xs text-indigo-600"><?= e($r['code']) ?></span><p class="font-medium text-slate-900"><?= e($r['title']) ?></p><span class="text-xs text-slate-500"><?= e($r['uni_name']) ?></span></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status']) ?></td>
                <td class="px-6 py-4 text-right">
                    <?php if ($r['status'] === 'pending'): ?>
                    <div class="flex gap-2 justify-end">
                        <form method="post" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button name="status" value="approved" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg font-medium transition-all">Approve</button>
                            <button name="status" value="rejected" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg font-medium transition-all">Reject</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <span class="text-xs text-slate-400"><?= e(ucfirst($r['status'])) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No enrollments found.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
