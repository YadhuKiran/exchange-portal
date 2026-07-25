<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];

$id = (int) ($_GET['id'] ?? 0);
$visa = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM visas WHERE id=? AND student_id=?');
    $stmt->execute([$id, $stuId]);
    $visa = $stmt->fetch();
    if (!$visa) { flash('error', 'Visa not found.'); redirect('/student/visas.php'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [trim($_POST['visa_type']), strtoupper(trim($_POST['visa_number'])), trim($_POST['issuing_country']), $_POST['issue_date'], $_POST['expiry_date'], $_POST['status'] ?? 'pending'];
    if ($id) {
        db()->prepare('UPDATE visas SET visa_type=?,visa_number=?,issuing_country=?,issue_date=?,expiry_date=?,status=? WHERE id=?')
            ->execute([...$data, $id]);
        flash('success', 'Visa updated.');
    } else {
        db()->prepare('INSERT INTO visas (student_id,visa_type,visa_number,issuing_country,issue_date,expiry_date,status) VALUES (?,?,?,?,?,?,?)')
            ->execute([$stuId, ...$data]);
        flash('success', 'Visa added.');
    }
    redirect('/student/visas.php');
}

$pageTitle = $id ? 'Edit Visa' : 'Add Visa';
$activeNav = 'visas';
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
            <div><h2 class="text-sm font-semibold text-slate-900"><?= $id ? 'Edit Visa' : 'Add Visa' ?></h2><p class="text-xs text-slate-500">Student visa details</p></div>
        </div>
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Visa Type</label>
                    <input name="visa_type" required value="<?= e($visa['visa_type'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Student Visa"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Visa Number</label>
                    <input name="visa_number" value="<?= e($visa['visa_number'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Country</label>
                <input name="issuing_country" required value="<?= e($visa['issuing_country'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Issue Date</label>
                    <input type="date" name="issue_date" value="<?= e($visa['issue_date'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?= e($visa['expiry_date'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all"></div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save</button>
                <a href="<?= url('/student/visas.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
