<?php

class UsuariosempresasService
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

    public function listarUsuariosempresas(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroUsuarioid' => $filtros['filtroUsuarioid'] ?? null,
            'filtroEmpresaid' => $filtros['filtroEmpresaid'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_usuariosempresas_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarEmpresasPorUsuarioFormSelect(?string $activoFilter = null, ?string $usuarioId = null): array
    {
        $sql = "SELECT I.empresaid, E.razonsocial FROM usuariosempresas I INNER JOIN empresas E ON I.empresaid = E.empresaid";
        $params = [];
        $conditions = [];

        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $conditions[] = "E.empresaactivo = ?";
            $params[] = (int)$activoFilter;
        }

        if ($usuarioId !== null) {
            $conditions[] = "I.usuarioid = ?";
            $params[] = (int)$usuarioId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY E.razonsocial ASC";

        return $this->db->select($sql, $params);
    }   

    public function crearUsuarioempresa(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_usuariosempresas_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function eliminarUsuarioempresa(int $usuarioIdAssoc, int $empresaidAssoc, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'usuarioid' => $usuarioIdAssoc,
            'empresaid' => $empresaidAssoc,
        ];

        return $this->db->callSpMaint(
            'sp_usuariosempresas_eliminar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
