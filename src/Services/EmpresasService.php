<?php

/**
 * EmpresasService
 *
 * Coordinador entre controllers (Web/API) y la capa de BD (SP vía Database).
 * NO contiene lógica de negocio — solo valida datos mínimos y arma p_in_json.
 */
class EmpresasService
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

    /**
     * Listar empresas (resumen)
     */
    public function listarEmpresas(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        // Normalizar filtros hacia los nombres esperados por el SP
        $dataJson = [
            'filtroRazonsocial'     => $filtros['filtroRazonsocial'] ?? null,
            'filtroEmpresarut'      => $filtros['filtroEmpresarut'] ?? null,
            'filtroEmpresaactivo'   => $filtros['filtroEmpresaactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_empresas_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarEmpresasFormSelect(?string $activoFilter = null): array
    {
        $sql = "SELECT E.empresaid, E.razonsocial FROM empresas E";
        $params = [];
        $conditions = [];

        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $conditions[] = "E.empresaactivo = ?";
            $params[] = (int)$activoFilter;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY E.razonsocial ASC";

        return $this->db->select($sql, $params);
    }

    public function obtenerNombreEmpresa(int $empresaId): ?string
    {
        if ($empresaId <= 0) {
            return null;
        }

        $sql = "SELECT razonsocial FROM empresas WHERE empresaid = ? LIMIT 1";
        $rows = $this->db->select($sql, [$empresaId]);
        return $rows[0]['razonsocial'] ?? null;
    }

    /**
     * Crear una empresa
     */
    public function crearEmpresa(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        // remove PK (PK se autogenera)
        unset($data['empresaid']);

        return $this->db->callSpMaint(
            'sp_empresas_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    /**
     * Editar empresa (requiere PK y solo columnas permitidas)
     */
    public function editarEmpresa(int $empresaId, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        // asegurar inclusión de PK para el SP
        $data['empresaid'] = $empresaId;

        return $this->db->callSpMaint(
            'sp_empresas_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    /**
     * Anular empresa (SP solo recibe PK)
     */
    public function anularEmpresa(int $empresaId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'empresaid' => $empresaId
        ];

        return $this->db->callSpMaint(
            'sp_empresas_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    /**
     * Consultar empresa por ID (utilizando el SP de listado)
     */
    public function consultarEmpresaPorId(int $empresaId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_empresas_listar',
            [], // sin filtros, se filtra en PHP
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($empresaId) {
            return (int)($row['empresaid'] ?? 0) === $empresaId;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
