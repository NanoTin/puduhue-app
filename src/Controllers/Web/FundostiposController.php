<?php

class FundostiposController
{
    private \FundostiposService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/FundostiposService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \FundostiposService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = ['filtroFundotipodsc' => $_GET['filtroFundotipodsc'] ?? null];

        $result = $this->service->listarFundostipos(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $fundostipos = $result['rows'] ?? [];
        $meta       = $result['meta'] ?? null;

        $viewFile = $this->viewPath('fundostipos_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;
        $viewFile = $this->viewPath('fundostipos_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearFundotipo(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Tipo de fundo creado correctamente', 'success');
            header('Location: ?route=fundostipos/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('fundostipos_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundotipoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($fundotipoId <= 0) {
            header('Location: ?route=fundostipos/listar');
            exit;
        }

        $result = $this->service->consultarFundotipoPorId(
            $fundotipoId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $fundotipo = $result['rows'][0] ?? null;
        if ($fundotipo === null) {
            header('Location: ?route=fundostipos/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('fundostipos_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fundotipoId = isset($_POST['fundotipoid']) ? (int)$_POST['fundotipoid'] : 0;
        if ($fundotipoId <= 0) {
            header('Location: ?route=fundostipos/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarFundotipo(
                $fundotipoId,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Tipo de fundo editado correctamente', 'success');
            header('Location: ?route=fundostipos/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarFundotipoPorId(
                $fundotipoId,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $fundotipo = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('fundostipos_editar.php');
            require $viewFile;
        }
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
