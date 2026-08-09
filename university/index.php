<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_login();



$user = current_user();
// Only coordinators can access this dashboard
if ($user['role'] !== 'coordinator') {
    flash('error', 'Access denied. University dashboard is for coordinators only.');
    redirect('/login.php');
}

$cp = coordinator_profile((int) $user['id']);
if (!$cp) {
    flash('error', 'No university profile found.');
    redirect('/login.php');
}
$uniId = (int) $cp['university_id'];

// Stats
$stmt = db()->prepare("SELECT COUNT(*) FROM students WHERE university_id = ?");
$stmt->execute([$uniId]);
$studentCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare("SELECT COUNT(*) FROM courses WHERE university_id = ?");
$stmt->execute([$uniId]);
$courseCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT COUNT(*) FROM applications
     WHERE home_university_id = ? OR host_university_id = ?"
);
$stmt->execute([$uniId, $uniId]);
$appCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT COUNT(*) FROM enrollments e
     JOIN courses c ON c.id = e.course_id
     WHERE c.university_id = ? AND e.status = 'approved'"
);
$stmt->execute([$uniId]);
$enrolledCount = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT c.*, u.first_name, u.last_name, u.email
     FROM coordinators c
     JOIN users u ON u.id = c.user_id
     WHERE c.university_id = ?"
);
$stmt->execute([$uniId]);
$coordinators = $stmt->fetchAll();

$stmt = db()->prepare(
    "SELECT al.*, u.first_name, u.last_name
     FROM activity_logs al
     LEFT JOIN users u ON u.id = al.user_id
     WHERE al.metadata LIKE ?
     ORDER BY al.created_at DESC
     LIMIT 20"
);
$stmt->execute(['%"university_id":"' . $uniId . '"%']);
$activities = $stmt->fetchAll();

$pageTitle = 'University Dashboard — ' . e($cp['university_name']);
$activeNav = 'dashboard';
require __DIR__ . '/../includes/layout.php';
?>

<div class="page-transition">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-violet-600 rounded-2xl p-8 mb-8 text-white shadow-xl shadow-indigo-500/20">
        <div class="flex items-start justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-white/90 text-xs font-medium backdrop-blur-sm mb-3">University Dashboard</span>
                <h1 class="text-2xl font-bold"><?= e($cp['university_name']) ?></h1>
                <p class="text-indigo-100/80 text-sm mt-1">Institutional overview and performance metrics</p>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <?php
        $cards = [
            ['value' => $studentCount, 'label' => 'Students', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'color' => 'indigo', 'href' => '/coordinator/students.php'],
            ['value' => $courseCount, 'label' => 'Courses Offered', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'color' => 'violet', 'href' => '/coordinator/courses.php'],
            ['value' => $appCount, 'label' => 'Applications', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald', 'href' => '/coordinator/applications.php'],
            ['value' => $enrolledCount, 'label' => 'Enrolled Students', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'amber', 'href' => '/coordinator/enrollments.php'],
        ];
        foreach ($cards as $c):
        ?>
        <a href="<?= url($c['href']) ?>" class="stat-card bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <div class="w-11 h-11 rounded-xl bg-<?= $c['color'] ?>-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-<?= $c['color'] ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $c['icon'] ?>"/></svg>
                </div>
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
            <div class="text-3xl font-extrabold text-slate-900"><?= $c['value'] ?></div>
            <div class="text-sm text-slate-500 mt-1"><?= $c['label'] ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Coordinators -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Coordinator Team
            </h3>
            <?php if ($coordinators): ?>
            <div class="space-y-3">
                <?php foreach ($coordinators as $co): ?>
                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <?= avatar_icon($co['first_name'] . ' ' . $co['last_name'], 'sm') ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-900"><?= e($co['first_name'] . ' ' . $co['last_name']) ?></p>
                        <p class="text-xs text-slate-500 truncate"><?= e($co['email']) ?></p>
                    </div>
                    <span class="text-xs text-slate-400"><?= e($co['department'] ?? 'N/A') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-slate-500">No coordinators assigned.</p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Actions
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="<?= url('/coordinator/students.php') ?>" class="flex items-center gap-3 p-4 rounded-xl bg-indigo-50 border border-indigo-100 hover:bg-indigo-100 transition-all">
                    <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center"><svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg></div>
                    <span class="text-xs font-semibold text-indigo-700">View Students</span>
                </a>
                <a href="<?= url('/coordinator/courses.php') ?>" class="flex items-center gap-3 p-4 rounded-xl bg-violet-50 border border-violet-100 hover:bg-violet-100 transition-all">
                    <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center"><svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                    <span class="text-xs font-semibold text-violet-700">Manage Courses</span>
                </a>
                <a href="<?= url('/coordinator/applications.php') ?>" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100 hover:bg-emerald-100 transition-all">
                    <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center"><svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <span class="text-xs font-semibold text-emerald-700">Applications</span>
                </a>
                <a href="<?= url('/coordinator/reports/index.php') ?>" class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-100 hover:bg-amber-100 transition-all">
                    <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center"><svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                    <span class="text-xs font-semibold text-amber-700">Reports</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
