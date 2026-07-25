<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

$pass = db()->prepare('SELECT * FROM passports WHERE student_id = ?');
$pass->execute([$stuId]);
$pass = $pass->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [strtoupper(trim($_POST['passport_number'])), trim($_POST['issuing_country']), $_POST['issue_date'], $_POST['expiry_date'], $_POST['status'] ?? 'pending'];
    if ($pass) {
        db()->prepare('UPDATE passports SET passport_number=?,issuing_country=?,issue_date=?,expiry_date=?,status=? WHERE student_id=?')
            ->execute([...$data, $stuId]);
        flash('success', 'Passport updated.');
    } else {
        db()->prepare('INSERT INTO passports (student_id,passport_number,issuing_country,issue_date,expiry_date,status) VALUES (?,?,?,?,?,?)')
            ->execute([$stuId, ...$data]);
        flash('success', 'Passport saved.');
    }
    redirect('/student/passport.php');
}

$pageTitle = 'My Passport';
$activeNav = 'passports';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900">Passport Information</h2><p class="text-xs text-slate-500"><?= $pass ? 'Update your passport details' : 'Add your passport details' ?></p></div>
            <?php if ($pass): ?><div class="ml-auto"><?= status_badge_enhanced($pass['status'], 'sm') ?></div><?php endif; ?>
        </div>
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Passport Number</label>
                    <input name="passport_number" required value="<?= e($pass['passport_number'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-mono uppercase focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Issuing Country</label>
                    <input name="issuing_country" required value="<?= e($pass['issuing_country'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Issue Date</label>
                    <input type="date" name="issue_date" value="<?= e($pass['issue_date'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?= e($pass['expiry_date'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/student/index.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
