<?php

/**
 * Simple environment loader for Puduhue App.
 *
 * Loads key/value pairs from .env into memory and allows
 * fetching values using Env::get().
 */

class Env
{
    private static bool $loaded = false;
    private static array $vars = [];

    /**
     * Loads the .env file once.
     */
    public static function load(string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        $path = $path ?? dirname(__DIR__, 2) . '/.env';

        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) {
                    continue;
                }
                if (!str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                self::$vars[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Fetch an environment variable.
     */
    public static function get(string $key, string $default = ''): string
    {
        if (!self::$loaded) {
            self::load();
        }

        if (array_key_exists($key, self::$vars)) {
            return self::$vars[$key];
        }

        $value = getenv($key); // fallback
        return $value !== false ? $value : $default;
    }
}
