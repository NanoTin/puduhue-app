<?php

class InvbodegasService
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

    public function listarInvbodegas(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroInvbodegadsc'    => $filtros['filtroInvbodegadsc'] ?? null,
            'filtroErpinvbodegacod' => $filtros['filtroErpinvbodegacod'] ?? null,
            'filtroFundoid'         => $filtros['filtroFundoid'] ?? null,
            'filtroInvbodactivo'    => $filtros['filtroInvbodactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_invbodegas_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarInvbodegasPorFundoFormSelect(?string $fundoId = null, ?string $activoFilter = null): array
    {
        $sql = "SELECT invbodegaid, invbodegadsc, fundoid, erpinvbodegacod FROM invbodegas WHERE 1=1";
        $params = [];

        if ($fundoId !== null) {
            $sql .= " AND fundoid = ?";
            $params[] = (int)$fundoId;
        }

        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $sql .= " AND invbodactivo = ?";
            $params[] = (int)$activoFilter;
        }

        $sql .= " ORDER BY invbodegadsc ASC";

        return $this->db->select($sql, $params);
    }

    public function crearInvbodega(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['invbodegaid']);

        return $this->db->callSpMaint(
            'sp_invbodegas_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarInvbodega(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['invbodegaid'] = $id;

        return $this->db->callSpMaint(
            'sp_invbodegas_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularInvbodega(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['invbodegaid' => $id];

        return $this->db->callSpMaint(
            'sp_invbodegas_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarInvbodegaPorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_invbodegas_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['invbodegaid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
