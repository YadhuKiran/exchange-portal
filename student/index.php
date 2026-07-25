<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['student']);

$student = student_profile((int) current_user()['id']);
$stuId = (int) $student['id'];
$pageTitle = 'Dashboard';
$loadCharts = true;
$activeNav = 'dashboard';

$appCount = db()->prepare('SELECT COUNT(*) FROM applications WHERE student_id=?');
$appCount->execute([$stuId]);
$appCount = (int) $appCount->fetchColumn();

$docCount = db()->prepare('SELECT COUNT(*) FROM documents WHERE student_id=?');
$docCount->execute([$stuId]);
$docCount = (int) $docCount->fetchColumn();

$enrollCount = db()->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id=?');
$enrollCount->execute([$stuId]);
$enrollCount = (int) $enrollCount->fetchColumn();

$recentApps = db()->prepare(
    "SELECT a.*, hu.name AS host_uni FROM applications a
     JOIN universities hu ON hu.id = a.host_university_id
     WHERE a.student_id = ? ORDER BY a.updated_at DESC LIMIT 5"
);
$recentApps->execute([$stuId]);
$recentApps = $recentApps->fetchAll();

$chartTrend = chart_application_trend($stuId);
$chartStatus = chart_application_status($stuId);

$homeCoord = db()->prepare(
    "SELECT u.first_name, u.last_name, u.email, c.department
     FROM coordinators c
     JOIN users u ON u.id = c.user_id
     WHERE c.university_id = ? AND u.status = 'active'
     LIMIT 1"
);
$homeCoord->execute([$student['university_id']]);
$homeCoord = $homeCoord->fetch();

require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-700 rounded-2xl p-6 text-white shadow-lg shadow-emerald-500/20 ring-1 ring-white/10">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-emerald-100/80 text-sm font-medium">Welcome back</p>
            <h2 class="text-2xl font-bold mt-1 tracking-tight"><?= e($student['first_name'].' '.$student['last_name']) ?></h2>
            <p class="text-emerald-200/70 text-sm mt-1"><?= e($student['university_name']) ?> · Student #<?= e($student['student_number'] ?? '—') ?></p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-bold"><?= $appCount ?></p>
            <p class="text-emerald-200/70 text-xs">Applications</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
    <?= stat_card('Applications', $appCount, 'brand') ?>
    <?= stat_card('Documents', $docCount, 'rose') ?>
    <?= stat_card('Enrollments', $enrollCount, 'amber') ?>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-5 flex flex-col justify-between card-hover">
        <div>
            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide">My Coordinator</h3>
            <?php if ($homeCoord): ?>
            <p class="text-sm font-bold text-slate-900 mt-2"><?= e($homeCoord['first_name'].' '.$homeCoord['last_name']) ?></p>
            <p class="text-xs text-slate-500"><?= e($homeCoord['department'] ?? 'International Office') ?></p>
            <?php else: ?>
            <p class="text-sm text-slate-500 mt-2">Not assigned</p>
            <?php endif; ?>
        </div>
        <?php if ($homeCoord): ?>
        <a href="mailto:<?= e($homeCoord['email']) ?>" class="mt-3 text-xs text-indigo-600 font-medium hover:text-indigo-700 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Contact
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Application Trend (6 wk)</h2>
        <div class="chart-box" style="height:200px"><canvas id="chartStuTrend"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Application Status</h2>
        <div class="chart-box" style="height:200px"><canvas id="chartStuStatus"></canvas></div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden card-hover">
    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
        <h2 class="text-sm font-semibold text-slate-900">Recent Applications</h2>
        <a href="<?= url('/student/applications.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
            <th class="px-6 py-3 text-left">Host University</th><th class="px-6 py-3 text-left">Status</th><th class="px-6 py-3 text-left">Updated</th><th class="px-6 py-3 text-right"></th>
        </tr></thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($recentApps as $app): ?>
        <tr class="table-row-hover">
            <td class="px-6 py-3.5 font-medium text-slate-900"><?= e($app['host_uni']) ?></td>
            <td class="px-6 py-3.5"><?= status_badge_enhanced($app['status']) ?></td>
            <td class="px-6 py-3.5 text-xs text-slate-500"><?= date('M j, Y', strtotime($app['updated_at'])) ?></td>
            <td class="px-6 py-3.5 text-right"><a href="<?= url('/student/applications.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View →</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    var tc=document.getElementById('chartStuTrend');
    var sc=document.getElementById('chartStuStatus');
    if(tc&&typeof Chart!=='undefined') new Chart(tc,{type:'line',data:{labels:<?= json_encode($chartTrend['labels']) ?>,datasets:[{data:<?= json_encode($chartTrend['data']) ?>,borderColor:'#4f46e5',backgroundColor:'rgba(79,70,229,0.1)',fill:true,tension:0.35}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
    if(sc&&typeof Chart!=='undefined') new Chart(sc,{type:'doughnut',data:{labels:<?= json_encode($chartStatus['labels']) ?>,datasets:[{data:<?= json_encode($chartStatus['data']) ?>,backgroundColor:['#4f46e5','#f59e0b','#10b981','#ef4444','#6366f1']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:10,font:{size:10}}}}}});
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
