<?php

function setting(string $key, $default = null)
{
    static $cache = null;
    if (!enterprise_tables_ready()) {
        return $default;
    }
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db()->query('SELECT setting_key, setting_value, data_type FROM system_settings')->fetchAll();
            foreach ($rows as $row) {
                $cache[$row['setting_key']] = cast_setting_value($row['setting_value'], $row['data_type']);
            }
        } catch (Throwable $e) {
            return $default;
        }
    }
    return $cache[$key] ?? $default;
}

function cast_setting_value(string $value, string $type)
{
    return match ($type) {
        'integer' => (int) $value,
        'boolean' => in_array(strtolower($value), ['1', 'true', 'yes'], true),
        'json'    => json_decode($value, true) ?? $value,
        default   => $value,
    };
}

function set_setting(string $key, $value, ?int $updatedBy = null): void
{
    if (!enterprise_tables_ready()) {
        return;
    }
    $str = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    db()->prepare(
        'UPDATE system_settings SET setting_value = ?, updated_by = ?, updated_at = NOW() WHERE setting_key = ?'
    )->execute([$str, $updatedBy, $key]);
}

function all_settings(): array
{
    if (!enterprise_tables_ready()) {
        return [];
    }
    return db()->query('SELECT * FROM system_settings ORDER BY setting_group, label')->fetchAll();
}
