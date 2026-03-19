<?php
// Variables: $invitem, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Editar Ítem</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=invitems/editar" class="row g-3">
        <input type="hidden" name="invitemid" value="<?= htmlspecialchars($invitem['invitemid'] ?? '') ?>">

        <div class="col-4">
            <label class="form-label">Descripción</label>
            <input type="text" name="invitemdsc" class="form-control" required maxlength="50"
                   value="<?= htmlspecialchars($invitem['invitemdsc'] ?? '') ?>">
        </div>
        <div class="col-4">
            <label class="form-label">Unidad Medida ID</label>
            <select name="invunidmedid" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach (($invunidmedOptions ?? []) as $unidmedOpt): ?>
                    <option value="<?= htmlspecialchars($unidmedOpt['invunidmedid']) ?>"
                        <?= (isset($invitem['invunidmedid']) && $invitem['invunidmedid'] == $unidmedOpt['invunidmedid']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($unidmedOpt['invunidmeddsc']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label">ERP Ítem Código</label>
            <input type="text" name="erpinvitemcod" class="form-control" required maxlength="50"
                   value="<?= htmlspecialchars($invitem['erpinvitemcod'] ?? '') ?>">
        </div>
        <div class="col-4">
            <label class="form-label" for="invitemleche">Para módulo Leche</label>
            <select class="form-select" id="invitemleche" name="invitemleche">
                <option value="1" <?= (isset($invitem['invitemleche']) && $invitem['invitemleche'] == 1) ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= (isset($invitem['invitemleche']) && $invitem['invitemleche'] == 0) ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label" for="invitemstockeable">Stockeable</label>
            <select class="form-select" id="invitemstockeable" name="invitemstockeable">
                <option value="1" <?= (isset($invitem['invitemstockeable']) && $invitem['invitemstockeable'] == 1) ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= (isset($invitem['invitemstockeable']) && $invitem['invitemstockeable'] == 0) ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label" for="invitemactivo">Activo</label>
            <select class="form-select" id="invitemactivo" name="invitemactivo">
                <option value="1" <?= (isset($invitem['invitemactivo']) && $invitem['invitemactivo'] == 1) ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= (isset($invitem['invitemactivo']) && $invitem['invitemactivo'] == 0) ? 'selected' : '' ?>>No</option>
            </select>
        </div>

        <div class="col-4 d-flex gap-2">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="?route=invitems/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
