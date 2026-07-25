<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$course = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    if (!$course) { flash('error', 'Course not found.'); redirect('/admin/courses.php'); }
}

$universities = db()->query("SELECT id, name FROM universities WHERE status='active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        (int) ($_POST['university_id'] ?? 0),
        strtoupper(trim($_POST['code'] ?? '')),
        trim($_POST['title'] ?? ''),
        (float) ($_POST['credits'] ?? 3.0),
        trim($_POST['semester'] ?? ''),
        (int) ($_POST['capacity'] ?? 30),
        $_POST['status'] ?? 'open',
    ];
    if ($id) {
        db()->prepare('UPDATE courses SET university_id=?,code=?,title=?,credits=?,semester=?,capacity=?,status=? WHERE id=?')
            ->execute([...$data, $id]);
        flash('success', 'Course updated.');
    } else {
        db()->prepare('INSERT INTO courses (university_id,code,title,credits,semester,capacity,status) VALUES (?,?,?,?,?,?,?)')
            ->execute($data);
        flash('success', 'Course created.');
    }
    redirect('/admin/courses.php');
}

$pageTitle = $id ? 'Edit Course' : 'Add Course';
$activeNav = 'courses';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit Course' : 'New Course' ?></h2><p class="text-xs text-slate-500"><?= $id ? 'Update course details' : 'Add a new exchange course' ?></p></div>
        </div>
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">University</label>
                <select name="university_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    <?php foreach ($universities as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (($course['university_id'] ?? '') == $u['id']) ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Course code</label>
                    <input name="code" required value="<?= e($course['code'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-mono uppercase focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. CS-101"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Credits</label>
                    <input type="number" step="0.5" name="credits" value="<?= e($course['credits'] ?? '3.0') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Course title</label>
                <input name="title" required value="<?= e($course['title'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Introduction to Computer Science"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Semester</label>
                    <input name="semester" required value="<?= e($course['semester'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Fall 2026"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Capacity</label>
                    <input type="number" name="capacity" value="<?= e($course['capacity'] ?? '30') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    <option value="open" <?= ($course['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="closed" <?= ($course['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/admin/courses.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
