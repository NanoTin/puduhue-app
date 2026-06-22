<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PptolechemensualController
{
    private \PptolechemensualService $service;
    private \UsuariosfundosService $usuariosfundosService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/PptolechemensualService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $usuariosfundosPath = dirname(__DIR__, 2) . '/Services/UsuariosfundosService.php';
        if (file_exists($usuariosfundosPath)) {
            require_once $usuariosfundosPath;
        }

        $this->service = new \PptolechemensualService();
        $this->usuariosfundosService = new \UsuariosfundosService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroPptolecanio' => $_GET['filtroPptolecanio'] ?? null,
            'filtroPptolecmes'  => $_GET['filtroPptolecmes'] ?? null,
            'filtroFundoid'     => $_GET['filtroFundoid'] ?? null,
        ];

        $result = $this->service->listarPptolechemensual(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $pptolechemensual = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId'], 1);

        $viewFile = $this->viewPath('pptolechemensual_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();
        $errorMessage = null;
        $formData = [];

        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);

        $viewFile = $this->viewPath('pptolechemensual_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $uploadedFile = $_FILES['pptoleche_excel'] ?? null;
        if ($this->isValidUpload($uploadedFile)) {
            try {
                $rows = $this->leerExcelPptoleche($uploadedFile['tmp_name']);
                if (empty($rows)) {
                    throw new RuntimeException('No se encontraron filas validas en el Excel.');
                }

                $result = $this->service->cargarMasivaPptolechemensual(
                    $rows,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );

                $message = $result['message'] ?? 'Carga masiva realizada correctamente.';
                $this->setToast($message, 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }

            header('Location: ?route=pptolechemensual/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $formData = $data;

        try {
            $payload = $this->normalizarPayload($data);
            $this->service->crearPptolechemensual(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro creado correctamente', 'success');
            header('Location: ?route=pptolechemensual/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $viewFile = $this->viewPath('pptolechemensual_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : 0;
        $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : 0;
        $fundoId = isset($_GET['fundoid']) ? (int)$_GET['fundoid'] : 0;

        if ($anio <= 0 || $mes <= 0 || $fundoId <= 0) {
            header('Location: ?route=pptolechemensual/listar');
            exit;
        }

        $result = $this->service->consultarPptolechemensualPorPk(
            $anio,
            $mes,
            $fundoId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $registro = $result['rows'][0] ?? null;
        if ($registro === null) {
            header('Location: ?route=pptolechemensual/listar');
            exit;
        }

        $errorMessage = null;
        $formData = $registro;
        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);

        $viewFile = $this->viewPath('pptolechemensual_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $anio = isset($_POST['pptolecanio']) ? (int)$_POST['pptolecanio'] : 0;
        $mes = isset($_POST['pptolecmes']) ? (int)$_POST['pptolecmes'] : 0;
        $fundoId = isset($_POST['fundoid']) ? (int)$_POST['fundoid'] : 0;

        if ($anio <= 0 || $mes <= 0 || $fundoId <= 0) {
            header('Location: ?route=pptolechemensual/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $formData = $data;

        try {
            $payload = $this->normalizarPayload($data);
            $this->service->actualizarPptolechemensual(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro actualizado correctamente', 'success');
            header('Location: ?route=pptolechemensual/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(1, $user['usuarioId'], $user['empresaId']);
            $viewFile = $this->viewPath('pptolechemensual_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $anio = isset($_POST['pptolecanio']) ? (int)$_POST['pptolecanio'] : 0;
        $mes = isset($_POST['pptolecmes']) ? (int)$_POST['pptolecmes'] : 0;
        $fundoId = isset($_POST['fundoid']) ? (int)$_POST['fundoid'] : 0;

        if ($anio > 0 && $mes > 0 && $fundoId > 0) {
            try {
                $this->service->eliminarPptolechemensual(
                    $anio,
                    $mes,
                    $fundoId,
                    $user['usuarioId'],
                    $user['dispositivo'],
                    $user['ip']
                );
                $this->setToast('Registro eliminado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=pptolechemensual/listar');
        exit;
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

    private function isValidUpload(?array $file): bool
    {
        return $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    private function leerExcelPptoleche(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headerMap = [];
        $startIndex = 0;
        $rows = array_values($rows);

        foreach ($rows as $index => $row) {
            $map = $this->buildHeaderMap($row);
            if (count($map) >= 3) {
                $headerMap = $map;
                $startIndex = $index + 1;
                break;
            }
        }

        $dataRows = [];
        $totalRows = count($rows);
        for ($i = $startIndex; $i < $totalRows; $i++) {
            $rowNumber = $i + 1;
            $row = $rows[$i] ?? [];
            $payload = $this->buildPayloadFromRow($row, $headerMap, $rowNumber);
            if ($payload !== null) {
                $dataRows[] = $payload;
            }
        }

        return $dataRows;
    }

    private function buildHeaderMap(array $row): array
    {
        $aliases = [
            'pptolecanio' => 'pptolecanio',
            'anio' => 'pptolecanio',
            'year' => 'pptolecanio',
            'pptolecmes' => 'pptolecmes',
            'mes' => 'pptolecmes',
            'month' => 'pptolecmes',
            'fundoid' => 'fundoid',
            'fundo' => 'fundoid',
            'pptoleclitros' => 'pptoleclitros',
            'litros' => 'pptoleclitros',
            'pptolecvacas' => 'pptolecvacas',
            'vacas' => 'pptolecvacas',
            'pptolecltsxvc' => 'pptolecltsxvc',
            'ltsxvaca' => 'pptolecltsxvc',
            'litrosxvaca' => 'pptolecltsxvc',
            'pptolecfecha' => 'pptolecfecha',
            'fecha' => 'pptolecfecha',
            'pptolecdiasdelmes' => 'pptolecdiasdelmes',
            'diasdelmes' => 'pptolecdiasdelmes',
            'diasmes' => 'pptolecdiasdelmes',
        ];

        $map = [];
        foreach ($row as $col => $value) {
            $normalized = $this->normalizeHeader($value);
            if ($normalized === '') {
                continue;
            }
            if (isset($aliases[$normalized])) {
                $field = $aliases[$normalized];
                $map[$field] = $col;
            }
        }

        return $map;
    }

    private function normalizeHeader($value): string
    {
        if ($value === null) {
            return '';
        }
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9]/', '', $value);
        return $value ?? '';
    }

    private function buildPayloadFromRow(array $row, array $headerMap, int $rowNumber): ?array
    {
        $order = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $getCell = function (string $field) use ($row, $headerMap, $order) {
            if (!empty($headerMap)) {
                $col = $headerMap[$field] ?? null;
                return $col ? ($row[$col] ?? null) : null;
            }

            $indexMap = [
                'pptolecanio' => 0,
                'pptolecmes' => 1,
                'fundoid' => 2,
                'pptoleclitros' => 3,
                'pptolecvacas' => 4,
                'pptolecltsxvc' => 5,
                'pptolecfecha' => 6,
                'pptolecdiasdelmes' => 7,
            ];
            $index = $indexMap[$field] ?? null;
            if ($index === null) {
                return null;
            }
            $col = $order[$index] ?? null;
            return $col ? ($row[$col] ?? null) : null;
        };

        $anio = $this->parseInt($getCell('pptolecanio'));
        $mes = $this->parseInt($getCell('pptolecmes'));
        $fundoId = $this->parseInt($getCell('fundoid'));

        $litros = $this->parseFloat($getCell('pptoleclitros'));
        $vacas = $this->parseFloat($getCell('pptolecvacas'));
        $ltsxvc = $this->parseFloat($getCell('pptolecltsxvc'));

        $fecha = $this->parseFecha($getCell('pptolecfecha'));
        $dias = $this->parseInt($getCell('pptolecdiasdelmes'));

        $hasData = $anio !== null || $mes !== null || $fundoId !== null || $litros !== null || $vacas !== null || $fecha !== null;
        if (!$hasData) {
            return null;
        }

        if ($anio === null || $mes === null || $fundoId === null) {
            throw new RuntimeException("Fila {$rowNumber}: faltan claves (anio/mes/fundoid).");
        }

        if ($litros === null || $vacas === null) {
            throw new RuntimeException("Fila {$rowNumber}: faltan litros o vacas.");
        }

        if ($ltsxvc === null && $vacas > 0) {
            $ltsxvc = $litros / $vacas;
        }

        if ($dias === null || $dias <= 0) {
            $dias = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        }

        if ($fecha === null) {
            $fecha = sprintf('%04d-%02d-01', $anio, $mes);
        }

        return [
            'pptolecanio' => $anio,
            'pptolecmes' => $mes,
            'fundoid' => $fundoId,
            'pptoleclitros' => $litros,
            'pptolecvacas' => $vacas,
            'pptolecltsxvc' => $ltsxvc ?? 0,
            'pptolecfecha' => $fecha,
            'pptolecdiasdelmes' => $dias,
        ];
    }

    private function parseInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        $value = preg_replace('/[^0-9]/', '', (string)$value);
        return $value === '' ? null : (int)$value;
    }

    private function parseFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        $value = str_replace(',', '.', (string)$value);
        $value = preg_replace('/[^0-9.]/', '', $value);
        return $value === '' ? null : (float)$value;
    }

    private function parseFecha($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$value)->format('Y-m-d');
            } catch (Throwable $e) {
                return null;
            }
        }

        $value = trim((string)$value);
        try {
            if (str_contains($value, '/')) {
                $dt = \DateTimeImmutable::createFromFormat('d/m/Y', $value);
                if ($dt !== false) {
                    return $dt->format('Y-m-d');
                }
            }
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    private function normalizarPayload(array $data): array
    {
        $anio = isset($data['pptolecanio']) ? (int)$data['pptolecanio'] : 0;
        $mes = isset($data['pptolecmes']) ? (int)$data['pptolecmes'] : 0;
        $fundoId = isset($data['fundoid']) ? (int)$data['fundoid'] : 0;

        $litros = $this->parseFloat($data['pptoleclitros'] ?? null) ?? 0;
        $vacas = $this->parseFloat($data['pptolecvacas'] ?? null) ?? 0;
        $ltsxvc = $this->parseFloat($data['pptolecltsxvc'] ?? null);

        if (($ltsxvc === null || $ltsxvc <= 0) && $vacas > 0) {
            $ltsxvc = $litros / $vacas;
        }

        $dias = $this->parseInt($data['pptolecdiasdelmes'] ?? null);
        if (($dias === null || $dias <= 0) && $anio > 0 && $mes > 0) {
            $dias = cal_days_in_month(CAL_GREGORIAN, $mes, $anio);
        }

        $fecha = $data['pptolecfecha'] ?? null;
        if (empty($fecha) && $anio > 0 && $mes > 0) {
            $fecha = sprintf('%04d-%02d-01', $anio, $mes);
        }

        return [
            'pptolecanio' => $anio,
            'pptolecmes' => $mes,
            'fundoid' => $fundoId,
            'pptoleclitros' => $litros,
            'pptolecvacas' => $vacas,
            'pptolecltsxvc' => $ltsxvc ?? 0,
            'pptolecfecha' => $fecha,
            'pptolecdiasdelmes' => $dias ?? 0,
        ];
    }
}
