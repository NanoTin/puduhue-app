<?php
// Variables: $perfil, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="mb-3">Editar Perfil</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=perfiles/editar" class="row g-3">
        <input type="hidden" name="perfilid" value="<?= htmlspecialchars($perfil['perfilid'] ?? '') ?>">
        <!-- Grid de 4 columnas que se adapta automaticamente -->
        <div class="form-grid-4">
            <div class="form-field form-field-half">
                <label class="form-label">Descripción</label>
                <input type="text" name="perfildesc" class="form-control" required maxlength="100"
                    value="<?= htmlspecialchars($perfil['perfildesc'] ?? '') ?>">
            </div>
            <div class="form-field">
                <input type="hidden" name="perfilesroot" value="<?= !empty($perfil['perfilesroot']) ? 1 : 0 ?>">
                <label class="form-check-label" for="perfilesroot">ROOT</label>
                <select class="form-select" id="perfilesroot" name="perfilesroot" disabled>
                    <option value="1" <?= !empty($perfil['perfilesroot']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($perfil['perfilesroot']) ? 'selected' : '' ?>>No</option>
                </select>            
            </div>
            <div class="form-field">
                <input type="hidden" name="perfilesadmin" value="<?= !empty($perfil['perfilesadmin']) ? 1 : 0 ?>">
                <label class="form-check-label" for="perfilesadmin">Administrador de Sistema</label>
                <select class="form-select" id="perfilesadmin" name="perfilesadmin" disabled>
                    <option value="1" <?= !empty($perfil['perfilesadmin']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($perfil['perfilesadmin']) ? 'selected' : '' ?>>No</option>
                </select>            
            </div>
            <div class="form-field">
                <label class="form-check-label" for="perfilactivo">Activo</label>    
                <select class="form-select" id="perfilactivo" name="perfilactivo">
                    <option value="1" <?= !empty($perfil['perfilactivo']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($perfil['perfilactivo']) ? 'selected' : '' ?>>No</option>
                </select>            
            </div>
        </div>
        <!-- Botones de accion -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="?route=perfiles/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
