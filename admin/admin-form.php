<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);

if (isset($_GET['delete']) && $id) {
    verify_csrf();
    if ((int) current_user()['id'] === $id) { flash('error', 'You cannot delete yourself.'); redirect('/admin/admins.php'); }
    db()->prepare('DELETE FROM users WHERE id=? AND role=?')->execute([$id, 'admin']);
    flash('success', 'Admin deleted.');
    redirect('/admin/admins.php');
}

$admin = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id=? AND role=?');
    $stmt->execute([$id, 'admin']);
    $admin = $stmt->fetch();
    if (!$admin) { flash('error', 'Admin not found.'); redirect('/admin/admins.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $pw = trim($_POST['password'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($id) {
        if ($pw) {
            db()->prepare('UPDATE users SET email=?, first_name=?, last_name=?, password_hash=?, status=? WHERE id=?')
                ->execute([$email, $first, $last, password_hash($pw, PASSWORD_DEFAULT), $status, $id]);
        } else {
            db()->prepare('UPDATE users SET email=?, first_name=?, last_name=?, status=? WHERE id=?')
                ->execute([$email, $first, $last, $status, $id]);
        }
        flash('success', 'Admin updated.');
    } else {
        $existing = db()->prepare('SELECT id FROM users WHERE email=?');
        $existing->execute([$email]);
        if ($existing->fetch()) { flash('error', 'Email already in use.'); redirect('/admin/admin-form.php'); }
        db()->prepare('INSERT INTO users (role,email,password_hash,first_name,last_name,status) VALUES (?,?,?,?,?,?)')
            ->execute(['admin', $email, password_hash($pw ?: 'password123', PASSWORD_DEFAULT), $first, $last, $status]);
        flash('success', 'Admin created.');
    }
    redirect('/admin/admins.php');
}

$pageTitle = $id ? 'Edit Admin' : 'Add Admin';
$activeNav = 'admins';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit Admin' : 'New Admin' ?></h2><p class="text-xs text-slate-500">System administrator account</p></div>
        </div>
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                    <input name="first_name" required value="<?= e($admin['first_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input name="last_name" required value="<?= e($admin['last_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input type="email" name="email" required value="<?= e($admin['email'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5"><?= $id ? 'New Password (leave empty to keep current)' : 'Password' ?></label>
                <input type="password" name="password" <?= $id ? '' : 'required' ?> class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    <option value="active" <?= ($admin['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($admin['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/admin/admins.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
