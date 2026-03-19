<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Crear Categoría de Animal</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=invcateganimal/crear">
        <!-- Grid de 4 columnas que se adapta automáticamente -->
        <div class="form-grid-4">
            <!-- Campo Descripción - ocupa más espacio -->
            <div class="form-field form-field-half">
                <label class="form-label">Descripción</label>
                <input type="text" name="invcateganimaldsc" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">ERP Categoría Código</label>
                <input type="text" name="erpinvcateganimalcod" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">Kilos x Cab</label>
                <input type="number" step="0.01" name="invcateganimalkilosxcab" class="form-control" required>
            </div>
            <div class="form-field">
                <label class="form-label">Activo</label>
                <select class="form-select" id="invcateganimalactivo" name="invcateganimalactivo">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=invcateganimal/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
