<?php

class InvunidmedController
{
    private \InvunidmedService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/InvunidmedService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \InvunidmedService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroInvunidmeddsc'    => $_GET['filtroInvunidmeddsc'] ?? null,
            'filtroErpunidmedcod'    => $_GET['filtroErpunidmedcod'] ?? null,
            'filtroInvunidmedactivo' => $_GET['filtroInvunidmedactivo'] ?? null,
        ];

        $result = $this->service->listarInvunidmed(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $invunidmed = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('invunidmed_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;
        $viewFile = $this->viewPath('invunidmed_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $data['invunidmedactivo'] = isset($_POST['invunidmedactivo']) ? 1 : 0;

        try {
            $this->service->crearInvunidmed(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Unidad de medida creada correctamente', 'success');
            header('Location: ?route=invunidmed/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('invunidmed_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            error_log('InvunidmedController::editarForm - ID inválido. ID: ' . $id);
            header('Location: ?route=invunidmed/listar');
            exit;
        }

        $result = $this->service->consultarInvunidmedPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $registro = $result['rows'][0] ?? null;
        if ($registro === null) {
            error_log('InvunidmedController::editarForm - Registro no encontrado. ID: ' . $id);
            header('Location: ?route=invunidmed/listar');
            exit;
        }

        $invunidmed = $registro;
        $errorMessage = null;
        $viewFile = $this->viewPath('invunidmed_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invunidmedid']) ? (int)$_POST['invunidmedid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=invunidmed/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $data['invunidmedactivo'] = isset($_POST['invunidmedactivo']) ? 1 : 0;

        try {
            $this->service->editarInvunidmed(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Unidad de medida editada correctamente', 'success');
            header('Location: ?route=invunidmed/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $result = $this->service->consultarInvunidmedPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $invunidmed = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('invunidmed_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invunidmedid']) ? (int)$_POST['invunidmedid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularInvunidmed(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Unidad de medida anulada correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=invunidmed/listar');
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
