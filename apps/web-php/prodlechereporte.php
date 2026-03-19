<?php
// Reporte de produccion de leche por fundo (diario mensual)
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$prodlecheReporte = $prodlecheReporte ?? [];
$fundosOptions = $fundosOptions ?? [];
$presupuestoRows = $presupuestoRows ?? [];
$proyeccionLecheRows = $proyeccionLecheRows ?? [];
$anioActual = $anioActual ?? (int)date('Y');
$mesActual = $mesActual ?? (int)date('n');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mesActual, $anioActual);
$now = new DateTime('now');
$currentYear = (int)$now->format('Y');
$currentMonth = (int)$now->format('n');
$currentDay = (int)$now->format('j');
if ($anioActual === $currentYear && $mesActual === $currentMonth) {
    $daysInMonth = min($currentDay, $daysInMonth);
}
$filtroFundoId = isset($_GET['filtroFundoid']) ? (string)$_GET['filtroFundoid'] : '';
$filtroAnio = isset($_GET['filtroAnio']) ? (string)$_GET['filtroAnio'] : (string)$anioActual;
$filtroMes = isset($_GET['filtroMes']) ? (string)$_GET['filtroMes'] : (string)$mesActual;

$months = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];

$formatDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    try {
        return (new DateTime($value))->format('d-m-Y');
    } catch (Exception $e) {
        return (string)$value;
    }
};

$formatIntCl = static function ($value): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, 0, ',', '.');
};

$formatFloatCl = static function ($value, int $decimals = 2): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, $decimals, ',', '.');
};

$formatPercent = static function ($value, int $decimals = 0): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, $decimals, ',', '.') . '%';
};

$fundosMap = [];
$fundosOrder = [];
foreach ($fundosOptions as $fundoOpt) {
    $id = (int)($fundoOpt['fundoid'] ?? 0);
    if ($id > 0) {
        $fundosMap[$id] = $fundoOpt['fundonombre'] ?? ('Fundo ' . $id);
    }
}

foreach ($prodlecheReporte as $row) {
    $id = (int)($row['fundoid'] ?? 0);
    if ($id > 0 && !isset($fundosMap[$id])) {
        $fundosMap[$id] = $row['fundonombre'] ?? ('Fundo ' . $id);
    }
    if ($id > 0 && !isset($fundosOrder[$id]) && isset($row['reporteorden']) && is_numeric($row['reporteorden'])) {
        $fundosOrder[$id] = (int)$row['reporteorden'];
    }
}

$presupuestoMap = [];
foreach ($presupuestoRows as $row) {
    $id = (int)($row['fundoid'] ?? 0);
    if ($id > 0) {
        $presupuestoMap[$id] = $row;
        if (!isset($fundosMap[$id])) {
            $fundosMap[$id] = $row['fundonombre'] ?? ('Fundo ' . $id);
        }
    }
}

$fundosMap = array_filter($fundosMap, static fn($value) => $value !== null && $value !== '');
if ($filtroFundoId !== '') {
    $filterId = (int)$filtroFundoId;
    $fundosMap = array_intersect_key($fundosMap, [$filterId => true]);
}

if (!empty($fundosMap)) {
    uksort($fundosMap, static function ($idA, $idB) use ($fundosMap, $fundosOrder) {
        $orderA = $fundosOrder[$idA] ?? PHP_INT_MAX;
        $orderB = $fundosOrder[$idB] ?? PHP_INT_MAX;
        if ($orderA !== $orderB) {
            return $orderA <=> $orderB;
        }
        return strnatcasecmp((string)$fundosMap[$idA], (string)$fundosMap[$idB]);
    });
}

$dailyData = [];
foreach ($prodlecheReporte as $row) {
    $dateKey = $row['prodlechefecha'] ?? null;
    if ($dateKey === null || $dateKey === '') {
        $dia = (int)($row['dia'] ?? 0);
        if ($dia > 0) {
            $dateKey = sprintf('%04d-%02d-%02d', $anioActual, $mesActual, $dia);
        } else {
            continue;
        }
    }

    $fundId = (int)($row['fundoid'] ?? 0);
    if ($fundId <= 0) {
        continue;
    }

    $litros = $row['prodlecheventatotlitros']
        ?? $row['prodlecheventatotlitros']
        ?? 0;
    $vacas = $row['prodlecheventatotvacas']
        ?? $row['prodlecheventatotvacas']
        ?? 0;
    $ltsxvc = $row['prodlecheventalitrosxvaca']
        ?? $row['prodlecheventalitrosxvaca']
        ?? 0;

    $dailyData[$dateKey][$fundId] = [
        'litros' => is_numeric($litros) ? (float)$litros : null,
        'vacas' => is_numeric($vacas) ? (float)$vacas : null,
        'ltsxvc' => is_numeric($ltsxvc) ? (float)$ltsxvc : null,
    ];
}

$totals = [];
$counts = [];
foreach ($dailyData as $fundsData) {
    foreach ($fundsData as $fundId => $metrics) {
        if ($metrics['litros'] !== null) {
            $totals[$fundId]['litros'] = ($totals[$fundId]['litros'] ?? 0) + (float)$metrics['litros'];
            $counts[$fundId]['litros'] = ($counts[$fundId]['litros'] ?? 0) + 1;
        } else {
            $totals[$fundId]['litros'] = $totals[$fundId]['litros'] ?? 0;
        }
        if ($metrics['vacas'] !== null) {
            $totals[$fundId]['vacas'] = ($totals[$fundId]['vacas'] ?? 0) + (float)$metrics['vacas'];
            $counts[$fundId]['vacas'] = ($counts[$fundId]['vacas'] ?? 0) + 1;
        }
        if ($metrics['ltsxvc'] !== null) {
            $totals[$fundId]['ltsxvc'] = ($totals[$fundId]['ltsxvc'] ?? 0) + (float)$metrics['ltsxvc'];
            $counts[$fundId]['ltsxvc'] = ($counts[$fundId]['ltsxvc'] ?? 0) + 1;
        }
    }
}

$proyeccionMap = [];
$projectionTotals = ['litros' => 0, 'vacas' => 0, 'ltsxvc' => 0];
$projectionCounts = ['vacas' => 0, 'ltsxvc' => 0];
$projectionLitrosHas = false;
foreach ($proyeccionLecheRows as $row) {
    $dateKey = $row['proylechefecha'] ?? null;
    if ($dateKey === null || $dateKey === '') {
        continue;
    }
    $litros = $row['proylecheventatotlitros']
        ?? $row['proylecheventatotlitros']
        ?? 0;
    $vacas = $row['proylecheventatotvacas']
        ?? $row['proylecheventatotvacas']
        ?? 0;
    $ltsxvc = $row['proylecheventatotltsxvaca']
        ?? $row['proylecheventatotltsxvaca']
        ?? 0;

    $metrics = [
        'litros' => is_numeric($litros) ? (float)$litros : null,
        'vacas' => is_numeric($vacas) ? (float)$vacas : null,
        'ltsxvc' => is_numeric($ltsxvc) ? (float)$ltsxvc : null,
    ];
    $proyeccionMap[$dateKey] = $metrics;

    if ($metrics['litros'] !== null) {
        $projectionTotals['litros'] += (float)$metrics['litros'];
        $projectionLitrosHas = true;
    }
    if ($metrics['vacas'] !== null) {
        $projectionTotals['vacas'] += (float)$metrics['vacas'];
        $projectionCounts['vacas']++;
    }
    if ($metrics['ltsxvc'] !== null) {
        $projectionTotals['ltsxvc'] += (float)$metrics['ltsxvc'];
        $projectionCounts['ltsxvc']++;
    }
}

$consolidatedTotals = ['litros' => 0, 'vacas' => 0, 'ltsxvc' => 0];
$consolidatedCounts = ['litros' => 0, 'vacas' => 0, 'ltsxvc' => 0];
$consolidatedAvgVacasSum = 0.0;
$consolidatedAvgVacasHas = false;
$consolidatedAvgLtsxvcSum = 0.0;
$consolidatedAvgLtsxvcCount = 0;
foreach ($totals as $fundId => $fundTotals) {
    if (!isset($fundosMap[$fundId])) {
        continue;
    }
    $consolidatedTotals['litros'] += (float)($fundTotals['litros'] ?? 0);
    if (!empty($counts[$fundId]['litros'])) {
        $consolidatedCounts['litros'] += $counts[$fundId]['litros'];
    }
    if (!empty($counts[$fundId]['vacas'])) {
        $consolidatedTotals['vacas'] += (float)($fundTotals['vacas'] ?? 0);
        $consolidatedCounts['vacas'] += $counts[$fundId]['vacas'];
        if ($daysInMonth > 0) {
            $fundAvgVacas = ($fundTotals['vacas'] ?? 0) / $daysInMonth;
            $consolidatedAvgVacasSum += (float)$fundAvgVacas;
            $consolidatedAvgVacasHas = true;
        }
    }
    if (!empty($counts[$fundId]['ltsxvc'])) {
        $consolidatedTotals['ltsxvc'] += (float)($fundTotals['ltsxvc'] ?? 0);
        $consolidatedCounts['ltsxvc'] += $counts[$fundId]['ltsxvc'];
    }

    $fundTotalLitros = $fundTotals['litros'] ?? null;
    $fundAvgVacas = $daysInMonth > 0 ? (($fundTotals['vacas'] ?? 0) / $daysInMonth) : null;
    if ($daysInMonth > 0 && $fundTotalLitros !== null && $fundAvgVacas !== null && (float)$fundAvgVacas > 0) {
        $fundMonthlyLtsxvc = ((float)$fundTotalLitros / (float)$fundAvgVacas) / $daysInMonth;
        $consolidatedAvgLtsxvcSum += $fundMonthlyLtsxvc;
        $consolidatedAvgLtsxvcCount++;
    }
}

$consolidatedAvgVacas = $consolidatedAvgVacasHas ? $consolidatedAvgVacasSum : null;
$consolidatedAvgLtsxvc = $consolidatedAvgLtsxvcCount > 0
    ? $consolidatedAvgLtsxvcSum / $consolidatedAvgLtsxvcCount
    : null;

$projectionAvgVacas = $projectionCounts['vacas'] > 0
    ? $projectionTotals['vacas'] / $projectionCounts['vacas']
    : null;
$projectionAvgLtsxvc = $projectionCounts['ltsxvc'] > 0
    ? $projectionTotals['ltsxvc'] / $projectionCounts['ltsxvc']
    : null;

$consolidatedBudget = ['litros' => null, 'vacas' => null, 'ltsxvc' => null];
$budgetLitrosSum = 0.0;
$budgetVacasSum = 0.0;
$budgetLtsxvcSum = 0.0;
$budgetLitrosHas = false;
$budgetLtsxvcCount = 0;
foreach ($fundosMap as $fundId => $fundName) {
    $pres = $presupuestoMap[$fundId] ?? null;
    if (!$pres) {
        continue;
    }
    if (isset($pres['pptoleclitros']) && is_numeric($pres['pptoleclitros'])) {
        $budgetLitrosSum += (float)$pres['pptoleclitros'];
        $budgetLitrosHas = true;
    }
    if (isset($pres['pptolecvacas']) && is_numeric($pres['pptolecvacas'])) {
        $budgetVacasSum += (float)$pres['pptolecvacas'];
    }
    if (isset($pres['pptolecltsxvc']) && is_numeric($pres['pptolecltsxvc'])) {
        $budgetLtsxvcSum += (float)$pres['pptolecltsxvc'];
        $budgetLtsxvcCount++;
    }
}
$consolidatedBudget['litros'] = $budgetLitrosHas ? $budgetLitrosSum : null;
$consolidatedBudget['vacas'] = $budgetVacasSum > 0 ? $budgetVacasSum : null;
$consolidatedBudget['ltsxvc'] = $budgetLtsxvcCount > 0 ? ($budgetLtsxvcSum / $budgetLtsxvcCount) : null;

$dateRows = [];
for ($day = 1; $day <= $daysInMonth; $day++) {
    $dateRows[] = sprintf('%04d-%02d-%02d', $anioActual, $mesActual, $day);
}
?>

<div class="container-fluid mt-2 px-2">
    <style>
        .prodleche-sep {
            border-right: 2px solid #6c757d !important;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-0">Control produccion leche</h3>
            <div class="text-muted small">Mes: <?= htmlspecialchars($mesActual) ?> / <?= htmlspecialchars($anioActual) ?></div>
        </div>
        <form action="?route=prodlechereporte/listar" method="GET" class="row g-2 align-items-center m-0">
            <input type="hidden" name="route" value="prodlechereporte/listar">
            <div class="col-auto">
                <input type="number" name="filtroAnio" class="form-control form-control-sm" min="2000" max="2100"
                       value="<?= htmlspecialchars($filtroAnio) ?>" style="width: 100px;">
            </div>
            <div class="col-auto">
                <select name="filtroMes" class="form-select form-select-sm">
                    <?php foreach ($months as $monthId => $monthName): ?>
                        <option value="<?= $monthId ?>" <?= ((string)$monthId === $filtroMes) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($monthName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="filtroFundoid" class="form-select form-select-sm">
                    <option value="">Todos los fundos</option>
                    <?php foreach ($fundosOptions as $fundoOpt): ?>
                        <option value="<?= htmlspecialchars($fundoOpt['fundoid']) ?>"
                            <?= ($filtroFundoId !== '' && $filtroFundoId == ($fundoOpt['fundoid'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fundoOpt['fundonombre'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary btn-sm">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div class="col-auto">
                <a href="?route=prodlechereporte/listar" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-eraser"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle text-nowrap" style="font-size: 9px;">
            <thead>
                <tr class="table-primary">
                    <th rowspan="2" class="align-middle">Fecha</th>
                    <?php if (empty($fundosMap)): ?>
                        <th class="text-center">Sin fundos</th>
                    <?php else: ?>
                        <?php foreach ($fundosMap as $fundName): ?>
                            <th colspan="3" class="text-center prodleche-sep"><?= htmlspecialchars($fundName) ?></th>
                        <?php endforeach; ?>
                        <th colspan="3" class="text-center prodleche-sep" style="background-color:#2c3e50;color:#fff;">Total Consolidado</th>
                        <th colspan="3" class="text-center prodleche-sep">Proyección</th>
                        <th rowspan="2" class="text-center">Dif. Lts.</th>
                    <?php endif; ?>
                </tr>
                <?php if (!empty($fundosMap)): ?>
                    <tr class="table-light">
                        <?php foreach ($fundosMap as $fundName): ?>
                            <th class="text-center">Litros</th>
                            <th class="text-center">Vacas</th>
                            <th class="text-center prodleche-sep">Lt/vaca</th>
                        <?php endforeach; ?>
                        <th class="text-center" style="background-color:#2c3e50;color:#fff;">Litros</th>
                        <th class="text-center" style="background-color:#2c3e50;color:#fff;">Vacas</th>
                        <th class="text-center prodleche-sep" style="background-color:#2c3e50;color:#fff;">Lt/vaca</th>
                        <th class="text-center">Litros</th>
                        <th class="text-center">Vacas</th>
                        <th class="text-center prodleche-sep">Lt/vaca</th>
                    </tr>
                <?php endif; ?>
            </thead>
            <tbody>
                <?php if (empty($fundosMap)): ?>
                    <tr>
                        <td colspan="2" class="text-center text-muted">No hay fundos disponibles.</td>
                    </tr>
                <?php else: ?>
                    <?php $diffTotalMes = 0.0; ?>
                    <?php foreach ($dateRows as $dateKey): ?>
                        <?php
                            $dayLitrosSum = 0.0;
                            $dayVacasSum = 0.0;
                            $dayLtsxvcSum = 0.0;
                            $dayLitrosHas = false;
                            $dayVacasCount = 0;
                            $dayLtsxvcCount = 0;
                        ?>
                        <tr class="prodleche-date-row">
                            <td><?= htmlspecialchars($formatDate($dateKey)) ?></td>
                            <?php foreach ($fundosMap as $fundId => $fundName): ?>
                                <?php $metrics = $dailyData[$dateKey][$fundId] ?? null; ?>
                                <?php
                                    if ($metrics) {
                                        if ($metrics['litros'] !== null) {
                                            $dayLitrosSum += (float)$metrics['litros'];
                                            $dayLitrosHas = true;
                                        }
                                        if ($metrics['vacas'] !== null) {
                                            $dayVacasSum += (float)$metrics['vacas'];
                                            $dayVacasCount++;
                                        }
                                        if ($metrics['ltsxvc'] !== null) {
                                            $dayLtsxvcSum += (float)$metrics['ltsxvc'];
                                            $dayLtsxvcCount++;
                                        }
                                    }
                                ?>
                                <td class="text-end"><?= htmlspecialchars($metrics ? $formatIntCl($metrics['litros']) : '-') ?></td>
                                <td class="text-end"><?= htmlspecialchars($metrics ? $formatIntCl($metrics['vacas']) : '-') ?></td>
                                <td class="text-end prodleche-sep"><?= htmlspecialchars($metrics ? $formatFloatCl($metrics['ltsxvc']) : '-') ?></td>
                            <?php endforeach; ?>
                            <?php
                            $dayAvgLtsxvc = $dayLtsxvcCount > 0 ? ($dayLtsxvcSum / $dayLtsxvcCount) : null;
                            $dayLitrosOut = $dayLitrosHas ? $formatIntCl($dayLitrosSum) : '-';
                            $dayVacasOut = $dayVacasCount > 0 ? $formatIntCl($dayVacasSum) : '-';
                            $dayLtsxvcOut = $dayAvgLtsxvc === null ? '-' : $formatFloatCl($dayAvgLtsxvc);
                            $projMetrics = $proyeccionMap[$dateKey] ?? null;
                            $projLitrosVal = ($projMetrics && $projMetrics['litros'] !== null) ? (float)$projMetrics['litros'] : 0.0;
                            $projLitrosOut = $projMetrics && $projMetrics['litros'] !== null
                                ? $formatIntCl($projMetrics['litros'])
                                : '-';
                            $projVacasOut = $projMetrics && $projMetrics['vacas'] !== null
                                ? $formatIntCl($projMetrics['vacas'])
                                : '-';
                            $projLtsxvcOut = $projMetrics && $projMetrics['ltsxvc'] !== null
                                ? $formatFloatCl($projMetrics['ltsxvc'])
                                : '-';
                            $diffLitros = $dayLitrosSum - $projLitrosVal;
                            $diffTotalMes += $diffLitros;
                        ?>
                        <td class="text-end"><?= htmlspecialchars($dayLitrosOut) ?></td>
                        <td class="text-end"><?= htmlspecialchars($dayVacasOut) ?></td>
                        <td class="text-end prodleche-sep"><?= htmlspecialchars($dayLtsxvcOut) ?></td>
                        <td class="text-end"><?= htmlspecialchars($projLitrosOut) ?></td>
                        <td class="text-end"><?= htmlspecialchars($projVacasOut) ?></td>
                        <td class="text-end prodleche-sep"><?= htmlspecialchars($projLtsxvcOut) ?></td>
                        <td class="text-end <?= $diffLitros < 0 ? 'text-danger' : '' ?>">
                            <?= htmlspecialchars($formatIntCl($diffLitros)) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <tr class="table-warning fw-bold">
                        <td>Total mes</td>
                        <?php foreach ($fundosMap as $fundId => $fundName): ?>
                            <?php
                                $totalLitros = $totals[$fundId]['litros'] ?? null;
                                $avgVacasBase = $totals[$fundId]['vacas'] ?? null;
                                $avgVacas = null;
                                $avgLtsxvc = null;
                                if ($daysInMonth > 0) {
                                    $avgVacas = ($totals[$fundId]['vacas'] ?? 0) / $daysInMonth;
                                    if ($totalLitros !== null && $avgVacas !== null && (float)$avgVacas > 0) {
                                        $avgLtsxvc = ((float)$totalLitros / (float)$avgVacas) / $daysInMonth;
                                    }
                                }
                            ?>
                            <td class="text-end"><?= htmlspecialchars($totalLitros === null ? '-' : $formatIntCl($totalLitros)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($avgVacas === null ? '-' : $formatFloatCl($avgVacas, 0)) ?></td>
                            <td class="text-end prodleche-sep"><?= htmlspecialchars($avgLtsxvc === null ? '-' : $formatFloatCl($avgLtsxvc)) ?></td>
                        <?php endforeach; ?>
                        <td class="text-end"><?= htmlspecialchars($consolidatedCounts['litros'] === 0 ? '-' : $formatIntCl($consolidatedTotals['litros'])) ?></td>
                        <td class="text-end"><?= htmlspecialchars($consolidatedAvgVacas === null ? '-' : $formatFloatCl($consolidatedAvgVacas, 0)) ?></td>
                        <td class="text-end prodleche-sep"><?= htmlspecialchars($consolidatedAvgLtsxvc === null ? '-' : $formatFloatCl($consolidatedAvgLtsxvc)) ?></td>
                        <td class="text-end"><?= htmlspecialchars($projectionLitrosHas ? $formatIntCl($projectionTotals['litros']) : '-') ?></td>
                        <td class="text-end"><?= htmlspecialchars($projectionAvgVacas === null ? '-' : $formatFloatCl($projectionAvgVacas, 0)) ?></td>
                        <td class="text-end prodleche-sep"><?= htmlspecialchars($projectionAvgLtsxvc === null ? '-' : $formatFloatCl($projectionAvgLtsxvc)) ?></td>
                        <td class="text-end <?= $diffTotalMes < 0 ? 'text-danger' : '' ?>">
                            <?= htmlspecialchars($formatIntCl($diffTotalMes)) ?>
                        </td>
                    </tr>

                    <tr class="table-info">
                        <td>Presupuesto</td>
                        <?php foreach ($fundosMap as $fundId => $fundName): ?>
                            <?php $pres = $presupuestoMap[$fundId] ?? null; ?>
                            <td class="text-end"><?= htmlspecialchars($pres ? $formatIntCl($pres['pptoleclitros'] ?? null) : '-') ?></td>
                            <td class="text-end"><?= htmlspecialchars($pres ? $formatIntCl($pres['pptolecvacas'] ?? null) : '-') ?></td>
                            <td class="text-end prodleche-sep"><?= htmlspecialchars($pres ? $formatFloatCl($pres['pptolecltsxvc'] ?? null) : '-') ?></td>
                        <?php endforeach; ?>
                        <td class="text-end"><?= htmlspecialchars($consolidatedBudget['litros'] === null ? '-' : $formatIntCl($consolidatedBudget['litros'])) ?></td>
                        <td class="text-end"><?= htmlspecialchars($consolidatedBudget['vacas'] === null ? '-' : $formatIntCl($consolidatedBudget['vacas'])) ?></td>
                        <td class="text-end prodleche-sep"><?= htmlspecialchars($consolidatedBudget['ltsxvc'] === null ? '-' : $formatFloatCl($consolidatedBudget['ltsxvc'])) ?></td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end prodleche-sep">-</td>
                        <td class="text-end">-</td>
                    </tr>

                    <tr class="table-success fw-bold">
                        <td>% cumplimiento</td>
                        <?php foreach ($fundosMap as $fundId => $fundName): ?>
                            <?php
                                $pres = $presupuestoMap[$fundId] ?? null;
                                $totalLitros = $totals[$fundId]['litros'] ?? null;
                                $avgVacasBase = $totals[$fundId]['vacas'] ?? null;
                                $avgVacas = null;
                                $avgLtsxvc = null;
                                if ($daysInMonth > 0) {
                                    $avgVacas = ($totals[$fundId]['vacas'] ?? 0) / $daysInMonth;
                                    if ($totalLitros !== null && $avgVacas !== null && (float)$avgVacas > 0) {
                                        $avgLtsxvc = ((float)$totalLitros / (float)$avgVacas) / $daysInMonth;
                                    }
                                }

                                $pctVacas = ($pres && ($pres['pptolecvacas'] ?? 0) > 0 && $avgVacas !== null)
                                    ? ($avgVacas / (float)$pres['pptolecvacas']) * 100
                                    : null;
                                $pctLitros = ($pres && ($pres['pptoleclitros'] ?? 0) > 0 && $totalLitros !== null)
                                    ? ($totalLitros / (float)$pres['pptoleclitros']) * 100
                                    : null;
                                $pctLtsxvc = ($pres && ($pres['pptolecltsxvc'] ?? 0) > 0 && $avgLtsxvc !== null)
                                    ? ($avgLtsxvc / (float)$pres['pptolecltsxvc']) * 100
                                    : null;
                            ?>
                            <td class="text-end"><?= htmlspecialchars($pctLitros === null ? '-' : $formatPercent($pctLitros)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($pctVacas === null ? '-' : $formatPercent($pctVacas)) ?></td>
                            <td class="text-end prodleche-sep"><?= htmlspecialchars($pctLtsxvc === null ? '-' : $formatPercent($pctLtsxvc)) ?></td>
                        <?php endforeach; ?>
                        <?php
                            $pctConsolLitros = ($consolidatedBudget['litros'] ?? 0) > 0
                                ? ($consolidatedTotals['litros'] / (float)$consolidatedBudget['litros']) * 100
                                : null;
                            $pctConsolVacas = ($consolidatedBudget['vacas'] ?? 0) > 0 && $consolidatedAvgVacas !== null
                                ? ($consolidatedAvgVacas / (float)$consolidatedBudget['vacas']) * 100
                                : null;
                            $pctConsolLtsxvc = ($consolidatedBudget['ltsxvc'] ?? 0) > 0 && $consolidatedAvgLtsxvc !== null
                                ? ($consolidatedAvgLtsxvc / (float)$consolidatedBudget['ltsxvc']) * 100
                                : null;
                        ?>
                        <td class="text-end"><?= htmlspecialchars($pctConsolLitros === null ? '-' : $formatPercent($pctConsolLitros)) ?></td>
                        <td class="text-end"><?= htmlspecialchars($pctConsolVacas === null ? '-' : $formatPercent($pctConsolVacas)) ?></td>
                        <td class="text-end prodleche-sep"><?= htmlspecialchars($pctConsolLtsxvc === null ? '-' : $formatPercent($pctConsolLtsxvc)) ?></td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end prodleche-sep">-</td>
                        <td class="text-end">-</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('.prodleche-date-row');
        if (!rows.length) return;

        rows.forEach(function (row) {
            row.addEventListener('click', function () {
                rows.forEach(function (r) { r.classList.remove('table-active'); });
                row.classList.add('table-active');
            });
        });
    });
</script>
