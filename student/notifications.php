<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$userId = (int) current_user()['id'];

$rows = db()->prepare(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC"
);
$rows->execute([$userId]);
$rows = $rows->fetchAll();

$pageTitle = 'Notifications';
$activeNav = 'notifications';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6"><h1 class="text-lg font-bold text-slate-900">Notifications</h1><p class="text-xs text-slate-500">Alerts and updates</p></div>
<div class="max-w-2xl space-y-3">
    <?php if ($rows): ?>
        <?php foreach ($rows as $n): ?>
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-4 flex items-start gap-3 card-hover <?= $n['is_read'] ? '' : 'ring-2 ring-brand-500/20 bg-brand-50/30' ?>">
            <div class="w-8 h-8 rounded-full <?= $n['is_read'] ? 'bg-slate-100 text-slate-400' : 'bg-brand-500/10 text-brand-600' ?> flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900"><?= nl2br(e($n['message'])) ?></p>
                <p class="text-xs text-slate-400 mt-1"><?= date('M j, Y g:i a', strtotime($n['created_at'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="p-12 text-center text-slate-500">No notifications yet.</div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
