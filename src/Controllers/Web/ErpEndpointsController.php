<?php

class ErpEndpointsController
{
    private \ErpListadoEndpointsService $service;
    private \ErpMonedasSyncService $monedasSyncService;
    private \ErpCentrosCostoSyncService $centrosCostoSyncService;
    private \ErpPreItemsSyncService $preItemsSyncService;
    private \ErpProductosSyncService $productosSyncService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ErpListadoEndpointsService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        $monedasSyncPath = dirname(__DIR__, 2) . '/Services/ErpMonedasSyncService.php';
        if (file_exists($monedasSyncPath)) {
            require_once $monedasSyncPath;
        }
        $centrosCostoSyncPath = dirname(__DIR__, 2) . '/Services/ErpCentrosCostoSyncService.php';
        if (file_exists($centrosCostoSyncPath)) {
            require_once $centrosCostoSyncPath;
        }
        $preItemsSyncPath = dirname(__DIR__, 2) . '/Services/ErpPreItemsSyncService.php';
        if (file_exists($preItemsSyncPath)) {
            require_once $preItemsSyncPath;
        }
        $productosSyncPath = dirname(__DIR__, 2) . '/Services/ErpProductosSyncService.php';
        if (file_exists($productosSyncPath)) {
            require_once $productosSyncPath;
        }

        $this->service = new \ErpListadoEndpointsService();
        $this->monedasSyncService = new \ErpMonedasSyncService();
        $this->centrosCostoSyncService = new \ErpCentrosCostoSyncService();
        $this->preItemsSyncService = new \ErpPreItemsSyncService();
        $this->productosSyncService = new \ErpProductosSyncService();
    }

    public function diagnostico(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();

        $endpointCodigo = isset($_GET['endpointCodigo']) ? trim((string)$_GET['endpointCodigo']) : '';
        $errorMessage = null;
        $endpointsActivos = [];
        $planDiagnostico = [];

        try {
            $endpointsActivos = $this->service->listarActivos(true);
            $planDiagnostico = $this->service->diagnosticarPlan($endpointCodigo !== '' ? $endpointCodigo : null);
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
        }

        $viewFile = $this->viewPath('erpendpoints_diagnostico.php');
        require $viewFile;
    }

    public function ejecutarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        ignore_user_abort(true);
        @set_time_limit(0);

        $user = AuthMiddleware::getUserContext();

        $endpointCodigo = isset($_POST['endpointCodigo']) ? trim((string)$_POST['endpointCodigo']) : '';
        if ($endpointCodigo === '') {
            $this->setToast('Debe seleccionar un endpoint ERP para ejecutar.', 'danger');
            header('Location: ?route=erpendpoints/diagnostico');
            exit;
        }

        try {
            if ($endpointCodigo === 'ERP_PRODUCTOS_LIST') {
                $sync = $this->productosSyncService->sincronizar(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_PRODUCTOS_LIST', 'Productos');
            } elseif ($endpointCodigo === 'ERP_MONEDAS_LIST') {
                $sync = $this->monedasSyncService->sincronizar(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_MONEDAS_LIST', 'Monedas');
            } elseif ($endpointCodigo === 'ERP_CENTROS_COSTOS_LIST') {
                $sync = $this->centrosCostoSyncService->sincronizar(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_CENTROS_COSTOS_LIST', 'Centros de Costo');
            } elseif ($endpointCodigo === 'ERP_PARTIDAS_FINANCIERAS_LIST') {
                $sync = $this->preItemsSyncService->sincronizarPartidasFinancieras(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_PARTIDAS_FINANCIERAS_LIST', 'Partidas Financieras');
            } elseif ($endpointCodigo === 'ERP_UNIDADES_MEDIDA_LIST') {
                $sync = $this->preItemsSyncService->sincronizarUnidadesMedida(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_UNIDADES_MEDIDA_LIST', 'Unidades de Medida');
            } elseif ($endpointCodigo === 'ERP_FAMILIAS_LIST') {
                $sync = $this->preItemsSyncService->sincronizarFamilias(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_FAMILIAS_LIST', 'Familias');
            } elseif ($endpointCodigo === 'ERP_SUBFAMILIAS_LIST') {
                $sync = $this->preItemsSyncService->sincronizarSubfamilias(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_SUBFAMILIAS_LIST', 'Subfamilias');
            } elseif ($endpointCodigo === 'ERP_TASAS_IMPOSITIVAS_LIST') {
                $sync = $this->preItemsSyncService->sincronizarTasasImpositivas(
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip'],
                    'MANUAL'
                );
                $resultado = $this->resultadoDesdeSync($sync, 'ERP_TASAS_IMPOSITIVAS_LIST', 'Tasas Impositivas');
            } else {
                $resultado = $this->service->ejecutarPlanOndemand(
                    $endpointCodigo,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
            }

            $errores = array_filter($resultado['resultados'] ?? [], static function (array $row): bool {
                return ($row['estado'] ?? '') === 'ERROR';
            });
            $omitidos = array_filter($resultado['resultados'] ?? [], static function (array $row): bool {
                return ($row['estado'] ?? '') === 'OMITIDO';
            });
            $parciales = array_filter($resultado['resultados'] ?? [], static function (array $row): bool {
                return ($row['estado'] ?? '') === 'PARCIAL';
            });

            if (!empty($errores)) {
                $this->setToast('Ejecucion ERP finalizada con errores. Revise el log tecnico.', 'warning');
            } elseif (!empty($parciales)) {
                $this->setToast('Ejecucion ERP finalizada con registros omitidos. Revise el log tecnico.', 'warning');
            } elseif (!empty($omitidos)) {
                $this->setToast('Ejecucion ERP finalizada. Algunos endpoints de detalle quedaron omitidos por requerir codigo.', 'info');
            } else {
                $this->setToast('Ejecucion ERP finalizada correctamente.', 'success');
            }
        } catch (\Throwable $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=erpendpoints/diagnostico&endpointCodigo=' . urlencode($endpointCodigo));
        exit;
    }

    public function logJson(): void
    {
        AuthMiddleware::requireAuth();

        $endpointId = isset($_GET['endpointId']) ? (int)$_GET['endpointId'] : 0;
        $fechaDesde = $_GET['fechaDesde'] ?? null;
        $fechaHasta = $_GET['fechaHasta'] ?? null;

        try {
            $logs = $this->service->listarLogsEndpoint($endpointId, $fechaDesde, $fechaHasta);
            $this->jsonResponse([
                'status' => 200,
                'rows' => $logs,
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'status' => 400,
                'message' => $e->getMessage(),
                'rows' => [],
            ], 400);
        }
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        require_once dirname(__DIR__, 2) . '/Helpers/FlashMessageHelper.php';
        FlashMessageHelper::toast($message, $type);
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function resultadoDesdeSync(array $sync, string $endpointCodigo, string $descripcionDefault): array
    {
        $conteos = $sync['conteos'] ?? [];

        return [
            'estado' => $sync['estado'] ?? 'OK',
            'resultados' => [[
                'endpointCodigo' => $endpointCodigo,
                'descripcion' => $sync['descripcion'] ?? $descripcionDefault,
                'estado' => $sync['estado'] ?? 'OK',
                'mensaje' => sprintf(
                    'HTTP %d - leidos: %d, insertados: %d, actualizados: %d, inactivos: %d, omitidos: %d',
                    (int)($sync['httpCode'] ?? 0),
                    (int)($conteos['leidos'] ?? 0),
                    (int)($conteos['insertados'] ?? 0),
                    (int)($conteos['actualizados'] ?? 0),
                    (int)($conteos['inactivos'] ?? 0),
                    (int)($conteos['omitidos'] ?? 0)
                ),
            ]],
        ];
    }
}
