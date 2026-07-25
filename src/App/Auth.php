<?php

namespace App;

class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function checkBruteForce(string $email): array
    {
        try {
            $pdo = db();
            $window = date('Y-m-d H:i:s', strtotime('-' . self::LOCKOUT_MINUTES . ' minutes'));

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM activity_logs WHERE action = ? AND description LIKE ? AND created_at >= ?'
            );
            $stmt->execute(['auth.login.failed', '%' . $email . '%', $window]);
            $attempts = (int) $stmt->fetchColumn();

            $remaining = max(0, self::MAX_ATTEMPTS - $attempts);

            if ($attempts >= self::MAX_ATTEMPTS) {
                app_log()->warning('Brute force lockout triggered', ['email' => $email]);
                return [
                    'locked' => true,
                    'remaining' => 0,
                    'message' => 'Account temporarily locked due to too many failed attempts. Try again in ' . self::LOCKOUT_MINUTES . ' minutes.',
                ];
            }

            return [
                'locked' => false,
                'remaining' => $remaining,
                'message' => $remaining <= 2
                    ? 'Warning: ' . $remaining . ' login attempt' . ($remaining === 1 ? '' : 's') . ' remaining.'
                    : '',
            ];
        } catch (\Throwable $e) {
            return ['locked' => false, 'remaining' => 5, 'message' => ''];
        }
    }

    public static function logFailedAttempt(string $email, string $ip): void
    {
        try {
            log_activity('auth.login.failed', "Failed login attempt for {$email}", 'user', null, [
                'ip' => $ip,
                'email' => $email,
            ]);
        } catch (\Throwable $e) {
            app_log()->warning('Could not log failed attempt', ['error' => $e->getMessage()]);
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function generateResetToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function passwordStrength(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'At least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'At least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'At least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'At least one number';
        }
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => max(0, 100 - (count($errors) * 25)),
        ];
    }
}
