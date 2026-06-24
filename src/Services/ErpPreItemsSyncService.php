<?php

require_once dirname(__DIR__) . '/Config/Env.php';
require_once dirname(__DIR__) . '/api-external/FinnegansClient.php';
require_once __DIR__ . '/ErpListadoEndpointsService.php';

class ErpPreItemsSyncService
{
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

    public function sincronizarPartidasFinancieras(int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): array
    {
        return $this->sincronizarCatalogoSimple($this->configPartidasFinancieras(), $usuarioId, $disp, $ip, $tipoExec);
    }

    public function sincronizarUnidadesMedida(int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): array
    {
        return $this->sincronizarCatalogoSimple($this->configUnidadesMedida(), $usuarioId, $disp, $ip, $tipoExec);
    }

    public function sincronizarFamilias(int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): array
    {
        return $this->sincronizarCatalogoSimple($this->configFamilias(), $usuarioId, $disp, $ip, $tipoExec);
    }

    public function sincronizarSubfamilias(int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): array
    {
        return $this->sincronizarCatalogoSimple(
            $this->configSubfamilias(),
            $usuarioId,
            $disp,
            $ip,
            $tipoExec,
            function (array $row) use ($usuarioId, $disp, $ip): array {
                $detalle = $this->obtenerDetallePorCodigo('ERP_SUBFAMILIAS_DETALLE', $row['codigo'], $usuarioId, $disp, $ip);
                $familiaCodigo = trim((string)($detalle['ProductoFamiliaCodigo'] ?? ''));
                $familiaId = null;
                if ($familiaCodigo !== '') {
                    $familiaId = $this->buscarIdPorCodigo('familias', 'familiaid', 'familiacod', $familiaCodigo);
                }

                return [
                    'familiaid' => $familiaId,
                    'detalle' => [
                        'ProductoFamiliaCodigo' => $familiaCodigo !== '' ? $familiaCodigo : null,
                    ],
                ];
            }
        );
    }

    public function sincronizarTasasImpositivas(int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): array
    {
        return $this->sincronizarCatalogoSimple(
            $this->configTasasImpositivas(),
            $usuarioId,
            $disp,
            $ip,
            $tipoExec,
            function (array $row) use ($usuarioId, $disp, $ip): array {
                $detalle = $this->obtenerDetallePorCodigo('ERP_TASAS_IMPOSITIVAS_DETALLE', $row['codigo'], $usuarioId, $disp, $ip);
                return [
                    'porcentaje' => isset($detalle['Porcentaje']) ? (float)$detalle['Porcentaje'] : null,
                    'detalle' => [
                        'PaisID' => $detalle['PaisID'] ?? null,
                    ],
                ];
            }
        );
    }

    private function sincronizarCatalogoSimple(
        array $config,
        int $usuarioId,
        ?string $disp,
        ?string $ip,
        string $tipoExec,
        ?callable $enriquecer = null
    ): array {
        $endpoint = $this->endpointsService->obtenerPorCodigo($config['endpointCodigo']);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP no encontrado o inactivo: ' . $config['endpointCodigo']);
        }

        $inicio = new \DateTimeImmutable();
        $requestMeta = [
            'endpointCodigo' => $config['endpointCodigo'],
            'maestro' => $config['table'],
            'metodo' => 'GET',
            'url' => $this->ocultarAccessToken($this->endpointsService->construirUrlEndpoint($endpoint)),
        ];

        try {
            $response = $this->ejecutarGet($endpoint, $usuarioId, $disp, $ip);
            $rows = $this->normalizarRespuestaLista($config, $response['decoded'], $enriquecer);
            if (empty($rows)) {
                throw new \RuntimeException('Respuesta ERP no contiene registros validos para ' . $config['label'] . '.');
            }

            $conteos = $this->persistirCatalogo($config, $rows, $usuarioId, $disp, $ip);
            $estado = ((int)$response['httpCode'] >= 200 && (int)$response['httpCode'] < 300) ? 'OK' : 'ERROR';
            $mensaje = $estado === 'OK'
                ? 'Sincronizacion de ' . $config['label'] . ' ejecutada correctamente.'
                : 'Consulta ERP de ' . $config['label'] . ' respondio con error HTTP ' . (int)$response['httpCode'] . '.';

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
                'endpointCodigo' => $config['endpointCodigo'],
                'descripcion' => $config['label'],
                'httpCode' => (int)$response['httpCode'],
                'conteos' => $conteos,
            ];
        } catch (\Throwable $e) {
            $conteos = ['leidos' => 0, 'insertados' => 0, 'actualizados' => 0, 'inactivos' => 0];
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

    private function ejecutarGet(array $endpoint, int $usuarioId, ?string $disp, ?string $ip, ?string $codigo = null): array
    {
        $url = $this->endpointsService->construirUrlEndpoint($endpoint, $codigo);
        $token = $this->finnegansClient->obtenerTokenVigente($usuarioId, $disp, $ip);
        $response = $this->finnegansClient->getJsonWithToken($url, $token);

        if ($this->finnegansClient->esTokenInvalido($response['decoded'], (int)$response['httpCode'])) {
            $token = $this->finnegansClient->refrescarToken($usuarioId, $disp, $ip);
            $response = $this->finnegansClient->getJsonWithToken($url, $token);
        }

        return $response;
    }

    private function obtenerDetallePorCodigo(string $endpointCodigo, string $codigo, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $endpoint = $this->endpointsService->obtenerPorCodigo($endpointCodigo);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP de detalle no encontrado o inactivo: ' . $endpointCodigo);
        }

        $response = $this->ejecutarGet($endpoint, $usuarioId, $disp, $ip, $codigo);
        if ((int)$response['httpCode'] < 200 || (int)$response['httpCode'] >= 300 || !is_array($response['decoded'])) {
            throw new \RuntimeException('No se pudo obtener detalle ERP para codigo ' . $codigo . '.');
        }

        return $response['decoded'];
    }

    private function normalizarRespuestaLista(array $config, $decoded, ?callable $enriquecer): array
    {
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new \RuntimeException('Respuesta ERP no tiene formato de lista.');
        }

        $items = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = trim((string)($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }

            $item = [
                'codigo' => $this->limitarTexto($codigo, (int)($config['codeMax'] ?? 50)),
                'nombre' => $this->limitarTexto(trim((string)($row['nombre'] ?? $codigo)), (int)($config['nameMax'] ?? 100)),
                'descripcion' => $this->nullIfEmpty($this->limitarTexto(trim((string)($row['descripcion'] ?? '')), (int)($config['descMax'] ?? 255))),
                'activo' => !empty($row['activo']) ? 1 : 0,
            ];

            if ($enriquecer !== null) {
                $extra = $enriquecer($item);
                if (is_array($extra)) {
                    $item = array_merge($item, $extra);
                }
            }

            $items[$codigo] = $item;
        }

        return array_values($items);
    }

    private function persistirCatalogo(array $config, array $items, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $pdo = $this->db->getPdo();
        $conteos = ['leidos' => count($items), 'insertados' => 0, 'actualizados' => 0, 'inactivos' => 0];
        $codigosVigentes = array_map(static fn(array $row): string => $row['codigo'], $items);

        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $existente = $this->obtenerPorCodigo($config, $item['codigo']);
                if ($existente === null) {
                    $id = $this->insertarItem($config, $item, $usuarioId, $disp, $ip);
                    $this->registrarLogLocal($config, $id, 'INS', $item, null, $usuarioId, $disp, $ip);
                    $conteos['insertados']++;
                    continue;
                }

                if ($this->itemCambio($config, $existente, $item)) {
                    $this->actualizarItem($config, (int)$existente[$config['pk']], $item, $usuarioId, $disp, $ip);
                    $this->registrarLogLocal($config, (int)$existente[$config['pk']], 'UPD', $item, $existente, $usuarioId, $disp, $ip);
                    $conteos['actualizados']++;
                }
            }

            $conteos['inactivos'] = $this->inactivarAusentes($config, $codigosVigentes, $usuarioId, $disp, $ip);
            $pdo->commit();
            return $conteos;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function obtenerPorCodigo(array $config, string $codigo): ?array
    {
        $rows = $this->db->select(
            'SELECT * FROM ' . $config['table'] . ' WHERE ' . $config['codeCol'] . ' = ? LIMIT 1',
            [$codigo]
        );
        return $rows[0] ?? null;
    }

    private function insertarItem(array $config, array $item, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $columns = [
            $config['codeCol'],
            $config['nameCol'],
        ];
        $values = [
            $item['codigo'],
            $item['nombre'],
        ];

        if (!empty($config['descCol'])) {
            $columns[] = $config['descCol'];
            $values[] = $item['descripcion'];
        }

        $columns = array_merge($columns, [
            $config['activeCol'],
            $config['syncCol'],
            'auditcreacionusuarioid',
            'auditcreaciondispositivo',
            'auditcreacionip',
        ]);
        $values = array_merge($values, [
            $item['activo'],
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
        ]);

        if (!empty($config['insertEditAuditDefaults'])) {
            $columns[] = 'auditediciondispositivo';
            $values[] = $this->limitarTexto((string)$disp, 100);
            $columns[] = 'auditedicionip';
            $values[] = $this->limitarTexto((string)$ip, 50);
        }

        foreach (($config['extraCols'] ?? []) as $key => $column) {
            $columns[] = $column;
            $values[] = $item[$key] ?? null;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO ' . $config['table'] . ' (`' . implode('`, `', $columns) . '`) VALUES (' . $placeholders . ')';
        $this->db->execute($sql, $values);

        return (int)$this->db->getPdo()->lastInsertId();
    }

    private function actualizarItem(array $config, int $id, array $item, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sets = [
            $config['nameCol'] . ' = ?',
            $config['activeCol'] . ' = ?',
            $config['syncCol'] . ' = NOW()',
            'auditedicionusuarioid = ?',
            'auditediciondispositivo = ?',
            'auditedicionip = ?',
        ];
        $values = [
            $item['nombre'],
            $item['activo'],
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
        ];

        if (!empty($config['descCol'])) {
            array_splice($sets, 1, 0, [$config['descCol'] . ' = ?']);
            array_splice($values, 1, 0, [$item['descripcion']]);
        }

        foreach (($config['extraCols'] ?? []) as $key => $column) {
            $sets[] = $column . ' = ?';
            $values[] = $item[$key] ?? null;
        }

        $values[] = $id;
        $sql = 'UPDATE ' . $config['table'] . ' SET ' . implode(', ', $sets) . ' WHERE ' . $config['pk'] . ' = ?';
        $this->db->execute($sql, $values);
    }

    private function inactivarAusentes(array $config, array $codigosVigentes, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $rows = $this->db->select('SELECT * FROM ' . $config['table'] . ' WHERE ' . $config['activeCol'] . ' = 1');
        $vigentes = array_flip($codigosVigentes);
        $inactivos = 0;

        foreach ($rows as $row) {
            $codigo = (string)($row[$config['codeCol']] ?? '');
            if (isset($vigentes[$codigo])) {
                continue;
            }

            $this->db->execute(
                'UPDATE ' . $config['table'] . '
                 SET ' . $config['activeCol'] . ' = 0,
                     ' . $config['syncCol'] . ' = NOW(),
                     auditedicionusuarioid = ?,
                     auditediciondispositivo = ?,
                     auditedicionip = ?
                 WHERE ' . $config['pk'] . ' = ?',
                [
                    $usuarioId,
                    $this->limitarTexto((string)$disp, 100),
                    $this->limitarTexto((string)$ip, 50),
                    (int)$row[$config['pk']],
                ]
            );
            $this->registrarLogLocal($config, (int)$row[$config['pk']], 'ANL', ['motivo' => 'Ausente en sincronizacion ERP'], $row, $usuarioId, $disp, $ip);
            $inactivos++;
        }

        return $inactivos;
    }

    private function itemCambio(array $config, array $existente, array $item): bool
    {
        if ((string)($existente[$config['nameCol']] ?? '') !== (string)$item['nombre']) {
            return true;
        }
        if (!empty($config['descCol']) && (string)($existente[$config['descCol']] ?? '') !== (string)($item['descripcion'] ?? '')) {
            return true;
        }
        if ((int)($existente[$config['activeCol']] ?? 0) !== (int)$item['activo']) {
            return true;
        }
        foreach (($config['extraCols'] ?? []) as $key => $column) {
            if ((string)($existente[$column] ?? '') !== (string)($item[$key] ?? '')) {
                return true;
            }
        }
        return false;
    }

    private function registrarLogLocal(array $config, int $id, string $tipo, array $param, ?array $backup, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sql = 'INSERT INTO ' . $config['logTable'] . ' (
                    ' . $config['logFk'] . ',
                    logusuarioid,
                    logdispositivo,
                    logip,
                    logtipo,
                    logparamjson,
                    logregbkpjson
                ) VALUES (?, ?, ?, ?, ?, ?, ?)';
        $this->db->execute($sql, [
            $id,
            $usuarioId,
            $this->limitarTexto((string)$disp, 100),
            $this->limitarTexto((string)$ip, 50),
            $tipo,
            $this->jsonEncode($param),
            $backup !== null ? $this->jsonEncode($backup) : $this->backupJsonNulo($config),
        ]);
    }

    private function buscarIdPorCodigo(string $table, string $pk, string $codeCol, string $codigo): ?int
    {
        $rows = $this->db->select('SELECT ' . $pk . ' FROM ' . $table . ' WHERE ' . $codeCol . ' = ? LIMIT 1', [$codigo]);
        if (empty($rows)) {
            return null;
        }
        return (int)$rows[0][$pk];
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

    private function configPartidasFinancieras(): array
    {
        return [
            'label' => 'Partidas Financieras',
            'endpointCodigo' => 'ERP_PARTIDAS_FINANCIERAS_LIST',
            'table' => 'erppartidasfinancieras',
            'pk' => 'erppartidafinancieraid',
            'codeCol' => 'erppartidafinancieracod',
            'nameCol' => 'erppartidafinancieradsc',
            'descCol' => 'erppartidafinancieradescripcion',
            'activeCol' => 'erppartidafinancieraactivo',
            'syncCol' => 'sincfechahora',
            'logTable' => 'erppartidasfinancieraslog',
            'logFk' => 'erppartidafinancieraid',
        ];
    }

    private function configUnidadesMedida(): array
    {
        return [
            'label' => 'Unidades de Medida',
            'endpointCodigo' => 'ERP_UNIDADES_MEDIDA_LIST',
            'table' => 'invunidadesmedidas',
            'pk' => 'invunidmedid',
            'codeCol' => 'erpunidmedcod',
            'nameCol' => 'invunidmeddsc',
            'descCol' => null,
            'activeCol' => 'invunidmedactivo',
            'syncCol' => 'auditedicionfechahora',
            'logTable' => 'invunidadesmedidaslog',
            'logFk' => 'invunidmedid',
            'nameMax' => 50,
            'insertEditAuditDefaults' => true,
            'logBackupNotNull' => true,
        ];
    }

    private function configFamilias(): array
    {
        return [
            'label' => 'Familias',
            'endpointCodigo' => 'ERP_FAMILIAS_LIST',
            'table' => 'familias',
            'pk' => 'familiaid',
            'codeCol' => 'familiacod',
            'nameCol' => 'familiadsc',
            'descCol' => 'familiadescripcion',
            'activeCol' => 'familiaactivo',
            'syncCol' => 'sincfechahora',
            'logTable' => 'familiaslog',
            'logFk' => 'familiaid',
        ];
    }

    private function configSubfamilias(): array
    {
        return [
            'label' => 'Subfamilias',
            'endpointCodigo' => 'ERP_SUBFAMILIAS_LIST',
            'table' => 'subfamilias',
            'pk' => 'subfamiliaid',
            'codeCol' => 'subfamiliacod',
            'nameCol' => 'subfamiliadsc',
            'descCol' => 'subfamiliadescripcion',
            'activeCol' => 'subfamiliaactivo',
            'syncCol' => 'sincfechahora',
            'logTable' => 'subfamiliaslog',
            'logFk' => 'subfamiliaid',
            'extraCols' => [
                'familiaid' => 'familiaid',
            ],
        ];
    }

    private function configTasasImpositivas(): array
    {
        return [
            'label' => 'Tasas Impositivas',
            'endpointCodigo' => 'ERP_TASAS_IMPOSITIVAS_LIST',
            'table' => 'erptasasimpositivas',
            'pk' => 'erptasaimpositivaid',
            'codeCol' => 'erptasaimpositivacod',
            'nameCol' => 'erptasaimpositivadsc',
            'descCol' => 'erptasaimpositivadescripcion',
            'activeCol' => 'erptasaimpositivaactivo',
            'syncCol' => 'sincfechahora',
            'logTable' => 'erptasasimpositivaslog',
            'logFk' => 'erptasaimpositivaid',
            'extraCols' => [
                'porcentaje' => 'erptasaimpositivaporcentaje',
            ],
        ];
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

    private function backupJsonNulo(array $config): ?string
    {
        if (!empty($config['logBackupNotNull'])) {
            return '{}';
        }

        return null;
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
