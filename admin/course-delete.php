<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);
$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    db()->prepare('DELETE FROM courses WHERE id = ?')->execute([$id]);
    log_activity('course.deleted', "Course #{$id} deleted", 'course', $id);
    flash('success', 'Course deleted.');
}
redirect('/admin/courses.php');
