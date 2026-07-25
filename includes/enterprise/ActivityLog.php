<?php

function mvp_tables_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        db()->query('SELECT 1 FROM users LIMIT 1');
        $ready = true;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function enterprise_tables_ready(): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        db()->query('SELECT 1 FROM activity_logs LIMIT 1');
        $ready = true;
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function log_activity(
    string $action,
    string $description,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $metadata = null
): void {
    if (!enterprise_tables_ready()) {
        return;
    }
    $user = current_user();
    $userId = $user ? (int) $user['id'] : null;
    $metaJson = $metadata ? json_encode($metadata) : null;
    db()->prepare(
        'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, metadata)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $userId,
        $action,
        $entityType,
        $entityId,
        mb_substr($description, 0, 500),
        $_SERVER['REMOTE_ADDR'] ?? null,
        isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
        $metaJson,
    ]);
}

function fetch_activity_feed(int $limit = 10, ?int $universityId = null, ?int $userId = null): array
{
    if (!enterprise_tables_ready()) {
        return [];
    }
    $sql = 'SELECT al.*, u.first_name, u.last_name, u.role
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE 1=1';
    $params = [];
    if ($userId !== null) {
        $sql .= ' AND al.user_id = ?';
        $params[] = $userId;
    } elseif ($universityId !== null) {
        $sql .= ' AND (al.user_id IN (
            SELECT user_id FROM students WHERE university_id = ?
        ) OR al.user_id IN (
            SELECT user_id FROM coordinators WHERE university_id = ?
        ) OR al.entity_type IN (\'application\',\'document\',\'enrollment\'))';
        $params[] = $universityId;
        $params[] = $universityId;
    }
    $sql .= ' ORDER BY al.created_at DESC LIMIT ' . (int) $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
