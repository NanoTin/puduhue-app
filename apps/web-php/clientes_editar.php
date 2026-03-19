<?php
// Variables: $cliente, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Editar Cliente</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=clientes/editar" class="row g-3">
        <input type="hidden" name="clienteid" value="<?= htmlspecialchars($cliente['clienteid'] ?? '') ?>">

        <div class="col-md-4">
            <label class="form-label">RUT</label>
            <input type="text" name="clienterut" class="form-control" required maxlength="12"
                   value="<?= htmlspecialchars($cliente['clienterut'] ?? '') ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label">Razón Social</label>
            <input type="text" name="clienterazonsocial" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($cliente['clienterazonsocial'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="clienteemail" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($cliente['clienteemail'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Contacto</label>
            <input type="text" name="clientecontacto" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($cliente['clientecontacto'] ?? '') ?>">
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="clienteactivo" name="clienteactivo"
                <?= !empty($cliente['clienteactivo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="clienteactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="?route=clientes/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
