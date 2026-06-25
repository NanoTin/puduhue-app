<?php

require_once dirname(__DIR__) . '/Helpers/Logger.php';

use App\Helpers\Logger;

/**
 * Servicios auxiliares de autenticacion reutilizables desde login.php.
 */
class AuthService
{
    /**
     * Normaliza el usuario (RUT) permitiendo ROOT como excepcion.
     */
    public function normalizeUsernameInput(string $username, \UsuariosService $usuariosService): string
    {
        $trimmed = trim($username);
        if ($trimmed === '') {
            throw new RuntimeException('Debe ingresar usuario y contrasena.');
        }

        if (strtoupper($trimmed) === 'ROOT') {
            return 'ROOT';
        }

        return $usuariosService->normalizarRutParaLogin($trimmed);
    }

    /**
     * Control simple de tasa por IP en una ventana de tiempo.
     */
    public function checkAndRegisterRateLimit(string $ip, int $limit, int $windowSeconds): bool
    {
        $now = time();
        $data = $_SESSION['login_rate'] ?? ['ip' => $ip, 'count' => 0, 'start' => $now];

        if (($data['ip'] ?? '') !== $ip || ($now - ($data['start'] ?? 0)) > $windowSeconds) {
            $data = ['ip' => $ip, 'count' => 0, 'start' => $now];
        }

        if (($data['count'] ?? 0) >= $limit) {
            $_SESSION['login_rate'] = $data;
            return false;
        }

        $data['count'] = ($data['count'] ?? 0) + 1;
        $_SESSION['login_rate'] = $data;
        return true;
    }

    /**
     * Valida token de reCAPTCHA Enterprise contra Google.
     */
    public function verifyRecaptchaToken(string $token, array $config): void
    {
        $apiKey = $config['apiKey'] ?? '';
        $projectId = $config['projectId'] ?? '';
        $siteKey = $config['siteKey'] ?? '';
        $minScore = (float)($config['minScore'] ?? 0.5);

        if ($token === '') {
            throw new RuntimeException('Debe completar la verificacion de reCAPTCHA.');
        }
        if ($apiKey === '' || $projectId === '' || $siteKey === '') {
            throw new RuntimeException('Configuracion de reCAPTCHA incompleta.');
        }

        $endpoint = sprintf(
            'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments?key=%s',
            rawurlencode($projectId),
            rawurlencode($apiKey)
        );

        $payload = json_encode([
            'event' => [
                'token' => $token,
                'siteKey' => $siteKey,
                'expectedAction' => 'LOGIN',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($response === false) {
            throw new RuntimeException('No se pudo validar reCAPTCHA: ' . ($curlError ?: 'error de conexion'));
        }

        $decoded = json_decode($response, true);
        $isValid = (bool)($decoded['tokenProperties']['valid'] ?? false);
        $action = $decoded['tokenProperties']['action'] ?? '';
        $score = (float)($decoded['riskAnalysis']['score'] ?? 0);

        if ($httpCode !== 200 || !$isValid) {
            throw new RuntimeException('reCAPTCHA invalido o expirado.');
        }
        if ($action !== 'LOGIN' || $score < $minScore) {
            throw new RuntimeException('reCAPTCHA no aprobado. Intente nuevamente.');
        }
    }

    /**
     * Obtiene los datos del usuario via SP sp_usuario_login_obtenerdatos.
     */
    public function fetchUserRow(\PDO $pdo, string $usuarioCod): array
    {
        $stmt = $pdo->prepare('CALL sp_usuario_login_obtenerdatos(?)');
        $stmt->execute([$usuarioCod]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        while ($stmt->nextRowset()) {
            // consumir
        }
        $stmt->closeCursor();
        return $row;
    }

    /**
     * Registra intento de login exitoso/fallido probando alias alternativos.
     */
    public function registerLoginAttempt(\PDO $pdo, string $usuarioCod, string $usuarioip, string $usuariodispositivo, bool $success): void
    {
        $procedures = $success
            ? ['sp_usuario_login_exitoso', 'sp_user_login_successful']
            : ['sp_usuario_login_fallido', 'sp_user_login_failed'];

        try {
            $availableProcedures = $this->filterExistingProcedures($pdo, $procedures);
            if (empty($availableProcedures)) {
                return;
            }

            $this->callLoginProcedure(
                $pdo,
                $availableProcedures,
                $usuarioCod,
                $usuarioip,
                $this->limitText($usuariodispositivo, 50)
            );
        } catch (\Throwable $e) {
            Logger::error('No se pudo registrar intento de login: ' . $e->getMessage());
        }
    }

    /**
     * Genera JWT HS256 sin dependencias externas (opcional para API).
     */
    public function generateJwtToken(array $claims, string $secret, string $algorithm): string
    {
        $algo = strtoupper($algorithm);
        if ($algo !== 'HS256') {
            throw new RuntimeException('Algoritmo JWT no soportado.');
        }

        $header = ['alg' => $algo, 'typ' => 'JWT'];
        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function callLoginProcedure(\PDO $pdo, array $spNames, string $usuarioCod, string $usuarioip, string $usuariodispositivo): void
    {
        $lastException = null;
        foreach ($spNames as $spName) {
            try {
                $stmt = $pdo->prepare("CALL {$spName}(?,?,?)");
                $stmt->execute([$usuarioCod, $usuarioip, $usuariodispositivo]);
                while ($stmt->nextRowset()) {
                    // consume extra sets
                }
                $stmt->closeCursor();
                return;
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }
    }

    private function filterExistingProcedures(\PDO $pdo, array $spNames): array
    {
        if (empty($spNames)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($spNames), '?'));
        $stmt = $pdo->prepare("
            SELECT ROUTINE_NAME
            FROM INFORMATION_SCHEMA.ROUTINES
            WHERE ROUTINE_SCHEMA = DATABASE()
              AND ROUTINE_TYPE = 'PROCEDURE'
              AND ROUTINE_NAME IN ({$placeholders})
        ");
        $stmt->execute($spNames);
        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $stmt->closeCursor();

        return array_values(array_filter($spNames, static function (string $spName) use ($rows): bool {
            return in_array($spName, $rows, true);
        }));
    }

    private function limitText(?string $text, int $max): string
    {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $max);
        }

        return substr($text, 0, $max);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
