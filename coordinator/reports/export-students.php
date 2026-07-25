<?php
require_once __DIR__ . '/../../includes/init.php';
require_role(['coordinator']);

$coord = coordinator_profile((int) current_user()['id']);
$uid = (int) $coord['university_id'];
$stmt = db()->prepare(
    "SELECT s.student_number, u.first_name, u.last_name, u.email, uni.name, s.phone, u.status
     FROM students s JOIN users u ON u.id=s.user_id JOIN universities uni ON uni.id=s.university_id
     WHERE s.university_id=? OR s.id IN (SELECT student_id FROM applications WHERE home_university_id=? OR host_university_id=?)"
);
$stmt->execute([$uid, $uid, $uid]);
$csv = array_map(fn($r) => array_values($r), $stmt->fetchAll());
stream_csv('students_scoped_' . date('Y-m-d') . '.csv',
    ['Number','First','Last','Email','University','Phone','Status'], $csv);
