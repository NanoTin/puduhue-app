<?php

class ApiRequestLogMonitorService
{
    private \Database $db;
    private ?bool $hasTokenPermisosColumn = null;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/Config/Database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $this->db = \Database::getInstance();
    }

    public function getConsultas(): array
    {
        return [
            'errores_ip' => [
                'titulo' => 'Errores por IP',
                'icono' => 'bi-shield-exclamation',
                'columnas' => [
                    'iporigen' => 'IP origen',
                    'responsecode' => 'HTTP',
                    'total' => 'Total',
                    'primera_vez' => 'Primera vez',
                    'ultima_vez' => 'Ultima vez',
                ],
            ],
            'tokens_ip' => [
                'titulo' => 'Uso de tokens por IP',
                'icono' => 'bi-key',
                'columnas' => [
                    'usuarioapitokenid' => 'Token ID',
                    'tokennombre' => 'Token',
                    'tokenprefijo' => 'Prefijo',
                    'tokenipultuso' => 'IP ultimo uso',
                    'iporigen' => 'IP log',
                    'total' => 'Total',
                    'primera_vez' => 'Primera vez',
                    'ultima_vez' => 'Ultima vez',
                ],
            ],
            'endpoints_ruido' => [
                'titulo' => 'Endpoints desconocidos',
                'icono' => 'bi-bug',
                'columnas' => [
                    'endpoint' => 'Endpoint',
                    'metodohttp' => 'Metodo',
                    'responsecode' => 'HTTP',
                    'total' => 'Total',
                    'ultima_vez' => 'Ultima vez',
                ],
            ],
            'uso_token_recurso' => [
                'titulo' => 'Uso por token y recurso',
                'icono' => 'bi-activity',
                'columnas' => [
                    'usuarioapitokenid' => 'Token ID',
                    'tokennombre' => 'Token',
                    'tokenprefijo' => 'Prefijo',
                    'tokenpermisos' => 'Permisos',
                    'recurso' => 'Recurso',
                    'responsecode' => 'HTTP',
                    'total' => 'Total',
                    'ultima_vez' => 'Ultima vez',
                ],
            ],
        ];
    }

    public function consultaPorDefecto(): string
    {
        return 'errores_ip';
    }

    public function ejecutarConsulta(string $codigo): array
    {
        $consultas = $this->getConsultas();
        if (!isset($consultas[$codigo])) {
            $codigo = $this->consultaPorDefecto();
        }

        return [
            'codigo' => $codigo,
            'definicion' => $consultas[$codigo],
            'rows' => $this->db->select($this->sqlPorConsulta($codigo)),
        ];
    }

    private function sqlPorConsulta(string $codigo): string
    {
        return match ($codigo) {
            'tokens_ip' => "
                SELECT
                  l.usuarioapitokenid,
                  t.tokennombre,
                  t.tokenprefijo,
                  t.tokenipultuso,
                  l.iporigen,
                  COUNT(*) AS total,
                  MIN(l.fechahora) AS primera_vez,
                  MAX(l.fechahora) AS ultima_vez
                FROM apirequestlog l
                INNER JOIN usuariosapitokens t ON t.usuarioapitokenid = l.usuarioapitokenid
                WHERE l.fechahora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND l.usuarioapitokenid IS NOT NULL
                GROUP BY l.usuarioapitokenid, t.tokennombre, t.tokenprefijo, t.tokenipultuso, l.iporigen
                ORDER BY l.usuarioapitokenid, ultima_vez DESC
                LIMIT 200
            ",
            'endpoints_ruido' => "
                SELECT
                  endpoint,
                  metodohttp,
                  responsecode,
                  COUNT(*) AS total,
                  MAX(fechahora) AS ultima_vez
                FROM apirequestlog
                WHERE fechahora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND (recurso IS NULL OR recurso = '')
                GROUP BY endpoint, metodohttp, responsecode
                ORDER BY total DESC, ultima_vez DESC
                LIMIT 50
            ",
            'uso_token_recurso' => $this->sqlUsoTokenRecurso(),
            default => "
                SELECT
                  iporigen,
                  responsecode,
                  COUNT(*) AS total,
                  MIN(fechahora) AS primera_vez,
                  MAX(fechahora) AS ultima_vez
                FROM apirequestlog
                WHERE fechahora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND responsecode IN (401, 403, 404, 405, 500)
                GROUP BY iporigen, responsecode
                HAVING COUNT(*) >= 10
                ORDER BY total DESC, ultima_vez DESC
                LIMIT 200
            ",
        };
    }

    private function sqlUsoTokenRecurso(): string
    {
        $tokenPermisosSelect = $this->hasTokenPermisosColumn()
            ? 't.tokenpermisos'
            : "NULL AS tokenpermisos";
        $tokenPermisosGroup = $this->hasTokenPermisosColumn()
            ? ', t.tokenpermisos'
            : '';

        return "
                SELECT
                  l.usuarioapitokenid,
                  t.tokennombre,
                  t.tokenprefijo,
                  {$tokenPermisosSelect},
                  l.recurso,
                  l.responsecode,
                  COUNT(*) AS total,
                  MAX(l.fechahora) AS ultima_vez
                FROM apirequestlog l
                LEFT JOIN usuariosapitokens t ON t.usuarioapitokenid = l.usuarioapitokenid
                WHERE l.fechahora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY l.usuarioapitokenid, t.tokennombre, t.tokenprefijo{$tokenPermisosGroup}, l.recurso, l.responsecode
                ORDER BY ultima_vez DESC, total DESC
                LIMIT 200
            ";
    }

    private function hasTokenPermisosColumn(): bool
    {
        if ($this->hasTokenPermisosColumn !== null) {
            return $this->hasTokenPermisosColumn;
        }

        try {
            $rows = $this->db->select("SHOW COLUMNS FROM usuariosapitokens LIKE 'tokenpermisos'");
            $this->hasTokenPermisosColumn = !empty($rows);
        } catch (\Throwable $e) {
            $this->hasTokenPermisosColumn = false;
        }

        return $this->hasTokenPermisosColumn;
    }
}
