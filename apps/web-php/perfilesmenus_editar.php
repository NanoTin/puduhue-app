<?php
// Variables: $perfilmenu, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Editar Asignacion Perfil - Menu</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=perfilesmenus/editar" class="row g-3">
        <input type="hidden" name="perfilid" value="<?= htmlspecialchars($perfilmenu['perfilid'] ?? '') ?>">
        <input type="hidden" name="menuid" value="<?= htmlspecialchars($perfilmenu['menuid'] ?? '') ?>">

        <div class="col-md-6">
            <label class="form-label">Perfil</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($perfilmenu['perfiles_perfildesc'] ?? $perfilmenu['perfilid'] ?? '') ?>" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label">Menu</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($perfilmenu['menus_menudesc'] ?? $perfilmenu['menuid'] ?? '') ?>" disabled>
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="perfilmenuactivo" name="perfilmenuactivo"
                <?= !empty($perfilmenu['perfilmenuactivo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="perfilmenuactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="?route=perfilesmenus/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
