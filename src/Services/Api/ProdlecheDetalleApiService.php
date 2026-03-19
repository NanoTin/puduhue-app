<?php

class ProdlecheDetalleApiService
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
            FROM prodlechedetalle d
            INNER JOIN prodleche p ON p.prodlecheid = d.prodlecheid
            INNER JOIN fundos f ON f.fundoid = p.fundoid
            INNER JOIN prodlechetipos t ON t.prodlechetipoid = d.prodlechetipoid
            {$whereSql}
        ";

        $totalRows = $this->db->select($countSql, $params);
        $total = (int)($totalRows[0]['total'] ?? 0);

        $dataSql = "
            SELECT
                p.prodlecheid AS prodleche_id,
                p.prodlechefecha AS prodleche_fecha,
                p.prodlechehorario AS prodleche_horario,
                p.prodlechestatus AS prodleche_status,
                p.empresaid AS empresa_id,
                p.fundoid AS fundo_id,
                f.fundonombre AS fundo_nombre,
                d.prodlechetipoid AS tipo_leche_id,
                t.prodlechetipodsc AS tipo_leche_nombre,
                t.prodlecheventa AS tipo_leche_venta,
                d.pldetlitros AS litros,
                d.pldetvacas AS vacas,
                d.pldetlitrosxvaca AS litros_por_vaca,
                d.prodlechecod AS codigo_operacion,
                d.erpdocumentocod AS erp_documento_cod,
                d.pldetfechareg AS fecha_registro,
                d.pldetfechaedt AS fecha_edicion
            FROM prodlechedetalle d
            INNER JOIN prodleche p ON p.prodlecheid = d.prodlecheid
            INNER JOIN fundos f ON f.fundoid = p.fundoid
            INNER JOIN prodlechetipos t ON t.prodlechetipoid = d.prodlechetipoid
            {$whereSql}
            ORDER BY p.prodlechefecha DESC, p.prodlechehorario DESC, f.fundonombre ASC, t.prodlecheorden ASC, d.prodlechetipoid ASC
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
            'param_tipos_leche_ids' => $this->normalizeIdArray($payload['param_tipos_leche_ids'] ?? []),
        ];
    }

    private function buildWhere(array $filters): array
    {
        $clauses = ['WHERE 1=1'];
        $params = [];

        if ($filters['param_fecha_desde'] !== null) {
            $clauses[] = 'AND DATE(p.prodlechefecha) >= :fecha_desde';
            $params[':fecha_desde'] = $filters['param_fecha_desde'];
        }
        if ($filters['param_fecha_hasta'] !== null) {
            $clauses[] = 'AND DATE(p.prodlechefecha) <= :fecha_hasta';
            $params[':fecha_hasta'] = $filters['param_fecha_hasta'];
        }

        if (!empty($filters['param_fundos_ids'])) {
            $placeholders = [];
            foreach ($filters['param_fundos_ids'] as $index => $id) {
                $name = ':fundo_' . $index;
                $placeholders[] = $name;
                $params[$name] = $id;
            }
            $clauses[] = 'AND p.fundoid IN (' . implode(', ', $placeholders) . ')';
        }

        if (!empty($filters['param_tipos_leche_ids'])) {
            $placeholders = [];
            foreach ($filters['param_tipos_leche_ids'] as $index => $id) {
                $name = ':tipo_' . $index;
                $placeholders[] = $name;
                $params[$name] = $id;
            }
            $clauses[] = 'AND d.prodlechetipoid IN (' . implode(', ', $placeholders) . ')';
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
