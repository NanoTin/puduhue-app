<?php

/**
 * Env loader for Puduhue App.
 *
 * Reads .env key/value pairs into $_ENV and getenv() when available.
 * Lightweight to work on shared hosting without external dependencies.
 */
class Env
{
    /**
     * Load environment variables from .env located at project root.
     */
    public static function load(?string $path = null): void
    {
        $root = dirname(__DIR__, 2);
        $envPath = $path ?? ($root . '/.env');

        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = $parts;
            $key = trim($key);
            $value = trim($value);

            // Remove surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }

    /**
     * Get env value with optional default.
     */
    public static function get(string $key, $default = null)
    {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($val === false || $val === null) ? $default : $val;
    }
}
