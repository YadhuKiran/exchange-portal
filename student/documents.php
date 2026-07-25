<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

$rows = db()->prepare(
    "SELECT d.* FROM documents d
     WHERE d.student_id = ? ORDER BY d.uploaded_at DESC"
);
$rows->execute([$stuId]);
$rows = $rows->fetchAll();

$pageTitle = 'My Documents';
$activeNav = 'documents';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><h1 class="text-lg font-bold text-slate-900">My Documents</h1><p class="text-xs text-slate-500">Upload and manage documents</p></div>
    <a href="<?= url('/student/document-upload.php') ?>" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-4 py-2 rounded-xl shadow-md shadow-indigo-500/20 text-sm whitespace-nowrap">+ Upload</a>
</div>
<div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
        <div><h3 class="font-bold text-indigo-900 text-sm">Passport</h3><p class="text-xs text-indigo-700/70">Required for travel</p></div>
        <a href="<?= url('/student/document-upload.php?title=Passport') ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-all shadow-sm shadow-indigo-500/20">Upload</a>
    </div>
    <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
        <div><h3 class="font-bold text-rose-900 text-sm">Visa Copy</h3><p class="text-xs text-rose-700/70">Host country visa</p></div>
        <a href="<?= url('/student/document-upload.php?title=Visa+Copy') ?>" class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-all shadow-sm shadow-rose-500/20">Upload</a>
    </div>
    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
        <div><h3 class="font-bold text-emerald-900 text-sm">Transcripts</h3><p class="text-xs text-emerald-700/70">Academic records</p></div>
        <a href="<?= url('/student/document-upload.php?title=Transcripts') ?>" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-all shadow-sm shadow-emerald-500/20">Upload</a>
    </div>
</div>

<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="stuDocsTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="type">Type</th><th class="px-6 py-3.5 text-left" data-sort="file">File</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-left" data-sort="uploaded">Uploaded</th>
                <th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4 font-medium text-slate-700"><?= e($r['title']) ?></td>
                <td class="px-6 py-4 text-xs text-slate-500"><?= e($r['file_name']) ?></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status'], 'sm') ?></td>
                <td class="px-6 py-4 text-xs text-slate-500"><?= date('M j, Y', strtotime($r['uploaded_at'])) ?></td>
                <td class="px-6 py-4 text-right">
                    <a href="<?= url('/download.php?id='.$r['id']) ?>" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-all" title="View Document">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?>
    <div class="p-12 text-center text-slate-500">
        <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        <p>No documents uploaded. <a href="<?= url('/student/document-upload.php') ?>" class="text-indigo-600 hover:underline">Upload →</a></p>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
