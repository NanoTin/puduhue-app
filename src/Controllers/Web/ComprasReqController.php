<?php

class ComprasReqController
{
    private \ComprasReqService $service;
    private \ComprasCatalogosService $catalogosService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ComprasReqService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $catalogosPath = dirname(__DIR__, 2) . '/Services/ComprasCatalogosService.php';
        if (file_exists($catalogosPath)) {
            require_once $catalogosPath;
        }

        $this->service = new \ComprasReqService();
        $this->catalogosService = new \ComprasCatalogosService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = $this->buildListadoFilters($_GET);
        $result = $this->service->listarReq($filtros, $user['usuarioId'], $user['dispositivo'], $user['ip']);

        $reqs = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;
        $centrosOptions = $this->catalogosService->listarCentrosCostoUsuarioFormSelect((int)$user['usuarioId']);
        $errorMessage = null;

        require $this->viewPath('compras_req_listar.php');
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $formData = $this->buildDefaultFormData((int)$user['usuarioId']);
        $centrosOptions = $this->catalogosService->listarCentrosCostoUsuarioFormSelect((int)$user['usuarioId']);
        $funcionariosOptions = $this->catalogosService->listarFuncionariosFormSelect(null, $this->normalizeInt($formData['centrocostoid'] ?? null));
        $itemsRows = $this->catalogosService->listarItemsCompraReqFormGrid(0);
        $aprobadoresRows = $this->catalogosService->listarUsuariosAprobadoresReqFormGrid();
        $errorMessage = null;

        require $this->viewPath('compras_req_crear.php');
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $formData = $this->normalizeReqFormData($_POST);
        $action = $formData['accion'] ?? '';

        if (!in_array($action, ['guardar_borrador', 'enviar_aprobacion'], true)) {
            header('Location: ?route=compras-req/listar');
            exit;
        }

        try {
            $this->validarPayloadMinimo($formData);
            $result = $this->service->crearReq($formData, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            $this->setToast((string)($result['message'] ?? 'REQ creado correctamente.'), 'success');
            header('Location: ?route=compras-req/ver&id=' . urlencode((string)($result['id'] ?? '')));
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $centrosOptions = $this->catalogosService->listarCentrosCostoUsuarioFormSelect((int)$user['usuarioId']);
            $funcionariosOptions = $this->catalogosService->listarFuncionariosFormSelect(null, $this->normalizeInt($formData['centrocostoid'] ?? null));
            $itemsRows = $this->catalogosService->listarItemsCompraReqFormGrid(0);
            $aprobadoresRows = $this->catalogosService->listarUsuariosAprobadoresReqFormGrid();
            require $this->viewPath('compras_req_crear.php');
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_GET['id'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/listar');
            exit;
        }

        $bundle = $this->service->consultarReqCompleto($reqcompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        $req = $bundle['req'] ?? null;
        if ($req === null) {
            $this->setToast('REQ no encontrado.', 'warning');
            header('Location: ?route=compras-req/listar');
            exit;
        }

        if (!$this->canOpenEditForm($req, (int)$user['usuarioId'])) {
            $this->setToast('El REQ no puede editarse en el estado actual.', 'warning');
            header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
            exit;
        }

        $detalle = $bundle['detalle'] ?? [];
        $firmantes = $bundle['firmantes'] ?? [];
        $comentarios = $bundle['comentarios'] ?? [];
        $analisisPpto = $bundle['analisisPpto'] ?? [];
        $formData = $this->buildFormDataFromReq($req, $detalle, $firmantes);
        $centrosOptions = $this->catalogosService->listarCentrosCostoUsuarioFormSelect((int)$user['usuarioId']);
        $funcionariosOptions = $this->catalogosService->listarFuncionariosFormSelect(null, $this->normalizeInt($formData['centrocostoid'] ?? null));
        $itemsRows = $this->catalogosService->listarItemsCompraReqFormGrid(0);
        $aprobadoresRows = $this->catalogosService->listarUsuariosAprobadoresReqFormGrid();
        $errorMessage = null;

        require $this->viewPath('compras_req_editar.php');
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_POST['reqcompraid'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/listar');
            exit;
        }

        $formData = $this->normalizeReqFormData($_POST);
        $formData['reqcompraid'] = $reqcompraid;

        try {
            $this->validarPayloadMinimo($formData);
            $result = $this->service->editarReq($reqcompraid, $formData, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            $this->setToast((string)($result['message'] ?? 'REQ actualizado correctamente.'), 'success');
            header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $bundle = $this->service->consultarReqCompleto($reqcompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            $req = $bundle['req'] ?? null;
            $detalle = $bundle['detalle'] ?? [];
            $firmantes = $bundle['firmantes'] ?? [];
            $comentarios = $bundle['comentarios'] ?? [];
            $analisisPpto = $bundle['analisisPpto'] ?? [];
            $centrosOptions = $this->catalogosService->listarCentrosCostoUsuarioFormSelect((int)$user['usuarioId']);
            $funcionariosOptions = $this->catalogosService->listarFuncionariosFormSelect(null, $this->normalizeInt($formData['centrocostoid'] ?? null));
            $itemsRows = $this->catalogosService->listarItemsCompraReqFormGrid(0);
            $aprobadoresRows = $this->catalogosService->listarUsuariosAprobadoresReqFormGrid();
            require $this->viewPath('compras_req_editar.php');
        }
    }

    public function ver(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_GET['id'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/listar');
            exit;
        }

        $bundle = $this->service->consultarReqCompleto($reqcompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        $req = $bundle['req'] ?? null;
        if ($req === null) {
            $this->setToast('REQ no encontrado.', 'warning');
            header('Location: ?route=compras-req/listar');
            exit;
        }

        $detalle = $bundle['detalle'] ?? [];
        $firmantes = $bundle['firmantes'] ?? [];
        $comentarios = $bundle['comentarios'] ?? [];
        $analisisPpto = $bundle['analisisPpto'] ?? [];
        $puedeAprobar = $this->canApprove($req, (int)$user['usuarioId']);
        $puedeRechazar = $puedeAprobar;
        $puedeEditar = $this->canEditFromView($req, (int)$user['usuarioId'], $firmantes);
        $puedeAnular = $this->canAnular($req, (int)$user['usuarioId'], $firmantes);
        $errorMessage = null;

        require $this->viewPath('compras_req_ver.php');
    }

    public function pendientesAprobacion(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroBusqueda' => $_GET['filtroBusqueda'] ?? null,
            'filtroFechaDesde' => $_GET['filtroFechaDesde'] ?? null,
            'filtroFechaHasta' => $_GET['filtroFechaHasta'] ?? null,
            'filtroCentroCostoId' => $_GET['filtroCentroCostoId'] ?? null,
            'filtroPrioridad' => $_GET['filtroPrioridad'] ?? null,
        ];

        $result = $this->service->listarPendientesAprobacion($filtros, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        $reqs = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;
        $centrosOptions = $this->catalogosService->listarCentrosCostoUsuarioFormSelect((int)$user['usuarioId']);
        $errorMessage = null;

        require $this->viewPath('compras_req_pendientes_aprobacion.php');
    }

    public function aprobarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_POST['reqcompraid'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/pendientes-aprobacion');
            exit;
        }

        try {
            $result = $this->service->aprobarReq(
                $reqcompraid,
                $_POST['comentario'] ?? null,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast((string)($result['message'] ?? 'REQ aprobado correctamente.'), 'success');
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
        exit;
    }

    public function rechazarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_POST['reqcompraid'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/pendientes-aprobacion');
            exit;
        }

        $comentario = trim((string)($_POST['comentario'] ?? ''));
        if (mb_strlen($comentario) <= 10) {
            $this->setToast('El comentario de rechazo es obligatorio y debe tener mas de 10 caracteres.', 'warning');
            header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
            exit;
        }

        try {
            $result = $this->service->rechazarReq(
                $reqcompraid,
                $comentario,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast((string)($result['message'] ?? 'REQ rechazado correctamente.'), 'success');
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
        exit;
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_POST['reqcompraid'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/listar');
            exit;
        }

        $comentario = trim((string)($_POST['comentario'] ?? ''));
        if ($comentario === '') {
            $this->setToast('El comentario de anulacion es obligatorio.', 'warning');
            header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
            exit;
        }

        try {
            $result = $this->service->anularReq(
                $reqcompraid,
                $comentario,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast((string)($result['message'] ?? 'REQ anulado correctamente.'), 'success');
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=compras-req/listar');
        exit;
    }

    public function tomarEdicionPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_POST['reqcompraid'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/listar');
            exit;
        }

        try {
            $this->service->tomarEdicion($reqcompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            header('Location: ?route=compras-req/editar&id=' . urlencode((string)$reqcompraid));
            exit;
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
            header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
            exit;
        }
    }

    public function cancelarEdicionPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $reqcompraid = $this->normalizeInt($_POST['reqcompraid'] ?? null);
        if ($reqcompraid === null || $reqcompraid <= 0) {
            header('Location: ?route=compras-req/listar');
            exit;
        }

        try {
            $result = $this->service->cancelarEdicion(
                $reqcompraid,
                $_POST['motivo'] ?? null,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast((string)($result['message'] ?? 'Edicion cancelada.'), 'success');
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=compras-req/ver&id=' . urlencode((string)$reqcompraid));
        exit;
    }

    public function validarItemPpto(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $invitemid = $this->normalizeInt($_GET['invitemid'] ?? null);
        $centrocostoid = $this->normalizeInt($_GET['centrocostoid'] ?? null);
        $reqcompratipo = $this->normalizeInt($_GET['reqcompratipo'] ?? null);
        $fecha = trim((string)($_GET['fecha'] ?? date('Y-m-d')));

        if ($invitemid === null || $invitemid <= 0) {
            $this->jsonResponse(['status' => 400, 'message' => 'invitemid es obligatorio.'], 400);
            return;
        }
        if ($centrocostoid === null || $centrocostoid <= 0) {
            $this->jsonResponse(['status' => 400, 'message' => 'Debe seleccionar un centro de costo.'], 400);
            return;
        }
        if ($reqcompratipo === null || !in_array($reqcompratipo, [1, 2], true)) {
            $this->jsonResponse(['status' => 400, 'message' => 'Debe seleccionar un tipo de REQ válido.'], 400);
            return;
        }

        $item = $this->catalogosService->obtenerItemCompraReqPorId($invitemid);
        if ($item === null) {
            $this->jsonResponse(['status' => 404, 'message' => 'El ítem no existe o no está disponible para compras.'], 404);
            return;
        }

        $itemType = !empty($item['invitemstockeable']) ? 1 : 2;
        if ($itemType !== $reqcompratipo) {
            $this->jsonResponse(['status' => 400, 'message' => 'El ítem no coincide con el tipo de REQ seleccionado.'], 400);
            return;
        }

        $subfamiliaid = $this->normalizeInt($item['subfamiliaid'] ?? null);
        if ($subfamiliaid === null || $subfamiliaid <= 0) {
            $this->jsonResponse(['status' => 422, 'message' => 'El ítem no tiene subfamilia válida para presupuesto.'], 422);
            return;
        }

        $result = $this->service->resolverPresupuestoCompra(
            $fecha,
            $subfamiliaid,
            $centrocostoid,
            (int)$user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $presupuesto = $result['rows'][0] ?? null;
        if ($presupuesto === null) {
            $this->jsonResponse([
                'status' => 422,
                'message' => 'La subfamilia del ítem no tiene presupuesto de compra vigente para el centro seleccionado. Contacte a Administración.',
            ], 422);
            return;
        }

        $saldoDisponible = (float)($presupuesto['pptocomprasaldodisponible'] ?? 0);
        $otrosReq = (float)($presupuesto['reqpptomontootroscurso'] ?? 0);
        $aprobados = (float)($presupuesto['reqpptomontoaprobadospend'] ?? 0);
        $this->jsonResponse([
            'status' => 200,
            'message' => 'OK',
            'item' => [
                'invitemid' => (int)($item['invitemid'] ?? 0),
                'subfamiliaid' => $subfamiliaid,
                'subfamiliadsc' => (string)($item['subfamiliadsc'] ?? ''),
            ],
            'presupuesto' => [
                'pptocompraid' => (int)($presupuesto['pptocompraid'] ?? 0),
                'temporadadescripcion' => (string)($presupuesto['temporadadescripcion'] ?? ''),
                'saldoDisponible' => $saldoDisponible,
                'otrosReq' => $otrosReq,
                'aprobados' => $aprobados,
                'advertenciaSaldo' => ($saldoDisponible <= 0),
            ],
        ]);
    }

    private function buildListadoFilters(array $source): array
    {
        return [
            'filtroBusqueda' => $source['filtroBusqueda'] ?? null,
            'filtroEstado' => $source['filtroEstado'] ?? null,
            'filtroFechaDesde' => $source['filtroFechaDesde'] ?? null,
            'filtroFechaHasta' => $source['filtroFechaHasta'] ?? null,
            'filtroCentroCostoId' => $source['filtroCentroCostoId'] ?? null,
            'filtroPrioridad' => $source['filtroPrioridad'] ?? null,
            'filtroSoloVigentes' => $source['filtroSoloVigentes'] ?? '1',
        ];
    }

    private function buildDefaultFormData(int $usuarioId): array
    {
        $centros = $this->catalogosService->listarCentrosCostoUsuarioFormSelect($usuarioId);
        $centroDefault = null;
        foreach ($centros as $centro) {
            if (!empty($centro['usucendefault'])) {
                $centroDefault = (string)($centro['centrocostoid'] ?? '');
                break;
            }
        }
        if ($centroDefault === null && !empty($centros[0]['centrocostoid'])) {
            $centroDefault = (string)$centros[0]['centrocostoid'];
        }

        return [
            'reqcompratipo' => '1',
            'centrocostoid' => $centroDefault ?? '',
            'funcionariorut' => '',
            'reqcompraobs' => '',
            'reqcompraprioridad' => '1',
            'accion' => 'guardar_borrador',
            'detalle' => [],
            'firmantesManual' => [],
            'comentario' => '',
        ];
    }

    private function buildFormDataFromReq(array $req, array $detalle, array $firmantes): array
    {
        $formData = [
            'reqcompraid' => (string)($req['reqcompraid'] ?? ''),
            'reqcompratipo' => (string)($req['reqcompratipo'] ?? '1'),
            'centrocostoid' => (string)($req['centrocostoid'] ?? ''),
            'funcionariorut' => (string)($req['funcionariorut'] ?? ''),
            'reqcompraobs' => (string)($req['reqcompraobs'] ?? ''),
            'reqcompraprioridad' => (string)($req['reqcompraprioridad'] ?? '1'),
            'accion' => 'guardar_borrador',
            'detalle' => [],
            'firmantesManual' => [],
            'comentario' => '',
        ];

        foreach ($detalle as $row) {
            $formData['detalle'][] = [
                'invitemid' => (string)($row['invitemid'] ?? ''),
                'reqcompradetcantidad' => (string)($row['reqcompradetcantidad'] ?? ''),
                'reqcompradetobs' => (string)($row['reqcompradetobs'] ?? ''),
            ];
        }

        foreach ($firmantes as $row) {
            if (($row['firmantetipo'] ?? '') === 'MANUAL') {
                $formData['firmantesManual'][] = [
                    'usuarioid' => (string)($row['firmanteusuarioid'] ?? ''),
                    'firmanteorden' => (string)($row['firmanteorden'] ?? ''),
                ];
            }
        }

        return $formData;
    }

    private function normalizeReqFormData(array $source): array
    {
        return [
            'reqcompraid' => $this->normalizeInt($source['reqcompraid'] ?? null),
            'reqcompratipo' => (string)($source['reqcompratipo'] ?? ''),
            'centrocostoid' => (string)($source['centrocostoid'] ?? ''),
            'funcionariorut' => trim((string)($source['funcionariorut'] ?? '')),
            'reqcompraobs' => trim((string)($source['reqcompraobs'] ?? '')),
            'reqcompraprioridad' => (string)($source['reqcompraprioridad'] ?? ''),
            'accion' => trim((string)($source['accion'] ?? '')),
            'detalle' => $this->normalizeDetalleRows($source['detalle'] ?? []),
            'firmantesManual' => $this->normalizeFirmantesRows($source['firmantesManual'] ?? []),
            'comentario' => trim((string)($source['comentario'] ?? '')),
        ];
    }

    private function normalizeDetalleRows($detalle): array
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
                'invitemid' => trim((string)($row['invitemid'] ?? '')),
                'reqcompradetcantidad' => trim((string)($row['reqcompradetcantidad'] ?? '')),
                'reqcompradetobs' => trim((string)($row['reqcompradetobs'] ?? '')),
            ];
        }

        return $rows;
    }

    private function normalizeFirmantesRows($firmantes): array
    {
        if (!is_array($firmantes)) {
            return [];
        }

        $rows = [];
        foreach ($firmantes as $row) {
            if (!is_array($row)) {
                continue;
            }

            $usuarioId = trim((string)($row['usuarioid'] ?? ''));
            if ($usuarioId === '') {
                continue;
            }

            $rows[] = [
                'usuarioid' => $usuarioId,
                'firmanteorden' => trim((string)($row['firmanteorden'] ?? '')),
            ];
        }

        return $rows;
    }

    private function validarPayloadMinimo(array $data): void
    {
        if (($data['reqcompratipo'] ?? '') === '') {
            throw new RuntimeException('Debe seleccionar el tipo de REQ.');
        }
        if (($data['centrocostoid'] ?? '') === '') {
            throw new RuntimeException('Debe seleccionar un centro de costo.');
        }
        if (($data['reqcompraprioridad'] ?? '') === '') {
            throw new RuntimeException('Debe seleccionar la prioridad.');
        }

        $hasValidDetail = false;
        foreach ($data['detalle'] ?? [] as $row) {
            $itemId = $this->normalizeInt($row['invitemid'] ?? null);
            $cantidad = str_replace(',', '.', (string)($row['reqcompradetcantidad'] ?? ''));
            if ($itemId !== null && $itemId > 0 && is_numeric($cantidad) && (float)$cantidad > 0) {
                $hasValidDetail = true;
                break;
            }
        }

        if (!$hasValidDetail) {
            throw new RuntimeException('Debe ingresar al menos una linea valida en el detalle.');
        }
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function canOpenEditForm(array $req, int $usuarioId): bool
    {
        $estado = (string)($req['reqcompraestadoid'] ?? '');
        $creador = (int)($req['auditcreacionusuarioid'] ?? 0);

        if ($creador !== $usuarioId) {
            return false;
        }

        return in_array($estado, ['BRR', 'RCH', 'EDT'], true);
    }

    private function canEditFromView(array $req, int $usuarioId, array $firmantes): bool
    {
        $estado = (string)($req['reqcompraestadoid'] ?? '');
        $creador = (int)($req['auditcreacionusuarioid'] ?? 0);
        if ($creador !== $usuarioId) {
            return false;
        }

        if (in_array($estado, ['BRR', 'RCH', 'EDT'], true)) {
            return true;
        }

        if ($estado === 'PND') {
            foreach ($firmantes as $firmante) {
                if (($firmante['firmanteestado'] ?? '') === 'APR') {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    private function canApprove(array $req, int $usuarioId): bool
    {
        return (string)($req['reqcompraestadoid'] ?? '') === 'PND'
            && (int)($req['reqaprobadoridpnd'] ?? 0) === $usuarioId;
    }

    private function canAnular(array $req, int $usuarioId, array $firmantes): bool
    {
        $estado = (string)($req['reqcompraestadoid'] ?? '');
        if ((int)($req['auditcreacionusuarioid'] ?? 0) !== $usuarioId) {
            return false;
        }

        if (!in_array($estado, ['BRR', 'RCH', 'PND', 'EDT'], true)) {
            return false;
        }

        foreach ($firmantes as $firmante) {
            if (($firmante['firmanteestado'] ?? '') === 'APR') {
                return false;
            }
        }

        return true;
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

    private function viewPath(string $fileName): string
    {
        return dirname(__DIR__, 3) . '/apps/web-php/' . $fileName;
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        $flashPath = dirname(__DIR__, 2) . '/Helpers/FlashMessageHelper.php';
        if (file_exists($flashPath)) {
            require_once $flashPath;
        }

        if (class_exists('FlashMessageHelper')) {
            FlashMessageHelper::toast($message, $type);
        }
    }
}
