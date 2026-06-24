<?php

require_once dirname(__DIR__) . '/Config/Env.php';
require_once dirname(__DIR__) . '/api-external/FinnegansClient.php';
require_once __DIR__ . '/ErpListadoEndpointsService.php';

class ErpMonedasSyncService
{
    private const ENDPOINT_CODIGO = 'ERP_MONEDAS_LIST';

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
            throw new \RuntimeException('Endpoint ERP de monedas no encontrado o inactivo.');
        }

        $inicio = new \DateTimeImmutable();
        $requestMeta = [
            'endpointCodigo' => self::ENDPOINT_CODIGO,
            'maestro' => 'erpmonedas',
            'metodo' => 'GET',
            'url' => $this->ocultarAccessToken($this->endpointsService->construirUrlEndpoint($endpoint)),
        ];

        try {
            $response = $this->ejecutarGet($endpoint, $usuarioId, $disp, $ip);
            $monedas = $this->normalizarRespuesta($response['decoded']);
            if (empty($monedas)) {
                throw new \RuntimeException('Respuesta ERP de monedas no contiene registros validos.');
            }
            $conteos = $this->persistirMonedas($monedas, $usuarioId, $disp, $ip);

            $estado = ((int)$response['httpCode'] >= 200 && (int)$response['httpCode'] < 300) ? 'OK' : 'ERROR';
            $mensaje = $estado === 'OK'
                ? 'Sincronizacion de monedas ejecutada correctamente.'
                : 'Consulta ERP de monedas respondio con error HTTP ' . (int)$response['httpCode'] . '.';

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
                'descripcion' => (string)($endpoint['erpendpointdescripcion'] ?? 'Monedas'),
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
            throw new \RuntimeException('Respuesta ERP de monedas no tiene el formato esperado.');
        }

        $monedas = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = trim((string)($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }

            $monedas[$codigo] = [
                'codigo' => $this->limitarTexto($codigo, 50),
                'nombre' => $this->limitarTexto(trim((string)($row['nombre'] ?? $codigo)), 100),
                'descripcion' => $this->nullIfEmpty($this->limitarTexto(trim((string)($row['descripcion'] ?? '')), 255)),
                'activo' => !empty($row['activo']) ? 1 : 0,
                'default' => strtoupper($codigo) === 'PES' ? 1 : 0,
            ];
        }

        return array_values($monedas);
    }

    private function persistirMonedas(array $monedas, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $pdo = $this->db->getPdo();
        $conteos = [
            'leidos' => count($monedas),
            'insertados' => 0,
            'actualizados' => 0,
            'inactivos' => 0,
        ];

        $codigosVigentes = array_map(static fn(array $row): string => $row['codigo'], $monedas);

        $pdo->beginTransaction();
        try {
            foreach ($monedas as $moneda) {
                $existente = $this->obtenerMonedaPorCodigo($moneda['codigo']);
                if ($existente === null) {
                    $monedaId = $this->insertarMoneda($moneda, $usuarioId, $disp, $ip);
                    $this->registrarMonedaLog($monedaId, 'INS', $moneda, null, $usuarioId, $disp, $ip);
                    $conteos['insertados']++;
                    continue;
                }

                if ($this->monedaCambio($existente, $moneda)) {
                    $this->actualizarMoneda((int)$existente['erpmonedaid'], $moneda, $usuarioId, $disp, $ip);
                    $this->registrarMonedaLog((int)$existente['erpmonedaid'], 'UPD', $moneda, $existente, $usuarioId, $disp, $ip);
                    $conteos['actualizados']++;
                }
            }

            $inactivadas = $this->inactivarAusentes($codigosVigentes, $usuarioId, $disp, $ip);
            $conteos['inactivos'] = $inactivadas;

            if (in_array('PES', array_map('strtoupper', $codigosVigentes), true)) {
                $this->db->execute("UPDATE erpmonedas SET erpmonedadefault = CASE WHEN UPPER(erpmonedacod) = 'PES' THEN 1 ELSE 0 END");
            }

            $pdo->commit();
            return $conteos;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function obtenerMonedaPorCodigo(string $codigo): ?array
    {
        $rows = $this->db->select('SELECT * FROM erpmonedas WHERE erpmonedacod = ? LIMIT 1', [$codigo]);
        return $rows[0] ?? null;
    }

    private function insertarMoneda(array $moneda, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $sql = 'INSERT INTO erpmonedas (
                    erpmonedacod,
                    erpmonedadsc,
                    erpmonedadescripcion,
                    erpmonedadefault,
                    erpmonedaactivo,
                    sincfechahora,
                    auditcreacionusuarioid,
                    auditcreaciondispositivo,
                    auditcreacionip
                ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)';
        $this->db->execute($sql, [
            $moneda['codigo'],
            $moneda['nombre'],
            $moneda['descripcion'],
            $moneda['default'],
            $moneda['activo'],
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
        ]);

        return (int)$this->db->getPdo()->lastInsertId();
    }

    private function actualizarMoneda(int $monedaId, array $moneda, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sql = 'UPDATE erpmonedas
                SET erpmonedadsc = ?,
                    erpmonedadescripcion = ?,
                    erpmonedadefault = ?,
                    erpmonedaactivo = ?,
                    sincfechahora = NOW(),
                    auditedicionusuarioid = ?,
                    auditediciondispositivo = ?,
                    auditedicionip = ?
                WHERE erpmonedaid = ?';
        $this->db->execute($sql, [
            $moneda['nombre'],
            $moneda['descripcion'],
            $moneda['default'],
            $moneda['activo'],
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
            $monedaId,
        ]);
    }

    private function inactivarAusentes(array $codigosVigentes, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $rows = $this->db->select('SELECT * FROM erpmonedas WHERE erpmonedaactivo = 1');
        $vigentes = array_flip($codigosVigentes);
        $inactivos = 0;

        foreach ($rows as $row) {
            $codigo = (string)($row['erpmonedacod'] ?? '');
            if (isset($vigentes[$codigo])) {
                continue;
            }

            $this->db->execute(
                'UPDATE erpmonedas
                 SET erpmonedaactivo = 0,
                     sincfechahora = NOW(),
                     auditedicionusuarioid = ?,
                     auditediciondispositivo = ?,
                     auditedicionip = ?
                 WHERE erpmonedaid = ?',
                [
                    $usuarioId,
                    $this->limitarTexto((string)$disp, 100),
                    $this->limitarTexto((string)$ip, 50),
                    (int)$row['erpmonedaid'],
                ]
            );
            $this->registrarMonedaLog((int)$row['erpmonedaid'], 'ANL', ['motivo' => 'Ausente en sincronizacion ERP'], $row, $usuarioId, $disp, $ip);
            $inactivos++;
        }

        return $inactivos;
    }

    private function monedaCambio(array $existente, array $moneda): bool
    {
        return (string)($existente['erpmonedadsc'] ?? '') !== $moneda['nombre']
            || (string)($existente['erpmonedadescripcion'] ?? '') !== (string)($moneda['descripcion'] ?? '')
            || (int)($existente['erpmonedadefault'] ?? 0) !== (int)$moneda['default']
            || (int)($existente['erpmonedaactivo'] ?? 0) !== (int)$moneda['activo'];
    }

    private function registrarMonedaLog(int $monedaId, string $tipo, array $param, ?array $backup, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sql = 'INSERT INTO erpmonedaslog (
                    erpmonedaid,
                    logusuarioid,
                    logdispositivo,
                    logip,
                    logtipo,
                    logparamjson,
                    logregbkpjson
                ) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $this->db->execute($sql, [
            $monedaId,
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
