<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['admin']);

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = (int) db()->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'contact.submission'")->fetchColumn();
$rows = db()->prepare(
    "SELECT * FROM activity_logs WHERE action = 'contact.submission' ORDER BY
     CASE WHEN JSON_EXTRACT(metadata, '$.read') IS NULL THEN 0 ELSE 1 END,
     created_at DESC LIMIT ? OFFSET ?"
);
$rows->execute([$perPage, $offset]);
$messages = $rows->fetchAll();

$pageTitle = 'Contact Messages';
$activeNav = 'contact-messages';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Contact Messages</h1>
        <p class="text-sm text-slate-500 mt-1">Messages submitted through the public contact form</p>
    </div>
    <span class="text-xs text-slate-500"><?= $total ?> total</span>
</div>

<div class="space-y-4">
    <?php if (!$messages): ?>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-12 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-sm font-medium text-slate-700">No messages yet</p>
        <p class="text-xs text-slate-500 mt-1">Contact form submissions will appear here.</p>
    </div>
    <?php endif; ?>

    <?php foreach ($messages as $m):
        $meta = json_decode($m['metadata'] ?? '{}', true);
        $isRead = !empty($meta['read']);
    ?>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover <?= $isRead ? 'opacity-75' : 'border-l-4 border-l-indigo-500' ?>">
        <div class="flex items-start justify-between gap-4 mb-3">
            <div class="flex items-center gap-3">
                <?php if (!$isRead): ?>
                <span class="w-2 h-2 rounded-full bg-indigo-600 shrink-0" title="Unread"></span>
                <?php endif; ?>
                <div>
                    <p class="font-semibold text-slate-900 text-sm"><?= e($meta['name'] ?? 'Unknown') ?></p>
                    <a href="mailto:<?= e($meta['email'] ?? '') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium"><?= e($meta['email'] ?? '') ?></a>
                </div>
            </div>
            <span class="text-xs text-slate-400 shrink-0"><?= date('M j, Y g:i A', strtotime($m['created_at'])) ?></span>
        </div>
        <p class="text-sm text-slate-600 bg-slate-50 rounded-xl p-4 border border-slate-100"><?= e($meta['message'] ?? '') ?></p>
        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
            <div class="flex items-center gap-2">
                <span class="text-[11px] text-slate-400">IP: <?= e($m['ip_address'] ?? 'N/A') ?></span>
                <?php if ($isRead): ?>
                <span class="text-[11px] text-slate-400">&middot;</span>
                <span class="text-[11px] text-emerald-600 font-medium">Read</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= url('/admin/contact-messages-action.php?id=' . $m['id'] . '&action=' . ($isRead ? 'unread' : 'read')) ?>"
                   class="inline-flex items-center gap-1 text-xs font-medium <?= $isRead ? 'text-slate-500 hover:text-indigo-600 bg-slate-100 hover:bg-indigo-50' : 'text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100' ?> px-3 py-1.5 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $isRead ? 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' : 'M5 13l4 4L19 7' ?>"/></svg>
                    <?= $isRead ? 'Mark Unread' : 'Mark Read' ?>
                </a>
                <a href="mailto:<?= e($meta['email'] ?? '') ?>?subject=Re: Global Exchange Inquiry" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Reply
                </a>
                <a href="<?= url('/admin/contact-messages-action.php?id=' . $m['id'] . '&action=delete') ?>"
                   onclick="return confirm('Delete this message?')"
                   class="inline-flex items-center gap-1 text-xs font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($total > $perPage): ?>
<div class="mt-6 flex justify-center gap-2">
    <?php $last = (int) ceil($total / $perPage); for ($p = 1; $p <= $last; $p++): ?>
    <a href="?page=<?= $p ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
