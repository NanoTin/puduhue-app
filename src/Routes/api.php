<?php

use App\Helpers\Logger;

function handleApiRequest(): void
{
    $requestId = apiGenerateRequestId();
    $startedAt = microtime(true);
    $headers = ApiRequest::getHeaders();
    $sanitizedHeaders = ApiRequest::sanitizeHeadersForLog($headers);
    $ip = ApiRequest::getClientIp();
    $userAgent = ApiRequest::getUserAgent();
    $path = ApiRequest::getPath();
    $method = ApiRequest::getMethod();
    $version = 'v1';
    $resource = '';
    $payload = null;
    $authContext = [
        'usuarioid' => null,
        'usuarioapitokenid' => null,
    ];
    $statusCode = 500;
    $message = 'Error interno del servidor';
    $data = [];
    $meta = ['request_id' => $requestId];
    $errors = [];

    try {
        $route = apiTryResolveRoute($path);
        if ($route !== null) {
            $version = $route['version'];
            $resource = $route['resource'];
            $action = $route['action'];
        }

        if ($method !== 'POST') {
            throw new ApiException('Metodo HTTP no permitido.', 405);
        }

        if ($route === null) {
            $route = apiResolveRoute($path);
            $version = $route['version'];
            $resource = $route['resource'];
            $action = $route['action'];
        }

        $version = $route['version'];
        $resource = $route['resource'];
        $action = $route['action'];

        if ($action !== 'query') {
            throw new ApiException('Accion no soportada.', 404);
        }

        $payload = ApiRequest::getJsonBody();

        $auth = new ApiBearerAuthMiddleware();
        $authContext = $auth->authenticate($headers);
        $auth->authorize($authContext, $resource, $action);

        [$controller, $methodName] = apiResolveController($resource, $action);
        $response = $controller->$methodName($payload, [
            'request_id' => $requestId,
            'auth' => $authContext,
        ]);

        $auth->markTokenUsage((int)$authContext['usuarioapitokenid'], $ip);

        $statusCode = (int)($response['status'] ?? 200);
        $message = (string)($response['message'] ?? 'OK');
        $data = (array)($response['data'] ?? []);
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : ['request_id' => $requestId];
        $meta['request_id'] = $requestId;
        $meta['execution_ms'] = (int)round((microtime(true) - $startedAt) * 1000);
    } catch (ApiException $e) {
        $statusCode = $e->getStatusCode();
        $message = $e->getMessage();
        $errors = $e->getErrors();
        $meta['execution_ms'] = (int)round((microtime(true) - $startedAt) * 1000);
    } catch (\Throwable $e) {
        Logger::error('API request failed: ' . $e->getMessage());
        $statusCode = 500;
        $message = 'Error interno del servidor';
        $meta['execution_ms'] = (int)round((microtime(true) - $startedAt) * 1000);
    }

    try {
        $logService = new ApiRequestLogService();
        $logService->log([
            'requestid' => $requestId,
            'usuarioid' => $authContext['usuarioid'] ?? null,
            'usuarioapitokenid' => $authContext['usuarioapitokenid'] ?? null,
            'apiversion' => $version,
            'recurso' => $resource,
            'metodohttp' => $method,
            'endpoint' => $path,
            'iporigen' => $ip,
            'useragent' => $userAgent,
            'requestheadersjson' => $sanitizedHeaders,
            'requestbodyjson' => $payload,
            'responsecode' => $statusCode,
            'responsetimems' => (int)($meta['execution_ms'] ?? 0),
        ]);
    } catch (\Throwable $e) {
        Logger::error('API request log failed: ' . $e->getMessage());
    }

    ApiResponse::send($statusCode, $message, $data, $meta, $errors);
}

function apiTryResolveRoute(string $path): ?array
{
    try {
        return apiResolveRoute($path);
    } catch (ApiException $e) {
        return null;
    }
}

function apiResolveRoute(string $path): array
{
    $normalizedPath = trim($path, '/');
    $segments = $normalizedPath === '' ? [] : explode('/', $normalizedPath);

    if (($segments[0] ?? '') === 'api') {
        $version = $segments[1] ?? '';
        $resource = $segments[2] ?? '';
        $action = $segments[3] ?? '';
    } else {
        $version = $segments[0] ?? '';
        $resource = $segments[1] ?? '';
        $action = $segments[2] ?? '';
    }

    if ($version !== 'v1' || $resource === '' || $action === '') {
        throw new ApiException('Ruta API no encontrada.', 404);
    }

    return [
        'version' => $version,
        'resource' => $resource,
        'action' => $action,
    ];
}

function apiResolveController(string $resource, string $action): array
{
    $map = [
        'prodleche-detalle' => [new ProdlecheDetalleController(), 'query'],
        'suplanimal-detalle' => [new SuplanimalDetalleController(), 'query'],
    ];

    if (!isset($map[$resource])) {
        throw new ApiException('Recurso no encontrado.', 404);
    }

    return $map[$resource];
}

function apiGenerateRequestId(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);

    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}
