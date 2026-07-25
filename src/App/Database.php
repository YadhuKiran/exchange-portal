<?php

namespace App;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    public static function connect(
        string $host,
        string $port,
        string $name,
        string $user,
        string $pass,
        string $charset = 'utf8mb4'
    ): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return self::$instance;
    }

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Database not connected. Call Database::connect() first.');
        }
        return self::$instance;
    }

    public static function disconnect(): void
    {
        self::$instance = null;
    }
}
