<?php

require_once dirname(__DIR__) . '/Config/Env.php';
require_once dirname(__DIR__) . '/Helpers/Logger.php';
require_once dirname(__DIR__) . '/api-external/FinnegansClient.php';
require_once dirname(__DIR__) . '/api-external/DTOs/ProduccionLecheRequestDTO.php';
require_once dirname(__DIR__) . '/api-external/DTOs/ProduccionLecheResponseDTO.php';

use ApiExternal\DTOs\ProduccionLecheRequestDTO;
use ApiExternal\DTOs\ProduccionLecheResponseDTO;
use App\Helpers\Logger;

class ProdlecheService
{
    private const ENTIDAD_CODE = 'PRDLCH';

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

    public function listarProdleche(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroProdlecheid'          => $this->nullIfEmpty($filtros['filtroProdlecheid'] ?? null),
            'filtroProdlechestatus'      => $this->nullIfEmpty($filtros['filtroProdlechestatus'] ?? null),
            'filtroEmpresaid'            => $this->nullIfEmpty($filtros['filtroEmpresaid'] ?? null),
            'filtroFundoid'              => $this->nullIfEmpty($filtros['filtroFundoid'] ?? null),
            'filtroFechaDesde'           => $this->nullIfEmpty($filtros['filtroFechaDesde'] ?? null),
            'filtroFechaHasta'           => $this->nullIfEmpty($filtros['filtroFechaHasta'] ?? null),
            'filtroProdlecheobservacion' => $this->nullIfEmpty($filtros['filtroProdlecheobservacion'] ?? null),
            'filtroProdlechehorario'     => $this->nullIfEmpty($filtros['filtroProdlechehorario'] ?? null),
        ];

        return $this->db->callSpQuery(
            'sp_prodleche_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function crearProdleche(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['prodlecheid']);

        return $this->db->callSpMaint(
            'sp_prodleche_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarProdleche(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['prodlecheid'] = $id;

        return $this->db->callSpMaint(
            'sp_prodleche_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularProdleche(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['prodlecheid' => $id];

        return $this->db->callSpMaint(
            'sp_prodleche_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarProdlechePorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_prodleche_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['prodlecheid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }

    public function listarDetallesPorProdleche(int $prodlecheId): array
    {
        $sql = 'SELECT prodlecheid, prodlechetipoid, pldetlitros, pldetvacas, pldetlitrosxvaca, prodlechecod, erpdocumentocod
                FROM prodlechedetalle
                WHERE prodlecheid = ?';
        return $this->db->select($sql, [$prodlecheId]);
    }

    public function cargaMasivaProdleche(array $payload, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_prodleche_carga_masiva',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );

        return $result['meta'] ?? [];
    }

    /**
     * Sincroniza un registro de produccion de leche contra Finnegans.
     */
    public function sincronizarProdlecheConErp(int $prodlecheId, int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): void
    {
        $logId = $this->iniciarLogSincronizacion($tipoExec, self::ENTIDAD_CODE, $prodlecheId, $usuarioId);
        $logPayload = [
            'detalles' => [],
        ];

        try {
            $cabecera = $this->obtenerCabecera($prodlecheId);
            if (empty($cabecera)) {
                throw new \RuntimeException('Registro de produccion no encontrado.');
            }
            if (($cabecera['prodlechestatus'] ?? '') !== 'PND') {
                throw new \RuntimeException('Solo se pueden sincronizar registros en estado pendiente.');
            }

            $detalles = $this->obtenerDetalles($prodlecheId);
            $detallesValidos = array_values(array_filter($detalles, static function (array $detalle): bool {
                return (float)($detalle['pldetlitros'] ?? 0) > 0;
            }));
            if (empty($detallesValidos)) {
                throw new \RuntimeException('No hay detalles con litros mayores a cero para integrar.');
            }

            $token = $this->finnegansClient->obtenerTokenVigente($usuarioId, $disp, $ip);
            $apiUrl = $this->finnegansClient->getEnvRequired(['ERP_API_URL_PRODLECH', 'erp_api_url_prodlech'], 'URL de API de produccion de leche');

            foreach ($detallesValidos as $detalle) {
                $requestDto = $this->armarRequest($cabecera, $detalle);
                $response = $this->finnegansClient->postJsonWithToken($apiUrl, $token, $requestDto->toArray());

                if ($this->finnegansClient->esTokenInvalido($response['decoded'], $response['httpCode'])) {
                    $token = $this->finnegansClient->refrescarToken($usuarioId, $disp, $ip);
                    $response = $this->finnegansClient->postJsonWithToken($apiUrl, $token, $requestDto->toArray());
                }

                $responseDto = ProduccionLecheResponseDTO::fromArray($response['decoded'] ?? []);

                $this->registrarLogProdleche(
                    $prodlecheId,
                    $usuarioId,
                    $disp,
                    $ip,
                    [
                        'request' => $requestDto->toArray(),
                        'response' => $responseDto->toArray(),
                        'httpCode' => $response['httpCode'],
                        'raw' => $response['raw'],
                    ]
                );

                if (!$responseDto->isSuccess()) {
                    $message = $responseDto->error ?? $responseDto->message ?? 'Error en la integracion con ERP.';
                    throw new \RuntimeException($message);
                }

                $this->marcarDetalleIntegrado($prodlecheId, (int)($detalle['prodlechetipoid'] ?? 0), $responseDto);

                $logPayload['detalles'][] = [
                    'prodlechetipoid' => (int)($detalle['prodlechetipoid'] ?? 0),
                    'litros' => (float)($detalle['pldetlitros'] ?? 0),
                    'request' => $requestDto->toArray(),
                    'response' => $responseDto->toArray(),
                    'httpCode' => $response['httpCode'],
                ];
            }

            $this->marcarCabeceraIntegrada($prodlecheId, $usuarioId, $disp, $ip);
            $logPayload['totalDetalles'] = count($logPayload['detalles']);
            $this->finalizarLogSincronizacion($logId, 'success', 'Sincronizacion OK', $logPayload);
        } catch (\Throwable $e) {
            $logPayload['totalDetalles'] = count($logPayload['detalles']);
            $this->finalizarLogSincronizacion($logId, 'error', $e->getMessage(), $logPayload);
            $this->registrarLogProdleche(
                $prodlecheId,
                $usuarioId,
                $disp,
                $ip,
                ['error' => $e->getMessage(), 'prodlecheId' => $prodlecheId]
            );
            Logger::error('Integracion ERP prodleche ' . $prodlecheId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    private function obtenerCabecera(int $id): array
    {
        $sql = 'SELECT p.*, e.empresaiderp, e.razonsocial, f.fundonombre, u.usuarionombre
                FROM prodleche p
                INNER JOIN empresas e ON e.empresaid = p.empresaid
                INNER JOIN fundos f ON f.fundoid = p.fundoid
                LEFT JOIN usuarios u ON u.usuarioid = p.auditcreacionusuarioid
                WHERE p.prodlecheid = ?';
        $rows = $this->db->select($sql, [$id]);
        return $rows[0] ?? [];
    }

    private function obtenerDetalles(int $id): array
    {
        $sql = 'SELECT d.*, pt.prodlechetipodsc, ii.erpinvitemcod
                FROM prodlechedetalle d
                LEFT JOIN prodlechetipos pt ON pt.prodlechetipoid = d.prodlechetipoid
                LEFT JOIN invitems ii ON ii.invitemid = pt.invitemid
                WHERE d.prodlecheid = ? AND d.pldetlitrosxvaca > 0 AND (d.erpdocumentocod IS NULL OR d.erpdocumentocod = "" OR d.erpdocumentocod = "PEND")';
        return $this->db->select($sql, [$id]);
    }

    private function armarRequest(array $cabecera, array $detalle): ProduccionLecheRequestDTO
    {
        $empresaIdErp = (string)($cabecera['empresaiderp'] ?? '');
        $establecimiento = (string)($cabecera['pl_erpestablecimientocod'] ?? '');
        $lote = (string)($cabecera['pl_erplotecod'] ?? '');
        $categoria = (string)($cabecera['pl_erpleche_invcateganimalcod'] ?? '');
        $bodega = (string)($cabecera['pl_erpleche_invbodegacod'] ?? '');
        $observacion = (string)($cabecera['prodlecheobservacion'] ?? '');

        if ($empresaIdErp === '' || $establecimiento === '' || $lote === '' || $categoria === '' || $bodega === '') {
            throw new \RuntimeException('Faltan datos ERP obligatorios en la cabecera.');
        }

        $fecha = $cabecera['prodlechefecha'] ?? null;
        $fechaFmt = '';
        if ($fecha) {
            try {
                $fechaFmt = (new \DateTimeImmutable($fecha))->format('Y-m-d');
            } catch (\Throwable $e) {
                throw new \RuntimeException('Fecha de produccion invalida.');
            }
        }

        $descripcionParts = [
            $cabecera['fundonombre'] ?? '',
            $detalle['prodlechetipodsc'] ?? '',
        ];
        if (!empty($cabecera['usuarionombre'])) {
            $descripcionParts[] = 'Registrado por: ' . $cabecera['usuarionombre'];
        }
        if ($observacion !== '') {
            $descripcionParts[] = 'Obs: ' . $observacion;
        }
        $descripcion = trim(implode(' - ', array_filter($descripcionParts)));

        $productoId = $detalle['erpinvitemcod'] ?? $detalle['prodlechetipoid'] ?? '';
        if ($productoId === '') {
            throw new \RuntimeException('No se encontro codigo de producto para el ERP.');
        }

        $movimientos = [[
            'ProductoID' => (string)$productoId,
            'DepositoIDOrigen' => $bodega,
            'OrganizacionIDStock' => '',
            'Litros' => (float)($detalle['pldetlitros'] ?? 0),
            'Grasa' => 0.0,
            'UFC' => 0.0,
            'Acidez' => 0.0,
            'Proteinas' => 0.0,
            'Temperatura' => 0.0,
            'CelSomaticas' => 0.0,
            'PartidaID' => null,
        ]];

        return new ProduccionLecheRequestDTO(
            $empresaIdErp,
            $fechaFmt,
            $establecimiento,
            $lote,
            $categoria,
            (string)($detalle['prodlechecod'] ?? ''),
            (int)($detalle['pldetvacas'] ?? 0),
            $descripcion,
            $movimientos
        );
    }

    private function marcarDetalleIntegrado(int $prodlecheId, int $prodlecheTipoId, ProduccionLecheResponseDTO $response): void
    {
        $codigoRespuesta = $response->documento ?? $response->id ?? 'OK';
        $sql = 'UPDATE prodlechedetalle SET erpdocumentocod = ? WHERE prodlecheid = ? AND prodlechetipoid = ?';
        $this->db->execute($sql, [$codigoRespuesta, $prodlecheId, $prodlecheTipoId]);
    }

    private function marcarCabeceraIntegrada(int $prodlecheId, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sql = 'UPDATE prodleche
                SET prodlechestatus = ?, pl_erp_sync = NOW(),
                    auditedicionusuarioid = ?, auditediciondispositivo = ?, auditedicionip = ?
                WHERE prodlecheid = ?';
        $this->db->execute($sql, [
            'CN',
            $usuarioId,
            $disp ?? '',
            $ip ?? '',
            $prodlecheId,
        ]);
    }

    private function registrarLogProdleche(int $prodlecheId, int $usuarioId, ?string $disp, ?string $ip, array $payload, string $tipo = 'QRY'): void
    {
        $sql = 'INSERT INTO prodlechelog (prodlecheid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $params = [
            $prodlecheId,
            $usuarioId,
            $disp ?? '',
            $ip ?? '',
            $tipo,
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            '{}',
        ];
        $this->db->execute($sql, $params);
    }

    private function iniciarLogSincronizacion(string $tipoExec, string $entidad, int $entidadId, int $usuarioId): ?int
    {
        $sql = 'INSERT INTO sincronizacionerplog
                (sincronizaciontipoexec, entidad, entidadid, accion, estado, mensaje, jsondatos, usuarioid)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $params = [
            $this->normalizarTipoExec($tipoExec),
            $entidad,
            $entidadId,
            'UPD',
            'processing',
            '',
            null,
            $usuarioId,
        ];

        try {
            $this->db->execute($sql, $params);
            return (int)$this->db->getPdo()->lastInsertId();
        } catch (\Throwable $e) {
            Logger::error('No se pudo iniciar log sincronizacion ERP: ' . $e->getMessage());
            return null;
        }
    }

    private function finalizarLogSincronizacion(?int $logId, string $estado, string $mensaje, ?array $jsonDatos): void
    {
        if (!$logId) {
            return;
        }

        $sql = 'UPDATE sincronizacionerplog
                SET estado = ?, mensaje = ?, jsondatos = ?, fechafin = NOW()
                WHERE sincronizacionlogid = ?';
        $payload = $jsonDatos ? json_encode($jsonDatos, JSON_UNESCAPED_UNICODE) : null;

        try {
            $this->db->execute($sql, [$estado, $mensaje, $payload, $logId]);
        } catch (\Throwable $e) {
            Logger::error('No se pudo cerrar log sincronizacion ERP: ' . $e->getMessage());
        }
    }

    private function normalizarTipoExec(?string $tipoExec): string
    {
        $tipo = strtoupper(trim((string)$tipoExec));
        return in_array($tipo, ['MANUAL', 'CRON'], true) ? $tipo : 'MANUAL';
    }

    private function nullIfEmpty($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }
}
