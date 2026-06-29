<?php

require_once dirname(__DIR__) . '/Config/Env.php';
require_once dirname(__DIR__) . '/api-external/FinnegansClient.php';

class ErpListadoEndpointsService
{
    private \Database $db;
    private \FinnegansClient $finnegansClient;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/Config/Database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $this->db = \Database::getInstance();
        $this->finnegansClient = new \FinnegansClient($this->db);
    }

    public function listarActivos(bool $soloGet = true): array
    {
        $sql = $this->baseEndpointSelect() . ' WHERE erpendpointactivo = 1';
        $params = [];

        if ($soloGet) {
            $sql .= ' AND erpendpointmetodo = ?';
            $params[] = 'GET';
        }

        $sql .= ' ORDER BY erpendpointgrupoid ASC, erpendpointorden ASC, erpendpointdescripcion ASC';

        return $this->db->select($sql, $params);
    }

    public function obtenerPorCodigo(string $endpointCodigo): ?array
    {
        $endpointCodigo = $this->normalizarCodigo($endpointCodigo);
        $rows = $this->db->select(
            $this->baseEndpointSelect() . ' WHERE erpendpointcodigo = ? AND erpendpointactivo = 1 LIMIT 1',
            [$endpointCodigo]
        );

        return $rows[0] ?? null;
    }

    public function listarHijosPorCodigoPadre(string $endpointCodigoPadre): array
    {
        $endpoint = $this->obtenerPorCodigo($endpointCodigoPadre);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP no encontrado o inactivo.');
        }

        return $this->db->select(
            $this->baseEndpointSelect() . '
             WHERE erpendpointpadreid = ?
               AND erpendpointactivo = 1
             ORDER BY erpendpointorden ASC',
            [(int)$endpoint['erpendpointid']]
        );
    }

    public function resolverPlanEjecucion(string $endpointCodigo, bool $soloOnDemand = false): array
    {
        $endpoint = $this->obtenerPorCodigo($endpointCodigo);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP no encontrado o inactivo.');
        }

        if ($soloOnDemand && (int)($endpoint['erpendpointpermiteondemand'] ?? 0) !== 1) {
            throw new \RuntimeException('El endpoint ERP no permite ejecucion bajo demanda.');
        }

        $grupoId = (int)($endpoint['erpendpointgrupoid'] ?? 0);
        if ($grupoId === 0) {
            return [$endpoint];
        }

        $sql = $this->baseEndpointSelect() . '
                WHERE erpendpointactivo = 1
                  AND erpendpointmetodo = ?
                  AND erpendpointgrupoid = ?
                ORDER BY erpendpointorden ASC';
        return $this->db->select($sql, ['GET', $grupoId]);
    }

    public function diagnosticarPlan(?string $endpointCodigo = null): array
    {
        $endpoints = $endpointCodigo === null || trim($endpointCodigo) === ''
            ? $this->listarActivos(true)
            : $this->resolverPlanEjecucion($endpointCodigo, false);

        $baseUrl = $this->obtenerBaseUrlConfigurable();

        return array_map(function (array $endpoint) use ($baseUrl): array {
            $hijos = $this->listarHijosPorId((int)$endpoint['erpendpointid']);
            $resource = (string)($endpoint['erpendpointrecurso'] ?? '');
            $resourcePreview = str_replace('{codigo}', '{codigo}', $resource);
            $urlPreview = $this->construirUrlPreview($baseUrl['value'], $resourcePreview);

            return [
                'endpoint' => $endpoint,
                'hijos' => $hijos,
                'urlPreview' => $urlPreview,
                'baseUrlConfigurada' => $baseUrl['configured'],
                'baseUrlKey' => $baseUrl['key'],
                'ejecucionPermitida' => [
                    'ondemand' => (int)($endpoint['erpendpointpermiteondemand'] ?? 0) === 1,
                    'auto' => (int)($endpoint['erpendpointpermiteauto'] ?? 0) === 1,
                ],
            ];
        }, $endpoints);
    }

    public function construirUrlEndpoint(array $endpoint, ?string $codigoRecurso = null): string
    {
        $resource = (string)($endpoint['erpendpointrecurso'] ?? '');
        if ((int)($endpoint['erpendpointrequierecodigo'] ?? 0) === 1) {
            $codigoRecurso = trim((string)$codigoRecurso);
            if ($codigoRecurso === '') {
                throw new \InvalidArgumentException('El codigo de recurso es obligatorio para este endpoint ERP.');
            }
            $resource = str_replace('{codigo}', rawurlencode($codigoRecurso), $resource);
        }

        if (str_contains($resource, '{codigo}')) {
            throw new \InvalidArgumentException('El recurso ERP requiere codigo, pero no se pudo resolver.');
        }

        return $this->finnegansClient->buildUrlFromBaseAndResource(
            $this->finnegansClient->getBaseUrl(),
            $resource
        );
    }

    public function ejecutarGetPorCodigo(
        string $endpointCodigo,
        int $usuarioId,
        ?string $disp,
        ?string $ip,
        string $tipoExec = 'TECNICO',
        ?string $codigoRecurso = null
    ): array {
        $endpoint = $this->obtenerPorCodigo($endpointCodigo);
        if ($endpoint === null) {
            throw new \RuntimeException('Endpoint ERP no encontrado o inactivo.');
        }

        return $this->ejecutarGetEndpoint($endpoint, $usuarioId, $disp, $ip, $tipoExec, $codigoRecurso);
    }

    public function ejecutarPlanOndemand(
        string $endpointCodigo,
        int $usuarioId,
        ?string $disp,
        ?string $ip
    ): array {
        $plan = $this->resolverPlanEjecucion($endpointCodigo, true);
        $resultados = [];
        $estadoGeneral = 'OK';

        foreach ($plan as $endpoint) {
            if ((int)($endpoint['erpendpointrequierecodigo'] ?? 0) === 1) {
                $resultados[] = [
                    'endpointCodigo' => (string)($endpoint['erpendpointcodigo'] ?? ''),
                    'descripcion' => (string)($endpoint['erpendpointdescripcion'] ?? ''),
                    'estado' => 'OMITIDO',
                    'mensaje' => 'Endpoint de detalle requiere codigo; se ejecutara cuando el sincronizador de maestro resuelva codigos desde la respuesta padre.',
                ];
                continue;
            }

            try {
                $resultado = $this->ejecutarGetEndpoint($endpoint, $usuarioId, $disp, $ip, 'MANUAL');
                $resultados[] = [
                    'endpointCodigo' => (string)($endpoint['erpendpointcodigo'] ?? ''),
                    'descripcion' => (string)($endpoint['erpendpointdescripcion'] ?? ''),
                    'estado' => $resultado['estado'],
                    'mensaje' => 'HTTP ' . (int)$resultado['httpCode'] . ' - registros leidos: ' . (int)$resultado['registrosLeidos'],
                    'httpCode' => (int)$resultado['httpCode'],
                    'registrosLeidos' => (int)$resultado['registrosLeidos'],
                ];

                if (($resultado['estado'] ?? '') !== 'OK') {
                    $estadoGeneral = 'PARCIAL';
                }
            } catch (\Throwable $e) {
                $estadoGeneral = 'PARCIAL';
                $resultados[] = [
                    'endpointCodigo' => (string)($endpoint['erpendpointcodigo'] ?? ''),
                    'descripcion' => (string)($endpoint['erpendpointdescripcion'] ?? ''),
                    'estado' => 'ERROR',
                    'mensaje' => $e->getMessage(),
                ];
            }
        }

        return [
            'estado' => $estadoGeneral,
            'resultados' => $resultados,
        ];
    }

    public function ejecutarGetEndpoint(
        array $endpoint,
        int $usuarioId,
        ?string $disp,
        ?string $ip,
        string $tipoExec = 'TECNICO',
        ?string $codigoRecurso = null
    ): array {
        $this->validarEndpointGet($endpoint);

        $inicio = new \DateTimeImmutable();
        $url = $this->construirUrlEndpoint($endpoint, $codigoRecurso);
        $requestMeta = [
            'endpointCodigo' => (string)($endpoint['erpendpointcodigo'] ?? ''),
            'metodo' => 'GET',
            'url' => $this->ocultarAccessToken($url),
            'codigoRecurso' => $codigoRecurso,
        ];

        try {
            $token = $this->finnegansClient->obtenerTokenVigente($usuarioId, $disp, $ip);
            $response = $this->finnegansClient->getJsonWithToken($url, $token);

            if ($this->finnegansClient->esTokenInvalido($response['decoded'], (int)$response['httpCode'])) {
                $token = $this->finnegansClient->refrescarToken($usuarioId, $disp, $ip);
                $response = $this->finnegansClient->getJsonWithToken($url, $token);
            }

            $estado = $this->estadoDesdeRespuesta($response);
            $mensaje = $estado === 'OK'
                ? 'Consulta ERP ejecutada correctamente.'
                : 'Consulta ERP respondio con error HTTP ' . (int)$response['httpCode'] . '.';
            $registrosLeidos = $this->contarRegistrosRespuesta($response['decoded']);

            $this->registrarLogEndpoint(
                (int)$endpoint['erpendpointid'],
                $tipoExec,
                $inicio,
                $estado,
                $mensaje,
                $registrosLeidos,
                $requestMeta,
                $this->resumirRespuesta($response),
                $usuarioId
            );
            $this->actualizarUltimaSync((int)$endpoint['erpendpointid'], $estado, $estado === 'OK' ? null : $mensaje);

            return [
                'endpoint' => $endpoint,
                'estado' => $estado,
                'registrosLeidos' => $registrosLeidos,
                'httpCode' => (int)$response['httpCode'],
                'decoded' => $response['decoded'],
                'raw' => $response['raw'],
            ];
        } catch (\Throwable $e) {
            $this->registrarLogEndpoint(
                (int)$endpoint['erpendpointid'],
                $tipoExec,
                $inicio,
                'ERROR',
                $e->getMessage(),
                0,
                $requestMeta,
                ['error' => $e->getMessage()],
                $usuarioId
            );
            $this->actualizarUltimaSync((int)$endpoint['erpendpointid'], 'ERROR', $e->getMessage());
            throw $e;
        }
    }

    private function registrarLogEndpoint(
        int $endpointId,
        string $tipoExec,
        \DateTimeImmutable $inicio,
        string $estado,
        string $mensaje,
        int $registrosLeidos,
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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0, ?, ?, ?)';

        $this->db->execute($sql, [
            $endpointId,
            $this->normalizarTipoExec($tipoExec),
            $inicio->format('Y-m-d H:i:s'),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $estado,
            $this->limitarTexto($mensaje, 2000),
            $registrosLeidos,
            $this->jsonEncode($requestMeta),
            $this->jsonEncode($responseMeta),
            $usuarioId,
        ]);
    }

    public function listarLogsEndpoint(int $endpointId, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        if ($endpointId <= 0) {
            throw new \InvalidArgumentException('Endpoint ERP invalido.');
        }

        $sql = 'SELECT erpendpointlogid,
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
                FROM erplistadoendpointslog
                WHERE erpendpointid = ?';
        $params = [$endpointId];

        $fechaDesde = $this->normalizarFechaFiltro($fechaDesde);
        if ($fechaDesde !== null) {
            $sql .= ' AND DATE(erpendpointlogfechaini) >= ?';
            $params[] = $fechaDesde;
        }

        $fechaHasta = $this->normalizarFechaFiltro($fechaHasta);
        if ($fechaHasta !== null) {
            $sql .= ' AND DATE(erpendpointlogfechaini) <= ?';
            $params[] = $fechaHasta;
        }

        $sql .= ' ORDER BY erpendpointlogfechaini DESC, erpendpointlogid DESC LIMIT 100';

        return $this->db->select($sql, $params);
    }

    private function actualizarUltimaSync(int $endpointId, string $estado, ?string $error): void
    {
        $sql = 'UPDATE erplistadoendpoints
                SET erpendpointultsync = NOW(),
                    erpendpointultestado = ?,
                    erpendpointulterror = ?
                WHERE erpendpointid = ?';
        $this->db->execute($sql, [$estado, $this->limitarTexto($error, 2000), $endpointId]);
    }

    private function listarHijosPorId(int $endpointId): array
    {
        return $this->db->select(
            $this->baseEndpointSelect() . '
             WHERE erpendpointpadreid = ?
               AND erpendpointactivo = 1
             ORDER BY erpendpointorden ASC',
            [$endpointId]
        );
    }

    private function obtenerBaseUrlConfigurable(): array
    {
        foreach (['ERP_API_BASE_URL', 'erp_api_base_url', 'ERP_API_URL', 'erp_api_url'] as $key) {
            $value = \Env::get($key);
            if ($value !== null && $value !== '') {
                return [
                    'configured' => true,
                    'key' => $key,
                    'value' => rtrim((string)$value, '/'),
                ];
            }
        }

        return [
            'configured' => false,
            'key' => null,
            'value' => '{ERP_API_BASE_URL}',
        ];
    }

    private function construirUrlPreview(string $baseUrl, string $resource): string
    {
        if (preg_match('/^https?:\/\//i', $resource) === 1) {
            return $this->ocultarAccessToken($resource);
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($resource, '/');
    }

    private function validarEndpointGet(array $endpoint): void
    {
        if (strtoupper((string)($endpoint['erpendpointmetodo'] ?? '')) !== 'GET') {
            throw new \InvalidArgumentException('El servicio base solo ejecuta endpoints GET en este corte.');
        }
        if ((int)($endpoint['erpendpointactivo'] ?? 0) !== 1) {
            throw new \InvalidArgumentException('El endpoint ERP esta inactivo.');
        }
    }

    private function estadoDesdeRespuesta(array $response): string
    {
        $httpCode = (int)($response['httpCode'] ?? 0);
        return ($httpCode >= 200 && $httpCode < 300) ? 'OK' : 'ERROR';
    }

    private function contarRegistrosRespuesta($decoded): int
    {
        if (!is_array($decoded)) {
            return 0;
        }

        if (array_is_list($decoded)) {
            return count($decoded);
        }

        foreach (['data', 'items', 'rows', 'result', 'results'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key]) && array_is_list($decoded[$key])) {
                return count($decoded[$key]);
            }
        }

        return 1;
    }

    private function resumirRespuesta(array $response): array
    {
        $decoded = $response['decoded'];
        $raw = (string)($response['raw'] ?? '');

        return [
            'httpCode' => (int)($response['httpCode'] ?? 0),
            'decodedType' => gettype($decoded),
            'registrosLeidos' => $this->contarRegistrosRespuesta($decoded),
            'rawLength' => strlen($raw),
            'rawPreview' => $this->limitarTexto($raw, 1000),
        ];
    }

    private function ocultarAccessToken(string $url): string
    {
        return preg_replace('/([?&]ACCESS_TOKEN=)[^&]+/i', '$1***', $url) ?? $url;
    }

    private function baseEndpointSelect(): string
    {
        return 'SELECT erpendpointid,
                       erpendpointcodigo,
                       erpendpointdescripcion,
                       erpendpointrecurso,
                       erpendpointmetodo,
                       erpendpointtipo,
                       erpendpointproposito,
                       erpendpointgrupoid,
                       erpendpointorden,
                       erpendpointpadreid,
                       erpendpointrequierecodigo,
                       erpendpointpermiteondemand,
                       erpendpointpermiteauto,
                       erpendpointfrecuencia,
                       erpendpointdiaevento,
                       erpendpointhoraevento,
                       erpendpointformulariocall,
                       erpendpointjsonarchivoejemplo,
                       erpendpointultsync,
                       erpendpointultestado,
                       erpendpointulterror,
                       erpendpointactivo
                FROM erplistadoendpoints';
    }

    private function normalizarCodigo(string $codigo): string
    {
        $codigo = trim(strtoupper($codigo));
        if ($codigo === '') {
            throw new \InvalidArgumentException('El codigo de endpoint ERP es obligatorio.');
        }

        return $codigo;
    }

    private function normalizarTipoExec(string $tipoExec): string
    {
        $tipoExec = strtoupper(trim($tipoExec));
        return in_array($tipoExec, ['MANUAL', 'AUTO', 'TECNICO'], true) ? $tipoExec : 'TECNICO';
    }

    private function normalizarFechaFiltro(?string $fecha): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($dt === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException('La fecha de filtro debe tener formato YYYY-MM-DD.');
        }

        return $dt->format('Y-m-d');
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
