<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Crear Cliente</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=clientes/crear" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">RUT</label>
            <input type="text" name="clienterut" class="form-control" required maxlength="12" placeholder="XXXXXXXX-X">
        </div>
        <div class="col-md-8">
            <label class="form-label">Razón Social</label>
            <input type="text" name="clienterazonsocial" class="form-control" required maxlength="100">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="clienteemail" class="form-control" maxlength="100">
        </div>
        <div class="col-md-6">
            <label class="form-label">Contacto</label>
            <input type="text" name="clientecontacto" class="form-control" maxlength="100">
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="clienteactivo" name="clienteactivo" checked>
            <label class="form-check-label" for="clienteactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar</button>
            <a href="?route=clientes/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
