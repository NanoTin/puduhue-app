<?php

class InvbodegasController
{
    private \InvbodegasService $service;
    private \FundosService $fundosService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/InvbodegasService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $serviceFundosPath = dirname(__DIR__, 2) . '/Services/FundosService.php';
        if (file_exists($serviceFundosPath)) {
            require_once $serviceFundosPath;
        }

        $this->service = new \InvbodegasService();
        $this->fundosService = new \FundosService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroInvbodegadsc'   => $_GET['filtroInvbodegadsc'] ?? null,
            'filtroErpinvbodegacod'=> $_GET['filtroErpinvbodegacod'] ?? null,
            'filtroFundoid'        => $_GET['filtroFundoid'] ?? null,
            'filtroInvbodactivo'   => $_GET['filtroInvbodactivo'] ?? null,
        ];

        $result = $this->service->listarInvbodegas(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $invbodegas = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('invbodegas_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $fundosOptions = $this->fundosService->listarFundosFormSelect('1');

        $viewFile = $this->viewPath('invbodegas_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearInvbodega(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Bodega creada correctamente', 'success');
            header('Location: ?route=invbodegas/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('invbodegas_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?route=invbodegas/listar');
            exit;
        }
        $fundosOptions = $this->fundosService->listarFundosFormSelect('1');
        
        $result = $this->service->consultarInvbodegaPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $invbodega = $result['rows'][0] ?? null;
        if ($invbodega === null) {
            header('Location: ?route=invbodegas/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('invbodegas_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invbodegaid']) ? (int)$_POST['invbodegaid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=invbodegas/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarInvbodega(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Bodega editada correctamente', 'success');
            header('Location: ?route=invbodegas/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarInvbodegaPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $invbodega = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('invbodegas_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invbodegaid']) ? (int)$_POST['invbodegaid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularInvbodega(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Bodega anulada correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=invbodegas/listar');
        exit;
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
}
