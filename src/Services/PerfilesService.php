<?php

class PerfilesService
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

    public function listarPerfiles(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroPerfildesc'    => $filtros['filtroPerfildesc'] ?? null,
            'filtroPerfilesroot'  => $filtros['filtroPerfilesroot'] ?? null,
            'filtroPerfilesadmin' => $filtros['filtroPerfilesadmin'] ?? null,
            'filtroPerfilactivo'  => $filtros['filtroPerfilactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_perfiles_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    /**
     * Lista perfiles sin SP. Si $activoFilter es 1/0 filtra por activo, si es null/'' lista todos.
     */
    public function listarPerfilesFormSelect($activoFilter = null): array
    {
        $sql = "SELECT perfilid, perfildesc, perfilactivo FROM perfiles";
        // siempre se debe excluir perfilesroot = 1.
        $params = [];
        if ($activoFilter === '0' || $activoFilter === 0 || $activoFilter === '1' || $activoFilter === 1) {
            $sql .= " WHERE perfilactivo = ? and perfilesroot = 0";
            $params[] = (int)$activoFilter;
        }
        else{
            $sql .= " WHERE perfilesroot = 0";
        }
        $sql .= " ORDER BY perfildesc ASC";

        return $this->db->select($sql, $params);
    }

    public function crearPerfil(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['perfilid']);

        return $this->db->callSpMaint(
            'sp_perfiles_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarPerfil(int $id, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['perfilid'] = $id;

        return $this->db->callSpMaint(
            'sp_perfiles_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularPerfil(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['perfilid' => $id];

        return $this->db->callSpMaint(
            'sp_perfiles_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarPerfilPorId(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['perfilid' => $id,];
        return $this->db->callSpQuery(
            'sp_perfiles_consultar_por_id',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }
}
