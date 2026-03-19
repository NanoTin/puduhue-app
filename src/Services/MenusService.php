<?php

/**
 * MenusService
 */
class MenusService
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

    public function listarMenus(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = [
            'filtroMenupadre'  => $filtros['filtroMenupadre'] ?? null,
            'filtroMenudesc'   => $filtros['filtroMenudesc'] ?? null,
            'filtroMenuactivo' => $filtros['filtroMenuactivo'] ?? null,
        ];

        return $this->db->callSpQuery(
            'sp_menus_listar',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function listarMenusPadreNivel(int $nivel): array
    {
        if ($nivel === 0) {
            // Nivel 0: solo opción "Sin Padre"
            return [
                ['menuid' => null, 'menudesc' => '-- Sin Padre --', 'menunivel' => 0]
            ];
        }else{        
            $sql = "SELECT menuid, menudesc, menunivel FROM menus WHERE menunivel = ? ORDER BY menudesc ASC";
            return $this->db->select($sql, [$nivel]);
        }
    }

    /*No utilizado aún*/
    public function listarMenusFormSelect(?int $menupadreFilter = null): array
    {
        $sql = "SELECT menuid, menudesc FROM menus";
        $params = [];
        if ($menupadreFilter !== null) {
            $sql .= " WHERE menupadre = ?";
            $params[] = $menupadreFilter;
        }
        $sql .= " ORDER BY menudesc ASC";
        return $this->db->select($sql, $params);
    }

    public function crearMenu(array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        unset($data['menuid']);

        return $this->db->callSpMaint(
            'sp_menus_insertar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function editarMenu(int $menuId, array $data, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $data['menuid'] = $menuId;

        return $this->db->callSpMaint(
            'sp_menus_editar',
            $data,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function anularMenu(int $menuId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $dataJson = ['menuid' => $menuId];

        return $this->db->callSpMaint(
            'sp_menus_anular',
            $dataJson,
            $usuarioId,
            $disp,
            $ip
        );
    }

    public function consultarMenuPorId(int $menuId, int $usuarioId, ?string $disp, ?string $ip): array
    {
        $result = $this->db->callSpQuery(
            'sp_menus_listar',
            [],
            $usuarioId,
            $disp,
            $ip
        );

        $rows = $result['rows'] ?? [];
        $filtered = array_values(array_filter($rows, static function ($row) use ($menuId) {
            return (int)($row['menuid'] ?? 0) === $menuId;
        }));

        return [
            'rows' => $filtered,
            'meta' => $result['meta'] ?? null,
        ];
    }
}
