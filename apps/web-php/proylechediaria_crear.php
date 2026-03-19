<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Crear Proyeccion de Leche - Consolidado</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
        action="?route=proylechediaria/crear" autocomplete="off"
        data-confirm="1"
        data-confirm-message="Desea confirmar los datos ingresados?">
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label">Fecha</label>
                <input type="date" name="proylechefecha" class="form-control" required
                       value="<?= htmlspecialchars($formData['proylechefecha'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Litros</label>
                <input type="number" name="proylecheventatotlitros" class="form-control" required min="0" step="1"
                       value="<?= htmlspecialchars($formData['proylecheventatotlitros'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Vacas</label>
                <input type="number" name="proylecheventatotvacas" class="form-control" required min="0" step="1"
                       value="<?= htmlspecialchars($formData['proylecheventatotvacas'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Lts x Vaca</label>
                <input type="number" name="proylecheventatotltsxvaca" class="form-control" min="0" step="0.01"
                       value="<?= htmlspecialchars($formData['proylecheventatotltsxvaca'] ?? '') ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=proylechediaria/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const litros = document.querySelector('input[name="proylecheventatotlitros"]');
        const vacas = document.querySelector('input[name="proylecheventatotvacas"]');
        const ltsxvc = document.querySelector('input[name="proylecheventatotltsxvaca"]');

        const calcLtsxvc = () => {
            const l = parseFloat(litros?.value || '0');
            const v = parseFloat(vacas?.value || '0');
            if (ltsxvc && v > 0) {
                ltsxvc.value = (l / v).toFixed(2);
            }
        };

        if (litros && vacas && ltsxvc) {
            litros.addEventListener('input', calcLtsxvc);
            vacas.addEventListener('input', calcLtsxvc);
        }
    });
</script>
