<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Asignar Usuario a Centro de Costo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="?route=usuarios-centros-costo/crear"
          autocomplete="off"
          data-confirm="1"
          data-confirm-message="¿Desea confirmar los datos ingresados?">
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label" for="usuarioid">Usuario</label>
                <select name="usuarioid" id="usuarioid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($usuariosOptions ?? []) as $usuarioOpt): ?>
                        <option value="<?= htmlspecialchars($usuarioOpt['usuarioid']) ?>"
                            <?= (string)($formData['usuarioid'] ?? '') === (string)($usuarioOpt['usuarioid'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($usuarioOpt['usuarionombre'] ?? '') . ' (' . ($usuarioOpt['usuariorut'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label" for="centrocostoid">Centro de costo</label>
                <select name="centrocostoid" id="centrocostoid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($centrosOptions ?? []) as $centroOpt): ?>
                        <option value="<?= htmlspecialchars($centroOpt['centrocostoid']) ?>"
                            <?= (string)($formData['centrocostoid'] ?? '') === (string)($centroOpt['centrocostoid'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($centroOpt['centrocostodsc'] ?? '') . ' (' . ($centroOpt['centrocostocod'] ?? '') . ')' ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label" for="usucendefault">Centro default</label>
                <select class="form-select" id="usucendefault" name="usucendefault">
                    <option value="0" <?= (string)($formData['usucendefault'] ?? '0') === '0' ? 'selected' : '' ?>>No</option>
                    <option value="1" <?= (string)($formData['usucendefault'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=usuarios-centros-costo/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
