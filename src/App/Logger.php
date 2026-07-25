<?php

namespace App;

use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logger
{
    private static ?MonologLogger $instance = null;

    public static function init(string $channel = 'app', ?string $logDir = null): MonologLogger
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $logDir ??= dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );

        $logger = new MonologLogger($channel);

        $rotating = new RotatingFileHandler($logDir . '/app.log', 30, Level::Debug);
        $rotating->setFormatter($formatter);
        $logger->pushHandler($rotating);

        $errorHandler = new StreamHandler($logDir . '/errors.log', Level::Warning);
        $errorHandler->setFormatter($formatter);
        $logger->pushHandler($errorHandler);

        if (defined('APP_ENV') && APP_ENV === 'development') {
            $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
        }

        self::$instance = $logger;
        return $logger;
    }

    public static function instance(): ?MonologLogger
    {
        return self::$instance;
    }
}

function app_log(): MonologLogger
{
    return Logger::instance() ?? Logger::init();
}
