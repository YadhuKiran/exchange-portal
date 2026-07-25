<?php

function verify_compliance_record(string $table, int $id, string $status, int $verifierId): bool
{
    if (!in_array($table, ['passports', 'visas', 'transcripts'], true)) {
        return false;
    }
    if (!in_array($status, ['verified', 'rejected'], true)) {
        return false;
    }
    $sql = "UPDATE {$table} SET status=?, verified_by=?, verified_at=NOW() WHERE id=? AND status='pending'";
    $stmt = db()->prepare($sql);
    $stmt->execute([$status, $verifierId, $id]);
    if (!$stmt->rowCount()) {
        return false;
    }
    log_activity("{$table}.{$status}", ucfirst(rtrim($table, 's')) . " #{$id} {$status}", $table, $id);

    $labels = ['passports' => 'Passport', 'visas' => 'Visa', 'transcripts' => 'Transcript'];
    $rowStmt = db()->prepare("SELECT student_id, document_id FROM {$table} WHERE id=?");
    $rowStmt->execute([$id]);
    $row = $rowStmt->fetch();
    if ($row && $row['document_id']) {
        db()->prepare('UPDATE documents SET status=? WHERE id=?')->execute([
            $status === 'verified' ? 'approved' : 'rejected',
            $row['document_id'],
        ]);
    }
    if ($row) {
        $u = db()->prepare('SELECT user_id FROM students WHERE id=?');
        $u->execute([$row['student_id']]);
        $label = $labels[$table];
        notify((int) $u->fetchColumn(), "{$label} {$status}", "Your {$label} record has been {$status}.");
    }

    return true;
}
