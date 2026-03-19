<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Crear Tipo de Leche</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=prodlechetipos/crear">
        <!-- Grid de 4 columnas que se adapta automáticamente -->
        <div class="form-grid-4">
            <!-- Campo Descripción - ocupa más espacio -->
            <div class="form-field form-field-half">
                <label class="form-label">Descripción</label>
                <input type="text" name="prodlechetipodsc" class="form-control" required maxlength="50">
            </div>
            <!-- Campo Ítem -->
            <div class="form-field">
                <label class="form-label">Ítem</label>
                <select name="invitemid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($invitemsOptions ?? []) as $invitemOpt): ?>
                        <option value="<?= htmlspecialchars($invitemOpt['invitemid']) ?>">
                            <?= htmlspecialchars($invitemOpt['invitemdsc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Campo Para venta -->
            <div class="form-field">
                <label class="form-label" for="prodlecheventa">Para venta</label>
                <select class="form-select" id="prodlecheventa" name="prodlecheventa">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
            <!-- Campo Orden -->
            <div class="form-field">
                <label class="form-label">Orden</label>
                <input type="number" name="prodlecheorden" class="form-control" required min="1" max="99">
            </div>
            <!-- Campo Activo -->
            <div class="form-field">
                <label class="form-label" for="prodlecheactivo">Activo</label>
                <select class="form-select" id="prodlecheactivo" name="prodlecheactivo">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=prodlechetipos/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
