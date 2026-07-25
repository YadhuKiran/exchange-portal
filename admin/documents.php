<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$pageTitle = 'Documents';
$activeNav = 'documents';

$rows = db()->query(
    "SELECT d.*, u.first_name, u.last_name FROM documents d
     JOIN students s ON s.id = d.student_id
     JOIN users u ON u.id = s.user_id
     ORDER BY d.uploaded_at DESC"
)->fetchAll();

require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchDocs" data-table-search="documentsTable" placeholder="Search documents..." class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <select id="filterDocStatus" data-table-filter="documentsTable" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
    <span class="text-xs text-slate-500"><?= count($rows) ?> total</span>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="documentsTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="title">Document</th><th class="px-6 py-3.5 text-left" data-sort="student">Student</th>
                <th class="px-6 py-3.5 text-left" data-sort="size">Size</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th>
                <th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4">
                    <p class="font-medium text-slate-900"><?= e($r['title']) ?></p>
                    <p class="text-xs text-slate-500 font-mono truncate max-w-[200px]"><?= e($r['file_name']) ?></p>
                </td>
                <td class="px-6 py-4"><div class="flex items-center gap-2"><?= avatar_icon($r['first_name'].' '.$r['last_name'], 'sm') ?><span class="font-medium text-slate-700"><?= e($r['first_name'].' '.$r['last_name']) ?></span></div></td>
                <td class="px-6 py-4 text-slate-500 text-xs"><?= round($r['file_size']/1024) ?> KB</td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status']) ?></td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <?php if ($r['status'] === 'pending'): ?>
                        <form method="post" action="<?= url('/admin/document-action.php') ?>" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" name="action" value="approve" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Approve">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <button type="submit" name="action" value="reject" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Reject">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                        <?php endif; ?>
                        <a href="<?= url('/download.php?id='.$r['id']) ?>" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-all" title="View Document">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            View
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No documents found.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
