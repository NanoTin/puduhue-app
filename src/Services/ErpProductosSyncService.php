<?php

require_once dirname(__DIR__) . '/Config/Env.php';
require_once dirname(__DIR__) . '/api-external/FinnegansClient.php';
require_once __DIR__ . '/ErpListadoEndpointsService.php';

class ErpProductosSyncService
{
    private const ENDPOINT_LIST = 'ERP_PRODUCTOS_LIST';
    private const ENDPOINT_DETALLE = 'ERP_PRODUCTOS_DETALLE';

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
        $endpoint = $this->endpointsService->obtenerPorCodigo(self::ENDPOINT_LIST);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP no encontrado o inactivo: ' . self::ENDPOINT_LIST);
        }

        $inicio = new \DateTimeImmutable();
        $requestMeta = [
            'endpointCodigo' => self::ENDPOINT_LIST,
            'maestro' => 'invitems',
            'metodo' => 'GET',
            'url' => $this->ocultarAccessToken($this->endpointsService->construirUrlEndpoint($endpoint)),
            'detalleEndpointCodigo' => self::ENDPOINT_DETALLE,
            'match' => 'invitems.erpinvitemcod',
            'noSobrescribe' => ['erpinvitemcod', 'invitemusocodigo', 'invitemleche'],
        ];

        try {
            $response = $this->ejecutarGet($endpoint, $usuarioId, $disp, $ip);
            if ((int)$response['httpCode'] < 200 || (int)$response['httpCode'] >= 300) {
                throw new \RuntimeException('Consulta ERP de Productos respondio con error HTTP ' . (int)$response['httpCode'] . '.');
            }

            $productosLista = $this->normalizarLista($response['decoded']);
            if (empty($productosLista)) {
                throw new \RuntimeException('Respuesta ERP no contiene productos validos.');
            }

            $items = [];
            foreach ($productosLista as $productoLista) {
                $detalle = $this->obtenerDetallePorCodigo((string)$productoLista['codigo'], $usuarioId, $disp, $ip);
                $items[] = $this->normalizarDetalle($productoLista, $detalle);
            }

            $conteos = $this->persistirProductos($items, $usuarioId, $disp, $ip);
            $estado = ((int)($conteos['omitidos'] ?? 0) > 0) ? 'PARCIAL' : 'OK';
            $mensaje = $estado === 'OK'
                ? 'Sincronizacion de Productos ejecutada correctamente.'
                : 'Sincronizacion de Productos finalizada con registros omitidos por dependencias faltantes.';
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
                    'detallesConsultados' => count($items),
                    'omitidos' => $conteos['omitidosDetalle'] ?? [],
                ],
                $usuarioId
            );
            $this->actualizarEndpointUltSync((int)$endpoint['erpendpointid'], $estado, $estado === 'OK' ? null : $mensaje);

            return [
                'estado' => $estado,
                'endpointCodigo' => self::ENDPOINT_LIST,
                'descripcion' => 'Productos',
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

    private function obtenerDetallePorCodigo(string $codigo, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $endpoint = $this->endpointsService->obtenerPorCodigo(self::ENDPOINT_DETALLE);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP de detalle no encontrado o inactivo: ' . self::ENDPOINT_DETALLE);
        }

        $response = $this->ejecutarGet($endpoint, $usuarioId, $disp, $ip, $codigo);
        if ((int)$response['httpCode'] < 200 || (int)$response['httpCode'] >= 300 || !is_array($response['decoded'])) {
            throw new \RuntimeException('No se pudo obtener detalle ERP para producto ' . $codigo . '.');
        }

        return $response['decoded'];
    }

    private function normalizarLista($decoded): array
    {
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new \RuntimeException('Respuesta ERP de Productos no tiene formato de lista.');
        }

        $productos = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = trim((string)($row['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }

            $productos[$codigo] = [
                'codigo' => $codigo,
                'nombre' => trim((string)($row['nombre'] ?? $codigo)),
                'descripcion' => trim((string)($row['descripcion'] ?? '')),
                'activo' => !empty($row['activo']) ? 1 : 0,
            ];
        }

        return array_values($productos);
    }

    private function normalizarDetalle(array $productoLista, array $detalle): array
    {
        $codigo = trim((string)($detalle['Codigo'] ?? $productoLista['codigo']));
        if ($codigo === '') {
            throw new \RuntimeException('Detalle ERP de producto sin Codigo.');
        }

        $nombre = trim((string)($detalle['Nombre'] ?? $detalle['Descripcion'] ?? $productoLista['nombre'] ?? $codigo));
        $unidadCodigo = $this->primerValorNoVacio([
            $detalle['UnidadCodigoCompra'] ?? null,
            $detalle['UnidadCodigoStock1'] ?? null,
            $detalle['UnidadCodigoVenta'] ?? null,
        ]);
        $costo = $this->decimalDesdeValor($detalle['CostoStandard'] ?? null);

        return [
            'codigo' => $this->limitarTexto($codigo, 50),
            'descripcion' => $this->limitarTexto($nombre !== '' ? $nombre : $codigo, 50),
            'unidadCodigo' => $unidadCodigo,
            'invunidmedid' => $unidadCodigo !== null ? $this->buscarIdPorCodigo('invunidadesmedidas', 'invunidmedid', 'erpunidmedcod', $unidadCodigo) : null,
            'invitemstockeable' => $this->boolComoTinyint($detalle['EsStockeable'] ?? false),
            'invitemactivo' => $this->boolComoTinyint($detalle['Activo'] ?? $productoLista['activo'] ?? false),
            'familiaCodigo' => $this->nullIfEmpty((string)($detalle['ProductoFamiliaCodigo'] ?? '')),
            'familiaid' => $this->buscarIdOpcional('familias', 'familiaid', 'familiacod', $detalle['ProductoFamiliaCodigo'] ?? null),
            'subfamiliaCodigo' => $this->nullIfEmpty((string)($detalle['ProductoSubFamiliaCodigo'] ?? '')),
            'subfamiliaid' => $this->buscarIdOpcional('subfamilias', 'subfamiliaid', 'subfamiliacod', $detalle['ProductoSubFamiliaCodigo'] ?? null),
            'tasaCodigo' => $this->nullIfEmpty((string)($detalle['TasaImpositivaCodigoCompra'] ?? '')),
            'erptasaimpositivaid' => $this->buscarIdOpcional('erptasasimpositivas', 'erptasaimpositivaid', 'erptasaimpositivacod', $detalle['TasaImpositivaCodigoCompra'] ?? null),
            'partidaCodigo' => $this->resolverDimensionDistribucionCodigo($detalle, 'DIMPARFIN'),
            'erppartidafinancieraid' => $this->buscarIdOpcional('erppartidasfinancieras', 'erppartidafinancieraid', 'erppartidafinancieracod', $this->resolverDimensionDistribucionCodigo($detalle, 'DIMPARFIN')),
            'invitemcompra' => $this->resolverEsCompra($detalle),
            'invitemcostoestandar' => $costo > 0 ? $costo : 0.0,
            'invitemcostoestandarfechahora' => $costo > 0 ? $this->normalizarFechaCosto($detalle['FechaCostoStandard'] ?? null) : null,
            'detalleResumen' => [
                'ProductoFamiliaCodigo' => $detalle['ProductoFamiliaCodigo'] ?? null,
                'ProductoSubFamiliaCodigo' => $detalle['ProductoSubFamiliaCodigo'] ?? null,
                'UnidadCodigoCompra' => $detalle['UnidadCodigoCompra'] ?? null,
                'UnidadCodigoStock1' => $detalle['UnidadCodigoStock1'] ?? null,
                'TasaImpositivaCodigoCompra' => $detalle['TasaImpositivaCodigoCompra'] ?? null,
                'DIMPARFIN' => $this->resolverDimensionDistribucionCodigo($detalle, 'DIMPARFIN'),
                'CheckSeCompra' => $detalle['CheckSeCompra'] ?? null,
                'CostoStandard' => $detalle['CostoStandard'] ?? null,
                'FechaCostoStandard' => $detalle['FechaCostoStandard'] ?? null,
            ],
        ];
    }

    private function persistirProductos(array $items, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $pdo = $this->db->getPdo();
        $conteos = [
            'leidos' => count($items),
            'insertados' => 0,
            'actualizados' => 0,
            'inactivos' => 0,
            'omitidos' => 0,
            'omitidosDetalle' => [],
        ];
        $codigosVigentes = array_map(static fn(array $row): string => $row['codigo'], $items);

        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $existente = $this->obtenerInvitemPorCodigo($item['codigo']);
                if ($existente !== null) {
                    $item = $this->aplicarFallbacksExistente($item, $existente);
                }

                if (empty($item['invunidmedid'])) {
                    $conteos['omitidos']++;
                    $conteos['omitidosDetalle'][] = [
                        'codigo' => $item['codigo'],
                        'motivo' => 'Unidad de medida ERP no sincronizada',
                        'unidadCodigo' => $item['unidadCodigo'] ?? null,
                    ];
                    continue;
                }

                if ($existente === null) {
                    $id = $this->insertarProducto($item, $usuarioId, $disp, $ip);
                    $this->registrarInvitemLog($id, 'INS', $item, null, $usuarioId, $disp, $ip);
                    $conteos['insertados']++;
                    continue;
                }

                if ($this->productoCambio($existente, $item)) {
                    $this->actualizarProducto((int)$existente['invitemid'], $item, $usuarioId, $disp, $ip);
                    $this->registrarInvitemLog((int)$existente['invitemid'], 'UPD', $item, $existente, $usuarioId, $disp, $ip);
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

    private function insertarProducto(array $item, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $this->db->execute(
            'INSERT INTO invitems (
                invitemdsc,
                invunidmedid,
                erpinvitemcod,
                invitemleche,
                invitemstockeable,
                invitemusocodigo,
                familiaid,
                subfamiliaid,
                erptasaimpositivaid,
                erppartidafinancieraid,
                invitemcompra,
                invitemcostoestandar,
                invitemcostoestandarfechahora,
                invitemactivo,
                auditcreacionusuarioid,
                auditcreaciondispositivo,
                auditcreacionip,
                auditediciondispositivo,
                auditedicionip
            ) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $item['descripcion'],
                (int)$item['invunidmedid'],
                $item['codigo'],
                (int)$item['invitemstockeable'],
                'BDG',
                $item['familiaid'],
                $item['subfamiliaid'],
                $item['erptasaimpositivaid'],
                $item['erppartidafinancieraid'],
                (int)$item['invitemcompra'],
                (float)$item['invitemcostoestandar'],
                $item['invitemcostoestandarfechahora'],
                (int)$item['invitemactivo'],
                $usuarioId,
                $this->limitarTexto((string)$disp, 100),
                $this->limitarTexto((string)$ip, 50),
                $this->limitarTexto((string)$disp, 100),
                $this->limitarTexto((string)$ip, 50),
            ]
        );

        return (int)$this->db->getPdo()->lastInsertId();
    }

    private function actualizarProducto(int $invitemId, array $item, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $this->db->execute(
            'UPDATE invitems
             SET invitemdsc = ?,
                 invunidmedid = ?,
                 invitemstockeable = ?,
                 familiaid = ?,
                 subfamiliaid = ?,
                 erptasaimpositivaid = ?,
                 erppartidafinancieraid = ?,
                 invitemcompra = ?,
                 invitemcostoestandar = ?,
                 invitemcostoestandarfechahora = ?,
                 invitemactivo = ?,
                 auditedicionusuarioid = ?,
                 auditediciondispositivo = ?,
                 auditedicionip = ?,
                 auditedicionfechahora = NOW()
             WHERE invitemid = ?',
            [
                $item['descripcion'],
                (int)$item['invunidmedid'],
                (int)$item['invitemstockeable'],
                $item['familiaid'],
                $item['subfamiliaid'],
                $item['erptasaimpositivaid'],
                $item['erppartidafinancieraid'],
                (int)$item['invitemcompra'],
                (float)$item['invitemcostoestandar'],
                $item['invitemcostoestandarfechahora'],
                (int)$item['invitemactivo'],
                $usuarioId,
                $this->limitarTexto((string)$disp, 100),
                $this->limitarTexto((string)$ip, 50),
                $invitemId,
            ]
        );
    }

    private function inactivarAusentes(array $codigosVigentes, int $usuarioId, ?string $disp, ?string $ip): int
    {
        $vigentes = array_flip($codigosVigentes);
        $rows = $this->db->select(
            "SELECT *
             FROM invitems
             WHERE erpinvitemcod <> ''
               AND invitemactivo = 1"
        );
        $inactivos = 0;

        foreach ($rows as $row) {
            $codigo = (string)($row['erpinvitemcod'] ?? '');
            if ($codigo === '' || isset($vigentes[$codigo])) {
                continue;
            }

            $this->db->execute(
                'UPDATE invitems
                 SET invitemactivo = 0,
                     auditedicionusuarioid = ?,
                     auditediciondispositivo = ?,
                     auditedicionip = ?,
                     auditedicionfechahora = NOW()
                 WHERE invitemid = ?',
                [
                    $usuarioId,
                    $this->limitarTexto((string)$disp, 100),
                    $this->limitarTexto((string)$ip, 50),
                    (int)$row['invitemid'],
                ]
            );
            $this->registrarInvitemLog(
                (int)$row['invitemid'],
                'ANL',
                ['motivo' => 'Ausente en sincronizacion ERP Productos', 'erpinvitemcod' => $codigo],
                $row,
                $usuarioId,
                $disp,
                $ip
            );
            $inactivos++;
        }

        return $inactivos;
    }

    private function obtenerInvitemPorCodigo(string $codigo): ?array
    {
        $rows = $this->db->select('SELECT * FROM invitems WHERE erpinvitemcod = ? LIMIT 1', [$codigo]);
        return $rows[0] ?? null;
    }

    private function aplicarFallbacksExistente(array $item, array $existente): array
    {
        if (empty($item['invunidmedid'])) {
            $item['invunidmedid'] = (int)$existente['invunidmedid'];
        }

        return $item;
    }

    private function productoCambio(array $existente, array $item): bool
    {
        $campos = [
            'invitemdsc' => 'descripcion',
            'invunidmedid' => 'invunidmedid',
            'invitemstockeable' => 'invitemstockeable',
            'familiaid' => 'familiaid',
            'subfamiliaid' => 'subfamiliaid',
            'erptasaimpositivaid' => 'erptasaimpositivaid',
            'erppartidafinancieraid' => 'erppartidafinancieraid',
            'invitemcompra' => 'invitemcompra',
            'invitemactivo' => 'invitemactivo',
        ];

        foreach ($campos as $dbCol => $itemKey) {
            if ((string)($existente[$dbCol] ?? '') !== (string)($item[$itemKey] ?? '')) {
                return true;
            }
        }

        if (round((float)($existente['invitemcostoestandar'] ?? 0), 4) !== round((float)($item['invitemcostoestandar'] ?? 0), 4)) {
            return true;
        }

        return (string)($existente['invitemcostoestandarfechahora'] ?? '') !== (string)($item['invitemcostoestandarfechahora'] ?? '');
    }

    private function registrarInvitemLog(int $invitemId, string $tipo, array $param, ?array $backup, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $this->db->execute(
            'INSERT INTO invitemslog (
                invitemid,
                logusuarioid,
                logdispositivo,
                logip,
                logtipo,
                logparamjson,
                logregbkpjson
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $invitemId,
                $usuarioId,
                $this->limitarTexto((string)$disp, 100),
                $this->limitarTexto((string)$ip, 50),
                $tipo,
                $this->jsonEncode($param),
                $backup !== null ? $this->jsonEncode($backup) : '{}',
            ]
        );
    }

    private function buscarIdOpcional(string $table, string $pk, string $codeCol, $codigo): ?int
    {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return null;
        }

        return $this->buscarIdPorCodigo($table, $pk, $codeCol, $codigo);
    }

    private function buscarIdPorCodigo(string $table, string $pk, string $codeCol, string $codigo): ?int
    {
        $rows = $this->db->select('SELECT ' . $pk . ' FROM ' . $table . ' WHERE ' . $codeCol . ' = ? LIMIT 1', [$codigo]);
        if (empty($rows)) {
            return null;
        }

        return (int)$rows[0][$pk];
    }

    private function resolverDimensionDistribucionCodigo(array $detalle, string $dimensionCodigo): ?string
    {
        $dimensiones = $detalle['Dimensiones'] ?? [];
        if (!is_array($dimensiones)) {
            return null;
        }

        foreach ($dimensiones as $dimension) {
            if (!is_array($dimension)) {
                continue;
            }
            if (strtoupper(trim((string)($dimension['DimensionCodigo'] ?? ''))) !== strtoupper($dimensionCodigo)) {
                continue;
            }

            return $this->nullIfEmpty((string)($dimension['DimensionDistribucionCodigo'] ?? ''));
        }

        return null;
    }

    private function resolverEsCompra(array $detalle): int
    {
        foreach (['CheckSeCompra', 'EsCompra', 'SeCompra'] as $key) {
            if (array_key_exists($key, $detalle)) {
                return $this->boolComoTinyint($detalle[$key]);
            }
        }

        return $this->primerValorNoVacio([
            $detalle['CuentaCodigoCompra'] ?? null,
            $detalle['ConceptoCodigoCompra'] ?? null,
            $detalle['UnidadCodigoCompra'] ?? null,
            $detalle['TasaImpositivaCodigoCompra'] ?? null,
        ]) !== null ? 1 : 0;
    }

    private function boolComoTinyint($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int)$value) === 1 ? 1 : 0;
        }

        return in_array(strtolower(trim((string)$value)), ['true', 't', 'si', 'sí', 's', 'yes', 'y', '1'], true) ? 1 : 0;
    }

    private function decimalDesdeValor($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float)$value, 4);
    }

    private function normalizarFechaCosto($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        }
    }

    private function primerValorNoVacio(array $values): ?string
    {
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
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

        return trim($value);
    }

    private function jsonEncode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function limitarTexto(?string $value, int $max): string
    {
        $value = (string)$value;
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }
}
