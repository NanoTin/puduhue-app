<?php

class InvcateganimalController
{
    private \InvcateganimalService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/InvcateganimalService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \InvcateganimalService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroInvcateganimaldsc'    => $_GET['filtroInvcateganimaldsc'] ?? null,
            'filtroErpinvcateganimalcod' => $_GET['filtroErpinvcateganimalcod'] ?? null,
            'filtroInvcateganimalactivo' => $_GET['filtroInvcateganimalactivo'] ?? null,
        ];

        $result = $this->service->listarInvcateganimal(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $invcateganimal = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('invcateganimal_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;
        $viewFile = $this->viewPath('invcateganimal_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearInvcateganimal(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Categoría de animal creada correctamente', 'success');
            header('Location: ?route=invcateganimal/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('invcateganimal_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?route=invcateganimal/listar');
            exit;
        }

        $result = $this->service->consultarInvcateganimalPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $categoria = $result['rows'][0] ?? null;
        if ($categoria === null) {
            $this->setToast('Categoría de animal no encontrada.', 'warning');
            header('Location: ?route=invcateganimal/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('invcateganimal_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invcateganimalid']) ? (int)$_POST['invcateganimalid'] : 0;
        if ($id <= 0) {
            $this->setToast('Categoría de animal no encontrada.', 'warning');
            header('Location: ?route=invcateganimal/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarInvcateganimal(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Categoría de animal editada correctamente', 'success');
            header('Location: ?route=invcateganimal/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarInvcateganimalPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $categoria = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('invcateganimal_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invcateganimalid']) ? (int)$_POST['invcateganimalid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularInvcateganimal(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Categoría de animal anulada correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=invcateganimal/listar');
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
