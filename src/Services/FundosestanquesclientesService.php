<?php

class FundosestanquesclientesService
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

    public function listarFundosestanquesclientes(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroClienteid'       => $filtros['filtroClienteid'] ?? null,
            'filtroFundoId'         => $filtros['filtroFundoId'] ?? null,
            'filtroFndestcliactivo' => $filtros['filtroFndestcliactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_fundosestanquesclientes_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarFundosestanquesclientesFormSelect(?string $activoFilter = null, ?string $fundoidFilter = null): array
    {
        $sql = "SELECT fc.fundoestanqueid, fc.clienteid, fc.estanqueclientecod, fe.fundoid, CONCAT(fc.estanqueclientecod, ' - ', clienterazonsocial) as fundoestanqueclientedsc FROM fundosestanquesclientes fc INNER JOIN fundosestanques fe ON fc.fundoestanqueid = fe.fundoestanqueid INNER JOIN fundos f ON fe.fundoid = f.fundoid INNER JOIN clientes c ON fc.clienteid = c.clienteid";
        $params = [];
        if ($activoFilter !== null && ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1)) {
            $sql .= " WHERE fndestcliactivo = ?";
            $params[] = (int)$activoFilter;
        }
        if ($fundoidFilter !== null) {
            $sql .= (empty($params) ? " WHERE " : " AND ") . " fe.fundoid = ?";
            $params[] = (int)$fundoidFilter;
        }
        $sql .= " ORDER BY fundoestanqueclientedsc ASC";
        return $this->db->select($sql, $params);
    }

    public function crearFundosestanquescliente(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_fundosestanquesclientes_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarFundosestanquescliente(int $fundoestanqueid, int $clienteid, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['fundoestanqueid'] = $fundoestanqueid;
        $data['clienteid'] = $clienteid;

        return $this->db->callSpMaint(
            'sp_fundosestanquesclientes_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularFundosestanquescliente(int $fundoestanqueid, int $clienteid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'fundoestanqueid' => $fundoestanqueid,
            'clienteid' => $clienteid,
        ];

        return $this->db->callSpMaint(
            'sp_fundosestanquesclientes_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarFundosestanquesclientePorId(int $fundoestanqueid, int $clienteid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_fundosestanquesclientes_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($fundoestanqueid, $clienteid) {
            return (int)($row['fundoestanqueid'] ?? 0) === $fundoestanqueid
                && (int)($row['clienteid'] ?? 0) === $clienteid;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
