<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);
$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    try {
        db()->prepare('DELETE FROM universities WHERE id = ?')->execute([$id]);
        log_activity('university.deleted', "University #{$id} deleted", 'university', $id);
        flash('success', 'University deleted.');
    } catch (PDOException $e) {
        flash('error', 'Cannot delete: university is linked to students or applications.');
    }
}
redirect('/admin/universities.php');
