<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Crear Perfil</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=perfiles/crear" class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="perfildesc">Descripcion</label>
            <input type="text" id="perfildesc" name="perfildesc" class="form-control" required maxlength="100">
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="perfilesroot" name="perfilesroot" readonly>
            <label class="form-check-label" for="perfilesroot">ROOT</label>
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="perfilesadmin" name="perfilesadmin" readonly>
            <label class="form-check-label" for="perfilesadmin">Administrador</label>
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="perfilactivo" name="perfilactivo" checked readonly>
            <label class="form-check-label" for="perfilactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar</button>
            <a href="?route=perfiles/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
