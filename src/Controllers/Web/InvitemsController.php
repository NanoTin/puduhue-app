<?php

class InvitemsController
{
    private \InvitemsService $service;
    private \InvunidmedService $serviceInvunidmed;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/InvitemsService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        $serviceInvunidmedPath = dirname(__DIR__, 2) . '/Services/InvunidmedService.php';
        if (file_exists($serviceInvunidmedPath)) {
            require_once $serviceInvunidmedPath;
        }

        $this->service = new \InvitemsService();
        $this->serviceInvunidmed = new \InvunidmedService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroInvitemdsc'    => $_GET['filtroInvitemdsc'] ?? null,
            'filtroInvunidmedid'  => $_GET['filtroInvunidmedid'] ?? null,
            'filtroErpinvitemcod' => $_GET['filtroErpinvitemcod'] ?? null,
            'filtroInvitemleche'  => $_GET['filtroInvitemleche'] ?? null,
            'filtroInvitemactivo' => $_GET['filtroInvitemactivo'] ?? null,
        ];

        $result = $this->service->listarInvitems(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $invitems = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('invitems_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $invunidmedOptions = $this->serviceInvunidmed->listarInvunidmedFormSelect(1);

        $viewFile = $this->viewPath('invitems_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearInvitem(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Item de inventario creado correctamente', 'success');
            header('Location: ?route=invitems/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('invitems_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?route=invitems/listar');
            exit;
        }

        $result = $this->service->consultarInvitemPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $invitem = $result['rows'][0] ?? null;
        if ($invitem === null) {
            header('Location: ?route=invitems/listar');
            exit;
        }

        $errorMessage = null;
        $invunidmedOptions = $this->serviceInvunidmed->listarInvunidmedFormSelect(1);
        $viewFile = $this->viewPath('invitems_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invitemid']) ? (int)$_POST['invitemid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=invitems/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarInvitem(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Item de inventario editado correctamente', 'success');
            header('Location: ?route=invitems/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarInvitemPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $invitem = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('invitems_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['invitemid']) ? (int)$_POST['invitemid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularInvitem(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Item de inventario anulado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=invitems/listar');
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
