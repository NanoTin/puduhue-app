<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Crear Ítem</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=invitems/crear" class="row g-3">
        <div class="col-4">
            <label class="form-label">Descripción</label>
            <input type="text" name="invitemdsc" class="form-control" required maxlength="50">
        </div>
        <div class="col-4">
            <label class="form-label">Unidad Medida ID</label>
            <select name="invunidmedid" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach (($invunidmedOptions ?? []) as $unidmedOpt): ?>
                    <option value="<?= htmlspecialchars($unidmedOpt['invunidmedid']) ?>">
                        <?= htmlspecialchars($unidmedOpt['invunidmeddsc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label">ERP Ítem Código</label>
            <input type="text" name="erpinvitemcod" class="form-control" required maxlength="50">
        </div>
        <div class="col-4">
            <label class="form-label" for="invitemleche">Para módulo Leche</label>
            <select class="form-select" id="invitemleche" name="invitemleche">
                <option value="1">Sí</option>
                <option value="0" selected>No</option>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label" for="invitemstockeable">Stockeable</label>
            <select class="form-select" id="invitemstockeable" name="invitemstockeable">
                <option value="1" selected>Sí</option>
                <option value="0">No</option>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label" for="invitemactivo">Activo</label>
            <select class="form-select" id="invitemactivo" name="invitemactivo">
                <option value="1" selected>Sí</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="col-4 d-flex gap-2">
            <button class="btn btn-primary">Guardar</button>
            <a href="?route=invitems/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
