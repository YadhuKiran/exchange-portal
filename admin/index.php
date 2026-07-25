<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['admin']);

$loadCharts = true;
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
$stats = enterprise_dashboard_stats();

$chartTrend = chart_application_trend();
$chartStudentsUni = chart_students_by_university();
$chartApproval = chart_approval_rate();
$chartDocs = chart_document_verification();
$chartGrowth = chart_monthly_growth();

$recentApps = db()->query(
    "SELECT a.*, u.first_name, u.last_name, hu.name AS host_uni
     FROM applications a
     JOIN students s ON s.id = a.student_id
     JOIN users u ON u.id = s.user_id
     JOIN universities hu ON hu.id = a.host_university_id
     ORDER BY a.updated_at DESC LIMIT 5"
)->fetchAll();

require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-700 rounded-2xl p-6 text-white shadow-lg shadow-indigo-500/20 ring-1 ring-white/10">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-indigo-100/80 text-sm font-medium">Global Mobility Operations</p>
            <h2 class="text-2xl font-bold mt-1 tracking-tight">Enterprise Overview</h2>
            <p class="text-indigo-200/70 text-sm mt-1"><?= $stats['universities'] ?> partner institutions · <?= $stats['students'] ?> active students</p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-bold"><?= $stats['applications'] ?></p>
            <p class="text-indigo-200/70 text-xs">Total Applications</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <?= stat_card('Students', $stats['students'], 'brand') ?>
    <?= stat_card('Applications', $stats['applications'], 'emerald', $stats['approved_apps'] . ' approved') ?>
    <?= stat_card('In Review', $stats['submitted_apps'], 'amber') ?>
    <?= stat_card('Enrollments', $stats['enrollments'] ?? 0, 'violet', ($stats['pending_enrollments'] ?? 0) . ' pending') ?>
    <?= stat_card('Documents', $stats['documents'], 'rose', $stats['pending_docs'] . ' pending') ?>
    <?= stat_card('Universities', $stats['universities'], 'cyan') ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Application Trend
        </h2>
        <div class="chart-box"><canvas id="chartAppTrend"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Approval Rate
        </h2>
        <div class="chart-box"><canvas id="chartApprovalRate"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-cyan-500"></span> Students by University
        </h2>
        <div class="chart-box"><canvas id="chartStudentsByUni"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Monthly Growth
        </h2>
        <div class="chart-box"><canvas id="chartMonthlyGrowth"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Document Verification
        </h2>
        <div class="chart-box" style="height:220px"><canvas id="chartDocVerification"></canvas></div>
    </div>
    <?php $queueScope = 'admin'; require __DIR__ . '/../includes/widgets/verification_queue.php'; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden card-hover">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-sm font-semibold text-slate-900">Recent Applications</h2>
                <a href="<?= url('/admin/applications.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                    <th class="px-6 py-3 text-left">Student</th><th class="px-6 py-3 text-left">Host</th><th class="px-6 py-3 text-left">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($recentApps as $app): ?>
                <tr class="table-row-hover">
                    <td class="px-6 py-3.5"><div class="flex items-center gap-2"><?= avatar_icon($app['first_name'].' '.$app['last_name'], 'sm') ?><span class="font-medium text-slate-900"><?= e($app['first_name'].' '.$app['last_name']) ?></span></div></td>
                    <td class="px-6 py-3.5 text-slate-600"><?= e($app['host_uni']) ?></td>
                    <td class="px-6 py-3.5"><?= status_badge_enhanced($app['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/../includes/widgets/status_summary.php'; ?>
    </div>
    <?php $feedLimit = 10; $feedUniversityId = null; require __DIR__ . '/../includes/widgets/activity_feed.php'; ?>
</div>

<?php require __DIR__ . '/../includes/widgets/admin_charts_script.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
