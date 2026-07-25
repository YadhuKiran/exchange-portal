<?php
/** @var int $timelineApplicationId */
$timeline = fetch_application_timeline($timelineApplicationId);
?>
<div class="bg-white rounded-xl ring-1 ring-slate-200/60 shadow-sm p-5">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-4">Status Timeline</h2>
    <?php if (!$timeline): ?>
    <p class="text-sm text-slate-500">No status history yet.</p>
    <?php else: ?>
    <ol class="relative border-l border-slate-200 ml-2 space-y-4">
        <?php foreach ($timeline as $h): ?>
        <li class="ml-4">
            <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-brand-500 ring-4 ring-white"></span>
            <p class="text-sm font-medium text-slate-900"><?= e(ucwords(str_replace('_', ' ', $h['to_status']))) ?></p>
            <p class="text-xs text-slate-500"><?= e($h['first_name'] . ' ' . $h['last_name']) ?> (<?= e($h['role']) ?>) · <?= e(date('M j, Y g:i A', strtotime($h['created_at']))) ?></p>
            <?php if ($h['comment']): ?><p class="text-xs text-slate-600 mt-1"><?= e($h['comment']) ?></p><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>
</div>
