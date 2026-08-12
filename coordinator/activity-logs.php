<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['coordinator']);



$coord = coordinator_profile((int) current_user()['id']);
$uniId = (int) $coord['university_id'];

$rows = db()->prepare(
    "SELECT al.*, u.first_name, u.last_name
     FROM activity_logs al
     LEFT JOIN users u ON u.id = al.user_id
     WHERE al.entity_type IN ('student','application','document','course','enrollment')
        AND al.entity_id IN (
            SELECT id FROM applications WHERE home_university_id=? OR host_university_id=?
        )
     ORDER BY al.created_at DESC"
);
$rows->execute([$uniId, $uniId]);
$rows = $rows->fetchAll();

$pageTitle = 'Activity Logs';
$activeNav = 'activity-logs';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6">
    <h1 class="text-lg font-bold text-slate-900">Activity Logs</h1>
    <p class="text-xs text-slate-500">Recent activity across your institution</p>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="coordActivityTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="user">User</th><th class="px-6 py-3.5 text-left" data-sort="action">Action</th><th class="px-6 py-3.5 text-left" data-sort="description">Description</th><th class="px-6 py-3.5 text-left" data-sort="date">Date</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4"><div class="flex items-center gap-2"><?= avatar_icon($r['first_name'].' '.$r['last_name'], 'sm') ?><span class="font-medium text-slate-900"><?= e(($r['first_name'] ?? 'System').' '.($r['last_name'] ?? '')) ?></span></div></td>
                <td class="px-6 py-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded-md text-slate-700"><?= e($r['action']) ?></code></td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['description']) ?></td>
                <td class="px-6 py-4 text-xs text-slate-500"><?= date('M j, Y g:i a', strtotime($r['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No activity logs available.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
