<?php

class FundosestanquesclientesController
{
    private \FundosestanquesclientesService $service;
    private \FundosestanquesService $fundosestanquesService;
    private \ClientesService $clientesService;
    private \FundosService $fundosService;

    public function __construct()
    {
        $servicePathFundosestanquesclientes = dirname(__DIR__, 2) . '/Services/FundosestanquesclientesService.php';
        if (file_exists($servicePathFundosestanquesclientes)) {
            require_once $servicePathFundosestanquesclientes;
        }

        $servicePathFundosestanques = dirname(__DIR__, 2) . '/Services/FundosestanquesService.php';
        if (file_exists($servicePathFundosestanques)) {
            require_once $servicePathFundosestanques;
        }

        $servicePathClientes = dirname(__DIR__, 2) . '/Services/ClientesService.php';
        if (file_exists($servicePathClientes)) {
            require_once $servicePathClientes;
        }

        $servicePathFundos = dirname(__DIR__, 2) . '/Services/FundosService.php';
        if (file_exists($servicePathFundos)) {
            require_once $servicePathFundos;
        }

        $this->service = new \FundosestanquesclientesService();
        $this->fundosestanquesService = new \FundosestanquesService();
        $this->clientesService = new \ClientesService();
        $this->fundosService = new \FundosService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroClienteid'       => $_GET['filtroClienteid'] ?? null,
            'filtroFundoId'         => $_GET['filtroFundoId'] ?? null,
            'filtroFndestcliactivo' => $_GET['filtroFndestcliactivo'] ?? null,
        ];

        $result = $this->service->listarFundosestanquesclientes(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $fundosestanquesclientes = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $fundosOptions = $this->fundosService->listarFundosFormSelect(null, $user['empresaId']);
        $clientesOptions = $this->clientesService->listarClientesFormSelect(null);

        $viewFile = $this->viewPath('fundosestanquesclientes_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $fundosestanquesOptions = $this->fundosestanquesService->listarFundosestanquesFormSelect(1);
        $clientesOptions = $this->clientesService->listarClientesFormSelect(1);

        $viewFile = $this->viewPath('fundosestanquesclientes_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearFundosestanquescliente(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Asociacion creada correctamente', 'success');
            header('Location: ?route=fundosestanquesclientes/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $fundosestanquesOptions = $this->fundosestanquesService->listarFundosestanquesFormSelect(1);
            $clientesOptions = $this->clientesService->listarClientesFormSelect(1);

            $viewFile = $this->viewPath('fundosestanquesclientes_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundoestanqueid = isset($_GET['fundoestanqueid']) ? (int)$_GET['fundoestanqueid'] : 0;
        $clienteid = isset($_GET['clienteid']) ? (int)$_GET['clienteid'] : 0;

        if ($fundoestanqueid <= 0 || $clienteid <= 0) {
            header('Location: ?route=fundosestanquesclientes/listar');
            exit;
        }

        $result = $this->service->consultarFundosestanquesclientePorId(
            $fundoestanqueid,
            $clienteid,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $fundosestanquescliente = $result['rows'][0] ?? null;
        if ($fundosestanquescliente === null) {
            header('Location: ?route=fundosestanquesclientes/listar');
            exit;
        }

        $errorMessage = null;
        $fundosestanquesOptions = $this->fundosestanquesService->listarFundosestanquesFormSelect(1);
        $clientesOptions = $this->clientesService->listarClientesFormSelect(1);

        $viewFile = $this->viewPath('fundosestanquesclientes_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundoestanqueid = isset($_POST['fundoestanqueid']) ? (int)$_POST['fundoestanqueid'] : 0;
        $clienteid = isset($_POST['clienteid']) ? (int)$_POST['clienteid'] : 0;

        if ($fundoestanqueid <= 0 || $clienteid <= 0) {
            header('Location: ?route=fundosestanquesclientes/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarFundosestanquescliente(
                $fundoestanqueid,
                $clienteid,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Asociacion editada correctamente', 'success');
            header('Location: ?route=fundosestanquesclientes/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $result = $this->service->consultarFundosestanquesclientePorId(
                $fundoestanqueid,
                $clienteid,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $fundosestanquescliente = $result['rows'][0] ?? null;

            $fundosestanquesOptions = $this->fundosestanquesService->listarFundosestanquesFormSelect(1);
            $clientesOptions = $this->clientesService->listarClientesFormSelect(1);

            $viewFile = $this->viewPath('fundosestanquesclientes_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundoestanqueid = isset($_POST['fundoestanqueid']) ? (int)$_POST['fundoestanqueid'] : 0;
        $clienteid = isset($_POST['clienteid']) ? (int)$_POST['clienteid'] : 0;

        if ($fundoestanqueid > 0 && $clienteid > 0) {
            try {
                $this->service->anularFundosestanquescliente(
                    $fundoestanqueid,
                    $clienteid,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Asociacion anulada correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=fundosestanquesclientes/listar');
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
            'type' => $type,
        ];
    }
}
