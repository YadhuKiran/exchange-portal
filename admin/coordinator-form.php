<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$coord = null;
if ($id) {
    $stmt = db()->prepare('SELECT c.*, u.* FROM coordinators c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
    $stmt->execute([$id]);
    $coord = $stmt->fetch() ?: null;
    if (!$coord) { flash('error', 'Not found.'); redirect('/admin/coordinators.php'); }
}

$universities = db()->query("SELECT id, name, code, city, country FROM universities WHERE status='active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        trim($_POST['first_name'] ?? ''),
        trim($_POST['last_name'] ?? ''),
        trim($_POST['email'] ?? ''),
        $_POST['status'] ?? 'active',
        (int) ($_POST['university_id'] ?? 0),
        trim($_POST['department'] ?? '') ?: null,
    ];
    $pdo = db();
    try {
        if ($id) {
            $pdo->prepare('UPDATE users SET first_name=?,last_name=?,email=?,status=? WHERE id=?')
                ->execute([$data[0],$data[1],$data[2],$data[3],$coord['user_id']]);
            if (!empty($_POST['password'])) {
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $coord['user_id']]);
            }
            $pdo->prepare('UPDATE coordinators SET university_id=?, department=? WHERE id=?')
                ->execute([$data[4], $data[5], $id]);
            $targetUid = $coord['user_id'];
        } else {
            $hash = password_hash($_POST['password'] ?: 'password123', PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO users (role,email,password_hash,first_name,last_name,status) VALUES (?,?,?,?,?,?)')
                ->execute(['coordinator', $data[2], $hash, $data[0], $data[1], $data[3]]);
            $uid = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO coordinators (user_id,university_id,department) VALUES (?,?,?)')
                ->execute([$uid, $data[4], $data[5]]);
            $pdo->commit();
            $targetUid = $uid;
            notify($uid, 'Coordinator Account', 'Your coordinator account is ready.');
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            flash('error', 'The email address provided is already in use by another account.');
            redirect($id ? "/admin/coordinator-form.php?id=$id" : '/admin/coordinator-form.php');
        } else {
            throw $e;
        }
    }

    $uploadMsg = '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        try {
            $upload = handle_upload('profile_picture');
            if ($upload) {
                db()->prepare('UPDATE users SET profile_picture=? WHERE id=?')->execute([$upload['path'], $targetUid]);
            }
        } catch (Exception $e) {
            $uploadMsg = ' (Picture upload failed: ' . $e->getMessage() . ')';
        }
    }

    flash('success', ($id ? 'Coordinator updated.' : 'Coordinator created.') . $uploadMsg);
    redirect('/admin/coordinators.php');
}

$pageTitle = $id ? 'Edit Coordinator' : 'Add Coordinator';
$activeNav = 'coordinators';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center"><?= avatar_icon(($coord['first_name'] ?? 'N').' '.($coord['last_name'] ?? 'A'), 'sm', $coord['profile_picture'] ?? null) ?></div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit Coordinator' : 'New Coordinator' ?></h2><p class="text-xs text-slate-500"><?= $id ? 'Update coordinator details' : 'Create a new coordinator account' ?></p></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">First name</label>
                    <input name="first_name" required value="<?= e($coord['first_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Last name</label>
                    <input name="last_name" required value="<?= e($coord['last_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input type="email" name="email" required value="<?= e($coord['email'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">University</label>
                    <select name="university_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                        <?php foreach ($universities as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (($coord['university_id'] ?? '') == $u['id']) ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['city']) ?>, <?= e($u['country']) ?>)</option>
                        <?php endforeach; ?>
                    </select></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                    <input name="department" value="<?= e($coord['department'] ?? '') ?>" placeholder="e.g. International Office" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                        <option value="active" <?= ($coord['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($coord['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Profile Picture <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="file" name="profile_picture" accept="image/*" class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Password <?= $id ? '<span class="text-slate-400 font-normal">(optional)</span>' : '' ?></label>
                    <input type="password" name="password" <?= $id ? '' : 'required minlength="8"' ?> class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div class="flex items-center justify-between pt-2">
                <div class="flex gap-3">
                    <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                    <a href="<?= url('/admin/coordinators.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
                </div>
                <?php if ($id): ?>
                <a href="<?= url('/admin/coordinator-delete.php?id=' . $id) ?>" onclick="return confirm('Are you sure you want to delete this coordinator?');" class="text-red-500 hover:text-red-700 text-sm font-medium px-4 py-2 hover:bg-red-50 rounded-xl transition-all">Delete</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
