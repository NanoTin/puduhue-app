<?php
// Listado Proyeccion de Leche - Consolidado
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
    <h3 class="mb-4">Proyeccion de Leche - Consolidado</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form id="proyleche-excel-form" action="?route=proylechediaria/crear" method="POST" enctype="multipart/form-data" class="m-0">
            <input type="file" name="proyleche_excel" id="proyleche_excel" class="d-none" accept=".xlsx,.xls,.csv">
            <button type="button" id="btn-proyleche-excel" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Carga masiva Excel
            </button>
        </form>

        <a href="?route=proylechediaria/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Registro
        </a>
    </div>

    <form id="proyleche-filter-form" action="?route=proylechediaria/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="proylechediaria/listar">
        <div class="col-md-2">
            <input type="number" name="filtroProylecheanio" class="form-control" placeholder="Anio"
                   value="<?= htmlspecialchars($filtros['filtroProylecheanio'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroProylechemes" class="form-select">
                <option value="">Mes</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ((string)($filtros['filtroProylechemes'] ?? '') === (string)$m) ? 'selected' : '' ?>>
                        <?= $m ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-proyleche" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Anio</th>
                    <th>Mes</th>
                    <th>Litros</th>
                    <th>Vacas</th>
                    <th>Lts x Vaca</th>
                    <th style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($proylechediaria)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($proylechediaria as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($formatDate($p['proylechefecha'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['proylecheanio'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['proylechemes'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['proylecheventatotlitros'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['proylecheventatotvacas'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatFloatCl($p['proylecheventatotltsxvaca'] ?? null)) ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=proylechediaria/editar&fecha=<?= urlencode($p['proylechefecha'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <form action="?route=proylechediaria/anular" method="POST" class="d-inline">
                                    <input type="hidden" name="proylechefecha" value="<?= htmlspecialchars($p['proylechefecha'] ?? '') ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Desea eliminar el registro?');">
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
        const form = document.getElementById('proyleche-filter-form');
        const clearBtn = document.getElementById('btn-clear-proyleche');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="number"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        const excelForm = document.getElementById('proyleche-excel-form');
        const excelInput = document.getElementById('proyleche_excel');
        const excelBtn = document.getElementById('btn-proyleche-excel');

        if (excelBtn && excelInput && excelForm) {
            excelBtn.addEventListener('click', function () {
                excelInput.click();
            });
            excelInput.addEventListener('change', function () {
                if (excelInput.files && excelInput.files.length > 0) {
                    if (confirm('Desea cargar el archivo Excel seleccionado?')) {
                        excelForm.submit();
                    }
                }
            });
        }

        if (form) {
            const autoKey = 'proylecheAutoSearch';
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
