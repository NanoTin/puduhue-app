<?php

class InvcateganimalService
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

    public function listarInvcateganimal(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroInvcateganimaldsc'    => $filtros['filtroInvcateganimaldsc'] ?? null,
            'filtroErpinvcateganimalcod' => $filtros['filtroErpinvcateganimalcod'] ?? null,
            'filtroInvcateganimalactivo' => $filtros['filtroInvcateganimalactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_invcateganimal_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarInvcateganimalFormSelect(?string $activoFiler  = null): array
    {
        $sql = "SELECT invcateganimalid, invcateganimaldsc, erpinvcateganimalcod FROM invcateganimal";
        $params = [];
        if ($activoFiler === '0' || $activoFiler === 0 || $activoFiler === '1' || $activoFiler === 1) {
            $sql .= " WHERE invcateganimalactivo = ?";
            $params[] = (int)$activoFiler;
        }
        $sql .= " ORDER BY invcateganimaldsc ASC";

        return $this->db->select($sql, $params);
    }

    public function crearInvcateganimal(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['invcateganimalid']);

        return $this->db->callSpMaint(
            'sp_invcateganimal_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarInvcateganimal(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['invcateganimalid'] = $id;

        return $this->db->callSpMaint(
            'sp_invcateganimal_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularInvcateganimal(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['invcateganimalid' => $id];

        return $this->db->callSpMaint(
            'sp_invcateganimal_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarInvcateganimalPorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_invcateganimal_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['invcateganimalid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
