<?php

class UsuariosfundosController
{
    private \UsuariosfundosService $service;
    private \UsuariosService $usuariosService;
    private \FundosService $fundosService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/UsuariosfundosService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $servicePathUsuarios = dirname(__DIR__, 2) . '/Services/UsuariosService.php';
        if (file_exists($servicePathUsuarios)) {
            require_once $servicePathUsuarios;
        }

        $servicePathFundos = dirname(__DIR__, 2) . '/Services/FundosService.php';
        if (file_exists($servicePathFundos)) {
            require_once $servicePathFundos;
        }

        $this->service = new \UsuariosfundosService();
        $this->usuariosService = new \UsuariosService();
        $this->fundosService = new \FundosService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroUsuarioid' => $_GET['filtroUsuarioid'] ?? null,
            'filtroFundoid'   => $_GET['filtroFundoid'] ?? null,
        ];

        $result = $this->service->listarUsuariosfundos(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $usuariosfundos = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $usuariosOptions = $this->usuariosService->listarUsuariosFormSelect(null);
        $fundosOptions = $this->fundosService->listarFundosFormSelect(null);

        $viewFile = $this->viewPath('usuariosfundos_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $usuariosOptions = $this->usuariosService->listarUsuariosFormSelect(1);
        $fundosOptions = $this->fundosService->listarFundosFormSelect(1);

        $viewFile = $this->viewPath('usuariosfundos_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearUsuariofundo(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Asociación creada correctamente', 'success');
            header('Location: ?route=usuariosfundos/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $usuariosOptions = $this->usuariosService->listarUsuariosFormSelect(1);
            $fundosOptions = $this->fundosService->listarFundosFormSelect(1);
            
            $viewFile = $this->viewPath('usuariosfundos_crear.php');
            require $viewFile;
        }
    }

    public function eliminarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = isset($_POST['usuarioid']) ? (int)$_POST['usuarioid'] : 0;
        $fundoId   = isset($_POST['fundoid']) ? (int)$_POST['fundoid'] : 0;

        if ($usuarioId > 0 && $fundoId > 0) {
            try {
                $this->service->eliminarUsuariofundo(
                    $usuarioId,
                    $fundoId,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Asociación eliminada correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=usuariosfundos/listar');
        exit;
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
