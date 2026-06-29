<?php

class ComprasCatalogosService
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

    public function listarCentrosCostoUsuarioFormSelect(int $usuarioId, ?int $activo = 1): array
    {
        $sql = 'SELECT uc.centrocostoid,
                       uc.usucendefault,
                       cc.centrocostocod,
                       cc.centrocostodsc,
                       cc.centrocostojefeusuarioid,
                       uj.usuarionombre AS centrocostojefeusuarionombre,
                       uj.usuariorut AS centrocostojefeusuariorut,
                       uj.usuarioactivo AS centrocostojefeusuarioactivo,
                       uj.usuariobloqueado AS centrocostojefeusuariobloqueado,
                       uj.usuariopermiteaprobreq AS centrocostojefeusuariopermiteaprobreq,
                       cc.centrocostojefetecnicoid,
                       ut.usuarionombre AS centrocostojefetecniconombre,
                       ut.usuariorut AS centrocostojefetecnicorut,
                       ut.usuarioactivo AS centrocostojefetecnicoactivo,
                       ut.usuariobloqueado AS centrocostojefetecnicobloqueado,
                       ut.usuariopermiteaprobreq AS centrocostojefetecnicopermiteaprobreq
                FROM usuarioscentroscosto uc
                INNER JOIN centroscosto cc ON cc.centrocostoid = uc.centrocostoid
                LEFT JOIN usuarios uj ON uj.usuarioid = cc.centrocostojefeusuarioid
                LEFT JOIN usuarios ut ON ut.usuarioid = cc.centrocostojefetecnicoid
                WHERE uc.usuarioid = ?
                  AND uc.usucenactivo = 1';
        $params = [$usuarioId];

        if ($activo === 0 || $activo === 1) {
            $sql .= ' AND cc.centrocostoactivo = ?';
            $params[] = $activo;
        }

        $sql .= ' ORDER BY uc.usucendefault DESC, cc.centrocostodsc ASC';

        return $this->db->select($sql, $params);
    }

    public function listarUsuariosAprobadoresReqFormGrid(?string $filtroBusqueda = null, ?array $excluirUsuarioIds = null): array
    {
        $sql = 'SELECT u.usuarioid, u.usuariorut, u.usuarionombre, u.usuarioemail
                FROM usuarios u
                WHERE u.usuarioactivo = 1
                  AND u.usuariobloqueado = 0
                  AND u.usuariopermiteaprobreq = 1';
        $params = [];

        $filtroBusqueda = $this->nullIfEmpty($filtroBusqueda);
        if ($filtroBusqueda !== null) {
            $sql .= ' AND CONCAT_WS(" ", u.usuariorut, u.usuarionombre, u.usuarioemail) LIKE ?';
            $params[] = '%' . $filtroBusqueda . '%';
        }

        $ids = array_values(array_filter(array_map('intval', $excluirUsuarioIds ?? []), static function (int $id): bool {
            return $id > 0;
        }));
        if (!empty($ids)) {
            $sql .= ' AND u.usuarioid NOT IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            foreach ($ids as $id) {
                $params[] = $id;
            }
        }

        $sql .= ' ORDER BY u.usuarionombre ASC';

        return $this->db->select($sql, $params);
    }

    public function listarItemsCompraReqFormGrid(int $reqcompratipo, ?string $filtroBusqueda = null): array
    {
        $sql = 'SELECT ii.invitemid,
                       ii.erpinvitemcod,
                       ii.invitemdsc,
                       ii.invitemstockeable,
                       ii.invitemcostoestandar,
                       ii.subfamiliaid,
                       sf.subfamiliacod,
                       sf.subfamiliadsc,
                       ii.invunidmedid,
                       um.invunidmeddsc
                FROM invitems ii
                LEFT JOIN subfamilias sf ON sf.subfamiliaid = ii.subfamiliaid
                LEFT JOIN invunidadesmedidas um ON um.invunidmedid = ii.invunidmedid
                WHERE ii.invitemactivo = 1
                  AND ii.invitemcompra = 1
                  AND ii.invitemcostoestandar > 0';
        $params = [];

        if ($reqcompratipo === 1) {
            $sql .= ' AND ii.invitemstockeable = 1';
        } elseif ($reqcompratipo === 2) {
            $sql .= ' AND ii.invitemstockeable = 0';
        }

        $filtroBusqueda = $this->nullIfEmpty($filtroBusqueda);
        if ($filtroBusqueda !== null) {
            $sql .= ' AND CONCAT_WS(" ", ii.erpinvitemcod, ii.invitemdsc, IFNULL(sf.subfamiliadsc, "")) LIKE ?';
            $params[] = '%' . $filtroBusqueda . '%';
        }

        $sql .= ' ORDER BY ii.invitemdsc ASC';

        return $this->db->select($sql, $params);
    }

    public function obtenerItemCompraReqPorId(int $invitemid): ?array
    {
        $rows = $this->db->select(
            'SELECT ii.invitemid,
                    ii.erpinvitemcod,
                    ii.invitemdsc,
                    ii.invitemstockeable,
                    ii.invitemcostoestandar,
                    ii.subfamiliaid,
                    sf.subfamiliacod,
                    sf.subfamiliadsc,
                    ii.invunidmedid,
                    um.invunidmeddsc
               FROM invitems ii
               LEFT JOIN subfamilias sf ON sf.subfamiliaid = ii.subfamiliaid
               LEFT JOIN invunidadesmedidas um ON um.invunidmedid = ii.invunidmedid
              WHERE ii.invitemid = ?
                AND ii.invitemactivo = 1
                AND ii.invitemcompra = 1
              LIMIT 1',
            [$invitemid]
        );

        return $rows[0] ?? null;
    }

    public function listarFuncionariosFormSelect(?string $filtroBusqueda = null, ?int $centrocostoid = null): array
    {
        $sql = 'SELECT f.funcionariorut, f.funcionarionombre, f.funcionarioemail, f.funcencos
                FROM funcionarios f
                WHERE f.funcionarioactivo = 1';
        $params = [];

        if ($centrocostoid !== null && $centrocostoid > 0) {
            $sql .= ' AND (f.funcencos = ? OR f.funcencos IS NULL)';
            $params[] = $centrocostoid;
        }

        $filtroBusqueda = $this->nullIfEmpty($filtroBusqueda);
        if ($filtroBusqueda !== null) {
            $sql .= ' AND CONCAT_WS(" ", f.funcionariorut, f.funcionarionombre, IFNULL(f.funcionarioemail, "")) LIKE ?';
            $params[] = '%' . $filtroBusqueda . '%';
        }

        $sql .= ' ORDER BY f.funcionarionombre ASC';

        return $this->db->select($sql, $params);
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
