<?php

class ClientesController
{
    private \ClientesService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ClientesService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \ClientesService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroClienterut'        => $_GET['filtroClienterut'] ?? null,
            'filtroClienterazonsocial'=> $_GET['filtroClienterazonsocial'] ?? null,
            'filtroClienteemail'      => $_GET['filtroClienteemail'] ?? null,
            'filtroClienteactivo'     => $_GET['filtroClienteactivo'] ?? null,
        ];

        $result = $this->service->listarClientes(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $clientes = $result['rows'] ?? [];
        $meta     = $result['meta'] ?? null;

        $viewFile = $this->viewPath('clientes_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;
        $viewFile = $this->viewPath('clientes_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearCliente(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Cliente creado correctamente', 'success');
            header('Location: ?route=clientes/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('clientes_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $clienteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($clienteId <= 0) {
            header('Location: ?route=clientes/listar');
            exit;
        }

        $result = $this->service->consultarClientePorId(
            $clienteId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $cliente = $result['rows'][0] ?? null;
        if ($cliente === null) {
            header('Location: ?route=clientes/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('clientes_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $clienteId = isset($_POST['clienteid']) ? (int)$_POST['clienteid'] : 0;
        if ($clienteId <= 0) {
            header('Location: ?route=clientes/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarCliente(
                $clienteId,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Cliente actualizado correctamente', 'success');
            header('Location: ?route=clientes/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $result = $this->service->consultarClientePorId(
                $clienteId,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $cliente = $result['rows'][0] ?? null;
            $viewFile = $this->viewPath('clientes_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $clienteId = isset($_POST['clienteid']) ? (int)$_POST['clienteid'] : 0;
        if ($clienteId > 0) {
            try {
                $this->service->anularCliente(
                    $clienteId,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Cliente anulado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=clientes/listar');
        exit;
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        $_SESSION['toast'] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }
}
