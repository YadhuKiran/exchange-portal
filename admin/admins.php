<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$rows = db()->query(
    "SELECT id, first_name, last_name, email, status, created_at FROM users WHERE role='admin' ORDER BY first_name"
)->fetchAll();

$pageTitle = 'Admins';
$activeNav = 'admins';
require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div><h1 class="text-lg font-bold text-slate-900">Admins</h1><p class="text-xs text-slate-500">System administrators</p></div>
    <div class="flex gap-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" data-table-search="adminsTable" placeholder="Search admins..." class="w-full sm:w-56 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        </div>
        <a href="<?= url('/admin/admin-form.php') ?>" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-4 py-2 rounded-xl shadow-md shadow-indigo-500/20 text-sm whitespace-nowrap">+ Add Admin</a>
    </div>
</div>
<div class="table-wrapper bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table id="adminsTable" data-table-paginate="15" class="sortable w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3.5 text-left w-12">#</th><th class="px-6 py-3.5 text-left" data-sort="name">Name</th><th class="px-6 py-3.5 text-left" data-sort="email">Email</th><th class="px-6 py-3.5 text-left" data-sort="status">Status</th><th class="px-6 py-3.5 text-left" data-sort="created">Created</th><th class="px-6 py-3.5 text-right">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php $i = 1; foreach ($rows as $r): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?= $i++ ?></td>
                <td class="px-6 py-4"><div class="flex items-center gap-2"><?= avatar_icon($r['first_name'].' '.$r['last_name'], 'sm') ?><span class="font-medium text-slate-900"><?= e($r['first_name'].' '.$r['last_name']) ?></span></div></td>
                <td class="px-6 py-4 text-slate-600"><?= e($r['email']) ?></td>
                <td class="px-6 py-4"><?= status_badge_enhanced($r['status'], 'sm') ?></td>
                <td class="px-6 py-4 text-xs text-slate-500"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td class="px-6 py-4 text-right">
                    <a href="<?= url('/admin/admin-form.php?id='.$r['id']) ?>" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">Edit</a>
                    <?php if ((int) $r['id'] !== (int) current_user()['id']): ?>
                    <span class="text-slate-300 mx-1">·</span>
                    <a href="<?= url('/admin/admin-form.php?delete='.$r['id']) ?>" class="text-red-500 hover:text-red-600 text-xs font-medium" onclick="return confirm('Delete this admin?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$rows): ?><div class="p-12 text-center text-slate-500">No admin accounts.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
