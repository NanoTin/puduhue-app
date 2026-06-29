<?php

class TemporadasService
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

    public function obtenerPorTipoYFecha(string $tipoCodigo, ?string $fecha = null, bool $soloActivas = true): ?array
    {
        $tipoCodigo = trim(strtoupper($tipoCodigo));
        if ($tipoCodigo === '') {
            throw new \InvalidArgumentException('El tipo de temporada es obligatorio.');
        }

        $fechaConsulta = $this->normalizarFecha($fecha);

        $sql = "SELECT temporadaid,
                       temporadatipocodigo,
                       temporadadescripcion,
                       temporadainicio,
                       temporadafin,
                       temporadaactivo
                FROM temporadas
                WHERE temporadatipocodigo = ?
                  AND ? BETWEEN temporadainicio AND temporadafin";
        $params = [$tipoCodigo, $fechaConsulta];

        if ($soloActivas) {
            $sql .= " AND temporadaactivo = 1";
        }

        $sql .= " ORDER BY temporadaactivo DESC, temporadainicio DESC
                  LIMIT 1";

        $rows = $this->db->select($sql, $params);
        return $rows[0] ?? null;
    }

    public function obtenerTemporadaComprasPorFecha(?string $fecha = null): ?array
    {
        return $this->obtenerPorTipoYFecha('PPTO_COMPRAS', $fecha, true);
    }

    private function normalizarFecha(?string $fecha): string
    {
        if ($fecha === null || trim($fecha) === '') {
            return (new \DateTimeImmutable('today'))->format('Y-m-d');
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException('La fecha debe tener formato YYYY-MM-DD.');
        }

        return $date->format('Y-m-d');
    }
}
