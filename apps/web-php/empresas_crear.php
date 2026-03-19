<?php
// empresas_crear.php
// Variables: $errorMessage (opcional), $partial (opcional)
$isPartial = $partial ?? false;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Crear Empresa</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form method="POST" action="?route=empresas/crear" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Código</label>
            <input type="text" name="empresacod" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">RUT</label>
            <input type="text" name="empresarut" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Razón Social</label>
            <input type="text" name="razonsocial" class="form-control" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Giro</label>
            <input type="text" name="giro" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Email Empresa</label>
            <input type="email" name="empresaemail" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">ID ERP</label>
            <input type="text" name="empresaiderp" class="form-control" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Nombre Contacto</label>
            <input type="text" name="contactonombre" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Email Contacto</label>
            <input type="email" name="contactoemail" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Celular Contacto</label>
            <input type="text" name="contactocelular" class="form-control" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">API Key Hash</label>
            <input type="text" name="empapikeyhash" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">API Key Fecha Gen</label>
            <input type="datetime-local" name="empapikeyfechagen" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">API Key Último Uso</label>
            <input type="datetime-local" name="empapikeyultuso" class="form-control">
        </div>

        <div class="col-md-4">
            <label class="form-label">API Key IP Último Uso</label>
            <input type="text" name="empapikeyipultuso" class="form-control">
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="empapikeyactiva" value="1" id="empapikeyactiva" checked>
                <label class="form-check-label" for="empapikeyactiva">API Key Activa</label>
            </div>
        </div>
        <div class="col-md-4 d-flex align-items-center">
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="empresaactivo" value="1" id="empresaactivo" checked>
                <label class="form-check-label" for="empresaactivo">Empresa Activa</label>
            </div>
        </div>

        <div class="col-12 d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar
            </button>
            <a href="?route=empresas/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
