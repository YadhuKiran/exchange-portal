<?php
require_once __DIR__ . '/../../includes/init.php';
require_role(['admin']);

$rows = db()->query(
    "SELECT d.id, u.first_name, u.last_name, d.title, d.file_name, d.status, d.application_id, d.uploaded_at, d.file_size
     FROM documents d JOIN students s ON s.id=d.student_id JOIN users u ON u.id=s.user_id"
)->fetchAll();
$csv = array_map(fn($r) => array_values($r), $rows);
log_activity('report.export', 'Exported documents CSV', 'report', null);
stream_csv('documents_' . date('Y-m-d') . '.csv',
    ['ID','First Name','Last Name','Title','File','Status','Application ID','Uploaded','Size'],
    $csv);
