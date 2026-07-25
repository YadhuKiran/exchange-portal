<?php

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;

    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile) && class_exists('Dotenv\Dotenv')) {
        try {
            Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
        } catch (Throwable $e) {
            $_ENV['APP_ENV'] = 'production';
        }
    }

    if (class_exists('App\Logger')) {
        App\Logger::init();
    }
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/components.php';
require_once __DIR__ . '/enterprise/ActivityLog.php';
require_once __DIR__ . '/enterprise/Settings.php';
require_once __DIR__ . '/enterprise/ApplicationHistory.php';
require_once __DIR__ . '/enterprise/ChartData.php';
require_once __DIR__ . '/enterprise/ExportCsv.php';

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 0),
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function url(string $path = ''): string
{
    return BASE_URL . $path;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND status = ?');
        $stmt->execute([$_SESSION['user_id'], 'active']);
        $user = $stmt->fetch() ?: null;
        if (!$user) {
            session_destroy();
            return null;
        }
    }
    return $user;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('/login.php');
    }
}

function require_role(array $roles): void
{
    require_login();
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        flash('error', 'You do not have permission to access that page.');
        redirect('/login.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}

function notify(int $userId, string $title, string $message): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $message]);
}

function notify_and_mail(int $userId, string $title, string $message, ?string $emailTemplate = null, array $emailData = []): void
{
    notify($userId, $title, $message);

    try {
        $userStmt = db()->prepare('SELECT email, first_name FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        if ($user && class_exists('App\Mailer')) {
            $data = array_merge([
                'first_name' => $user['first_name'],
                'app_url' => url(''),
                'year' => date('Y'),
            ], $emailData);

            if ($emailTemplate) {
                App\Mailer::init();
                App\Mailer::sendTemplate($user['email'], $title, $emailTemplate, $data);
            }
        }
    } catch (Throwable $e) {
        if (function_exists('app_log')) {
            app_log()->warning('notify_and_mail failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}

function change_password(int $userId, string $currentPassword, string $newPassword): array
{
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);

    if (function_exists('log_activity')) {
        log_activity('auth.password.change', 'Password changed', 'user', $userId);
    }

    return ['success' => true, 'message' => 'Password changed successfully.'];
}

function unread_notification_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function status_badge(string $status): string
{
    $map = [
        'draft'        => 'bg-slate-100 text-slate-700',
        'submitted'    => 'bg-blue-100 text-blue-800',
        'under_review' => 'bg-amber-100 text-amber-800',
        'approved'     => 'bg-emerald-100 text-emerald-800',
        'rejected'     => 'bg-red-100 text-red-800',
        'pending'      => 'bg-amber-100 text-amber-800',
        'open'         => 'bg-emerald-100 text-emerald-800',
        'closed'       => 'bg-slate-100 text-slate-600',
        'active'       => 'bg-emerald-100 text-emerald-800',
        'inactive'     => 'bg-slate-100 text-slate-600',
        'verified'     => 'bg-emerald-100 text-emerald-800',
        'expired'      => 'bg-red-100 text-red-800',
        'dropped'      => 'bg-slate-100 text-slate-600',
        'completed'    => 'bg-blue-100 text-blue-800',
    ];
    $class = $map[$status] ?? 'bg-slate-100 text-slate-700';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . e($label) . '</span>';
}

function handle_upload(string $fieldName, string $customDir = ''): ?array
{
    if (empty($_FILES[$fieldName]['name'])) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed.');
    }
    $maxMb = (int) setting('max_upload_mb', UPLOAD_MAX_MB);
    if ($file['size'] > $maxMb * 1024 * 1024) {
        throw new RuntimeException('File exceeds maximum size of ' . $maxMb . ' MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $blockedExts = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'bat', 'sh', 'js'];
    if (in_array($ext, $blockedExts, true)) {
        throw new RuntimeException('This specific file type is banned for security reasons.');
    }

    $dir = $customDir ?: UPLOAD_DIR;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $stored = uniqid('doc_', true) . '.' . $ext;
    $dest = $dir . $stored;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save uploaded file.');
    }
    return [
        'original' => $file['name'],
        'stored'   => $stored,
        'path'     => $stored,
        'size'     => (int) $file['size'],
    ];
}

function student_profile(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT s.*, u.first_name, u.last_name, u.email, uni.name AS university_name
         FROM students s
         JOIN users u ON u.id = s.user_id
         JOIN universities uni ON uni.id = s.university_id
         WHERE s.user_id = ?'
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function coordinator_profile(int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT c.*, u.first_name, u.last_name, u.email, uni.name AS university_name
         FROM coordinators c
         JOIN users u ON u.id = c.user_id
         JOIN universities uni ON uni.id = c.university_id
         WHERE c.user_id = ?'
    );
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function dashboard_stats(): array
{
    $pdo = db();
    return [
        'students'      => (int) $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
        'coordinators'  => (int) $pdo->query("SELECT COUNT(*) FROM coordinators")->fetchColumn(),
        'universities'  => (int) $pdo->query("SELECT COUNT(*) FROM universities WHERE status='active'")->fetchColumn(),
        'courses'       => (int) $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
        'applications'  => (int) $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
        'documents'     => (int) $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
        'pending_docs'  => (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE status='pending'")->fetchColumn(),
        'approved_apps' => (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status='approved'")->fetchColumn(),
    ];
}

if (is_logged_in()) {
    $user_role = $_SESSION['user_role'] ?? '';
    if (in_array($user_role, ['student', 'coordinator'])) {
        if (!isset($_SESSION['login_time'])) {
            $_SESSION['login_time'] = time();
        }
        if (time() - $_SESSION['login_time'] >= 1800) {
            session_unset();
            session_destroy();
            session_start();
            flash('error', 'Your session has expired after 30 minutes.');
            redirect('/login.php');
        }
    }
}

if (php_sapi_name() !== 'cli' && !mvp_tables_ready()) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Setup required</title></head><body style="font-family:system-ui,sans-serif;padding:2rem;max-width:36rem;line-height:1.5"><h1>Database setup required</h1><p>Core tables are missing. Import <code>database/database.sql</code> (then <code>database/migrations/001_enterprise.sql</code> for enterprise features) before using this portal.</p></body></html>';
    exit;
}
