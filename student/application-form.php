<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];
$homeUniId = (int) $student['university_id'];

$id = (int) ($_GET['id'] ?? 0);
$app = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM applications WHERE id = ? AND student_id = ?');
    $stmt->execute([$id, $stuId]);
    $app = $stmt->fetch();
    if (!$app) { flash('error', 'Application not found.'); redirect('/student/applications.php'); }
}

$hostUniversities = db()->query("SELECT id, name, city, country FROM universities WHERE id != $homeUniId ORDER BY name")->fetchAll();
$allCourses = db()->query("SELECT id, title, code, university_id FROM courses WHERE status='open' ORDER BY title")->fetchAll();

$prefillUni = (int) ($_GET['uni_id'] ?? 0);
$prefillCourse = (int) ($_GET['course_id'] ?? 0);
$prefillSem = $_GET['sem'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $hostId = (int) ($_POST['host_university_id'] ?? 0);
    $statement = trim($_POST['personal_statement'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $prefDept = trim($_POST['preferred_department'] ?? '');
    $prefCourse = (int) ($_POST['preferred_course_id'] ?? 0);
    if (!$prefCourse) $prefCourse = null;

    if ($id) {
        db()->prepare('UPDATE applications SET host_university_id=?, semester=?, preferred_department=?, preferred_course_id=?, personal_statement=?, updated_at=NOW() WHERE id=? AND student_id=?')
            ->execute([$hostId, $semester, $prefDept, $prefCourse, $statement, $id, $stuId]);
        flash('success', 'Application updated.');
    } else {
        db()->prepare('INSERT INTO applications (student_id, home_university_id, host_university_id, semester, preferred_department, preferred_course_id, personal_statement, status) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$stuId, $homeUniId, $hostId, $semester, $prefDept, $prefCourse, $statement, 'draft']);
        $appId = (int) db()->lastInsertId();
        db()->prepare("INSERT INTO application_status_history (application_id, from_status, to_status, comment, changed_by_user_id) VALUES (?,NULL,?,?,?)")->execute([$appId, 'draft', 'Application created', ($_SESSION['user_id'] ?? 0)]);
        flash('success', 'Application created.');
        redirect('/student/applications.php');
    }
    redirect('/student/applications.php');
}

$pageTitle = $id ? 'Edit Application' : 'New Application';
$activeNav = 'applications';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit Application' : 'New Application' ?></h2><p class="text-xs text-slate-500">Home: <?= e($student['university_name']) ?></p></div>
        </div>
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Host University</label>
                    <select name="host_university_id" id="host_university_id" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all bg-white text-slate-900">
                        <option value="">Select university…</option>
                        <?php foreach ($hostUniversities as $hu): ?>
                        <option value="<?= $hu['id'] ?>" <?= (($app['host_university_id'] ?? $prefillUni) == $hu['id']) ? 'selected' : '' ?>><?= e($hu['name']) ?> (<?= e($hu['city']) ?>, <?= e($hu['country']) ?>)</option>
                        <?php endforeach; ?>
                    </select></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Semester</label>
                    <select name="semester" required class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all bg-white text-slate-900">
                        <option value="">Select semester…</option>
                        <option value="Fall 2026" <?= (($app['semester'] ?? $prefillSem) === 'Fall 2026') ? 'selected' : '' ?>>Fall 2026</option>
                        <option value="Spring 2027" <?= (($app['semester'] ?? $prefillSem) === 'Spring 2027') ? 'selected' : '' ?>>Spring 2027</option>
                        <option value="Fall 2027" <?= (($app['semester'] ?? $prefillSem) === 'Fall 2027') ? 'selected' : '' ?>>Fall 2027</option>
                    </select></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Preferred Department</label>
                    <input type="text" name="preferred_department" value="<?= e($app['preferred_department'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all bg-white text-slate-900" placeholder="e.g. Computer Science"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Specific Course (Optional)</label>
                    <select name="preferred_course_id" id="preferred_course_id" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all bg-white text-slate-900">
                        <option value="">No specific course</option>
                        <?php foreach ($allCourses as $c): ?>
                        <option value="<?= $c['id'] ?>" data-uni="<?= $c['university_id'] ?>" <?= (($app['preferred_course_id'] ?? $prefillCourse) == $c['id']) ? 'selected' : '' ?>><?= e($c['code']) ?> - <?= e($c['title']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Personal Statement</label>
                <textarea name="personal_statement" rows="5" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all bg-white text-slate-900" placeholder="Why do you want to study at this university?"><?= e($app['personal_statement'] ?? '') ?></textarea></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/student/applications.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
// Filter courses based on selected host university
document.addEventListener('DOMContentLoaded', function() {
    const uniSelect = document.getElementById('host_university_id');
    const courseSelect = document.getElementById('preferred_course_id');
    const allCourseOptions = Array.from(courseSelect.options).slice(1); // exclude the default option

    function updateCourseDropdown() {
        const uniId = uniSelect.value;
        let hasVisibleOptions = false;
        
        allCourseOptions.forEach(opt => {
            if (!uniId || opt.getAttribute('data-uni') === uniId) {
                opt.style.display = '';
                hasVisibleOptions = true;
            } else {
                opt.style.display = 'none';
                if (opt.selected) opt.selected = false;
            }
        });
        
        if (!hasVisibleOptions && courseSelect.value !== "") {
            courseSelect.value = "";
        }
    }

    uniSelect.addEventListener('change', updateCourseDropdown);
    updateCourseDropdown(); // Run on load to handle pre-filled values
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
