<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProdlecheController
{
    private \ProdlecheService $service;
    private \UsuariosempresasService $usuariosempresasService;
    private \UsuariosfundosService $usuariosfundosService;
    private \ProdlechetiposService $prodlechetiposService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ProdlecheService.php';
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
        $prodlechetiposPath = dirname(__DIR__, 2) . '/Services/ProdlechetiposService.php';
        if (file_exists($prodlechetiposPath)) {
            require_once $prodlechetiposPath;
        }

        $this->service = new \ProdlecheService();
        $this->usuariosempresasService = new \UsuariosempresasService();
        $this->usuariosfundosService = new \UsuariosfundosService();
        $this->prodlechetiposService = new \ProdlechetiposService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $empresaIdWS = $user['empresaId'] ?? 0;
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);

        $filtros = [
            'filtroProdlecheid'        => $_GET['filtroProdlecheid'] ?? null,
            'filtroProdlechestatus'    => $_GET['filtroProdlechestatus'] ?? null,
            'filtroEmpresaid'          => $_GET['filtroEmpresaid'] ?? null,
            'filtroFundoid'            => $_GET['filtroFundoid'] ?? null,
            'filtroFechaDesde'         => $_GET['filtroFechaDesde'] ?? null,
            'filtroFechaHasta'         => $_GET['filtroFechaHasta'] ?? null,
            'filtroProdlecheobservacion' => $_GET['filtroProdlecheobservacion'] ?? null,
            'filtroProdlechehorario'   => $_GET['filtroProdlechehorario'] ?? null,
        ];

        $result = $this->service->listarProdleche(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $prodleche = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('prodleche_listar.php');
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
        $prodlechetiposOptions = $this->prodlechetiposService->listarProdlechetiposFormGrid();
        //Obtener Empresa/Fundo Global seleccionada
        $empresaIdWS = $user['empresaId'] ?? 0;
        $fundoIdWS = $user['fundoId'] ?? 0;

        $viewFile = $this->viewPath('prodleche_crear.php');
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

        $fundosMap = $this->getFundosMap($user);
        $prodlecheTiposRows = $this->prodlechetiposService->listarProdlechetiposFormGrid(); //$this->getProdlecheTiposMap($user);
        $prodlecheTiposMap = [];
        foreach ($prodlecheTiposRows as $tipoRow) {
            $tipoId = (int)($tipoRow['prodlechetipoid'] ?? 0);
            if ($tipoId > 0) {
                $prodlecheTiposMap[$tipoId] = $tipoRow;
            }
        }

        try {
            $this->validarFechaNoFutura($postData['prodlechefecha'] ?? null);
            $this->validarHoras($postData['prodlechehoraini'] ?? null, $postData['prodlechehorafin'] ?? null);

            $detallesInput = is_array($postData['detalles'] ?? null) ? $postData['detalles'] : [];
            $detalles = [];
            $totalLitros = 0;
            $totalVacas = 0;
            $ventaLitros = 0;
            $ventaVacas = 0;
            $detalleConCantidades = false;

            foreach ($detallesInput as $detalle) {
                $tipoId = (int)($detalle['prodlechetipoid'] ?? 0);
                $litros = (float)($detalle['pldetlitros'] ?? 0);
                $vacas = (float)($detalle['pldetvacas'] ?? 0);
                $litrosxvaca = $vacas > 0 ? $litros / $vacas : 0;

                if ($tipoId <= 0) {
                    continue;
                }

                if ($litros > 0 && $vacas > 0) {
                    $detalleConCantidades = true;
                }

                $detalles[] = [
                    'prodlechetipoid' => $tipoId,
                    'pldetlitros' => $litros,
                    'pldetvacas' => $vacas,
                    'pldetlitrosxvaca' => $litrosxvaca,
                    'prodlechecod' => $this->generarCodigoDetalle($tipoId),
                    'erpdocumentocod' => 'PEND',
                ];

                $totalLitros += $litros;
                $totalVacas += $vacas;

                $esVenta = !empty($prodlecheTiposMap[$tipoId]['prodlecheventa']);
                if ($esVenta) {
                    $ventaLitros += $litros;
                    $ventaVacas += $vacas;
                }
            }

            if (empty($detalles)) {
                throw new RuntimeException('Debe ingresar al menos un detalle de producción.');
            }
            if (!$detalleConCantidades) {
                throw new RuntimeException('Ingrese al menos un tipo de leche con litros y vacas mayores a cero.');
            }

            $ventaLxVaca = $ventaVacas > 0 ? $ventaLitros / $ventaVacas : 0;
            $horario = $this->calcularHorario($postData['prodlechehoraini'] ?? null);

            $fundoId = (int)($postData['fundoid'] ?? 0);
            $fundoData = $fundosMap[$fundoId] ?? [];

            $payload = [
                'empresaid' => $fundoData['empresaid'] ?? (int)($postData['empresaid'] ?? 0),
                'fundoid' => $fundoId,
                'prodlechefecha' => $postData['prodlechefecha'] ?? null,
                'prodlechehoraini' => $postData['prodlechehoraini'] ?? null,
                'prodlechehorafin' => $postData['prodlechehorafin'] ?? null,
                'prodlechehorario' => $horario,
                'pl_erpestablecimientocod' => $fundoData['erpestablecimientocod'] ?? ($postData['pl_erpestablecimientocod'] ?? ''),
                'pl_erplotecod' => $fundoData['erplotecod'] ?? ($postData['pl_erplotecod'] ?? ''),
                'pl_erpleche_invbodegacod' => $fundoData['erpleche_invbodegacod'] ?? ($postData['pl_erpleche_invbodegacod'] ?? ''),
                'pl_erpleche_invcateganimalcod' => $fundoData['erpleche_invcateganimalcod'] ?? ($postData['pl_erpleche_invcateganimalcod'] ?? ''),
                'prodlechetotlitros' => $totalLitros,
                'prodlechetotvacas' => $totalVacas,
                'prodlecheventatotlitros' => $ventaLitros,
                'prodlecheventatotvacas' => $ventaVacas,
                'prodlecheventalitrosxvaca' => $ventaLxVaca,
                'prodlecheobservacion' => $postData['prodlecheobservacion'] ?? '',
                'detalles' => $detalles,
            ];

            $formData['detalles'] = $detallesInput;
            $formData['prodlechehorario'] = $horario;
            $formData['prodlechetotlitros'] = $totalLitros;
            $formData['prodlechetotvacas'] = $totalVacas;
            $formData['prodlecheventatotlitros'] = $ventaLitros;
            $formData['prodlecheventatotvacas'] = $ventaVacas;
            $formData['prodlecheventalitrosxvaca'] = $ventaLxVaca;
            $formData['pl_erpestablecimientocod'] = $payload['pl_erpestablecimientocod'];
            $formData['pl_erplotecod'] = $payload['pl_erplotecod'];
            $formData['pl_erpleche_invbodegacod'] = $payload['pl_erpleche_invbodegacod'];
            $formData['pl_erpleche_invcateganimalcod'] = $payload['pl_erpleche_invcateganimalcod'];
            $this->validarCamposErpObligatorios($payload);

            $this->service->crearProdleche(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro de producción creado correctamente.', 'success');
            header('Location: ?route=prodleche/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $prodlechetiposOptions = $this->prodlechetiposService->listarProdlechetiposFormGrid();
            $viewFile = $this->viewPath('prodleche_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
        $prodlechetiposOptions = $this->prodlechetiposService->listarProdlechetiposFormGrid();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->setToast('ID de producción no válido.', 'warning');
            header('Location: ?route=prodleche/listar');
            exit;
        }

        $result = $this->service->consultarProdlechePorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $registro = $result['rows'][0] ?? null;
        if ($registro === null) {
            $this->setToast('Registro de producción no encontrado.', 'warning');
            header('Location: ?route=prodleche/listar');
            exit;
        }

        $errorMessage = null;
        $formData = $registro;
        $formData['detalles'] = $this->service->listarDetallesPorProdleche($id);

        $viewFile = $this->viewPath('prodleche_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['prodlecheid']) ? (int)$_POST['prodlecheid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=prodleche/listar');
            exit;
        }

        $postData = $_POST;
        $formData = $postData;
        unset($formData['_token'], $formData['action'], $formData['route']);
        unset($postData['_token'], $postData['action'], $postData['route']);

        try {
            $result = $this->service->consultarProdlechePorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $registro = $result['rows'][0] ?? null;
            if ($registro === null) {
                throw new RuntimeException('Registro de producciИn no encontrado.');
            }

            //$this->validarFechaNoFutura($registro['prodlechefecha'] ?? null);
            $this->validarHoras($registro['prodlechehoraini'] ?? null, $postData['prodlechehorafin'] ?? null);

            $detallesInput = is_array($postData['detalles'] ?? null) ? $postData['detalles'] : [];
            $detalles = [];
            $totalLitros = 0;
            $totalVacas = 0;
            $ventaLitros = 0;
            $ventaVacas = 0;
            $detalleConCantidades = false;

            $prodlecheTiposRows = $this->prodlechetiposService->listarProdlechetiposFormGrid();
            $prodlecheTiposMap = [];
            foreach ($prodlecheTiposRows as $tipoRow) {
                $tipoId = (int)($tipoRow['prodlechetipoid'] ?? 0);
                if ($tipoId > 0) {
                    $prodlecheTiposMap[$tipoId] = $tipoRow;
                }
            }
            $detallesExistentes = $this->service->listarDetallesPorProdleche($id);
            $detallesMap = [];
            foreach ($detallesExistentes as $detalleExistente) {
                $tipoIdExistente = (int)($detalleExistente['prodlechetipoid'] ?? 0);
                if ($tipoIdExistente > 0) {
                    $detallesMap[$tipoIdExistente] = $detalleExistente;
                }
            }

            foreach ($detallesInput as $detalle) {
                $tipoId = (int)($detalle['prodlechetipoid'] ?? 0);
                $litros = (float)($detalle['pldetlitros'] ?? 0);
                $vacas = (float)($detalle['pldetvacas'] ?? 0);
                $litrosxvaca = $vacas > 0 ? $litros / $vacas : 0;

                if ($tipoId <= 0) {
                    continue;
                }

                if ($litros > 0 && $vacas > 0) {
                    $detalleConCantidades = true;
                }

                $existente = $detallesMap[$tipoId] ?? [];
                $detalles[] = [
                    'prodlechetipoid' => $tipoId,
                    'pldetlitros' => $litros,
                    'pldetvacas' => $vacas,
                    'pldetlitrosxvaca' => $litrosxvaca,
                    'prodlechecod' => $existente['prodlechecod'] ?? $this->generarCodigoDetalle($tipoId),
                    'erpdocumentocod' => $existente['erpdocumentocod'] ?? 'PEND',
                ];

                $totalLitros += $litros;
                $totalVacas += $vacas;

                $esVenta = !empty($prodlecheTiposMap[$tipoId]['prodlecheventa']);
                if ($esVenta) {
                    $ventaLitros += $litros;
                    $ventaVacas += $vacas;
                }
            }

            if (empty($detalles)) {
                throw new RuntimeException('Debe ingresar al menos un detalle de produccion.');
            }
            if (!$detalleConCantidades) {
                throw new RuntimeException('Ingrese al menos un tipo de leche con litros y vacas mayores a cero.');
            }

            $ventaLxVaca = $ventaVacas > 0 ? $ventaLitros / $ventaVacas : 0;
            $horario = $this->calcularHorario($registro['prodlechehoraini'] ?? null);

            $payload = [
                'prodlecheid' => $id,
                'empresaid' => (int)($registro['empresaid'] ?? 0),
                'fundoid' => (int)($registro['fundoid'] ?? 0),
                'prodlechefecha' => $registro['prodlechefecha'] ?? null,
                'prodlechehoraini' => $registro['prodlechehoraini'] ?? null,
                'prodlechehorafin' => $postData['prodlechehorafin'] ?? ($registro['prodlechehorafin'] ?? null),
                'prodlechehorario' => $horario,
                'pl_erpestablecimientocod' => $registro['pl_erpestablecimientocod'] ?? '',
                'pl_erplotecod' => $registro['pl_erplotecod'] ?? '',
                'pl_erpleche_invbodegacod' => $registro['pl_erpleche_invbodegacod'] ?? '',
                'pl_erpleche_invcateganimalcod' => $registro['pl_erpleche_invcateganimalcod'] ?? '',
                'prodlechetotlitros' => $totalLitros,
                'prodlechetotvacas' => $totalVacas,
                'prodlecheventatotlitros' => $ventaLitros,
                'prodlecheventatotvacas' => $ventaVacas,
                'prodlecheventalitrosxvaca' => $ventaLxVaca,
                'prodlecheobservacion' => $postData['prodlecheobservacion'] ?? ($registro['prodlecheobservacion'] ?? ''),
                'detalles' => $detalles,
            ];

            $formData['detalles'] = $detallesInput;
            $formData['prodlechehorario'] = $horario;
            $formData['prodlechetotlitros'] = $totalLitros;
            $formData['prodlechetotvacas'] = $totalVacas;
            $formData['prodlecheventatotlitros'] = $ventaLitros;
            $formData['prodlecheventatotvacas'] = $ventaVacas;
            $formData['prodlecheventalitrosxvaca'] = $ventaLxVaca;
            $this->validarCamposErpObligatorios($payload);

            $this->service->editarProdleche(
                $id,
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro actualizado correctamente.', 'success');
            header('Location: ?route=prodleche/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarProdlechePorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $registro = $result['rows'][0] ?? null;
            $formData = array_merge($registro ?? [], $formData);

            $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $prodlechetiposOptions = $this->prodlechetiposService->listarProdlechetiposFormGrid();
            $viewFile = $this->viewPath('prodleche_editar.php');
            require $viewFile;
        }
    }

    public function visualizarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $empresasOptions = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, $user['usuarioId']);
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
        $prodlechetiposOptions = $this->prodlechetiposService->listarProdlechetiposFormGrid();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->setToast('ID de producción no válido.', 'warning');
            header('Location: ?route=prodleche/listar');
            exit;
        }

        $result = $this->service->consultarProdlechePorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $registro = $result['rows'][0] ?? null;
        if ($registro === null) {
            $this->setToast('Registro de producción no encontrado.', 'warning');
            header('Location: ?route=prodleche/listar');
            exit;
        }

        $errorMessage = null;
        $formData = $registro;
        $formData['detalles'] = $this->service->listarDetallesPorProdleche($id);

        $viewFile = $this->viewPath('prodleche_visualizar.php');
        require $viewFile;
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['prodlecheid']) ? (int)$_POST['prodlecheid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularProdleche(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
            } catch (RuntimeException $e) {
                $message = $e->getMessage();
                $this->setToast($message, 'danger');
            }
        }

        header('Location: ?route=prodleche/listar');
        exit;
    }

    public function syncPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['prodlecheid']) ? (int)$_POST['prodlecheid'] : 0;
        if ($id > 0) {
            try {
                $this->service->sincronizarProdlecheConErp(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Sincronización con ERP realizada correctamente.', 'success');
            } catch (RuntimeException $e) {
                $message = $e->getMessage();
                $this->setToast($message, 'danger');
            }
        }

        header('Location: ?route=prodleche/listar');
        exit;
    }

    public function cargaMasivaPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        header('Content-Type: application/json; charset=utf-8');

        $usuarioCod = strtoupper($user['usuarioCod'] ?? '');
        if ($usuarioCod !== 'ROOT') {
            echo json_encode([
                'status' => 403,
                'message' => 'No tiene permisos para realizar esta carga masiva.',
                'totEncInsertados' => 0,
                'totDetInsertados' => 0,
                'errores' => [],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $uploadedFile = $_FILES['prodleche_excel'] ?? null;
        if (!$this->isValidUpload($uploadedFile)) {
            echo json_encode([
                'status' => 400,
                'message' => 'Debe seleccionar un archivo Excel valido.',
                'totEncInsertados' => 0,
                'totDetInsertados' => 0,
                'errores' => [],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $rows = $this->leerExcelProdleche($uploadedFile['tmp_name']);
            if (empty($rows)) {
                throw new RuntimeException('No se encontraron filas validas en el Excel.');
            }

            $empresaid = (int)($user['empresaId'] ?? 0);
            if ($empresaid <= 0) {
                throw new RuntimeException('Empresa no valida para la carga masiva.');
            }

            $payload = [
                'empresaid' => $empresaid,
                'observacion' => 'Carga masiva desde ERP Finnegans',
                'rows' => $rows,
            ];

            $result = $this->service->cargaMasivaProdleche(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            echo json_encode([
                'status' => $result['status'] ?? 500,
                'message' => $result['message'] ?? 'Carga masiva procesada.',
                'totEncInsertados' => $result['totEncInsertados'] ?? 0,
                'totDetInsertados' => $result['totDetInsertados'] ?? 0,
                'errores' => $result['errores'] ?? [],
            ], JSON_UNESCAPED_UNICODE);
        } catch (RuntimeException $e) {
            echo json_encode([
                'status' => 400,
                'message' => $e->getMessage(),
                'totEncInsertados' => 0,
                'totDetInsertados' => 0,
                'errores' => [],
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode([
                'status' => 500,
                'message' => 'Error inesperado al procesar la carga masiva.',
                'totEncInsertados' => 0,
                'totDetInsertados' => 0,
                'errores' => [],
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }

    private function getFundosMap(array $user): array
    {
        $map = [];
        foreach ($this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']) as $fundo){
            $id = (int)($fundo['fundoid'] ?? 0);
            if ($id > 0) {
                $map[$id] = $fundo;
            }
        }
        return $map;
    }

    private function getProdlecheTiposMap(array $user): array
    {
        $map = [];
        foreach ($this->getProdlecheTipos($user) as $tipo) {
            $id = (int)($tipo['prodlechetipoid'] ?? 0);
            if ($id > 0) {
                $map[$id] = $tipo;
            }
        }
        return $map;
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

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha, $tz);
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
            throw new RuntimeException('La fecha no puede ser mayor a hoy...');
        }
    }

    private function validarCamposErpObligatorios(array $payload): void
    {
        $campos = [
            'pl_erpestablecimientocod' => 'ERP Establecimiento',
            'pl_erplotecod' => 'ERP Lote',
            'pl_erpleche_invbodegacod' => 'ERP Bodega Leche',
            'pl_erpleche_invcateganimalcod' => 'ERP Cat. Animal Leche',
        ];

        foreach ($campos as $key => $label) {
            $valor = $payload[$key] ?? null;
            if ($valor === null || $valor === '') {
                throw new RuntimeException("El campo {$label} no puede estar vacío. Contactar al administrador de la plataforma.");
            }
        }
    }

    private function validarHoras(?string $horaIni, ?string $horaFin): void
    {
        if (empty($horaIni) || empty($horaFin)) {
            return;
        }

        $iniParts = explode(':', $horaIni);
        $finParts = explode(':', $horaFin);
        $ini = ((int)($iniParts[0] ?? 0)) * 60 + ((int)($iniParts[1] ?? 0));
        $fin = ((int)($finParts[0] ?? 0)) * 60 + ((int)($finParts[1] ?? 0));
        if ($fin < $ini) {
            throw new RuntimeException('La hora término no puede ser menor a la hora inicio.');
        }
    }

    private function calcularHorario(?string $horaIni): string
    {
        if (empty($horaIni)) {
            return '';
        }
        $parts = explode(':', $horaIni);
        $hour = (int)($parts[0] ?? 0);
        return $hour < 12 ? 'AM' : 'PM';
    }

    private function generarCodigoDetalle(int $tipoId): string
    {
        $rand = random_int(0, 99);
        return sprintf('P%s%02d%02d', date('His'), $tipoId % 100, $rand);
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        $_SESSION['toast'] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    private function isValidUpload(?array $file): bool
    {
        return $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    private function leerExcelProdleche(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $dataRows = [];
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $payload = $this->buildPayloadFromRow($row, $rowNumber);
            if ($payload !== null) {
                $dataRows[] = $payload;
            }
        }

        return $dataRows;
    }

    private function buildPayloadFromRow(array $row, int $rowNumber): ?array
    {
        $fundoId = $this->parseInt($row['A'] ?? null);
        $fecha = $this->parseFecha($row['B'] ?? null);
        $lechetipoid = $this->parseInt($row['C'] ?? null);
        $litros = $this->parseFloat($row['D'] ?? null);
        $vacas = $this->parseFloat($row['E'] ?? null);
        $ltsxvaca = $this->parseFloat($row['F'] ?? null);

        $hasData = $fundoId !== null || $fecha !== null || $lechetipoid !== null || $litros !== null || $vacas !== null || $ltsxvaca !== null;
        if (!$hasData) {
            return null;
        }

        if ($ltsxvaca === null && $litros !== null && $vacas !== null && $vacas > 0) {
            $ltsxvaca = $litros / $vacas;
        }

        return [
            'fundoid' => $fundoId,
            'fecha' => $fecha,
            'lechetipoid' => $lechetipoid,
            'litros' => $litros,
            'vacas' => $vacas,
            'lts_x_vaca' => $ltsxvaca ?? 0,
            'row' => $rowNumber,
        ];
    }

    private function parseInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        $value = preg_replace('/[^0-9]/', '', (string)$value);
        return $value === '' ? null : (int)$value;
    }

    private function parseFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        $value = str_replace(',', '.', (string)$value);
        $value = preg_replace('/[^0-9.]/', '', $value);
        return $value === '' ? null : (float)$value;
    }

    private function parseFecha($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d');
            } catch (Throwable $e) {
                return null;
            }
        }

        $value = trim((string)$value);
        try {
            if (str_contains($value, '/')) {
                $dt = \DateTimeImmutable::createFromFormat('d/m/Y', $value);
                if ($dt !== false) {
                    return $dt->format('Y-m-d');
                }
            }
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }
}
