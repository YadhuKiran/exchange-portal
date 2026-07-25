<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

$rows = db()->prepare("SELECT * FROM visas WHERE student_id = ? ORDER BY updated_at DESC");
$rows->execute([$stuId]);
$rows = $rows->fetchAll();

$pageTitle = 'My Visas';
$activeNav = 'visas';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><h1 class="text-lg font-bold text-slate-900">My Visas</h1><p class="text-xs text-slate-500">Student visa records</p></div>
    <a href="<?= url('/student/visa-form.php') ?>" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-4 py-2 rounded-xl shadow-md shadow-indigo-500/20 text-sm whitespace-nowrap">+ Add Visa</a>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="stuVisaTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="type">Type</th><th class="px-6 py-3.5 text-left" data-sort="number">Visa No.</th><th class="px-6 py-3.5 text-left" data-sort="expiry">Expiry</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4 font-medium text-slate-700"><?= e($r['visa_type']) ?></td>
                <td class="px-6 py-4 font-mono text-xs text-slate-600"><?= e($r['visa_number'] ?? '—') ?></td>
                <td class="px-6 py-4 text-slate-600 text-xs"><?= $r['expiry_date'] ? date('M j, Y', strtotime($r['expiry_date'])) : '—' ?></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status'], 'sm') ?></td>
                <td class="px-6 py-4 text-right"><a href="<?= url('/student/visa-form.php?id='.$r['id']) ?>" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">Edit</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No visa records.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
