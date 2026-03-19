<?php

class PptolechemensualService
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

    public function listarPptolechemensual(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroPptolecanio' => $filtros['filtroPptolecanio'] ?? null,
            'filtroPptolecmes'  => $filtros['filtroPptolecmes'] ?? null,
            'filtroFundoid'     => $filtros['filtroFundoid'] ?? null,
        ];
        
        return $this->db->callSpQuery(
            'sp_pptolechemensual_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarPresupuestoMensual(int $anio, int $mes, ?int $fundoId = null): array
    {
        $sql = 'SELECT p.fundoid, f.fundonombre, p.pptoleclitros, p.pptolecvacas, p.pptolecltsxvc
                FROM pptolechemensual p
                INNER JOIN fundos f ON f.fundoid = p.fundoid
                WHERE p.pptolecanio = ? AND p.pptolecmes = ?';
        $params = [$anio, $mes];

        if ($fundoId !== null && $fundoId > 0) {
            $sql .= ' AND p.fundoid = ?';
            $params[] = $fundoId;
        }

        $sql .= ' ORDER BY f.fundonombre ASC';

        return $this->db->select($sql, $params);
    }

    public function crearPptolechemensual(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_pptolechemensual_crear',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function actualizarPptolechemensual(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_pptolechemensual_actualizar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function eliminarPptolechemensual(int $anio, int $mes, int $fundoId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'pptolecanio' => $anio,
            'pptolecmes'  => $mes,
            'fundoid'     => $fundoId,
        ];

        return $this->db->callSpMaint(
            'sp_pptolechemensual_eliminar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarPptolechemensualPorPk(int $anio, int $mes, int $fundoId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroPptolecanio' => $anio,
            'filtroPptolecmes'  => $mes,
            'filtroFundoid'     => $fundoId,
        ];

        return $this->db->callSpQuery(
            'sp_pptolechemensual_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function cargarMasivaPptolechemensual(array $rows, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_pptolechemensual_ins_upd',
            $rows,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
