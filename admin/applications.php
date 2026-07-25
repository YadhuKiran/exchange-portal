<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$pageTitle = 'Applications';
$activeNav = 'applications';

$rows = db()->query(
    "SELECT a.*, u.first_name, u.last_name, ho.name AS home_uni, hu.name AS host_uni
     FROM applications a
     JOIN students s ON s.id = a.student_id
     JOIN users u ON u.id = s.user_id
     JOIN universities ho ON ho.id = a.home_university_id
     JOIN universities hu ON hu.id = a.host_university_id
     ORDER BY a.updated_at DESC"
)->fetchAll();

require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="searchApplications" data-table-search="applicationsTable" placeholder="Search applications..." class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <select id="filterStatus" data-table-filter="applicationsTable" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="submitted">Submitted</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-xs text-slate-500"><?= count($rows) ?> total</span>
    </div>
</div>

<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="applicationsTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold">
                <tr>
                    <th class="px-6 py-3.5 text-left w-12" data-sort="">#</th>
                    <th class="px-6 py-3.5 text-left" data-sort="student">Student</th>
                    <th class="px-6 py-3.5 text-left" data-sort="route">Route</th>
                    <th class="px-6 py-3.5 text-left" data-sort="semester">Semester</th>
                    <th class="px-6 py-3.5 text-left" data-sort="status">Status</th>
                    <th class="px-6 py-3.5 text-right" data-sort="">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4">
                    <p class="font-medium text-slate-900"><?= e($r['first_name'].' '.$r['last_name']) ?></p>
                </td>
                <td class="px-6 py-4">
                    <span class="text-xs text-slate-500"><?= e($r['home_uni']) ?></span>
                    <svg class="w-3 h-3 inline text-slate-300 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-xs font-medium text-slate-700"><?= e($r['host_uni']) ?></span>
                </td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['semester']) ?></td>
                <td class="px-6 py-4" data-status="<?= e($r['status']) ?>"><?= status_badge_enhanced($r['status']) ?></td>
                <td class="px-6 py-4 text-right">
                    <a href="<?= url('/admin/application-view.php?id='.$r['id']) ?>" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-all">Review
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?>
    <div class="p-12 text-center text-slate-500">No applications found.</div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
