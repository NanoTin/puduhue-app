<?php

class PerfilesController
{
    private \PerfilesService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/PerfilesService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \PerfilesService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroPerfildesc'     => $_GET['filtroPerfildesc'] ?? null,
            'filtroPerfilesroot'   => $_GET['filtroPerfilesroot'] ?? null,
            'filtroPerfilesadmin'  => $_GET['filtroPerfilesadmin'] ?? null,
            'filtroPerfilactivo'   => $_GET['filtroPerfilactivo'] ?? null,
        ];

        $result = $this->service->listarPerfiles(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $perfiles = $result['rows'] ?? [];
        $meta     = $result['meta'] ?? null;

        $viewFile = $this->viewPath('perfiles_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;
        $viewFile = $this->viewPath('perfiles_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $data['perfilesroot'] = isset($_POST['perfilesroot']) ? (int)$_POST['perfilesroot'] : 0;
        $data['perfilesadmin'] = isset($_POST['perfilesadmin']) ? (int)$_POST['perfilesadmin'] : 0;
        $data['perfilactivo'] = isset($_POST['perfilactivo']) ? (int)$_POST['perfilactivo'] : 0;

        try {
            $this->service->crearPerfil(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Perfil creado correctamente', 'success');
            header('Location: ?route=perfiles/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('perfiles_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?route=perfiles/listar');
            exit;
        }

        $result = $this->service->consultarPerfilPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $perfil = $result['rows'][0] ?? null;
        if ($perfil === null) {
            header('Location: ?route=perfiles/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('perfiles_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['perfilid']) ? (int)$_POST['perfilid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=perfiles/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $data['perfilesroot'] = isset($_POST['perfilesroot']) ? (int)$_POST['perfilesroot'] : 0;
        $data['perfilesadmin'] = isset($_POST['perfilesadmin']) ? (int)$_POST['perfilesadmin'] : 0;
        $data['perfilactivo'] = isset($_POST['perfilactivo']) ? (int)$_POST['perfilactivo'] : 0;

        try {
            $this->service->editarPerfil(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Perfil editado correctamente', 'success');
            header('Location: ?route=perfiles/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarPerfilPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $perfil = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('perfiles_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['perfilid']) ? (int)$_POST['perfilid'] : 0;
        if ($id <= 0) {
            $this->setToast('ID de perfil inválido', 'danger');
            header('Location: ?route=perfiles/listar');
            exit;
        }
      
        try {
            $this->service->anularPerfil(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Perfil anulado correctamente', 'success');
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }
    
        header('Location: ?route=perfiles/listar');
        exit;
    }

    public function consultarPorId(int $id, bool $partial = false): ?array
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $result = $this->service->consultarPerfilPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        return $result['rows'][0] ?? null;
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        $_SESSION['toast'] = [
            'message' => $message,
            'type'    => $type,
        ];
    }
}
