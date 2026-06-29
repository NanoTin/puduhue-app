<?php

require_once dirname(__DIR__) . '/Config/Env.php';

class FinnegansClient
{
    private const TOKEN_TTL_SECONDS = 240;

    public function __construct(private \Database $db)
    {
    }

    public function obtenerTokenVigente(int $usuarioId, ?string $disp, ?string $ip): string
    {
        $rows = $this->db->select('SELECT access_token, generado FROM erptokenactivo ORDER BY generado DESC LIMIT 1');
        $row = $rows[0] ?? null;
        if ($row && !$this->tokenExpirado($row['generado'] ?? null)) {
            return (string)$row['access_token'];
        }

        return $this->refrescarToken($usuarioId, $disp, $ip);
    }

    public function refrescarToken(int $usuarioId, ?string $disp, ?string $ip): string
    {
        $tokenData = $this->solicitarTokenApi();
        $payload = [
            'access_token' => $tokenData['token'],
            'generado' => $tokenData['generado'],
        ];
        $this->db->callSpMaint('sp_erptokenactivo_insertar', $payload, $usuarioId, $disp, $ip);
        return $tokenData['token'];
    }

    public function postJsonWithToken(string $apiUrl, string $token, array $payload): array
    {
        $this->assertDevelopmentEmpresa($payload);

        $url = $this->addAccessTokenToUrl($apiUrl, $token);

        $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            throw new \RuntimeException('No se pudo conectar con Finnegans: ' . ($curlError ?: 'error desconocido'));
        }

        return [
            'httpCode' => $httpCode,
            'decoded' => json_decode($response, true),
            'raw' => $response,
        ];
    }

    public function getJsonWithToken(string $apiUrl, string $token): array
    {
        $url = $this->addAccessTokenToUrl($apiUrl, $token);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            throw new \RuntimeException('No se pudo conectar con Finnegans: ' . ($curlError ?: 'error desconocido'));
        }

        return [
            'httpCode' => $httpCode,
            'decoded' => json_decode($response, true),
            'raw' => $response,
        ];
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->getEnvRequired(
            ['ERP_API_BASE_URL', 'erp_api_base_url', 'ERP_API_URL', 'erp_api_url'],
            'URL base ERP'
        ), '/');
    }

    public function buildUrlFromBaseAndResource(string $baseUrl, string $resource): string
    {
        $resource = trim($resource);
        if ($resource === '') {
            throw new \InvalidArgumentException('El recurso ERP es obligatorio.');
        }
        if (preg_match('/^https?:\/\//i', $resource) === 1) {
            return $resource;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($resource, '/');
    }

    public function esTokenInvalido(?array $decoded, int $httpCode): bool
    {
        if (!is_array($decoded)) {
            return false;
        }
        if ($httpCode === 400) {
            $error = strtolower((string)($decoded['error'] ?? ''));
            return $error === 'invalid token' || str_contains($error, 'invalid token');
        }
        return false;
    }

    public function getEnvRequired(array $keys, string $label): string
    {
        foreach ($keys as $key) {
            $value = \Env::get($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        throw new \RuntimeException("Falta configuracion de {$label} en .env");
    }

    private function solicitarTokenApi(): array
    {
        $grantType = $this->getEnvRequired(['ERP_GRANT_TYPE', 'grant_type'], 'grant_type');
        $clientId = $this->getEnvRequired(['ERP_CLIENT_ID', 'client_id'], 'client_id');
        $clientSecret = $this->getEnvRequired(['ERP_CLIENT_SECRET', 'client_secret'], 'client_secret');
        $authUrl = $this->getEnvRequired(['ERP_AUTH_URL', 'erp_auth_url'], 'URL de autenticacion ERP');

        $query = http_build_query([
            'grant_type' => $grantType,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ], '', '&', PHP_QUERY_RFC3986);

        $url = rtrim($authUrl, '?');
        $url .= (str_contains($url, '?') ? '&' : '?') . $query;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            throw new \RuntimeException('No se pudo obtener token ERP: ' . ($curlError ?: 'error de conexion'));
        }

        $token = trim($response, " \t\n\r\0\x0B\"");
        if ($httpCode < 200 || $httpCode >= 300 || $token === '') {
            throw new \RuntimeException('Token ERP invalido o respuesta inesperada.');
        }

        return [
            'token' => $token,
            'generado' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    private function addAccessTokenToUrl(string $apiUrl, string $token): string
    {
        $url = rtrim($apiUrl, '?&');
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'ACCESS_TOKEN=' . rawurlencode($token);
    }

    private function tokenExpirado(?string $generado): bool
    {
        if (empty($generado)) {
            return true;
        }
        try {
            $dt = new \DateTimeImmutable($generado);
        } catch (\Throwable $e) {
            return true;
        }
        $now = new \DateTimeImmutable();
        return ($now->getTimestamp() - $dt->getTimestamp()) >= self::TOKEN_TTL_SECONDS;
    }

    private function assertDevelopmentEmpresa(array $payload): void
    {
        $appEnv = strtolower(trim((string)\Env::get('APP_ENV', 'production')));
        if ($appEnv !== 'development') {
            return;
        }

        $empresaId = (string)($payload['EmpresaID'] ?? '');
        $expected = (string)\Env::get('ERP_DEV_EMPRESA_IDERP', 'PRUEBA39');
        if ($empresaId !== $expected) {
            throw new \RuntimeException("APP_ENV=development solo permite sincronizar contra empresaiderp {$expected}.");
        }
    }
}
