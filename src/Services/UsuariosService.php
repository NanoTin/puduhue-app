<?php

/**
 * UsuariosService
 *
 * Coordina llamadas a SP de usuarios y preserva campos obligatorios.
 */
class UsuariosService
{
    private \Database $db;
    private const DEFAULT_API_TOKEN_DAYS = 30;
    private const MAX_API_TOKEN_DAYS = 3650;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/Config/Database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $this->db = \Database::getInstance();
    }

    /**
     * Listado de usuarios (consulta via SP).
     */
    public function listarUsuarios(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroUsuariorut'       => $filtros['filtroUsuariorut'] ?? null,
            'filtroUsuarionombre'    => $filtros['filtroUsuarionombre'] ?? null,
            'filtroUsuarioemail'     => $filtros['filtroUsuarioemail'] ?? null,
            'filtroPerfilid'         => $filtros['filtroPerfilid'] ?? null,
            'filtroUsuarioesadmin'   => $filtros['filtroUsuarioesadmin'] ?? null,
            'filtroUsuariobloqueado' => $filtros['filtroUsuariobloqueado'] ?? null,
            'filtroUsuarioactivo'    => $filtros['filtroUsuarioactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_usuarios_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarUsuariosFormSelect(?string $activoFilter = null): array
    {
        $sql = "SELECT usuarioid, usuarionombre FROM usuarios";
        $params = [];
        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $sql .= " WHERE usuarioactivo = ?";
            $params[] = (int)$activoFilter;
        }
        $sql .= " ORDER BY usuarionombre ASC";

        return $this->db->select($sql, $params);
    }  

    /**
     * Crear usuario.
     */
    public function crearUsuario(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['usuarioid']);

        $payload = $this->buildPayload($data, null, $ip);

        return $this->db->callSpMaint(
            'sp_usuarios_insertar',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );
    }

    /**
     * Editar usuario (incluye PK).
     */
    public function editarUsuario(int $usuarioId, array $data, int $actorId, ?string $disp, ?string $ip): array
    {
        $existing = $this->getUsuarioCompleto($usuarioId);
        if (!$existing) {
            throw new \RuntimeException('Usuario no encontrado.');
        }

        $payload = $this->buildPayload($data, $existing, $ip);
        $payload['usuarioid'] = $usuarioId;

        return $this->db->callSpMaint(
            'sp_usuarios_editar',
            $payload,
            $actorId,
            $disp,
            $ip
        );
    }

    /**
     * Anular usuario (baja logica).
     */
    public function anularUsuario(int $usuarioId, int $actorId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['usuarioid' => $usuarioId];

        return $this->db->callSpMaint(
            'sp_usuarios_anular',
            $dataJson,
            $actorId,
            $disp,
            $ip
        );
    }

    public function cambiarClave(int $usuarioId, string $plainPassword, array $context): array
    {
        if ($usuarioId <= 0) {
            throw new \RuntimeException('Usuario invalido.');
        }

        $this->assertPasswordPolicy($plainPassword);
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $payload = [
            'usuarioid' => $usuarioId,
            'usuariopwdhash' => $hash,
        ];

        $actorId = (int)($context['usuarioId'] ?? 0);
        if ($actorId <= 0) {
            throw new \RuntimeException('Sesion invalida.');
        }

        return $this->db->callSpMaint(
            'sp_usuarios_cambio_clave',
            $payload,
            $actorId,
            $context['dispositivo'] ?? null,
            $context['ip'] ?? null
        );
    }

    public function generarTokenApi(
        int $usuarioId,
        array $data,
        int $actorId,
        ?string $disp,
        ?string $ip
    ): array {
        if ($usuarioId <= 0) {
            throw new \RuntimeException('Usuario invalido.');
        }
        if ($actorId <= 0) {
            throw new \RuntimeException('Sesion invalida.');
        }

        $usuario = $this->getUsuarioCompleto($usuarioId);
        if (!$usuario) {
            throw new \RuntimeException('Usuario no encontrado.');
        }
        if (empty($usuario['usuarioactivo'])) {
            throw new \RuntimeException('El usuario seleccionado esta inactivo.');
        }

        $tokenNombre = trim((string)($data['tokennombre'] ?? ''));
        if ($tokenNombre === '') {
            throw new \RuntimeException('Debe ingresar un nombre para el token.');
        }
        if (strlen($tokenNombre) > 150) {
            throw new \RuntimeException('El nombre del token excede el largo permitido.');
        }

        $observacion = trim((string)($data['observacion'] ?? ''));
        if (strlen($observacion) > 255) {
            throw new \RuntimeException('La observacion excede el largo permitido.');
        }

        $sinExpiracion = !empty($data['sin_expiracion']);
        $diasVigencia = (int)($data['dias_vigencia'] ?? self::DEFAULT_API_TOKEN_DAYS);
        if (!$sinExpiracion) {
            if ($diasVigencia <= 0) {
                throw new \RuntimeException('La cantidad de dias debe ser mayor a cero.');
            }
            if ($diasVigencia > self::MAX_API_TOKEN_DAYS) {
                throw new \RuntimeException('La cantidad de dias excede el maximo permitido.');
            }
        }

        $plainToken = $this->buildApiTokenPlainValue();
        $tokenPrefix = substr($plainToken, 0, 12);
        $tokenHash = $this->hashApiToken($plainToken);
        $fechaExpira = $sinExpiracion
            ? null
            : date('Y-m-d H:i:s', strtotime('+' . $diasVigencia . ' days'));

        $sql = "
            INSERT INTO usuariosapitokens (
                usuarioid,
                tokennombre,
                tokenhash,
                tokenprefijo,
                tokenactiva,
                tokenfechaexpira,
                tokenultuso,
                tokenipultuso,
                observacion,
                auditcreacionusuarioid,
                auditcreaciondispositivo,
                auditcreacionip
            ) VALUES (
                :usuarioid,
                :tokennombre,
                :tokenhash,
                :tokenprefijo,
                1,
                :tokenfechaexpira,
                NULL,
                NULL,
                :observacion,
                :auditcreacionusuarioid,
                :auditcreaciondispositivo,
                :auditcreacionip
            )
        ";

        $this->db->execute($sql, [
            ':usuarioid' => $usuarioId,
            ':tokennombre' => $tokenNombre,
            ':tokenhash' => $tokenHash,
            ':tokenprefijo' => $tokenPrefix,
            ':tokenfechaexpira' => $fechaExpira,
            ':observacion' => $observacion !== '' ? $observacion : null,
            ':auditcreacionusuarioid' => $actorId,
            ':auditcreaciondispositivo' => $disp ?? '',
            ':auditcreacionip' => $ip ?? '',
        ]);

        return [
            'usuarioid' => $usuarioId,
            'usuarionombre' => $usuario['usuarionombre'] ?? '',
            'tokennombre' => $tokenNombre,
            'token' => $plainToken,
            'tokenprefijo' => $tokenPrefix,
            'tokenfechaexpira' => $fechaExpira,
            'sin_expiracion' => $sinExpiracion,
        ];
    }

    /**
     * Consultar por ID usando el SP de listado.
     */
    public function consultarUsuarioPorId(int $usuarioId, int $actorId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_usuarios_listar',
            [],
            $actorId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($usuarioId) {
            return (int)($row['usuarioid'] ?? 0) === $usuarioId;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }

    /**
     * Construye el payload esperado por los SP.
     * $existing incluye fila completa de BD para conservar hash/API key si no se cambia.
     */
    private function buildPayload(array $data, ?array $existing, ?string $ip): array
    {
        $now = date('Y-m-d H:i:s');

        $password = $data['usuariopwd'] ?? null;
        $passwordHash = null;
        if ($password !== null && $password !== '') {
            $this->assertPasswordPolicy($password);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        } elseif ($existing) {
            $passwordHash = $existing['usuariopwdhash'] ?? null;
        }

        if (!$passwordHash) {
            throw new \RuntimeException('La contrasena es requerida.');
        }

        $rutNormalized = null;
        if (!empty($data['usuariorut'])) {
            $rutNormalized = $this->normalizeRutOrFail($data['usuariorut']);
        } elseif ($existing && !empty($existing['usuariorut'])) {
            $rutNormalized = $this->normalizeRutOrFail($existing['usuariorut']);
        }

        if (!$rutNormalized) {
            throw new \RuntimeException('El RUT es requerido y debe tener formato XXXXXXXX-V.');
        }

        $autorizaFuera = $this->normalizeBoolInt($data['usuarioreqautorizadorfuerapptocompra'] ?? ($existing['usuarioreqautorizadorfuerapptocompra'] ?? 0));
        $autorizaOrden = $this->normalizeNullableInt($data['usuarioreqautorizadorfuerapptocompraorden'] ?? ($existing['usuarioreqautorizadorfuerapptocompraorden'] ?? 0)) ?? 0;

        if ($autorizaFuera === 1 && $autorizaOrden <= 0) {
            throw new \RuntimeException('Debe informar un orden mayor a cero para autorizador fuera de presupuesto.');
        }

        if ($autorizaFuera === 0) {
            $autorizaOrden = 0;
        }

        $apiHash = $existing['usuarioapikeyhash'] ?? password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $apiActiva = $existing['usuarioapikeyactiva'] ?? 1;
        $apiFechaGen = $existing['usuarioapikeyfechagen'] ?? $now;
        $apiUltUso = $existing['usuarioapikeyultuso'] ?? $now;
        $apiIpUltUso = $existing['usuarioapikeyipultuso'] ?? ($ip ?? '');

        return [
            'usuariocod'            => $rutNormalized,
            'usuariorut'            => $rutNormalized,
            'usuarionombre'         => $data['usuarionombre'] ?? ($existing['usuarionombre'] ?? null),
            'usuariopwdhash'        => $passwordHash,
            'usuarioemail'          => $data['usuarioemail'] ?? ($existing['usuarioemail'] ?? null),
            'usuariocelular'        => $data['usuariocelular'] ?? ($existing['usuariocelular'] ?? null),
            'perfilid'              => (int)($data['perfilid'] ?? ($existing['perfilid'] ?? 0)),
            'empresaiddefault'      => (int)($data['empresaiddefault'] ?? ($existing['empresaiddefault'] ?? 0)),
            'usuarioesroot'         => !empty($data['usuarioesroot']) ? 1 : 0,
            'usuarioesadmin'        => !empty($data['usuarioesadmin']) ? 1 : 0,
            'usuariobloqueado'      => !empty($data['usuariobloqueado']) ? 1 : 0,
            'usuariobloqueadodesc'  => $data['usuariobloqueadodesc'] ?? ($existing['usuariobloqueadodesc'] ?? ''),
            'usuarioapikeyhash'     => $apiHash,
            'usuarioapikeyactiva'   => $apiActiva,
            'usuarioapikeyfechagen' => $apiFechaGen,
            'usuarioapikeyultuso'   => $apiUltUso,
            'usuarioapikeyipultuso' => $apiIpUltUso,
            'usuarioactivo'         => !empty($data['usuarioactivo']) ? 1 : 0,
            'usuariopermiteaprobreq' => $this->normalizeBoolInt($data['usuariopermiteaprobreq'] ?? ($existing['usuariopermiteaprobreq'] ?? 0)),
            'usuariopermiteaprobpreoc' => $this->normalizeBoolInt($data['usuariopermiteaprobpreoc'] ?? ($existing['usuariopermiteaprobpreoc'] ?? 0)),
            'usuariocomprador' => $this->normalizeBoolInt($data['usuariocomprador'] ?? ($existing['usuariocomprador'] ?? 0)),
            'usuariopermiteanularpreoc' => $this->normalizeBoolInt($data['usuariopermiteanularpreoc'] ?? ($existing['usuariopermiteanularpreoc'] ?? 0)),
            'usuariopermiteeditarprecios' => $this->normalizeBoolInt($data['usuariopermiteeditarprecios'] ?? ($existing['usuariopermiteeditarprecios'] ?? 0)),
            'usuariopermitecrearitem' => $this->normalizeBoolInt($data['usuariopermitecrearitem'] ?? ($existing['usuariopermitecrearitem'] ?? 0)),
            'usuariopermiteeditaritem' => $this->normalizeBoolInt($data['usuariopermiteeditaritem'] ?? ($existing['usuariopermiteeditaritem'] ?? 0)),
            'usuariopermitesynctrnerp' => $this->normalizeBoolInt($data['usuariopermitesynctrnerp'] ?? ($existing['usuariopermitesynctrnerp'] ?? 0)),
            'usuarioreqautorizadorfuerapptocompra' => $autorizaFuera,
            'usuarioreqautorizadorfuerapptocompraorden' => $autorizaOrden,
        ];
    }

    /**
     * Obtiene la fila completa del usuario (para conservar hashes/flags).
     */
    private function getUsuarioCompleto(int $usuarioId): array
    {
        $sql = 'SELECT * FROM usuarios WHERE usuarioid = :id LIMIT 1';
        $rows = $this->db->select($sql, [':id' => $usuarioId]);
        return $rows[0] ?? [];
    }

    /**
     * Expone la normalizacion/validacion de RUT para ser reutilizada fuera del CRUD (ej. login).
     */
    public function normalizarRutParaLogin(string $rut): string
    {
        return $this->normalizeRutOrFail($rut);
    }

    /**
     * Normaliza y valida RUT chileno (XXXXXXXX-V, sin puntos, DV correcto).
     */
    private function normalizeRutOrFail(string $rut): string
    {
        $clean = strtoupper(trim($rut));
        $clean = preg_replace('/[^0-9K]/i', '', $clean ?? '');

        if (!preg_match('/^([0-9]{1,8})([0-9K])$/', $clean, $matches)) {
            throw new \RuntimeException('Formato de RUT invalido. Use XXXXXXXX-V sin puntos.');
        }

        $body = $matches[1];
        $dv = $matches[2];

        if (!$this->isValidRutDigits($body, $dv)) {
            throw new \RuntimeException('Digito verificador de RUT invalido.');
        }

        return $body . '-' . $dv;
    }

    private function isValidRutDigits(string $body, string $dv): bool
    {
        $sum = 0;
        $multiplier = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += ((int)$body[$i]) * $multiplier;
            $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
        }
        $remainder = 11 - ($sum % 11);
        $expected = $remainder === 11 ? '0' : ($remainder === 10 ? 'K' : (string)$remainder);

        return strtoupper($dv) === $expected;
    }

    /**
     * Verifica complejidad de contrasenas segun README.
     */
    private function assertPasswordPolicy(string $password): void
    {
        $hasLength = strlen($password) >= 5;
        $hasUpper = (bool)preg_match('/[A-Z]/', $password);
        $hasNumber = (bool)preg_match('/[0-9]/', $password);
        $hasSpecial = (bool)preg_match('/[^A-Za-z0-9]/', $password);

        if (!($hasLength && $hasUpper && $hasNumber && $hasSpecial)) {
            throw new \RuntimeException('La contrasena no cumple la politica (min 5, al menos 1 mayuscula, 1 numero y 1 caracter especial).');
        }
    }

    private function normalizeBoolInt($value): int
    {
        return !empty($value) ? 1 : 0;
    }

    private function normalizeNullableInt($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int)$value;
    }

    private function buildApiTokenPlainValue(): string
    {
        return 'pudu_' . bin2hex(random_bytes(32));
    }

    private function hashApiToken(string $plainToken): string
    {
        $secret = '';
        if (class_exists('Env')) {
            $secret = (string)\Env::get('API_TOKEN_SECRET', '');
            if ($secret === '') {
                $secret = (string)\Env::get('APP_KEY', '');
            }
            if ($secret === '') {
                $secret = (string)\Env::get('DB_PASS', '');
            }
        }

        if ($secret === '') {
            throw new \RuntimeException('No existe una clave configurada para firmar tokens API.');
        }

        return hash_hmac('sha256', $plainToken, $secret);
    }
}
