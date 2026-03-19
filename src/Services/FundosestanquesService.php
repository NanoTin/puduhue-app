<?php

class FundosestanquesService
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

    public function listarFundosestanques(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroFundoid'             => $filtros['filtroFundoid'] ?? null,
            'filtroFundoestanquedsc'    => $filtros['filtroFundoestanquedsc'] ?? null,
            'filtroEstanquemarcaid'     => $filtros['filtroEstanquemarcaid'] ?? null,
            'filtroFundoestanqueactivo' => $filtros['filtroFundoestanqueactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_fundosestanques_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarFundosestanquesFormSelect(?string $activoFilter = null): array
    {
        $sql = "SELECT fundoestanqueid, fe.fundoid, CONCAT(fundonombre, ' (',fundoestanquedsc,')') as fundoestanquedsc FROM fundosestanques fe INNER JOIN fundos f ON fe.fundoid = f.fundoid";
        $params = [];
        if ($activoFilter !== null && ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1)) {
            $sql .= " WHERE fundoestanqueactivo = ?";
            $params[] = (int)$activoFilter;
        }
        $sql .= " ORDER BY fundoestanquedsc ASC";
        return $this->db->select($sql, $params);
    }   

    public function crearFundoestanque(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['fundoestanqueid']);

        return $this->db->callSpMaint(
            'sp_fundosestanques_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarFundoestanque(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['fundoestanqueid'] = $id;

        return $this->db->callSpMaint(
            'sp_fundosestanques_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularFundoestanque(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['fundoestanqueid' => $id];

        return $this->db->callSpMaint(
            'sp_fundosestanques_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarFundoestanquePorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_fundosestanques_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['fundoestanqueid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
