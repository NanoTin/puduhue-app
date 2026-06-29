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

    public function cargaMasivaForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();

        $temporadas = $this->service->listarTemporadasCompras();
        $formData = [];
        $preview = null;
        $errorMessage = null;

        require $this->viewPath('pptocompra_carga_masiva.php');
    }

    public function cargaMasivaPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $temporadas = $this->service->listarTemporadasCompras();
        $formData = $_POST;
        unset($formData['_token'], $formData['action'], $formData['route']);
        $preview = null;
        $errorMessage = null;

        try {
            $excelAction = (string)($_POST['excel_action'] ?? 'preview');
            $temporadaid = $this->normalizarEntero($_POST['temporadaid'] ?? null, 'Temporada');
            $temporada = $this->buscarTemporada($temporadas, $temporadaid);

            if ($excelAction === 'confirm') {
                $previewPayload = $this->decodePreviewPayload((string)($_POST['preview_payload'] ?? ''));
                $preview = $this->buildCargaMasivaPreview($previewPayload['rows'] ?? [], $temporada, $user);
                if (empty($preview['payloads'])) {
                    throw new \RuntimeException('No hay presupuestos nuevos para cargar. Todos los registros fueron omitidos.');
                }
                foreach ($preview['payloads'] as $payload) {
                    $this->service->crearPptocompra($payload, $user['usuarioId'], $user['dispositivo'], $user['ip']);
                }

                $this->setToast(
                    'Carga masiva realizada: ' . count($preview['payloads']) . ' presupuestos, ' . count($preview['detalle']) . ' líneas.',
                    'success'
                );
                header('Location: ?route=pptocompra/listar&filtroTemporadaid=' . urlencode((string)$temporadaid));
                exit;
            }

            if (!$this->isValidUpload($_FILES['pptocompra_excel'] ?? null)) {
                throw new \RuntimeException('Debe seleccionar un archivo Excel valido.');
            }

            $rows = $this->leerExcelPptocompra($_FILES['pptocompra_excel']['tmp_name']);
            $preview = $this->buildCargaMasivaPreview($rows, $temporada, $user);
            $formData['preview_payload'] = base64_encode(json_encode([
                'temporadaid' => $temporadaid,
                'rows' => $preview['rows'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
        }

        require $this->viewPath('pptocompra_carga_masiva.php');
    }

    private function buscarTemporada(array $temporadas, int $temporadaid): array
    {
        foreach ($temporadas as $temporada) {
            if ((int)($temporada['temporadaid'] ?? 0) === $temporadaid) {
                return $temporada;
            }
        }
        throw new \RuntimeException('Debe seleccionar una temporada valida.');
    }

    private function isValidUpload(?array $file): bool
    {
        return is_array($file)
            && (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)
            && !empty($file['tmp_name'])
            && is_uploaded_file($file['tmp_name']);
    }

    private function leerExcelPptocompra(string $filePath): array
    {
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            throw new \RuntimeException('No se encuentra disponible el lector de Excel.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        if (empty($sheetRows)) {
            throw new \RuntimeException('El archivo Excel no contiene datos.');
        }

        $headerRow = array_shift($sheetRows);
        $headerMap = $this->buildPptocompraHeaderMap($headerRow);
        $required = ['subfamiliacod', 'centrocostocod', 'ppoanio', 'ppomes', 'ppomontoppto'];
        foreach ($required as $field) {
            if (!isset($headerMap[$field])) {
                throw new \RuntimeException('El Excel debe contener columnas: Subfamilia Codigo, Centro Costo Codigo, Anio, Mes y Monto.');
            }
        }

        $rows = [];
        $excelRowNumber = 2;
        foreach ($sheetRows as $sheetRow) {
            $row = $this->buildPptocompraExcelRow($sheetRow, $headerMap, $excelRowNumber);
            if ($row !== null) {
                $rows[] = $row;
            }
            $excelRowNumber++;
        }

        if (empty($rows)) {
            throw new \RuntimeException('El Excel no contiene líneas de presupuesto para cargar.');
        }

        return $rows;
    }

    private function buildPptocompraHeaderMap(array $headerRow): array
    {
        $aliases = [
            'subfamiliacod' => ['subfamiliacodigo', 'subfamiliacod', 'codigosubfamilia', 'subfamilia'],
            'centrocostocod' => ['centrocostocodigo', 'centrocostocod', 'centrodecostocodigo', 'codigocentrocosto', 'centrocosto', 'cc', 'ceco'],
            'ppoanio' => ['anio', 'ano', 'year'],
            'ppomes' => ['mes', 'month'],
            'ppomontoppto' => ['monto', 'montoppto', 'ppomontoppto', 'presupuesto'],
            'ppoobservacion' => ['observacionmes', 'observacion', 'obs'],
        ];

        $map = [];
        foreach ($headerRow as $column => $label) {
            $normalized = $this->normalizeExcelHeader((string)$label);
            if ($normalized === '') {
                continue;
            }
            foreach ($aliases as $field => $fieldAliases) {
                if (in_array($normalized, $fieldAliases, true)) {
                    $map[$field] = $column;
                    break;
                }
            }
        }
        return $map;
    }

    private function normalizeExcelHeader(string $value): string
    {
        $value = strtr(mb_strtolower(trim($value), 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n',
        ]);
        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function buildPptocompraExcelRow(array $sheetRow, array $headerMap, int $excelRowNumber): ?array
    {
        $read = static function (string $field) use ($sheetRow, $headerMap) {
            $column = $headerMap[$field] ?? null;
            return $column !== null ? ($sheetRow[$column] ?? null) : null;
        };

        $subfamiliaCod = trim((string)$read('subfamiliacod'));
        $centrocostoCod = trim((string)$read('centrocostocod'));
        $anioRaw = $read('ppoanio');
        $mesRaw = $read('ppomes');
        $montoRaw = $read('ppomontoppto');
        $observacion = trim((string)$read('ppoobservacion'));

        if ($subfamiliaCod === '' && $centrocostoCod === '' && trim((string)$anioRaw) === '' && trim((string)$mesRaw) === '' && trim((string)$montoRaw) === '') {
            return null;
        }

        return [
            'excel_row' => $excelRowNumber,
            'subfamiliacod' => $subfamiliaCod,
            'centrocostocod' => $centrocostoCod,
            'ppoanio' => $this->parseExcelInt($anioRaw, 'Año', $excelRowNumber),
            'ppomes' => $this->parseExcelInt($mesRaw, 'Mes', $excelRowNumber),
            'ppomontoppto' => $this->parseExcelFloat($montoRaw, 'Monto', $excelRowNumber),
            'ppoobservacion' => $observacion,
        ];
    }

    private function parseExcelInt($value, string $field, int $rowNumber): int
    {
        $text = trim((string)$value);
        if ($text === '' || !is_numeric($text)) {
            throw new \RuntimeException("Fila {$rowNumber}: {$field} debe ser numérico.");
        }
        return (int)$text;
    }

    private function parseExcelFloat($value, string $field, int $rowNumber): float
    {
        $text = trim((string)$value);
        if (str_contains($text, ',') && str_contains($text, '.')) {
            $lastComma = strrpos($text, ',');
            $lastDot = strrpos($text, '.');
            $text = $lastComma > $lastDot
                ? str_replace(['.', ','], ['', '.'], $text)
                : str_replace(',', '', $text);
        } elseif (str_contains($text, ',')) {
            $text = str_replace(['.', ','], ['', '.'], $text);
        } elseif (substr_count($text, '.') >= 1) {
            $parts = explode('.', $text);
            $looksLikeThousands = count($parts) > 2 || (count($parts) === 2 && strlen(end($parts)) === 3 && strlen($parts[0]) <= 3);
            if ($looksLikeThousands) {
                $text = str_replace('.', '', $text);
            }
        }
        if ($text === '' || !is_numeric($text)) {
            throw new \RuntimeException("Fila {$rowNumber}: {$field} debe ser numérico.");
        }
        return (float)$text;
    }

    private function buildCargaMasivaPreview(array $rows, array $temporada, array $user): array
    {
        $subfamilias = $this->service->listarSubfamiliasFormSelect();
        $centroscosto = $this->service->listarCentroscostoFormSelect();
        $subfamiliasByCode = [];
        $centrosByCode = [];
        foreach ($subfamilias as $subfamilia) {
            $subfamiliasByCode[mb_strtoupper(trim((string)($subfamilia['subfamiliacod'] ?? '')), 'UTF-8')] = $subfamilia;
        }
        foreach ($centroscosto as $centro) {
            $centrosByCode[mb_strtoupper(trim((string)($centro['centrocostocod'] ?? '')), 'UTF-8')] = $centro;
        }

        $temporadaid = (int)($temporada['temporadaid'] ?? 0);
        $existingRows = $this->service->listarPptocompra(['filtroTemporadaid' => $temporadaid], $user['usuarioId'], $user['dispositivo'], $user['ip'])['rows'] ?? [];
        $existingCombos = [];
        foreach ($existingRows as $existing) {
            $existingCombos[(int)($existing['subfamiliaid'] ?? 0) . '|' . (int)($existing['centrocostoid'] ?? 0)] = $existing;
        }

        $temporadaInicio = new \DateTime((string)($temporada['temporadainicio'] ?? 'now'));
        $temporadaFin = new \DateTime((string)($temporada['temporadafin'] ?? 'now'));
        $seenPeriods = [];
        $detalle = [];
        $resumenSubfamilia = [];
        $resumenCentro = [];
        $omitidos = [];
        $total = 0.0;

        foreach ($rows as $row) {
            $excelRow = (int)($row['excel_row'] ?? 0);
            $subfamiliaKey = mb_strtoupper(trim((string)($row['subfamiliacod'] ?? '')), 'UTF-8');
            $centroKey = mb_strtoupper(trim((string)($row['centrocostocod'] ?? '')), 'UTF-8');
            $subfamilia = $subfamiliasByCode[$subfamiliaKey] ?? null;
            $centro = $centrosByCode[$centroKey] ?? null;

            if ($subfamilia === null) {
                throw new \RuntimeException("Fila {$excelRow}: Subfamilia no encontrada o inactiva ({$row['subfamiliacod']}).");
            }
            if ($centro === null) {
                throw new \RuntimeException("Fila {$excelRow}: Centro de costo no encontrado o inactivo ({$row['centrocostocod']}).");
            }
            if (($row['ppoanio'] ?? 0) < 2000 || ($row['ppoanio'] ?? 0) > 2200 || ($row['ppomes'] ?? 0) < 1 || ($row['ppomes'] ?? 0) > 12) {
                throw new \RuntimeException("Fila {$excelRow}: Periodo invalido.");
            }
            if (($row['ppomontoppto'] ?? 0) < 0) {
                throw new \RuntimeException("Fila {$excelRow}: El monto no puede ser negativo.");
            }

            $periodDate = \DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', (int)$row['ppoanio'], (int)$row['ppomes']));
            if (!$periodDate || $periodDate < $temporadaInicio || $periodDate > $temporadaFin) {
                throw new \RuntimeException("Fila {$excelRow}: Periodo fuera del rango de la temporada seleccionada.");
            }

            $comboKey = (int)$subfamilia['subfamiliaid'] . '|' . (int)$centro['centrocostoid'];
            if (isset($existingCombos[$comboKey])) {
                $existing = $existingCombos[$comboKey];
                $pptocompraidExistente = (int)($existing['pptocompraid'] ?? 0);
                $tieneTransacciones = $pptocompraidExistente > 0 ? $this->tieneMovimientos($pptocompraidExistente, $user) : false;
                $omitidos[] = [
                    'excel_row' => $excelRow,
                    'pptocompraid' => $pptocompraidExistente,
                    'subfamiliacod' => (string)$subfamilia['subfamiliacod'],
                    'subfamiliadsc' => (string)$subfamilia['subfamiliadsc'],
                    'centrocostocod' => (string)$centro['centrocostocod'],
                    'centrocostodsc' => (string)$centro['centrocostodsc'],
                    'ppoanio' => (int)$row['ppoanio'],
                    'ppomes' => (int)$row['ppomes'],
                    'ppomontoppto' => (float)$row['ppomontoppto'],
                    'motivo' => $tieneTransacciones
                        ? 'Ya existe presupuesto con transacciones registradas.'
                        : 'Ya existe presupuesto para esta temporada, subfamilia y centro de costo.',
                ];
                continue;
            }

            $periodKey = $comboKey . '|' . (int)$row['ppoanio'] . '|' . (int)$row['ppomes'];
            if (isset($seenPeriods[$periodKey])) {
                throw new \RuntimeException("Fila {$excelRow}: Existe un periodo repetido para la misma subfamilia y centro de costo.");
            }
            $seenPeriods[$periodKey] = true;

            $monto = (float)$row['ppomontoppto'];
            $total += $monto;
            $detalleRow = [
                'excel_row' => $excelRow,
                'subfamiliaid' => (int)$subfamilia['subfamiliaid'],
                'subfamiliacod' => (string)$subfamilia['subfamiliacod'],
                'subfamiliadsc' => (string)$subfamilia['subfamiliadsc'],
                'centrocostoid' => (int)$centro['centrocostoid'],
                'centrocostocod' => (string)$centro['centrocostocod'],
                'centrocostodsc' => (string)$centro['centrocostodsc'],
                'ppoanio' => (int)$row['ppoanio'],
                'ppomes' => (int)$row['ppomes'],
                'ppomontoppto' => $monto,
                'ppoobservacion' => (string)($row['ppoobservacion'] ?? ''),
            ];
            $detalle[] = $detalleRow;

            $subKey = (string)$detalleRow['subfamiliaid'];
            if (!isset($resumenSubfamilia[$subKey])) {
                $resumenSubfamilia[$subKey] = [
                    'subfamiliacod' => $detalleRow['subfamiliacod'],
                    'subfamiliadsc' => $detalleRow['subfamiliadsc'],
                    'total' => 0.0,
                ];
            }
            $resumenSubfamilia[$subKey]['total'] += $monto;

            $centroResumenKey = (string)$detalleRow['centrocostoid'];
            if (!isset($resumenCentro[$centroResumenKey])) {
                $resumenCentro[$centroResumenKey] = [
                    'centrocostocod' => $detalleRow['centrocostocod'],
                    'centrocostodsc' => $detalleRow['centrocostodsc'],
                    'total' => 0.0,
                    'subfamilias' => [],
                ];
            }
            $resumenCentro[$centroResumenKey]['total'] += $monto;
            if (!isset($resumenCentro[$centroResumenKey]['subfamilias'][$subKey])) {
                $resumenCentro[$centroResumenKey]['subfamilias'][$subKey] = [
                    'subfamiliacod' => $detalleRow['subfamiliacod'],
                    'subfamiliadsc' => $detalleRow['subfamiliadsc'],
                    'total' => 0.0,
                ];
            }
            $resumenCentro[$centroResumenKey]['subfamilias'][$subKey]['total'] += $monto;
        }

        return [
            'temporada' => $temporada,
            'total' => $total,
            'rows' => $rows,
            'detalle' => $detalle,
            'omitidos' => $omitidos,
            'resumenSubfamilia' => array_values($resumenSubfamilia),
            'resumenCentro' => array_values(array_map(static function (array $centro): array {
                $centro['subfamilias'] = array_values($centro['subfamilias']);
                return $centro;
            }, $resumenCentro)),
            'payloads' => $this->buildCargaMasivaPayloads($detalle, $temporadaid),
        ];
    }

    private function buildCargaMasivaPayloads(array $detalle, int $temporadaid): array
    {
        $payloads = [];
        foreach ($detalle as $line) {
            $key = (int)$line['subfamiliaid'] . '|' . (int)$line['centrocostoid'];
            if (!isset($payloads[$key])) {
                $payloads[$key] = [
                    'temporadaid' => $temporadaid,
                    'subfamiliaid' => (int)$line['subfamiliaid'],
                    'centrocostoid' => (int)$line['centrocostoid'],
                    'pptocompraobservacion' => 'Carga masiva Excel',
                    'mensual' => [],
                ];
            }
            $payloads[$key]['mensual'][] = [
                'ppoanio' => (int)$line['ppoanio'],
                'ppomes' => (int)$line['ppomes'],
                'ppomontoppto' => (float)$line['ppomontoppto'],
                'ppoobservacion' => (string)$line['ppoobservacion'],
            ];
        }
        return array_values($payloads);
    }

    private function decodePreviewPayload(string $payload): array
    {
        if ($payload === '') {
            throw new \RuntimeException('No hay datos leidos desde Excel para confirmar.');
        }
        $decoded = base64_decode($payload, true);
        $data = $decoded !== false ? json_decode($decoded, true) : null;
        if (!is_array($data) || empty($data['rows']) || !is_array($data['rows'])) {
            throw new \RuntimeException('Los datos de previsualizacion no son validos. Vuelva a leer el Excel.');
        }
        return $data;
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
