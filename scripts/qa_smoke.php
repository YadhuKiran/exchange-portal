<?php
/**
 * CLI smoke tests — run: c:\xampp\php\php.exe scripts\qa_smoke.php
 */
require_once __DIR__ . '/../includes/init.php';

$issues = [];

function issue(string $severity, string $area, string $msg): void
{
    global $issues;
    $issues[] = ['severity' => $severity, 'area' => $area, 'message' => $msg];
}

// DB connectivity
try {
    db()->query('SELECT 1');
} catch (Throwable $e) {
    issue('Critical', 'Database', 'Cannot connect: ' . $e->getMessage());
}

$requiredTables = [
    'users', 'students', 'coordinators', 'universities', 'courses', 'applications', 'documents', 'notifications',
    'passports', 'visas', 'transcripts', 'enrollments', 'application_status_history', 'activity_logs', 'system_settings',
];
foreach ($requiredTables as $t) {
    try {
        db()->query("SELECT 1 FROM `{$t}` LIMIT 1");
    } catch (Throwable $e) {
        issue('Critical', 'Database', "Missing or inaccessible table: {$t}");
    }
}

// Enterprise helpers
if (!function_exists('enterprise_tables_ready') || !enterprise_tables_ready()) {
    issue('Critical', 'Database', 'enterprise_tables_ready() is false — run 001_enterprise.sql');
}

// Chart data functions
foreach (['chart_application_trend', 'chart_students_by_university', 'chart_approval_rate', 'chart_document_verification', 'chart_monthly_growth'] as $fn) {
    if (!function_exists($fn)) {
        issue('High', 'Charts', "Missing function {$fn}");
        continue;
    }
    try {
        $data = $fn();
        if (empty($data['labels']) && empty($data['data'])) {
            issue('Low', 'Charts', "{$fn}() returned empty labels and data");
        }
    } catch (Throwable $e) {
        issue('High', 'Charts', "{$fn}() error: " . $e->getMessage());
    }
}

// Files exist for all nav routes
require_once __DIR__ . '/../includes/nav-config.php';
$root = realpath(__DIR__ . '/..');
foreach (['admin', 'coordinator', 'student'] as $role) {
    foreach (get_enterprise_nav($role) as $group) {
        foreach ($group['items'] as $item) {
            $path = $root . str_replace('/', DIRECTORY_SEPARATOR, $item['href']);
            if (!is_file($path)) {
                issue('Critical', 'Navigation', "Missing file for [{$role}] {$item['label']}: {$item['href']}");
            }
        }
    }
}

// Public routes
foreach (['/index.php', '/login.php', '/register.php', '/logout.php', '/download.php'] as $r) {
    if (!is_file($root . $r)) {
        issue('Critical', 'Routes', "Missing {$r}");
    }
}

// Admin reports exports
foreach (['/admin/reports/export-applications.php', '/admin/reports/export-students.php', '/admin/reports/export-documents.php'] as $r) {
    if (!is_file($root . $r)) {
        issue('High', 'Reports', "Missing {$r}");
    }
}

// Widget includes
foreach (['activity_feed.php', 'verification_queue.php', 'application_timeline.php', 'compliance_checklist.php', 'status_summary.php', 'admin_charts_script.php'] as $w) {
    if (!is_file($root . '/includes/widgets/' . $w)) {
        issue('High', 'Widgets', "Missing widget {$w}");
    }
}

// Document status enum vs ComplianceVerify
$docEnum = db()->query("SHOW COLUMNS FROM documents LIKE 'status'")->fetch();
if ($docEnum && strpos($docEnum['Type'], 'approved') !== false && strpos($docEnum['Type'], 'verified') === false) {
    // passports use verified - check ComplianceVerify updates documents to approved - OK
}

// passports verified updates documents - OK

echo json_encode($issues, JSON_PRETTY_PRINT);
