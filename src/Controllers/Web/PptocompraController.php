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
            'filtroPptocompraactivo' => $_GET['filtroPptocompraactivo'] ?? null,
        ];

        $result = $this->service->listarPptocompra($filtros, $user['usuarioId'], $user['dispositivo'], $user['ip']);

        $pptocompra = $result['rows'] ?? [];
        $temporadas = $this->service->listarTemporadasCompras(0);
        $subfamilias = $this->service->listarSubfamiliasFormSelect();
        $centroscosto = $this->service->listarCentroscostoFormSelect();

        require $this->viewPath('pptocompra_listar.php');
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();

        $formData = [];
        $errorMessage = null;
        $temporadas = $this->service->listarTemporadasCompras();
        $subfamilias = $this->service->listarSubfamiliasFormSelect();
        $centroscosto = $this->service->listarCentroscostoFormSelect();

        require $this->viewPath('pptocompra_crear.php');
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $formData = $_POST;
        unset($formData['_token'], $formData['action'], $formData['route']);

        try {
            $payload = $this->normalizarPayloadCrear($this->buildPayloadFromPost($_POST));
            $this->validarPayload($payload, true);
            $this->service->crearPptocompra($payload, $user['usuarioId'], $user['dispositivo'], $user['ip']);

            $this->setToast('Presupuesto creado correctamente.', 'success');
            header('Location: ?route=pptocompra/listar');
            exit;
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $temporadas = $this->service->listarTemporadasCompras();
            $subfamilias = $this->service->listarSubfamiliasFormSelect();
            $centroscosto = $this->service->listarCentroscostoFormSelect();
            require $this->viewPath('pptocompra_crear.php');
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

        $pptocompra = $this->service->consultarPptocompraPorId($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        if ($pptocompra === null) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $mensual = $this->service->listarPptocompraMensual($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        $temporadas = $this->service->listarTemporadasCompras();
        $subfamilias = $this->service->listarSubfamiliasFormSelect();
        $centroscosto = $this->service->listarCentroscostoFormSelect();
        $errorMessage = null;
        $hasMovimientos = $this->tieneMovimientos($pptocompraid, $user);

        require $this->viewPath('pptocompra_editar.php');
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
            $payload = $this->normalizarPayloadCrear($this->buildPayloadFromPost($_POST));
            $payload['pptocompraid'] = $pptocompraid;
            $this->validarPayload($payload, true);
            $this->service->actualizarPptocompra($payload, $user['usuarioId'], $user['dispositivo'], $user['ip']);

            $this->setToast('Presupuesto actualizado correctamente.', 'success');
            header('Location: ?route=pptocompra/listar');
            exit;
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $pptocompra = $this->service->consultarPptocompraPorId($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            $mensual = $this->service->listarPptocompraMensual($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            $temporadas = $this->service->listarTemporadasCompras();
            $subfamilias = $this->service->listarSubfamiliasFormSelect();
            $centroscosto = $this->service->listarCentroscostoFormSelect();
            $formData['pptocompraid'] = $pptocompraid;
            $hasMovimientos = $this->tieneMovimientos($pptocompraid, $user);
            require $this->viewPath('pptocompra_editar.php');
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
            $this->service->anularPptocompra($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
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

        $pptocompra = $this->service->consultarPptocompraPorId($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        if ($pptocompra === null) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $mensual = $this->service->listarPptocompraMensual($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        $filtroTipo = $_GET['filtroTipo'] ?? '';
        $movimientos = $this->service->listarPptocompraMovimientosConSaldos($pptocompraid, $filtroTipo, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        $detalleResumen = $this->buildDetalleResumen($pptocompra, $mensual, $movimientos);

        require $this->viewPath('pptocompra_detalle.php');
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

        $pptocompra = $this->service->consultarPptocompraPorId($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        if ($pptocompra === null) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }
        if (empty($pptocompra['pptocompraactivo'])) {
            $this->setToast('No se puede ajustar un presupuesto no vigente.', 'warning');
            header('Location: ?route=pptocompra/detalle&pptocompraid=' . urlencode((string)$pptocompraid));
            exit;
        }

        $tiposAjuste = $this->service->listarTiposMovimientoActivo();
        $errorMessage = null;
        $formData = [];
        require $this->viewPath('pptocompra_ajustar.php');
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
            $this->service->ajustarPptocompra($payload, $user['usuarioId'], $user['dispositivo'], $user['ip']);

            $this->setToast('Ajuste aplicado correctamente.', 'success');
            header('Location: ?route=pptocompra/detalle&pptocompraid=' . $pptocompraid);
            exit;
        } catch (\RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
            $errorMessage = $e->getMessage();
            $pptocompra = $this->service->consultarPptocompraPorId($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            $tiposAjuste = $this->service->listarTiposMovimientoActivo();
            require $this->viewPath('pptocompra_ajustar.php');
        }
    }

    public function traspasarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $pptocompraid = isset($_GET['pptocompraid']) ? (int)$_GET['pptocompraid'] : 0;
        if ($pptocompraid <= 0) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $pptocompra = $this->service->consultarPptocompraPorId($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        if ($pptocompra === null) {
            header('Location: ?route=pptocompra/listar');
            exit;
        }
        if (empty($pptocompra['pptocompraactivo'])) {
            $this->setToast('No se puede traspasar desde un presupuesto anulado.', 'danger');
            header('Location: ?route=pptocompra/listar');
            exit;
        }

        $errorMessage = null;
        $formData = [];
        $presupuestosDestino = $this->service->listarPptocompraDestinoTraspaso($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        require $this->viewPath('pptocompra_traspasar.php');
    }

    public function traspasarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $post = $_POST;
        $formData = $post;
        unset($formData['_token'], $formData['action'], $formData['route']);
        $pptocompraid = isset($post['pptocompraidOrigen']) ? (int)$post['pptocompraidOrigen'] : 0;

        try {
            if ($pptocompraid <= 0) {
                throw new \RuntimeException('Debe seleccionar un presupuesto origen.');
            }
            $payload = [
                'pptocompraidOrigen' => $pptocompraid,
                'pptocompraidDestino' => $this->normalizarEntero($post['pptocompraidDestino'] ?? null, 'PPTO destino'),
                'ppoanio' => $this->normalizarEntero($post['ppoanio'] ?? null, 'Año'),
                'ppomes' => $this->normalizarEntero($post['ppomes'] ?? null, 'Mes'),
                'pptocompramonto' => $this->normalizarDecimal($post['pptocompramonto'] ?? null, 'Monto'),
                'pptocompramotivo' => trim((string)($post['pptocompramotivo'] ?? '')),
                'pptocompregenciaorigen' => trim((string)($post['pptocompregenciaorigen'] ?? '')),
                'pptocomprareflinea' => trim((string)($post['pptocomprareflinea'] ?? '')),
                'pptocompregruppomovimiento' => trim((string)($post['pptocompregruppomovimiento'] ?? '')),
            ];
            $this->validarTraspaso($payload);
            $this->service->traspasarPptocompra($payload, $user['usuarioId'], $user['dispositivo'], $user['ip']);

            $this->setToast('Traspaso registrado correctamente.', 'success');
            header('Location: ?route=pptocompra/detalle&pptocompraid=' . $pptocompraid);
            exit;
        } catch (\RuntimeException $e) {
            $this->setToast($e->getMessage(), 'danger');
            $errorMessage = $e->getMessage();
            $pptocompra = $this->service->consultarPptocompraPorId($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip']);
            $presupuestosDestino = $pptocompraid > 0
                ? $this->service->listarPptocompraDestinoTraspaso($pptocompraid, $user['usuarioId'], $user['dispositivo'], $user['ip'])
                : [];
            require $this->viewPath('pptocompra_traspasar.php');
        }
    }

    private function buildPayloadFromPost(array $post): array
    {
        return [
            'temporadaid' => $post['temporadaid'] ?? null,
            'subfamiliaid' => $post['subfamiliaid'] ?? null,
            'centrocostoid' => $post['centrocostoid'] ?? null,
            'pptocompraobservacion' => trim((string)($post['pptocompraobservacion'] ?? '')),
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

    private function validarTraspaso(array $payload): void
    {
        if (($payload['pptocompraidOrigen'] ?? 0) <= 0) {
            throw new \RuntimeException('PPTO origen inválido.');
        }
        if (($payload['pptocompraidDestino'] ?? 0) <= 0) {
            throw new \RuntimeException('PPTO destino inválido.');
        }
        if (($payload['pptocompraidOrigen'] ?? 0) === ($payload['pptocompraidDestino'] ?? 0)) {
            throw new \RuntimeException('El origen y destino deben ser diferentes.');
        }
        if (($payload['ppoanio'] ?? 0) < 2000 || ($payload['ppoanio'] ?? 0) > 2200) {
            throw new \RuntimeException('Año inválido.');
        }
        if (($payload['ppomes'] ?? 0) < 1 || ($payload['ppomes'] ?? 0) > 12) {
            throw new \RuntimeException('Mes inválido.');
        }
        if (($payload['pptocompramonto'] ?? 0) <= 0) {
            throw new \RuntimeException('El monto del traspaso debe ser mayor a 0.');
        }
        if (($payload['pptocompramotivo'] ?? '') === '') {
            throw new \RuntimeException('La justificación del traspaso es obligatoria.');
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
        $texto = str_replace(',', '.', trim((string)$valor));
        if (!is_numeric($texto)) {
            throw new \RuntimeException($campo . ' debe ser numérico.');
        }
        return (float)$texto;
    }

    private function tieneMovimientos(int $pptocompraid, array $user): bool
    {
        $movimientos = $this->service->listarPptocompraMovimientos($pptocompraid, null, $user['usuarioId'], $user['dispositivo'], $user['ip']);
        foreach ($movimientos as $movimiento) {
            if (($movimiento['pptocompratransacciontipoid'] ?? '') !== 'PPTO_CARGA') {
                return true;
            }
        }
        return false;
    }

    private function buildDetalleResumen(array $pptocompra, array $mensual, array $movimientos): array
    {
        $periodos = [];
        foreach ($mensual as $line) {
            $periodo = sprintf('%04d-%02d', (int)($line['ppoanio'] ?? 0), (int)($line['ppomes'] ?? 0));
            if ($periodo === '0000-00') {
                continue;
            }
            if (!isset($periodos[$periodo])) {
                $periodos[$periodo] = ['periodo' => $periodo, 'presupuestado' => 0.0, 'consumo' => 0.0];
            }
            $periodos[$periodo]['presupuestado'] += (float)($line['ppomontoppto'] ?? 0);
        }
        foreach ($movimientos as $mov) {
            $periodo = (string)($mov['ppoanomes'] ?? sprintf('%04d-%02d', (int)($mov['ppoanio'] ?? 0), (int)($mov['ppomes'] ?? 0)));
            if ($periodo === '0000-00' || $periodo === '') {
                continue;
            }
            if (!isset($periodos[$periodo])) {
                $periodos[$periodo] = ['periodo' => $periodo, 'presupuestado' => 0.0, 'consumo' => 0.0];
            }
            $periodos[$periodo]['consumo'] += (float)($mov['pptocompramontoencurso'] ?? 0) + (float)($mov['pptocompramontoconfirmado'] ?? 0);
        }
        ksort($periodos);
        $acumPresupuesto = 0.0;
        $acumConsumo = 0.0;
        foreach ($periodos as &$periodo) {
            $acumPresupuesto += $periodo['presupuestado'];
            $periodo['consumo'] = abs($periodo['consumo']);
            $acumConsumo += $periodo['consumo'];
            $periodo['presupuestado_acum'] = $acumPresupuesto;
            $periodo['consumo_acum'] = $acumConsumo;
        }
        unset($periodo);

        $ajustePositivo = (float)($pptocompra['ajustespositivos'] ?? 0);
        $ajusteNegativo = (float)($pptocompra['ajustesnegativos'] ?? 0);
        $consumoPendiente = (float)($pptocompra['consumosencurso'] ?? 0);
        $consumoConfirmado = (float)($pptocompra['consumosconfirmados'] ?? 0);
        $reproyectado = (float)($pptocompra['reproyectado'] ?? 0);
        $disponible = (float)($pptocompra['saldodisponible'] ?? 0);

        return [
            'presupuestado' => (float)($pptocompra['presupuestado'] ?? 0),
            'ajustado' => $ajustePositivo + $ajusteNegativo,
            'ajuste_positivo' => $ajustePositivo,
            'ajuste_negativo' => $ajusteNegativo,
            'reproyectado' => $reproyectado,
            'consumido' => abs($consumoPendiente + $consumoConfirmado),
            'consumo_pendiente' => $consumoPendiente,
            'consumo_confirmado' => $consumoConfirmado,
            'disponible' => $disponible,
            'disponible_pct' => $reproyectado > 0 ? ($disponible / $reproyectado) * 100 : 0,
            'series' => array_values($periodos),
        ];
    }

    private function viewPath(string $fileName): string
    {
        return dirname(__DIR__, 3) . '/apps/web-php/' . $fileName;
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        require_once dirname(__DIR__, 2) . '/Helpers/FlashMessageHelper.php';
        FlashMessageHelper::toast($message, $type);
    }
}
