<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Crear Asignacion Perfil - Menu</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=perfilesmenus/crear" class="row g-3">
        <div class="col-md-6">
            <select name="perfilid" class="form-select">
                <option value="">Perfil</option>
                <?php foreach (($perfilesOptions ?? []) as $perfilOpt): ?>
                    <option value="<?= htmlspecialchars($perfilOpt['perfilid']) ?>"
                        <?= ($filtros['perfilid'] ?? '') == ($perfilOpt['perfilid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($perfilOpt['perfildesc'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="menuid">Menu ID</label>
            <input type="number" id="menuid" name="menuid" class="form-control" required min="1">
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="perfilmenuactivo" name="perfilmenuactivo" checked>
            <label class="form-check-label" for="perfilmenuactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar</button>
            <a href="?route=perfilesmenus/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
