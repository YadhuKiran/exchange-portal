<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$uid = (int) current_user()['id'];
$user = db()->prepare('SELECT * FROM users WHERE id=?');
$user->execute([$uid]);
$user = $user->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pw = trim($_POST['new_password'] ?? '');
    if ($pw) {
        if ($pw !== ($_POST['confirm_password'] ?? '')) {
            flash('error', 'New passwords do not match.');
            redirect('/student/profile.php');
        }
        $result = change_password($uid, $_POST['current_password'] ?? '', $pw);
        flash($result['success'] ? 'success' : 'error', $result['message']);
    } else {
        $uploadMsg = '';
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            try {
                $upload = handle_upload('profile_picture');
                if ($upload) {
                    db()->prepare('UPDATE users SET profile_picture=? WHERE id=?')->execute([$upload['path'], $uid]);
                }
            } catch (Exception $e) {
                $uploadMsg = ' But picture upload failed: ' . $e->getMessage();
            }
        }
        db()->prepare('UPDATE users SET first_name=?, last_name=? WHERE id=?')
            ->execute([trim($_POST['first_name']), trim($_POST['last_name']), $uid]);
        db()->prepare('UPDATE students SET phone=? WHERE user_id=?')
            ->execute([trim($_POST['phone'] ?? ''), $uid]);
        flash('success', 'Profile updated.' . $uploadMsg);
    }
    redirect('/student/profile.php');
}

$pageTitle = 'My Profile';
$activeNav = 'profile';
require __DIR__ . '/../includes/layout.php';
?>
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">My Profile</h2>
            <p class="text-xs text-slate-500">Update your account details</p>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="flex items-center gap-6 mb-6">
                <div class="shrink-0 ring-4 ring-slate-50 rounded-full shadow-sm">
                    <?= avatar_icon($user['first_name'].' '.$user['last_name'], 'lg', $user['profile_picture'] ?? null) ?>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 mb-1">Profile Photo</h3>
                    <p class="text-xs text-slate-500 mb-3">Upload a new avatar (JPG, PNG).</p>
                    <input type="file" name="profile_picture" accept="image/*" class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer">
                </div>
            </div>
            <hr class="border-slate-100 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                    <input name="first_name" required value="<?= e($user['first_name']) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input name="last_name" required value="<?= e($user['last_name']) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input readonly value="<?= e($user['email']) ?>" class="w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-sm text-slate-400 cursor-not-allowed"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                <input name="phone" value="<?= e($student['phone'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <hr class="border-slate-100">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Change Password</p>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Current Password</label>
                    <input type="password" name="current_password" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">New Password</label>
                    <input type="password" name="new_password" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div class="col-span-2"><label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/student/index.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
