<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$uni = null;
$coord = null;
$coordUser = null;

if ($id) {
    $stmt = db()->prepare('SELECT * FROM universities WHERE id = ?');
    $stmt->execute([$id]);
    $uni = $stmt->fetch();

    $stmt = db()->prepare(
        'SELECT c.*, u.first_name, u.last_name, u.email, u.status AS user_status
         FROM coordinators c JOIN users u ON u.id = c.user_id
         WHERE c.university_id = ?'
    );
    $stmt->execute([$id]);
    $coord = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $uniName = trim($_POST['name']);
        $uniCode = trim($_POST['code']);
        $country = trim($_POST['country']);
        $city = trim($_POST['city']);
        $uniStatus = $_POST['status'] ?? 'active';

        $logoPath = $uni['logo_path'] ?? null;
        if (!empty($_FILES['logo_file']['name'])) {
            try {
                $upload = handle_upload('logo_file', UPLOAD_DIR . 'universities/');
                if ($upload) {
                    $logoPath = 'universities/' . $upload['stored'];
                }
            } catch (Exception $e) {
                flash('error', 'Logo upload failed: ' . $e->getMessage());
            }
        }

        if ($id) {
            $pdo->prepare('UPDATE universities SET name=?,code=?,country=?,city=?,status=?,logo_path=? WHERE id=?')
                ->execute([$uniName, $uniCode, $country, $city, $uniStatus, $logoPath, $id]);
            log_activity('university.updated', "University updated: $uniName", 'university', $id);
            $uniId = $id;
        } else {
            $pdo->prepare('INSERT INTO universities (name,code,country,city,status,logo_path) VALUES (?,?,?,?,?,?)')
                ->execute([$uniName, $uniCode, $country, $city, $uniStatus, $logoPath]);
            $uniId = (int) $pdo->lastInsertId();
            log_activity('university.created', "University created: $uniName", 'university', $uniId);
        }

        $coordEmail = trim($_POST['coord_email'] ?? '');
        $coordFirst = trim($_POST['coord_first_name'] ?? '');
        $coordLast = trim($_POST['coord_last_name'] ?? '');
        $coordDept = trim($_POST['coord_department'] ?? 'International Programs');

        if (!$id && !$coordEmail) {
            throw new RuntimeException('Coordinator email is required for a new university.');
        }

        if ($coordEmail) {
            $existing = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $existing->execute([$coordEmail]);
            $existingUser = $existing->fetch();

            if ($existingUser) {
                $coordUserId = (int) $existingUser['id'];
                $pdo->prepare('UPDATE users SET first_name=?, last_name=? WHERE id=?')
                    ->execute([$coordFirst, $coordLast, $coordUserId]);
            } else {
                $coordPw = trim($_POST['coord_password'] ?? 'password123');
                $pdo->prepare('INSERT INTO users (role,email,password_hash,first_name,last_name,status) VALUES (?,?,?,?,?,?)')
                    ->execute(['coordinator', $coordEmail, password_hash($coordPw, PASSWORD_DEFAULT), $coordFirst, $coordLast, 'active']);
                $coordUserId = (int) $pdo->lastInsertId();
            }

            $existingCoord = $pdo->prepare('SELECT id FROM coordinators WHERE university_id = ?');
            $existingCoord->execute([$uniId]);
            if ($existingCoord->fetch()) {
                $pdo->prepare('UPDATE coordinators SET user_id=?, department=? WHERE university_id=?')
                    ->execute([$coordUserId, $coordDept, $uniId]);
            } else {
                $pdo->prepare('INSERT INTO coordinators (user_id, university_id, department) VALUES (?,?,?)')
                    ->execute([$coordUserId, $uniId, $coordDept]);
            }
        }

        $pdo->commit();
        flash('success', 'University saved with assigned coordinator.');
        redirect('/admin/universities.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Error: ' . $e->getMessage());
        redirect('/admin/university-form.php' . ($id ? "?id=$id" : ''));
    }
}

$pageTitle = $id ? 'Edit University' : 'Add University';
$activeNav = 'universities';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-cyan-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit University' : 'New University' ?></h2><p class="text-xs text-slate-500"><?= $id ? 'Update university details' : 'Add a partner institution' ?></p></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">University name</label>
                <input name="name" required value="<?= e($uni['name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Stanford University"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Code</label>
                <input name="code" required value="<?= e($uni['code'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-mono uppercase focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. STN"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Country</label>
                    <input name="country" required value="<?= e($uni['country'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. United States"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">City</label>
                    <input name="city" required value="<?= e($uni['city'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Stanford"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                        <option value="active" <?= ($uni['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($uni['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">University Logo</label>
                    <input type="file" name="logo_file" accept="image/png, image/jpeg" class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                </div>
            </div>

            <hr class="border-slate-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div><h3 class="text-sm font-semibold text-slate-900">Assigned Coordinator</h3><p class="text-xs text-slate-500">Each university must have a designated coordinator</p></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">First Name</label>
                    <input name="coord_first_name" <?= $id ? '' : 'required' ?> value="<?= e($coord['first_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input name="coord_last_name" <?= $id ? '' : 'required' ?> value="<?= e($coord['last_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="coord_email" <?= $id ? '' : 'required' ?> value="<?= e($coord['email'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                    <input name="coord_department" value="<?= e($coord['department'] ?? 'International Programs') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <?php if (!$id): ?>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                <input type="password" name="coord_password" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Defaults to password123 if left empty"></div>
            <?php endif; ?>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/admin/universities.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
