<?php
/** @var string $queueScope admin|coordinator */
/** @var int|null $queueUniversityId */
$pdo = db();
$pendingDocs = 0;
$pendingPassports = 0;
$pendingVisas = 0;
$pendingTranscripts = 0;
$pendingEnrollments = 0;

if (enterprise_tables_ready()) {
    if ($queueScope === 'admin') {
        $pendingDocs = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE status='pending'")->fetchColumn();
        $pendingPassports = (int) $pdo->query("SELECT COUNT(*) FROM passports WHERE status='pending'")->fetchColumn();
        $pendingVisas = (int) $pdo->query("SELECT COUNT(*) FROM visas WHERE status='pending'")->fetchColumn();
        $pendingTranscripts = (int) $pdo->query("SELECT COUNT(*) FROM transcripts WHERE status='pending'")->fetchColumn();
        $pendingEnrollments = (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='pending'")->fetchColumn();
    } elseif ($queueUniversityId) {
        $uid = $queueUniversityId;
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM documents d JOIN students s ON s.id=d.student_id
             WHERE d.status='pending' AND (s.university_id=? OR d.application_id IN (
               SELECT id FROM applications WHERE home_university_id=? OR host_university_id=?))"
        );
        $stmt->execute([$uid, $uid, $uid]);
        $pendingDocs = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM passports p JOIN students s ON s.id=p.student_id WHERE p.status='pending' AND s.university_id=?"
        );
        $stmt->execute([$uid]);
        $pendingPassports = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM visas v JOIN students s ON s.id=v.student_id WHERE v.status='pending' AND s.university_id=?"
        );
        $stmt->execute([$uid]);
        $pendingVisas = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.status='pending' AND c.university_id=?");
        $stmt->execute([$uid]);
        $pendingEnrollments = (int) $stmt->fetchColumn();
    }
}
$queues = [
    ['Documents', $pendingDocs, $queueScope === 'admin' ? '/admin/documents.php' : '/coordinator/documents.php'],
    ['Passports', $pendingPassports, $queueScope === 'admin' ? '/admin/passports.php' : '/coordinator/passports.php'],
    ['Visas', $pendingVisas, $queueScope === 'admin' ? '/admin/visas.php' : '/coordinator/visas.php'],
    ['Enrollments', $pendingEnrollments, $queueScope === 'admin' ? '/admin/enrollments.php' : '/coordinator/enrollments.php'],
];
if ($queueScope === 'admin') {
    $queues[] = ['Transcripts', $pendingTranscripts, '/admin/transcripts.php'];
}
?>
<div class="bg-white rounded-xl ring-1 ring-slate-200/60 shadow-sm p-5">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-4">Verification Queue</h2>
    <div class="space-y-2">
        <?php foreach ($queues as [$label, $count, $href]): ?>
        <a href="<?= url($href) ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-slate-50 transition group">
            <span class="text-sm font-medium text-slate-700 group-hover:text-brand-600"><?= e($label) ?></span>
            <?php if ($count > 0): ?>
            <span class="min-w-[1.5rem] text-center text-xs font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full"><?= $count ?></span>
            <?php else: ?>
            <span class="text-xs text-slate-400">0</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
