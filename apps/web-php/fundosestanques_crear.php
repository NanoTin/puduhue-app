<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Crear Estanque</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=fundosestanques/crear" class="row g-3">
        <!-- Grid de 4 columnas que se adapta automaticamente -->
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label">Fundo</label>
                <select name="fundoid" class="form-select" required>
                    <option value="">Seleccione un fundo</option>
                    <?php foreach ($fundosOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option['fundoid']) ?>"><?= htmlspecialchars($option['fundonombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field form-field-half">
                <label class="form-label">Descripción</label>
                <input type="text" name="fundoestanquedsc" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">Marca ID</label>
                <input type="number" name="estanquemarcaid" class="form-control">
            </div>
            <div class="form-field">
                <label class="form-label">Orden</label>
                <input type="number" name="fundoestanqueorden" class="form-control" required>
            </div>
            <div class="form-field">
                <label class="form-label" for="fundoestanqueactivo">Activo</label>
                <select name="fundoestanqueactivo" id="fundoestanqueactivo" class="form-select">
                    <option value="1" selected>Si</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <!-- Botones de accion -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=fundosestanques/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
