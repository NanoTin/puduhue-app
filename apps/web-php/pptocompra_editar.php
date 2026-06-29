<?php
$isPartial = $partial ?? false;
$formData = $formData ?? [];
$pptocompra = $pptocompra ?? [];
$mensual = is_array($mensual ?? null) ? $mensual : [];
$temporadas = $temporadas ?? [];
$subfamilias = $subfamilias ?? [];
$centroscosto = $centroscosto ?? [];
$hasMovimientos = (bool)($hasMovimientos ?? false);
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

if (empty($mensual)) {
    $mensual = [['ppoanio' => '', 'ppomes' => '', 'ppomontoppto' => '', 'ppoobservacion' => '']];
}
?>

<link rel="stylesheet" href="assets/css/frm_mstr.css">
<div class="form-responsive-container">
    <h3 class="page-title">Editar Presupuesto de Compras</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($hasMovimientos): ?>
        <div class="alert alert-warning" role="alert">
            Este presupuesto tiene movimientos registrados. La carga mensual base no puede modificarse; para cambios de monto use Ajustes.
        </div>
    <?php endif; ?>

    <form method="POST"
          action="?route=pptocompra/editar"
          autocomplete="off"
          data-confirm="1"
          data-confirm-message="¿Desea actualizar el presupuesto de compras?"
    >
        <input type="hidden" name="pptocompraid" value="<?= htmlspecialchars((string)($formData['pptocompraid'] ?? ($pptocompra['pptocompraid'] ?? ''))) ?>">

        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label" for="temporadaid">Temporada</label>
                <select name="temporadaid" id="temporadaid" class="form-select" required <?= $hasMovimientos ? 'disabled' : '' ?>>
                    <option value="">Seleccione</option>
                    <?php foreach ($temporadas as $temporadaOpt): ?>
                        <option value="<?= htmlspecialchars($temporadaOpt['temporadaid'] ?? '') ?>" <?= ((string)($formData['temporadaid'] ?? ($pptocompra['temporadaid'] ?? '')) === (string)($temporadaOpt['temporadaid'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($temporadaOpt['temporadadescripcion'] ?? '') . ' (' . ($temporadaOpt['temporadainicio'] ?? '') . ' - ' . ($temporadaOpt['temporadafin'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($hasMovimientos): ?>
                    <input type="hidden" name="temporadaid" value="<?= htmlspecialchars((string)($formData['temporadaid'] ?? ($pptocompra['temporadaid'] ?? ''))) ?>">
                <?php endif; ?>
            </div>
            <div class="form-field">
                <label class="form-label" for="subfamiliaid">Subfamilia</label>
                <select name="subfamiliaid" id="subfamiliaid" class="form-select" required <?= $hasMovimientos ? 'disabled' : '' ?>>
                    <option value="">Seleccione</option>
                    <?php foreach ($subfamilias as $subfamiliaOpt): ?>
                        <option value="<?= htmlspecialchars($subfamiliaOpt['subfamiliaid'] ?? '') ?>" <?= ((string)($formData['subfamiliaid'] ?? ($pptocompra['subfamiliaid'] ?? '')) === (string)($subfamiliaOpt['subfamiliaid'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($subfamiliaOpt['subfamiliadsc'] ?? '') . ' (' . ($subfamiliaOpt['subfamiliacod'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($hasMovimientos): ?>
                    <input type="hidden" name="subfamiliaid" value="<?= htmlspecialchars((string)($formData['subfamiliaid'] ?? ($pptocompra['subfamiliaid'] ?? ''))) ?>">
                <?php endif; ?>
            </div>
            <div class="form-field">
                <label class="form-label" for="centrocostoid">Centro de Costo</label>
                <select name="centrocostoid" id="centrocostoid" class="form-select" required <?= $hasMovimientos ? 'disabled' : '' ?>>
                    <option value="">Seleccione</option>
                    <?php foreach ($centroscosto as $centroOpt): ?>
                        <option value="<?= htmlspecialchars($centroOpt['centrocostoid'] ?? '') ?>" <?= ((string)($formData['centrocostoid'] ?? ($pptocompra['centrocostoid'] ?? '')) === (string)($centroOpt['centrocostoid'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($centroOpt['centrocostodsc'] ?? '') . ' (' . ($centroOpt['centrocostocod'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($hasMovimientos): ?>
                    <input type="hidden" name="centrocostoid" value="<?= htmlspecialchars((string)($formData['centrocostoid'] ?? ($pptocompra['centrocostoid'] ?? ''))) ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-field mb-3">
            <label class="form-label" for="pptocompraobservacion">Observación presupuesto</label>
            <textarea name="pptocompraobservacion" id="pptocompraobservacion" class="form-control" rows="2" maxlength="500" <?= $hasMovimientos ? 'readonly' : '' ?>><?= htmlspecialchars((string)($formData['pptocompraobservacion'] ?? ($pptocompra['pptocompraobservacion'] ?? ''))) ?></textarea>
        </div>

        <section class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Detalle mensual</h5>
                <?php if (!$hasMovimientos): ?>
                    <button type="button" id="add-month-line" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Agregar periodo
                    </button>
                <?php endif; ?>
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
                                           value="<?= htmlspecialchars((string)($line['ppoanio'] ?? '')) ?>" <?= $hasMovimientos ? 'readonly' : '' ?>>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm line-month" name="mensual[<?= (int)$index ?>][ppomes]" <?= $hasMovimientos ? 'disabled' : '' ?>>
                                        <option value="">Mes</option>
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?= $m ?>" <?= (string)($line['ppomes'] ?? '') === (string)$m ? 'selected' : '' ?>><?= $m ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <?php if ($hasMovimientos): ?>
                                        <input type="hidden" name="mensual[<?= (int)$index ?>][ppomes]" value="<?= htmlspecialchars((string)($line['ppomes'] ?? '')) ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm line-amount"
                                           name="mensual[<?= (int)$index ?>][ppomontoppto]"
                                           step="0.01" min="0" value="<?= htmlspecialchars((string)($line['ppomontoppto'] ?? '')) ?>" <?= $hasMovimientos ? 'readonly' : '' ?>>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm line-observation"
                                           name="mensual[<?= (int)$index ?>][ppoobservacion]"
                                           maxlength="500"
                                           value="<?= htmlspecialchars((string)($line['ppoobservacion'] ?? '')) ?>" <?= $hasMovimientos ? 'readonly' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <?php if (!$hasMovimientos): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-month-line" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="form-actions">
            <?php if (!$hasMovimientos): ?>
                <button type="submit" class="btn btn-primary">Guardar</button>
            <?php endif; ?>
            <a href="?route=pptocompra/listar" class="btn btn-secondary">Volver</a>
            <a href="?route=pptocompra/ajustar&pptocompraid=<?= urlencode((string)($formData['pptocompraid'] ?? ($pptocompra['pptocompraid'] ?? ''))) ?>" class="btn btn-outline-secondary">Ajustes</a>
        </div>
    </form>
</div>

<?php if (!$hasMovimientos): ?>
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
<?php endif; ?>

<?php if (!$isPartial) { require 'footer.php'; } ?>

<?php if (!$hasMovimientos): ?>
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
<?php endif; ?>
