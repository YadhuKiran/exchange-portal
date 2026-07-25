<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];
$id = (int) ($_GET['id'] ?? 0);

$enroll = db()->prepare('SELECT e.*, c.capacity, c.id AS course_id FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.id=? AND e.student_id=?');
$enroll->execute([$id, $stuId]);
$enroll = $enroll->fetch();
if (!$enroll) { flash('error', 'Enrollment not found.'); redirect('/student/enrollments.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    db()->prepare("UPDATE enrollments SET status='dropped', dropped_at=NOW() WHERE id=?")->execute([$id]);
    db()->prepare('UPDATE courses SET capacity = capacity+1 WHERE id=?')->execute([$enroll['course_id']]);
    flash('success', 'Enrollment dropped.');
    redirect('/student/enrollments.php');
}

$pageTitle = 'Drop Enrollment';
$activeNav = 'enrollments';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover text-center">
        <div class="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <h2 class="text-lg font-bold text-slate-900 mb-2">Drop Enrollment?</h2>
        <p class="text-sm text-slate-500 mb-6">Are you sure you want to drop this enrollment? This action can be undone by re-enrolling if seats are available.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="flex gap-3 justify-center">
                <button type="submit" class="btn-hover bg-red-500 hover:bg-red-400 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-red-500/20 text-sm">Drop</button>
                <a href="<?= url('/student/enrollments.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Keep</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
