<?php

class CentroscostoService
{
    private \Database $db;

    public function __construct()
    {
        $dbPath = dirname(__DIR__) . '/Config/Database.php';
        if (file_exists($dbPath)) {
            require_once $dbPath;
        }

        $this->db = \Database::getInstance();
    }

    public function listarCentroscosto(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroCentrocostocod' => $this->nullIfEmpty($filtros['filtroCentrocostocod'] ?? null),
            'filtroCentrocostodsc' => $this->nullIfEmpty($filtros['filtroCentrocostodsc'] ?? null),
            'filtroCentrocostoactivo' => $this->normalizeInt($filtros['filtroCentrocostoactivo'] ?? null),
        ];

        return $this->db->callSpQuery(
            'sp_centroscosto_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarCentrocostoPorId(int $centrocostoid, int $usuarioId, ?string $disp, ?string $ip): ?array
    {
        $result = $this->listarCentroscosto(
            ['filtroCentrocostocod' => null, 'filtroCentrocostodsc' => null, 'filtroCentrocostoactivo' => null],
            $usuarioId,
            $disp,
            $ip
        );

        foreach (($result['rows'] ?? []) as $row) {
            if ((int)($row['centrocostoid'] ?? 0) === $centrocostoid) {
                return $row;
            }
        }

        return null;
    }

    public function editarCentrocosto(int $centrocostoid, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $payload = [
            'centrocostoid' => $centrocostoid,
            'centrocostojefeusuarioid' => $this->normalizeInt($data['centrocostojefeusuarioid'] ?? null),
            'centrocostojefetecnicoid' => $this->normalizeInt($data['centrocostojefetecnicoid'] ?? null),
        ];

        return $this->db->callSpMaint(
            'sp_centroscosto_editar',
            $payload,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarUsuariosAprobadoresReqFormSelect(): array
    {
        return $this->db->select(
            'SELECT usuarioid, usuarionombre, usuariorut
             FROM usuarios
             WHERE usuarioactivo = 1
               AND usuariobloqueado = 0
               AND usuariopermiteaprobreq = 1
             ORDER BY usuarionombre ASC'
        );
    }

    private function normalizeInt($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int)$value;
    }

    private function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
