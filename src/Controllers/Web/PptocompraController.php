<?php

class PptocompraController
{
    private \PptocompraService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/PptocompraService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \PptocompraService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroPptocompraid' => $_GET['filtroPptocompraid'] ?? null,
            'filtroTemporadaid' => $_GET['filtroTemporadaid'] ?? null,
            'filtroSubfamiliaid' => $_GET['filtroSubfamiliaid'] ?? null,
            'filtroCentrocostoid' => $_GET['filtroCentrocostoid'] ?? null,
            'filtroTemporadatipo' => $_GET['filtroTemporadatipo'] ?? null,
            'filtroPptocompraactivo' => $_GET['filtroPptocompraactivo'] ?? null,
        ];

        $result = $this->service->listarPptocompra(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $pptocompra = $result['rows'] ?? [];
        $temporadas = $this->service->listarTemporadasCompras();
        $subfamilias = $this->service->listarSubfamiliasFormSelect();
        $centroscosto = $this->service->listarCentroscostoFormSelect();

        $viewFile = $this->viewPath('pptocompra_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $formData = [];
        $errorMessage = null;
        $temporadas = $this->service->listarTemporadasCompras();
        $subfamilias = $this->service->listarSubfamiliasFormSelect();
        $centroscosto = $this->service->listarCentroscostoFormSelect();

        $viewFile = $this->viewPath('pptocompra_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $formData = $_POST;
        unset($formData['_token'], $formData['action'], $formData['route']);

        try {
            $payload = $this->buildPayloadFromPost($_POST);
            $payload = $this->normalizarPayloadCrear($payload);
            $this->validarPayload($payload, true);

            $this->service->crearPptocompra(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Presupuesto creado correctamente.', 'success');
            header('Location: ?route=pptocompra/listar');
            exit;
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $temporadas = $this->service->listarTemporadasCompras();
            $subfamilias = $this->service->listarSubfamiliasFormSelect();
            $centroscosto = $this->service->listarCentroscostoFormSelect();

            $viewFile = $this->viewPath('pptocompra_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $pptocompraid = isset($_GET['pptocompraid']) ? (int)$_GET['pptocompraid'] : 0;
        if ($pptocompraid <= 0) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $pptocompra = $this->service->consultarPptocompraPorId(
            $pptocompraid,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        if ($pptocompra === null) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $mensual = $this->service->listarPptocompraMensual(
            $pptocompraid,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $temporadas = $this->service->listarTemporadasCompras();
        $subfamilias = $this->service->listarSubfamiliasFormSelect();
        $centroscosto = $this->service->listarCentroscostoFormSelect();
        $errorMessage = null;
        $hasMovimientos = $this->tieneMovimientos($pptocompraid, $user);

        $viewFile = $this->viewPath('pptocompra_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $pptocompraid = isset($_POST['pptocompraid']) ? (int)$_POST['pptocompraid'] : 0;
        if ($pptocompraid <= 0) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $formData = $_POST;
        unset($formData['_token'], $formData['action'], $formData['route']);

        try {
            $payload = $this->buildPayloadFromPost($_POST);
            $payload['pptocompraid'] = $pptocompraid;
            $payload = $this->normalizarPayloadCrear($payload);
            $this->validarPayload($payload, true);

            $this->service->actualizarPptocompra(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Presupuesto actualizado correctamente.', 'success');
            header('Location: ?route=pptocompra/listar');
            exit;
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');

            $pptocompra = $this->service->consultarPptocompraPorId(
                $pptocompraid,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $mensual = $this->service->listarPptocompraMensual(
                $pptocompraid,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $temporadas = $this->service->listarTemporadasCompras();
            $subfamilias = $this->service->listarSubfamiliasFormSelect();
            $centroscosto = $this->service->listarCentroscostoFormSelect();
            $formData['pptocompraid'] = $pptocompraid;
            $hasMovimientos = $this->tieneMovimientos($pptocompraid, $user);

            $viewFile = $this->viewPath('pptocompra_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $pptocompraid = isset($_POST['pptocompraid']) ? (int)$_POST['pptocompraid'] : 0;
        if ($pptocompraid <= 0) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        try {
            $this->service->anularPptocompra(
                $pptocompraid,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Presupuesto anulado correctamente.', 'success');
        } catch (\RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=pptocompra/listar');
        exit;
    }

    public function detalle(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $pptocompraid = isset($_GET['pptocompraid']) ? (int)$_GET['pptocompraid'] : 0;
        if ($pptocompraid <= 0) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $pptocompra = $this->service->consultarPptocompraPorId(
            $pptocompraid,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        if ($pptocompra === null) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $mensual = $this->service->listarPptocompraMensual(
            $pptocompraid,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $filtroTipo = $_GET['filtroTipo'] ?? '';
        $movimientos = $this->service->listarPptocompraMovimientos(
            $pptocompraid,
            $filtroTipo,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $viewFile = $this->viewPath('pptocompra_detalle.php');
        require $viewFile;
    }

    public function ajustarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $pptocompraid = isset($_GET['pptocompraid']) ? (int)$_GET['pptocompraid'] : 0;
        if ($pptocompraid <= 0) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $pptocompra = $this->service->consultarPptocompraPorId(
            $pptocompraid,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );
        if ($pptocompra === null) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $tiposAjuste = $this->service->listarTiposMovimientoActivo();
        $errorMessage = null;
        $formData = [];

        $viewFile = $this->viewPath('pptocompra_ajustar.php');
        require $viewFile;
    }

    public function ajustarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $post = $_POST;
        $formData = $post;
        unset($formData['_token'], $formData['action'], $formData['route']);
        $pptocompraid = isset($post['pptocompraid']) ? (int)$post['pptocompraid'] : 0;

        try {
            if ($pptocompraid <= 0) {
                throw new \RuntimeException('Debe seleccionar un presupuesto.');
            }

            $payload = [
                'pptocompraid' => $pptocompraid,
                'ppoanio' => $this->normalizarEntero($post['ppoanio'] ?? null, 'Año'),
                'ppomes' => $this->normalizarEntero($post['ppomes'] ?? null, 'Mes'),
                'pptocompramonto' => $this->normalizarDecimal($post['pptocompramonto'] ?? null, 'Monto'),
                'pptocompratransacciontipoid' => (string)($post['pptocompratransacciontipoid'] ?? ''),
                'pptocompramotivo' => trim((string)($post['pptocompramotivo'] ?? '')),
                'pptocompregenciaorigen' => trim((string)($post['pptocompregenciaorigen'] ?? '')),
                'pptocomprareflinea' => trim((string)($post['pptocomprareflinea'] ?? '')),
                'pptocompregruppomovimiento' => trim((string)($post['pptocompregruppomovimiento'] ?? '')),
            ];

            $this->validarAjuste($payload);

            $this->service->ajustarPptocompra(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );

            $this->setToast('Ajuste aplicado correctamente.', 'success');
            header('Location: ?route=pptocompra/detalle&pptocompraid=' . $pptocompraid);
            exit;
        } catch (\RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
            $errorMessage = $e->getMessage();
            $pptocompra = $this->service->consultarPptocompraPorId(
                $pptocompraid,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $tiposAjuste = $this->service->listarTiposMovimientoActivo();
            $viewFile = $this->viewPath('pptocompra_ajustar.php');
            require $viewFile;
        }
    }

    private function buildPayloadFromPost(array $post): array
    {
        return [
            'temporadaid' => $post['temporadaid'] ?? null,
            'subfamiliaid' => $post['subfamiliaid'] ?? null,
            'centrocostoid' => $post['centrocostoid'] ?? null,
            'mensual' => $this->normalizarMensualRows($post['mensual'] ?? null),
        ];
    }

    private function normalizarMensualRows(?array $mensualRows): array
    {
        if (!is_array($mensualRows)) {
            return [];
        }

        $normalized = [];
        $seen = [];
        foreach ($mensualRows as $line) {
            if (!is_array($line)) {
                continue;
            }

            $anio = $this->normalizarEntero($line['ppoanio'] ?? null, 'Año', true);
            $mes = $this->normalizarEntero($line['ppomes'] ?? null, 'Mes', true);
            $monto = $this->normalizarDecimal($line['ppomontoppto'] ?? null, 'Monto', true);

            if ($anio === null && $mes === null && $monto === null) {
                continue;
            }

            if ($anio === null || $mes === null || $monto === null) {
                throw new \RuntimeException('Revisión mensual incompleta. Debe indicar año, mes y monto.');
            }

            if ($anio < 2000 || $anio > 2200 || $mes < 1 || $mes > 12) {
                throw new \RuntimeException('Periodo mensual inválido.');
            }

            if (isset($seen["{$anio}-{$mes}"])) {
                throw new \RuntimeException('Existe un periodo mensual repetido.');
            }

            $seen["{$anio}-{$mes}"] = true;
            $normalized[] = [
                'ppoanio' => $anio,
                'ppomes' => $mes,
                'ppomontoppto' => $monto,
                'ppoobservacion' => trim((string)($line['ppoobservacion'] ?? '')),
            ];
        }

        return $normalized;
    }

    private function normalizarPayloadCrear(array $payload): array
    {
        $payload['temporadaid'] = $this->normalizarEntero($payload['temporadaid'] ?? null, 'Temporada');
        $payload['subfamiliaid'] = $this->normalizarEntero($payload['subfamiliaid'] ?? null, 'Subfamilia');
        $payload['centrocostoid'] = $this->normalizarEntero($payload['centrocostoid'] ?? null, 'Centro de costo');

        if (!isset($payload['mensual']) || !is_array($payload['mensual']) || empty($payload['mensual'])) {
            throw new \RuntimeException('Debe definir al menos un período mensual.');
        }

        return $payload;
    }

    private function validarPayload(array $payload, bool $checkMensual = true): void
    {
        if (($payload['temporadaid'] ?? 0) <= 0) {
            throw new \RuntimeException('Debe seleccionar temporada de presupuesto compras.');
        }

        if (($payload['subfamiliaid'] ?? 0) <= 0) {
            throw new \RuntimeException('Debe seleccionar subfamilia de item.');
        }

        if (($payload['centrocostoid'] ?? 0) <= 0) {
            throw new \RuntimeException('Debe seleccionar centro de costo.');
        }

        if ($checkMensual && (empty($payload['mensual']) || !is_array($payload['mensual']))) {
            throw new \RuntimeException('Debe definir al menos un período mensual.');
        }
    }

    private function validarAjuste(array $payload): void
    {
        if (($payload['pptocompraid'] ?? 0) <= 0) {
            throw new \RuntimeException('Presupuesto no válido.');
        }

        if (($payload['ppoanio'] ?? 0) < 2000 || ($payload['ppoanio'] ?? 0) > 2200) {
            throw new \RuntimeException('Año inválido.');
        }

        if (($payload['ppomes'] ?? 0) < 1 || ($payload['ppomes'] ?? 0) > 12) {
            throw new \RuntimeException('Mes inválido.');
        }

        if (($payload['pptocompramonto'] ?? 0) <= 0) {
            throw new \RuntimeException('Debe ingresar monto mayor a 0.');
        }

        if (!in_array($payload['pptocompratransacciontipoid'], ['PPTO_AJUSTE_POS', 'PPTO_AJUSTE_NEG'], true)) {
            throw new \RuntimeException('Tipo de ajuste inválido.');
        }

        if (trim((string)$payload['pptocompramotivo']) === '') {
            throw new \RuntimeException('El motivo del ajuste es obligatorio.');
        }
    }

    private function normalizarEntero($valor, string $campo, bool $nuloSiVacio = false): ?int
    {
        if ($valor === null || trim((string)$valor) === '') {
            return $nuloSiVacio ? null : 0;
        }

        if (!is_numeric((string)$valor)) {
            throw new \RuntimeException($campo . ' debe ser numérico.');
        }

        return (int)$valor;
    }

    private function normalizarDecimal($valor, string $campo, bool $nuloSiVacio = false): ?float
    {
        if ($valor === null || trim((string)$valor) === '') {
            return $nuloSiVacio ? null : 0.0;
        }

        $texto = (string)$valor;
        $texto = str_replace(',', '.', trim($texto));
        if (!is_numeric($texto)) {
            throw new \RuntimeException($campo . ' debe ser numérico.');
        }

        return (float)$texto;
    }

    private function tieneMovimientos(int $pptocompraid, array $user): bool
    {
        $movimientos = $this->service->listarPptocompraMovimientos(
            $pptocompraid,
            null,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        return count($movimientos) > 0;
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
