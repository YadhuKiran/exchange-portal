<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id && in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        
        $stmt = db()->prepare('SELECT student_id, title FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        
        if ($doc) {
            db()->prepare('UPDATE documents SET status = ? WHERE id = ?')->execute([$status, $id]);
            
            $uStmt = db()->prepare('SELECT user_id FROM students WHERE id = ?');
            $uStmt->execute([$doc['student_id']]);
            $stu = $uStmt->fetch();
            
            if ($stu) {
                notify((int) $stu['user_id'], 'Document ' . ucfirst($status), "Your document '" . $doc['title'] . "' has been " . $status . ".");
            }
            
            flash('success', 'Document status updated to ' . $status . '.');
        } else {
            flash('error', 'Document not found.');
        }
    } else {
        flash('error', 'Invalid request.');
    }
}
redirect($_SERVER['HTTP_REFERER'] ?? '/admin/documents.php');
