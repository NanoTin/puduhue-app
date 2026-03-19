<?php

class FundosestanquesController
{
    private \FundosestanquesService $service;
    private \FundosService $fundosService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/FundosestanquesService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        $servicePathFundos = dirname(__DIR__, 2) . '/Services/FundosService.php';
        if (file_exists($servicePathFundos)) {
            require_once $servicePathFundos;
        }

        $this->service = new \FundosestanquesService();
        $this->fundosService = new \FundosService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroFundoid'             => $_GET['filtroFundoid'] ?? null,
            'filtroFundoestanquedsc'    => $_GET['filtroFundoestanquedsc'] ?? null,
            'filtroEstanquemarcaid'     => $_GET['filtroEstanquemarcaid'] ?? null,
            'filtroFundoestanqueactivo' => $_GET['filtroFundoestanqueactivo'] ?? null,
        ];

        $result = $this->service->listarFundosestanques(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $fundosestanques = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('fundosestanques_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $errorMessage = null;

        $fundosOptions = $this->fundosService->listarFundosFormSelect(1, $user['empresaId']);

        $viewFile = $this->viewPath('fundosestanques_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearFundoestanque(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Estanque creado correctamente', 'success');
            header('Location: ?route=fundosestanques/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('fundosestanques_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundosOptions = $this->fundosService->listarFundosFormSelect(1, $user['empresaId']);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?route=fundosestanques/listar');
            exit;
        }

        $result = $this->service->consultarFundoestanquePorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $fundoestanque = $result['rows'][0] ?? null;
        if ($fundoestanque === null) {
            header('Location: ?route=fundosestanques/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('fundosestanques_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['fundoestanqueid']) ? (int)$_POST['fundoestanqueid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=fundosestanques/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarFundoestanque(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Estanque editado correctamente', 'success');
            header('Location: ?route=fundosestanques/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarFundoestanquePorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $fundoestanque = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('fundosestanques_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['fundoestanqueid']) ? (int)$_POST['fundoestanqueid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularFundoestanque(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Estanque anulado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=fundosestanques/listar');
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
