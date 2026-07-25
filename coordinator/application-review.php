<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['coordinator']);

$id = (int) ($_GET['id'] ?? 0);
$coord = coordinator_profile((int) current_user()['id']);
$uniId = (int) $coord['university_id'];

$app = db()->prepare(
    "SELECT a.*, s.id AS student_id, u.id AS user_id, u.first_name, u.last_name, u.email,
            s.phone, ho.name AS home_uni, hu.name AS host_uni
     FROM applications a
     JOIN students s ON s.id = a.student_id
     JOIN users u ON u.id = s.user_id
     JOIN universities ho ON ho.id = a.home_university_id
     JOIN universities hu ON hu.id = a.host_university_id
     WHERE a.id = ? AND (a.home_university_id = ? OR a.host_university_id = ?)"
);
$app->execute([$id, $uniId, $uniId]);
$app = $app->fetch();
if (!$app) { flash('error', 'Application not found.'); redirect('/coordinator/applications.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action = $_POST['action'];
    $comment = trim($_POST['comment'] ?? '');
    $newStatus = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'under_review');
    db()->prepare('UPDATE applications SET status=?, updated_at=NOW() WHERE id=?')->execute([$newStatus, $id]);
    record_status_change($id, $app['status'], $newStatus, (int) current_user()['id'], $comment ?: null);
    if ($comment) {
        db()->prepare('INSERT INTO activity_logs (user_id, action, description, entity_type, entity_id) VALUES (?,?,?,?,?)')
            ->execute([current_user()['id'], "application.$action", $comment, 'application', $id]);
    }
    $statusLabel = str_replace('_', ' ', $newStatus);
    notify_and_mail(
        (int) $app['user_id'],
        'Application ' . ucwords($statusLabel),
        "Your application #{$id} to {$app['host_uni']} has been {$statusLabel}.",
        'application_status',
        [
            'application_id' => $id,
            'status' => ucwords($statusLabel),
            'notes_html' => $comment ? '<p style="margin-top:12px;padding:12px;background:#f8fafc;border-radius:8px;font-size:13px;color:#64748b"><strong>Coordinator notes:</strong> ' . htmlspecialchars($comment) . '</p>' : '',
        ]
    );
    flash('success', 'Application ' . $newStatus . '.');
    redirect('/coordinator/application-review.php?id='.$id);
}

$docs = db()->prepare(
    "SELECT d.* FROM documents d 
     WHERE d.student_id = ? ORDER BY d.uploaded_at DESC"
);
$docs->execute([$app['student_id']]);
$docs = $docs->fetchAll();

$timeline = db()->prepare(
    "SELECT * FROM application_status_history WHERE application_id = ? ORDER BY created_at DESC"
);
$timeline->execute([$id]);
$timeline = $timeline->fetchAll();

$pageTitle = 'Review Application';
$activeNav = 'applications';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Review Application</h1>
            <p class="text-sm text-slate-500">#<?= $id ?> · <?= e($app['host_uni']) ?></p>
        </div>
        <?= status_badge_enhanced($app['status'], 'lg') ?>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
            <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100">Student Profile</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div><span class="text-slate-500 text-xs block">Name</span><span class="font-medium"><?= e($app['first_name'].' '.$app['last_name']) ?></span></div>
                <div><span class="text-slate-500 text-xs block">Email</span><span><?= e($app['email']) ?></span></div>
                <div><span class="text-slate-500 text-xs block">Phone</span><span><?= e($app['phone'] ?? '—') ?></span></div>
                <div><span class="text-slate-500 text-xs block">Student #</span><span><?= e($app['student_number'] ?? '—') ?></span></div>
                <div class="col-span-3"><span class="text-slate-500 text-xs block">Personal Statement</span><span class="text-slate-700"><?= nl2br(e($app['personal_statement'] ?? 'No statement provided.')) ?></span></div>
            </div>
        </div>

        <?php if ($app['status'] === 'submitted' || $app['status'] === 'under_review'): ?>
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
            <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100">Decision</h2>
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Reviewer Comment</label>
                    <textarea name="comment" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Optional note..."></textarea></div>
                <div class="flex gap-3">
                    <button type="submit" name="action" value="under_review" class="btn-hover bg-amber-500 hover:bg-amber-400 text-white font-semibold px-5 py-2 rounded-xl text-sm">Mark In Review</button>
                    <button type="submit" name="action" value="approve" class="btn-hover bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-white font-semibold px-5 py-2 rounded-xl shadow-md shadow-emerald-500/20 text-sm">Approve</button>
                    <button type="submit" name="action" value="reject" class="btn-hover bg-red-500 hover:bg-red-400 text-white font-semibold px-5 py-2 rounded-xl text-sm" onclick="return confirm('Reject this application?')">Reject</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
            <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100">Uploaded Documents (<?= count($docs) ?>)</h2>
            <?php if ($docs): ?>
            <div class="grid gap-3">
            <?php foreach ($docs as $d): ?>
                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50 hover:bg-slate-100/80 transition-all">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <div><span class="text-sm font-medium text-slate-700"><?= e($d['title']) ?></span><br><span class="text-xs text-slate-400"><?= e($d['file_name']) ?></span></div>
                    </div>
                    <?= status_badge_enhanced($d['status'], 'sm') ?>
                </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-slate-500">No documents uploaded yet.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
            <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100">Status Timeline</h2>
            <?php if ($timeline): ?>
            <div class="space-y-3">
            <?php foreach ($timeline as $t): ?>
                <div class="flex items-start gap-3">
                    <div class="w-2 h-2 rounded-full bg-brand-500 mt-2 shrink-0 ring-4 ring-brand-500/10"></div>
                    <div><p class="text-sm font-medium text-slate-700"><?= ucfirst(str_replace('_', ' ', $t['to_status'])) ?></p>
                    <p class="text-xs text-slate-400"><?= e($t['comment'] ?? '') ?> · <?= date('M j, Y g:i a', strtotime($t['created_at'])) ?></p></div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-slate-500">No timeline events.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
