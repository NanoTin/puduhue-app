<?php
// Variables: $invbodega, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="mb-3">Editar Bodega</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=invbodegas/editar" class="row g-3">
        <input type="hidden" name="invbodegaid" value="<?= htmlspecialchars($invbodega['invbodegaid'] ?? '') ?>">
        <!-- Grid de 4 columnas que se adapta automaticamente -->
        <div class="form-grid-4">
            <div class="form-field form-field-half">
                <label for="invbodegadsc" class="form-label">Descripción</label>
                <input type="text" name="invbodegadsc" class="form-control" required maxlength="50"
                    value="<?= htmlspecialchars($invbodega['invbodegadsc'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label for="erpinvbodegacod" class="form-label">ERP Bodega Código</label>
                <input type="text" name="erpinvbodegacod" class="form-control" required maxlength="50"
                    value="<?= htmlspecialchars($invbodega['erpinvbodegacod'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label for="fundoid" class="form-label">Fundo</label>
                <select name="fundoid" class="form-select" required>
                    <option value="">-- Seleccione un fundo --</option>
                    <?php foreach ($fundosOptions as $fundo): ?>
                        <option value="<?= htmlspecialchars($fundo['fundoid']) ?>"
                            <?= (isset($invbodega['fundoid']) && $invbodega['fundoid'] == $fundo['fundoid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fundo['fundonombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="invbodactivo" class="form-label">Activo</label>
                <select class="form-select" id="invbodactivo" name="invbodactivo">
                    <option value="1" <?= (isset($invbodega['invbodactivo']) && $invbodega['invbodactivo'] == 1) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= (isset($invbodega['invbodactivo']) && $invbodega['invbodactivo'] == 0) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>
        <!-- Botones de accion -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Modificar</button>
            <a href="?route=invbodegas/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
