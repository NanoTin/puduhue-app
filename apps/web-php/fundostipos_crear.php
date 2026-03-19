<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Crear Tipo de Fundo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=fundostipos/crear" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Descripción</label>
            <input type="text" name="fundotipodsc" class="form-control" required maxlength="50">
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Guardar</button>
            <a href="?route=fundostipos/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
