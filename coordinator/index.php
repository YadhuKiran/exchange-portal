<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['coordinator']);

$coord = coordinator_profile((int) current_user()['id']);
$uniId = (int) $coord['university_id'];
$pageTitle = 'Dashboard';
$loadCharts = true;
$activeNav = 'dashboard';

$pendingApps = db()->prepare(
    "SELECT COUNT(*) FROM applications WHERE (home_university_id=? OR host_university_id=?) AND status IN ('submitted','under_review')"
);
$pendingApps->execute([$uniId, $uniId]);
$pendingApps = (int) $pendingApps->fetchColumn();

$pendingDocs = nav_badge_counts('coordinator', $uniId)['documents'] ?? 0;
$pendingEnroll = nav_badge_counts('coordinator', $uniId)['enrollments'] ?? 0;

$studentCount = db()->prepare('SELECT COUNT(*) FROM students WHERE university_id=?');
$studentCount->execute([$uniId]);
$studentCount = (int) $studentCount->fetchColumn();

$chartTrend = chart_coordinator_pending_trend($uniId);
$chartDocs = chart_document_verification();

$recentApps = db()->prepare(
    "SELECT a.*, u.first_name, u.last_name, hu.name AS host_uni
     FROM applications a JOIN students s ON s.id=a.student_id JOIN users u ON u.id=s.user_id
     JOIN universities hu ON hu.id=a.host_university_id
     WHERE (a.home_university_id=? OR a.host_university_id=?) AND a.status!='draft'
     ORDER BY a.updated_at DESC LIMIT 5"
);
$recentApps->execute([$uniId, $uniId]);
$recentApps = $recentApps->fetchAll();

require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6 bg-gradient-to-r from-amber-600 via-orange-600 to-rose-700 rounded-2xl p-6 text-white shadow-lg shadow-orange-500/20 ring-1 ring-white/10">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-orange-100/80 text-sm font-medium">Assigned Institution</p>
            <h2 class="text-2xl font-bold mt-1 tracking-tight"><?= e($coord['university_name']) ?></h2>
            <p class="text-orange-200/70 text-sm mt-1"><?= e($coord['department'] ?? 'International Programs') ?></p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-bold"><?= $pendingApps ?></p>
            <p class="text-orange-200/70 text-xs">Pending Reviews</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <?= stat_card('Pending Applications', $pendingApps, 'amber') ?>
    <?= stat_card('Documents to Verify', $pendingDocs, 'rose') ?>
    <?= stat_card('Active Students', $studentCount, 'brand') ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Pending Applications (6 wk)</h2>
        <div class="chart-box" style="height:200px"><canvas id="chartCoordTrend"></canvas></div>
    </div>
    <?php $queueScope = 'coordinator'; $queueUniversityId = $uniId; require __DIR__ . '/../includes/widgets/verification_queue.php'; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden card-hover">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h2 class="text-sm font-semibold text-slate-900">Recent Applications</h2>
            <a href="<?= url('/coordinator/applications.php') ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                <th class="px-6 py-3 text-left">Student</th><th class="px-6 py-3 text-left">Host</th><th class="px-6 py-3 text-left">Status</th><th class="px-6 py-3 text-right"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($recentApps as $app): ?>
            <tr class="table-row-hover">
                <td class="px-6 py-3.5"><div class="flex items-center gap-2"><?= avatar_icon($app['first_name'].' '.$app['last_name'], 'sm') ?><span class="font-medium text-slate-900"><?= e($app['first_name'].' '.$app['last_name']) ?></span></div></td>
                <td class="px-6 py-3.5 text-slate-600"><?= e($app['host_uni']) ?></td>
                <td class="px-6 py-3.5"><?= status_badge_enhanced($app['status']) ?></td>
                <td class="px-6 py-3.5 text-right"><a href="<?= url('/coordinator/application-review.php?id='.$app['id']) ?>" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">Review →</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php $feedLimit = 8; $feedUniversityId = $uniId; require __DIR__ . '/../includes/widgets/activity_feed.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const el=document.getElementById('chartCoordTrend');
    if(el&&typeof Chart!=='undefined') new Chart(el,{type:'line',data:{labels:<?= json_encode($chartTrend['labels']) ?>,datasets:[{data:<?= json_encode($chartTrend['data']) ?>,borderColor:'#4f46e5',backgroundColor:'rgba(79,70,229,0.1)',fill:true,tension:0.35}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
