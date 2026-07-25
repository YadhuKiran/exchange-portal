<?php
require_once __DIR__ . '/../../includes/init.php';
require_role(['coordinator']);

$coord = coordinator_profile((int) current_user()['id']);
$uid = (int) $coord['university_id'];
$stmt = db()->prepare(
    "SELECT a.id, u.first_name, u.last_name, u.email, ho.name, hu.name, a.semester, a.status, a.submitted_at, a.updated_at
     FROM applications a JOIN students s ON s.id=a.student_id JOIN users u ON u.id=s.user_id
     JOIN universities ho ON ho.id=a.home_university_id JOIN universities hu ON hu.id=a.host_university_id
     WHERE a.home_university_id=? OR a.host_university_id=? ORDER BY a.id"
);
$stmt->execute([$uid, $uid]);
$csv = array_map(fn($r) => array_values($r), $stmt->fetchAll());
stream_csv('applications_scoped_' . date('Y-m-d') . '.csv',
    ['ID','First','Last','Email','Home','Host','Semester','Status','Submitted','Updated'], $csv);
