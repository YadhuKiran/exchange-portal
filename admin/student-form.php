<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$student = null;
$user = null;
if ($id) {
    $stmt = db()->prepare('SELECT s.*, u.* FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) { flash('error', 'Student not found.'); redirect('/admin/students.php'); }
    $student = $row;
    $user = $row;
}

$universities = db()->query("SELECT id, name, code FROM universities WHERE status='active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $uniId = (int) ($_POST['university_id'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';

    $pdo = db();
    try {
        if ($id) {
            $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, status=? WHERE id=?')
                ->execute([$first, $last, $email, $status, $student['user_id']]);
            if ($password) {
                $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $student['user_id']]);
            }
            $pdo->prepare('UPDATE students SET university_id=?, phone=? WHERE id=?')
                ->execute([$uniId, $phone ?: null, $id]);
            $targetUid = $student['user_id'];
        } else {
            $hash = password_hash($password ?: 'password123', PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO users (role,email,password_hash,first_name,last_name,status) VALUES (?,?,?,?,?,?)')
                ->execute(['student', $email, $hash, $first, $last, $status]);
            $uid = (int) $pdo->lastInsertId();
            $num = trim($_POST['student_number'] ?? '') ?: 'STU-' . date('Y') . '-' . str_pad((string)$uid, 4, '0', STR_PAD_LEFT);
            $pdo->prepare('INSERT INTO students (user_id,student_number,university_id,phone) VALUES (?,?,?,?)')
                ->execute([$uid, $num, $uniId, $phone ?: null]);
            $pdo->commit();
            $targetUid = $uid;
            notify($uid, 'Account Created', 'Your student account has been set up by an administrator.');
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            flash('error', 'The email address provided is already in use by another account.');
            redirect($id ? "/admin/student-form.php?id=$id" : '/admin/student-form.php');
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

    flash('success', ($id ? 'Student updated.' : 'Student created.') . $uploadMsg);
    redirect('/admin/students.php');
}

$pageTitle = $id ? 'Edit Student' : 'Add Student';
$activeNav = 'students';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center"><?= avatar_icon(($user['first_name'] ?? 'N').' '.($user['last_name'] ?? 'A'), 'sm', $user['profile_picture'] ?? null) ?></div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit Student' : 'New Student' ?></h2><p class="text-xs text-slate-500"><?= $id ? 'Update student information' : 'Create a new student account' ?></p></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">First name</label>
                    <input type="text" name="first_name" required value="<?= e($user['first_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Last name</label>
                    <input type="text" name="last_name" required value="<?= e($user['last_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input type="email" name="email" required value="<?= e($user['email'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <?php if (!$id): ?>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Student number <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="text" name="student_number" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Auto-generated if empty"></div>
            <?php endif; ?>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">University</label>
                <select name="university_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    <?php foreach ($universities as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (($student['university_id'] ?? '') == $u['id']) ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" value="<?= e($student['phone'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                        <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Profile Picture <span class="text-slate-400 font-normal">(optional)</span></label>
                <input type="file" name="profile_picture" accept="image/*" class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Password <?= $id ? '<span class="text-slate-400 font-normal">(leave blank to keep current)</span>' : '' ?></label>
                <input type="password" name="password" <?= $id ? '' : 'required minlength="8"' ?> class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/admin/students.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
