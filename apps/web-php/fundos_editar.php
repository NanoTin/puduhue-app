<?php
// Variables: $fundo, $errorMessage, $partial
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Editar Fundo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=fundos/editar" class="row g-3">
        <input type="hidden" name="fundoid" value="<?= htmlspecialchars($fundo['fundoid'] ?? '') ?>">
        <!-- Grid de 4 columnas que se adapta automáticamente -->
        <div class="form-grid-4">
            <div class="form-field form-field-half">
                <label class="form-label">Nombre</label>
                <input type="text" name="fundonombre" class="form-control" required maxlength="50"
                    value="<?= htmlspecialchars($fundo['fundonombre'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Tipo Fundo</label>
                <select name="fundotipoid" class="form-select" required>
                    <option value="" disabled>Seleccione un tipo</option>
                    <?php foreach ($fundostiposOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option['fundotipoid']) ?>"
                            <?= (isset($fundo['fundotipoid']) && $fundo['fundotipoid'] == $option['fundotipoid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option['fundotipodsc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Empresa</label>
                <select name="empresaid" class="form-select" required>
                    <option value="" disabled>Seleccione una empresa</option>
                    <?php foreach ($empresasOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option['empresaid']) ?>"
                            <?= (isset($fundo['empresaid']) && $fundo['empresaid'] == $option['empresaid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option['razonsocial']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label">ERP Establecimiento</label>
                <input type="text" name="erpestablecimientocod" class="form-control" required maxlength="50"
                    value="<?= htmlspecialchars($fundo['erpestablecimientocod'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">ERP Lote</label>
                <input type="text" name="erplotecod" class="form-control" required maxlength="50"
                    value="<?= htmlspecialchars($fundo['erplotecod'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">ERP Bodega Leche</label>
                <input type="text" name="erpleche_invbodegacod" class="form-control" required maxlength="50"
                    value="<?= htmlspecialchars($fundo['erpleche_invbodegacod'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">ERP Categoría Animal</label>
                <input type="text" name="erpleche_invcateganimalcod" class="form-control" required maxlength="50"
                    value="<?= htmlspecialchars($fundo['erpleche_invcateganimalcod'] ?? '') ?>">
            </div>

            <div class="form-field">
                <label class="form-check-label" for="fundopabco">PABCO</label>
                <select class="form-select" id="fundopabco" name="fundopabco">
                    <option value="1" <?= !empty($fundo['fundopabco']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($fundo['fundopabco']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label">RUP</label>
                <input type="text" name="fundorup" class="form-control" maxlength="50"
                    value="<?= htmlspecialchars($fundo['fundorup'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Región</label>
                <input type="text" name="fundoregion" class="form-control" maxlength="50"
                    value="<?= htmlspecialchars($fundo['fundoregion'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Provincia</label>
                <input type="text" name="fundoprovincia" class="form-control" maxlength="50"
                    value="<?= htmlspecialchars($fundo['fundoprovincia'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Comuna</label>
                <input type="text" name="fundocomuna" class="form-control" maxlength="50"
                    value="<?= htmlspecialchars($fundo['fundocomuna'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Dirección</label>
                <input type="text" name="fundodireccion" class="form-control" maxlength="100"
                    value="<?= htmlspecialchars($fundo['fundodireccion'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Latitud</label>
                <input type="number" step="0.000001" name="fundolatitud" class="form-control"
                    value="<?= htmlspecialchars($fundo['fundolatitud'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Longitud</label>
                <input type="number" step="0.000001" name="fundolongitud" class="form-control"
                    value="<?= htmlspecialchars($fundo['fundolongitud'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Email</label>
                <input type="email" name="fundoemail" class="form-control" maxlength="100"
                    value="<?= htmlspecialchars($fundo['fundoemail'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Reporte Orden</label>
                <input type="number" name="reporteorden" class="form-control" min="0"
                    value="<?= htmlspecialchars($fundo['reporteorden'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-check-label" for="fundoactivo">Activo</label>
                <select class="form-select" id="fundoactivo" name="fundoactivo">
                    <option value="1" <?= !empty($fundo['fundoactivo']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($fundo['fundoactivo']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>
        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Modificar</button>
            <a href="?route=fundos/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
