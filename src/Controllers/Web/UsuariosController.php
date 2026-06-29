<?php

class UsuariosController
{
    private \UsuariosService $service;
    private \PerfilesService $servicePerfiles;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/UsuariosService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $servicePerfilesPath = dirname(__DIR__, 2) . '/Services/PerfilesService.php';
        if (file_exists($servicePerfilesPath)) {
            require_once $servicePerfilesPath;
        }

        $this->service = new \UsuariosService();
        $this->servicePerfiles = new \PerfilesService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroUsuariorut'       => $_GET['filtroUsuariorut'] ?? null,
            'filtroUsuarionombre'    => $_GET['filtroUsuarionombre'] ?? null,
            'filtroUsuarioemail'     => $_GET['filtroUsuarioemail'] ?? null,
            'filtroPerfilid'         => $_GET['filtroPerfilid'] ?? null,
            'filtroUsuarioesadmin'   => $_GET['filtroUsuarioesadmin'] ?? null,
            'filtroUsuariobloqueado' => $_GET['filtroUsuariobloqueado'] ?? null,
            'filtroUsuarioactivo'    => $_GET['filtroUsuarioactivo'] ?? null,
        ];

        $result = $this->service->listarUsuarios(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $usuarios = $result['rows'] ?? [];
        $meta     = $result['meta'] ?? null;

        $viewFile = $this->viewPath('usuarios_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;
        $perfilesOptions = $this->servicePerfiles->listarPerfilesFormSelect(1);
        $formData = $this->defaultFormData();

        $viewFile = $this->viewPath('usuarios_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $password = $data['usuariopwd'] ?? '';
            $passwordConfirm = $data['usuariopwd2'] ?? '';
            if ($password !== $passwordConfirm) {
                throw new RuntimeException('La confirmacion de contrasena no coincide.');
            }
            unset($data['usuariopwd2']);

            if (isset($data['usuariorut'])) {
                $data['usuariorut'] = trim((string)$data['usuariorut']);
            }

            $this->service->crearUsuario(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Usuario creado correctamente', 'success');
            header('Location: ?route=usuarios/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $perfilesOptions = $this->servicePerfiles->listarPerfilesFormSelect(1);
            $formData = array_merge($this->defaultFormData(), $data);
            $viewFile = $this->viewPath('usuarios_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($usuarioId <= 0) {
            $this->setToast('ID de usuario inválido', 'danger');
            header('Location: ?route=usuarios/listar');
            exit;
        }

        $result = $this->service->consultarUsuarioPorId(
            $usuarioId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $usuario = $result['rows'][0] ?? null;
        if ($usuario === null) {
            $this->setToast('Usuario no encontrado', 'danger');
            header('Location: ?route=usuarios/listar');
            exit;
        }

        $errorMessage = null;
        $perfilesOptions = $this->servicePerfiles->listarPerfilesFormSelect(1);
        $viewFile = $this->viewPath('usuarios_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = isset($_POST['usuarioid']) ? (int)$_POST['usuarioid'] : 0;
        if ($usuarioId <= 0) {
            $this->setToast('ID de usuario inválido', 'danger');
            header('Location: ?route=usuarios/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        try {
            $password = $data['usuariopwd'] ?? '';
            $passwordConfirm = $data['usuariopwd2'] ?? '';
            if ($password !== $passwordConfirm) {
                throw new RuntimeException('La confirmacion de contrasena no coincide.');
            }
            unset($data['usuariopwd2']);

            $this->service->editarUsuario(
                $usuarioId,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Usuario editado correctamente', 'success');
            header('Location: ?route=usuarios/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $result = $this->service->consultarUsuarioPorId(
                $usuarioId,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $usuario = $result['rows'][0] ?? null;
            if ($usuario !== null) {
                $usuario = array_merge($usuario, $data);
            }
            $perfilesOptions = $this->servicePerfiles->listarPerfilesFormSelect(1);

            $viewFile = $this->viewPath('usuarios_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = isset($_POST['usuarioid']) ? (int)$_POST['usuarioid'] : 0;
        if ($usuarioId > 0) {
            try {
                $this->service->anularUsuario(
                    $usuarioId,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Usuario anulado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=usuarios/listar');
        exit;
    }

    public function cambioClaveForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = (int)($user['usuarioId'] ?? 0);
        if ($usuarioId <= 0) {
            $this->setToast('Sesion invalida.', 'danger');
            header('Location: ?route=dashboard/listar');
            exit;
        }

        $usuarioRut = $user['usuarioCod'] ?? '';
        $usuarioNombre = $user['usuarioNom'] ?? '';
        $errorMessage = null;

        $viewFile = $this->viewPath('usuarios_cambio_password.php');
        require $viewFile;
    }

    public function cambioClaveGuardar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $usuarioId = (int)($user['usuarioId'] ?? 0);
        if ($usuarioId <= 0) {
            $this->setToast('Sesion invalida.', 'danger');
            header('Location: ?route=dashboard/listar');
            exit;
        }

        $usuarioRut = $user['usuarioCod'] ?? '';
        $usuarioNombre = $user['usuarioNom'] ?? '';

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $postedUsuarioId = isset($_POST['usuarioid']) ? (int)$_POST['usuarioid'] : 0;
        if ($postedUsuarioId > 0 && $postedUsuarioId !== $usuarioId) {
            $errorMessage = 'Usuario invalido.';
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('usuarios_cambio_password.php');
            require $viewFile;
            return;
        }

        if ($password === '' || $passwordConfirm === '') {
            $errorMessage = 'Debe ingresar la nueva contrasena y su confirmacion.';
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('usuarios_cambio_password.php');
            require $viewFile;
            return;
        }

        if ($password !== $passwordConfirm) {
            $errorMessage = 'La confirmacion de contrasena no coincide.';
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('usuarios_cambio_password.php');
            require $viewFile;
            return;
        }

        try {
            $this->service->cambiarClave($usuarioId, $password, $user);
            $this->setToast('Contrasena actualizada correctamente.', 'success');
            header('Location: ?route=dashboard/listar');
            exit;
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('usuarios_cambio_password.php');
            require $viewFile;
        }
    }

    public function generarTokenApiPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $actorId = (int)($user['usuarioId'] ?? 0);
        if ($actorId <= 0) {
            $this->jsonResponse([
                'status' => 401,
                'message' => 'Sesion invalida.',
                'data' => [],
                'meta' => null,
            ], 401);
            return;
        }

        $usuarioId = isset($_POST['usuarioid']) ? (int)$_POST['usuarioid'] : 0;
        if ($usuarioId <= 0) {
            $this->jsonResponse([
                'status' => 400,
                'message' => 'Usuario invalido.',
                'data' => [],
                'meta' => null,
            ], 400);
            return;
        }

        $payload = [
            'tokennombre' => trim((string)($_POST['tokennombre'] ?? '')),
            'observacion' => trim((string)($_POST['observacion'] ?? '')),
            'sin_expiracion' => isset($_POST['sin_expiracion']) && (string)$_POST['sin_expiracion'] === '1',
            'dias_vigencia' => isset($_POST['dias_vigencia']) ? (int)$_POST['dias_vigencia'] : 30,
        ];

        try {
            $result = $this->service->generarTokenApi(
                $usuarioId,
                $payload,
                $actorId,
                $user['dispositivo'] ?? null,
                $user['ip'] ?? null
            );

            $this->jsonResponse([
                'status' => 200,
                'message' => 'Token API generado correctamente. Copielo ahora, no podra volver a visualizarse.',
                'data' => $result,
                'meta' => null,
            ]);
        } catch (\RuntimeException $e) {
            $this->jsonResponse([
                'status' => 400,
                'message' => $e->getMessage(),
                'data' => [],
                'meta' => null,
            ], 400);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'status' => 500,
                'message' => 'No se pudo generar el token API.',
                'data' => [],
                'meta' => null,
            ], 500);
        }
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

    private function defaultFormData(): array
    {
        return [
            'usuarioesadmin' => 0,
            'usuarioactivo' => 1,
            'usuariobloqueado' => 0,
            'usuariopermiteaprobreq' => 0,
            'usuariopermiteaprobpreoc' => 0,
            'usuariocomprador' => 0,
            'usuariopermiteanularpreoc' => 0,
            'usuariopermiteeditarprecios' => 0,
            'usuariopermitecrearitem' => 0,
            'usuariopermiteeditaritem' => 0,
            'usuariopermitesynctrnerp' => 0,
            'usuarioreqautorizadorfuerapptocompra' => 0,
            'usuarioreqautorizadorfuerapptocompraorden' => 0,
        ];
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
