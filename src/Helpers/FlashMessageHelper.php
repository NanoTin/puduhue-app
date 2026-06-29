<?php

class FlashMessageHelper
{
    private const ALLOWED_TOAST_TYPES = ['success', 'danger', 'warning', 'info', 'primary', 'secondary', 'light', 'dark'];

    public static function toast(string $message, string $type = 'info'): void
    {
        self::ensureSession();

        $_SESSION['toast'] = [
            'message' => $message,
            'type' => self::normalizeToastType($type),
        ];
    }

    public static function pullToast(): ?array
    {
        self::ensureSession();

        $toast = $_SESSION['toast'] ?? null;
        unset($_SESSION['toast']);

        return is_array($toast) && !empty($toast['message']) ? $toast : null;
    }

    private static function normalizeToastType(string $type): string
    {
        return in_array($type, self::ALLOWED_TOAST_TYPES, true) ? $type : 'info';
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
