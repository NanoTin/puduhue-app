<?php

class SuplanimalController
{
    private \SuplanimalService $service;
    private \UsuariosempresasService $usuariosempresasService;
    private \UsuariosfundosService $usuariosfundosService;
    private \InvbodegasService $invbodegasService;
    private \InvcateganimalService $invcateganimalService;
    private \InvitemsService $invitemsService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/SuplanimalService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        $usuariosempresasPath = dirname(__DIR__, 2) . '/Services/UsuariosempresasService.php';
        if (file_exists($usuariosempresasPath)) {
            require_once $usuariosempresasPath;
        }
        $usuariosfundosPath = dirname(__DIR__, 2) . '/Services/UsuariosfundosService.php';
        if (file_exists($usuariosfundosPath)) {
            require_once $usuariosfundosPath;
        }
        $invbodegasPath = dirname(__DIR__, 2) . '/Services/InvbodegasService.php';
        if (file_exists($invbodegasPath)) {
            require_once $invbodegasPath;
        }
        $invcateganimalPath = dirname(__DIR__, 2) . '/Services/InvcateganimalService.php';
        if (file_exists($invcateganimalPath)) {
            require_once $invcateganimalPath;
        }
        $invitemsPath = dirname(__DIR__, 2) . '/Services/InvitemsService.php';
        if (file_exists($invitemsPath)) {
            require_once $invitemsPath;
        }

        $this->service = new \SuplanimalService();
        $this->usuariosempresasService = new \UsuariosempresasService();
        $this->usuariosfundosService = new \UsuariosfundosService();
        $this->invbodegasService = new \InvbodegasService();
        $this->invcateganimalService = new \InvcateganimalService();
        $this->invitemsService = new \InvitemsService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $empresaIdWS = $user['empresaId'] ?? 0;
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);

        $filtros = [
            'filtroEmpresaid'             => $_GET['filtroEmpresaid'] ?? null,
            'filtroFundoid'               => $_GET['filtroFundoid'] ?? null,
            'filtroSuplanimalestatus'     => $_GET['filtroSuplanimalestatus'] ?? null,
            'filtroInvbodegaid'           => $_GET['filtroInvbodegaid'] ?? null,
            'filtroSuplanimalobservacion' => $_GET['filtroSuplanimalobservacion'] ?? null,
            'filtroFechaDesde'            => $_GET['filtroFechaDesde'] ?? null,
            'filtroFechaHasta'            => $_GET['filtroFechaHasta'] ?? null,
        ];

        $result = $this->service->listarSuplanimal(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $suplanimal = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('suplanimal_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $errorMessage = null;
        $formData = [];

        $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
        $invbodegasOptions = $this->invbodegasService->listarInvbodegasPorFundoFormSelect(null, 1);
        $invcateganimalOptions = $this->invcateganimalService->listarInvcateganimalFormSelect(1);
        $invitemsOptions = $this->invitemsService->listarInvitemsFormSelect(null, 1, 1);
        //Obtener Empresa/Fundo Global seleccionada
        $empresaIdWS = $user['empresaId'] ?? 0;
        $fundoIdWS = $user['fundoId'] ?? 0;

        $viewFile = $this->viewPath('suplanimal_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $postData = $_POST;
        $formData = $postData;
        unset($formData['_token'], $formData['action'], $formData['route']);
        unset($postData['_token'], $postData['action'], $postData['route']);

        try {
            $fundoId = (int)($postData['fundoid'] ?? 0);
            $bodegaId = (int)($postData['invbodegaid'] ?? 0);
            $fecha = $postData['suplanimalfecha'] ?? null;

            if ($fundoId <= 0) {
                throw new RuntimeException('Debe seleccionar un fundo.');
            }
            if ($bodegaId <= 0) {
                throw new RuntimeException('Debe seleccionar una bodega.');
            }
            if (empty($fecha)) {
                throw new RuntimeException('Debe seleccionar una fecha.');
            }

            $this->validarFechaNoFutura($fecha);

            $fundosMap = $this->getFundosMap($user);
            $bodegasMap = $this->getBodegasMap();
            $fundoData = $fundosMap[$fundoId] ?? [];
            $bodegaData = $bodegasMap[$bodegaId] ?? [];

            if (empty($fundoData)) {
                throw new RuntimeException('Fundo no valido.');
            }
            if (empty($bodegaData)) {
                throw new RuntimeException('Bodega no valida.');
            }
            if ((int)($bodegaData['fundoid'] ?? 0) !== $fundoId) {
                throw new RuntimeException('La bodega seleccionada no pertenece al fundo.');
            }

            $detallesInput = is_array($postData['detalles'] ?? null) ? $postData['detalles'] : [];
            $detalles = $this->armarDetalles($detallesInput);

            if (empty($detalles)) {
                throw new RuntimeException('Debe agregar al menos un detalle.');
            }

            $payload = [
                'empresaid' => (int)($fundoData['empresaid'] ?? 0),
                'fundoid' => $fundoId,
                'invbodegaid' => $bodegaId,
                'suplanimalfecha' => $fecha,
                'suplanimalobservacion' => $postData['suplanimalobservacion'] ?? '',
                'sup_erpestablecimientocod' => $fundoData['erpestablecimientocod'] ?? '',
                'sup_erplotecod' => $fundoData['erplotecod'] ?? '',
                'sup_erpinvbodegacod' => $bodegaData['erpinvbodegacod'] ?? '',
                'detalles' => $detalles,
            ];

            $this->validarCamposErpObligatorios($payload);

            $result = $this->service->crearSuplanimal(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro creado correctamente.', 'success');
            header('Location: ?route=suplanimal/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $invbodegasOptions = $this->invbodegasService->listarInvbodegasPorFundoFormSelect(null, 1);
            $invcateganimalOptions = $this->invcateganimalService->listarInvcateganimalFormSelect(1);
            $invitemsOptions = $this->invitemsService->listarInvitemsFormSelect(null, 1, 1);
            $detallesInput = is_array($postData['detalles'] ?? null) ? $postData['detalles'] : [];
            $formData['detalles'] = $this->mapDetallesParaVista($detallesInput);

            $viewFile = $this->viewPath('suplanimal_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->setToast('ID de suplementacion no valido.', 'warning');
            header('Location: ?route=suplanimal/listar');
            exit;
        }

        $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
        $invbodegasOptions = $this->invbodegasService->listarInvbodegasPorFundoFormSelect(null, 1);
        $invcateganimalOptions = $this->invcateganimalService->listarInvcateganimalFormSelect(1);
        $invitemsOptions = $this->invitemsService->listarInvitemsFormSelect(null, 1, 1);

        $result = $this->service->consultarSuplanimalPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $registro = $result['rows'][0] ?? null;
        if ($registro === null) {
            $this->setToast('Registro de suplementacion no encontrado.', 'warning');
            header('Location: ?route=suplanimal/listar');
            exit;
        }

        $formData = $registro;
        $formData['detalles'] = $this->service->listarDetallesPorSuplanimal($id);

        $viewFile = $this->viewPath('suplanimal_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['suplanimalid']) ? (int)$_POST['suplanimalid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=suplanimal/listar');
            exit;
        }

        $postData = $_POST;
        $formData = $postData;
        unset($formData['_token'], $formData['action'], $formData['route']);
        unset($postData['_token'], $postData['action'], $postData['route']);

        try {
            $result = $this->service->consultarSuplanimalPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $registro = $result['rows'][0] ?? null;
            if ($registro === null) {
                throw new RuntimeException('Registro de suplementacion no encontrado.');
            }

            $this->validarFechaNoFutura($registro['suplanimalfecha'] ?? null);

            $detallesInput = is_array($postData['detalles'] ?? null) ? $postData['detalles'] : [];
            $detalles = $this->armarDetalles(
                $detallesInput,
                $this->service->listarDetallesPorSuplanimal($id)
            );

            if (empty($detalles)) {
                throw new RuntimeException('Debe agregar al menos un detalle.');
            }

            $payload = [
                'suplanimalid' => $id,
                'empresaid' => (int)($registro['empresaid'] ?? 0),
                'fundoid' => (int)($registro['fundoid'] ?? 0),
                'invbodegaid' => (int)($registro['invbodegaid'] ?? 0),
                'suplanimalfecha' => $registro['suplanimalfecha'] ?? null,
                'suplanimalobservacion' => $postData['suplanimalobservacion'] ?? ($registro['suplanimalobservacion'] ?? ''),
                'sup_erpestablecimientocod' => $registro['sup_erpestablecimientocod'] ?? '',
                'sup_erplotecod' => $registro['sup_erplotecod'] ?? '',
                'sup_erpinvbodegacod' => $registro['sup_erpinvbodegacod'] ?? '',
                'detalles' => $detalles,
            ];

            $this->validarCamposErpObligatorios($payload);

            $this->service->editarSuplanimal(
                $id,
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro actualizado correctamente.', 'success');
            header('Location: ?route=suplanimal/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarSuplanimalPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $registro = $result['rows'][0] ?? null;
            $formData = array_merge($registro ?? [], $formData);
            $detallesInput = is_array($postData['detalles'] ?? null) ? $postData['detalles'] : [];
            $detallesVista = $this->mapDetallesParaVista($detallesInput);
            $formData['detalles'] = !empty($detallesVista) ? $detallesVista : $this->service->listarDetallesPorSuplanimal($id);

            $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $invbodegasOptions = $this->invbodegasService->listarInvbodegasPorFundoFormSelect(null, 1);
            $invcateganimalOptions = $this->invcateganimalService->listarInvcateganimalFormSelect(1);
            $invitemsOptions = $this->invitemsService->listarInvitemsFormSelect(null, 1, 1);

            $viewFile = $this->viewPath('suplanimal_editar.php');
            require $viewFile;
        }
    }

    public function visualizarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->setToast('ID de suplementacion no valido.', 'warning');
            header('Location: ?route=suplanimal/listar');
            exit;
        }

        $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
        $invbodegasOptions = $this->invbodegasService->listarInvbodegasPorFundoFormSelect(null, 1);
        $invcateganimalOptions = $this->invcateganimalService->listarInvcateganimalFormSelect(1);
        $invitemsOptions = $this->invitemsService->listarInvitemsFormSelect(null, 1, 1);

        $result = $this->service->consultarSuplanimalPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $registro = $result['rows'][0] ?? null;
        if ($registro === null) {
            $this->setToast('Registro de suplementacion no encontrado.', 'warning');
            header('Location: ?route=suplanimal/listar');
            exit;
        }

        $errorMessage = null;
        $formData = $registro;
        $formData['detalles'] = $this->service->listarDetallesPorSuplanimal($id);

        $viewFile = $this->viewPath('suplanimal_visualizar.php');
        require $viewFile;
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['suplanimalid']) ? (int)$_POST['suplanimalid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularSuplanimal(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Registro anulado correctamente.', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=suplanimal/listar');
        exit;
    }

    public function syncPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['suplanimalid']) ? (int)$_POST['suplanimalid'] : 0;
        if ($id > 0) {
            try {
                $this->service->sincronizarSuplanimalConErp(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Suplementacion integrada correctamente.', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=suplanimal/listar');
        exit;
    }

    private function armarDetalles(array $detallesInput, array $detallesExistentes = []): array
    {
        $detalleMap = [];
        foreach ($detallesExistentes as $detalle) {
            $key = ($detalle['invcateganimalid'] ?? 0) . '|' . ($detalle['invitemid'] ?? 0);
            $detalleMap[$key] = $detalle;
        }

        $categorias = $this->getCategoriasMap();
        $items = $this->getItemsMap();

        $detalles = [];
        $seen = [];
        $linea = 1;
        foreach ($detallesInput as $detalle) {
            $categoriaId = (int)($detalle['invcateganimalid'] ?? 0);
            $itemId = (int)($detalle['invitemid'] ?? 0);
            $totalConsumido = (float)($detalle['totalconsumido'] ?? 0);
            $totalAnimales = (int)($detalle['totalanimales'] ?? 0);

            if ($categoriaId <= 0 || $itemId <= 0) {
                continue;
            }
            if ($totalConsumido <= 0 || $totalAnimales <= 0) {
                throw new RuntimeException('Los totales deben ser mayores a cero.');
            }

            $dupKey = $categoriaId . '|' . $itemId;
            if (isset($seen[$dupKey])) {
                throw new RuntimeException('No se permiten duplicados de categoria y producto.');
            }
            $seen[$dupKey] = true;

            $categoriaData = $categorias[$categoriaId] ?? null;
            $itemData = $items[$itemId] ?? null;

            if (!$categoriaData || !$itemData) {
                throw new RuntimeException('Categoria o producto no valido.');
            }

            $invunidmedId = (int)($itemData['invunidmedid'] ?? 0);
            $erpunidmedCod = (string)($itemData['erpunidmedcod'] ?? '');
            if ($invunidmedId <= 0 || $erpunidmedCod === '') {
                throw new RuntimeException('El producto no tiene unidad de medida valida.');
            }

            $dosis = $totalAnimales > 0 ? $totalConsumido / $totalAnimales : 0;
            if ($dosis <= 0) {
                throw new RuntimeException('La dosis por animal debe ser mayor a cero.');
            }

            $erpdocumento = 'PEND';
            $mapKey = $categoriaId . '|' . $itemId;
            if (isset($detalleMap[$mapKey])) {
                $erpdocumento = $detalleMap[$mapKey]['erpdocumentocod'] ?? 'PEND';
            }

            $detalles[] = [
                'suplanimallinea' => $linea,
                'invcateganimalid' => $categoriaId,
                'sup_erpinvcateganimalcod' => $categoriaData['erpinvcateganimalcod'] ?? '',
                'invitemid' => $itemId,
                'sup_erpinvitemcod' => $itemData['erpinvitemcod'] ?? '',
                'invunidmedid' => $invunidmedId,
                'sup_erpunidmedcod' => $erpunidmedCod,
                'totalconsumido' => $totalConsumido,
                'totalanimales' => $totalAnimales,
                'dosisporanimal' => $dosis,
                'erpdocumentocod' => $erpdocumento,
            ];
            $linea++;
        }

        return $detalles;
    }

    private function mapDetallesParaVista(array $detallesInput): array
    {
        $categorias = $this->getCategoriasMap();
        $items = $this->getItemsMap();
        $detalles = [];
        $linea = 1;

        foreach ($detallesInput as $detalle) {
            $categoriaId = (int)($detalle['invcateganimalid'] ?? 0);
            $itemId = (int)($detalle['invitemid'] ?? 0);
            if ($categoriaId <= 0 || $itemId <= 0) {
                continue;
            }
            $categoriaData = $categorias[$categoriaId] ?? [];
            $itemData = $items[$itemId] ?? [];

            $detalles[] = [
                'suplanimallinea' => $linea,
                'invcateganimalid' => $categoriaId,
                'invcateganimaldsc' => $categoriaData['invcateganimaldsc'] ?? '',
                'sup_erpinvcateganimalcod' => $categoriaData['erpinvcateganimalcod'] ?? '',
                'invitemid' => $itemId,
                'invitemdsc' => $itemData['invitemdsc'] ?? '',
                'sup_erpinvitemcod' => $itemData['erpinvitemcod'] ?? '',
                'invunidmedid' => $itemData['invunidmedid'] ?? ($detalle['invunidmedid'] ?? ''),
                'invunidmeddsc' => $itemData['invunidmeddsc'] ?? '',
                'sup_erpunidmedcod' => $itemData['erpunidmedcod'] ?? '',
                'totalconsumido' => $detalle['totalconsumido'] ?? '',
                'totalanimales' => $detalle['totalanimales'] ?? '',
                'dosisporanimal' => $detalle['dosisporanimal'] ?? '',
                'erpdocumentocod' => $detalle['erpdocumentocod'] ?? 'PEND',
            ];
            $linea++;
        }

        return $detalles;
    }

    private function getCategoriasMap(): array
    {
        $map = [];
        foreach ($this->invcateganimalService->listarInvcateganimalFormSelect(1) as $cat) {
            $id = (int)($cat['invcateganimalid'] ?? 0);
            if ($id > 0) {
                $map[$id] = $cat;
            }
        }
        return $map;
    }

    private function getItemsMap(): array
    {
        $map = [];
        foreach ($this->invitemsService->listarInvitemsFormSelect(null, 1, 1) as $item) {
            $id = (int)($item['invitemid'] ?? 0);
            if ($id > 0) {
                $map[$id] = $item;
            }
        }
        return $map;
    }

    private function getFundosMap(array $user): array
    {
        $map = [];
        foreach ($this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']) as $fundo) {
            $id = (int)($fundo['fundoid'] ?? 0);
            if ($id > 0) {
                $map[$id] = $fundo;
            }
        }
        return $map;
    }

    private function getBodegasMap(): array
    {
        $map = [];
        foreach ($this->invbodegasService->listarInvbodegasPorFundoFormSelect(null, 1) as $bodega) {
            $id = (int)($bodega['invbodegaid'] ?? 0);
            if ($id > 0) {
                $map[$id] = $bodega;
            }
        }
        return $map;
    }

    private function validarCamposErpObligatorios(array $payload): void
    {
        $campos = [
            'sup_erpestablecimientocod' => 'ERP Establecimiento',
            'sup_erplotecod' => 'ERP Lote',
            'sup_erpinvbodegacod' => 'ERP Bodega',
        ];

        foreach ($campos as $key => $label) {
            $valor = $payload[$key] ?? null;
            if ($valor === null || $valor === '') {
                throw new RuntimeException("El campo {$label} no puede estar vacio. Contactar al administrador de la plataforma.");
            }
        }
    }

    private function validarFechaNoFutura(?string $fecha): void
    {
        if ($fecha === null || $fecha === '') {
            return;
        }

        $tzId = getenv('TIMEZONE') ?: 'America/Santiago';
        try {
            $tz = new \DateTimeZone($tzId);
        } catch (\Throwable $e) {
            $tz = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', substr($fecha, 0, 10), $tz);
        $errors = \DateTimeImmutable::getLastErrors();
        $warningCount = is_array($errors) ? ($errors['warning_count'] ?? 0) : 0;
        $errorCount = is_array($errors) ? ($errors['error_count'] ?? 0) : 0;
        $hasErrors = ($warningCount + $errorCount) > 0;
        if (!$date || $hasErrors) {
            throw new RuntimeException('Fecha no valida.');
        }

        $today = new \DateTimeImmutable('today', $tz);
        $normalizedDate = $date->setTime(0, 0, 0, 0);
        $normalizedToday = $today->setTime(0, 0, 0, 0);
        if ($normalizedDate > $normalizedToday) {
            throw new RuntimeException('La fecha no puede ser mayor a hoy.');
        }
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        require_once dirname(__DIR__, 2) . '/Helpers/FlashMessageHelper.php';
        FlashMessageHelper::toast($message, $type);
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }
}
