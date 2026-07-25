<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$reviews = db()->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Manage Reviews';
$activeNav = 'reviews';
require __DIR__ . '/../includes/layout.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Testimonials & Reviews</h2>
        <p class="text-sm text-slate-500 mt-0.5">Manage the student reviews displayed on the public home page.</p>
    </div>
    <a href="<?= url('/admin/review-form.php') ?>" class="btn-hover inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md shadow-indigo-500/20">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add Review
    </a>
</div>

<div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                <tr>
                    <th class="px-6 py-3.5">Reviewer</th>
                    <th class="px-6 py-3.5">Role/Program</th>
                    <th class="px-6 py-3.5">Excerpt</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($reviews as $r): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-100 to-violet-100 border border-indigo-200 flex items-center justify-center text-[11px] font-bold text-indigo-600">
                                <?= e($r['initials']) ?>
                            </div>
                            <span class="font-medium text-slate-900 text-sm"><?= e($r['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600"><?= e($r['role']) ?></td>
                    <td class="px-6 py-4 text-sm text-slate-500 truncate max-w-xs" title="<?= e($r['text']) ?>"><?= e($r['text']) ?></td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= url('/admin/review-form.php?id=' . $r['id']) ?>" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <a href="<?= url('/admin/review-delete.php?id=' . $r['id']) ?>" onclick="return confirm('Are you sure you want to delete this review?');" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$reviews): ?>
                <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500 text-sm">No reviews found. Click "Add Review" to create one.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
