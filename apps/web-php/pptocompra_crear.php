<?php
$isPartial = $partial ?? false;
$formData = $formData ?? [];
$temporadas = $temporadas ?? [];
$subfamilias = $subfamilias ?? [];
$centroscosto = $centroscosto ?? [];
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$mensual = is_array($formData['mensual'] ?? null) ? $formData['mensual'] : [];
if (empty($mensual)) {
    $mensual = [['ppoanio' => '', 'ppomes' => '', 'ppomontoppto' => '', 'ppoobservacion' => '']];
}
?>

<link rel="stylesheet" href="assets/css/frm_mstr.css">
<div class="form-responsive-container">
    <h3 class="page-title">Crear Presupuesto de Compras</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="?route=pptocompra/crear"
          autocomplete="off"
          data-confirm="1"
          data-confirm-message="¿Desea guardar el presupuesto de compras con los meses definidos?">
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label" for="temporadaid">Temporada</label>
                <select name="temporadaid" id="temporadaid" class="form-select" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($temporadas as $temporadaOpt): ?>
                        <option value="<?= htmlspecialchars($temporadaOpt['temporadaid'] ?? '') ?>" <?= ((string)($formData['temporadaid'] ?? '') === (string)($temporadaOpt['temporadaid'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($temporadaOpt['temporadadescripcion'] ?? '') . ' (' . ($temporadaOpt['temporadainicio'] ?? '') . ' - ' . ($temporadaOpt['temporadafin'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="subfamiliaid">Subfamilia</label>
                <select name="subfamiliaid" id="subfamiliaid" class="form-select" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($subfamilias as $subfamiliaOpt): ?>
                        <option value="<?= htmlspecialchars($subfamiliaOpt['subfamiliaid'] ?? '') ?>" <?= ((string)($formData['subfamiliaid'] ?? '') === (string)($subfamiliaOpt['subfamiliaid'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($subfamiliaOpt['subfamiliacod'] ?? '') . ' - ' . ($subfamiliaOpt['subfamiliadsc'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="centrocostoid">Centro de Costo</label>
                <select name="centrocostoid" id="centrocostoid" class="form-select" required>
                    <option value="">Seleccione</option>
                    <?php foreach ($centroscosto as $centroOpt): ?>
                        <option value="<?= htmlspecialchars($centroOpt['centrocostoid'] ?? '') ?>" <?= ((string)($formData['centrocostoid'] ?? '') === (string)($centroOpt['centrocostoid'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($centroOpt['centrocostocod'] ?? '') . ' - ' . ($centroOpt['centrocostodsc'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <section class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Detalle mensual</h5>
                <button type="button" id="add-month-line" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-circle"></i> Agregar periodo
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle" id="pptocompra-months-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Año</th>
                            <th style="width: 100px;">Mes</th>
                            <th style="width: 160px;">Monto base</th>
                            <th>Observación</th>
                            <th style="width: 90px;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody id="pptocompra-months-body">
                        <?php foreach ($mensual as $index => $line): ?>
                            <tr data-row-index="<?= (int)$index ?>">
                                <td>
                                    <input type="number" class="form-control form-control-sm line-year"
                                           name="mensual[<?= (int)$index ?>][ppoanio]"
                                           min="2000" max="2200"
                                           value="<?= htmlspecialchars((string)($line['ppoanio'] ?? '')) ?>">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm line-month" name="mensual[<?= (int)$index ?>][ppomes]">
                                        <option value="">Mes</option>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= (string)($line['ppomes'] ?? '') === (string)$m ? 'selected' : '' ?>><?= $m ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm line-amount"
                                           name="mensual[<?= (int)$index ?>][ppomontoppto]"
                                           step="0.01" min="0" value="<?= htmlspecialchars((string)($line['ppomontoppto'] ?? '')) ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm line-observation"
                                           name="mensual[<?= (int)$index ?>][ppoobservacion]"
                                           maxlength="500"
                                           value="<?= htmlspecialchars((string)($line['ppoobservacion'] ?? '')) ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-month-line" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=pptocompra/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<template id="month-row-template">
    <tr>
        <td>
            <input type="number" class="form-control form-control-sm line-year" min="2000" max="2200" value="">
        </td>
        <td>
            <select class="form-select form-select-sm line-month">
                <option value="">Mes</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>"><?= $m ?></option>
                <?php endfor; ?>
            </select>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm line-amount" step="0.01" min="0" value="">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm line-observation" maxlength="500" value="">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm remove-month-line" title="Eliminar">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
<script>
    (function () {
        const body = document.getElementById('pptocompra-months-body');
        const addBtn = document.getElementById('add-month-line');
        const template = document.getElementById('month-row-template');

        function refreshRows() {
            Array.from(body.querySelectorAll('tr')).forEach((row, index) => {
                row.querySelector('.line-year').name = `mensual[${index}][ppoanio]`;
                row.querySelector('.line-month').name = `mensual[${index}][ppomes]`;
                row.querySelector('.line-amount').name = `mensual[${index}][ppomontoppto]`;
                row.querySelector('.line-observation').name = `mensual[${index}][ppoobservacion]`;
                row.dataset.rowIndex = index;
            });
        }

        function addLine() {
            const fragment = template.content.cloneNode(true);
            body.appendChild(fragment);
            refreshRows();
        }

        function removeLine(event) {
            const btn = event.target.closest('.remove-month-line');
            if (!btn) return;
            const row = btn.closest('tr');
            if (!row) return;
            if (body.querySelectorAll('tr').length <= 1) {
                return;
            }
            row.remove();
            refreshRows();
        }

        if (addBtn) {
            addBtn.addEventListener('click', addLine);
        }

        body.addEventListener('click', removeLine);
        refreshRows();
    })();
</script>
