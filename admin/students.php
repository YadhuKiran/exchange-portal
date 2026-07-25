<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['admin']);

$pageTitle = 'Students';
$activeNav = 'students';

$total = db()->query("SELECT COUNT(*) FROM students")->fetchColumn();
$paginator = new App\Paginator((int) $total, (int) ($_GET['page'] ?? 1), 10);

$rows = db()->prepare(
    "SELECT s.*, u.first_name, u.last_name, u.email, u.status AS user_status, uni.name AS university_name
     FROM students s
     JOIN users u ON u.id = s.user_id
     JOIN universities uni ON uni.id = s.university_id
     ORDER BY u.last_name, u.first_name
     LIMIT ? OFFSET ?"
);
$rows->execute([$paginator->limit(), $paginator->offset()]);
$rows = $rows->fetchAll();

require __DIR__ . '/../includes/layout.php';
page_actions('/admin/student-form.php', 'Add Student');
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchStudents" data-table-search="studentsTable" placeholder="Search students..." class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <select id="filterStatus" data-table-filter="studentsTable" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    <span class="text-xs text-slate-500"><?= $paginator->info() ?></span>
</div>

<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="studentsTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                <tr>
                    <th class="px-6 py-3.5 text-left w-12" data-sort="">#</th>
                    <th class="px-6 py-3.5 text-left" data-sort="name">Student</th>
                    <th class="px-6 py-3.5 text-left" data-sort="number">Number</th>
                    <th class="px-6 py-3.5 text-left" data-sort="university">University</th>
                    <th class="px-6 py-3.5 text-left" data-sort="status">Status</th>
                    <th class="px-6 py-3.5 text-right" data-sort="">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-[11px] font-bold text-white shadow-sm shrink-0"><?= e(strtoupper(substr($r['first_name'],0,1).substr($r['last_name'],0,1))) ?></div>
                        <div>
                            <p class="font-medium text-slate-900"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></p>
                            <p class="text-xs text-slate-500"><?= e($r['email']) ?></p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4"><span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-slate-600"><?= e($r['student_number']) ?></span></td>
                <td class="px-6 py-4 text-slate-600 text-sm"><?= e($r['university_name']) ?></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['user_status']) ?></td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="<?= url('/admin/student-form.php?id=' . $r['id']) ?>" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-all">Edit</a>
                    <a href="<?= url('/admin/student-delete.php?id=' . $r['id']) ?>" onclick="return confirm('Delete this student?')" class="inline-flex items-center gap-1 text-red-600 hover:text-red-700 text-xs font-medium bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-all">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?>
    <div class="p-12 text-center text-slate-500">No students found.</div>
    <?php endif; ?>
</div>
<?= $paginator->render() ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
