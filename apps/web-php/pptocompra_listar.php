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

$fmtMoney = static function ($value, int $decimals = 2): string {
    if (!is_numeric($value)) {
        return '0';
    }
    return number_format((float)$value, $decimals, ',', '.');
};
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Presupuesto de Compras</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="?route=pptocompra/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Presupuesto
        </a>
    </div>

    <form id="pptocompra-filter-form" action="?route=pptocompra/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="pptocompra/listar">
        <div class="col-md-2">
            <input type="number" name="filtroPptocompraid" class="form-control" placeholder="ID Presupuesto" value="<?= htmlspecialchars($filtros['filtroPptocompraid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroTemporadaid" class="form-select">
                <option value="">Temporada</option>
                <?php foreach ($temporadas as $temporadaOpt): ?>
                    <option value="<?= htmlspecialchars($temporadaOpt['temporadaid'] ?? '') ?>" <?= ((string)($filtros['filtroTemporadaid'] ?? '') === (string)($temporadaOpt['temporadaid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($temporadaOpt['temporadacod'] ?? '') . ' - ' . ($temporadaOpt['temporadadescripcion'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroTemporadatipo" class="form-select">
                <option value="">Tipo Temporada</option>
                <option value="PPTO_COMPRAS" <?= ($filtros['filtroTemporadatipo'] ?? '') === 'PPTO_COMPRAS' ? 'selected' : '' ?>>PPTO_COMPRAS</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroSubfamiliaid" class="form-select">
                <option value="">Subfamilia</option>
                <?php foreach ($subfamilias as $subfamiliaOpt): ?>
                    <option value="<?= htmlspecialchars($subfamiliaOpt['subfamiliaid'] ?? '') ?>" <?= ((string)($filtros['filtroSubfamiliaid'] ?? '') === (string)($subfamiliaOpt['subfamiliaid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($subfamiliaOpt['subfamiliacod'] ?? '') . ' - ' . ($subfamiliaOpt['subfamiliadsc'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroCentrocostoid" class="form-select">
                <option value="">Centro de Costo</option>
                <?php foreach ($centroscosto as $centroOpt): ?>
                    <option value="<?= htmlspecialchars($centroOpt['centrocostoid'] ?? '') ?>" <?= ((string)($filtros['filtroCentrocostoid'] ?? '') === (string)($centroOpt['centrocostoid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($centroOpt['centrocostocod'] ?? '') . ' - ' . ($centroOpt['centrocostodsc'] ?? '')) ?>
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
        <table class="table table-striped table-hover align-middle">
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
                    <th class="text-end">Consumo en curso</th>
                    <th class="text-end">Consumo confirmado</th>
                    <th class="text-end">Saldo disponible</th>
                    <th class="text-end">Periodos</th>
                    <th class="text-end">Estado</th>
                    <th class="col-actions-lg">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pptocompra)): ?>
                    <tr><td colspan="13" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($pptocompra as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['pptocompraid'] ?? '') ?></td>
                            <td>
                                <?= htmlspecialchars(($row['temporadacod'] ?? '') . ' - ' . ($row['temporadadescripcion'] ?? '')) ?>
                                <div class="small text-muted">
                                    <?= htmlspecialchars(($row['temporadatipocodigo'] ?? '') . ' | ' . ($row['temporadainicio'] ?? '') . ' a ' . ($row['temporadafin'] ?? '')) ?>
                                </div>
                            </td>
                            <td>
                                <?= htmlspecialchars(($row['subfamiliacod'] ?? '') . ' - ' . ($row['subfamiliadsc'] ?? '')) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(($row['centrocostocod'] ?? '') . ' - ' . ($row['centrocostodsc'] ?? '')) ?>
                            </td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['presupuestado'] ?? 0, 2)) ?></td>
                            <td class="text-end text-success">+<?= htmlspecialchars($fmtMoney($row['ajustespositivos'] ?? 0, 2)) ?></td>
                            <td class="text-end text-danger">-<?= htmlspecialchars($fmtMoney($row['ajustesnegativos'] ?? 0, 2)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['reproyectado'] ?? 0, 2)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['consumosencurso'] ?? 0, 2)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['consumosconfirmados'] ?? 0, 2)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($fmtMoney($row['saldodisponible'] ?? 0, 2)) ?></td>
                            <td class="text-end"><?= htmlspecialchars($row['total_periodos'] ?? 0) ?></td>
                            <td>
                                <?= !empty($row['pptocompraactivo']) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" href="?route=pptocompra/detalle&pptocompraid=<?= urlencode($row['pptocompraid'] ?? '') ?>">
                                    <i class="bi bi-journal-text"></i> Detalle
                                </a>
                                <?php if (!empty($row['pptocompraactivo'])): ?>
                                    <a class="btn btn-warning btn-sm" href="?route=pptocompra/editar&pptocompraid=<?= urlencode($row['pptocompraid'] ?? '') ?>">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </a>
                                    <form action="?route=pptocompra/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Desea anular este presupuesto de compras?">
                                        <input type="hidden" name="pptocompraid" value="<?= htmlspecialchars($row['pptocompraid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-circle"></i> Anular
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

<?php if (!$isPartial) { require 'footer.php'; } ?>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>
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
    });
</script>
