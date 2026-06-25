<?php

class PptocompraService
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

    public function listarPptocompra(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $normalizeInt = static function ($value): ?int {
            if ($value === null) {
                return null;
            }

            $normalized = trim((string)$value);
            if ($normalized === '') {
                return null;
            }

            if (!preg_match('/^-?\\d+$/', $normalized)) {
                return null;
            }

            return (int)$normalized;
        };

        $dataJson = [
            'filtroPptocompraid' => $normalizeInt($filtros['filtroPptocompraid'] ?? null),
            'filtroTemporadaid' => $normalizeInt($filtros['filtroTemporadaid'] ?? null),
            'filtroSubfamiliaid' => $normalizeInt($filtros['filtroSubfamiliaid'] ?? null),
            'filtroCentrocostoid' => $normalizeInt($filtros['filtroCentrocostoid'] ?? null),
            'filtroPptocompraactivo' => $normalizeInt($filtros['filtroPptocompraactivo'] ?? null),
        ];

        return $this->db->callSpQuery(
            'sp_pptocompra_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarPptocompraPorId(int $pptocompraid, int $usuarioId, ?string $disp, ?string $ip): ?array
    {
        $result = $this->listarPptocompra(
            ['filtroPptocompraid' => $pptocompraid],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        return $rows[0] ?? null;
    }

    public function listarPptocompraDestinoTraspaso(int $pptocompraidOrigen, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->listarPptocompra(
            ['filtroPptocompraactivo' => 1],
            $usuarioId,
            $disp,
            $ip
        );

        return array_values(array_filter($result['rows'] ?? [], static function (array $row) use ($pptocompraidOrigen): bool {
            return (int)($row['pptocompraid'] ?? 0) !== $pptocompraidOrigen;
        }));
    }

    public function listarPptocompraMensual(int $pptocompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_pptocompra_mensual_listar',
            ['filtroPptocompraid' => $pptocompraid],
            $usuarioId,
            $disp,
            $ip
        );

        return $result['rows'] ?? [];
    }

    public function listarPptocompraMovimientos(int $pptocompraid, ?string $filtroTipo, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_pptocompra_movimientos_listar',
            [
                'filtroPptocompraid' => $pptocompraid,
                'filtroTipo' => $filtroTipo,
            ],
            $usuarioId,
            $disp,
            $ip
        );

        return $result['rows'] ?? [];
    }

    public function listarPptocompraMovimientosConSaldos(int $pptocompraid, ?string $filtroTipo, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_pptocompra_movimientos_listar_calculos',
            [
                'filtroPptocompraid' => $pptocompraid,
                'filtroTipo' => $filtroTipo,
            ],
            $usuarioId,
            $disp,
            $ip
        );

        return $result['rows'] ?? [];
    }

    public function listarTemporadasCompras(int $activo = 1): array
    {
        $sql = 'SELECT temporadaid, temporadatipocodigo, temporadadescripcion, temporadainicio, temporadafin
                FROM temporadas
                WHERE temporadatipocodigo = "PPTO_COMPRAS"';

        if ($activo === 1) {
            $sql .= ' AND temporadaactivo = 1';
        }

        $sql .= ' ORDER BY temporadainicio DESC, temporadadescripcion ASC';

        return $this->db->select($sql);
    }

    public function listarSubfamiliasFormSelect(): array
    {
        return $this->db->select(
            'SELECT subfamiliaid, subfamiliacod, subfamiliadsc
             FROM subfamilias
             WHERE subfamiliaactivo = 1
             ORDER BY subfamiliadsc ASC'
        );
    }

    public function listarCentroscostoFormSelect(): array
    {
        return $this->db->select(
            'SELECT centrocostoid, centrocostocod, centrocostodsc
             FROM centroscosto
             WHERE centrocostoactivo = 1
             ORDER BY centrocostodsc ASC'
        );
    }

    public function listarTiposMovimientoActivo(): array
    {
        return $this->db->select(
            'SELECT pptocompratransacciontipoid, pptocompratransacciontipodsc
             FROM pptocompratransaccionestipo
             WHERE pptocompratransacciontipoactivo = 1
               AND pptocompratransacciontipoid IN (\'PPTO_AJUSTE_POS\', \'PPTO_AJUSTE_NEG\')
             ORDER BY FIELD(pptocompratransacciontipoid, \'PPTO_AJUSTE_POS\', \'PPTO_AJUSTE_NEG\')'
        );
    }

    public function crearPptocompra(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_pptocompra_crear',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function actualizarPptocompra(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_pptocompra_actualizar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularPptocompra(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data = ['pptocompraid' => $id];
        return $this->db->callSpMaint(
            'sp_pptocompra_anular',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function ajustarPptocompra(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_pptocompra_ajustar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function traspasarPptocompra(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_pptocompra_traspasar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
