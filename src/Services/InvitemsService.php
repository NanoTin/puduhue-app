<?php

class InvitemsService
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

    public function listarInvitems(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroInvitemdsc'    => $filtros['filtroInvitemdsc'] ?? null,
            'filtroInvunidmedid'  => $filtros['filtroInvunidmedid'] ?? null,
            'filtroErpinvitemcod' => $filtros['filtroErpinvitemcod'] ?? null,
            'filtroInvitemleche'  => $filtros['filtroInvitemleche'] ?? null,
            'filtroInvitemactivo' => $filtros['filtroInvitemactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_invitems_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarInvitemsFormSelect($itemlecheFilter = null, $itemStockeable = null, $activoFilter = null): array
    {
        $sql = "SELECT invitems.invitemid, invitems.invitemdsc, invitems.erpinvitemcod, invitems.invunidmedid, invunidadesmedidas.invunidmeddsc, invunidadesmedidas.erpunidmedcod
                FROM invitems
                LEFT JOIN invunidadesmedidas ON invunidadesmedidas.invunidmedid = invitems.invunidmedid";
        $params = [];
        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $sql .= " WHERE invitemactivo = ?";
            $params[] = (int)$activoFilter;
        }
        if ($itemlecheFilter === '0' || $itemlecheFilter === 0 || $itemlecheFilter === '1' || $itemlecheFilter === 1) {
            $sql .= empty($params) ? " WHERE " : " AND ";
            $sql .= "invitemleche = ?";
            $params[] = (int)$itemlecheFilter;
        }
        if ($itemStockeable === '0' || $itemStockeable === 0 || $itemStockeable === '1' || $itemStockeable === 1) {
            $sql .= empty($params) ? " WHERE " : " AND ";
            $sql .= "invitemstockeable = ?";
            $params[] = (int)$itemStockeable;
        }
        $sql .= " ORDER BY invitems.invitemdsc ASC";

        return $this->db->select($sql, $params);
    }

    public function crearInvitem(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['invitemid']);

        return $this->db->callSpMaint(
            'sp_invitems_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarInvitem(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['invitemid'] = $id;

        return $this->db->callSpMaint(
            'sp_invitems_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularInvitem(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['invitemid' => $id];

        return $this->db->callSpMaint(
            'sp_invitems_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarInvitemPorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_invitems_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['invitemid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
