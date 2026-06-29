<?php

class ProdlechetiposService
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

    public function listarProdlechetipos(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroProdlechetipodsc' => $filtros['filtroProdlechetipodsc'] ?? null,
            'filtroInvitemid'        => $filtros['filtroInvitemid'] ?? null,
            'filtroProdlecheventa'   => $filtros['filtroProdlecheventa'] ?? null,
            'filtroProdlecheactivo'  => $filtros['filtroProdlecheactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_prodlechetipos_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarProdlechetiposFormGrid($activoFilter = null): array
    {
        $sql = "SELECT prodlechetipoid, prodlechetipodsc, prodlecheventa, prodlecheactivo FROM prodlechetipos";
        $params = [];
        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $sql .= " WHERE prodlecheactivo = ?";
            $params[] = (int)$activoFilter;
        }
        $sql .= " ORDER BY prodlecheorden ASC";

        return $this->db->select($sql, $params);
    }

    public function crearProdlechetipo(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['prodlechetipoid']);

        return $this->db->callSpMaint(
            'sp_prodlechetipos_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarProdlechetipo(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['prodlechetipoid'] = $id;

        return $this->db->callSpMaint(
            'sp_prodlechetipos_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularProdlechetipo(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['prodlechetipoid' => $id];

        return $this->db->callSpMaint(
            'sp_prodlechetipos_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarProdlechetipoPorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_prodlechetipos_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['prodlechetipoid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
