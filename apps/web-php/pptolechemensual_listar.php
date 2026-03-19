<?php
// Listado de Presupuesto Produccion de Leche Mensual
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

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
?>

<div class="container mt-4">
    <h3 class="mb-4">Presupuesto Produccion Leche Mensual</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form id="pptoleche-excel-form" action="?route=pptolechemensual/crear" method="POST" enctype="multipart/form-data" class="m-0">
            <input type="file" name="pptoleche_excel" id="pptoleche_excel" class="d-none" accept=".xlsx,.xls,.csv">
            <button type="button" id="btn-pptoleche-excel" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Carga masiva Excel
            </button>
        </form>

        <a href="?route=pptolechemensual/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Registro
        </a>
    </div>

    <form id="pptoleche-filter-form" action="?route=pptolechemensual/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="pptolechemensual/listar">
        <div class="col-md-2">
            <input type="number" name="filtroPptolecanio" class="form-control" placeholder="Año"
                   value="<?= htmlspecialchars($filtros['filtroPptolecanio'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroPptolecmes" class="form-select">
                <option value="">Mes</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ((string)($filtros['filtroPptolecmes'] ?? '') === (string)$m) ? 'selected' : '' ?>>
                        <?= $m ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
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
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-pptoleche" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Año</th>
                    <th>Mes</th>
                    <th>Fundo</th>
                    <th>Litros</th>
                    <th>Vacas</th>
                    <th>Lts x Vaca</th>
                    <th>Fecha</th>
                    <th>Dias Mes</th>
                    <th style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pptolechemensual)): ?>
                    <tr><td colspan="9" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($pptolechemensual as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($formatIntCl($p['pptolecanio'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['pptolecmes'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($p['fundonombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['pptoleclitros'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['pptolecvacas'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatFloatCl($p['pptolecltsxvc'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatDate($p['pptolecfecha'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['pptolecdiasdelmes'] ?? null)) ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=pptolechemensual/editar&anio=<?= urlencode($p['pptolecanio'] ?? '') ?>&mes=<?= urlencode($p['pptolecmes'] ?? '') ?>&fundoid=<?= urlencode($p['fundoid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <form action="?route=pptolechemensual/anular" method="POST" class="d-inline">
                                    <input type="hidden" name="pptolecanio" value="<?= htmlspecialchars($p['pptolecanio'] ?? '') ?>">
                                    <input type="hidden" name="pptolecmes" value="<?= htmlspecialchars($p['pptolecmes'] ?? '') ?>">
                                    <input type="hidden" name="fundoid" value="<?= htmlspecialchars($p['fundoid'] ?? '') ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar registro?');">
                                        <i class="bi bi-x-circle"></i> Eliminar
                                    </button>
                                </form>
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
        const form = document.getElementById('pptoleche-filter-form');
        const clearBtn = document.getElementById('btn-clear-pptoleche');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="number"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        const excelForm = document.getElementById('pptoleche-excel-form');
        const excelInput = document.getElementById('pptoleche_excel');
        const excelBtn = document.getElementById('btn-pptoleche-excel');

        if (excelBtn && excelInput && excelForm) {
            excelBtn.addEventListener('click', function () {
                excelInput.click();
            });
            excelInput.addEventListener('change', function () {
                if (excelInput.files && excelInput.files.length > 0) {
                    if (confirm('¿Desea cargar el archivo Excel seleccionado?')) {
                        excelForm.submit();
                    }
                }
            });
        }

        if (form) {
            const autoKey = 'pptolecheAutoSearch';
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
