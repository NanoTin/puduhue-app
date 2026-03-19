<?php

class SuplanimalDetalleApiService
{
    private const DEFAULT_PAGE_SIZE = 100;
    private const MAX_PAGE_SIZE = 500;

    private \Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function query(array $payload): array
    {
        $filters = $this->validateAndNormalize($payload);
        [$whereSql, $params] = $this->buildWhere($filters);

        $countSql = "
            SELECT COUNT(*) AS total
            FROM suplanimaldetalle d
            INNER JOIN suplanimal s ON s.suplanimalid = d.suplanimalid
            INNER JOIN fundos f ON f.fundoid = s.fundoid
            INNER JOIN invcateganimal c ON c.invcateganimalid = d.invcateganimalid
            INNER JOIN invitems i ON i.invitemid = d.invitemid
            INNER JOIN invunidadesmedidas u ON u.invunidmedid = d.invunidmedid
            {$whereSql}
        ";

        $totalRows = $this->db->select($countSql, $params);
        $total = (int)($totalRows[0]['total'] ?? 0);

        $dataSql = "
            SELECT
                s.suplanimalid AS suplanimal_id,
                d.suplanimallinea AS linea,
                s.suplanimalfecha AS suplanimal_fecha,
                s.suplanimalstatus AS suplanimal_status,
                s.empresaid AS empresa_id,
                s.fundoid AS fundo_id,
                f.fundonombre AS fundo_nombre,
                d.invcateganimalid AS categoria_animal_id,
                c.invcateganimaldsc AS categoria_animal_nombre,
                d.invitemid AS item_id,
                i.invitemdsc AS item_nombre,
                d.invunidmedid AS unidad_medida_id,
                u.invunidmeddsc AS unidad_medida_nombre,
                d.totalconsumido AS total_consumido,
                d.totalanimales AS total_animales,
                d.dosisporanimal AS dosis_por_animal,
                d.erpdocumentocod AS erp_documento_cod,
                d.supdetfechareg AS fecha_registro,
                d.supdetfechaedt AS fecha_edicion
            FROM suplanimaldetalle d
            INNER JOIN suplanimal s ON s.suplanimalid = d.suplanimalid
            INNER JOIN fundos f ON f.fundoid = s.fundoid
            INNER JOIN invcateganimal c ON c.invcateganimalid = d.invcateganimalid
            INNER JOIN invitems i ON i.invitemid = d.invitemid
            INNER JOIN invunidadesmedidas u ON u.invunidmedid = d.invunidmedid
            {$whereSql}
            ORDER BY s.suplanimalfecha DESC, f.fundonombre ASC, s.suplanimalid DESC, d.suplanimallinea ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->getPdo()->prepare($dataSql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $filters['page_size'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($filters['page'] - 1) * $filters['page_size'], \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        return [
            'rows' => $rows,
            'page' => $filters['page'],
            'page_size' => $filters['page_size'],
            'total_registros' => $total,
        ];
    }

    private function validateAndNormalize(array $payload): array
    {
        $page = isset($payload['page']) ? (int)$payload['page'] : 1;
        $pageSize = isset($payload['page_size']) ? (int)$payload['page_size'] : self::DEFAULT_PAGE_SIZE;
        if ($page <= 0) {
            throw new ApiException('page debe ser mayor o igual a 1.', 400);
        }
        if ($pageSize <= 0 || $pageSize > self::MAX_PAGE_SIZE) {
            throw new ApiException('page_size excede el maximo permitido.', 400);
        }

        $fechaDesde = $this->normalizeDate($payload['param_fecha_desde'] ?? null, 'param_fecha_desde');
        $fechaHasta = $this->normalizeDate($payload['param_fecha_hasta'] ?? null, 'param_fecha_hasta');
        if ($fechaDesde !== null && $fechaHasta !== null && $fechaDesde > $fechaHasta) {
            throw new ApiException('param_fecha_desde no puede ser mayor que param_fecha_hasta.', 400);
        }

        return [
            'page' => $page,
            'page_size' => $pageSize,
            'param_fecha_desde' => $fechaDesde,
            'param_fecha_hasta' => $fechaHasta,
            'param_fundos_ids' => $this->normalizeIdArray($payload['param_fundos_ids'] ?? []),
        ];
    }

    private function buildWhere(array $filters): array
    {
        $clauses = ['WHERE 1=1'];
        $params = [];

        if ($filters['param_fecha_desde'] !== null) {
            $clauses[] = 'AND DATE(s.suplanimalfecha) >= :fecha_desde';
            $params[':fecha_desde'] = $filters['param_fecha_desde'];
        }
        if ($filters['param_fecha_hasta'] !== null) {
            $clauses[] = 'AND DATE(s.suplanimalfecha) <= :fecha_hasta';
            $params[':fecha_hasta'] = $filters['param_fecha_hasta'];
        }

        if (!empty($filters['param_fundos_ids'])) {
            $placeholders = [];
            foreach ($filters['param_fundos_ids'] as $index => $id) {
                $name = ':fundo_' . $index;
                $placeholders[] = $name;
                $params[$name] = $id;
            }
            $clauses[] = 'AND s.fundoid IN (' . implode(', ', $placeholders) . ')';
        }

        return [implode("\n            ", $clauses), $params];
    }

    private function normalizeDate($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', (string)$value);
        if (!$date || $date->format('Y-m-d') !== (string)$value) {
            throw new ApiException($field . ' debe tener formato YYYY-MM-DD.', 400);
        }
        return $date->format('Y-m-d');
    }

    private function normalizeIdArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (!is_array($value)) {
            throw new ApiException('Los filtros por IDs deben ser arreglos.', 400);
        }

        $normalized = [];
        foreach ($value as $item) {
            $id = (int)$item;
            if ($id <= 0) {
                throw new ApiException('Los arreglos de IDs solo aceptan enteros positivos.', 400);
            }
            $normalized[] = $id;
        }

        return array_values(array_unique($normalized));
    }
}
