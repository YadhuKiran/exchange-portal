<?php
/** Chart.js init — set $chartTrend, $chartStudentsUni, $chartApproval, $chartDocs, $chartGrowth before include */
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') {
        document.querySelectorAll('.chart-box').forEach(function(box) {
            box.innerHTML = '<p class="text-sm text-amber-700 p-4">Charts unavailable. Check your network connection (Chart.js CDN).</p>';
        });
        return;
    }
    const colors = { brand: '#4f46e5', emerald: '#10b981', amber: '#f59e0b', rose: '#f43f5e', slate: '#64748b' };
    const grid = { color: '#f1f5f9' };
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    const mk = (id, type, labels, data, opts = {}) => {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type,
            data: {
                labels,
                datasets: [{ label: opts.label || '', data, backgroundColor: opts.bg || colors.brand, borderColor: opts.border || colors.brand, borderWidth: opts.borderWidth ?? 2, fill: opts.fill ?? false, tension: 0.35 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: opts.legend !== false } },
                scales: type === 'doughnut' || type === 'pie' ? {} : { x: { grid }, y: { beginAtZero: true, grid } }
            }
        });
    };

    mk('chartAppTrend', 'line', <?= json_encode($chartTrend['labels']) ?>, <?= json_encode($chartTrend['data']) ?>, { label: 'Applications', fill: true, bg: 'rgba(79,70,229,0.15)', border: colors.brand });
    mk('chartStudentsByUni', 'bar', <?= json_encode($chartStudentsUni['labels']) ?>, <?= json_encode($chartStudentsUni['data']) ?>, { legend: false, bg: 'rgba(16,185,129,0.7)' });
    mk('chartApprovalRate', 'doughnut', <?= json_encode($chartApproval['labels']) ?>, <?= json_encode($chartApproval['data']) ?>, { bg: [colors.emerald, colors.amber, colors.rose], legend: true });
    mk('chartDocVerification', 'bar', <?= json_encode($chartDocs['labels']) ?>, <?= json_encode($chartDocs['data']) ?>, { legend: false, bg: [colors.amber, colors.emerald, colors.rose] });
    mk('chartMonthlyGrowth', 'bar', <?= json_encode($chartGrowth['labels']) ?>, <?= json_encode($chartGrowth['data']) ?>, { label: 'Submitted', legend: false, bg: 'rgba(99,102,241,0.75)' });
});
</script>
