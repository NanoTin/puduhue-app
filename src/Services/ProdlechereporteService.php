<?php

class ProdlechereporteService
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

    public function listarProdlecheReporte(int $anio, int $mes, ?int $fundoId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'fundoid' => ($fundoId !== null && $fundoId > 0) ? $fundoId : null,
            'anio' => $anio,
            'mes' => $mes,
        ];

        return $this->db->callSpQuery(
            'sp_prodleche_planta_consulta_diaria_por_ames',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
