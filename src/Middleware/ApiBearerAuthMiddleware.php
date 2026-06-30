<?php

class ApiBearerAuthMiddleware
{
    private \Database $db;
    private ?bool $hasTokenScopesColumn = null;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function authenticate(array $headers): array
    {
        $plainToken = ApiRequest::getBearerToken($headers);
        if ($plainToken === '') {
            throw new ApiException('Token vacio.', 401);
        }

        $tokenHash = $this->hashToken($plainToken);
        $tokenScopesSelect = $this->hasTokenScopesColumn() ? 't.tokenpermisos,' : 'NULL AS tokenpermisos,';
        $sql = "
            SELECT
                t.usuarioapitokenid,
                t.usuarioid,
                t.tokenactiva,
                t.tokenfechaexpira,
                {$tokenScopesSelect}
                u.usuarioactivo,
                u.usuarionombre,
                u.usuariorut
            FROM usuariosapitokens t
            INNER JOIN usuarios u ON u.usuarioid = t.usuarioid
            WHERE t.tokenhash = :tokenhash
            LIMIT 1
        ";

        $rows = $this->db->select($sql, [':tokenhash' => $tokenHash]);
        $row = $rows[0] ?? null;
        if (!$row) {
            throw new ApiException('Token no valido o inexistente.', 401);
        }
        if (empty($row['tokenactiva'])) {
            throw new ApiException('Token no valido o inactivo.', 401);
        }
        if (!empty($row['tokenfechaexpira']) && strtotime((string)$row['tokenfechaexpira']) < time()) {
            throw new ApiException('Token expirado.', 401);
        }
        if (empty($row['usuarioactivo'])) {
            throw new ApiException('Usuario asociado inactivo.', 403);
        }

        return [
            'usuarioid' => (int)$row['usuarioid'],
            'usuarioapitokenid' => (int)$row['usuarioapitokenid'],
            'usuarionombre' => (string)($row['usuarionombre'] ?? ''),
            'usuariorut' => (string)($row['usuariorut'] ?? ''),
            'tokenpermisos' => (string)($row['tokenpermisos'] ?? ''),
            'plain_token' => $plainToken,
        ];
    }

    public function authorize(array $authContext, string $resource, string $action): void
    {
        $permission = $resource . ':' . $action;
        $rawScopes = trim((string)($authContext['tokenpermisos'] ?? ''));

        if ($rawScopes === '') {
            return;
        }

        $scopes = array_filter(array_map('trim', explode(',', $rawScopes)));
        if (in_array('*', $scopes, true) || in_array($permission, $scopes, true)) {
            return;
        }

        throw new ApiException('Token sin permiso para este recurso.', 403);
    }

    public function markTokenUsage(int $usuarioApiTokenId, string $ip): void
    {
        if ($usuarioApiTokenId <= 0) {
            return;
        }

        $sql = "
            UPDATE usuariosapitokens
            SET tokenultuso = NOW(),
                tokenipultuso = :ip
            WHERE usuarioapitokenid = :id
        ";

        $this->db->execute($sql, [
            ':ip' => $ip,
            ':id' => $usuarioApiTokenId,
        ]);
    }

    private function hashToken(string $plainToken): string
    {
        $secret = (string)Env::get('API_TOKEN_SECRET', '');
        if ($secret === '') {
            $secret = (string)Env::get('APP_KEY', '');
        }
        if ($secret === '') {
            $secret = (string)Env::get('DB_PASS', '');
        }
        if ($secret === '') {
            throw new ApiException('No existe configuracion de secreto para tokens API.', 500);
        }

        return hash_hmac('sha256', $plainToken, $secret);
    }

    private function hasTokenScopesColumn(): bool
    {
        if ($this->hasTokenScopesColumn !== null) {
            return $this->hasTokenScopesColumn;
        }

        try {
            $rows = $this->db->select("SHOW COLUMNS FROM usuariosapitokens LIKE 'tokenpermisos'");
            $this->hasTokenScopesColumn = !empty($rows);
        } catch (\Throwable $e) {
            $this->hasTokenScopesColumn = false;
        }

        return $this->hasTokenScopesColumn;
    }
}
