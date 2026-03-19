<?php

namespace App\Helpers;

/**
 * Simple file-based logger for Puduhue App.
 *
 * Writes to storage/LOGS/log_YYYYMMDD.log and falls back to error_log.
 */
class Logger
{
    private static function log(string $level, string $message): void
    {
        $root = dirname(__DIR__, 2);
        $logDir = $root . '/storage/LOGS';
        $logFile = $logDir . '/log_' . date('Ymd') . '.log';

        $line = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message
        );

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        if (is_writable(dirname($logFile)) || (!file_exists($logFile) && is_writable($logDir))) {
            @file_put_contents($logFile, $line, FILE_APPEND);
        } else {
            error_log($line);
        }
    }

    public static function info(string $message): void
    {
        self::log('info', $message);
    }

    public static function error(string $message): void
    {
        self::log('error', $message);
    }
}
