<?php
require_once __DIR__ . '/../../includes/init.php';
require_role(['admin']);

$rows = db()->query(
    "SELECT a.id, u.first_name, u.last_name, u.email, ho.name AS home_uni, hu.name AS host_uni,
            a.semester, a.status, a.submitted_at, a.updated_at, a.coordinator_notes
     FROM applications a
     JOIN students s ON s.id=a.student_id JOIN users u ON u.id=s.user_id
     JOIN universities ho ON ho.id=a.home_university_id
     JOIN universities hu ON hu.id=a.host_university_id
     ORDER BY a.id"
)->fetchAll();

$csv = [];
foreach ($rows as $r) {
    $csv[] = [$r['id'], $r['first_name'], $r['last_name'], $r['email'], $r['home_uni'], $r['host_uni'],
        $r['semester'], $r['status'], $r['submitted_at'], $r['updated_at'], $r['coordinator_notes']];
}
log_activity('report.export', 'Exported applications CSV', 'report', null);
stream_csv('applications_' . date('Y-m-d') . '.csv',
    ['ID','First Name','Last Name','Email','Home University','Host University','Semester','Status','Submitted','Updated','Notes'],
    $csv);
