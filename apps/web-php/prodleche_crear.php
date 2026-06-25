<?php
$isPartial = $partial ?? false;
$empresasOptions = $empresasOptions ?? [];
$fundosOptions = $fundosOptions ?? [];
$prodlechetiposOptions = $prodlechetiposOptions ?? [];
$formData = $formData ?? [];
$defaultProdlecheFecha = date('Y-m-d');
$detallesData = is_array($formData['detalles'] ?? null) ? $formData['detalles'] : [];
$detallesPorTipo = [];
foreach ($detallesData as $detalleItem) {
    $tipoIdForMap = $detalleItem['prodlechetipoid'] ?? null;
    if ($tipoIdForMap !== null) {
        $detallesPorTipo[(string)$tipoIdForMap] = $detalleItem;
    }
}

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<link rel="stylesheet" href="assets/css/frm_ins_upd.css">

<div class="container-fluid px-3 py-3">
    <div class="form-shell">
        <h3 class="mb-3">Crear Producci&oacute;n de Leche</h3>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form id="prodlecheForm" method="POST" action="?route=prodleche/crear">
            <section class="mb-4">
                <h5 class="section-title">Datos Generales</h5>
                <div class="row g-3 align-items-end general-grid">
                    <div class="col-12 col-lg-6 d-none">
                        <label for="empresaid" class="form-label">Empresa</label>
                        <select name="empresaid" id="empresaid" class="form-select" required>
                            <?php foreach ($empresasOptions as $empresa): ?>
                                <?php
                                $empresaId = $empresa['empresaid'] ?? '';
                                $empresaNombre = $empresa['razonsocial'] ?? ($empresaId ?? '');
                                $empresaSelected = (string)($formData['empresaid'] ?? $empresaIdWS) === (string)$empresaId ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($empresaId) ?>" <?= $empresaSelected ?>>
                                    <?= htmlspecialchars($empresaNombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="fundoid" class="form-label mb-0">Fundo</label>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="openErpModalBtn">Ver info ERP</button>
                        </div>
                        <select name="fundoid" id="fundoid" class="form-select" required>
                            <?php foreach ($fundosOptions as $fundo): ?>
                                <?php
                                $fundoId = $fundo['fundoid'] ?? '';
                                $fundoSelected = (string)($formData['fundoid'] ?? $fundoIdWS) === (string)$fundoId ? 'selected' : '';
                                ?>
                                <option
                                    value="<?= htmlspecialchars($fundoId) ?>" <?= $fundoSelected ?>
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
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <label for="prodlechefecha" class="form-label">Fecha</label>
                        <input
                            type="date"
                            name="prodlechefecha"
                            id="prodlechefecha"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['prodlechefecha'] ?? $defaultProdlecheFecha) ?>"
                            data-default="<?= htmlspecialchars($defaultProdlecheFecha) ?>"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="prodlechehoraini" class="form-label">Hora Inicio</label>
                        <input
                            type="time"
                            name="prodlechehoraini"
                            id="prodlechehoraini"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['prodlechehoraini'] ?? '') ?>"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="prodlechehorafin" class="form-label">Hora T&eacute;rmino</label>
                        <input
                            type="time"
                            name="prodlechehorafin"
                            id="prodlechehorafin"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['prodlechehorafin'] ?? '') ?>"
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
                                $tipoActivo = (int)($tipo['prodlecheactivo'] ?? 1) === 1;
                                $detalleInput = $detallesData[$index] ?? ($detallesPorTipo[(string)$tipoIdRaw] ?? []);
                                $litrosDetalle = isset($detalleInput['pldetlitros']) ? (float)$detalleInput['pldetlitros'] : 0;
                                $vacasDetalle = isset($detalleInput['pldetvacas']) ? (float)$detalleInput['pldetvacas'] : 0;
                                $ltsxvacaDetalle = $detalleInput['pldetlitrosxvaca'] ?? ($vacasDetalle > 0 ? $litrosDetalle / $vacasDetalle : 0);
                                $litrosVal = $tipoActivo ? htmlspecialchars((string)($detalleInput['pldetlitros'] ?? '0')) : '0';
                                $vacasVal = $tipoActivo ? htmlspecialchars((string)($detalleInput['pldetvacas'] ?? '0')) : '0';
                                $ltsxvacaVal = $tipoActivo ? htmlspecialchars(is_numeric($ltsxvacaDetalle) ? number_format((float)$ltsxvacaDetalle, 2, '.', '') : '0') : '0.00';
                                ?>
                                <tr data-prodlecheventa="<?= ($esVenta && $tipoActivo) ? '1' : '0' ?>" class="<?= $tipoActivo ? '' : 'text-muted table-secondary' ?>">
                                    <td class="fw-semibold">
                                        <?= $tipoDsc ?>
                                        <?php if (!$tipoActivo): ?>
                                            <span class="badge bg-secondary ms-1">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$tipoActivo): ?>
                                            <input type="hidden" name="detalles[<?= $index ?>][pldetlitros]" value="0">
                                        <?php endif; ?>
                                        <input
                                            type="number"
                                            name="detalles[<?= $index ?>][pldetlitros]"
                                            class="form-control form-control-sm litros-input"
                                            step="1"
                                            min="0"
                                            value="<?= $litrosVal ?>"
                                            <?= $tipoActivo ? '' : 'disabled' ?>
                                        >
                                        <input type="hidden" name="detalles[<?= $index ?>][prodlechetipoid]" value="<?= $tipoId ?>">
                                    </td>
                                    <td>
                                        <?php if (!$tipoActivo): ?>
                                            <input type="hidden" name="detalles[<?= $index ?>][pldetvacas]" value="0">
                                        <?php endif; ?>
                                        <input
                                            type="number"
                                            name="detalles[<?= $index ?>][pldetvacas]"
                                            class="form-control form-control-sm vacas-input"
                                            step="1"
                                            min="0"
                                            value="<?= $vacasVal ?>"
                                            <?= $tipoActivo ? '' : 'disabled' ?>
                                        >
                                    </td>
                                    <td>
                                        <?php if (!$tipoActivo): ?>
                                            <input type="hidden" name="detalles[<?= $index ?>][pldetlitrosxvaca]" value="0">
                                        <?php endif; ?>
                                        <input
                                            type="number"
                                            name="detalles[<?= $index ?>][pldetlitrosxvaca]"
                                            class="form-control form-control-sm ltsxvaca-input"
                                            step="0.01"
                                            min="0"
                                            value="<?= $ltsxvacaVal ?>"
                                            readonly
                                            <?= $tipoActivo ? '' : 'disabled' ?>
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
                        <h6 class="mb-0">Información ERP</h6>
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
                        <!--<a class="btn btn-outline-secondary" href="?route=prodleche/listar">Volver al listado</a>-->
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

        let hasManualChange = false;
        const markDirty = () => {
            hasManualChange = true;
        };

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
            if (ventaLxVTxt) ventaLxVTxt.textContent = formatNumber(ventaLxV);

            if (totalLitrosInput) totalLitrosInput.value = totalLitros.toFixed(0);
            if (totalVacasInput) totalVacasInput.value = totalVacas.toFixed(0);
            if (ventaLitrosInput) ventaLitrosInput.value = ventaLitros.toFixed(0);
            if (ventaVacasInput) ventaVacasInput.value = ventaVacas.toFixed(0);
            if (ventaLxVInput) ventaLxVInput.value = ventaLxV.toFixed(2);
        }

        function recalcAllRows() {
            const rows = document.querySelectorAll('table.detail-table tbody tr');
            rows.forEach(recalcRow);
            recalcTotals();
        }

        function hookGrid() {
            const rows = document.querySelectorAll('table.detail-table tbody tr');
            rows.forEach((row) => {
                const litrosInput = row.querySelector('.litros-input');
                const vacasInput = row.querySelector('.vacas-input');
                if (litrosInput) {
                    litrosInput.addEventListener('input', () => {
                        recalcRow(row);
                        recalcTotals();
                        markDirty();
                    });
                }
                if (vacasInput) {
                    vacasInput.addEventListener('input', () => {
                        recalcRow(row);
                        recalcTotals();
                        markDirty();
                    });
                }
            });
        }

        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('is-visible');
            modal.setAttribute('aria-hidden', 'false');
            modal.setAttribute('aria-hidden', 'false');
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('is-visible');
            modal.setAttribute('aria-hidden', 'true');
            modal.setAttribute('aria-hidden', 'true');
        }

        function wireModalClose() {
            document.querySelectorAll('[data-close-modal]').forEach((btn) => {
                const target = btn.getAttribute('data-close-modal');
                btn.addEventListener('click', () => hideModal(target));
            });
            document.querySelectorAll('.modal-shell').forEach((modal) => {
                const modalId = modal.id;
                const backdrop = modal.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.addEventListener('click', () => hideModal(modalId));
                }
            });
        }

        function addDirtyListeners() {
            const form = document.getElementById('prodlecheForm');
            if (!form) {
                return;
            }
            const fields = form.querySelectorAll('input, select, textarea');
            fields.forEach((el) => {
                if (el.type === 'hidden') {
                    return;
                }
                el.addEventListener('input', markDirty);
                el.addEventListener('change', markDirty);
            });
        }

        function showToast(message, type = 'warning') {
            if (window.ToastManager) {
                window.ToastManager.show(message, type);
            }
        }

        function validarFechaNoFuturaFrontend() {
            if (!prodlecheFechaInput || !prodlecheFechaInput.value) {
                return true;
            }
            const selectedDate = new Date(`${prodlecheFechaInput.value}T00:00:00`);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            if (selectedDate > today) {
                showToast('La fecha no puede ser mayor a hoy.', 'warning');
                prodlecheFechaInput.focus();
                return false;
            }
            return true;
        }

        function validarDetalleConCantidad() {
            const rows = document.querySelectorAll('table.detail-table tbody tr');
            let tieneDetalle = false;
            for (const row of rows) {
                const litros = parseFloat(row.querySelector('.litros-input')?.value || '0') || 0;
                const vacas = parseFloat(row.querySelector('.vacas-input')?.value || '0') || 0;

                // Regla por fila: si uno tiene valor, el otro tambien debe ser > 0.
                if ((litros > 0 && vacas <= 0) || (vacas > 0 && litros <= 0)) {
                    showToast('En cada fila, si ingresa litros debe ingresar vacas y viceversa (ambos mayores a cero).', 'warning');
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
                showToast('Ingrese al menos un tipo de leche con litros y vacas mayores a cero.', 'warning');
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
                showToast('La hora término no puede ser menor a la hora inicio.', 'warning');
                horaFinInput?.focus();
                return false;
            }
            return true;
        }

        function validarErpCampos() {
            const campos = [
                { id: 'pl_erpestablecimientocod', label: 'ERP Establecimiento' },
                { id: 'pl_erplotecod', label: 'ERP Lote' },
                { id: 'pl_erpleche_invbodegacod', label: 'ERP Bodega Leche' },
                { id: 'pl_erpleche_invcateganimalcod', label: 'ERP Cat. Animal Leche' },
            ];
            for (const campo of campos) {
                const input = document.getElementById(campo.id);
                const val = (input?.value || '').trim();
                if (!val) {
                    showToast(`El campo ${campo.label} no puede estar vacío. Contactar al administrador de la plataforma.`, 'danger');
                    input?.focus();
                    return false;
                }
            }
            return true;
        }

        function hasFormDataEntered() {
            const fechaVal = prodlecheFechaInput ? prodlecheFechaInput.value : '';
            const fechaDefault = prodlecheFechaInput ? (prodlecheFechaInput.dataset.default || '') : '';
            const empresaVal = empresaSelect ? empresaSelect.value : '';
            const fundoVal = fundoidSelect ? fundoidSelect.value : '';
            const horaIniVal = horaIniInput ? horaIniInput.value : '';
            const horaFinVal = horaFinInput ? horaFinInput.value : '';
            const obsVal = (observacionInput?.value || '').trim();
            let detalleConDatos = false;

            document.querySelectorAll('table.detail-table tbody tr').forEach((row) => {
                const litros = parseFloat(row.querySelector('.litros-input')?.value || '0') || 0;
                const vacas = parseFloat(row.querySelector('.vacas-input')?.value || '0') || 0;
                if (litros > 0 || vacas > 0) {
                    detalleConDatos = true;
                }
            });

            const fechaSeleccionada = fechaVal && fechaVal !== fechaDefault;
            return (
                fechaSeleccionada ||
                empresaVal ||
                fundoVal ||
                horaIniVal ||
                horaFinVal ||
                obsVal ||
                detalleConDatos
            );
        }

        function fillConfirmSummary() {
            if (confirmFecha) {
                confirmFecha.textContent = prodlecheFechaInput?.value || '';
            }
            if (confirmTotalVenta) {
                confirmTotalVenta.textContent = ventaLitrosTxt ? ventaLitrosTxt.textContent : '';
            }
            if (confirmTotalVacasVenta) {
                confirmTotalVacasVenta.textContent = ventaVacasTxt ? ventaVacasTxt.textContent : '';
            }
            if (confirmTotalLitros) {
                confirmTotalLitros.textContent = totalLitrosTxt ? totalLitrosTxt.textContent : '';
            }
            if (confirmTotalVacas) {
                confirmTotalVacas.textContent = totalVacasTxt ? totalVacasTxt.textContent : '';
            }
        }

        if (horaIniInput) {
            horaIniInput.addEventListener('change', () => {
                setHorario();
                markDirty();
            });
        }
        if (fundoidSelect) {
            fundoidSelect.addEventListener('change', () => {
                actualizarDatosFundo();
                markDirty();
            });
        }

        hookGrid();
        addDirtyListeners();
        wireModalClose();
        hideModal('erpModal');
        hideModal('confirmModal');
        setHorario();
        actualizarDatosFundo();
        recalcAllRows();

        const form = document.getElementById('prodlecheForm');
        const allowSubmitFlag = { value: false };

        function submitForm() {
            if (!form) return;
            setHorario();
            recalcTotals();
            allowSubmitFlag.value = true;
            form.requestSubmit();
        }

        if (form) {
            form.addEventListener('submit', (event) => {
                if (!allowSubmitFlag.value) {
                    event.preventDefault();
                }
                allowSubmitFlag.value = false;
            });
        }

        if (openErpBtn) {
            openErpBtn.addEventListener('click', () => {
                if (!fundoidSelect || !fundoidSelect.value) {
                    showToast('Seleccione un fundo para ver la información ERP.', 'warning');
                    return;
                }
                showModal('erpModal');
            });
        }

        if (openConfirmBtn) {
            openConfirmBtn.addEventListener('click', () => {
                setHorario();
                recalcTotals();
                if (!validarFechaNoFuturaFrontend() || !validarDetalleConCantidad() || !validarHoraFinMayorIgual() || !validarErpCampos()) {
                    return;
                }
                fillConfirmSummary();
                showModal('confirmModal');
            });
        }

        if (confirmSaveBtn) {
            confirmSaveBtn.addEventListener('click', () => {
                hideModal('confirmModal');
                submitForm();
            });
        }

        const backLink = document.querySelector('a[href=\"?route=prodleche/listar\"]');
        if (backLink) {
            backLink.addEventListener('click', (event) => {
                const shouldWarn = hasManualChange || hasFormDataEntered();
                if (shouldWarn && !window.confirm('Hay datos sin guardar. ¿Desea salir sin guardar?')) {
                    event.preventDefault();
                }
            });
        }
    })();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
