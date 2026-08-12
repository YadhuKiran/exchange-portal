<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['coordinator']);
verified
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $coord = coordinator_profile((int) current_user()['id']);
    $uniId = (int) $coord['university_id'];

    if ($id && in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $stmt = db()->prepare('
            SELECT d.student_id, d.title, s.user_id, s.university_id 
            FROM documents d
            JOIN students s ON s.id = d.student_id
            WHERE d.id = ?
        ');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        
        if ($doc && (int) $doc['university_id'] === $uniId) {
            db()->prepare('UPDATE documents SET status = ? WHERE id = ?')->execute([$status, $id]);
            
            notify((int) $doc['user_id'], 'Document ' . ucfirst($status), "Your document '" . $doc['title'] . "' has been " . $status . " by your coordinator.");
            
            flash('success', 'Document status updated to ' . $status . '.');
        } else {
            flash('error', 'Document not found or access denied.');
        }
    } else {
        flash('error', 'Invalid request.');
    }
}
redirect($_SERVER['HTTP_REFERER'] ?? '/coordinator/documents.php');
