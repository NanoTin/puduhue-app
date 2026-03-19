<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Asociar Usuario a Empresa</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=usuariosempresas/crear" data-confirm-message="¿Desea confirmar los datos ingresados?">
        <!-- Grid de 4 columnas que se adapta automaticamente -->
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label" for="usuarioid">Usuario</label>
                <select class="form-select" id="usuarioid" name="usuarioid"  required>
                    <option value="">Seleccionar usuario</option>
                    <?php foreach ($usuariosOptions as $usuario): ?>
                        <option value="<?= $usuario['usuarioid'] ?>"><?= $usuario['usuarionombre'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="empresaid">Empresa</label>
                <select class="form-select" id="empresaid" name="empresaid" required>
                    <option value="">Seleccionar empresa</option>
                    <?php foreach ($empresasOptions as $empresa): ?>
                        <option value="<?= $empresa['empresaid'] ?>"><?= $empresa['razonsocial'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="uedefault">Empresa Predeterminada</label>
                <select class="form-select" id="uedefault" name="uedefault" required>
                    <option value="0">No</option>
                    <option value="1">Sí</option>
                </select>
            </div>
        </div>
        <!-- Botones de accion -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=usuariosempresas/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
