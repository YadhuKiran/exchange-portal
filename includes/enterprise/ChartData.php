<?php

function chart_months_range(): int
{
    $n = (int) setting('chart_months_range', 12);
    return max(3, min(24, $n));
}

function chart_application_trend(?int $studentId = null): array
{
    $months = chart_months_range();
    $pdo = db();
    $labels = [];
    $data = [];
    $cond = $studentId ? 'AND student_id = ?' : '';
    for ($i = $months - 1; $i >= 0; $i--) {
        $d = new DateTime("first day of -{$i} months");
        $labels[] = $d->format('M Y');
        $start = $d->format('Y-m-01');
        $end = $d->format('Y-m-t');
        $sql = "SELECT COUNT(*) FROM applications WHERE DATE(created_at) BETWEEN ? AND ? $cond";
        $stmt = $pdo->prepare($sql);
        if ($studentId) {
            $stmt->execute([$start, $end, $studentId]);
        } else {
            $stmt->execute([$start, $end]);
        }
        $data[] = (int) $stmt->fetchColumn();
    }
    return ['labels' => $labels, 'data' => $data];
}

function chart_application_status(?int $studentId = null): array
{
    $pdo = db();
    $cond = $studentId ? 'WHERE student_id = ?' : '';
    $sql = "SELECT status, COUNT(*) AS cnt FROM applications $cond GROUP BY status";
    $stmt = $pdo->prepare($sql);
    if ($studentId) {
        $stmt->execute([$studentId]);
    } else {
        $stmt->execute();
    }
    $rows = $stmt->fetchAll();
    if (!$rows) {
        return ['labels' => ['Draft', 'Submitted', 'Approved', 'Rejected'], 'data' => [0, 0, 0, 0]];
    }
    return [
        'labels' => array_map(fn($r) => ucfirst(str_replace('_', ' ', $r['status'])), $rows),
        'data'   => array_map('intval', array_column($rows, 'cnt')),
    ];
}

function chart_students_by_university(): array
{
    $rows = db()->query(
        'SELECT u.name, COUNT(s.id) AS cnt
         FROM universities u
         LEFT JOIN students s ON s.university_id = u.id
         GROUP BY u.id ORDER BY cnt DESC LIMIT 10'
    )->fetchAll();
    return [
        'labels' => array_column($rows, 'name'),
        'data'   => array_map('intval', array_column($rows, 'cnt')),
    ];
}

function chart_approval_rate(): array
{
    $pdo = db();
    $approved = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='approved'")->fetchColumn();
    $rejected = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='rejected'")->fetchColumn();
    $other = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status NOT IN ('approved','rejected')")->fetchColumn();
    return [
        'labels' => ['Approved', 'In Progress', 'Rejected'],
        'data'   => [$approved, $other, $rejected],
    ];
}

function chart_document_verification(): array
{
    $rows = db()->query(
        "SELECT status, COUNT(*) AS cnt FROM documents GROUP BY status"
    )->fetchAll();
    $labels = [];
    $data = [];
    foreach ($rows as $r) {
        $labels[] = ucfirst($r['status']);
        $data[] = (int) $r['cnt'];
    }
    if (!$labels) {
        return ['labels' => ['Pending', 'Approved', 'Rejected'], 'data' => [0, 0, 0]];
    }
    return ['labels' => $labels, 'data' => $data];
}

function chart_monthly_growth(): array
{
    $months = chart_months_range();
    $labels = [];
    $data = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $d = new DateTime("first day of -{$i} months");
        $labels[] = $d->format('M');
        $start = $d->format('Y-m-01');
        $end = $d->format('Y-m-t');
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM applications WHERE status != 'draft' AND DATE(submitted_at) BETWEEN ? AND ?"
        );
        $stmt->execute([$start, $end]);
        $data[] = (int) $stmt->fetchColumn();
    }
    return ['labels' => $labels, 'data' => $data];
}

function chart_coordinator_pending_trend(int $uniId): array
{
    $labels = [];
    $data = [];
    for ($i = 5; $i >= 0; $i--) {
        $d = new DateTime("-{$i} weeks");
        $labels[] = $d->format('M j');
        $start = $d->format('Y-m-d');
        $end = (clone $d)->modify('+6 days')->format('Y-m-d');
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM applications
             WHERE (home_university_id=? OR host_university_id=?)
             AND status IN ('submitted','under_review')
             AND DATE(updated_at) BETWEEN ? AND ?"
        );
        $stmt->execute([$uniId, $uniId, $start, $end]);
        $data[] = (int) $stmt->fetchColumn();
    }
    return ['labels' => $labels, 'data' => $data];
}

function enterprise_dashboard_stats(): array
{
    $stats = dashboard_stats();
    if (!enterprise_tables_ready()) {
        return array_merge($stats, [
            'enrollments' => 0, 'pending_enrollments' => 0,
            'pending_passports' => 0, 'pending_visas' => 0, 'pending_transcripts' => 0,
        ]);
    }
    $pdo = db();
    $stats['enrollments'] = (int) $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn();
    $stats['pending_enrollments'] = (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='pending'")->fetchColumn();
    $stats['pending_passports'] = (int) $pdo->query("SELECT COUNT(*) FROM passports WHERE status='pending'")->fetchColumn();
    $stats['pending_visas'] = (int) $pdo->query("SELECT COUNT(*) FROM visas WHERE status='pending'")->fetchColumn();
    $stats['pending_transcripts'] = (int) $pdo->query("SELECT COUNT(*) FROM transcripts WHERE status='pending'")->fetchColumn();
    return $stats;
}

function nav_badge_counts(?string $role = null, ?int $universityId = null): array
{
    $badges = [];
    if (!enterprise_tables_ready()) {
        return $badges;
    }
    $pdo = db();
    if ($role === 'admin') {
        $badges['documents'] = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE status='pending'")->fetchColumn();
        $badges['applications'] = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('submitted','under_review')")->fetchColumn();
        $badges['enrollments'] = (int) $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status='pending'")->fetchColumn();
        $badges['passports'] = (int) $pdo->query("SELECT COUNT(*) FROM passports WHERE status='pending'")->fetchColumn();
        $badges['visas'] = (int) $pdo->query("SELECT COUNT(*) FROM visas WHERE status='pending'")->fetchColumn();
    } elseif ($role === 'coordinator' && $universityId) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM applications WHERE (home_university_id=? OR host_university_id=?) AND status IN ('submitted','under_review')"
        );
        $stmt->execute([$universityId, $universityId]);
        $badges['applications'] = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM documents d JOIN students s ON s.id=d.student_id
             WHERE d.status='pending' AND (s.university_id=? OR d.application_id IN (
               SELECT id FROM applications WHERE home_university_id=? OR host_university_id=?))"
        );
        $stmt->execute([$universityId, $universityId, $universityId]);
        $badges['documents'] = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE c.university_id=? AND e.status='pending'");
        $stmt->execute([$universityId]);
        $badges['enrollments'] = (int) $stmt->fetchColumn();
    }
    return $badges;
}
