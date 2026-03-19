<?php

class ApiRequest
{
    public static function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return is_string($path) ? $path : '/';
    }

    public static function getHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                $authorization = self::getAuthorizationHeaderFromServer();
                if ($authorization !== '' && !self::hasHeader($headers, 'Authorization')) {
                    $headers['Authorization'] = $authorization;
                }
                return $headers;
            }
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strncmp($key, 'HTTP_', 5) !== 0) {
                continue;
            }
            $headerName = str_replace('_', '-', strtolower(substr($key, 5)));
            $headerName = implode('-', array_map('ucfirst', explode('-', $headerName)));
            $headers[$headerName] = $value;
        }

        $authorization = self::getAuthorizationHeaderFromServer();
        if ($authorization !== '' && !self::hasHeader($headers, 'Authorization')) {
            $headers['Authorization'] = $authorization;
        }

        return $headers;
    }

    public static function getBearerToken(array $headers): string
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'Authorization') !== 0) {
                continue;
            }
            if (!preg_match('/^\s*Bearer\s+(.+)\s*$/i', (string)$value, $matches)) {
                throw new ApiException('Header Authorization invalido.', 401);
            }
            return trim($matches[1]);
        }

        throw new ApiException('Header Authorization requerido.', 401);
    }

    public static function getClientIp(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '');
    }

    public static function getUserAgent(): string
    {
        return (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new ApiException('Body JSON invalido.', 400);
        }

        return $decoded;
    }

    public static function sanitizeHeadersForLog(array $headers): array
    {
        $sanitized = [];
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, 'Authorization') === 0) {
                $sanitized[$name] = 'Bearer ***';
                continue;
            }
            $sanitized[$name] = $value;
        }
        return $sanitized;
    }

    private static function getAuthorizationHeaderFromServer(): string
    {
        $candidates = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? '',
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
            $_SERVER['Authorization'] ?? '',
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private static function hasHeader(array $headers, string $headerName): bool
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp((string)$name, $headerName) === 0) {
                return true;
            }
        }

        return false;
    }
}
