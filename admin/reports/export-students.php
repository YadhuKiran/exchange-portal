<?php
require_once __DIR__ . '/../../includes/init.php';
require_role(['admin']);

$rows = db()->query(
    "SELECT s.student_number, u.first_name, u.last_name, u.email, uni.name, s.phone, u.status, s.created_at
     FROM students s JOIN users u ON u.id=s.user_id JOIN universities uni ON uni.id=s.university_id"
)->fetchAll();
$csv = array_map(fn($r) => array_values($r), $rows);
log_activity('report.export', 'Exported students CSV', 'report', null);
stream_csv('students_' . date('Y-m-d') . '.csv',
    ['Student Number','First Name','Last Name','Email','University','Phone','Status','Created'],
    $csv);
