<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Crear Bodega</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=invbodegas/crear" class="row g-3">
        <!-- Grid de 4 columnas que se adapta automáticamente -->
        <div class="form-grid-4">
            <div class="form-field form-field-half">
                <label class="form-label" for="invbodegadsc">Descripción</label>
                <input type="text" id="invbodegadsc" name="invbodegadsc" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label" for="erpinvbodegacod">ERP Bodega Código</label>
                <input type="text" id="erpinvbodegacod" name="erpinvbodegacod" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label" for="fundoid">Fundo</label>
                <select class="form-select" id="fundoid" name="fundoid" required>
                    <option value="">-- Seleccione un fundo --</option>
                    <?php foreach ($fundosOptions as $fundo): ?>
                        <option value="<?= htmlspecialchars($fundo['fundoid']) ?>">
                            <?= htmlspecialchars($fundo['fundonombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="invbodactivo">Activo</label>
                <select class="form-select" id="invbodactivo" name="invbodactivo">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=invbodegas/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
