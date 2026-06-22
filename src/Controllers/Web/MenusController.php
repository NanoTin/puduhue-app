<?php

class MenusController
{
    private \MenusService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/MenusService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \MenusService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroMenupadre'  => $_GET['filtroMenupadre'] ?? null,
            'filtroMenudesc'   => $_GET['filtroMenudesc'] ?? null,
            'filtroMenuactivo' => $_GET['filtroMenuactivo'] ?? null,
        ];

        $result = $this->service->listarMenus(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $menus = $result['rows'] ?? [];
        $meta  = $result['meta'] ?? null;

        $viewFile = $this->viewPath('menus_listar.php');
        require $viewFile;
    }

    public function padresPorNivel(): void
    {
        AuthMiddleware::requireAuth();

        $nivel = isset($_GET['nivel']) ? (int)$_GET['nivel'] : 0;

        // Seguridad mínima: niveles válidos (padres son 0..2 si tus niveles son 1..3)
        if ($nivel < 0) { $nivel = 0; }
        if ($nivel > 2) { $nivel = 2; }

        $rows = $this->service->listarMenusPadreNivel($nivel);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'rows' => $rows
        ]);
        exit;
    }


    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $menunivel = isset($_GET['menunivel']) ? (int)$_GET['menunivel'] : 1;
        if ($menunivel < 1) { $menunivel = 1; }
        if ($menunivel > 3) { $menunivel = 3; }

        $menusPadreOptions = $this->service->listarMenusPadreNivel($menunivel - 1);

        $viewFile = $this->viewPath('menus_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearMenu(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Menú creado correctamente', 'success');
            header('Location: ?route=menus/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            
            $menunivel = isset($_POST['menunivel']) ? (int)$_POST['menunivel'] : 1;
            if ($menunivel < 1) { $menunivel = 1; }
            if ($menunivel > 3) { $menunivel = 3; }

            $menusPadreOptions = $this->service->listarMenusPadreNivel($menunivel - 1);

            $viewFile = $this->viewPath('menus_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $menuId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($menuId <= 0) {
            header('Location: ?route=menus/listar');
            exit;
        }

        $result = $this->service->consultarMenuPorId(
            $menuId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $menu = $result['rows'][0] ?? null;
        if ($menu === null) {
            header('Location: ?route=menus/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('menus_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $menuId = isset($_POST['menuid']) ? (int)$_POST['menuid'] : 0;
        if ($menuId <= 0) {
            header('Location: ?route=menus/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        $data['menupadre'] = isset($_POST['menupadre']) && (int)$_POST['menupadre'] !== 0 ? (int)$_POST['menupadre'] : null;

        try {
            $this->service->editarMenu(
                $menuId,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Menú editado correctamente', 'success');
            header('Location: ?route=menus/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $result = $this->service->consultarMenuPorId(
                $menuId,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $menu = $result['rows'][0] ?? null;

            $viewFile = $this->viewPath('menus_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $menuId = isset($_POST['menuid']) ? (int)$_POST['menuid'] : 0;
        if ($menuId > 0) {
            try {
                $this->service->anularMenu(
                    $menuId,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Menú anulado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=menus/listar');
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
