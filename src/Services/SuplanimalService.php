<?php

require_once dirname(__DIR__) . '/Config/Env.php';
require_once dirname(__DIR__) . '/Helpers/Logger.php';
require_once dirname(__DIR__) . '/api-external/FinnegansClient.php';
require_once dirname(__DIR__) . '/api-external/DTOs/SuplementacionRequestDTO.php';
require_once dirname(__DIR__) . '/api-external/DTOs/SuplementacionResponseDTO.php';

use ApiExternal\DTOs\SuplementacionRequestDTO;
use ApiExternal\DTOs\SuplementacionResponseDTO;
use App\Helpers\Logger;

class SuplanimalService
{
    private const ENTIDAD_CODE = 'SUPANML';

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

    public function listarSuplanimal(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroEmpresaid'             => $this->nullIfEmpty($filtros['filtroEmpresaid'] ?? null),
            'filtroFundoid'               => $this->nullIfEmpty($filtros['filtroFundoid'] ?? null),
            'filtroSuplanimalestatus'     => $this->nullIfEmpty($filtros['filtroSuplanimalestatus'] ?? null),
            'filtroInvbodegaid'           => $this->nullIfEmpty($filtros['filtroInvbodegaid'] ?? null),
            'filtroSuplanimalobservacion' => $this->nullIfEmpty($filtros['filtroSuplanimalobservacion'] ?? null),
            'filtroFechaDesde'            => $this->nullIfEmpty($filtros['filtroFechaDesde'] ?? null),
            'filtroFechaHasta'            => $this->nullIfEmpty($filtros['filtroFechaHasta'] ?? null),
        ];
        
        return $this->db->callSpQuery(
            'sp_suplanimal_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function crearSuplanimal(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['suplanimalid']);

        return $this->db->callSpMaint(
            'sp_suplanimal_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarSuplanimal(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['suplanimalid'] = $id;

        return $this->db->callSpMaint(
            'sp_suplanimal_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularSuplanimal(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['suplanimalid' => $id];

        return $this->db->callSpMaint(
            'sp_suplanimal_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarSuplanimalPorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['suplanimalid' => $id];
        $result = $this->db->callSpQuery(
            'sp_suplanimal_consulta_por_id',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($id) {
            return (int)($row['suplanimalid'] ?? 0) === $id;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }

    public function listarDetallesPorSuplanimal(int $suplanimalId): array
    {
        $sql = 'SELECT d.suplanimalid, d.suplanimallinea, d.invcateganimalid, d.sup_erpinvcateganimalcod,
                       d.invitemid, d.sup_erpinvitemcod, d.invunidmedid, d.sup_erpunidmedcod,
                       d.totalconsumido, d.totalanimales, d.dosisporanimal, d.erpdocumentocod,
                       ca.invcateganimaldsc, i.invitemdsc, um.invunidmeddsc
                FROM suplanimaldetalle d
                LEFT JOIN invcateganimal ca ON ca.invcateganimalid = d.invcateganimalid
                LEFT JOIN invitems i ON i.invitemid = d.invitemid
                LEFT JOIN invunidadesmedidas um ON um.invunidmedid = d.invunidmedid
                WHERE d.suplanimalid = ?
                ORDER BY d.suplanimallinea ASC';
        return $this->db->select($sql, [$suplanimalId]);
    }

    public function sincronizarSuplanimalConErp(int $suplanimalId, int $usuarioId, ?string $disp, ?string $ip, string $tipoExec = 'MANUAL'): void
    {
        $logId = $this->iniciarLogSincronizacion($tipoExec, self::ENTIDAD_CODE, $suplanimalId, $usuarioId);
        $logPayload = [
            'grupos' => [],
        ];

        try {
            $cabecera = $this->obtenerCabecera($suplanimalId);
            if (empty($cabecera)) {
                throw new \RuntimeException('Registro de suplementacion no encontrado.');
            }

            $detalles = $this->obtenerDetalles($suplanimalId);
            if (empty($detalles)) {
                throw new \RuntimeException('No existen detalles para integrar.');
            }

            $grupos = $this->agruparDetalles($detalles);
            if (empty($grupos)) {
                throw new \RuntimeException('No hay grupos validos para integrar.');
            }

            $token = $this->finnegansClient->obtenerTokenVigente($usuarioId, $disp, $ip);
            $apiUrl = $this->finnegansClient->getEnvRequired(
                ['ERP_API_URL_SUPLANML', 'ERP_API_URL_SUPLANIMAL', 'erp_api_url_suplanml', 'erp_api_url_suplanimal'],
                'URL de API de suplementacion'
            );

            $grupoIndex = $this->obtenerGrupoIndexSincronizados($suplanimalId);
            foreach ($grupos as $grupo) {
                $requestDto = $this->armarRequest($cabecera, $grupo, $grupoIndex);
                $response = $this->finnegansClient->postJsonWithToken($apiUrl, $token, $requestDto->toArray());

                if ($this->finnegansClient->esTokenInvalido($response['decoded'], $response['httpCode'])) {
                    $token = $this->finnegansClient->refrescarToken($usuarioId, $disp, $ip);
                    $response = $this->finnegansClient->postJsonWithToken($apiUrl, $token, $requestDto->toArray());
                }

                $responseDto = SuplementacionResponseDTO::fromArray($response['decoded'] ?? []);

                $this->registrarLogSuplanimal(
                    $suplanimalId,
                    $usuarioId,
                    $disp,
                    $ip,
                    [
                        'request' => $requestDto->toArray(),
                        'response' => $responseDto->toArray(),
                        'httpCode' => $response['httpCode'],
                        'raw' => $response['raw'],
                    ],
                    'SNC'
                );

                if (!$responseDto->isSuccess()) {
                    $message = $responseDto->error ?? $responseDto->message ?? 'Error en la integracion con ERP.';
                    throw new \RuntimeException($message);
                }

                $this->marcarGrupoIntegrado(
                    $suplanimalId,
                    (int)$grupo['invcateganimalid'],
                    (int)$grupo['totalanimales'],
                    $responseDto
                );

                $logPayload['grupos'][] = [
                    'invcateganimalid' => (int)$grupo['invcateganimalid'],
                    'totalanimales' => (int)$grupo['totalanimales'],
                    'totalconsumido' => (float)$grupo['totalconsumido'],
                    'request' => $requestDto->toArray(),
                    'response' => $responseDto->toArray(),
                    'httpCode' => $response['httpCode'],
                ];

                $grupoIndex++;
            }
            $this->marcarCabeceraIntegrada($suplanimalId, $usuarioId, $disp, $ip);
            $logPayload['totalGrupos'] = count($logPayload['grupos']);
            $this->finalizarLogSincronizacion($logId, 'success', 'Sincronizacion OK', $logPayload);
        } catch (\Throwable $e) {
            $logPayload['totalGrupos'] = count($logPayload['grupos']);
            $this->finalizarLogSincronizacion($logId, 'error', $e->getMessage(), $logPayload);
            $this->registrarLogSuplanimal(
                $suplanimalId,
                $usuarioId,
                $disp,
                $ip,
                ['error' => $e->getMessage(), 'suplanimalId' => $suplanimalId],
                'SNC'
            );
            Logger::error('Integracion ERP suplanimal ' . $suplanimalId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    private function obtenerCabecera(int $id): array
    {
        $sql = 'SELECT s.*, e.empresaiderp, f.fundonombre, b.invbodegadsc, u.usuarionombre
                FROM suplanimal s
                INNER JOIN empresas e ON e.empresaid = s.empresaid
                INNER JOIN fundos f ON f.fundoid = s.fundoid
                INNER JOIN invbodegas b ON b.invbodegaid = s.invbodegaid
                LEFT JOIN usuarios u ON u.usuarioid = s.auditcreacionusuarioid
                WHERE s.suplanimalid = ?';
        $rows = $this->db->select($sql, [$id]);
        return $rows[0] ?? [];
    }

    private function obtenerDetalles(int $suplanimalId): array
    {
        $sql = 'SELECT d.*, ca.invcateganimaldsc, ca.erpinvcateganimalcod,
                       i.invitemdsc, i.erpinvitemcod, um.invunidmeddsc, um.erpunidmedcod
                FROM suplanimaldetalle d
                LEFT JOIN invcateganimal ca ON ca.invcateganimalid = d.invcateganimalid
                LEFT JOIN invitems i ON i.invitemid = d.invitemid
                LEFT JOIN invunidadesmedidas um ON um.invunidmedid = d.invunidmedid
                WHERE d.suplanimalid = ? and (erpdocumentocod IS NULL OR erpdocumentocod = "PEND" OR erpdocumentocod = "")
                ORDER BY d.suplanimallinea ASC';
        return $this->db->select($sql, [$suplanimalId]);
    }

    //Obtener grupoindex ya sincronizados y ha eso, ir sumando 1
    private function obtenerGrupoIndexSincronizados(int $suplanimalId): int
    {
        $sql = 'SELECT COUNT(DISTINCT invcateganimalid, totalanimales) AS totalgrupos
                FROM suplanimaldetalle
                WHERE suplanimalid = ? AND erpdocumentocod IS NOT NULL AND erpdocumentocod != "" AND erpdocumentocod != "PEND"';
        $rows = $this->db->select($sql, [$suplanimalId]);
        $totalGrupos = (int)($rows[0]['totalgrupos'] ?? 0);
        return $totalGrupos + 1;
    }

    private function agruparDetalles(array $detalles): array
    {
        $grupos = [];
        foreach ($detalles as $detalle) {
            $categoriaId = (int)($detalle['invcateganimalid'] ?? 0);
            $totalAnimales = (int)($detalle['totalanimales'] ?? 0);
            if ($categoriaId <= 0 || $totalAnimales <= 0) {
                continue;
            }
            $key = $categoriaId . '|' . $totalAnimales;
            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'invcateganimalid' => $categoriaId,
                    'sup_erpinvcateganimalcod' => (string)($detalle['sup_erpinvcateganimalcod'] ?? $detalle['erpinvcateganimalcod'] ?? ''),
                    'totalanimales' => $totalAnimales,
                    'totalconsumido' => 0.0,
                    'detalles' => [],
                ];
            }
            $grupos[$key]['detalles'][] = $detalle;
            $grupos[$key]['totalconsumido'] += (float)($detalle['totalconsumido'] ?? 0);
        }

        return array_values($grupos);
    }

    private function armarRequest(array $cabecera, array $grupo, int $grupoIndex): SuplementacionRequestDTO
    {
        $empresaId = (string)($cabecera['empresaiderp'] ?? '');
        $lote = (string)($cabecera['sup_erplotecod'] ?? '');
        $deposito = (string)($cabecera['sup_erpinvbodegacod'] ?? '');
        $observacion = (string)($cabecera['suplanimalobservacion'] ?? '');
        $categoriaErp = (string)($grupo['sup_erpinvcateganimalcod'] ?? '');

        if ($empresaId === '' || $lote === '' || $deposito === '' || $categoriaErp === '') {
            throw new \RuntimeException('Faltan datos ERP obligatorios para suplementacion.');
        }

        $fecha = $cabecera['suplanimalfecha'] ?? null;
        $fechaFmt = '';
        if ($fecha) {
            $fechaFmt = (new \DateTimeImmutable($fecha))->format('Y-m-d');
        }

        $descripcionParts = [
            $cabecera['fundonombre'] ?? '',
        ];
        if (!empty($cabecera['usuarionombre'])) {
            $descripcionParts[] = 'Registrado por: ' . $cabecera['usuarionombre'];
        }
        if ($observacion !== '') {
            $descripcionParts[] = 'Obs: ' . $observacion;
        }
        $descripcion = trim(implode(' - ', array_filter($descripcionParts)));

        $totalAnimales = (int)($grupo['totalanimales'] ?? 0);
        $cantidadCertificada = (float)($grupo['totalconsumido'] ?? 0);
        $items = [[
            'Lote' => $lote,
            'CodigoCategoriahacienda' => $categoriaErp,
            'Cab' => $totalAnimales,
            'Kilos' => $totalAnimales * 500,
            'OrganizacionID' => null,
            'Tropa' => null,
            'EventoHaciendaClasificacionID' => 'ESTRATÉGICA-55',
            'CantidadCertificada' => $cantidadCertificada,
            'EventoHaciendaID' => 'SUPLEMENTACION',
        ]];

        $movimientos = [];
        foreach ($grupo['detalles'] as $detalle) {
            $movimientos[] = [
                'ProductoID' => (string)($detalle['sup_erpinvitemcod'] ?? $detalle['erpinvitemcod'] ?? ''),
                'Dosis' => (float)($detalle['dosisporanimal'] ?? 0),
                'CantidadStock1' => (float)($detalle['totalconsumido'] ?? 0),
                'CantidadStock2' => 0.0,
                'DepositoIDOrigen' => $deposito,
                'OrganizacionIDStock' => null,
                'PartidaID' => null,
                'Tipo' => 0,
                'TransaccionID' => 0,
            ];
        }

        return new SuplementacionRequestDTO(
            $empresaId,
            $fechaFmt,
            $descripcion,
            $this->generarIdentificacionExterna((int)($cabecera['suplanimalid'] ?? 0), $grupoIndex),
            $fechaFmt,
            $items,
            $movimientos
        );
    }

    private function generarIdentificacionExterna(int $suplanimalId, int $grupoIndex): string
    {
        return sprintf('AppSupl-%06d-%02d', $suplanimalId, $grupoIndex);
    }

    private function marcarGrupoIntegrado(int $suplanimalId, int $categoriaId, int $totalAnimales, SuplementacionResponseDTO $response): void
    {
        $codigoRespuesta = $response->documento ?? $response->id ?? 'OK';
        $sql = 'UPDATE suplanimaldetalle
                SET erpdocumentocod = ?
                WHERE suplanimalid = ? AND invcateganimalid = ? AND totalanimales = ?';
        $this->db->execute($sql, [$codigoRespuesta, $suplanimalId, $categoriaId, $totalAnimales]);
    }

    private function marcarCabeceraIntegrada(int $suplanimalId, int $usuarioId, ?string $disp, ?string $ip): void
    {
        $sql = 'UPDATE suplanimal
                SET suplanimalstatus = ?, suplanml_erp_sync = NOW(),
                    auditedicionusuarioid = ?, auditediciondispositivo = ?, auditedicionip = ?
                WHERE suplanimalid = ?';
        $this->db->execute($sql, ['CN', $usuarioId, $disp ?? '', $ip ?? '', $suplanimalId]);
    }

    private function registrarLogSuplanimal(int $suplanimalId, int $usuarioId, ?string $disp, ?string $ip, array $payload, string $tipo = 'QRY'): void
    {
        $sql = 'INSERT INTO suplanimallog (suplanimalid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
        $params = [
            $suplanimalId,
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
