<?php
// Variables: $fundosestanquescliente, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Editar Asociacion Estanque - Cliente</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=fundosestanquesclientes/editar" class="row g-3">
        <input type="hidden" name="fundoestanqueid" value="<?= htmlspecialchars($fundosestanquescliente['fundoestanqueid'] ?? '') ?>">
        <input type="hidden" name="clienteid" value="<?= htmlspecialchars($fundosestanquescliente['clienteid'] ?? '') ?>">
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label">Estanque</label>
                <select name="fundoestanqueid" class="form-select" required disabled>
                    <option value="">Seleccione un estanque</option>
                    <?php foreach (($fundosestanquesOptions ?? []) as $option): ?>
                        <option value="<?= htmlspecialchars($option['fundoestanqueid']) ?>"
                            <?= (isset($fundosestanquescliente['fundoestanqueid']) && $fundosestanquescliente['fundoestanqueid'] == $option['fundoestanqueid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option['fundoestanquedsc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Cliente</label>
                <select name="clienteid" class="form-select" required disabled>
                    <option value="">Seleccione un cliente</option>
                    <?php foreach (($clientesOptions ?? []) as $option): ?>
                        <option value="<?= htmlspecialchars($option['clienteid']) ?>"
                            <?= (isset($fundosestanquescliente['clienteid']) && $fundosestanquescliente['clienteid'] == $option['clienteid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option['clienterazonsocial']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Codigo Cliente</label>
                <input type="number" name="estanqueclientecod" class="form-control" readonly disabled
                    value="<?= htmlspecialchars($fundosestanquescliente['estanqueclientecod'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label" for="fndestcliactivo">Activo</label>
                <select name="fndestcliactivo" id="fndestcliactivo" class="form-select">
                    <option value="1" <?= (isset($fundosestanquescliente['fndestcliactivo']) && $fundosestanquescliente['fndestcliactivo'] == 1) ? 'selected' : '' ?>>Si</option>
                    <option value="0" <?= (isset($fundosestanquescliente['fndestcliactivo']) && $fundosestanquescliente['fndestcliactivo'] == 0) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=fundosestanquesclientes/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
