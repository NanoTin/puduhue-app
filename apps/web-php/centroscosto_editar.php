<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Editar Centro de Costo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="alert alert-info" role="alert">
        Los campos sincronizados desde ERP son de solo lectura. Solo se pueden editar el jefe de centro y el jefe técnico.
    </div>

    <form method="POST"
          action="?route=centroscosto/editar"
          autocomplete="off"
          data-confirm="1"
          data-confirm-message="¿Desea confirmar los datos ingresados?">
        <input type="hidden" name="centrocostoid" value="<?= htmlspecialchars($centrocosto['centrocostoid'] ?? '') ?>">

        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label">Código</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($centrocosto['centrocostocod'] ?? '') ?>" readonly>
            </div>
            <div class="form-field form-field-half">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($centrocosto['centrocostodsc'] ?? '') ?>" readonly>
            </div>
            <div class="form-field">
                <label class="form-label">Código ERP</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($centrocosto['erpcentrocostocod'] ?? '') ?>" readonly>
            </div>
            <div class="form-field form-field-half">
                <label class="form-label">Descripción ERP</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($centrocosto['centrocostodescripcion'] ?? '') ?>" readonly>
            </div>
            <div class="form-field">
                <label class="form-label">Jefe de Centro</label>
                <select name="centrocostojefeusuarioid" class="form-select">
                    <option value="">Sin asignar</option>
                    <?php foreach (($aprobadoresOptions ?? []) as $usuarioOpt): ?>
                        <option value="<?= htmlspecialchars($usuarioOpt['usuarioid']) ?>"
                            <?= (string)($centrocosto['centrocostojefeusuarioid'] ?? '') === (string)($usuarioOpt['usuarioid'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($usuarioOpt['usuarionombre'] ?? '') . ' (' . ($usuarioOpt['usuariorut'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Jefe Técnico</label>
                <select name="centrocostojefetecnicoid" class="form-select">
                    <option value="">Sin asignar</option>
                    <?php foreach (($aprobadoresOptions ?? []) as $usuarioOpt): ?>
                        <option value="<?= htmlspecialchars($usuarioOpt['usuarioid']) ?>"
                            <?= (string)($centrocosto['centrocostojefetecnicoid'] ?? '') === (string)($usuarioOpt['usuarioid'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($usuarioOpt['usuarionombre'] ?? '') . ' (' . ($usuarioOpt['usuariorut'] ?? '') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Activo ERP</label>
                <input type="text" class="form-control" value="<?= !empty($centrocosto['centrocostoactivo']) ? 'Sí' : 'No' ?>" readonly>
            </div>
            <div class="form-field">
                <label class="form-label">Última Sync ERP</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($centrocosto['sincfechahora'] ?? '') ?>" readonly>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="?route=centroscosto/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
