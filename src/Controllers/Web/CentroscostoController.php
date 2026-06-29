<?php

class CentroscostoController
{
    private \CentroscostoService $service;
    private \ErpCentrosCostoSyncService $syncService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/CentroscostoService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $syncPath = dirname(__DIR__, 2) . '/Services/ErpCentrosCostoSyncService.php';
        if (file_exists($syncPath)) {
            require_once $syncPath;
        }

        $this->service = new \CentroscostoService();
        $this->syncService = new \ErpCentrosCostoSyncService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroCentrocostocod' => $_GET['filtroCentrocostocod'] ?? null,
            'filtroCentrocostodsc' => $_GET['filtroCentrocostodsc'] ?? null,
            'filtroCentrocostoactivo' => $_GET['filtroCentrocostoactivo'] ?? null,
        ];

        $result = $this->service->listarCentroscosto(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $centroscosto = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;
        $errorMessage = null;

        require $this->viewPath('centroscosto_listar.php');
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $centrocostoid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($centrocostoid <= 0) {
            header('Location: ?route=centroscosto/listar');
            exit;
        }

        $centrocosto = $this->service->consultarCentrocostoPorId(
            $centrocostoid,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        if ($centrocosto === null) {
            $this->setToast('Centro de costo no encontrado.', 'danger');
            header('Location: ?route=centroscosto/listar');
            exit;
        }

        $aprobadoresOptions = $this->service->listarUsuariosAprobadoresReqFormSelect();
        $errorMessage = null;

        require $this->viewPath('centroscosto_editar.php');
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $centrocostoid = isset($_POST['centrocostoid']) ? (int)$_POST['centrocostoid'] : 0;
        if ($centrocostoid <= 0) {
            $this->setToast('Centro de costo invalido.', 'danger');
            header('Location: ?route=centroscosto/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarCentrocosto(
                $centrocostoid,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Centro de costo actualizado correctamente.', 'success');
            header('Location: ?route=centroscosto/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $centrocosto = $this->service->consultarCentrocostoPorId(
                $centrocostoid,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            if ($centrocosto !== null) {
                $centrocosto['centrocostojefeusuarioid'] = $data['centrocostojefeusuarioid'] ?? null;
                $centrocosto['centrocostojefetecnicoid'] = $data['centrocostojefetecnicoid'] ?? null;
            }
            $aprobadoresOptions = $this->service->listarUsuariosAprobadoresReqFormSelect();
            require $this->viewPath('centroscosto_editar.php');
        }
    }

    public function syncPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        try {
            $resultado = $this->syncService->sincronizar(
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip'],
                'MANUAL'
            );

            $estado = strtoupper((string)($resultado['estado'] ?? 'OK'));
            if ($estado === 'OK') {
                $this->setToast('Sincronización de centros de costo completada.', 'success');
            } else {
                $this->setToast('La sincronización de centros de costo finalizó con errores.', 'danger');
            }
        } catch (\Throwable $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=centroscosto/listar');
        exit;
    }

    public function crearForm(bool $partial = false): void
    {
        $this->bloquearCreacionManual();
    }

    public function crearPost(bool $partial = false): void
    {
        $this->bloquearCreacionManual();
    }

    private function bloquearCreacionManual(): void
    {
        $this->setToast('La creación manual de centros de costo está bloqueada. Use sincronización ERP.', 'warning');
        header('Location: ?route=centroscosto/listar');
        exit;
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        require_once dirname(__DIR__, 2) . '/Helpers/FlashMessageHelper.php';
        FlashMessageHelper::toast($message, $type);
    }

    private function viewPath(string $fileName): string
    {
        return dirname(__DIR__, 3) . '/apps/web-php/' . $fileName;
    }
}
