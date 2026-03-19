<?php
// Variables: $invunidmed, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Editar Unidad de Medida</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=invunidmed/editar" class="row g-3">
        <input type="hidden" name="invunidmedid" value="<?= htmlspecialchars($invunidmed['invunidmedid'] ?? '') ?>">

        <div class="col-md-6">
            <label class="form-label">Descripcion</label>
            <input type="text" name="invunidmeddsc" class="form-control" required maxlength="50"
                   value="<?= htmlspecialchars($invunidmed['invunidmeddsc'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Codigo ERP</label>
            <input type="text" name="erpunidmedcod" class="form-control" maxlength="50"
                   value="<?= htmlspecialchars($invunidmed['erpunidmedcod'] ?? '') ?>">
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="invunidmedactivo" name="invunidmedactivo"
                <?= !empty($invunidmed['invunidmedactivo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="invunidmedactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="?route=invunidmed/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
