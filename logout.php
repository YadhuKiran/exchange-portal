<?php
require_once __DIR__ . '/includes/init.php';
if (is_logged_in() && function_exists('log_activity')) {
    $u = current_user();
    log_activity('auth.logout', $u['first_name'] . ' ' . $u['last_name'] . ' signed out', 'user', (int) $u['id']);
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
flash('success', 'You have been signed out.');
redirect('/login.php');
