<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['student']);   

$student = student_profile((int) current_user()['id']);
   
$homeCoord = db()->prepare(
    "SELECT c.*, u.first_name, u.last_name, u.email
     FROM coordinators c
     JOIN users u ON u.id = c.user_id
     WHERE c.university_id = ? AND u.status = 'active'
     LIMIT 1"
);
$homeCoord->execute([$student['university_id']]);
$homeCoord = $homeCoord->fetch();

$pageTitle = 'My Coordinator';
$activeNav = 'coordinator';
require __DIR__ . '/../includes/layout.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 text-center card-hover">
        <?php if ($homeCoord): ?>
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-3xl font-bold shadow-xl mx-auto mb-6">
                <?= strtoupper(substr($homeCoord['first_name'], 0, 1) . substr($homeCoord['last_name'], 0, 1)) ?>
            </div>
            <h2 class="text-2xl font-bold text-slate-900"><?= e($homeCoord['first_name'].' '.$homeCoord['last_name']) ?></h2>
            <p class="text-slate-500 font-medium mt-1 mb-6"><?= e($homeCoord['department'] ?? 'International Office') ?> Coordinator</p>
            
            <div class="bg-slate-50 rounded-xl p-6 border border-slate-100 text-left max-w-sm mx-auto space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-indigo-500 shadow-sm shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-500 uppercase">Email</p>
                        <p class="text-sm font-medium text-slate-900 truncate"><a href="mailto:<?= e($homeCoord['email']) ?>" class="hover:text-indigo-600"><?= e($homeCoord['email']) ?></a></p>
                    </div>
                </div>
            </div>
            
            <div class="mt-8">
                <a href="mailto:<?= e($homeCoord['email']) ?>" class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold px-6 py-3 rounded-xl shadow-md shadow-indigo-500/20 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Send Email
                </a>
            </div>
        <?php else: ?>
            <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">No Coordinator Assigned</h2>
            <p class="text-slate-500 font-medium mt-1 mb-6">Your university currently does not have an active coordinator assigned.</p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
