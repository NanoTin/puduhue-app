<?php

class UsuariosempresasController
{
    private \UsuariosempresasService $service;
    private \EmpresasService $empresasService;
    private \UsuariosService $usuariosService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/UsuariosempresasService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $servicePathEmpresas = dirname(__DIR__, 2) . '/Services/EmpresasService.php';
        if (file_exists($servicePathEmpresas)) {
            require_once $servicePathEmpresas;
        }

        $servicePathUsuarios = dirname(__DIR__, 2) . '/Services/UsuariosService.php';
        if (file_exists($servicePathUsuarios)) {
            require_once $servicePathUsuarios;
        }

        $this->service = new \UsuariosempresasService();
        $this->empresasService = new \EmpresasService();
        $this->usuariosService = new \UsuariosService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroUsuarioid' => $_GET['filtroUsuarioid'] ?? null,
            'filtroEmpresaid' => $_GET['filtroEmpresaid'] ?? null,
        ];

        $result = $this->service->listarUsuariosempresas(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $usuariosempresas = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('usuariosempresas_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $usuariosOptions = $this->usuariosService->listarUsuariosFormSelect(1);
        $empresasOptions = $this->empresasService->listarEmpresasFormSelect(1);

        $viewFile = $this->viewPath('usuariosempresas_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearUsuarioempresa(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            header('Location: ?route=usuariosempresas/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $viewFile = $this->viewPath('usuariosempresas_crear.php');
            require $viewFile;
        }
    }

    public function eliminarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = isset($_POST['usuarioid']) ? (int)$_POST['usuarioid'] : 0;
        $empresaid = isset($_POST['empresaid']) ? (int)$_POST['empresaid'] : 0;

        if ($usuarioId > 0 && $empresaid > 0) {
            try {
                $this->service->eliminarUsuarioempresa(
                    $usuarioId,
                    $empresaid,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
            } catch (RuntimeException $e) {
                // opcional
            }
        }

        header('Location: ?route=usuariosempresas/listar');
        exit;
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }
}
