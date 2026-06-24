<?php

class ProdlechetiposController
{
    private \ProdlechetiposService $service;
    private \InvitemsService $serviceInvitems;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ProdlechetiposService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        $serviceInvitemsPath = dirname(__DIR__, 2) . '/Services/InvitemsService.php';
        if (file_exists($serviceInvitemsPath)) {
            require_once $serviceInvitemsPath;
        }

        $this->service = new \ProdlechetiposService();
        $this->serviceInvitems = new \InvitemsService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroProdlechetipodsc'  => $_GET['filtroProdlechetipodsc'] ?? null,
            'filtroInvitemid'         => $_GET['filtroInvitemid'] ?? null,
            'filtroProdlecheventa'    => $_GET['filtroProdlecheventa'] ?? null,
            'filtroProdlecheactivo'   => $_GET['filtroProdlecheactivo'] ?? null,
        ];

        $result = $this->service->listarProdlechetipos(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $prodlechetipos = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('prodlechetipos_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $invitemsOptions = $this->serviceInvitems->listarInvitemsFormSelect(1, null, 1, 'LCH');

        $viewFile = $this->viewPath('prodlechetipos_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearProdlechetipo(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Tipo de leche creado exitosamente.', 'success');
            header('Location: ?route=prodlechetipos/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('prodlechetipos_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?route=prodlechetipos/listar');
            exit;
        }

        $result = $this->service->consultarProdlechetipoPorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $tipo = $result['rows'][0] ?? null;
        if ($tipo === null) {
            $this->setToast('Tipo de leche no encontrado.', 'warning');
            header('Location: ?route=prodlechetipos/listar');
            exit;
        }

        $errorMessage = null;

        $invitemsOptions = $this->serviceInvitems->listarInvitemsFormSelect(1, null, 1, 'LCH');

        $viewFile = $this->viewPath('prodlechetipos_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['prodlechetipoid']) ? (int)$_POST['prodlechetipoid'] : 0;
        if ($id <= 0) {
            $this->setToast('Tipo de leche no válido.', 'warning');
            header('Location: ?route=prodlechetipos/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarProdlechetipo(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Tipo de leche editado exitosamente.', 'success');
            header('Location: ?route=prodlechetipos/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarProdlechetipoPorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $tipo = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('prodlechetipos_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['prodlechetipoid']) ? (int)$_POST['prodlechetipoid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularProdlechetipo(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Tipo de leche anulado correctamente', 'success');
            } catch (RuntimeException $e) {
                $errorMessage = $e->getMessage();
                $this->setToast($errorMessage, 'danger');
            }
        }

        header('Location: ?route=prodlechetipos/listar');
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
