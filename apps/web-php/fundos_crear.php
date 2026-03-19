<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Crear Fundo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=fundos/crear">
        <!-- Grid de 4 columnas que se adapta automáticamente -->
        <div class="form-grid-4">
            <div class="form-field form-field-half">
                <label class="form-label">Nombre</label>
                <input type="text" name="fundonombre" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">Tipo Fundo</label>
                <select name="fundotipoid" class="form-select" required>
                    <option value="" disabled selected>Seleccione un tipo</option>
                    <?php foreach ($fundostiposOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option['fundotipoid']) ?>">
                            <?= htmlspecialchars($option['fundotipodsc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Empresa</label>
                <select name="empresaid" class="form-select" required>
                    <option value="" disabled selected>Seleccione una empresa</option>
                    <?php foreach ($empresasOptions as $option): ?>
                        <option value="<?= htmlspecialchars($option['empresaid']) ?>">
                            <?= htmlspecialchars($option['razonsocial']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label">ERP Establecimiento</label>
                <input type="text" name="erpestablecimientocod" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">ERP Lote</label>
                <input type="text" name="erplotecod" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">ERP Bodega Leche</label>
                <input type="text" name="erpleche_invbodegacod" class="form-control" required maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">ERP Categoría Animal</label>
                <input type="text" name="erpleche_invcateganimalcod" class="form-control" required maxlength="50">
            </div>

            <div class="form-field">
                <label class="form-check-label" for="fundopabco">PABCO</label>
                <select class="form-select" id="fundopabco" name="fundopabco">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="form-field">
                <label class="form-label">RUP</label>
                <input type="text" name="fundorup" class="form-control" maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">Región</label>
                <input type="text" name="fundoregion" class="form-control" maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">Provincia</label>
                <input type="text" name="fundoprovincia" class="form-control" maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">Comuna</label>
                <input type="text" name="fundocomuna" class="form-control" maxlength="50">
            </div>
            <div class="form-field">
                <label class="form-label">Dirección</label>
                <input type="text" name="fundodireccion" class="form-control" maxlength="100">
            </div>
            <div class="form-field">
                <label class="form-label">Latitud</label>
                <input type="number" step="0.000001" name="fundolatitud" class="form-control">
            </div>
            <div class="form-field">
                <label class="form-label">Longitud</label>
                <input type="number" step="0.000001" name="fundolongitud" class="form-control">
            </div>
            <div class="form-field">
                <label class="form-label">Email</label>
                <input type="email" name="fundoemail" class="form-control" maxlength="100">
            </div>
            <div class="form-field">
                <label class="form-label">Reporte Orden</label>
                <input type="number" name="reporteorden" class="form-control" value="0" min="0">
            </div>
            <div class="form-field">
                <label class="form-check-label" for="fundoactivo">Activo</label>
                <select class="form-select" id="fundoactivo" name="fundoactivo">
                    <option value="1" selected>Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=fundos/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
