<?php
// Listado de Retiro de Leche
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$formatFloat = static function ($value, int $decimals = 1): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, $decimals, ',', '.');
};

$formatInt = static function ($value): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, 0, ',', '.');
};

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

$today = new DateTime('today');
$defaultFechaHasta = $today->format('Y-m-d');
$defaultFechaDesde = (clone $today)->modify('-15 days')->format('Y-m-d');

$fechaDesdeValue = $filtros['filtroFechaDesde'] ?? '';
if ($fechaDesdeValue === '') {
    $fechaDesdeValue = $defaultFechaDesde;
}
$fechaHastaValue = $filtros['filtroFechaHasta'] ?? '';
if ($fechaHastaValue === '') {
    $fechaHastaValue = $defaultFechaHasta;
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Retiro de Leche</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="retiroleche">

            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroFundoid" value="<?= htmlspecialchars($filtros['filtroFundoid'] ?? '') ?>">
            <input type="hidden" name="filtroRetirolechestatus" value="<?= htmlspecialchars($filtros['filtroRetirolechestatus'] ?? '') ?>">
            <input type="hidden" name="filtroFechaDesde" value="<?= htmlspecialchars($filtros['filtroFechaDesde'] ?? '') ?>">
            <input type="hidden" name="filtroFechaHasta" value="<?= htmlspecialchars($filtros['filtroFechaHasta'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>
        <a href="?route=retiroleche/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Registro
        </a>
    </div>

    <form id="retiroleche-filter-form" action="?route=retiroleche/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="retiroleche/listar">
        <div class="col-md-3">
            <select name="filtroFundoid" class="form-select">
                <option value="">Fundo</option>
                <?php foreach (($fundosOptions ?? []) as $fundoOpt): ?>
                    <option value="<?= htmlspecialchars($fundoOpt['fundoid']) ?>"
                        <?= ($filtros['filtroFundoid'] ?? '') == ($fundoOpt['fundoid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fundoOpt['fundonombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroRetirolechestatus" class="form-select">
                <option value="">Estatus</option>
                <option value="CN" <?= ($filtros['filtroRetirolechestatus'] ?? '') === 'CN' ? 'selected' : '' ?>>Confirmada</option>
                <option value="ANL" <?= ($filtros['filtroRetirolechestatus'] ?? '') === 'ANL' ? 'selected' : '' ?>>Anulada</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="filtroFechaDesde" class="form-control"
                   value="<?= htmlspecialchars($fechaDesdeValue) ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="filtroFechaHasta" class="form-control"
                   value="<?= htmlspecialchars($fechaHastaValue) ?>">
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-retiroleche" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fundo</th>
                    <th>Fecha</th>
                    <th>Estanque</th>
                    <th>Cliente</th>
                    <th>Cod Cliente</th>
                    <th>Litros</th>
                    <th>T</th>
                    <th>Observacion</th>
                    <th>Imagen</th>
                    <th style="width: 190px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($retiroleche)): ?>
                    <tr><td colspan="11" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($retiroleche as $r): ?>
                        <?php $estatus = $r['retirolechestatus'] ?? ''; ?>
                        <?php $isAnulada = $estatus === 'ANL'; ?>
                        <tr>
                            <td><?= htmlspecialchars($formatInt($r['retirolecheid'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($r['fundonombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($formatDate($r['retirolechefecha'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($r['fundoestanquedsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['clienterazonsocial'] ?? '') ?></td>
                            <td><?= htmlspecialchars((string)($r['estanqueclientecod'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($formatInt($r['retirolechelitros'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatFloat($r['retirolechetemperatura'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($r['retirolecheobservacion'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($r['retirolechefoto'])): ?>
                                    <a class="btn btn-outline-secondary btn-sm" target="_blank"
                                       href="uploads/retiroleche/img/<?= htmlspecialchars($r['retirolecheid'] ?? '') ?>/<?= htmlspecialchars($r['retirolechefoto']) ?>">
                                        <i class="bi bi-image"></i> Ver
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Sin imagen</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isAnulada): ?>
                                    <button class="btn btn-secondary btn-sm" disabled title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                <?php else: ?>
                                    <a class="btn btn-warning btn-sm" href="?route=retiroleche/editar&id=<?= urlencode($r['retirolecheid'] ?? '') ?>" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!$isAnulada): ?>
                                    <form action="?route=retiroleche/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="retirolecheid" value="<?= htmlspecialchars($r['retirolecheid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Desea anular este registro?');" title="Anular" aria-label="Anular">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Anular" aria-label="Anular">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('retiroleche-filter-form');
        const clearBtn = document.getElementById('btn-clear-retiroleche');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select, input[type="date"]').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'retirolecheAutoSearch';
            const submitForm = () => form.requestSubmit();

            if (window.__hasToast) {
                sessionStorage.removeItem(autoKey);
                return;
            }

            if (!sessionStorage.getItem(autoKey)) {
                sessionStorage.setItem(autoKey, '1');
                submitForm();
            } else {
                sessionStorage.removeItem(autoKey);
            }
        }
    });
</script>
