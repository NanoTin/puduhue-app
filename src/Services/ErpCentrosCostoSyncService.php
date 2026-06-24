<?php

require_once dirname(__DIR__) . '/Config/Env.php';
require_once dirname(__DIR__) . '/api-external/FinnegansClient.php';
require_once __DIR__ . '/ErpListadoEndpointsService.php';

class ErpCentrosCostoSyncService
{
    private const ENDPOINT_CODIGO = 'ERP_CENTROS_COSTOS_LIST';

    private \Database $db;
    private \FinnegansClient $finnegansClient;
    private \ErpListadoEndpointsService $endpointsService;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/Config/Database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $this->db = \Database::getInstance();
        $this->finnegansClient = new \FinnegansClient($this->db);
        $this->endpointsService = new \ErpListadoEndpointsService();
    }

    public function sincronizar(int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): array
    {
        $endpoint = $this->endpointsService->obtenerPorCodigo(self::ENDPOINT_CODIGO);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP de centros de costo no encontrado o inactivo.');
        }

        $inicio = new \DateTimeImmutable();
        $requestMeta = [
            'endpointCodigo' => self::ENDPOINT_CODIGO,
            'maestro' => 'centroscosto',
            'metodo' => 'GET',
            'url' => $this->ocultarAccessToken($this->endpointsService->construirUrlEndpoint($endpoint)),
        ];

        try {
            $response = $this->ejecutarGet($endpoint, $usuarioId, $disp, $ip);
            $centros = $this->normalizarRespuesta($response['decoded']);
            if (empty($centros)) {
                throw new \RuntimeException('Respuesta ERP de centros de costo no contiene registros validos.');
            }

            $conteos = $this->persistirCentrosCosto($centros, $usuarioId, $disp, $ip);
            $estado = ((int)$response['httpCode'] >= 200 && (int)$response['httpCode'] < 300) ? 'OK' : 'ERROR';
            $mensaje = $estado === 'OK'
                ? 'Sincronizacion de centros de costo ejecutada correctamente.'
                : 'Consulta ERP de centros de costo respondio con error HTTP ' . (int)$response['httpCode'] . '.';

            $this->registrarEndpointLog(
                (int)$endpoint['erpendpointid'],
                $tipoExec,
                $inicio,
                $estado,
                $mensaje,
                $conteos,
                $requestMeta,
                [
                    'httpCode' => (int)$response['httpCode'],
                    'decodedType' => gettype($response['decoded']),
                    'rawLength' => strlen((string)$response['raw']),
                    'rawPreview' => $this->limitarTexto((string)$response['raw'], 1000),
                ],
                $usuarioId
            );
            $this->actualizarEndpointUltSync((int)$endpoint['erpendpointid'], $estado, $estado === 'OK' ? null : $mensaje);

            return [
                'estado' => $estado,
                'endpointCodigo' => self::ENDPOINT_CODIGO,
                'descripcion' => (string)($endpoint['erpendpointdescripcion'] ?? 'Centros de Costo'),
                'httpCode' => (int)$response['httpCode'],
                'conteos' => $conteos,
            ];
        } catch (\Throwable $e) {
            $conteos = [
                'leidos' => 0,
                'insertados' => 0,
                'actualizados' => 0,
                'inactivos' => 0,
            ];
            $this->registrarEndpointLog(
                (int)$endpoint['erpendpointid'],
                $tipoExec,
                $inicio,
                'ERROR',
                $e->getMessage(),
                $conteos,
                $requestMeta,
                ['error' => $e->getMessage()],
                $usuarioId
            );
            $this->actualizarEndpointUltSync((int)$endpoint['erpendpointid'], 'ERROR', $e->getMessage());
            throw $e;
        }
    }

    private function ejecutarGet(array $endpoint, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $url = $this->endpointsService->construirUrlEndpoint($endpoint);
        $token = $this->finnegansClient->obtenerTokenVigente($usuarioId, $disp, $ip);
        $response = $this->finnegansClient->getJsonWithToken($url, $token);

        if ($this->finnegansClient->esTokenInvalido($response['decoded'], (int)$response['httpCode'])) {
            $token = $this->finnegansClient->refrescarToken($usuarioId, $disp, $ip);
            $response = $this->finnegansClient->getJsonWithToken($url, $token);
        }

        return $response;
    }

    private function normalizarRespuesta($decoded): array
    {
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new \RuntimeException('Respuesta ERP de centros de costo no tiene el formato esperado.');
        }

        $centros = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = trim((string)($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }

            $centros[$codigo] = [
                'codigo' => $this->limitarTexto($codigo, 50),
                'nombre' => $this->limitarTexto(trim((string)($row['nombre'] ?? $codigo)), 100),
                'descripcion' => $this->nullIfEmpty($this->limitarTexto(trim((string)($row['descripcion'] ?? '')), 255)),
                'activo' => !empty($row['activo']) ? 1 : 0,
            ];
        }

        return array_values($centros);
    }

    private function persistirCentrosCosto(array $centros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $pdo = $this->db->getPdo();
        $conteos = [
            'leidos' => count($centros),
            'insertados' => 0,
            'actualizados' => 0,
            'inactivos' => 0,
        ];

        $codigosVigentes = array_map(static fn(array $row): string => $row['codigo'], $centros);

        $pdo->beginTransaction();
        try {
            foreach ($centros as $centro) {
                $existente = $this->obtenerCentroPorCodigo($centro['codigo']);
                if ($existente === null) {
                    $centroId = $this->insertarCentro($centro, $usuarioId, $disp, $ip);
                    $this->registrarCentroLog($centroId, 'INS', $centro, null, $usuarioId, $disp, $ip);
                    $conteos['insertados']++;
                    continue;
                }

                if ($this->centroCambio($existente, $centro)) {
                    $this->actualizarCentro((int)$existente['centrocostoid'], $centro, $usuarioId, $disp, $ip);
                    $this->registrarCentroLog((int)$existente['centrocostoid'], 'UPD', $centro, $existente, $usuarioId, $disp, $ip);
                    $conteos['actualizados']++;
                }
            }

            $conteos['inactivos'] = $this->inactivarAusentes($codigosVigentes, $usuarioId, $disp, $ip);
            $pdo->commit();
            return $conteos;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function obtenerCentroPorCodigo(string $codigo): ?array
    {
        $rows = $this->db->select('SELECT * FROM centroscosto WHERE erpcentrocostocod = ? LIMIT 1', [$codigo]);
        return $rows[0] ?? null;
    }

    private function insertarCentro(array $centro, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $sql = 'INSERT INTO centroscosto (
                    centrocostocod,
                    centrocostodsc,
                    centrocostodescripcion,
                    erpcentrocostocod,
                    centrocostoactivo,
                    sincfechahora,
                    auditcreacionusuarioid,
                    auditcreaciondispositivo,
                    auditcreacionip
                ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)';
        $this->db->execute($sql, [
            $centro['codigo'],
            $centro['nombre'],
            $centro['descripcion'],
            $centro['codigo'],
            $centro['activo'],
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
        ]);

        return (int)$this->db->getPdo()->lastInsertId();
    }

    private function actualizarCentro(int $centroId, array $centro, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sql = 'UPDATE centroscosto
                SET centrocostocod = ?,
                    centrocostodsc = ?,
                    centrocostodescripcion = ?,
                    centrocostoactivo = ?,
                    sincfechahora = NOW(),
                    auditedicionusuarioid = ?,
                    auditediciondispositivo = ?,
                    auditedicionip = ?
                WHERE centrocostoid = ?';
        $this->db->execute($sql, [
            $centro['codigo'],
            $centro['nombre'],
            $centro['descripcion'],
            $centro['activo'],
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
            $centroId,
        ]);
    }

    private function inactivarAusentes(array $codigosVigentes, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $rows = $this->db->select('SELECT * FROM centroscosto WHERE centrocostoactivo = 1');
        $vigentes = array_flip($codigosVigentes);
        $inactivos = 0;

        foreach ($rows as $row) {
            $codigo = (string)($row['erpcentrocostocod'] ?? '');
            if (isset($vigentes[$codigo])) {
                continue;
            }

            $this->db->execute(
                'UPDATE centroscosto
                 SET centrocostoactivo = 0,
                     sincfechahora = NOW(),
                     auditedicionusuarioid = ?,
                     auditediciondispositivo = ?,
                     auditedicionip = ?
                 WHERE centrocostoid = ?',
                [
                    $usuarioId,
                    $this->limitarTexto((string)$disp, 100),
                    $this->limitarTexto((string)$ip, 50),
                    (int)$row['centrocostoid'],
                ]
            );
            $this->registrarCentroLog((int)$row['centrocostoid'], 'ANL', ['motivo' => 'Ausente en sincronizacion ERP'], $row, $usuarioId, $disp, $ip);
            $inactivos++;
        }

        return $inactivos;
    }

    private function centroCambio(array $existente, array $centro): bool
    {
        return (string)($existente['centrocostocod'] ?? '') !== $centro['codigo']
            || (string)($existente['centrocostodsc'] ?? '') !== $centro['nombre']
            || (string)($existente['centrocostodescripcion'] ?? '') !== (string)($centro['descripcion'] ?? '')
            || (int)($existente['centrocostoactivo'] ?? 0) !== (int)$centro['activo'];
    }

    private function registrarCentroLog(int $centroId, string $tipo, array $param, ?array $backup, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sql = 'INSERT INTO centroscostolog (
                    centrocostoid,
                    logusuarioid,
                    logdispositivo,
                    logip,
                    logtipo,
                    logparamjson,
                    logregbkpjson
                ) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $this->db->execute($sql, [
            $centroId,
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
            $tipo,
            $this->jsonEncode($param),
            $backup !== null ? $this->jsonEncode($backup) : null,
        ]);
    }

    private function registrarEndpointLog(
        int $endpointId,
        string $tipoExec,
        \DateTimeImmutable $inicio,
        string $estado,
        string $mensaje,
        array $conteos,
        array $requestMeta,
        array $responseMeta,
        int $usuarioId
    ): void {
        $sql = 'INSERT INTO erplistadoendpointslog (
                    erpendpointid,
                    erpendpointlogtipoexec,
                    erpendpointlogfechaini,
                    erpendpointlogfechafin,
                    erpendpointlogestado,
                    erpendpointlogmensaje,
                    erpendpointlogregistrosleidos,
                    erpendpointlogregistrosinsertados,
                    erpendpointlogregistrosactualizados,
                    erpendpointlogregistrosinactivos,
                    erpendpointlogrequestjson,
                    erpendpointlogresponsejson,
                    usuarioid
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $endpointId,
            $this->normalizarTipoExec($tipoExec),
            $inicio->format('Y-m-d H:i:s'),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $estado,
            $this->limitarTexto($mensaje, 2000),
            (int)($conteos['leidos'] ?? 0),
            (int)($conteos['insertados'] ?? 0),
            (int)($conteos['actualizados'] ?? 0),
            (int)($conteos['inactivos'] ?? 0),
            $this->jsonEncode($requestMeta),
            $this->jsonEncode($responseMeta),
            $usuarioId,
        ]);
    }

    private function actualizarEndpointUltSync(int $endpointId, string $estado, ?string $error): void
    {
        $this->db->execute(
            'UPDATE erplistadoendpoints
             SET erpendpointultsync = NOW(),
                 erpendpointultestado = ?,
                 erpendpointulterror = ?
             WHERE erpendpointid = ?',
            [$estado, $this->limitarTexto($error, 2000), $endpointId]
        );
    }

    private function normalizarTipoExec(string $tipoExec): string
    {
        $tipoExec = strtoupper(trim($tipoExec));
        return in_array($tipoExec, ['MANUAL', 'AUTO', 'TECNICO'], true) ? $tipoExec : 'TECNICO';
    }

    private function ocultarAccessToken(string $url): string
    {
        return preg_replace('/([?&]ACCESS_TOKEN=)[^&]+/i', '$1***', $url) ?? $url;
    }

    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $value;
    }

    private function jsonEncode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function limitarTexto(?string $texto, int $max): ?string
    {
        if ($texto === null) {
            return null;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $max);
        }

        return substr($texto, 0, $max);
    }
}
