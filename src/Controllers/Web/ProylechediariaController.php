<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProylechediariaController
{
    private \ProylechediariaService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ProylechediariaService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \ProylechediariaService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $filtros = [
            'filtroProylecheanio' => $_GET['filtroProylecheanio'] ?? null,
            'filtroProylechemes' => $_GET['filtroProylechemes'] ?? null,
        ];

        $result = $this->service->listarProylechediaria($filtros);
        $proylechediaria = $result['rows'] ?? [];

        $viewFile = $this->viewPath('proylechediaria_listar.php');
        require $viewFile;
    }

    public function crearForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $errorMessage = null;
        $formData = [];

        $viewFile = $this->viewPath('proylechediaria_crear.php');
        require $viewFile;
    }

    public function crearPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $uploadedFile = $_FILES['proyleche_excel'] ?? null;
        if ($this->isValidUpload($uploadedFile)) {
            try {
                $rows = $this->leerExcelProyleche($uploadedFile['tmp_name']);
                if (empty($rows)) {
                    throw new RuntimeException('No se encontraron filas validas en el Excel.');
                }

                $result = $this->service->cargarMasivaProylechediaria(
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

            header('Location: ?route=proylechediaria/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $formData = $data;

        try {
            $payload = $this->normalizarPayload($data);
            $this->service->crearProylechediaria(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro creado correctamente', 'success');
            header('Location: ?route=proylechediaria/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('proylechediaria_crear.php');
            require $viewFile;
        }
    }

    public function editarForm(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();

        $fecha = isset($_GET['fecha']) ? (string)$_GET['fecha'] : '';
        if ($fecha === '') {
            header('Location: ?route=proylechediaria/listar');
            exit;
        }

        $registro = $this->service->consultarPorFecha($fecha);
        if ($registro === null) {
            header('Location: ?route=proylechediaria/listar');
            exit;
        }

        $errorMessage = null;
        $formData = $registro;

        $viewFile = $this->viewPath('proylechediaria_editar.php');
        require $viewFile;
    }

    public function editarPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $fecha = isset($_POST['proylechefecha']) ? (string)$_POST['proylechefecha'] : '';
        if ($fecha === '') {
            header('Location: ?route=proylechediaria/listar');
            exit;
        }

        $data = $_POST;
        unset($data['_token'], $data['action'], $data['route']);
        $formData = $data;

        try {
            $payload = $this->normalizarPayload($data);
            $this->service->actualizarProylechediaria(
                $payload,
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip']
            );
            $this->setToast('Registro actualizado correctamente', 'success');
            header('Location: ?route=proylechediaria/listar');
            exit;
        } catch (RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->setToast($errorMessage, 'danger');
            $viewFile = $this->viewPath('proylechediaria_editar.php');
            require $viewFile;
        }
    }

    public function anularPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();

        $fecha = isset($_POST['proylechefecha']) ? (string)$_POST['proylechefecha'] : '';
        if ($fecha !== '') {
            try {
                $this->service->eliminarProylechediaria($fecha);
                $this->setToast('Registro eliminado correctamente', 'success');
            } catch (RuntimeException $e) {
                $this->setToast($e->getMessage(), 'danger');
            }
        }

        header('Location: ?route=proylechediaria/listar');
        exit;
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
            'type' => $type,
        ];
    }

    private function isValidUpload(?array $file): bool
    {
        return $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    private function leerExcelProyleche(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headerMap = [];
        $startIndex = 0;
        $rows = array_values($rows);

        foreach ($rows as $index => $row) {
            $map = $this->buildHeaderMap($row);
            if (count($map) >= 2) {
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
            'proylechefecha' => 'proylechefecha',
            'fecha' => 'proylechefecha',
            'date' => 'proylechefecha',
            'proylecheventatotlitros' => 'proylecheventatotlitros',
            'litros' => 'proylecheventatotlitros',
            'proylecheventatotvacas' => 'proylecheventatotvacas',
            'vacas' => 'proylecheventatotvacas',
            'proylecheventatotltsxvaca' => 'proylecheventatotltsxvaca',
            'ltsxvaca' => 'proylecheventatotltsxvaca',
            'litrosxvaca' => 'proylecheventatotltsxvaca',
            'ltsvaca' => 'proylecheventatotltsxvaca',
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
        $order = ['A', 'B', 'C', 'D'];
        $getCell = function (string $field) use ($row, $headerMap, $order) {
            if (!empty($headerMap)) {
                $col = $headerMap[$field] ?? null;
                return $col ? ($row[$col] ?? null) : null;
            }

            $indexMap = [
                'proylechefecha' => 0,
                'proylecheventatotlitros' => 1,
                'proylecheventatotvacas' => 2,
                'proylecheventatotltsxvaca' => 3,
            ];
            $index = $indexMap[$field] ?? null;
            if ($index === null) {
                return null;
            }
            $col = $order[$index] ?? null;
            return $col ? ($row[$col] ?? null) : null;
        };

        $fecha = $this->parseFecha($getCell('proylechefecha'));
        $litros = $this->parseFloat($getCell('proylecheventatotlitros'));
        $vacas = $this->parseFloat($getCell('proylecheventatotvacas'));
        $ltsxvaca = $this->parseFloat($getCell('proylecheventatotltsxvaca'));

        $hasData = $fecha !== null || $litros !== null || $vacas !== null || $ltsxvaca !== null;
        if (!$hasData) {
            return null;
        }

        if ($fecha === null) {
            throw new RuntimeException("Fila {$rowNumber}: falta la fecha.");
        }

        if ($litros === null || $vacas === null) {
            throw new RuntimeException("Fila {$rowNumber}: faltan litros o vacas.");
        }

        if ($ltsxvaca === null && $vacas > 0) {
            $ltsxvaca = $litros / $vacas;
        }

        return [
            'proylechefecha' => $fecha,
            'proylecheventatotlitros' => $litros,
            'proylecheventatotvacas' => $vacas,
            'proylecheventatotltsxvaca' => $ltsxvaca ?? 0,
        ];
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
        $fecha = $data['proylechefecha'] ?? null;

        $litros = $this->parseFloat($data['proylecheventatotlitros'] ?? null) ?? 0;
        $vacas = $this->parseFloat($data['proylecheventatotvacas'] ?? null) ?? 0;
        $ltsxvaca = $this->parseFloat($data['proylecheventatotltsxvaca'] ?? null);

        if (($ltsxvaca === null || $ltsxvaca <= 0) && $vacas > 0) {
            $ltsxvaca = $litros / $vacas;
        }

        if (empty($fecha)) {
            throw new RuntimeException('La fecha es obligatoria.');
        }

        return [
            'proylechefecha' => $fecha,
            'proylecheventatotlitros' => $litros,
            'proylecheventatotvacas' => $vacas,
            'proylecheventatotltsxvaca' => $ltsxvaca ?? 0,
        ];
    }
}
