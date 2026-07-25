<?php
require_once __DIR__ . '/../../includes/init.php';
require_role(['coordinator']);

$coord = coordinator_profile((int) current_user()['id']);
$uid = (int) $coord['university_id'];
$stmt = db()->prepare(
    "SELECT d.id, u.first_name, u.last_name, d.title, d.file_name, d.status, d.uploaded_at
     FROM documents d JOIN students s ON s.id=d.student_id JOIN users u ON u.id=s.user_id
     WHERE s.university_id=? OR d.application_id IN (SELECT id FROM applications WHERE home_university_id=? OR host_university_id=?)"
);
$stmt->execute([$uid, $uid, $uid]);
$csv = array_map(fn($r) => array_values($r), $stmt->fetchAll());
stream_csv('documents_scoped_' . date('Y-m-d') . '.csv',
    ['ID','First','Last','Title','File','Status','Uploaded'], $csv);
