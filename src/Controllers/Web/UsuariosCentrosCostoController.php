<?php

class UsuariosCentrosCostoController
{
    private \UsuariosCentrosCostoService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/UsuariosCentrosCostoService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \UsuariosCentrosCostoService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroUsuarioid' => $_GET['filtroUsuarioid'] ?? null,
            'filtroCentrocostoid' => $_GET['filtroCentrocostoid'] ?? null,
            'filtroActivo' => $_GET['filtroActivo'] ?? 1,
        ];

        $result = $this->service->listarAsignaciones(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $asignaciones = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;
        $usuariosOptions = $this->service->listarUsuariosFormSelect(1);
        $centrosOptions = $this->service->listarCentrosCostoFormSelect(1);
        $errorMessage = null;

        require $this->viewPath('usuarioscentroscosto_listar.php');
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();

        $formData = [
            'usuarioid' => '',
            'centrocostoid' => '',
            'usucendefault' => 0,
        ];
        $usuariosOptions = $this->service->listarUsuariosFormSelect(1);
        $centrosOptions = $this->service->listarCentrosCostoFormSelect(1);
        $errorMessage = null;

        require $this->viewPath('usuarioscentroscosto_crear.php');
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $formData = $_POST;
        unset($formData['_token'], $formData['action'], $formData['route']);

        try {
            $this->validarPayloadCrear($formData);
            $result = $this->service->crearAsignacion(
                $formData,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast((string)($result['message'] ?? 'Asignacion guardada correctamente.'), 'success');
            header('Location: ?route=usuarios-centros-costo/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $usuariosOptions = $this->service->listarUsuariosFormSelect(1);
            $centrosOptions = $this->service->listarCentrosCostoFormSelect(1);
            require $this->viewPath('usuarioscentroscosto_crear.php');
        }
    }

    public function editarForm(bool $partial = false): void
    {
        header('Location: ?route=usuarios-centros-costo/listar');
        exit;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['route']);

        try {
            $this->validarPayloadAccion($data);
            $result = $this->service->actualizarAsignacion(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast((string)($result['message'] ?? 'Asignacion actualizada correctamente.'), 'success');
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=usuarios-centros-costo/listar');
        exit;
    }

    private function validarPayloadCrear(array $data): void
    {
        if ((int)($data['usuarioid'] ?? 0) <= 0) {
            throw new RuntimeException('Debe seleccionar un usuario.');
        }

        if ((int)($data['centrocostoid'] ?? 0) <= 0) {
            throw new RuntimeException('Debe seleccionar un centro de costo.');
        }
    }

    private function validarPayloadAccion(array $data): void
    {
        if ((int)($data['usucenid'] ?? 0) <= 0) {
            throw new RuntimeException('Debe informar una asignacion valida.');
        }

        $accion = trim((string)($data['accion'] ?? ''));
        if (!in_array($accion, ['activar', 'desactivar', 'marcar_default'], true)) {
            throw new RuntimeException('La accion solicitada no es valida.');
        }
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        require_once dirname(__DIR__, 2) . '/Helpers/FlashMessageHelper.php';
        FlashMessageHelper::toast($message, $type);
    }

    private function viewPath(string $fileName): string
    {
        return dirname(__DIR__, 3) . '/apps/web-php/' . $fileName;
    }
}
