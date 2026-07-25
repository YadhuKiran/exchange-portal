<?php
/**
 * HTTP route probe — run with Apache up:
 * c:\xampp\php\php.exe scripts\qa_http.php
 */
$base = 'http://localhost/exchange_portal';
$routes = [
    'GET /login.php' => '/login.php',
    'GET /register.php' => '/register.php',
    'GET /admin/index.php (guest)' => '/admin/index.php',
    'GET /student/index.php (guest)' => '/student/index.php',
];

$ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
foreach ($routes as $label => $path) {
    $url = $base . $path;
    $body = @file_get_contents($url, false, $ctx);
    $code = '???';
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $code = $m[0];
    }
    $fatal = $body && (stripos($body, 'Fatal error') !== false || stripos($body, 'Parse error') !== false);
    $pdo = $body && stripos($body, 'PDOException') !== false;
    echo "{$label}: HTTP {$code}" . ($fatal ? ' FATAL' : '') . ($pdo ? ' PDO' : '') . PHP_EOL;
}
