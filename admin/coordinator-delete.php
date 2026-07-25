<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    $stmt = db()->prepare('SELECT user_id FROM coordinators WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        if (function_exists('log_activity')) log_activity('coordinator.deleted', 'Deleted coordinator ID: ' . $id);
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$row['user_id']]);
        flash('success', 'Coordinator deleted.');
    }
}
redirect('/admin/coordinators.php');
