<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$pageTitle = 'Activity Logs';
$activeNav = 'activity';

$actionFilter = $_GET['action'] ?? '';
$userFilter = (int) ($_GET['user_id'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$sql = 'SELECT al.*, u.first_name, u.last_name FROM activity_logs al LEFT JOIN users u ON u.id=al.user_id WHERE 1=1';
$params = [];

if ($actionFilter) {
    $sql .= ' AND al.action LIKE ?';
    $params[] = '%' . $actionFilter . '%';
}
if ($userFilter) {
    $sql .= ' AND al.user_id = ?';
    $params[] = $userFilter;
}
if ($dateFrom) {
    $sql .= ' AND DATE(al.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo) {
    $sql .= ' AND DATE(al.created_at) <= ?';
    $params[] = $dateTo;
}
$sql .= ' ORDER BY al.created_at DESC LIMIT 200';

$rows = enterprise_tables_ready() ? db()->prepare($sql)->execute($params)->fetchAll() : [];

$users = enterprise_tables_ready() ? db()->query('SELECT id, first_name, last_name FROM users ORDER BY first_name')->fetchAll() : [];

if (isset($_GET['export']) && $rows) {
    $csv = [];
    foreach ($rows as $r) {
        $csv[] = [$r['id'], $r['first_name'] ?? 'System', $r['action'], $r['description'], $r['ip_address'] ?? '', $r['created_at']];
    }
    stream_csv('activity_logs_' . date('Y-m-d') . '.csv',
        ['ID', 'User', 'Action', 'Description', 'IP', 'Timestamp'], $csv);
}

require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6">
    <form method="get" class="flex flex-wrap items-end gap-3 bg-white rounded-2xl border border-slate-200/60 shadow-sm p-4">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Action</label>
            <input type="text" name="action" value="<?= e($actionFilter) ?>" placeholder="e.g. application" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">User</label>
            <select name="user_id" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-40 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none">
                <option value="">All users</option>
                <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $userFilter === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['first_name'].' '.$u['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-36 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-36 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-all">Filter</button>
        <a href="<?= url('/admin/activity-logs.php') ?>" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-all">Clear</a>
        <a href="?export=1<?= $actionFilter ? '&action='.urlencode($actionFilter) : '' ?><?= $userFilter ? '&user_id='.$userFilter : '' ?>" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-all ml-auto">Export CSV</a>
    </form>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="activityTable" data-table-paginate="20" class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-5 py-3.5 text-left w-12">#</th><th class="px-5 py-3.5 text-left">Time</th><th class="px-5 py-3.5 text-left">User</th><th class="px-5 py-3.5 text-left">Action</th><th class="px-5 py-3.5 text-left">Description</th><th class="px-5 py-3.5 text-left">IP</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-5 py-3 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-5 py-3 text-slate-500 whitespace-nowrap text-xs"><?= e(date('M j, Y g:i A', strtotime($r['created_at']))) ?></td>
                <td class="px-5 py-3"><div class="flex items-center gap-2"><?= avatar_icon(trim(($r['first_name'] ?? 'S') . ' ' . ($r['last_name'] ?? '')), 'sm') ?><span class="text-sm font-medium text-slate-700"><?= e(trim(($r['first_name'] ?? 'System') . ' ' . ($r['last_name'] ?? ''))) ?></span></div></td>
                <td class="px-5 py-3"><span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-slate-600"><?= e($r['action']) ?></span></td>
                <td class="px-5 py-3 text-slate-600 max-w-xs truncate"><?= e($r['description']) ?></td>
                <td class="px-5 py-3 text-slate-400 text-xs font-mono"><?= e($r['ip_address'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No activity logs found. Import the enterprise migration.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
