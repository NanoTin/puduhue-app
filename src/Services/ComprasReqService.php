<?php

class ComprasReqService
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

    public function listarReq(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpQuery(
            'sp_compras_req_listar_resumen',
            $this->normalizarFiltrosListado($filtros),
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarPendientesAprobacion(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroBusqueda' => $this->nullIfEmpty($filtros['filtroBusqueda'] ?? null),
            'filtroFechaDesde' => $this->nullIfEmpty($filtros['filtroFechaDesde'] ?? null),
            'filtroFechaHasta' => $this->nullIfEmpty($filtros['filtroFechaHasta'] ?? null),
            'filtroCentroCostoId' => $this->normalizeInt($filtros['filtroCentroCostoId'] ?? ($filtros['filtroCentroCostoId'] ?? null)),
            'filtroPrioridad' => $this->normalizeInt($filtros['filtroPrioridad'] ?? null),
        ];

        return $this->db->callSpQuery(
            'sp_compras_req_listar_pendientes_aprobacion',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarReqResumen(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpQuery(
            'sp_compras_req_consulta_por_id_resumen',
            ['reqcompraid' => $reqcompraid],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarReqDetalle(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpQuery(
            'sp_compras_req_consulta_por_id_detalle',
            ['reqcompraid' => $reqcompraid],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarReqFirmantes(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpQuery(
            'sp_compras_req_consulta_por_id_firmantes',
            ['reqcompraid' => $reqcompraid],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarReqComentarios(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpQuery(
            'sp_compras_req_consulta_por_id_comentarios',
            ['reqcompraid' => $reqcompraid],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarReqAnalisisPpto(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpQuery(
            'sp_compras_req_ppto_analizar',
            ['reqcompraid' => $reqcompraid],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarReqCompleto(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $resumen = $this->consultarReqResumen($reqcompraid, $usuarioId, $disp, $ip);
        $detalle = $this->consultarReqDetalle($reqcompraid, $usuarioId, $disp, $ip);
        $firmantes = $this->consultarReqFirmantes($reqcompraid, $usuarioId, $disp, $ip);
        $comentarios = $this->consultarReqComentarios($reqcompraid, $usuarioId, $disp, $ip);
        $analisis = $this->consultarReqAnalisisPpto($reqcompraid, $usuarioId, $disp, $ip);

        return [
            'req' => $resumen['rows'][0] ?? null,
            'detalle' => $detalle['rows'] ?? [],
            'firmantes' => $firmantes['rows'] ?? [],
            'comentarios' => $comentarios['rows'] ?? [],
            'analisisPpto' => $analisis['rows'] ?? [],
            'meta' => $resumen['meta'] ?? null,
        ];
    }

    public function resolverPresupuestoCompra(string $fecha, int $subfamiliaid, int $centrocostoid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpQuery(
            'sp_compras_ppto_resolver',
            [
                'fecha' => $fecha,
                'subfamiliaid' => $subfamiliaid,
                'centrocostoid' => $centrocostoid,
            ],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function crearReq(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['reqcompraid']);

        return $this->db->callSpMaint(
            'sp_compras_req_crear',
            $this->normalizarPayloadReq($data),
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarReq(int $reqcompraid, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $payload = $this->normalizarPayloadReq($data);
        $payload['reqcompraid'] = $reqcompraid;

        return $this->db->callSpMaint(
            'sp_compras_req_editar',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function tomarEdicion(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_compras_req_tomar_edicion',
            ['reqcompraid' => $reqcompraid],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function cancelarEdicion(int $reqcompraid, ?string $motivo, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $payload = ['reqcompraid' => $reqcompraid];
        $motivo = $this->nullIfEmpty($motivo);
        if ($motivo !== null) {
            $payload['motivo'] = $motivo;
        }

        return $this->db->callSpMaint(
            'sp_compras_req_cancelar_edicion',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function liberarEdicion(int $reqcompraid, string $motivo, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_compras_req_liberar_edicion',
            ['reqcompraid' => $reqcompraid, 'motivo' => trim($motivo)],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function aprobarReq(int $reqcompraid, ?string $comentario, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $payload = ['reqcompraid' => $reqcompraid];
        $comentario = $this->nullIfEmpty($comentario);
        if ($comentario !== null) {
            $payload['comentario'] = $comentario;
        }

        return $this->db->callSpMaint(
            'sp_compras_req_aprobar',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function rechazarReq(int $reqcompraid, string $comentario, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_compras_req_rechazar',
            ['reqcompraid' => $reqcompraid, 'comentario' => trim($comentario)],
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularReq(int $reqcompraid, string $comentario, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_compras_req_anular',
            ['reqcompraid' => $reqcompraid, 'comentario' => trim($comentario)],
            $usuarioId,
            $disp,
            $ip
        );
    }

    private function normalizarFiltrosListado(array $filtros): array
    {
        return [
            'filtroBusqueda' => $this->nullIfEmpty($filtros['filtroBusqueda'] ?? null),
            'filtroEstado' => $this->nullIfEmpty($filtros['filtroEstado'] ?? null),
            'filtroFechaDesde' => $this->nullIfEmpty($filtros['filtroFechaDesde'] ?? null),
            'filtroFechaHasta' => $this->nullIfEmpty($filtros['filtroFechaHasta'] ?? null),
            'filtroCentroCostoId' => $this->normalizeInt($filtros['filtroCentroCostoId'] ?? null),
            'filtroPrioridad' => $this->normalizeInt($filtros['filtroPrioridad'] ?? null),
            'filtroSoloVigentes' => $this->normalizeInt($filtros['filtroSoloVigentes'] ?? 1),
        ];
    }

    private function normalizarPayloadReq(array $data): array
    {
        return [
            'reqcompraid' => $this->normalizeInt($data['reqcompraid'] ?? null),
            'reqcompratipo' => $this->normalizeInt($data['reqcompratipo'] ?? null),
            'centrocostoid' => $this->normalizeInt($data['centrocostoid'] ?? null),
            'funcionariorut' => $this->nullIfEmpty($data['funcionariorut'] ?? null),
            'reqcompraobs' => $this->nullIfEmpty($data['reqcompraobs'] ?? null),
            'reqcompraprioridad' => $this->normalizeInt($data['reqcompraprioridad'] ?? null),
            'accion' => $this->nullIfEmpty($data['accion'] ?? null),
            'detalle' => $this->normalizarDetalleInput($data['detalle'] ?? []),
            'firmantesManual' => $this->normalizarFirmantesInput($data['firmantesManual'] ?? []),
            'comentario' => $this->nullIfEmpty($data['comentario'] ?? null),
        ];
    }

    private function normalizarDetalleInput($detalle): array
    {
        if (!is_array($detalle)) {
            return [];
        }

        $rows = [];
        foreach ($detalle as $row) {
            if (!is_array($row)) {
                continue;
            }

            $rows[] = [
                'invitemid' => $this->normalizeInt($row['invitemid'] ?? null),
                'reqcompradetcantidad' => $this->normalizeDecimal($row['reqcompradetcantidad'] ?? null),
                'reqcompradetobs' => $this->nullIfEmpty($row['reqcompradetobs'] ?? null),
            ];
        }

        return $rows;
    }

    private function normalizarFirmantesInput($firmantes): array
    {
        if (!is_array($firmantes)) {
            return [];
        }

        $rows = [];
        foreach ($firmantes as $row) {
            if (!is_array($row)) {
                continue;
            }

            $usuarioId = $this->normalizeInt($row['usuarioid'] ?? null);
            if ($usuarioId === null || $usuarioId <= 0) {
                continue;
            }

            $rows[] = [
                'usuarioid' => $usuarioId,
                'firmanteorden' => $this->normalizeInt($row['firmanteorden'] ?? null),
            ];
        }

        return $rows;
    }

    private function normalizeInt($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int)$value;
    }

    private function normalizeDecimal($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace(',', '.', trim((string)$value));
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (float)$value;
    }

    private function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
