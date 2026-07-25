<?php
/** @var int $feedLimit */
/** @var int|null $feedUniversityId */
/** @var int|null $feedUserId */
$feedLimit = $feedLimit ?? 8;
$items = fetch_activity_feed($feedLimit, $feedUniversityId ?? null, $feedUserId ?? null);
?>
<div class="bg-white rounded-xl ring-1 ring-slate-200/60 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Recent Activity</h2>
    </div>
    <div class="divide-y divide-slate-50 max-h-80 overflow-y-auto">
        <?php if (!$items): ?>
        <p class="p-5 text-sm text-slate-500">No activity recorded yet.</p>
        <?php else: foreach ($items as $item): ?>
        <div class="px-5 py-3 hover:bg-slate-50/80">
            <p class="text-sm text-slate-800"><?= e($item['description']) ?></p>
            <p class="text-xs text-slate-400 mt-1">
                <?= e(trim(($item['first_name'] ?? 'System') . ' ' . ($item['last_name'] ?? ''))) ?>
                · <?= e(date('M j, g:i A', strtotime($item['created_at']))) ?>
            </p>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
