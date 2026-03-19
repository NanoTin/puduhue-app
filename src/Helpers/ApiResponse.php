<?php

class ApiResponse
{
    public static function send(int $statusCode, string $message, array $data = [], ?array $meta = null, array $errors = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $payload = [
            'status' => $statusCode,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
