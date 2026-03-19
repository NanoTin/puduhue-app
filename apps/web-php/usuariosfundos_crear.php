<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Asociar Usuario a Fundo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" 
        action="?route=usuariosfundos/crear" autocomplete="off"
        data-confirm="1"
        data-confirm-message="¿Desea confirmar los datos ingresados?">
        <!-- Grid de 4 columnas que se adapta automáticamente -->
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label">Usuario</label>
                <select name="usuarioid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($usuariosOptions ?? []) as $usuarioOpt): ?>
                        <option value="<?= htmlspecialchars($usuarioOpt['usuarioid']) ?>">
                            <?= htmlspecialchars($usuarioOpt['usuarionombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Fundo</label>
                <select name="fundoid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($fundosOptions ?? []) as $fundoOpt): ?>
                        <option value="<?= htmlspecialchars($fundoOpt['fundoid']) ?>">
                            <?= htmlspecialchars($fundoOpt['fundonombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="ufdefault">Default</label>
                <select class="form-select" id="ufdefault" name="ufdefault">
                    <option value="1">Sí</option>
                    <option value="0" selected>No</option>
                </select>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=usuariosfundos/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>
<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
