<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT a.*, u.first_name, u.last_name, u.email, ho.name AS home_uni, hu.name AS host_uni
     FROM applications a
     JOIN students s ON s.id = a.student_id
     JOIN users u ON u.id = s.user_id
     JOIN universities ho ON ho.id = a.home_university_id
     JOIN universities hu ON hu.id = a.host_university_id
     WHERE a.id = ?"
);
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) { flash('error', 'Not found.'); redirect('/admin/applications.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $status = $_POST['status'] ?? $app['status'];
    $notes = trim($_POST['coordinator_notes'] ?? '');
    record_status_change($id, $app['status'], $status, (int) current_user()['id'], $notes ?: null);
    db()->prepare('UPDATE applications SET status=?, coordinator_notes=? WHERE id=?')
        ->execute([$status, $notes ?: null, $id]);
    $stmt = db()->prepare('SELECT user_id FROM students WHERE id = ?');
    $stmt->execute([$app['student_id']]);
    $uid = (int) $stmt->fetchColumn();
    $statusLabel = str_replace('_', ' ', $status);
    notify_and_mail($uid, 'Application Updated', "Your application #{$id} status is now: {$statusLabel}.",
        'application_status', [
            'application_id' => $id,
            'status' => ucwords($statusLabel),
            'notes_html' => $notes ? '<p style="margin-top:12px;padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;color:#64748b"><strong>Coordinator notes:</strong> ' . htmlspecialchars($notes) . '</p>' : '',
        ]);
    flash('success', 'Application updated.');
    redirect('/admin/application-view.php?id=' . $id);
}

$docs = db()->prepare('SELECT * FROM documents WHERE application_id = ?');
$docs->execute([$id]);
$docs = $docs->fetchAll();

$pageTitle = 'Application #' . $id;
$activeNav = 'applications';
require __DIR__ . '/../includes/layout.php';
?>

<div class="mb-6 flex items-center gap-3">
    <a href="<?= url('/admin/applications.php') ?>" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Applications
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-start gap-4">
                    <?= avatar_icon($app['first_name'].' '.$app['last_name'], 'lg') ?>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900"><?= e($app['first_name'].' '.$app['last_name']) ?></h2>
                        <p class="text-sm text-slate-500"><?= e($app['email']) ?></p>
                    </div>
                </div>
                <?= status_badge_enhanced($app['status']) ?>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-6 p-4 bg-slate-50 rounded-xl">
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Home University</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1"><?= e($app['home_uni']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Host University</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1"><?= e($app['host_uni']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Semester</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1"><?= e($app['semester']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Submitted</p>
                    <p class="text-sm font-semibold text-slate-900 mt-1"><?= e($app['submitted_at'] ? date('M j, Y', strtotime($app['submitted_at'])) : '—') ?></p>
                </div>
            </div>
            <?php if ($app['personal_statement']): ?>
            <div class="mt-4">
                <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-2">Personal Statement</p>
                <div class="p-4 bg-slate-50 rounded-xl text-sm text-slate-700 leading-relaxed"><?= nl2br(e($app['personal_statement'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
            <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Documents
            </h3>
            <?php if (!$docs): ?>
            <p class="text-sm text-slate-500 text-center py-6">No documents linked to this application.</p>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($docs as $d): ?>
                <div class="flex items-center justify-between py-3 px-4 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <div>
                            <p class="text-sm font-medium text-slate-900"><?= e($d['title']) ?></p>
                            <p class="text-xs text-slate-500"><?= e($d['file_name']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?= status_badge_enhanced($d['status']) ?>
                        <a href="<?= url('/download.php?id='.$d['id']) ?>" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
            <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Update Status
            </h3>
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    <?php foreach (['draft','submitted','under_review','approved','rejected'] as $s): ?>
                    <option value="<?= $s ?>" <?= $app['status']===$s?'selected':'' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="coordinator_notes" rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Add notes..."><?= e($app['coordinator_notes'] ?? '') ?></textarea>
                <button type="submit" class="btn-hover w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white py-2.5 rounded-xl text-sm font-semibold shadow-md shadow-indigo-500/20">
                    Save Changes
                </button>
            </form>
        </div>
        <?php $timelineApplicationId = $id; require __DIR__ . '/../includes/widgets/application_timeline.php'; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
