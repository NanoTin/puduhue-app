<?php

class PerfilesmenusController
{
    private \PerfilesmenusService $service;
    private \PerfilesService $servicePerfiles;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/PerfilesmenusService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        $servicePerfilesPath = dirname(__DIR__, 2) . '/Services/PerfilesService.php';
        if (file_exists($servicePerfilesPath)) {
            require_once $servicePerfilesPath;
        }

        $this->service = new \PerfilesmenusService();
        $this->servicePerfiles = new \PerfilesService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroPerfilid'         => $_GET['filtroPerfilid'] ?? null,
            'filtroMenuid'           => $_GET['filtroMenuid'] ?? null,
            'filtroPerfilmenuactivo' => $_GET['filtroPerfilmenuactivo'] ?? null,
        ];

        $result = $this->service->listarPerfilesmenus(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $perfilesmenus = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;
        $perfilesOptions = $this->servicePerfiles->listarPerfilesFormSelect(null);

        $viewFile = $this->viewPath('perfilesmenus_listar.php');
        require $viewFile;
    }

    public function listarMenusPorPerfil(int $perfilId): array
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        return $this->service->listarMenusPerfilFormGrid(
            $perfilId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $perfilesOptions = $this->servicePerfiles->listarPerfilesFormSelect(1);

        $viewFile = $this->viewPath('perfilesmenus_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $data['perfilmenuactivo'] = isset($_POST['perfilmenuactivo']) ? 1 : 0;

        try {
            $this->service->crearPerfilesmenu(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Asignacion creada correctamente', 'success');
            header('Location: ?route=perfilesmenus/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('perfilesmenus_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $perfilId = isset($_GET['perfilid']) ? (int)$_GET['perfilid'] : 0;
        $menuId = isset($_GET['menuid']) ? (int)$_GET['menuid'] : 0;
        if ($perfilId <= 0 || $menuId <= 0) {
            error_log("Parámetros inválidos para editar la asignacion: perfilId=$perfilId, menuId=$menuId");
            header('Location: ?route=perfilesmenus/listar');
            exit;
        }

        $result = $this->service->listarPerfilesmenus(
            [
                'filtroPerfilid' => $perfilId,
                'filtroMenuid'   => $menuId,
            ],
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $registro = $result['rows'][0] ?? null;
        if ($registro === null) {
            error_log("No se encontró la asignacion para editar: perfilId=$perfilId, menuId=$menuId");
            header('Location: ?route=perfilesmenus/listar');
            exit;
        }

        $errorMessage = null;
        $perfilmenu = [
            'perfilid'         => $perfilId,
            'menuid'           => $menuId,
            'perfilmenuactivo' => $registro['perfilmenuactivo'] ?? 0,
            'perfiles_perfildesc' => $registro['perfiles_perfildesc'] ?? '',
            'menus_menudesc'      => $registro['menus_menudesc'] ?? '',
        ];

        $viewFile = $this->viewPath('perfilesmenus_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $perfilId = isset($_POST['perfilid']) ? (int)$_POST['perfilid'] : 0;
        $menuId = isset($_POST['menuid']) ? (int)$_POST['menuid'] : 0;
        if ($perfilId <= 0 || $menuId <= 0) {
            header('Location: ?route=perfilesmenus/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $data['perfilmenuactivo'] = isset($_POST['perfilmenuactivo']) ? 1 : 0;

        try {
            $this->service->editarPerfilesmenu(
                $perfilId,
                $menuId,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Asignacion editada correctamente', 'success');
            header('Location: ?route=perfilesmenus/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $perfilmenu = [
                'perfilid'         => $perfilId,
                'menuid'           => $menuId,
                'perfilmenuactivo' => $data['perfilmenuactivo'] ?? 0,
            ];
            $viewFile = $this->viewPath('perfilesmenus_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $perfilId = isset($_POST['perfilid']) ? (int)$_POST['perfilid'] : 0;
        $menuId = isset($_POST['menuid']) ? (int)$_POST['menuid'] : 0;
        if ($perfilId > 0 && $menuId > 0) {
            try {
                $this->service->anularPerfilesmenu(
                    $perfilId,
                    $menuId,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Asignacion anulada correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }else {
            error_log("Parámetros inválidos para anular la asignacion: perfilId=$perfilId, menuId=$menuId");
            $this->setToast('Parámetros inválidos para anular la asignacion', 'danger');
        }

        header('Location: ?route=perfilesmenus/listar');
        exit;
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
