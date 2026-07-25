<?php

function record_status_change(
    int $applicationId,
    ?string $fromStatus,
    string $toStatus,
    int $userId,
    ?string $comment = null
): void {
    if (!enterprise_tables_ready() || $fromStatus === $toStatus) {
        return;
    }
    db()->prepare(
        'INSERT INTO application_status_history (application_id, from_status, to_status, changed_by_user_id, comment)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$applicationId, $fromStatus, $toStatus, $userId, $comment]);

    log_activity(
        'application.status_change',
        "Application #{$applicationId} status changed from " . ($fromStatus ?? 'new') . " to {$toStatus}",
        'application',
        $applicationId,
        ['from' => $fromStatus, 'to' => $toStatus]
    );
}

function fetch_application_timeline(int $applicationId): array
{
    if (!enterprise_tables_ready()) {
        return [];
    }
    $stmt = db()->prepare(
        'SELECT h.*, u.first_name, u.last_name, u.role
         FROM application_status_history h
         JOIN users u ON u.id = h.changed_by_user_id
         WHERE h.application_id = ?
         ORDER BY h.created_at ASC'
    );
    $stmt->execute([$applicationId]);
    return $stmt->fetchAll();
}
