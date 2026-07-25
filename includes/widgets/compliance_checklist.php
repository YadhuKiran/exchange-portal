<?php
/** @var array $complianceProfile student profile row */
$sid = (int) $complianceProfile['id'];
$hasPassport = false;
$passportOk = false;
$visaCount = 0;
$visaOk = false;
$docCount = 0;
$transcriptCount = 0;
if (enterprise_tables_ready()) {
    $stmt = db()->prepare('SELECT status FROM passports WHERE student_id = ?');
    $stmt->execute([$sid]);
    $p = $stmt->fetch();
    $hasPassport = (bool) $p;
    $passportOk = $p && $p['status'] === 'verified';
    $stmt = db()->prepare("SELECT COUNT(*) FROM visas WHERE student_id = ?");
    $stmt->execute([$sid]);
    $visaCount = (int) $stmt->fetchColumn();
    $stmt = db()->prepare("SELECT COUNT(*) FROM visas WHERE student_id = ? AND status='verified'");
    $stmt->execute([$sid]);
    $visaOk = (int) $stmt->fetchColumn() > 0;
    $stmt = db()->prepare('SELECT COUNT(*) FROM documents WHERE student_id = ?');
    $stmt->execute([$sid]);
    $docCount = (int) $stmt->fetchColumn();
    $stmt = db()->prepare("SELECT COUNT(*) FROM transcripts WHERE student_id = ? AND status='verified'");
    $stmt->execute([$sid]);
    $transcriptCount = (int) $stmt->fetchColumn();
}
$items = [
    ['Passport on file', $hasPassport, '/student/passport.php'],
    ['Passport verified', $passportOk, '/student/passport.php'],
    ['Visa record', $visaCount > 0, '/student/visas.php'],
    ['Visa verified', $visaOk, '/student/visas.php'],
    ['Documents uploaded', $docCount >= 1, '/student/documents.php'],
    ['Verified transcript', $transcriptCount >= 1, '/student/transcripts.php'],
];
$done = count(array_filter($items, fn($i) => $i[1]));
$pct = round($done / count($items) * 100);
?>
<div class="bg-white rounded-xl ring-1 ring-slate-200/60 shadow-sm p-5">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Compliance Checklist</h2>
        <span class="text-sm font-bold text-brand-600"><?= $pct ?>%</span>
    </div>
    <div class="h-2 bg-slate-100 rounded-full mb-4 overflow-hidden">
        <div class="h-full bg-brand-600 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
    </div>
    <ul class="space-y-2">
        <?php foreach ($items as [$label, $ok, $href]): ?>
        <li>
            <a href="<?= url($href) ?>" class="flex items-center gap-2 text-sm <?= $ok ? 'text-emerald-700' : 'text-slate-600' ?> hover:text-brand-600">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs <?= $ok ? 'bg-emerald-100' : 'bg-slate-100' ?>"><?= $ok ? '✓' : '○' ?></span>
                <?= e($label) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
