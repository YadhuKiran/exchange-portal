<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    verify_csrf();
    $appId = (int) ($_POST['id'] ?? 0);
    $app = db()->prepare('SELECT * FROM applications WHERE id=? AND student_id=? AND status=?');
    $app->execute([$appId, $stuId, 'draft']);
    $app = $app->fetch();
    if ($app) {
        db()->prepare("UPDATE applications SET status='submitted', submitted_at=NOW() WHERE id=?")->execute([$appId]);
        record_status_change($appId, 'draft', 'submitted', (int) current_user()['id'], 'Application submitted by student');
        $coords = db()->prepare("SELECT c.user_id, u.first_name, u.last_name, u.email FROM coordinators c JOIN users u ON u.id = c.user_id WHERE c.university_id=?");
        $coords->execute([$app['host_university_id']]);
        $coordStr = '';
        foreach ($coords as $c) {
            notify((int) $c['user_id'], 'New Application', "A new exchange application has been submitted for your review.");
            $coordStr = $c['first_name'] . ' ' . $c['last_name'] . ' at ' . $c['email'];
        }
        
        $msg = 'Application submitted successfully.';
        if ($coordStr) {
            $msg .= ' Please contact your assigned coordinator, ' . $coordStr . ', for further assistance.';
        }
        flash('success', $msg);
        if (function_exists('log_activity')) {
            log_activity('application.submitted', 'Application submitted to host university.', 'application', $appId);
        }
    }
    redirect('/student/applications.php');
}

$rows = db()->prepare(
    "SELECT a.*, ho.name AS home_uni, hu.name AS host_uni
     FROM applications a
     JOIN universities ho ON ho.id = a.home_university_id
     JOIN universities hu ON hu.id = a.host_university_id
     WHERE a.student_id = ?
     ORDER BY a.updated_at DESC"
);
$rows->execute([$stuId]);
$rows = $rows->fetchAll();

$pageTitle = 'My Applications';
$activeNav = 'applications';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><h1 class="text-lg font-bold text-slate-900">My Applications</h1><p class="text-xs text-slate-500">Track your exchange applications</p></div>
    <div class="flex gap-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" data-table-search="stuAppsTable" placeholder="Search..." class="w-full sm:w-56 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <a href="<?= url('/student/application-form.php') ?>" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-4 py-2 rounded-xl shadow-md shadow-indigo-500/20 text-sm whitespace-nowrap">+ New</a>
    </div>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="stuAppsTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="route">Route</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-left" data-sort="created">Created</th><th class="px-6 py-3.5 text-left" data-sort="updated">Updated</th><th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4"><span class="text-xs text-slate-500"><?= e($r['home_uni']) ?></span> <svg class="w-3 h-3 inline text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg> <span class="font-medium text-slate-700"><?= e($r['host_uni']) ?></span></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status'], 'sm') ?></td>
                <td class="px-6 py-4 text-xs text-slate-500"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td class="px-6 py-4 text-xs text-slate-500"><?= date('M j, Y', strtotime($r['updated_at'])) ?></td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                    <?php if ($r['status'] === 'draft'): ?>
                        <form method="post" class="inline" onsubmit="return confirm('Submit this application for review?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="submit">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="text-emerald-600 hover:text-emerald-700 text-xs font-medium">Submit</button>
                        </form>
                    <?php endif; ?>
                    <a href="<?= url('/student/application-form.php?id='.$r['id']) ?>" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">Edit</a>
                    <a href="<?= url('/student/application-status.php?id='.$r['id']) ?>" class="text-slate-500 hover:text-slate-700 text-xs font-medium" title="View Timeline">Timeline</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No applications yet. <a href="<?= url('/student/application-form.php') ?>" class="text-indigo-600 hover:underline">Start one →</a></div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
