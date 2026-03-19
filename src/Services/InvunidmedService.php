<?php

class InvunidmedService
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

    public function listarInvunidmed(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroInvunidmeddsc'   => $filtros['filtroInvunidmeddsc'] ?? null,
            'filtroErpunidmedcod'   => $filtros['filtroErpunidmedcod'] ?? null,
            'filtroInvunidmedactivo'=> $filtros['filtroInvunidmedactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_invunidadesmedidas_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarInvunidmedFormSelect($activoFilter = null): array
    {
        $sql = "SELECT invunidmedid, invunidmeddsc, invunidmedactivo FROM invunidadesmedidas";
        $params = [];
        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $sql .= " WHERE invunidmedactivo = ?";
            $params[] = (int)$activoFilter;
        }
        $sql .= " ORDER BY invunidmeddsc ASC";

        return $this->db->select($sql, $params);
    }

    public function crearInvunidmed(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_invunidadesmedidas_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarInvunidmed(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['invunidmedid'] = $id;

        return $this->db->callSpMaint(
            'sp_invunidadesmedidas_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularInvunidmed(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['invunidmedid' => $id];

        return $this->db->callSpMaint(
            'sp_invunidadesmedidas_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarInvunidmedPorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['invunidmedid' => $id,];
        return $this->db->callSpQuery(
            'sp_invunidadesmedidas_consultar_por_id',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );

        /*$result = $this->listarInvunidmed([], $usuarioId, $disp, $ip);
        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['invunidmedid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];*/
    }
}
