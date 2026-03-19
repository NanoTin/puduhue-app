<?php

/**
 * EmpresasController (Web)
 *
 * Controlador para las vistas internas de Empresas.
 * Depende de:
 *   - EmpresasService
 *   - AuthMiddleware
 *
 * No hace lógica de negocio, solo:
 *   - lee GET/POST,
 *   - pide contexto de usuario,
 *   - llama al service,
 *   - carga la vista correspondiente.
 */
class EmpresasController
{
    private \EmpresasService $service;
    private \UsuariosempresasService $usuariosempresasService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/EmpresasService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \EmpresasService();

        $usuariosEmpresasPath = dirname(__DIR__, 2) . '/Services/UsuariosempresasService.php';
        if (file_exists($usuariosEmpresasPath)) {
            require_once $usuariosEmpresasPath;
        }

        $this->usuariosempresasService = new \UsuariosempresasService();
    }

    /**
     * Listar empresas (vista principal)
     */
    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroRazonsocial'    => $_GET['filtroRazonsocial'] ?? null,
            'filtroEmpresarut'     => $_GET['filtroEmpresarut'] ?? null,
            'filtroEmpresaactivo'  => $_GET['filtroEmpresaactivo'] ?? null,
        ];

        $result = $this->service->listarEmpresas(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $empresas = $result['rows'] ?? [];
        $meta     = $result['meta'] ?? null;

        // Variables disponibles en la vista:
        // $empresas, $meta, $filtros, $partial
        $viewFile = $this->viewPath('empresas_listar.php');
        require $viewFile;
    }

    /**
     * Mostrar formulario de creación
     */
    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        // podrías precargar combos, etc., si lo necesitas
        $errorMessage = null;
        $viewFile = $this->viewPath('empresas_crear.php');
        require $viewFile;
    }

    /**
     * Procesar POST de creación
     */
    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        // En caso de tener un token CSRF en el futuro, aquí se valida y luego se hace unset
        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->crearEmpresa(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            // Redirigir al listado
            $this->setToast('Empresa creada correctamente', 'success');
            header('Location: ?route=empresas/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('empresas_crear.php');
            require $viewFile;
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $empresaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($empresaId <= 0) {
            header('Location: ?route=empresas/listar');
            exit;
        }

        $result = $this->service->consultarEmpresaPorId(
            $empresaId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $empresa = $result['rows'][0] ?? null;
        if ($empresa === null) {
            // Si no existe, volver al listado
            header('Location: ?route=empresas/listar');
            exit;
        }

        $errorMessage = null;
        $viewFile = $this->viewPath('empresas_editar.php');
        require $viewFile;
    }

    /**
     * Procesar POST de edición
     */
    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $empresaId = isset($_POST['empresaid']) ? (int)$_POST['empresaid'] : 0;
        if ($empresaId <= 0) {
            header('Location: ?route=empresas/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $this->service->editarEmpresa(
                $empresaId,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Empresa editada correctamente', 'success');
            header('Location: ?route=empresas/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            // Volver a cargar datos actuales desde BD para el formulario
            $result = $this->service->consultarEmpresaPorId(
                $empresaId,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $empresa = $result['rows'][0] ?? null;

            $viewFile = $this->viewPath('empresas_editar.php');
            require $viewFile;
        }
    }

    /**
     * Procesar anulación (POST)
     */
    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $empresaId = isset($_POST['empresaid']) ? (int)$_POST['empresaid'] : 0;
        if ($empresaId <= 0) {
            header('Location: ?route=empresas/listar');
            exit;
        }

        try {
            $this->service->anularEmpresa(
                $empresaId,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Empresa anulada correctamente', 'success');
        } catch (RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=empresas/listar');
        exit;
    }

    public function listForChange(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = (int)($user['usuarioId'] ?? 0);
        if ($usuarioId <= 0) {
            $this->jsonResponse(['status' => 401, 'message' => 'Sesion invalida.'], 401);
            return;
        }

        $empresas = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, (string)$usuarioId);
        $this->jsonResponse([
            'status' => 200,
            'message' => 'OK',
            'data' => $empresas,
            'currentEmpresaId' => (int)($user['empresaId'] ?? 0),
        ]);
    }

    public function cambiarEmpresaPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = (int)($user['usuarioId'] ?? 0);
        if ($usuarioId <= 0) {
            $this->jsonResponse(['status' => 401, 'message' => 'Sesion invalida.'], 401);
            return;
        }

        $empresaId = isset($_POST['empresaid']) ? (int)$_POST['empresaid'] : 0;
        if ($empresaId <= 0) {
            $this->jsonResponse(['status' => 400, 'message' => 'Empresa invalida.'], 400);
            return;
        }

        $empresas = $this->usuariosempresasService->listarEmpresasPorUsuarioFormSelect(1, (string)$usuarioId);
        $valid = false;
        foreach ($empresas as $empresa) {
            if ((int)($empresa['empresaid'] ?? 0) === $empresaId) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $this->jsonResponse(['status' => 403, 'message' => 'Empresa no autorizada.'], 403);
            return;
        }

        $_SESSION['empresaIdSession'] = $empresaId;
        $this->jsonResponse(['status' => 200, 'message' => 'Empresa actualizada.']);
    }

    /**
     * Helper para resolver la ruta del archivo de vista.
     */
    private function viewPath(string $fileName): string
    {
        // /src/Controllers/Web -> subir 3 niveles -> raíz del proyecto
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

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
