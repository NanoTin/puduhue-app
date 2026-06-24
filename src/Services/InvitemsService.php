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
            'filtroInvitemusocodigo' => $filtros['filtroInvitemusocodigo'] ?? null,
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

    public function listarInvitemsFormSelect($itemlecheFilter = null, $itemStockeable = null, $activoFilter = null, ?string $usoCodigo = null): array
    {
        $sql = "SELECT invitems.invitemid, invitems.invitemdsc, invitems.erpinvitemcod, invitems.invunidmedid, invitems.invitemusocodigo, invunidadesmedidas.invunidmeddsc, invunidadesmedidas.erpunidmedcod
                FROM invitems
                LEFT JOIN invunidadesmedidas ON invunidadesmedidas.invunidmedid = invitems.invunidmedid";
        $params = [];
        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $sql .= " WHERE invitemactivo = ?";
            $params[] = (int)$activoFilter;
        }
        $usoCodigo = $this->normalizarUsoCodigo($usoCodigo);
        if ($usoCodigo !== null) {
            $sql .= empty($params) ? " WHERE " : " AND ";
            $sql .= "(invitemusocodigo = ?";
            $params[] = $usoCodigo;

            if ($usoCodigo === 'LCH' && ($itemlecheFilter === '0' || $itemlecheFilter === 0 || $itemlecheFilter === '1' || $itemlecheFilter === 1)) {
                $sql .= " OR (invitemusocodigo = 'BDG' AND invitemleche = ?)";
                $params[] = (int)$itemlecheFilter;
            } elseif ($usoCodigo === 'ALM' && ($itemStockeable === '0' || $itemStockeable === 0 || $itemStockeable === '1' || $itemStockeable === 1)) {
                $sql .= " OR (invitemusocodigo = 'BDG' AND invitemstockeable = ?)";
                $params[] = (int)$itemStockeable;
            }

            $sql .= ")";
        } elseif ($itemlecheFilter === '0' || $itemlecheFilter === 0 || $itemlecheFilter === '1' || $itemlecheFilter === 1) {
            $sql .= empty($params) ? " WHERE " : " AND ";
            $sql .= "invitemleche = ?";
            $params[] = (int)$itemlecheFilter;
        } elseif ($itemStockeable === '0' || $itemStockeable === 0 || $itemStockeable === '1' || $itemStockeable === 1) {
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

    public function listarFamiliasFormSelect(): array
    {
        return $this->db->select(
            'SELECT familiaid, familiacod, familiadsc
             FROM familias
             WHERE familiaactivo = 1
             ORDER BY familiadsc ASC'
        );
    }

    public function listarSubfamiliasFormSelect(): array
    {
        return $this->db->select(
            'SELECT subfamiliaid, subfamiliacod, subfamiliadsc, familiaid
             FROM subfamilias
             WHERE subfamiliaactivo = 1
             ORDER BY subfamiliadsc ASC'
        );
    }

    public function listarTasasImpositivasFormSelect(): array
    {
        return $this->db->select(
            'SELECT erptasaimpositivaid, erptasaimpositivacod, erptasaimpositivadsc, erptasaimpositivaporcentaje
             FROM erptasasimpositivas
             WHERE erptasaimpositivaactivo = 1
             ORDER BY erptasaimpositivadsc ASC'
        );
    }

    public function listarPartidasFinancierasFormSelect(): array
    {
        return $this->db->select(
            'SELECT erppartidafinancieraid, erppartidafinancieracod, erppartidafinancieradsc
             FROM erppartidasfinancieras
             WHERE erppartidafinancieraactivo = 1
             ORDER BY erppartidafinancieradsc ASC'
        );
    }

    private function normalizarUsoCodigo(?string $usoCodigo): ?string
    {
        $usoCodigo = strtoupper(trim((string)$usoCodigo));
        if ($usoCodigo === '') {
            return null;
        }

        return in_array($usoCodigo, ['BDG', 'LCH', 'ALM', 'CMB'], true) ? $usoCodigo : 'BDG';
    }
}
