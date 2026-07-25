<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$user = current_user();
if (isset($_GET['read'])) {
    db()->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')
        ->execute([(int)$_GET['read'], $user['id']]);
    redirect('/admin/notifications.php');
}
if (isset($_POST['mark_all'])) {
    verify_csrf();
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);
    flash('success', 'All marked as read.');
    redirect('/admin/notifications.php');
}

$pageTitle = 'Notifications';
$activeNav = 'notifications';
$rows = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC');
$rows->execute([$user['id']]);
$rows = $rows->fetchAll();

require __DIR__ . '/../includes/layout.php';
?>
<div class="flex items-center justify-between mb-6">
    <h2 class="text-sm font-semibold text-slate-900"><?= count($rows) ?> notification<?= count($rows) !== 1 ? 's' : '' ?></h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button name="mark_all" value="1" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">Mark all as read</button>
    </form>
</div>
<div class="space-y-3">
<?php foreach ($rows as $n): ?>
<div class="bg-white rounded-2xl border border-slate-200/60 p-5 shadow-sm transition-all <?= $n['is_read']?'':'border-l-4 border-l-indigo-500 bg-gradient-to-r from-indigo-50/30 to-white' ?>">
    <div class="flex justify-between items-start">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl <?= $n['is_read'] ? 'bg-slate-100' : 'bg-indigo-100' ?> flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 <?= $n['is_read'] ? 'text-slate-400' : 'text-indigo-600' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-sm text-slate-900"><?= e($n['title']) ?></h3>
                <p class="text-sm text-slate-600 mt-1"><?= e($n['message']) ?></p>
                <p class="text-xs text-slate-400 mt-2"><?= e(date('M j, Y g:i A', strtotime($n['created_at']))) ?></p>
            </div>
        </div>
        <?php if (!$n['is_read']): ?>
        <a href="?read=<?= $n['id'] ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium shrink-0 ml-4">Mark read</a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php if (!$rows): ?>
<div class="flex flex-col items-center justify-center py-16 text-slate-500">
    <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
    <p class="text-sm font-medium">No notifications</p>
</div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
