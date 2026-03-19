<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Crear Unidad de Medida</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=invunidmed/crear" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Descripcion</label>
            <input type="text" name="invunidmeddsc" class="form-control" required maxlength="50">
        </div>
        <div class="col-md-6">
            <label class="form-label">Codigo ERP</label>
            <input type="text" name="erpunidmedcod" class="form-control" maxlength="50">
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="invunidmedactivo" name="invunidmedactivo" checked>
            <label class="form-check-label" for="invunidmedactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar</button>
            <a href="?route=invunidmed/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
