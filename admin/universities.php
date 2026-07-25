<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['admin']);

$pageTitle = 'Universities';
$activeNav = 'universities';
$rows = db()->query(
    'SELECT u.*, c.id AS coord_id, uu.first_name AS coord_first, uu.last_name AS coord_last, uu.email AS coord_email
     FROM universities u
     LEFT JOIN coordinators c ON c.university_id = u.id
     LEFT JOIN users uu ON uu.id = c.user_id
     ORDER BY u.name'
)->fetchAll();

require __DIR__ . '/../includes/layout.php';
page_actions('/admin/university-form.php', 'Add University');
?>
<div class="mb-6 flex items-center justify-between gap-4">
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="searchUniversities" data-table-search="universitiesTable" placeholder="Search universities..." class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
    </div>
    <span class="text-xs text-slate-500"><?= count($rows) ?> total</span>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="universitiesTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="name">Name</th><th class="px-6 py-3.5 text-left" data-sort="code">Code</th><th class="px-6 py-3.5 text-left" data-sort="coordinator">Coordinator</th><th class="px-6 py-3.5 text-left" data-sort="location">Location</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <?php if (!empty($r['logo_path'])): ?>
                            <img src="<?= url('/uploads/' . $r['logo_path']) ?>" class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-500"><?= substr($r['name'], 0, 2) ?></div>
                        <?php endif; ?>
                        <p class="font-medium text-slate-900"><?= e($r['name']) ?></p>
                    </div>
                </td>
                <td class="px-6 py-4"><span class="font-mono text-xs bg-slate-100 px-2 py-1 rounded text-slate-600"><?= e($r['code']) ?></span></td>
                <td class="px-6 py-4">
                    <?php if ($r['coord_id']): ?>
                        <span class="inline-flex items-center gap-1.5">
                            <?= avatar_icon($r['coord_first'] . ' ' . $r['coord_last'], 'sm') ?>
                            <span class="text-slate-700"><?= e($r['coord_first'] . ' ' . $r['coord_last']) ?></span>
                        </span>
                    <?php else: ?>
                        <span class="text-slate-400 italic text-xs">Not assigned</span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-slate-600">
                    <svg class="w-3.5 h-3.5 inline text-slate-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?= e($r['city'] . ', ' . $r['country']) ?>
                </td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status']) ?></td>
                <td class="px-6 py-4 text-right space-x-2">
                    <a href="<?= url('/admin/university-form.php?id='.$r['id']) ?>" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-700 text-xs font-medium bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-all">Edit</a>
                    <a href="<?= url('/admin/university-delete.php?id='.$r['id']) ?>" onclick="return confirm('Delete this university?')" class="inline-flex items-center gap-1 text-red-600 hover:text-red-700 text-xs font-medium bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-all">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No universities found.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
