<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$redirect = '/admin/contact-messages.php';

if (!$id || !in_array($action, ['read', 'unread', 'delete'], true)) {
    flash('error', 'Invalid request.');
    redirect($redirect);
}

$stmt = db()->prepare("SELECT * FROM activity_logs WHERE id = ? AND action = 'contact.submission'");
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg) {
    flash('error', 'Message not found.');
    redirect($redirect);
}

$meta = json_decode($msg['metadata'] ?? '{}', true);

if ($action === 'read') {
    $meta['read'] = true;
    db()->prepare("UPDATE activity_logs SET metadata = ? WHERE id = ?")
        ->execute([json_encode($meta), $id]);
    flash('success', 'Message marked as read.');
} elseif ($action === 'unread') {
    unset($meta['read']);
    db()->prepare("UPDATE activity_logs SET metadata = ? WHERE id = ?")
        ->execute([json_encode($meta), $id]);
    flash('success', 'Message marked as unread.');
} elseif ($action === 'delete') {
    db()->prepare("DELETE FROM activity_logs WHERE id = ?")
        ->execute([$id]);
    flash('success', 'Message deleted.');
}

redirect($redirect);
