<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/components.php';
require_role(['admin']);

$loadCharts = true;
$pageTitle = 'Analytics Center';
$activeNav = 'analytics';

$stats = enterprise_dashboard_stats();
$chartTrend = chart_application_trend();
$chartStudentsUni = chart_students_by_university();
$chartApproval = chart_approval_rate();
$chartGrowth = chart_monthly_growth();

$uniComp = db()->query(
    "SELECT u.name, COUNT(DISTINCT a.id) AS apps, COUNT(DISTINCT s.id) AS students,
            COUNT(DISTINCT CASE WHEN a.status='approved' THEN a.id END) AS approved,
            COUNT(DISTINCT c.id) AS courses
     FROM universities u
     LEFT JOIN students s ON s.university_id = u.id
     LEFT JOIN applications a ON a.home_university_id = u.id
     LEFT JOIN courses c ON c.university_id = u.id
     GROUP BY u.id ORDER BY apps DESC"
)->fetchAll();

$enrollData = enterprise_tables_ready() ? db()->query(
    "SELECT c.title, COUNT(e.id) AS cnt FROM enrollments e
     JOIN courses c ON c.id=e.course_id WHERE e.status IN ('approved','pending')
     GROUP BY c.id ORDER BY cnt DESC LIMIT 10"
)->fetchAll() : [];

$complianceData = enterprise_tables_ready() ? [
    'passports_verified' => (int) db()->query("SELECT COUNT(*) FROM passports WHERE status='verified'")->fetchColumn(),
    'passports_pending' => (int) db()->query("SELECT COUNT(*) FROM passports WHERE status='pending'")->fetchColumn(),
    'visas_verified' => (int) db()->query("SELECT COUNT(*) FROM visas WHERE status='verified'")->fetchColumn(),
    'visas_pending' => (int) db()->query("SELECT COUNT(*) FROM visas WHERE status='pending'")->fetchColumn(),
    'transcripts_verified' => (int) db()->query("SELECT COUNT(*) FROM transcripts WHERE status='verified'")->fetchColumn(),
    'transcripts_pending' => (int) db()->query("SELECT COUNT(*) FROM transcripts WHERE status='pending'")->fetchColumn(),
] : [];

require __DIR__ . '/../includes/layout.php';
?>
<div class="mb-6">
    <h2 class="text-lg font-bold text-slate-900">Analytics Center</h2>
    <p class="text-sm text-slate-500 mt-1">Comprehensive insights across your global mobility program</p>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <?= stat_card('Total Applications', $stats['applications'], 'brand') ?>
    <?= stat_card('Approved', $stats['approved_apps'], 'emerald') ?>
    <?= stat_card('Active Students', $stats['students'], 'cyan') ?>
    <?= stat_card('Partner Universities', $stats['universities'], 'violet') ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Application Trends (12 months)</h2>
        <div class="chart-box"><canvas id="chartTrend"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Monthly Submission Growth</h2>
        <div class="chart-box"><canvas id="chartGrowth"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Approval Rate Breakdown</h2>
        <div class="chart-box"><canvas id="chartApproval"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Students by University</h2>
        <div class="chart-box"><canvas id="chartStudents"></canvas></div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm overflow-hidden card-hover">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">University Comparison</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>
                    <th class="px-5 py-3 text-left">University</th><th class="px-5 py-3 text-center">Students</th><th class="px-5 py-3 text-center">Applications</th><th class="px-5 py-3 text-center">Approved</th><th class="px-5 py-3 text-center">Courses</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($uniComp as $u): ?>
                <tr class="table-row-hover">
                    <td class="px-5 py-3 font-medium text-slate-900"><?= e($u['name']) ?></td>
                    <td class="px-5 py-3 text-center"><?= (int)$u['students'] ?></td>
                    <td class="px-5 py-3 text-center"><?= (int)$u['apps'] ?></td>
                    <td class="px-5 py-3 text-center"><span class="text-emerald-600 font-medium"><?= (int)$u['approved'] ?></span></td>
                    <td class="px-5 py-3 text-center"><?= (int)$u['courses'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Compliance Analytics</h2>
        <?php if ($complianceData): ?>
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="text-center p-4 bg-emerald-50 rounded-xl"><p class="text-2xl font-bold text-emerald-600"><?= $complianceData['passports_verified'] ?></p><p class="text-xs text-slate-500 mt-1">Passports</p></div>
            <div class="text-center p-4 bg-amber-50 rounded-xl"><p class="text-2xl font-bold text-amber-600"><?= $complianceData['visas_verified'] ?></p><p class="text-xs text-slate-500 mt-1">Visas</p></div>
            <div class="text-center p-4 bg-blue-50 rounded-xl"><p class="text-2xl font-bold text-blue-600"><?= $complianceData['transcripts_verified'] ?></p><p class="text-xs text-slate-500 mt-1">Transcripts</p></div>
        </div>
        <div class="space-y-3">
            <div><div class="flex justify-between text-xs mb-1"><span class="text-slate-600">Passport Compliance</span><span class="font-medium"><?= $complianceData['passports_verified']+$complianceData['passports_pending'] > 0 ? round($complianceData['passports_verified']/($complianceData['passports_verified']+$complianceData['passports_pending'])*100) : 0 ?>%</span></div><div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width:<?= $complianceData['passports_verified']+$complianceData['passports_pending'] > 0 ? $complianceData['passports_verified']/($complianceData['passports_verified']+$complianceData['passports_pending'])*100 : 0 ?>%"></div></div></div>
            <div><div class="flex justify-between text-xs mb-1"><span class="text-slate-600">Visa Compliance</span><span class="font-medium"><?= $complianceData['visas_verified']+$complianceData['visas_pending'] > 0 ? round($complianceData['visas_verified']/($complianceData['visas_verified']+$complianceData['visas_pending'])*100) : 0 ?>%</span></div><div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-amber-500 rounded-full" style="width:<?= $complianceData['visas_verified']+$complianceData['visas_pending'] > 0 ? $complianceData['visas_verified']/($complianceData['visas_verified']+$complianceData['visas_pending'])*100 : 0 ?>%"></div></div></div>
            <div><div class="flex justify-between text-xs mb-1"><span class="text-slate-600">Transcript Compliance</span><span class="font-medium"><?= $complianceData['transcripts_verified']+$complianceData['transcripts_pending'] > 0 ? round($complianceData['transcripts_verified']/($complianceData['transcripts_verified']+$complianceData['transcripts_pending'])*100) : 0 ?>%</span></div><div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-blue-500 rounded-full" style="width:<?= $complianceData['transcripts_verified']+$complianceData['transcripts_pending'] > 0 ? $complianceData['transcripts_verified']/($complianceData['transcripts_verified']+$complianceData['transcripts_pending'])*100 : 0 ?>%"></div></div></div>
        </div>
        <?php else: ?>
        <p class="text-sm text-slate-500 text-center py-6">Enterprise migration required for compliance data.</p>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 card-hover">
    <h2 class="text-sm font-semibold text-slate-900 mb-4">Top Enrolled Courses</h2>
    <?php if ($enrollData): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php foreach ($enrollData as $e): ?>
        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
            <span class="text-sm font-medium text-slate-700"><?= e($e['title']) ?></span>
            <span class="text-sm font-bold text-indigo-600"><?= (int)$e['cnt'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-sm text-slate-500 text-center py-6">No enrollment data available.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;
    const c = '#4f46e5', e = '#10b981', a = '#f59e0b', r = '#f43f5e', s = '#64748b', v = '#8b5cf6';
    const g = { color: '#f1f5f9' };
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    new Chart(document.getElementById('chartTrend'), { type:'line', data:{ labels:<?= json_encode($chartTrend['labels']) ?>, datasets:[{ label:'Applications', data:<?= json_encode($chartTrend['data']) ?>, borderColor:c, backgroundColor:'rgba(79,70,229,0.12)', fill:true, tension:0.35 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ grid:g }, y:{ beginAtZero:true, grid:g } } } });
    new Chart(document.getElementById('chartGrowth'), { type:'bar', data:{ labels:<?= json_encode($chartGrowth['labels']) ?>, datasets:[{ label:'Submitted', data:<?= json_encode($chartGrowth['data']) ?>, backgroundColor:'rgba(79,70,229,0.7)', borderRadius:4 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ grid:g }, y:{ beginAtZero:true, grid:g } } } });
    new Chart(document.getElementById('chartApproval'), { type:'doughnut', data:{ labels:<?= json_encode($chartApproval['labels']) ?>, datasets:[{ data:<?= json_encode($chartApproval['data']) ?>, backgroundColor:[e, a, r] }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } } });
    new Chart(document.getElementById('chartStudents'), { type:'bar', data:{ labels:<?= json_encode($chartStudentsUni['labels']) ?>, datasets:[{ data:<?= json_encode($chartStudentsUni['data']) ?>, backgroundColor:'rgba(16,185,129,0.7)', borderRadius:4 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ x:{ grid:g }, y:{ beginAtZero:true, grid:g } }, indexAxis:'y' } });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
