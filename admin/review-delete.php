<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
    db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
    flash('success', 'Review deleted.');
}
redirect('/admin/reviews.php');
