<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $existing = db()->prepare('SELECT id FROM enrollments WHERE student_id=? AND course_id=?');
    $existing->execute([$stuId, $courseId]);
    if ($existing->fetch()) { flash('error', 'Already enrolled in this course.'); redirect('/student/enrollments.php'); }
    db()->prepare('INSERT INTO enrollments (student_id,course_id,status) VALUES (?,?,?)')->execute([$stuId, $courseId, 'approved']);
    db()->prepare('UPDATE courses SET capacity = GREATEST(capacity-1, 0) WHERE id=?')->execute([$courseId]);
    flash('success', 'Enrolled successfully.');
    redirect('/student/enrollments.php');
}

$courses = db()->query(
    "SELECT c.*, u.name AS university_name, u.city, u.country
     FROM courses c JOIN universities u ON u.id = c.university_id
     WHERE c.status='open' AND c.capacity > 0 ORDER BY u.name, c.title"
)->fetchAll();

$enrolledIds = db()->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$enrolledIds->execute([$stuId]);
$enrolledIds = array_column($enrolledIds->fetchAll(), 'course_id');

$pageTitle = 'Enroll in a Course';
$activeNav = 'enrollments';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900">Enroll in a Course</h2><p class="text-xs text-slate-500">Select a course to register</p></div>
        </div>
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Course</label>
                <select name="course_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    <option value="">Select a course…</option>
                    <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= in_array($c['id'], $enrolledIds) ? 'disabled' : '' ?>>
                        <?= e($c['code']) ?> – <?= e($c['title']) ?> (<?= e($c['university_name']) ?>, <?= e($c['semester']) ?>) <?= in_array($c['id'], $enrolledIds) ? '[Already enrolled]' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Enroll</button>
                <a href="<?= url('/student/enrollments.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
