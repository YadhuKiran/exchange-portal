<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$review = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM reviews WHERE id = ?');
    $stmt->execute([$id]);
    $review = $stmt->fetch() ?: null;
    if (!$review) {
        flash('error', 'Review not found.');
        redirect('/admin/reviews.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $text = trim($_POST['text'] ?? '');
    $initials = trim($_POST['initials'] ?? '');
    
    if (!$initials && $name) {
        $parts = explode(' ', $name);
        $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? $parts[0] ?? '', 0, 1));
    }

    if ($id) {
        db()->prepare('UPDATE reviews SET name=?, role=?, text=?, initials=? WHERE id=?')
            ->execute([$name, $role, $text, $initials, $id]);
        flash('success', 'Review updated successfully.');
    } else {
        db()->prepare('INSERT INTO reviews (name, role, text, initials) VALUES (?, ?, ?, ?)')
            ->execute([$name, $role, $text, $initials]);
        flash('success', 'Review added successfully.');
    }
    redirect('/admin/reviews.php');
}

$pageTitle = $id ? 'Edit Review' : 'Add Review';
$activeNav = 'reviews';
require __DIR__ . '/../includes/layout.php';
?>

<div class="max-w-2xl">
    <div class="mb-6">
        <a href="<?= url('/admin/reviews.php') ?>" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Reviews
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 sm:p-8">
        <form method="post" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Reviewer Name</label>
                    <input type="text" name="name" required value="<?= e($review['name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role / Program details</label>
                    <input type="text" name="role" required value="<?= e($review['role'] ?? '') ?>" placeholder="e.g. MIT → Oxford" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Initials (Optional)</label>
                <input type="text" name="initials" maxlength="3" value="<?= e($review['initials'] ?? '') ?>" placeholder="AJ" class="w-full sm:w-32 rounded-xl border border-slate-200 px-4 py-2.5 text-sm bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                <p class="text-xs text-slate-500 mt-1.5">Will be auto-generated from name if left blank.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Review Text</label>
                <textarea name="text" required rows="4" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all resize-none"><?= e($review['text'] ?? '') ?></textarea>
            </div>

            <hr class="border-slate-100">

            <div class="flex gap-3">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">Save Review</button>
                <a href="<?= url('/admin/reviews.php') ?>" class="px-6 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-all">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
