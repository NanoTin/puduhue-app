<?php

/**
 * FundosService
 */
class FundosService
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

    public function listarFundos(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroFundonombre'             => $filtros['filtroFundonombre'] ?? null,
            'filtroFundotipoid'             => $filtros['filtroFundotipoid'] ?? null,
            'filtroEmpresaid'               => $filtros['filtroEmpresaid'] ?? null,
            'filtroErpestablecimientocod'   => $filtros['filtroErpestablecimientocod'] ?? null,
            'filtroErplotecod'              => $filtros['filtroErplotecod'] ?? null,
            'filtroErpleche_invbodegacod'   => $filtros['filtroErpleche_invbodegacod'] ?? null,
            'filtroErpleche_invcateganimalcod' => $filtros['filtroErpleche_invcateganimalcod'] ?? null,
            'filtroFundopabco'              => $filtros['filtroFundopabco'] ?? null,
            'filtroFundorup'                => $filtros['filtroFundorup'] ?? null,
            'filtroFundoactivo'             => $filtros['filtroFundoactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_fundos_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarFundosFormSelect(?string $activoFilter = null, ?string $empresaFilter = null): array
    {
        $sql = "SELECT fundoid, fundonombre, empresaid, erpestablecimientocod, erplotecod, erpleche_invbodegacod, erpleche_invcateganimalcod FROM fundos";
        $params = [];
        if ($activoFilter !== null && ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1)) {
            $sql .= " WHERE fundoactivo = ?";
            $params[] = (int)$activoFilter;
        }
        if ($empresaFilter !== null) {
            $sql .= (count($params) > 0 ? " AND " : " WHERE ") . " empresaid = ?";
            $params[] = (int)$empresaFilter;
        }
        $sql .= " ORDER BY fundonombre ASC";

        return $this->db->select($sql, $params);
    }

    public function crearFundo(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['fundoid']);

        return $this->db->callSpMaint(
            'sp_fundos_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarFundo(int $fundoId, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['fundoid'] = $fundoId;

        return $this->db->callSpMaint(
            'sp_fundos_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularFundo(int $fundoId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['fundoid' => $fundoId];

        return $this->db->callSpMaint(
            'sp_fundos_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarFundoPorId(int $fundoId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['fundoid' => $fundoId];
        return $this->db->callSpQuery(
            'sp_fundos_consultar_por_id',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
