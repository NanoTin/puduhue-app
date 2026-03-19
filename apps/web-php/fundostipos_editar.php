<?php
// Variables: $fundotipo, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Editar Tipo de Fundo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=fundostipos/editar" class="row g-3">
        <input type="hidden" name="fundotipoid" value="<?= htmlspecialchars($fundotipo['fundotipoid'] ?? '') ?>">

        <div class="col-md-6">
            <label class="form-label">Descripción</label>
            <input type="text" name="fundotipodsc" class="form-control" required maxlength="50"
                   value="<?= htmlspecialchars($fundotipo['fundotipodsc'] ?? '') ?>">
        </div>
        <div class="col-12">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="?route=fundostipos/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
