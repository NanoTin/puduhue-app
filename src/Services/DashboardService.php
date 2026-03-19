<?php

class DashboardService
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

    public function obtenerResumen(int $usuarioId, ?string $dispositivo, ?string $ip, ?int $fundoId = null): array
    {
        $temporada = $this->obtenerTemporadaActiva();
        if (!$temporada) {
            return [
                'cards' => $this->buildCards(0, 0.0, 0, 0.0),
                'charts' => [
                    'produccionMensual' => ['labels' => [], 'data' => []],
                    'vacasMensual' => ['labels' => [], 'data' => [], 'min' => null, 'max' => null],
                ],
                'temporada' => null,
            ];
        }

        $inicio = $this->formatDateBoundary($temporada['temporadainicio'] ?? null, 'start');
        $fin = $this->formatDateBoundary($temporada['temporadafin'] ?? null, 'end');

        $hoy = new \DateTimeImmutable('today');
        $anioActual = (int)$hoy->format('Y');
        $mesActual = (int)$hoy->format('n');

        $produccionRows = $this->listarProduccionMensual($inicio, $fin, $fundoId);
        $labels = [];
        $litrosMensual = [];
        $vacasMensual = [];
        $litrosTemporada = 0.0;
        $litrosMesActual = null;

        foreach ($produccionRows as $row) {
            $anio = (int)($row['anio'] ?? 0);
            $mes = (int)($row['mes'] ?? 0);
            $litros = is_numeric($row['litros_sum'] ?? null) ? (float)$row['litros_sum'] : 0.0;
            $vacas = is_numeric($row['vacas_avg'] ?? null) ? (float)$row['vacas_avg'] : null;

            if ($anio > 0 && $mes > 0) {
                $labels[] = $this->labelMes($mes, $anio);
                $litrosMensual[] = $litros;
                $vacasMensual[] = $vacas;
            }

            $litrosTemporada += $litros;
            if ($anio === $anioActual && $mes === $mesActual) {
                $litrosMesActual = $litros;
            }
        }

        if ($litrosMesActual === null) {
            $litrosMesActual = 0.0;
        }

        $vacasValores = array_filter($vacasMensual, static fn($value) => $value !== null);
        $vacasMin = !empty($vacasValores) ? min($vacasValores) : null;
        $vacasMax = !empty($vacasValores) ? max($vacasValores) : null;

        $presupuestoRows = $this->listarPresupuestoMensualTemporada($inicio, $fin, $fundoId);
        $presupuestoTemporada = 0.0;
        $presupuestoMesActual = null;
        foreach ($presupuestoRows as $row) {
            $anio = (int)($row['anio'] ?? 0);
            $mes = (int)($row['mes'] ?? 0);
            $litros = is_numeric($row['litros_sum'] ?? null) ? (float)$row['litros_sum'] : 0.0;
            $presupuestoTemporada += $litros;
            if ($anio === $anioActual && $mes === $mesActual) {
                $presupuestoMesActual = $litros;
            }
        }

        if ($presupuestoMesActual === null) {
            $presupuestoMesActual = 0.0;
        }

        $pctTemporada = $presupuestoTemporada > 0 ? ($litrosTemporada / $presupuestoTemporada) * 100 : 0.0;
        $pctMes = $presupuestoMesActual > 0 ? ($litrosMesActual / $presupuestoMesActual) * 100 : 0.0;

        return [
            'cards' => $this->buildCards($litrosTemporada, $pctTemporada, $litrosMesActual, $pctMes),
            'charts' => [
                'produccionMensual' => [
                    'labels' => $labels,
                    'data' => $litrosMensual,
                ],
                'vacasMensual' => [
                    'labels' => $labels,
                    'data' => $vacasMensual,
                    'min' => $vacasMin,
                    'max' => $vacasMax,
                ],
            ],
            'temporada' => $temporada,
        ];
    }

    private function obtenerTemporadaActiva(): ?array
    {
        $sql = "SELECT temporadainicio, temporadafin, temporadadescripcion
                FROM temporadas
                WHERE temporadatipocodigo = 'LECHE' AND temporadaactivo = 1
                LIMIT 1";
        $rows = $this->db->select($sql);
        return $rows[0] ?? null;
    }

    private function listarProduccionMensual(string $inicio, string $fin, ?int $fundoId): array
    {
        $params = [$inicio, $fin];

        if ($fundoId !== null && $fundoId > 0) {
            $sql = "SELECT YEAR(p.prodlechefecha) AS anio,
                           MONTH(p.prodlechefecha) AS mes,
                           SUM(p.prodlecheventatotlitros) AS litros_sum,
                           AVG(p.prodlecheventatotvacas) AS vacas_avg
                    FROM prodleche p
                    WHERE p.prodlechefecha BETWEEN ? AND ?
                      AND p.prodlechestatus <> 'ANL'
                      AND p.fundoid = ?
                    GROUP BY YEAR(p.prodlechefecha), MONTH(p.prodlechefecha)
                    ORDER BY YEAR(p.prodlechefecha), MONTH(p.prodlechefecha)";
            $params[] = $fundoId;
        } else {
            $sql = "SELECT v.anio,
                           v.mes,
                           SUM(v.litros_sum) AS litros_sum,
                           SUM(v.vacas_avg) AS vacas_avg
                    FROM (
                        SELECT YEAR(p.prodlechefecha) AS anio,
                               MONTH(p.prodlechefecha) AS mes,
                               p.fundoid AS fundoid,
                               SUM(p.prodlecheventatotlitros) AS litros_sum,
                               AVG(p.prodlecheventatotvacas) AS vacas_avg
                        FROM prodleche p
                        WHERE p.prodlechefecha BETWEEN ? AND ?
                          AND p.prodlechestatus <> 'ANL'
                        GROUP BY YEAR(p.prodlechefecha), MONTH(p.prodlechefecha), p.fundoid
                    ) v
                    GROUP BY v.anio, v.mes
                    ORDER BY v.anio, v.mes";
        }

        return $this->db->select($sql, $params);
    }

    private function listarPresupuestoMensualTemporada(string $inicio, string $fin, ?int $fundoId): array
    {
        $sql = "SELECT YEAR(pptolecfecha) AS anio,
                       MONTH(pptolecfecha) AS mes,
                       SUM(pptoleclitros) AS litros_sum
                FROM pptolechemensual
                WHERE pptolecfecha BETWEEN ? AND ?";
        $params = [$inicio, $fin];

        if ($fundoId !== null && $fundoId > 0) {
            $sql .= ' AND fundoid = ?';
            $params[] = $fundoId;
        }

        $sql .= ' GROUP BY YEAR(pptolecfecha), MONTH(pptolecfecha)
                  ORDER BY YEAR(pptolecfecha), MONTH(pptolecfecha)';

        return $this->db->select($sql, $params);
    }

    private function buildCards(float $litrosTemporada, float $pctTemporada, float $litrosMes, float $pctMes): array
    {
        return [
            [
                'title' => 'Temporada: Litros acumulados',
                'value' => $litrosTemporada,
                'decimals' => 0,
                'suffix' => '',
                'icon' => 'droplet-half',
                'variant' => 'primary',
            ],
            [
                'title' => 'Temporada: % Cumplimiento vs Ppto',
                'value' => $pctTemporada,
                'decimals' => 1,
                'suffix' => '%',
                'icon' => 'speedometer2',
                'variant' => 'success',
            ],
            [
                'title' => 'Mes: Litros acumulados',
                'value' => $litrosMes,
                'decimals' => 0,
                'suffix' => '',
                'icon' => 'droplet-half',
                'variant' => 'info',
            ],
            [
                'title' => 'Mes: % Cumplimiento vs Ppto',
                'value' => $pctMes,
                'decimals' => 1,
                'suffix' => '%',
                'icon' => 'percent',
                'variant' => 'warning',
            ],
        ];
    }

    private function labelMes(int $mes, int $anio): string
    {
        $map = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];

        return ($map[$mes] ?? (string)$mes) . ' ' . $anio;
    }

    private function formatDateBoundary(?string $date, string $mode): string
    {
        if (empty($date)) {
            return (new \DateTimeImmutable())->format('Y-m-d 00:00:00');
        }

        try {
            $dt = new \DateTimeImmutable($date);
        } catch (\Throwable $e) {
            return (new \DateTimeImmutable())->format('Y-m-d 00:00:00');
        }

        return $mode === 'end'
            ? $dt->format('Y-m-d 23:59:59')
            : $dt->format('Y-m-d 00:00:00');
    }
}
