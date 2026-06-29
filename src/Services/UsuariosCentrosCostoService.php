<?php

class UsuariosCentrosCostoService
{
    private \Database $db;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/Config/Database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $this->db = \Database::getInstance();
    }

    public function listarAsignaciones(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroUsuarioid' => $this->normalizeInt($filtros['filtroUsuarioid'] ?? null),
            'filtroCentrocostoid' => $this->normalizeInt($filtros['filtroCentrocostoid'] ?? null),
            'filtroActivo' => $this->normalizeInt($filtros['filtroActivo'] ?? null),
        ];

        return $this->db->callSpQuery(
            'sp_usuarioscentroscosto_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarUsuariosFormSelect(?int $activo = 1): array
    {
        $sql = 'SELECT usuarioid, usuarionombre, usuariorut
                FROM usuarios
                WHERE usuariobloqueado = 0';
        $params = [];

        if ($activo === 0 || $activo === 1) {
            $sql .= ' AND usuarioactivo = ?';
            $params[] = $activo;
        }

        $sql .= ' ORDER BY usuarionombre ASC';

        return $this->db->select($sql, $params);
    }

    public function listarCentrosCostoFormSelect(?int $activo = 1): array
    {
        $sql = 'SELECT centrocostoid, centrocostocod, centrocostodsc
                FROM centroscosto';
        $params = [];

        if ($activo === 0 || $activo === 1) {
            $sql .= ' WHERE centrocostoactivo = ?';
            $params[] = $activo;
        }

        $sql .= ' ORDER BY centrocostodsc ASC';

        return $this->db->select($sql, $params);
    }

    public function crearAsignacion(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $payload = [
            'usuarioid' => $this->normalizeInt($data['usuarioid'] ?? null),
            'centrocostoid' => $this->normalizeInt($data['centrocostoid'] ?? null),
            'usucendefault' => $this->normalizeBoolInt($data['usucendefault'] ?? 0),
        ];

        return $this->db->callSpMaint(
            'sp_usuarioscentroscosto_insertar',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function actualizarAsignacion(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $payload = [
            'usucenid' => $this->normalizeInt($data['usucenid'] ?? null),
            'accion' => trim((string)($data['accion'] ?? '')),
        ];

        return $this->db->callSpMaint(
            'sp_usuarioscentroscosto_editar',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );
    }

    private function normalizeInt($value): ?int
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

    private function normalizeBoolInt($value): int
    {
        return !empty($value) ? 1 : 0;
    }
}
