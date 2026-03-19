<?php
// Variables: $formData, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Editar Presupuesto Leche Mensual</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
        action="?route=pptolechemensual/editar" autocomplete="off"
        data-confirm="1"
        data-confirm-message="¿Desea confirmar los cambios?">
        <input type="hidden" name="pptolecanio" value="<?= htmlspecialchars($formData['pptolecanio'] ?? '') ?>">
        <input type="hidden" name="pptolecmes" value="<?= htmlspecialchars($formData['pptolecmes'] ?? '') ?>">
        <input type="hidden" name="fundoid" value="<?= htmlspecialchars($formData['fundoid'] ?? '') ?>">

        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label">Año</label>
                <input type="number" class="form-control" value="<?= htmlspecialchars($formData['pptolecanio'] ?? '') ?>" readonly>
            </div>
            <div class="form-field">
                <label class="form-label">Mes</label>
                <input type="number" class="form-control" value="<?= htmlspecialchars($formData['pptolecmes'] ?? '') ?>" readonly>
            </div>
            <div class="form-field">
                <label class="form-label">Fundo</label>
                <select class="form-select" disabled>
                    <?php foreach (($fundosOptions ?? []) as $fundoOpt): ?>
                        <option value="<?= htmlspecialchars($fundoOpt['fundoid']) ?>"
                            <?= ($formData['fundoid'] ?? '') == ($fundoOpt['fundoid'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fundoOpt['fundonombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Fecha inicio</label>
                <input type="date" name="pptolecfecha" class="form-control"
                       value="<?= htmlspecialchars($formData['pptolecfecha'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Días del mes</label>
                <input type="number" name="pptolecdiasdelmes" class="form-control" min="1" max="31"
                       value="<?= htmlspecialchars($formData['pptolecdiasdelmes'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Litros</label>
                <input type="number" name="pptoleclitros" class="form-control" required min="0" step="1"
                       value="<?= htmlspecialchars($formData['pptoleclitros'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Vacas</label>
                <input type="number" name="pptolecvacas" class="form-control" required min="0" step="1"
                       value="<?= htmlspecialchars($formData['pptolecvacas'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Lts x Vaca</label>
                <input type="number" name="pptolecltsxvc" class="form-control" min="0" step="0.01"
                       value="<?= htmlspecialchars($formData['pptolecltsxvc'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="?route=pptolechemensual/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const litros = document.querySelector('input[name="pptoleclitros"]');
        const vacas = document.querySelector('input[name="pptolecvacas"]');
        const ltsxvc = document.querySelector('input[name="pptolecltsxvc"]');
        const dias = document.querySelector('input[name="pptolecdiasdelmes"]');
        const anioVal = document.querySelector('input[name="pptolecanio"]')?.value || '';
        const mesVal = document.querySelector('input[name="pptolecmes"]')?.value || '';

        const calcLtsxvc = () => {
            const l = parseFloat(litros?.value || '0');
            const v = parseFloat(vacas?.value || '0');
            if (ltsxvc && v > 0) {
                ltsxvc.value = (l / v).toFixed(2);
            }
        };

        const calcDiasMes = () => {
            const y = parseInt(anioVal || '0', 10);
            const m = parseInt(mesVal || '0', 10);
            if (dias && y > 0 && m > 0 && !dias.value) {
                const lastDay = new Date(y, m, 0).getDate();
                dias.value = lastDay;
            }
        };

        if (litros && vacas && ltsxvc) {
            litros.addEventListener('input', calcLtsxvc);
            vacas.addEventListener('input', calcLtsxvc);
        }

        calcDiasMes();
    });
</script>
