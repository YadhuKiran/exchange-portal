<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];
$id = (int) ($_GET['id'] ?? 0);

$app = db()->prepare(
    "SELECT a.*, ho.name AS home_uni, hu.name AS host_uni
     FROM applications a
     JOIN universities ho ON ho.id = a.home_university_id
     JOIN universities hu ON hu.id = a.host_university_id
     WHERE a.id = ? AND a.student_id = ?"
);
$app->execute([$id, $stuId]);
$app = $app->fetch();
if (!$app) { flash('error', 'Application not found.'); redirect('/student/applications.php'); }

$timeline = fetch_application_timeline($id);

$docs = db()->prepare("SELECT * FROM documents WHERE application_id = ? OR (student_id = ? AND application_id IS NULL) ORDER BY uploaded_at DESC");
$docs->execute([$id, $student['id']]);
$docs = $docs->fetchAll();

$pageTitle = 'Application Timeline';
$activeNav = 'applications';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="<?= url('/student/applications.php') ?>" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Applications
            </a>
            <h1 class="text-lg font-bold text-slate-900">Application Timeline</h1>
            <p class="text-sm text-slate-500">#<?= $id ?> · <?= e($app['home_uni']) ?> → <?= e($app['host_uni']) ?> · <?= e($app['semester']) ?></p>
        </div>
        <?= status_badge_enhanced($app['status'], 'lg') ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
                <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Status History
                </h2>
                <?php if ($timeline): ?>
                <div class="relative pl-6 space-y-0">
                    <div class="absolute left-[7px] top-2 bottom-2 w-0.5 bg-slate-200"></div>
                    <?php foreach ($timeline as $t): $isLatest = $t === $timeline[0]; ?>
                    <div class="relative pb-6 last:pb-0">
                        <div class="absolute -left-[21px] top-1 w-[14px] h-[14px] rounded-full border-2 <?= $isLatest ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 bg-white' ?> flex items-center justify-center">
                            <?php if ($isLatest): ?><div class="w-2 h-2 rounded-full bg-indigo-500"></div><?php endif; ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium <?= $isLatest ? 'text-indigo-700' : 'text-slate-700' ?>">
                                <?= ucwords(str_replace('_', ' ', $t['to_status'])) ?>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <?= $t['comment'] ? e($t['comment']) . ' · ' : '' ?>
                                <?= $t['changed_by_name'] ?? 'System' ?> ·
                                <?= date('M j, Y g:i a', strtotime($t['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-slate-500 text-center py-6">No timeline events recorded yet.</p>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
                <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100">Personal Statement</h2>
                <div class="text-sm text-slate-700 leading-relaxed"><?= nl2br(e($app['personal_statement'] ?? 'No personal statement provided.')) ?></div>
                <?php if ($app['coordinator_notes']): ?>
                <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
                    <p class="text-xs font-medium text-amber-700 mb-1">Coordinator Notes</p>
                    <p class="text-sm text-amber-800"><?= nl2br(e($app['coordinator_notes'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
                <h2 class="text-sm font-semibold text-slate-900 mb-4 pb-3 border-b border-slate-100">Documents</h2>
                <?php if ($docs): ?>
                <div class="space-y-2">
                    <?php foreach ($docs as $d): ?>
                    <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-700 truncate"><?= e($d['title']) ?></p>
                            <p class="text-[10px] text-slate-400"><?= e($d['file_name']) ?></p>
                        </div>
                        <?= status_badge($d['status']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-slate-500 text-center py-4">No documents uploaded.</p>
                <?php endif; ?>
                <a href="<?= url('/student/document-upload.php?app_id='.$id) ?>" class="mt-3 block text-center text-xs font-medium text-indigo-600 hover:text-indigo-700">+ Upload Document</a>
            </div>

            <div class="bg-slate-50 rounded-2xl border border-slate-200/60 p-4">
                <h3 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-2">Application Info</h3>
                <dl class="space-y-1.5 text-xs">
                    <div class="flex justify-between"><dt class="text-slate-500">Home</dt><dd class="font-medium text-slate-700"><?= e($app['home_uni']) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Host</dt><dd class="font-medium text-slate-700"><?= e($app['host_uni']) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Semester</dt><dd class="font-medium text-slate-700"><?= e($app['semester']) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Created</dt><dd class="font-medium text-slate-700"><?= date('M j, Y', strtotime($app['created_at'])) ?></dd></div>
                    <?php if ($app['submitted_at']): ?>
                    <div class="flex justify-between"><dt class="text-slate-500">Submitted</dt><dd class="font-medium text-slate-700"><?= date('M j, Y', strtotime($app['submitted_at'])) ?></dd></div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
