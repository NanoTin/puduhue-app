<?php
$isPartial = $partial ?? false;
$filtros = $filtros ?? [];
$pptocompra = $pptocompra ?? [];
$temporadas = $temporadas ?? [];
$subfamilias = $subfamilias ?? [];
$centroscosto = $centroscosto ?? [];
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$fmtDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    try {
        return (new DateTime($value))->format('d-m-Y');
    } catch (Exception $e) {
        return (string)$value;
    }
};

$fmtMoney = static function ($value, int $decimals = 0): string {
    if (!is_numeric($value)) {
        return '0';
    }
    return number_format((float)$value, $decimals, ',', '.');
};
?>

<div class="container-fluid px-4 py-3 pdh-page">
    <div class="pdh-page__header">
        <div>
            <h3 class="mb-1">Presupuesto de Compras</h3>
        </div>
        <div class="pdh-page__actions">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pptocompraCreateModeModal">
                <i class="bi bi-plus-circle"></i> Crear Presupuesto
            </button>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form id="pptocompra-filter-form" action="?route=pptocompra/listar" method="GET" class="row g-2 pdh-filter-bar">
        <input type="hidden" name="route" value="pptocompra/listar">
        <div class="col-md-2">
            <input type="number" name="filtroPptocompraid" class="form-control" placeholder="ID Presupuesto" value="<?= htmlspecialchars($filtros['filtroPptocompraid'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="filtroTemporadaid" class="form-select">
                <option value="">Temporada</option>
                <?php foreach ($temporadas as $temporadaOpt): ?>
                    <option value="<?= htmlspecialchars($temporadaOpt['temporadaid'] ?? '') ?>" <?= ((string)($filtros['filtroTemporadaid'] ?? '') === (string)($temporadaOpt['temporadaid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($temporadaOpt['temporadadescripcion'] ?? '') . ' (' . ($temporadaOpt['temporadatipocodigo'] ?? '') . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroSubfamiliaid" class="form-select">
                <option value="">Subfamilia</option>
                <?php foreach ($subfamilias as $subfamiliaOpt): ?>
                    <option value="<?= htmlspecialchars($subfamiliaOpt['subfamiliaid'] ?? '') ?>" <?= ((string)($filtros['filtroSubfamiliaid'] ?? '') === (string)($subfamiliaOpt['subfamiliaid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($subfamiliaOpt['subfamiliadsc'] ?? '') . ' (' . ($subfamiliaOpt['subfamiliacod'] ?? '') . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroCentrocostoid" class="form-select">
                <option value="">Centro de Costo</option>
                <?php foreach ($centroscosto as $centroOpt): ?>
                    <option value="<?= htmlspecialchars($centroOpt['centrocostoid'] ?? '') ?>" <?= ((string)($filtros['filtroCentrocostoid'] ?? '') === (string)($centroOpt['centrocostoid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($centroOpt['centrocostodsc'] ?? '') . ' (' . ($centroOpt['centrocostocod'] ?? '') . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <select name="filtroPptocompraactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroPptocompraactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroPptocompraactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-pptocompra" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle pdh-data-table">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Temporada</th>
                    <th>Subfamilia</th>
                    <th>Centro de Costo</th>
                    <th class="text-end">Presupuestado</th>
                    <th class="text-end">Ajuste +</th>
                    <th class="text-end">Ajuste -</th>
                    <th class="text-end">Reproyectado</th>
                    <th class="text-end">Cons. curso</th>
                    <th class="text-end">Cons. conf.</th>
                    <th class="text-end">Disponible</th>
                    <th class="text-end">Per.</th>
                    <th>Estado</th>
                    <th class="col-actions-sm text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pptocompra)): ?>
                    <tr><td colspan="14" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($pptocompra as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['pptocompraid'] ?? '') ?></td>
                            <td>
                                <?= htmlspecialchars($row['temporadadescripcion'] ?? '') ?>
                                <div class="small text-muted">
                                    <?= htmlspecialchars(($row['temporadatipocodigo'] ?? '') . ' | ' . ($row['temporadainicio'] ?? '') . ' a ' . ($row['temporadafin'] ?? '')) ?>
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['subfamiliadsc'] ?? '') ?>
                                <div class="small text-muted"><?= htmlspecialchars($row['subfamiliacod'] ?? '') ?></div>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['centrocostodsc'] ?? '') ?>
                                <div class="small text-muted"><?= htmlspecialchars($row['centrocostocod'] ?? '') ?></div>
                            </td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['presupuestado'] ?? 0)) ?></td>
                            <td class="text-end text-success">+<?= htmlspecialchars($fmtMoney($row['ajustespositivos'] ?? 0)) ?></td>
                            <td class="text-end text-danger"><?= htmlspecialchars($fmtMoney($row['ajustesnegativos'] ?? 0)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['reproyectado'] ?? 0)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['consumosencurso'] ?? 0)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['consumosconfirmados'] ?? 0)) ?></td>
                            <td class="text-end fw-semibold"><?= htmlspecialchars($fmtMoney($row['saldodisponible'] ?? 0)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($row['total_periodos'] ?? 0) ?></td>
                            <td>
                                <?= !empty($row['pptocompraactivo']) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?>
                            </td>
                            <td class="pdh-data-table__actions">
                                <a class="btn btn-outline-primary btn-sm" href="?route=pptocompra/detalle&pptocompraid=<?= urlencode($row['pptocompraid'] ?? '') ?>" title="Ver detalle" aria-label="Ver detalle" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (!empty($row['pptocompraactivo'])): ?>
                                    <a class="btn btn-outline-success btn-sm" href="?route=pptocompra/ajustar&pptocompraid=<?= urlencode($row['pptocompraid'] ?? '') ?>" title="Ajustar presupuesto" aria-label="Ajustar presupuesto" data-bs-toggle="tooltip">
                                        <i class="bi bi-sliders2"></i>
                                    </a>
                                    <a class="btn btn-outline-secondary btn-sm" href="?route=pptocompra/traspasar&pptocompraid=<?= urlencode($row['pptocompraid'] ?? '') ?>" title="Traspasar entre presupuestos" aria-label="Traspasar entre presupuestos" data-bs-toggle="tooltip">
                                        <i class="bi bi-arrow-left-right"></i>
                                    </a>
                                    <form action="?route=pptocompra/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Desea cambiar este presupuesto a NO vigente?">
                                        <input type="hidden" name="pptocompraid" value="<?= htmlspecialchars($row['pptocompraid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Cambiar a NO Vigente" aria-label="Cambiar a NO Vigente" data-bs-toggle="tooltip">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="pptocompraCreateModeModal" tabindex="-1" aria-labelledby="pptocompraCreateModeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pptocompraCreateModeModalLabel">Forma de carga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Seleccione como desea crear el presupuesto de compras.</p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <a href="?route=pptocompra/crear" class="btn btn-outline-primary w-100 py-3">
                            <i class="bi bi-pencil-square d-block fs-3 mb-2"></i>
                            Manual
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="?route=pptocompra/carga_masiva" class="btn btn-outline-success w-100 py-3">
                            <i class="bi bi-file-earmark-spreadsheet d-block fs-3 mb-2"></i>
                            Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('pptocompra-filter-form');
        const clearBtn = document.getElementById('btn-clear-pptocompra');

        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], input[type="number"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    });
</script>
