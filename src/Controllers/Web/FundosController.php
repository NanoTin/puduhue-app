<?php

class FundosController
{
    private \FundosService $service;
    private \FundostiposService $ServiceFundosTipos;
    private \EmpresasService $serviceEmpresas;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/FundosService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $serviceFundosTiposPath = dirname(__DIR__, 2) . '/Services/FundostiposService.php';
        if (file_exists($serviceFundosTiposPath)) {
            require_once $serviceFundosTiposPath;
        }

        $serviceEmpresasPath = dirname(__DIR__, 2) . '/Services/EmpresasService.php';
        if (file_exists($serviceEmpresasPath)) {
            require_once $serviceEmpresasPath;
        }

        $this->service = new \FundosService();
        $this->ServiceFundosTipos = new \FundostiposService();
        $this->serviceEmpresas = new \EmpresasService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroFundonombre'   => $_GET['filtroFundonombre'] ?? null,
            'filtroFundotipoid'   => $_GET['filtroFundotipoid'] ?? null,
            'filtroEmpresaid'     => $_GET['filtroEmpresaid'] ?? null,
            'filtroFundopabco'    => $_GET['filtroFundopabco'] ?? null,
            'filtroFundoactivo'   => $_GET['filtroFundoactivo'] ?? null,
        ];

        $result = $this->service->listarFundos(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $fundos = $result['rows'] ?? [];
        $meta   = $result['meta'] ?? null;

        $viewFile = $this->viewPath('fundos_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;

        $fundostiposOptions = $this->ServiceFundosTipos->listarFundosTiposFormSelect();
        $empresasOptions = $this->serviceEmpresas->listarEmpresasFormSelect();

        $viewFile = $this->viewPath('fundos_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearFundo(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Fundo creado correctamente', 'success');
            header('Location: ?route=fundos/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('fundos_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($fundoId <= 0) {
            header('Location: ?route=fundos/listar');
            exit;
        }

        $fundostiposOptions = $this->ServiceFundosTipos->listarFundosTiposFormSelect();
        $empresasOptions = $this->serviceEmpresas->listarEmpresasFormSelect();

        $result = $this->service->consultarFundoPorId(
            $fundoId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $fundo = $result['rows'][0] ?? null;
        if ($fundo === null) {
            header('Location: ?route=fundos/listar');
            exit;
        }

        $errorMessage = null;

        $viewFile = $this->viewPath('fundos_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundoId = isset($_POST['fundoid']) ? (int)$_POST['fundoid'] : 0;
        if ($fundoId <= 0) {
            header('Location: ?route=fundos/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarFundo(
                $fundoId,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Fundo editado correctamente', 'success');
            header('Location: ?route=fundos/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $result = $this->service->consultarFundoPorId(
                $fundoId,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $fundo = $result['rows'][0] ?? null;

            $viewFile = $this->viewPath('fundos_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundoId = isset($_POST['fundoid']) ? (int)$_POST['fundoid'] : 0;
        if ($fundoId > 0) {
            try {
                $this->service->anularFundo(
                    $fundoId,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Fundo anulado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=fundos/listar');
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
