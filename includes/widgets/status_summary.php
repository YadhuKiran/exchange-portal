<?php
$statusRows = db()->query(
    "SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status ORDER BY cnt DESC"
)->fetchAll();
$total = array_sum(array_column($statusRows, 'cnt')) ?: 1;
?>
<div class="bg-white rounded-xl ring-1 ring-slate-200/60 shadow-sm p-5">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-4">Application Status Summary</h2>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($statusRows as $row): ?>
        <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-100 text-center min-w-[80px]">
            <p class="text-lg font-bold text-slate-900"><?= (int) $row['cnt'] ?></p>
            <p class="text-[10px] uppercase tracking-wide text-slate-500"><?= e(str_replace('_', ' ', $row['status'])) ?></p>
            <p class="text-[10px] text-brand-600"><?= round($row['cnt'] / $total * 100) ?>%</p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
