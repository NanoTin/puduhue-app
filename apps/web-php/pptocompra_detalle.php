<?php
$isPartial = $partial ?? false;
$pptocompra = $pptocompra ?? [];
$mensual = $mensual ?? [];
$movimientos = $movimientos ?? [];
$detalleResumen = $detalleResumen ?? [];
$filtroTipo = $filtroTipo ?? '';
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$money = static function ($value, int $decimals = 0): string {
    if (!is_numeric($value)) {
        return '0';
    }
    return number_format((float)$value, $decimals, ',', '.');
};

$fmtDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    try {
        return (new DateTime((string)$value))->format('d-m-Y');
    } catch (Exception $e) {
        return substr((string)$value, 0, 10);
    }
};

$movAmount = static function (array $mov): float {
    $encurso = (float)($mov['pptocompramontoencurso'] ?? 0);
    $confirmado = (float)($mov['pptocompramontoconfirmado'] ?? 0);
    if ($encurso !== 0.0 || $confirmado !== 0.0) {
        return $encurso + $confirmado;
    }
    return (float)($mov['pptocompramonto'] ?? 0);
};

$estadoClass = static function ($estado): string {
    return match (strtoupper((string)$estado)) {
        'PENDIENTE' => 'bg-warning-subtle text-warning-emphasis',
        'RECHAZADA' => 'bg-danger-subtle text-danger-emphasis',
        'REVERSA' => 'bg-secondary-subtle text-secondary-emphasis',
        default => 'bg-success-subtle text-success-emphasis',
    };
};

$chartSvg = static function (array $series, string $fieldA, string $fieldB, string $labelA, string $labelB): string {
    if (empty($series)) {
        return '<div class="text-muted small py-5 text-center">Sin datos para graficar</div>';
    }

    $width = 640;
    $height = 220;
    $pad = 34;
    $max = 1.0;
    foreach ($series as $row) {
        $max = max($max, (float)($row[$fieldA] ?? 0), (float)($row[$fieldB] ?? 0));
    }
    $count = max(count($series), 1);
    $plotWidth = $width - ($pad * 2);
    $plotHeight = $height - ($pad * 2);

    $pointsFor = static function (string $field) use ($series, $count, $pad, $plotWidth, $plotHeight, $max): string {
        $points = [];
        foreach (array_values($series) as $idx => $row) {
            $x = $count === 1 ? $pad + ($plotWidth / 2) : $pad + (($plotWidth / ($count - 1)) * $idx);
            $y = $pad + $plotHeight - (((float)($row[$field] ?? 0) / $max) * $plotHeight);
            $points[] = round($x, 2) . ',' . round($y, 2);
        }
        return implode(' ', $points);
    };

    $labels = '';
    foreach (array_values($series) as $idx => $row) {
        if ($idx % max(1, (int)ceil($count / 6)) !== 0 && $idx !== $count - 1) {
            continue;
        }
        $x = $count === 1 ? $pad + ($plotWidth / 2) : $pad + (($plotWidth / ($count - 1)) * $idx);
        $labels .= '<text x="' . round($x, 2) . '" y="' . ($height - 8) . '" text-anchor="middle" class="ppto-chart-label">' . htmlspecialchars((string)($row['periodo'] ?? '')) . '</text>';
    }

    return '
        <div class="d-flex justify-content-end gap-3 small mb-2">
            <span><span class="ppto-legend ppto-legend-primary"></span>' . htmlspecialchars($labelA) . '</span>
            <span><span class="ppto-legend ppto-legend-success"></span>' . htmlspecialchars($labelB) . '</span>
        </div>
        <svg class="ppto-line-chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Grafico presupuesto compra">
            <line x1="' . $pad . '" y1="' . ($height - $pad) . '" x2="' . ($width - $pad) . '" y2="' . ($height - $pad) . '" class="ppto-chart-axis" />
            <line x1="' . $pad . '" y1="' . $pad . '" x2="' . $pad . '" y2="' . ($height - $pad) . '" class="ppto-chart-axis" />
            <polyline points="' . $pointsFor($fieldA) . '" class="ppto-chart-line ppto-chart-line-primary" />
            <polyline points="' . $pointsFor($fieldB) . '" class="ppto-chart-line ppto-chart-line-success" />
            ' . $labels . '
        </svg>';
};

$barChartSvg = static function (array $series, string $fieldA, string $fieldB, string $labelA, string $labelB): string {
    if (empty($series)) {
        return '<div class="text-muted small py-5 text-center">Sin datos para graficar</div>';
    }

    $width = 640;
    $height = 220;
    $pad = 34;
    $max = 1.0;
    foreach ($series as $row) {
        $max = max($max, (float)($row[$fieldA] ?? 0), (float)($row[$fieldB] ?? 0));
    }

    $count = max(count($series), 1);
    $plotWidth = $width - ($pad * 2);
    $plotHeight = $height - ($pad * 2);
    $slot = $plotWidth / $count;
    $barWidth = min(22, max(8, ($slot - 8) / 2));
    $bars = '';
    $labels = '';

    foreach (array_values($series) as $idx => $row) {
        $baseX = $pad + ($slot * $idx) + (($slot - ($barWidth * 2) - 3) / 2);
        $valueA = (float)($row[$fieldA] ?? 0);
        $valueB = (float)($row[$fieldB] ?? 0);
        $heightA = ($valueA / $max) * $plotHeight;
        $heightB = ($valueB / $max) * $plotHeight;
        $yA = $pad + $plotHeight - $heightA;
        $yB = $pad + $plotHeight - $heightB;

        $bars .= '<rect x="' . round($baseX, 2) . '" y="' . round($yA, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($heightA, 2) . '" rx="4" class="ppto-chart-bar ppto-chart-bar-primary" />';
        $bars .= '<rect x="' . round($baseX + $barWidth + 3, 2) . '" y="' . round($yB, 2) . '" width="' . round($barWidth, 2) . '" height="' . round($heightB, 2) . '" rx="4" class="ppto-chart-bar ppto-chart-bar-success" />';

        if ($idx % max(1, (int)ceil($count / 6)) === 0 || $idx === $count - 1) {
            $labelX = $pad + ($slot * $idx) + ($slot / 2);
            $labels .= '<text x="' . round($labelX, 2) . '" y="' . ($height - 8) . '" text-anchor="middle" class="ppto-chart-label">' . htmlspecialchars((string)($row['periodo'] ?? '')) . '</text>';
        }
    }

    return '
        <div class="d-flex justify-content-end gap-3 small mb-2">
            <span><span class="ppto-legend ppto-legend-primary"></span>' . htmlspecialchars($labelA) . '</span>
            <span><span class="ppto-legend ppto-legend-success"></span>' . htmlspecialchars($labelB) . '</span>
        </div>
        <svg class="ppto-line-chart" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Grafico presupuesto compra">
            <line x1="' . $pad . '" y1="' . ($height - $pad) . '" x2="' . ($width - $pad) . '" y2="' . ($height - $pad) . '" class="ppto-chart-axis" />
            <line x1="' . $pad . '" y1="' . $pad . '" x2="' . $pad . '" y2="' . ($height - $pad) . '" class="ppto-chart-axis" />
            ' . $bars . '
            ' . $labels . '
        </svg>';
};

$disponiblePct = (float)($detalleResumen['disponible_pct'] ?? 0);
$disponibleTone = 'danger';
$disponibleBorder = 'border-danger';
$disponibleLabelClass = 'text-danger-emphasis';
$disponibleLeyenda = '> 0% y < 30%';
$disponibleBadge = 'Saldo Bajo';
$disponibleIcon = 'bi-exclamation-triangle';
$disponibleTooltip = $disponibleLeyenda . ' del reproyectado | ' . $money($disponiblePct, 1) . '% del reproyectado';
if ($disponiblePct >= 50 && $disponiblePct <= 100) {
    $disponibleTone = 'success';
    $disponibleBorder = 'border-success';
    $disponibleLabelClass = 'text-success-emphasis';
    $disponibleLeyenda = '>= 50% y <= 100%';
    $disponibleBadge = 'Saldo Saludable';
    $disponibleIcon = 'bi-check-circle';
    $disponibleTooltip = $disponibleLeyenda . ' del reproyectado | ' . $money($disponiblePct, 1) . '% del reproyectado';
} elseif ($disponiblePct >= 30 && $disponiblePct < 50) {
    $disponibleTone = 'warning';
    $disponibleBorder = 'border-warning';
    $disponibleLabelClass = 'text-warning-emphasis';
    $disponibleLeyenda = '>= 30% y < 50%';
    $disponibleBadge = 'Saldo Medio';
    $disponibleIcon = 'bi-exclamation-triangle';
    $disponibleTooltip = $disponibleLeyenda . ' del reproyectado | ' . $money($disponiblePct, 1) . '% del reproyectado';
} elseif ($disponiblePct <= 0) {
    $disponibleLeyenda = '<= 0%';
    $disponibleBadge = 'Sin Disponible';
    $disponibleIcon = 'bi-x-circle';
    $disponibleTooltip = $disponibleLeyenda . ' del reproyectado | ' . $money($disponiblePct, 1) . '% del reproyectado';
} elseif ($disponiblePct > 100) {
    $disponibleTone = 'success';
    $disponibleBorder = 'border-success';
    $disponibleLabelClass = 'text-success-emphasis';
    $disponibleLeyenda = '> 100%';
    $disponibleBadge = 'Sobre Reproyectado';
    $disponibleIcon = 'bi-check-circle';
    $disponibleTooltip = $disponibleLeyenda . ' del reproyectado | ' . $money($disponiblePct, 1) . '% del reproyectado';
}
?>

<style>
    .ppto-detail-page {
        --ppto-primary: #0f172a;
        --ppto-muted: #64748b;
        --ppto-border: #e2e8f0;
        --ppto-surface: #ffffff;
    }
    .ppto-card {
        background: var(--ppto-surface);
        border: 1px solid var(--ppto-border);
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    }
    .ppto-card.border-success {
        border-color: var(--bs-success) !important;
        border-width: 2px;
        box-shadow: 0 14px 30px rgba(25, 135, 84, 0.14);
    }
    .ppto-card.border-warning {
        border-color: var(--bs-warning) !important;
        border-width: 2px;
        box-shadow: 0 14px 30px rgba(255, 193, 7, 0.18);
    }
    .ppto-card.border-danger {
        border-color: var(--bs-danger) !important;
        border-width: 2px;
        box-shadow: 0 14px 30px rgba(220, 53, 69, 0.14);
    }
    .ppto-available-card {
        position: relative;
        overflow: hidden;
    }
    .ppto-available-card::after {
        bottom: -18px;
        color: currentColor;
        content: "\F5D5";
        font-family: "bootstrap-icons";
        font-size: 6.5rem;
        opacity: 0.045;
        position: absolute;
        right: -8px;
    }
    .ppto-available-badge {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.86rem;
        font-weight: 800;
        gap: 0.45rem;
        padding: 0.42rem 0.78rem;
    }
    .ppto-available-badge-success {
        background: rgba(25, 135, 84, 0.14);
        color: #0f5132;
    }
    .ppto-available-badge-warning {
        background: rgba(255, 193, 7, 0.23);
        color: #664d03;
    }
    .ppto-available-badge-danger {
        background: rgba(220, 53, 69, 0.14);
        color: #842029;
    }
    .ppto-kpi-label {
        color: #334155;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .ppto-kpi-value {
        color: var(--ppto-primary);
        font-size: clamp(1.45rem, 2.2vw, 2rem);
        font-weight: 800;
        line-height: 1.1;
    }
    .ppto-kpi-note {
        color: var(--ppto-muted);
        font-size: 0.82rem;
    }
    .ppto-line-chart {
        display: block;
        width: 100%;
        min-height: 220px;
    }
    .ppto-chart-axis {
        stroke: #e2e8f0;
        stroke-width: 1;
    }
    .ppto-chart-line {
        fill: none;
        stroke-width: 4;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .ppto-chart-line-primary {
        stroke: #0f172a;
    }
    .ppto-chart-line-success {
        stroke: #22c55e;
    }
    .ppto-chart-bar {
        shape-rendering: geometricPrecision;
    }
    .ppto-chart-bar-primary {
        fill: #0f172a;
    }
    .ppto-chart-bar-success {
        fill: #22c55e;
    }
    .ppto-chart-label {
        fill: #64748b;
        font-size: 11px;
    }
    .ppto-legend {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        margin-right: 6px;
    }
    .ppto-legend-primary {
        background: #0f172a;
    }
    .ppto-legend-success {
        background: #22c55e;
    }
    .ppto-trace-table th {
        color: #334155;
        font-size: 0.72rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .ppto-trace-table td {
        vertical-align: middle;
    }
</style>

<div class="container-fluid px-4 py-3 ppto-detail-page">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-wrap">
        <div>
            <div class="small text-muted mb-1">
                Presupuesto / <?= htmlspecialchars((string)($pptocompra['temporadadescripcion'] ?? '')) ?>
            </div>
            <h3 class="mb-1">Presupuesto de Compras #<?= htmlspecialchars((string)($pptocompra['pptocompraid'] ?? '')) ?></h3>
            <div class="text-muted">
                <?= htmlspecialchars((string)($pptocompra['subfamiliadsc'] ?? '')) ?>
                <span class="mx-1">|</span>
                <?= htmlspecialchars((string)($pptocompra['centrocostodsc'] ?? '')) ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (!empty($pptocompra['pptocompraactivo'])): ?>
                <a href="?route=pptocompra/ajustar&pptocompraid=<?= urlencode((string)($pptocompra['pptocompraid'] ?? '')) ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-sliders2"></i> Nuevo ajuste
                </a>
                <a href="?route=pptocompra/traspasar&pptocompraid=<?= urlencode((string)($pptocompra['pptocompraid'] ?? '')) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left-right"></i> Traspasar
                </a>
            <?php endif; ?>
            <a href="?route=pptocompra/listar" class="btn btn-outline-secondary btn-sm">Volver</a>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl">
            <div class="ppto-card p-3 h-100">
                <div class="ppto-kpi-label mb-3">Presupuesto inicial</div>
                <div class="ppto-kpi-value">$<?= htmlspecialchars($money($detalleResumen['presupuestado'] ?? 0)) ?></div>
                <div class="ppto-kpi-note mt-3">Carga base confirmada</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl">
            <div class="ppto-card p-3 h-100">
                <div class="ppto-kpi-label mb-3">Ajustado</div>
                <div class="ppto-kpi-value">$<?= htmlspecialchars($money($detalleResumen['ajustado'] ?? 0)) ?></div>
                <div class="ppto-kpi-note mt-3">
                    +<?= htmlspecialchars($money($detalleResumen['ajuste_positivo'] ?? 0)) ?>
                    /
                    <?= htmlspecialchars($money($detalleResumen['ajuste_negativo'] ?? 0)) ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl">
            <div class="ppto-card p-3 h-100">
                <div class="ppto-kpi-label mb-3">Reproyectado</div>
                <div class="ppto-kpi-value">$<?= htmlspecialchars($money($detalleResumen['reproyectado'] ?? 0)) ?></div>
                <div class="ppto-kpi-note mt-3">Inicial + ajustes + traspasos</div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl">
            <div class="ppto-card p-3 h-100">
                <div class="ppto-kpi-label mb-3">Consumido</div>
                <div class="ppto-kpi-value">$<?= htmlspecialchars($money($detalleResumen['consumido'] ?? 0)) ?></div>
                <div class="ppto-kpi-note mt-3">
                    Pend. <?= htmlspecialchars($money($detalleResumen['consumo_pendiente'] ?? 0)) ?>
                    /
                    Conf. <?= htmlspecialchars($money($detalleResumen['consumo_confirmado'] ?? 0)) ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl">
            <div class="ppto-card p-3 h-100 ppto-available-card <?= htmlspecialchars($disponibleBorder) ?> <?= htmlspecialchars($disponibleLabelClass) ?>"
                 title="<?= htmlspecialchars($disponibleTooltip) ?>"
                 data-bs-toggle="tooltip"
                 data-bs-placement="top">
                <div class="ppto-kpi-label mb-3 <?= htmlspecialchars($disponibleLabelClass) ?>">Disponible</div>
                <div class="ppto-kpi-value">$<?= htmlspecialchars($money($detalleResumen['disponible'] ?? 0)) ?></div>
                <div class="ppto-kpi-note mt-3">
                    <div class="ppto-available-badge ppto-available-badge-<?= htmlspecialchars($disponibleTone) ?>">
                        <i class="bi <?= htmlspecialchars($disponibleIcon) ?>"></i>
                        <?= htmlspecialchars($disponibleBadge) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="ppto-card p-3 h-100">
                <h5 class="mb-1">Ejecucion mensual</h5>
                <div class="text-muted small mb-3">Presupuestado por mes vs consumo por mes</div>
                <?= $barChartSvg($detalleResumen['series'] ?? [], 'presupuestado', 'consumo', 'Presupuestado', 'Consumo') ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="ppto-card p-3 h-100">
                <h5 class="mb-1">Acumulado mensual</h5>
                <div class="text-muted small mb-3">Presupuesto acumulado vs consumo acumulado</div>
                <?= $chartSvg($detalleResumen['series'] ?? [], 'presupuestado_acum', 'consumo_acum', 'Presupuesto acum.', 'Consumo acum.') ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="ppto-card p-3 h-100">
                <h5 class="mb-3">Carga base mensual</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th class="text-end">Monto base</th>
                                <th>Observacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mensual)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Sin detalle mensual</td></tr>
                            <?php else: ?>
                                <?php foreach ($mensual as $line): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(($line['ppoanio'] ?? '') . '-' . str_pad((string)($line['ppomes'] ?? ''), 2, '0', STR_PAD_LEFT)) ?></td>
                                        <td class="text-end">$<?= htmlspecialchars($money($line['ppomontoppto'] ?? 0)) ?></td>
                                        <td><?= htmlspecialchars($line['ppoobservacion'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="ppto-card p-3 h-100">
                <h5 class="mb-1">Resumen items temporada</h5>
                <div class="text-muted small mb-3">Top 5 items con mayor consumo</div>
                <!-- Pendiente: alimentar este ranking desde detalle PreOC cuando el modelo PreOC quede definido. -->
                <div class="alert alert-light border mb-0">
                    Disponible cuando se implemente el detalle de PreOC asociado a item, cantidad, precio y monto.
                </div>
            </div>
        </div>
    </div>

    <div class="ppto-card p-3" id="log-trazabilidad">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
            <div>
                <h5 class="mb-1">Log de trazabilidad</h5>
                <div class="text-muted small">Transacciones y eventos asociados al presupuesto</div>
            </div>
            <form class="d-flex gap-2" action="?route=pptocompra/detalle#log-trazabilidad" method="GET">
                <input type="hidden" name="route" value="pptocompra/detalle">
                <input type="hidden" name="pptocompraid" value="<?= htmlspecialchars((string)($pptocompra['pptocompraid'] ?? '')) ?>">
                <select name="filtroTipo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="PPTO_CARGA" <?= $filtroTipo === 'PPTO_CARGA' ? 'selected' : '' ?>>Carga</option>
                    <option value="PPTO_AJUSTE_POS" <?= $filtroTipo === 'PPTO_AJUSTE_POS' ? 'selected' : '' ?>>Ajuste positivo</option>
                    <option value="PPTO_AJUSTE_NEG" <?= $filtroTipo === 'PPTO_AJUSTE_NEG' ? 'selected' : '' ?>>Ajuste negativo</option>
                    <option value="PPTO_TRASPASO_SALIDA" <?= $filtroTipo === 'PPTO_TRASPASO_SALIDA' ? 'selected' : '' ?>>Traspaso salida</option>
                    <option value="PPTO_TRASPASO_ENTRADA" <?= $filtroTipo === 'PPTO_TRASPASO_ENTRADA' ? 'selected' : '' ?>>Traspaso entrada</option>
                    <option value="POC_RESERVA" <?= $filtroTipo === 'POC_RESERVA' ? 'selected' : '' ?>>PreOC en curso</option>
                    <option value="POC_CONFIRMACION" <?= $filtroTipo === 'POC_CONFIRMACION' ? 'selected' : '' ?>>PreOC aprobada</option>
                    <option value="POC_REVERSA" <?= $filtroTipo === 'POC_REVERSA' ? 'selected' : '' ?>>Reversa</option>
                </select>
                <button class="btn btn-secondary btn-sm"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle ppto-trace-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th class="text-end">Monto</th>
                        <th class="text-end">Saldo</th>
                        <th>Estado</th>
                        <th>Motivo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                        <tr><td colspan="8" class="text-center text-muted">No se registran movimientos</td></tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $mov): ?>
                            <?php
                                $amount = $movAmount($mov);
                                $module = $mov['pptocompramoduloorigen'] ?? ($mov['pptocompregenciaorigen'] ?? 'PPTO_COMPRA');
                                $doc = (int)($mov['pptocompranrodocumentoorigen'] ?? 0);
                                $estado = $mov['pptocompraestado'] ?? 'CONFIRMADO';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($fmtDate($mov['pptocompratransaccionfecha'] ?? ($mov['auditcreacionfechahora'] ?? ''))) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($mov['pptocompratransacciontipodsc'] ?? ($mov['pptocompratransacciontipoid'] ?? '')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string)$module) ?>
                                    <?php if ($doc > 0): ?>
                                        <div class="small text-muted">Doc. <?= htmlspecialchars((string)$doc) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="<?= $amount >= 0 ? 'text-end text-success fw-semibold' : 'text-end text-danger fw-semibold' ?>">
                                    <?= $amount >= 0 ? '+' : '' ?>$<?= htmlspecialchars($money($amount)) ?>
                                </td>
                                <td class="text-end">$<?= htmlspecialchars($money($mov['saldo_disponible'] ?? 0)) ?></td>
                                <td><span class="badge <?= htmlspecialchars($estadoClass($estado)) ?>"><?= htmlspecialchars((string)$estado) ?></span></td>
                                <td><?= htmlspecialchars($mov['pptocompramotivo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($mov['auditcreacionusuarionombre'] ?? ('Usuario #' . ($mov['auditcreacionusuarioid'] ?? ''))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
