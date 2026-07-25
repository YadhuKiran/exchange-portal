<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('SELECT user_id FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$row['user_id']]);
        flash('success', 'Student deleted.');
    }
}
redirect('/admin/students.php');
