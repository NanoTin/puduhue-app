<?php

class PerfilesmenusService
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

    public function listarPerfilesmenus(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroPerfilid'         => $filtros['filtroPerfilid'] ?? null,
            'filtroMenuid'           => $filtros['filtroMenuid'] ?? null,
            'filtroPerfilmenuactivo' => $filtros['filtroPerfilmenuactivo'] ?? null,
        ];
        //error_log("Llamando a listarPerfilesmenus con filtros: " . json_encode($dataJson));
        return $this->db->callSpQuery(
            'sp_perfilesmenus_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    // Se utiliza tanto para crea el menu lateral como para el grid de asignación de menús a perfiles (Maestro Perfiles Menús)
    public function listarMenusPerfilFormGrid(int $id, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['filtroPerfilid' => $id,];
        $result = $this->db->callSpQuery(
            'sp_perfilesmenus_consultar_por_perfilid',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );

        // Garantiza que el consumidor reciba todos los campos necesarios para armar el árbol del menú.
        $rows = $result['rows'] ?? [];
        foreach ($rows as &$row) {
            $row['menuid']           = isset($row['menuid']) ? (int)$row['menuid'] : 0;
            $row['menupadre']        = isset($row['menupadre']) ? (int)$row['menupadre'] : null;
            $row['menudesc']         = $row['menudesc'] ?? '';
            $row['menuform']         = $row['menuform'] ?? '';
            $row['menunivel']        = isset($row['menunivel']) ? (int)$row['menunivel'] : 0;
            $row['menunvlord']       = isset($row['menunvlord']) ? (int)$row['menunvlord'] : 0;
            $row['menuicono']        = $row['menuicono'] ?? '';
            $row['menuactivo']       = isset($row['menuactivo']) ? (int)$row['menuactivo'] : 0;
            $row['perfilmenuactivo'] = isset($row['perfilmenuactivo']) ? (int)$row['perfilmenuactivo'] : 1;

            if ($row['menupadre'] === 0) {
                $row['menupadre'] = null;
            }
        }
        unset($row);

        $result['rows'] = $rows;
        return $result;
    }

    public function crearPerfilesmenu(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        return $this->db->callSpMaint(
            'sp_perfilesmenus_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarPerfilesmenu(int $perfilId, int $menuId, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = $data;
        $dataJson['perfilid'] = $perfilId;
        $dataJson['menuid'] = $menuId;

        return $this->db->callSpMaint(
            'sp_perfilesmenus_editar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularPerfilesmenu(int $perfilId, int $menuId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'perfilid' => $perfilId,
            'menuid'   => $menuId,
        ];

        return $this->db->callSpMaint(
            'sp_perfilesmenus_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

}
