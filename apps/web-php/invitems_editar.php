<?php
// Variables: $invitem, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Editar Ítem</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST"
          action="?route=invitems/editar"
          autocomplete="off"
          data-confirm="1"
          data-confirm-message="¿Desea guardar los cambios?">
        <input type="hidden" name="invitemid" value="<?= htmlspecialchars($invitem['invitemid'] ?? '') ?>">

        <div class="form-grid-4">
            <div class="form-field form-field-half">
                <label class="form-label" for="invitemdsc">Descripción</label>
                <input type="text" name="invitemdsc" id="invitemdsc" class="form-control" required maxlength="50"
                       value="<?= htmlspecialchars($invitem['invitemdsc'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label" for="invunidmedid">Unidad Medida</label>
                <select name="invunidmedid" id="invunidmedid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($invunidmedOptions ?? []) as $unidmedOpt): ?>
                        <option value="<?= htmlspecialchars($unidmedOpt['invunidmedid']) ?>"
                            <?= (isset($invitem['invunidmedid']) && $invitem['invunidmedid'] == $unidmedOpt['invunidmedid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($unidmedOpt['invunidmeddsc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="erpinvitemcod">ERP Ítem Código</label>
                <input type="text" name="erpinvitemcod" id="erpinvitemcod" class="form-control" required maxlength="50"
                       value="<?= htmlspecialchars($invitem['erpinvitemcod'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label" for="invitemusocodigo">Uso funcional</label>
                <?php $usoSeleccionado = $invitem['invitemusocodigo'] ?? 'BDG'; ?>
                <select class="form-select" id="invitemusocodigo" name="invitemusocodigo">
                    <option value="BDG" <?= $usoSeleccionado === 'BDG' ? 'selected' : '' ?>>BDG - Bodega/Base</option>
                    <option value="LCH" <?= $usoSeleccionado === 'LCH' ? 'selected' : '' ?>>LCH - Leche</option>
                    <option value="ALM" <?= $usoSeleccionado === 'ALM' ? 'selected' : '' ?>>ALM - Suplementación Animal</option>
                    <option value="CMB" <?= $usoSeleccionado === 'CMB' ? 'selected' : '' ?>>CMB - Combustible</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="familiaid">Familia</label>
                <select name="familiaid" id="familiaid" class="form-select">
                    <option value="">Sin familia</option>
                    <?php foreach (($familiasOptions ?? []) as $familiaOpt): ?>
                        <option value="<?= htmlspecialchars($familiaOpt['familiaid']) ?>"
                            <?= (isset($invitem['familiaid']) && $invitem['familiaid'] == $familiaOpt['familiaid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($familiaOpt['familiacod'] ?? '') . ' - ' . ($familiaOpt['familiadsc'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="subfamiliaid">Subfamilia</label>
                <select name="subfamiliaid" id="subfamiliaid" class="form-select">
                    <option value="">Sin subfamilia</option>
                    <?php foreach (($subfamiliasOptions ?? []) as $subfamiliaOpt): ?>
                        <option value="<?= htmlspecialchars($subfamiliaOpt['subfamiliaid']) ?>"
                            <?= (isset($invitem['subfamiliaid']) && $invitem['subfamiliaid'] == $subfamiliaOpt['subfamiliaid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($subfamiliaOpt['subfamiliacod'] ?? '') . ' - ' . ($subfamiliaOpt['subfamiliadsc'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="erptasaimpositivaid">Tasa compra</label>
                <select name="erptasaimpositivaid" id="erptasaimpositivaid" class="form-select">
                    <option value="">Sin tasa</option>
                    <?php foreach (($tasasImpositivasOptions ?? []) as $tasaOpt): ?>
                        <option value="<?= htmlspecialchars($tasaOpt['erptasaimpositivaid']) ?>"
                            <?= (isset($invitem['erptasaimpositivaid']) && $invitem['erptasaimpositivaid'] == $tasaOpt['erptasaimpositivaid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($tasaOpt['erptasaimpositivacod'] ?? '') . ' - ' . ($tasaOpt['erptasaimpositivadsc'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="erppartidafinancieraid">Partida financiera</label>
                <select name="erppartidafinancieraid" id="erppartidafinancieraid" class="form-select">
                    <option value="">Sin partida</option>
                    <?php foreach (($partidasFinancierasOptions ?? []) as $partidaOpt): ?>
                        <option value="<?= htmlspecialchars($partidaOpt['erppartidafinancieraid']) ?>"
                            <?= (isset($invitem['erppartidafinancieraid']) && $invitem['erppartidafinancieraid'] == $partidaOpt['erppartidafinancieraid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($partidaOpt['erppartidafinancieracod'] ?? '') . ' - ' . ($partidaOpt['erppartidafinancieradsc'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="invitemleche">Para módulo Leche</label>
                <select class="form-select" id="invitemleche" name="invitemleche">
                    <option value="1" <?= (isset($invitem['invitemleche']) && $invitem['invitemleche'] == 1) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= (isset($invitem['invitemleche']) && $invitem['invitemleche'] == 0) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="invitemstockeable">Stockeable</label>
                <select class="form-select" id="invitemstockeable" name="invitemstockeable">
                    <option value="1" <?= (isset($invitem['invitemstockeable']) && $invitem['invitemstockeable'] == 1) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= (isset($invitem['invitemstockeable']) && $invitem['invitemstockeable'] == 0) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="invitemcompra">Es compra</label>
                <select class="form-select" id="invitemcompra" name="invitemcompra">
                    <option value="1" <?= (isset($invitem['invitemcompra']) && $invitem['invitemcompra'] == 1) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= (isset($invitem['invitemcompra']) && $invitem['invitemcompra'] == 0) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="invitemcostoestandar">Costo estándar</label>
                <?php
                $costoEstandar = (float)($invitem['invitemcostoestandar'] ?? 0);
                $costoBloqueado = $costoEstandar > 0;
                ?>
                <?php if ($costoBloqueado): ?>
                    <input type="hidden" name="invitemcostoestandar" value="<?= htmlspecialchars((string)$costoEstandar) ?>">
                <?php endif; ?>
                <input type="number"
                       name="invitemcostoestandar"
                       id="invitemcostoestandar"
                       class="form-control"
                       step="0.0001"
                       min="0"
                       value="<?= htmlspecialchars((string)$costoEstandar) ?>"
                       <?= $costoBloqueado ? 'disabled' : '' ?>>
            </div>
            <div class="form-field">
                <label class="form-label" for="invitemcostoestandarfechahora">Fecha costo estándar</label>
                <input type="text"
                       id="invitemcostoestandarfechahora"
                       class="form-control"
                       value="<?= htmlspecialchars($invitem['invitemcostoestandarfechahora'] ?? '') ?>"
                       readonly>
            </div>
            <div class="form-field">
                <label class="form-label" for="invitemactivo">Activo</label>
                <select class="form-select" id="invitemactivo" name="invitemactivo">
                    <option value="1" <?= (isset($invitem['invitemactivo']) && $invitem['invitemactivo'] == 1) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= (isset($invitem['invitemactivo']) && $invitem['invitemactivo'] == 0) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="?route=invitems/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
