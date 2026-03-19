<?php
// dashboard.php
// Variables: $cards, $charts, $partial, $fundosOptions, $filtroFundoId, $mostrarTodos
$isPartial = $partial ?? false;
$cards = $cards ?? [];
$charts = $charts ?? [];
$fundosOptions = $fundosOptions ?? [];
$filtroFundoId = $filtroFundoId ?? '';
$mostrarTodos = $mostrarTodos ?? true;

$formatNumberCl = static function ($value, int $decimals = 0): string {
    if ($value === null || $value === '') {
        return '-';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, $decimals, ',', '.');
};

$formatCardValue = static function (array $card) use ($formatNumberCl): string {
    $value = $card['value'] ?? null;
    $decimals = (int)($card['decimals'] ?? 0);
    $suffix = (string)($card['suffix'] ?? '');
    if ($value === null || $value === '') {
        return '-';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return $formatNumberCl($value, $decimals) . $suffix;
};

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-0">Dashboard</h3>
            <span class="text-muted">Resumen general</span>
        </div>
        <form action="?route=dashboard/listar" method="GET" class="row g-2 align-items-center m-0">
            <input type="hidden" name="route" value="dashboard/listar">
            <div class="col-auto">
                <select name="filtroFundoid" class="form-select form-select-sm">
                    <?php if ($mostrarTodos): ?>
                        <option value="" <?= $filtroFundoId === '' ? 'selected' : '' ?>>TODOS</option>
                    <?php endif; ?>
                    <?php foreach ($fundosOptions as $fundoOpt): ?>
                        <?php $optId = (string)($fundoOpt['fundoid'] ?? ''); ?>
                        <option value="<?= htmlspecialchars($optId) ?>" <?= $filtroFundoId !== '' && $filtroFundoId == $optId ? 'selected' : '' ?>>
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
            <?php if ($mostrarTodos): ?>
                <div class="col-auto">
                    <a href="?route=dashboard/listar" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eraser"></i>
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($cards as $card): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small"><?= htmlspecialchars($card['title']) ?></div>
                                <div class="h4 mb-0 fw-bold"><?= htmlspecialchars($formatCardValue($card)) ?></div>
                            </div>
                            <div class="text-<?= htmlspecialchars($card['variant']) ?> fs-3">
                                <i class="bi bi-<?= htmlspecialchars($card['icon']) ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Produccion mensual (L)</h5>
                    <canvas id="chartProduccion"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Vacas mensual (promedio)</h5>
                    <canvas id="chartVacas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$isPartial): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            const formatNumber = (value, decimals = 0) => {
                if (value === null || value === undefined || value === '' || isNaN(value)) {
                    return '';
                }
                return Number(value).toLocaleString('es-CL', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            };

            const prod = <?= json_encode($charts['produccionMensual'] ?? []) ?>;
            const ctxProd = document.getElementById('chartProduccion').getContext('2d');
            new Chart(ctxProd, {
                type: 'line',
                data: {
                    labels: prod.labels || [],
                    datasets: [{
                        label: 'Litros',
                        data: prod.data || [],
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.2)',
                        tension: 0.25,
                        fill: true
                    }]
                },
                options: {
                    plugins: {
                        legend: {display:false},
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatNumber(context.parsed.y, 0);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero:true,
                            ticks: {
                                callback: function(value) {
                                    return formatNumber(value, 0);
                                }
                            }
                        }
                    }
                }
            });

            const vacas = <?= json_encode($charts['vacasMensual'] ?? []) ?>;
            const vacasLabels = vacas.labels || [];
            const vacasData = vacas.data || [];
            const datasets = [{
                label: 'Vacas promedio',
                data: vacasData,
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,0.2)',
                tension: 0.25,
                fill: true
            }];

            if (vacas.min !== null && vacas.min !== undefined && vacasLabels.length) {
                datasets.push({
                    label: 'Minimo',
                    data: vacasLabels.map(() => vacas.min),
                    borderColor: '#dc3545',
                    borderDash: [6, 6],
                    pointRadius: 0,
                    fill: false
                });
            }

            if (vacas.max !== null && vacas.max !== undefined && vacasLabels.length) {
                datasets.push({
                    label: 'Maximo',
                    data: vacasLabels.map(() => vacas.max),
                    borderColor: '#0d6efd',
                    borderDash: [6, 6],
                    pointRadius: 0,
                    fill: false
                });
            }

            const ctxV = document.getElementById('chartVacas').getContext('2d');
            new Chart(ctxV, {
                type: 'line',
                data: {
                    labels: vacasLabels,
                    datasets: datasets
                },
                options: {
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatNumber(context.parsed.y, 0);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero:true,
                            ticks: {
                                callback: function(value) {
                                    return formatNumber(value, 0);
                                }
                            }
                        }
                    }
                }
            });
        })();
    </script>
<?php endif; ?>

<?php if (!$isPartial) { require 'footer.php'; } ?>
