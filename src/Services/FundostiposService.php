<?php

class FundostiposService
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

    public function listarFundostipos(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroFundotipodsc' => $filtros['filtroFundotipodsc'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_fundostipos_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarFundosTiposFormSelect(): array
    {
        $sql = "SELECT fundotipoid, fundotipodsc FROM fundostipos ORDER BY fundotipodsc ASC";

        return $this->db->select($sql);
    }

    public function crearFundotipo(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['fundotipoid']);

        return $this->db->callSpMaint(
            'sp_fundostipos_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarFundotipo(int $fundotipoId, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['fundotipoid'] = $fundotipoId;

        return $this->db->callSpMaint(
            'sp_fundostipos_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarFundotipoPorId(int $fundotipoId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_fundostipos_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($fundotipoId) {
            return (int)($row['fundotipoid'] ?? 0) === $fundotipoId;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
