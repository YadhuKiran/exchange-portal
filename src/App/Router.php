<?php

namespace App;

class Router
{
    public static function detectBaseUrl(): string
    {
        if (defined('BASE_URL')) {
            return BASE_URL;
        }

        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs'));
        $appDir = str_replace('\\', '/', realpath(__DIR__ . '/../..'));

        if (str_starts_with($appDir, $docRoot)) {
            $base = substr($appDir, strlen($docRoot));
            return $base ?: '';
        }

        return $scriptDir ?: '';
    }

    public static function currentUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$scheme}://{$host}{$uri}";
    }

    public static function asset(string $path): string
    {
        return self::detectBaseUrl() . '/' . ltrim($path, '/');
    }

    public static function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
}
