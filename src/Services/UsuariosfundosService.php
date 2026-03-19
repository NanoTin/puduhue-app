<?php

class UsuariosfundosService
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

    public function listarUsuariosfundos(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroUsuarioid' => $filtros['filtroUsuarioid'] ?? null,
            'filtroFundoid'   => $filtros['filtroFundoid'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_usuariosfundos_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarFundosPorUsuarioFormSelect(?string $activoFilter, ?string $usuarioId, ?string $empresaId, ?string $fundotipoId = null): array
    {
        $sql = "SELECT I.fundoid, F.fundonombre, F.empresaid, F.erpestablecimientocod, F.erplotecod, F.erpleche_invbodegacod, F.erpleche_invcateganimalcod FROM usuariosfundos I INNER JOIN fundos F ON I.fundoid = F.fundoid";
        $params = [];
        $conditions = [];

        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $conditions[] = "F.fundoactivo = ?";
            $params[] = (int)$activoFilter;
        }

        if ($usuarioId !== null) {
            $conditions[] = "I.usuarioid = ?";
            $params[] = (int)$usuarioId;
        }

        if ($empresaId > 0) {
            $conditions[] = "F.empresaid = ?";
            $params[] = (int)$empresaId;
        }

        if ($fundotipoId !== null) {
            $conditions[] = "F.fundotipoid = ?";
            $params[] = (int)$fundotipoId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY F.fundonombre ASC";

        return $this->db->select($sql, $params);
    }

    public function crearUsuariofundo(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_usuariosfundos_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function eliminarUsuariofundo(int $usuarioAssocId, int $fundoAssocId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'usuarioid' => $usuarioAssocId,
            'fundoid'   => $fundoAssocId,
        ];

        return $this->db->callSpMaint(
            'sp_usuariosfundos_eliminar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
