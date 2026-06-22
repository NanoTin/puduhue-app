<?php
$isPartial = $partial ?? false;
$empresasOptions = $empresasOptions ?? [];
$fundosOptions = $fundosOptions ?? [];
$prodlechetiposOptions = $prodlechetiposOptions ?? [];
$formData = $formData ?? ($registro ?? []);
$detallesData = is_array($formData['detalles'] ?? null) ? $formData['detalles'] : [];
$detallesPorTipo = [];
foreach ($detallesData as $detalleItem) {
    $tipoIdForMap = $detalleItem['prodlechetipoid'] ?? null;
    if ($tipoIdForMap !== null) {
        $detallesPorTipo[(string)$tipoIdForMap] = $detalleItem;
    }
}

$fechaValue = $formData['prodlechefecha'] ?? '';
if (!empty($fechaValue)) {
    $fechaValue = substr((string)$fechaValue, 0, 10);
}
$horaIniValue = $formData['prodlechehoraini'] ?? '';
if (!empty($horaIniValue)) {
    $horaIniValue = substr((string)$horaIniValue, 0, 5);
}
$horaFinValue = $formData['prodlechehorafin'] ?? '';
if (!empty($horaFinValue)) {
    $horaFinValue = substr((string)$horaFinValue, 0, 5);
}

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<link rel="stylesheet" href="assets/css/frm_ins_upd.css">

<div class="container-fluid px-3 py-3">
    <div class="form-shell">
        <h3 class="mb-3">Editar Producci&oacute;n de Leche</h3>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form id="prodlecheForm" method="POST" action="?route=prodleche/editar">
            <input type="hidden" name="prodlecheid" value="<?= htmlspecialchars($formData['prodlecheid'] ?? '') ?>">

            <section class="mb-4">
                <h5 class="section-title">Datos Generales</h5>
                <div class="row g-3 align-items-end general-grid">
                    <div class="col-12 col-lg-6 d-none">
                        <label for="empresaid" class="form-label">Empresa</label>
                        <select name="empresaid" id="empresaid" class="form-select" disabled>
                            <option value="">Seleccione</option>
                            <?php foreach ($empresasOptions as $empresa): ?>
                                <?php
                                $empresaId = $empresa['empresaid'] ?? '';
                                $empresaNombre = $empresa['razonsocial'] ?? ($empresaId ?? '');
                                $empresaSelected = (string)($formData['empresaid'] ?? '') === (string)$empresaId ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($empresaId) ?>" <?= $empresaSelected ?>>
                                    <?= htmlspecialchars($empresaNombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="empresaid" value="<?= htmlspecialchars($formData['empresaid'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="fundoid" class="form-label mb-0">Fundo</label>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="openErpModalBtn">Ver info ERP</button>
                        </div>
                        <select name="fundoid" id="fundoid" class="form-select" disabled>
                            <option value="">Seleccione</option>
                            <?php foreach ($fundosOptions as $fundo): ?>
                                <?php
                                $fundoId = $fundo['fundoid'] ?? '';
                                $fundoSelected = (string)($formData['fundoid'] ?? '') === (string)$fundoId ? 'selected' : '';
                                ?>
                                <option
                                    value="<?= htmlspecialchars($fundoId) ?>"
                                    <?= $fundoSelected ?>
                                    data-empresaid="<?= htmlspecialchars($fundo['empresaid'] ?? '') ?>"
                                    data-erpestablecimientocod="<?= htmlspecialchars($fundo['erpestablecimientocod'] ?? '') ?>"
                                    data-erplotecod="<?= htmlspecialchars($fundo['erplotecod'] ?? '') ?>"
                                    data-erpleche_invbodegacod="<?= htmlspecialchars($fundo['erpleche_invbodegacod'] ?? '') ?>"
                                    data-erpleche_invcateganimalcod="<?= htmlspecialchars($fundo['erpleche_invcateganimalcod'] ?? '') ?>"
                                >
                                    <?= htmlspecialchars($fundo['fundonombre'] ?? ($fundo['fundoid'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="fundoid" value="<?= htmlspecialchars($formData['fundoid'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <label for="prodlechefecha" class="form-label">Fecha</label>
                        <input
                            type="date"
                            name="prodlechefecha"
                            id="prodlechefecha"
                            class="form-control"
                            value="<?= htmlspecialchars($fechaValue) ?>"
                            readonly
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="prodlechehoraini" class="form-label">Hora Inicio</label>
                        <input
                            type="time"
                            name="prodlechehoraini"
                            id="prodlechehoraini"
                            class="form-control"
                            value="<?= htmlspecialchars($horaIniValue) ?>"
                            readonly
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="prodlechehorafin" class="form-label">Hora T&eacute;rmino</label>
                        <input
                            type="time"
                            name="prodlechehorafin"
                            id="prodlechehorafin"
                            class="form-control"
                            value="<?= htmlspecialchars($horaFinValue) ?>"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="prodlechehorario" class="form-label">Horario (AM/PM)</label>
                        <input
                            type="text"
                            name="prodlechehorario"
                            id="prodlechehorario"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['prodlechehorario'] ?? '') ?>"
                            readonly
                        >
                    </div>
                    <div class="col-12">
                        <label for="prodlecheobservacion" class="form-label">Observaci&oacute;n</label>
                        <textarea
                            name="prodlecheobservacion"
                            id="prodlecheobservacion"
                            class="form-control"
                            rows="2"
                            maxlength="50"
                            placeholder="Observaciones generales"
                        ><?= htmlspecialchars($formData['prodlecheobservacion'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h5 class="section-title mb-3">Detalle</h5>

                <div class="table-responsive transaction-detail-wrap">
                    <table class="detail-table">
                        <thead>
                        <tr>
                            <th class="col-detail-name">Tipo Leche</th>
                            <th class="col-detail-number">Litros</th>
                            <th class="col-detail-number">Vacas</th>
                            <th class="col-detail-number">Lts x Vaca</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($prodlechetiposOptions)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No hay tipos de leche configurados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prodlechetiposOptions as $index => $tipo): ?>
                                <?php
                                $tipoIdRaw = $tipo['prodlechetipoid'] ?? '';
                                $tipoId = htmlspecialchars($tipoIdRaw ?? '');
                                $tipoDsc = htmlspecialchars($tipo['prodlechetipodsc'] ?? '');
                                $esVenta = (int)($tipo['prodlecheventa'] ?? 0) === 1;
                                $detalleInput = $detallesData[$index] ?? ($detallesPorTipo[(string)$tipoIdRaw] ?? []);
                                $litrosDetalle = isset($detalleInput['pldetlitros']) ? (float)$detalleInput['pldetlitros'] : 0;
                                $vacasDetalle = isset($detalleInput['pldetvacas']) ? (float)$detalleInput['pldetvacas'] : 0;
                                $ltsxvacaDetalle = $detalleInput['pldetlitrosxvaca'] ?? ($vacasDetalle > 0 ? $litrosDetalle / $vacasDetalle : 0);
                                $litrosVal = htmlspecialchars((string)($detalleInput['pldetlitros'] ?? '0'));
                                $vacasVal = htmlspecialchars((string)($detalleInput['pldetvacas'] ?? '0'));
                                $ltsxvacaVal = htmlspecialchars(is_numeric($ltsxvacaDetalle) ? number_format((float)$ltsxvacaDetalle, 2, '.', '') : '0');
                                ?>
                                <tr data-prodlecheventa="<?= $esVenta ? '1' : '0' ?>">
                                    <td class="fw-semibold"><?= $tipoDsc ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            name="detalles[<?= $index ?>][pldetlitros]"
                                            class="form-control form-control-sm litros-input"
                                            step="1"
                                            min="0"
                                            value="<?= $litrosVal ?>"
                                        >
                                        <input type="hidden" name="detalles[<?= $index ?>][prodlechetipoid]" value="<?= $tipoId ?>">
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="detalles[<?= $index ?>][pldetvacas]"
                                            class="form-control form-control-sm vacas-input"
                                            step="1"
                                            min="0"
                                            value="<?= $vacasVal ?>"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="detalles[<?= $index ?>][pldetlitrosxvaca]"
                                            class="form-control form-control-sm ltsxvaca-input"
                                            step="0.01"
                                            min="0"
                                            value="<?= $ltsxvacaVal ?>"
                                            readonly
                                        >
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="mb-3">
                <?php
                $totalLitrosVal = (float)($formData['prodlechetotlitros'] ?? 0);
                $totalVacasVal = (float)($formData['prodlechetotvacas'] ?? 0);
                $ventaLitrosVal = (float)($formData['prodlecheventatotlitros'] ?? 0);
                $ventaVacasVal = (float)($formData['prodlecheventatotvacas'] ?? 0);
                $ventaLxVVal = (float)($formData['prodlecheventalitrosxvaca'] ?? 0);
                ?>
                <h5 class="section-title">Totales</h5>
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <dl class="row totals-box mb-0">
                            <dt class="col-7 col-sm-6">Total Litros</dt>
                            <dd class="col-5 col-sm-6" id="totalLitrosTxt"><?= htmlspecialchars(number_format($totalLitrosVal, 0, ',', '.')) ?></dd>

                            <dt class="col-7 col-sm-6">Total Vacas</dt>
                            <dd class="col-5 col-sm-6" id="totalVacasTxt"><?= htmlspecialchars(number_format($totalVacasVal, 0, ',', '.')) ?></dd>

                            <dt class="col-7 col-sm-6">Total Litros Planta</dt>
                            <dd class="col-5 col-sm-6" id="ventaLitrosTxt"><?= htmlspecialchars(number_format($ventaLitrosVal, 0, ',', '.')) ?></dd>

                            <dt class="col-7 col-sm-6">Total Vacas Planta</dt>
                            <dd class="col-5 col-sm-6" id="ventaVacasTxt"><?= htmlspecialchars(number_format($ventaVacasVal, 0, ',', '.')) ?></dd>

                            <dt class="col-7 col-sm-6">Lts x Vaca Planta</dt>
                            <dd class="col-5 col-sm-6" id="ventaLxVTxt"><?= htmlspecialchars(number_format($ventaLxVVal, 2, ',', '.')) ?></dd>
                        </dl>
                    </div>
                </div>

                <input type="hidden" name="prodlechetotlitros" id="prodlechetotlitros" value="<?= htmlspecialchars(number_format($totalLitrosVal, 0, '.', '')) ?>">
                <input type="hidden" name="prodlechetotvacas" id="prodlechetotvacas" value="<?= htmlspecialchars(number_format($totalVacasVal, 0, '.', '')) ?>">
                <input type="hidden" name="prodlecheventatotlitros" id="prodlecheventatotlitros" value="<?= htmlspecialchars(number_format($ventaLitrosVal, 0, '.', '')) ?>">
                <input type="hidden" name="prodlecheventatotvacas" id="prodlecheventatotvacas" value="<?= htmlspecialchars(number_format($ventaVacasVal, 0, '.', '')) ?>">
                <input type="hidden" name="prodlecheventalitrosxvaca" id="prodlecheventalitrosxvaca" value="<?= htmlspecialchars(number_format($ventaLxVVal, 2, '.', '')) ?>">
            </section>

            <div class="modal-shell" id="erpModal" aria-hidden="true">
                <div class="modal-backdrop"></div>
                <div class="modal-card">
                    <div class="modal-header">
                        <h6 class="mb-0">Informaci&oacute;n ERP</h6>
                        <button type="button" class="btn btn-icon" data-close-modal="erpModal" aria-label="Cerrar">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="pl_erpestablecimientocod" class="form-label">ERP Establecimiento</label>
                                <input
                                    type="text"
                                    name="pl_erpestablecimientocod"
                                    id="pl_erpestablecimientocod"
                                    class="form-control"
                                    value="<?= htmlspecialchars($formData['pl_erpestablecimientocod'] ?? '') ?>"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="pl_erplotecod" class="form-label">ERP Lote</label>
                                <input
                                    type="text"
                                    name="pl_erplotecod"
                                    id="pl_erplotecod"
                                    class="form-control"
                                    value="<?= htmlspecialchars($formData['pl_erplotecod'] ?? '') ?>"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="pl_erpleche_invbodegacod" class="form-label">ERP Bodega Leche</label>
                                <input
                                    type="text"
                                    name="pl_erpleche_invbodegacod"
                                    id="pl_erpleche_invbodegacod"
                                    class="form-control"
                                    value="<?= htmlspecialchars($formData['pl_erpleche_invbodegacod'] ?? '') ?>"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="pl_erpleche_invcateganimalcod" class="form-label">ERP Cat. Animal Leche</label>
                                <input
                                    type="text"
                                    name="pl_erpleche_invcateganimalcod"
                                    id="pl_erpleche_invcateganimalcod"
                                    class="form-control"
                                    value="<?= htmlspecialchars($formData['pl_erpleche_invcateganimalcod'] ?? '') ?>"
                                    readonly
                                >
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-close-modal="erpModal">Cerrar</button>
                    </div>
                </div>
            </div>

            <div class="modal-shell" id="confirmModal" aria-hidden="true">
                <div class="modal-backdrop"></div>
                <div class="modal-card">
                    <div class="modal-header">
                        <h6 class="mb-0">Confirmar guardado</h6>
                        <button type="button" class="btn btn-icon" data-close-modal="confirmModal" aria-label="Cerrar">×</button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">Revise los datos antes de guardar.</p>
                        <div class="summary-grid">
                            <div>
                                <span class="summary-label">Fecha</span>
                                <span class="summary-value" id="confirmFecha"></span>
                            </div>
                            <div>
                                <span class="summary-label">Total Planta</span>
                                <span class="summary-value" id="confirmTotalVenta"></span>
                            </div>
                            <div>
                                <span class="summary-label">Total Vacas Planta</span>
                                <span class="summary-value" id="confirmTotalVacasVenta"></span>
                            </div>
                            <div>
                                <span class="summary-label">Total litros</span>
                                <span class="summary-value" id="confirmTotalLitros"></span>
                            </div>
                            <div>
                                <span class="summary-label">Total vacas</span>
                                <span class="summary-value" id="confirmTotalVacas"></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" data-close-modal="confirmModal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="confirmSaveBtn">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="?route=prodleche/listar" class="btn btn-outline-secondary">Volver</a>
                <button type="button" class="btn btn-primary" id="openConfirmModalBtn">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const fundoidSelect = document.getElementById('fundoid');
        const empresaSelect = document.getElementById('empresaid');
        const horarioInput = document.getElementById('prodlechehorario');
        const horaIniInput = document.getElementById('prodlechehoraini');
        const horaFinInput = document.getElementById('prodlechehorafin');
        const prodlecheFechaInput = document.getElementById('prodlechefecha');
        const observacionInput = document.getElementById('prodlecheobservacion');

        const erpEstInput = document.getElementById('pl_erpestablecimientocod');
        const erpLoteInput = document.getElementById('pl_erplotecod');
        const erpBodInput = document.getElementById('pl_erpleche_invbodegacod');
        const erpCatInput = document.getElementById('pl_erpleche_invcateganimalcod');

        const totalLitrosTxt = document.getElementById('totalLitrosTxt');
        const totalVacasTxt = document.getElementById('totalVacasTxt');
        const ventaLitrosTxt = document.getElementById('ventaLitrosTxt');
        const ventaVacasTxt = document.getElementById('ventaVacasTxt');
        const ventaLxVTxt = document.getElementById('ventaLxVTxt');

        const totalLitrosInput = document.getElementById('prodlechetotlitros');
        const totalVacasInput = document.getElementById('prodlechetotvacas');
        const ventaLitrosInput = document.getElementById('prodlecheventatotlitros');
        const ventaVacasInput = document.getElementById('prodlecheventatotvacas');
        const ventaLxVInput = document.getElementById('prodlecheventalitrosxvaca');
        const openErpBtn = document.getElementById('openErpModalBtn');
        const openConfirmBtn = document.getElementById('openConfirmModalBtn');
        const confirmSaveBtn = document.getElementById('confirmSaveBtn');

        const confirmFecha = document.getElementById('confirmFecha');
        const confirmTotalVenta = document.getElementById('confirmTotalVenta');
        const confirmTotalVacasVenta = document.getElementById('confirmTotalVacasVenta');
        const confirmTotalLitros = document.getElementById('confirmTotalLitros');
        const confirmTotalVacas = document.getElementById('confirmTotalVacas');

        function setHorario() {
            const horaIni = horaIniInput ? horaIniInput.value : '';
            if (!horaIni) {
                if (horarioInput) horarioInput.value = '';
                return;
            }
            const parts = horaIni.split(':');
            const hour = parseInt(parts[0], 10);
            if (horarioInput) {
                horarioInput.value = hour < 12 ? 'AM' : 'PM';
            }
        }

        function actualizarDatosFundo() {
            if (!fundoidSelect) return;
            const selected = fundoidSelect.options[fundoidSelect.selectedIndex];
            if (!selected || !selected.dataset.empresaid) {
                if (erpEstInput) erpEstInput.value = '';
                if (erpLoteInput) erpLoteInput.value = '';
                if (erpBodInput) erpBodInput.value = '';
                if (erpCatInput) erpCatInput.value = '';
                return;
            }
            const empresaId = selected.dataset.empresaid;
            if (empresaId && empresaSelect) {
                empresaSelect.value = empresaId;
            }
            if (erpEstInput) erpEstInput.value = selected.dataset.erpestablecimientocod || '';
            if (erpLoteInput) erpLoteInput.value = selected.dataset.erplotecod || '';
            if (erpBodInput) erpBodInput.value = selected.dataset.erpleche_invbodegacod || '';
            if (erpCatInput) erpCatInput.value = selected.dataset.erpleche_invcateganimalcod || '';
        }

        function formatNumber(val, decimals = 2) {
            return Number(val).toLocaleString('es-CL', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        function recalcRow(row) {
            const litrosInput = row.querySelector('.litros-input');
            const vacasInput = row.querySelector('.vacas-input');
            const ltsxvacaInput = row.querySelector('.ltsxvaca-input');

            const litros = parseFloat(litrosInput?.value || '0') || 0;
            const vacas = parseFloat(vacasInput?.value || '0') || 0;
            const ltsxvaca = vacas > 0 ? litros / vacas : 0;

            if (ltsxvacaInput) {
                ltsxvacaInput.value = ltsxvaca.toFixed(2);
            }
        }

        function recalcTotals() {
            const rows = document.querySelectorAll('table.detail-table tbody tr');
            let totalLitros = 0;
            let totalVacas = 0;
            let ventaLitros = 0;
            let ventaVacas = 0;

            rows.forEach((row) => {
                const litros = parseFloat(row.querySelector('.litros-input')?.value || '0') || 0;
                const vacas = parseFloat(row.querySelector('.vacas-input')?.value || '0') || 0;
                const esVenta = row.dataset.prodlecheventa === '1';

                totalLitros += litros;
                totalVacas += vacas;
                if (esVenta) {
                    ventaLitros += litros;
                    ventaVacas += vacas;
                }
            });

            const ventaLxV = ventaVacas > 0 ? ventaLitros / ventaVacas : 0;

            if (totalLitrosTxt) totalLitrosTxt.textContent = formatNumber(totalLitros, 0);
            if (totalVacasTxt) totalVacasTxt.textContent = formatNumber(totalVacas, 0);
            if (ventaLitrosTxt) ventaLitrosTxt.textContent = formatNumber(ventaLitros, 0);
            if (ventaVacasTxt) ventaVacasTxt.textContent = formatNumber(ventaVacas, 0);
            if (ventaLxVTxt) ventaLxVTxt.textContent = formatNumber(ventaLxV, 2);

            if (totalLitrosInput) totalLitrosInput.value = Math.round(totalLitros);
            if (totalVacasInput) totalVacasInput.value = Math.round(totalVacas);
            if (ventaLitrosInput) ventaLitrosInput.value = Math.round(ventaLitros);
            if (ventaVacasInput) ventaVacasInput.value = Math.round(ventaVacas);
            if (ventaLxVInput) ventaLxVInput.value = ventaLxV.toFixed(2);
        }

        function validarDetalleConCantidad() {
            const rows = document.querySelectorAll('table.detail-table tbody tr');
            let tieneDetalle = false;
            for (const row of rows) {
                const litros = parseFloat(row.querySelector('.litros-input')?.value || '0') || 0;
                const vacas = parseFloat(row.querySelector('.vacas-input')?.value || '0') || 0;

                // Regla por fila: si uno tiene valor, el otro tambien debe ser > 0.
                if ((litros > 0 && vacas <= 0) || (vacas > 0 && litros <= 0)) {
                    window.ToastManager?.show('En cada fila, si ingresa litros debe ingresar vacas y viceversa (ambos mayores a cero).', 'warning');
                    if (litros > 0 && vacas <= 0) {
                        row.querySelector('.vacas-input')?.focus();
                    } else {
                        row.querySelector('.litros-input')?.focus();
                    }
                    return false;
                }

                if (litros > 0 && vacas > 0) {
                    tieneDetalle = true;
                }
            }

            if (!tieneDetalle) {
                window.ToastManager?.show('Ingrese al menos un tipo de leche con litros y vacas mayores a cero.', 'warning');
                return false;
            }
            return true;
        }

        function validarHoraFinMayorIgual() {
            const ini = horaIniInput ? horaIniInput.value : '';
            const fin = horaFinInput ? horaFinInput.value : '';
            if (!ini || !fin) {
                return true;
            }
            const iniParts = ini.split(':').map((n) => parseInt(n, 10));
            const finParts = fin.split(':').map((n) => parseInt(n, 10));
            const iniTotal = (iniParts[0] || 0) * 60 + (iniParts[1] || 0);
            const finTotal = (finParts[0] || 0) * 60 + (finParts[1] || 0);
            if (finTotal < iniTotal) {
                window.ToastManager?.show('La hora término no puede ser menor a la hora inicio.', 'warning');
                horaFinInput?.focus();
                return false;
            }
            return true;
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('is-visible');
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('is-visible');
            }
        }

        function bindModalButtons() {
            document.querySelectorAll('[data-close-modal]').forEach((btn) => {
                btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
            });
            document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
                backdrop.addEventListener('click', () => closeModal(backdrop.closest('.modal-shell')?.id || ''));
            });
        }

        function setConfirmSummary() {
            if (confirmFecha) confirmFecha.textContent = prodlecheFechaInput?.value || '';
            if (confirmTotalVenta) confirmTotalVenta.textContent = ventaLitrosTxt?.textContent || '';
            if (confirmTotalVacasVenta) confirmTotalVacasVenta.textContent = ventaVacasTxt?.textContent || '';
            if (confirmTotalLitros) confirmTotalLitros.textContent = totalLitrosTxt?.textContent || '';
            if (confirmTotalVacas) confirmTotalVacas.textContent = totalVacasTxt?.textContent || '';
        }

        function initDetailEvents() {
            const rows = document.querySelectorAll('table.detail-table tbody tr');
            rows.forEach((row) => {
                row.querySelectorAll('.litros-input, .vacas-input').forEach((input) => {
                    input.addEventListener('input', () => {
                        recalcRow(row);
                        recalcTotals();
                    });
                });
                recalcRow(row);
            });
            recalcTotals();
        }

        if (openErpBtn) {
            openErpBtn.addEventListener('click', () => openModal('erpModal'));
        }
        if (openConfirmBtn) {
            openConfirmBtn.addEventListener('click', () => {
                recalcTotals();
                if (!validarDetalleConCantidad() || !validarHoraFinMayorIgual()) {
                    return;
                }
                setConfirmSummary();
                openModal('confirmModal');
            });
        }
        if (confirmSaveBtn) {
            confirmSaveBtn.addEventListener('click', () => {
                document.getElementById('prodlecheForm')?.requestSubmit();
            });
        }

        if (horaIniInput) {
            horaIniInput.addEventListener('change', setHorario);
        }
        if (fundoidSelect) {
            fundoidSelect.addEventListener('change', actualizarDatosFundo);
        }

        bindModalButtons();
        actualizarDatosFundo();
        setHorario();
        initDetailEvents();
    })();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
