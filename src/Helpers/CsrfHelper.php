<?php

class CsrfHelper
{
    public static function generate(string $context = 'default'): string
    {
        self::ensureSession();

        if (empty($_SESSION['csrf_tokens'][$context])) {
            $_SESSION['csrf_tokens'][$context] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_tokens'][$context];
    }

    public static function validate(?string $token, string $context = 'default'): bool
    {
        self::ensureSession();

        $expected = $_SESSION['csrf_tokens'][$context] ?? '';
        return is_string($token) && $token !== '' && $expected !== '' && hash_equals($expected, $token);
    }

    public static function input(string $context = 'default'): string
    {
        $token = htmlspecialchars(self::generate($context), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function tokenFromRequest(): ?string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $headerToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (is_string($headerToken) && $headerToken !== '') {
            return $headerToken;
        }

        return isset($_POST['_csrf']) ? (string)$_POST['_csrf'] : null;
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
