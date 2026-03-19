<?php

class RetirolecheService
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

    public function listarRetiroleche(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroFundoid'          => $filtros['filtroFundoid'] ?? null,
            'filtroFundoestanqueid'  => $filtros['filtroFundoestanqueid'] ?? null,
            'filtroClienteid'        => $filtros['filtroClienteid'] ?? null,
            'filtroRetirolechestatus' => $filtros['filtroRetirolechestatus'] ?? null,
            'filtroFechaDesde'       => $filtros['filtroFechaDesde'] ?? null,
            'filtroFechaHasta'       => $filtros['filtroFechaHasta'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_retirolechedetalle_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function crearRetiroleche(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['retirolecheid']);

        return $this->db->callSpMaint(
            'sp_retirolechedetalle_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarRetiroleche(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['retirolecheid'] = $id;

        return $this->db->callSpMaint(
            'sp_retirolechedetalle_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularRetiroleche(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['retirolecheid' => $id];

        return $this->db->callSpMaint(
            'sp_retirolechedetalle_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarRetirolechePorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['retirolecheid' => $id];

        return $this->db->callSpQuery(
            'sp_retirolechedetalle_consulta_por_id',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
