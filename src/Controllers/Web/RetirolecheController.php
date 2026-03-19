<?php

class RetirolecheController
{
    private \RetirolecheService $service;
    private \UsuariosfundosService $usuariosfundosService;
    private \FundosestanquesclientesService $fundosestanquesclientesService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/RetirolecheService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $usuariosfundosPath = dirname(__DIR__, 2) . '/Services/UsuariosfundosService.php';
        if (file_exists($usuariosfundosPath)) {
            require_once $usuariosfundosPath;
        }

        $fundosestanquesclientesPath = dirname(__DIR__, 2) . '/Services/FundosestanquesclientesService.php';
        if (file_exists($fundosestanquesclientesPath)) {
            require_once $fundosestanquesclientesPath;
        }

        $this->service = new \RetirolecheService();
        $this->usuariosfundosService = new \UsuariosfundosService();
        $this->fundosestanquesclientesService = new \FundosestanquesclientesService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroFundoid'           => $_GET['filtroFundoid'] ?? null,
            'filtroFundoestanqueid'   => $_GET['filtroFundoestanqueid'] ?? null,
            'filtroClienteid'         => $_GET['filtroClienteid'] ?? null,
            'filtroRetirolechestatus' => $_GET['filtroRetirolechestatus'] ?? null,
            'filtroFechaDesde'        => $_GET['filtroFechaDesde'] ?? null,
            'filtroFechaHasta'        => $_GET['filtroFechaHasta'] ?? null,
        ];

        $result = $this->service->listarRetiroleche(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $retiroleche = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);

        $viewFile = $this->viewPath('retiroleche_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $errorMessage = null;

        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
        $fundosestanquesclientesOptions = $this->fundosestanquesclientesService->listarFundosestanquesclientesFormSelect(1);
        $fundoIdWS = $user['fundoId'] ?? 0;

        $viewFile = $this->viewPath('retiroleche_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $data['empresaid'] = $user['empresaId'];

        $uploadedFile = $_FILES['retirolechefoto'] ?? null;
        if (!$uploadedFile || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errorMessage = 'Debe adjuntar una imagen valida.';
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $fundosestanquesclientesOptions = $this->fundosestanquesclientesService->listarFundosestanquesclientesFormSelect(1);
            $formData = $data;
            $viewFile = $this->viewPath('retiroleche_crear.php');
            require $viewFile;
            return;
        }

        $newFileName = $this->buildImageFilename($uploadedFile);
        if ($newFileName === null) {
            $errorMessage = 'La imagen debe ser un archivo valido (jpg, jpeg, png, gif, webp, heic).';
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $fundosestanquesclientesOptions = $this->fundosestanquesclientesService->listarFundosestanquesclientesFormSelect(1);
            $formData = $data;
            $viewFile = $this->viewPath('retiroleche_crear.php');
            require $viewFile;
            return;
        }
        $data['retirolechefoto'] = $newFileName;

        try {
            $result = $this->service->crearRetiroleche(
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $retirolecheId = (int)($result['retirolecheid'] ?? 0);
            if ($retirolecheId <= 0) {
                throw new RuntimeException('No se pudo obtener el ID del registro.');
            }

            $this->guardarImagenRetiroleche($retirolecheId, $uploadedFile, $newFileName);

            header('Location: ?route=retiroleche/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $fundosestanquesclientesOptions = $this->fundosestanquesclientesService->listarFundosestanquesclientesFormSelect(1);
            $formData = $data;
            $viewFile = $this->viewPath('retiroleche_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?route=retiroleche/listar');
            exit;
        }

        $result = $this->service->consultarRetirolechePorId(
            $id,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        $retiro = $result['rows'][0] ?? null;
        if ($retiro === null) {
            header('Location: ?route=retiroleche/listar');
            exit;
        }

        $errorMessage = null;
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
        $fundosestanquesclientesOptions = $this->fundosestanquesclientesService->listarFundosestanquesclientesFormSelect(1);
        $viewFile = $this->viewPath('retiroleche_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['retirolecheid']) ? (int)$_POST['retirolecheid'] : 0;
        if ($id <= 0) {
            header('Location: ?route=retiroleche/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);

        $uploadedFile = $_FILES['retirolechefoto'] ?? null;
        $newFileName = null;
        $currentFileName = $_POST['retirolechefoto_actual'] ?? '';
        if ($uploadedFile && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $newFileName = $this->buildImageFilename($uploadedFile);
            if ($newFileName === null) {
                $errorMessage = 'La imagen debe ser un archivo valido (jpg, jpeg, png, gif, webp, heic).';
                $result = $this->service->consultarRetirolechePorId(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $retiro = $result['rows'][0] ?? null;
                $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
                $fundosestanquesclientesOptions = $this->fundosestanquesclientesService->listarFundosestanquesclientesFormSelect(1);
                $viewFile = $this->viewPath('retiroleche_editar.php');
                require $viewFile;
                return;
            }
            $data['retirolechefoto'] = $newFileName;
        } else {
            $data['retirolechefoto'] = $currentFileName;
        }

        try {
            $this->service->editarRetiroleche(
                $id,
                $data,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            if ($uploadedFile && $newFileName !== null) {
                $this->guardarImagenRetiroleche($id, $uploadedFile, $newFileName);
                if ($currentFileName !== '' && $currentFileName !== $newFileName) {
                    $this->eliminarImagenRetiroleche($id, $currentFileName);
                }
            }

            header('Location: ?route=retiroleche/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $result = $this->service->consultarRetirolechePorId(
                $id,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $retiro = $result['rows'][0] ?? null;
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $fundosestanquesclientesOptions = $this->fundosestanquesclientesService->listarFundosestanquesclientesFormSelect(1);
            $viewFile = $this->viewPath('retiroleche_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $id = isset($_POST['retirolecheid']) ? (int)$_POST['retirolecheid'] : 0;
        if ($id > 0) {
            try {
                $this->service->anularRetiroleche(
                    $id,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
            } catch (RuntimeException $e) {
                // opcional
            }
        }

        header('Location: ?route=retiroleche/listar');
        exit;
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }

    private function buildImageFilename(array $file): ?string
    {
        $name = $file['name'] ?? '';
        if ($name === '') {
            return null;
        }
        $tmpName = $file['tmp_name'] ?? '';
        if ($tmpName === '' || !is_file($tmpName)) {
            return null;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }
        $mime = $this->detectMimeType($tmpName);
        $allowedMimeByExt = [
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'heic' => ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'],
        ];
        if ($mime !== null && !in_array($mime, $allowedMimeByExt[$ext], true)) {
            return null;
        }
        if ($ext !== 'heic' && @getimagesize($tmpName) === false) {
            return null;
        }
        $uuid = $this->uuidV4();
        return $uuid . '.' . $ext;
    }

    private function guardarImagenRetiroleche(int $retirolecheId, array $file, string $fileName): void
    {
        $baseDir = dirname(__DIR__, 3) . '/apps/web-php/uploads/retiroleche/img/' . $retirolecheId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('No se pudo crear el directorio de imagenes.');
        }
        $destino = $baseDir . '/' . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new RuntimeException('No se pudo guardar la imagen en el servidor.');
        }
    }

    private function eliminarImagenRetiroleche(int $retirolecheId, string $fileName): void
    {
        $path = dirname(__DIR__, 3) . '/apps/web-php/uploads/retiroleche/img/' . $retirolecheId . '/' . $fileName;
        if ($fileName !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    private function detectMimeType(string $path): ?string
    {
        if (!function_exists('finfo_open')) {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        return is_string($mime) && $mime !== '' ? $mime : null;
    }
}
