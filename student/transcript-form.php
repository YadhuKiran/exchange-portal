<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

$id = (int) ($_GET['id'] ?? 0);
$t = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM transcripts WHERE id=? AND student_id=?');
    $stmt->execute([$id, $stuId]);
    $t = $stmt->fetch();
    if (!$t) { flash('error', 'Transcript not found.'); redirect('/student/transcripts.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [trim($_POST['institution_name']), trim($_POST['degree_program']), (float) ($_POST['gpa'] ?? 0), $_POST['issue_date'] ?: null, $_POST['status'] ?? 'pending'];
    if ($id) {
        db()->prepare('UPDATE transcripts SET institution_name=?,degree_program=?,gpa=?,issue_date=?,status=? WHERE id=?')
            ->execute([...$data, $id]);
        flash('success', 'Transcript updated.');
    } else {
        db()->prepare('INSERT INTO transcripts (student_id,institution_name,degree_program,gpa,issue_date,status) VALUES (?,?,?,?,?,?)')
            ->execute([$stuId, ...$data]);
        flash('success', 'Transcript added.');
    }
    redirect('/student/transcripts.php');
}

$pageTitle = $id ? 'Edit Transcript' : 'Add Transcript';
$activeNav = 'transcripts';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit Transcript' : 'Add Transcript' ?></h2><p class="text-xs text-slate-500">Academic record details</p></div>
        </div>
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Institution Name</label>
                <input name="institution_name" required value="<?= e($t['institution_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Degree</label>
                    <input name="degree_program" required value="<?= e($t['degree_program'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. B.Sc."></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Issue Date</label>
                    <input type="date" name="issue_date" value="<?= e($t['issue_date'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">GPA</label>
                <input type="number" step="0.01" name="gpa" value="<?= e($t['gpa'] ?? '3.0') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/student/transcripts.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
