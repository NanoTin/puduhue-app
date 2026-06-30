
<?php
$isPartial = $partial ?? false;
$empresasOptions = $empresasOptions ?? [];
$fundosOptions = $fundosOptions ?? [];
$invbodegasOptions = $invbodegasOptions ?? [];
$invcateganimalOptions = $invcateganimalOptions ?? [];
$invitemsOptions = $invitemsOptions ?? [];
$formData = $formData ?? ($registro ?? []);
$detallesData = is_array($formData['detalles'] ?? null) ? $formData['detalles'] : [];
$fechaValue = $formData['suplanimalfecha'] ?? '';
if (!empty($fechaValue)) {
    $fechaValue = substr((string)$fechaValue, 0, 10);
}

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<link rel="stylesheet" href="assets/css/frm_ins_upd.css">

<div class="container-fluid px-3 py-3">
    <div class="form-shell">
        <h3 class="mb-3">Editar Suplementacion Animal</h3>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form id="suplanimalForm" method="POST" action="?route=suplanimal/editar">
            <?= CsrfHelper::input('web') ?>
            <input type="hidden" name="suplanimalid" value="<?= htmlspecialchars($formData['suplanimalid'] ?? '') ?>">
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
                        <input type="hidden" name="empresaid" id="empresaid_hidden" value="<?= htmlspecialchars($formData['empresaid'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="fundoid" class="form-label mb-0">Fundo</label>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="openErpModalBtn">Ver info ERP</button>
                        </div>
                        <select name="fundoid" id="fundoid" class="form-select" required>
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
                                >
                                    <?= htmlspecialchars($fundo['fundonombre'] ?? ($fundo['fundoid'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="fundoid" id="fundoid_hidden" value="<?= htmlspecialchars($formData['fundoid'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <label for="invbodegaid" class="form-label">Bodega</label>
                        <select name="invbodegaid" id="invbodegaid" class="form-select" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($invbodegasOptions as $bodega): ?>
                                <?php
                                $bodegaId = $bodega['invbodegaid'] ?? '';
                                $bodegaSelected = (string)($formData['invbodegaid'] ?? '') === (string)$bodegaId ? 'selected' : '';
                                ?>
                                <option
                                    value="<?= htmlspecialchars($bodegaId) ?>"
                                    <?= $bodegaSelected ?>
                                    data-fundoid="<?= htmlspecialchars($bodega['fundoid'] ?? '') ?>"
                                    data-erpinvbodegacod="<?= htmlspecialchars($bodega['erpinvbodegacod'] ?? '') ?>"
                                >
                                    <?= htmlspecialchars($bodega['invbodegadsc'] ?? ($bodega['invbodegaid'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="invbodegaid" id="invbodegaid_hidden" value="<?= htmlspecialchars($formData['invbodegaid'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <label for="suplanimalfecha" class="form-label">Fecha</label>
                        <input
                            type="date"
                            name="suplanimalfecha"
                            id="suplanimalfecha"
                            class="form-control"
                            value="<?= htmlspecialchars($fechaValue) ?>"
                            required
                        >
                    </div>
                    <div class="col-12">
                        <label for="suplanimalobservacion" class="form-label">Observacion</label>
                        <textarea
                            name="suplanimalobservacion"
                            id="suplanimalobservacion"
                            class="form-control"
                            rows="2"
                            maxlength="50"
                            placeholder="Observaciones generales"
                        ><?= htmlspecialchars($formData['suplanimalobservacion'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h5 class="section-title mb-3">Suplementacion por Categoria de Animal</h5>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="detalle_categoria" class="form-label">Categoria Animal</label>
                        <select id="detalle_categoria" class="form-select">
                            <option value="">Seleccione</option>
                            <?php foreach ($invcateganimalOptions as $categoria): ?>
                                <option
                                    value="<?= htmlspecialchars($categoria['invcateganimalid'] ?? '') ?>"
                                    data-erpcod="<?= htmlspecialchars($categoria['erpinvcateganimalcod'] ?? '') ?>"
                                >
                                    <?= htmlspecialchars($categoria['invcateganimaldsc'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="detalle_item" class="form-label">Producto</label>
                        <select id="detalle_item" class="form-select">
                            <option value="">Seleccione</option>
                            <?php foreach ($invitemsOptions as $item): ?>
                                <option
                                    value="<?= htmlspecialchars($item['invitemid'] ?? '') ?>"
                                    data-erpcod="<?= htmlspecialchars($item['erpinvitemcod'] ?? '') ?>"
                                    data-umid="<?= htmlspecialchars($item['invunidmedid'] ?? '') ?>"
                                    data-umdesc="<?= htmlspecialchars($item['invunidmeddsc'] ?? '') ?>"
                                    data-umcod="<?= htmlspecialchars($item['erpunidmedcod'] ?? '') ?>"
                                >
                                    <?= htmlspecialchars($item['invitemdsc'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="detalle_um" class="form-label">U.M</label>
                        <input type="text" id="detalle_um" class="form-control" readonly>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="detalle_totalconsumido" class="form-label">Total Consumido</label>
                        <input type="number" id="detalle_totalconsumido" class="form-control" step="0.01" min="0">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="detalle_totalanimales" class="form-label">Total Animales</label>
                        <input type="number" id="detalle_totalanimales" class="form-control" step="1" min="0">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="detalle_dosis" class="form-label">Dosis por Animal</label>
                        <input type="number" id="detalle_dosis" class="form-control" step="0.01" min="0" readonly>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" id="btnAgregarDetalle">Agregar</button>
                    </div>
                </div>
            </section>
            <section class="mb-4">
                <h5 class="section-title mb-3">Detalle de registros</h5>
                <div class="table-responsive transaction-detail-wrap">
                    <table class="detail-table" id="detalleTable">
                        <thead>
                        <tr>
                            <th class="col-actions-xs"></th>
                            <th class="col-line-sm">Linea</th>
                            <th>Categoria Animal</th>
                            <th>Producto</th>
                            <th>U.M</th>
                            <th>Total Consumido</th>
                            <th>Total Animales</th>
                            <th>Dosis x Animal</th>
                            <th>N Doc ERP</th>
                        </tr>
                        </thead>
                        <tbody id="detalleBody">
                        <?php if (!empty($detallesData)): ?>
                            <?php foreach ($detallesData as $idx => $detalle): ?>
                                <?php
                                $erpDocumentoCod = (string)($detalle['erpdocumentocod'] ?? 'PEND');
                                $permiteEliminar = $erpDocumentoCod === 'PEND';
                                $btnEliminarClass = $permiteEliminar
                                    ? 'btn btn-link text-danger btn-sm btnEliminarDetalle'
                                    : 'btn btn-link text-muted btn-sm btnEliminarDetalle';
                                ?>
                                <tr data-categoria="<?= htmlspecialchars($detalle['invcateganimalid'] ?? '') ?>" data-item="<?= htmlspecialchars($detalle['invitemid'] ?? '') ?>">
                                    <td>
                                        <button
                                            type="button"
                                            class="<?= $btnEliminarClass ?>"
                                            <?= $permiteEliminar ? '' : 'disabled aria-disabled="true" title="No se puede eliminar: documento ERP generado"' ?>
                                        >
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </td>
                                    <td class="detalle-linea"><?= htmlspecialchars($detalle['suplanimallinea'] ?? ($idx + 1)) ?></td>
                                    <td class="detalle-categoria-text"><?= htmlspecialchars($detalle['invcateganimaldsc'] ?? '') ?></td>
                                    <td class="detalle-item-text"><?= htmlspecialchars($detalle['invitemdsc'] ?? '') ?></td>
                                    <td class="detalle-um-text"><?= htmlspecialchars($detalle['invunidmeddsc'] ?? '') ?></td>
                                    <td class="detalle-totalconsumido-text"><?= htmlspecialchars($detalle['totalconsumido'] ?? '') ?></td>
                                    <td class="detalle-totalanimales-text"><?= htmlspecialchars($detalle['totalanimales'] ?? '') ?></td>
                                    <td class="detalle-dosis-text"><?= htmlspecialchars($detalle['dosisporanimal'] ?? '') ?></td>
                                    <td class="detalle-erp-text"><?= htmlspecialchars($detalle['erpdocumentocod'] ?? 'PEND') ?></td>
                                    <td class="d-none">
                                        <input type="hidden" data-field="suplanimallinea" value="<?= htmlspecialchars($detalle['suplanimallinea'] ?? ($idx + 1)) ?>">
                                        <input type="hidden" data-field="invcateganimalid" value="<?= htmlspecialchars($detalle['invcateganimalid'] ?? '') ?>">
                                        <input type="hidden" data-field="sup_erpinvcateganimalcod" value="<?= htmlspecialchars($detalle['sup_erpinvcateganimalcod'] ?? '') ?>">
                                        <input type="hidden" data-field="invitemid" value="<?= htmlspecialchars($detalle['invitemid'] ?? '') ?>">
                                        <input type="hidden" data-field="sup_erpinvitemcod" value="<?= htmlspecialchars($detalle['sup_erpinvitemcod'] ?? '') ?>">
                                        <input type="hidden" data-field="invunidmedid" value="<?= htmlspecialchars($detalle['invunidmedid'] ?? '') ?>">
                                        <input type="hidden" data-field="sup_erpunidmedcod" value="<?= htmlspecialchars($detalle['sup_erpunidmedcod'] ?? '') ?>">
                                        <input type="hidden" data-field="totalconsumido" value="<?= htmlspecialchars($detalle['totalconsumido'] ?? '') ?>">
                                        <input type="hidden" data-field="totalanimales" value="<?= htmlspecialchars($detalle['totalanimales'] ?? '') ?>">
                                        <input type="hidden" data-field="dosisporanimal" value="<?= htmlspecialchars($detalle['dosisporanimal'] ?? '') ?>">
                                        <input type="hidden" data-field="erpdocumentocod" value="<?= htmlspecialchars($detalle['erpdocumentocod'] ?? 'PEND') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="text-muted">
                                <td colspan="9" class="text-center py-3">No hay detalles agregados.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="modal-shell" id="erpModal" aria-hidden="true">
                <div class="modal-backdrop"></div>
                <div class="modal-card">
                    <div class="modal-header">
                        <h6 class="mb-0">Informacion ERP</h6>
                        <button type="button" class="btn btn-icon" data-close-modal="erpModal" aria-label="Cerrar">x</button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="sup_erpestablecimientocod" class="form-label">ERP Establecimiento</label>
                                <input type="text" name="sup_erpestablecimientocod" id="sup_erpestablecimientocod" class="form-control" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="sup_erplotecod" class="form-label">ERP Lote</label>
                                <input type="text" name="sup_erplotecod" id="sup_erplotecod" class="form-control" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="sup_erpinvbodegacod" class="form-label">ERP Bodega</label>
                                <input type="text" name="sup_erpinvbodegacod" id="sup_erpinvbodegacod" class="form-control" readonly>
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
                        <button type="button" class="btn btn-icon" data-close-modal="confirmModal" aria-label="Cerrar">x</button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">Revise los datos antes de guardar.</p>
                        <div class="summary-grid">
                            <div>
                                <span class="summary-label">Fecha</span>
                                <span class="summary-value" id="confirmFecha"></span>
                            </div>
                            <div>
                                <span class="summary-label">Registros</span>
                                <span class="summary-value" id="confirmTotalRegistros"></span>
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

            <div class="modal-shell" id="deleteModal" aria-hidden="true">
                <div class="modal-backdrop"></div>
                <div class="modal-card">
                    <div class="modal-header">
                        <h6 class="mb-0">Eliminar detalle</h6>
                        <button type="button" class="btn btn-icon" data-close-modal="deleteModal" aria-label="Cerrar">x</button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Desea eliminar esta fila?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-close-modal="deleteModal">Cancelar</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="?route=suplanimal/listar" class="btn btn-outline-secondary">Volver</a>
                <button type="button" class="btn btn-primary" id="openConfirmModalBtn">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const fundoSelect = document.getElementById('fundoid');
        const bodegaSelect = document.getElementById('invbodegaid');
        const empresaSelect = document.getElementById('empresaid');
        const empresaHidden = document.getElementById('empresaid_hidden');
        const fundoHidden = document.getElementById('fundoid_hidden');
        const bodegaHidden = document.getElementById('invbodegaid_hidden');
        const fechaInput = document.getElementById('suplanimalfecha');
        const observacionInput = document.getElementById('suplanimalobservacion');

        const erpEstInput = document.getElementById('sup_erpestablecimientocod');
        const erpLoteInput = document.getElementById('sup_erplotecod');
        const erpBodegaInput = document.getElementById('sup_erpinvbodegacod');

        const categoriaSelect = document.getElementById('detalle_categoria');
        const itemSelect = document.getElementById('detalle_item');
        const umInput = document.getElementById('detalle_um');
        const totalConsumidoInput = document.getElementById('detalle_totalconsumido');
        const totalAnimalesInput = document.getElementById('detalle_totalanimales');
        const dosisInput = document.getElementById('detalle_dosis');
        const addBtn = document.getElementById('btnAgregarDetalle');

        const detalleBody = document.getElementById('detalleBody');
        const confirmFecha = document.getElementById('confirmFecha');
        const confirmTotalRegistros = document.getElementById('confirmTotalRegistros');
        const openConfirmBtn = document.getElementById('openConfirmModalBtn');
        const confirmSaveBtn = document.getElementById('confirmSaveBtn');

        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        let rowToDelete = null;

        function showToast(message, type = 'warning') {
            if (window.ToastManager) {
                window.ToastManager.show(message, type);
            }
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

        function refreshBodegas() {
            const fundoId = fundoSelect?.value || '';
            const current = bodegaSelect?.value || '';
            let hasValue = false;
            Array.from(bodegaSelect.options).forEach((opt) => {
                if (!opt.value) return;
                const match = opt.dataset.fundoid === fundoId;
                opt.hidden = !match;
                if (match && current === opt.value) {
                    hasValue = true;
                }
            });
            if (!hasValue) {
                bodegaSelect.value = '';
            }
            syncBodegaHidden();
            updateErpBodega();
        }

        function updateErpFundo() {
            const selected = fundoSelect?.options[fundoSelect.selectedIndex];
            if (!selected) return;
            const empresaId = selected.dataset.empresaid || '';
            if (empresaSelect) empresaSelect.value = empresaId;
            if (empresaHidden) empresaHidden.value = empresaId;
            if (erpEstInput) erpEstInput.value = selected.dataset.erpestablecimientocod || '';
            if (erpLoteInput) erpLoteInput.value = selected.dataset.erplotecod || '';
        }

        function updateErpBodega() {
            const selected = bodegaSelect?.options[bodegaSelect.selectedIndex];
            if (!selected) return;
            if (erpBodegaInput) erpBodegaInput.value = selected.dataset.erpinvbodegacod || '';
        }

        function syncFundoHidden() {
            if (fundoHidden) fundoHidden.value = fundoSelect?.value || '';
        }

        function syncBodegaHidden() {
            if (bodegaHidden) bodegaHidden.value = bodegaSelect?.value || '';
        }

        function updateHeaderLock() {
            const hasRows = detalleBody.querySelectorAll('tr[data-categoria]').length > 0;
            if (fundoSelect) fundoSelect.disabled = hasRows;
            if (bodegaSelect) bodegaSelect.disabled = hasRows;
            if (fechaInput) fechaInput.readOnly = hasRows;
            if (observacionInput) observacionInput.readOnly = hasRows;
        }

        function calculateDosis() {
            const totalConsumido = parseFloat(totalConsumidoInput.value || '0') || 0;
            const totalAnimales = parseFloat(totalAnimalesInput.value || '0') || 0;
            const dosis = totalAnimales > 0 ? totalConsumido / totalAnimales : 0;
            dosisInput.value = totalAnimales > 0 ? dosis.toFixed(2) : '';
        }

        function clearDetalleInputs() {
            categoriaSelect.value = '';
            itemSelect.value = '';
            umInput.value = '';
            totalConsumidoInput.value = '';
            totalAnimalesInput.value = '';
            dosisInput.value = '';
        }

        function rebuildRows() {
            const rows = detalleBody.querySelectorAll('tr[data-categoria]');
            rows.forEach((row, index) => {
                const line = index + 1;
                row.querySelector('.detalle-linea').textContent = line;
                row.querySelectorAll('input[data-field]').forEach((input) => {
                    const field = input.dataset.field;
                    if (field === 'suplanimallinea') {
                        input.value = line;
                    }
                    input.name = `detalles[${index}][${field}]`;
                });
            });
            updateHeaderLock();
        }

        function addDetalleRow() {
            const empresaId = empresaHidden?.value || empresaSelect?.value || '';
            const fundoId = fundoSelect?.value || fundoHidden?.value || '';
            const bodegaId = bodegaSelect?.value || bodegaHidden?.value || '';

            if (!empresaId || !fundoId || !bodegaId) {
                showToast('Debe seleccionar empresa, fundo y bodega antes de agregar.', 'warning');
                return;
            }

            const categoriaId = categoriaSelect.value;
            const categoriaText = categoriaSelect.options[categoriaSelect.selectedIndex]?.text || '';
            const categoriaErp = categoriaSelect.options[categoriaSelect.selectedIndex]?.dataset.erpcod || '';

            const itemId = itemSelect.value;
            const itemText = itemSelect.options[itemSelect.selectedIndex]?.text || '';
            const itemErp = itemSelect.options[itemSelect.selectedIndex]?.dataset.erpcod || '';
            const umId = itemSelect.options[itemSelect.selectedIndex]?.dataset.umid || '';
            const umText = itemSelect.options[itemSelect.selectedIndex]?.dataset.umdesc || '';
            const umCod = itemSelect.options[itemSelect.selectedIndex]?.dataset.umcod || '';

            const totalConsumido = parseFloat(totalConsumidoInput.value || '0') || 0;
            const totalAnimales = parseInt(totalAnimalesInput.value || '0', 10) || 0;
            const dosis = totalAnimales > 0 ? totalConsumido / totalAnimales : 0;

            if (!categoriaId || !itemId || !umId) {
                showToast('Debe seleccionar categoria y producto validos.', 'warning');
                return;
            }
            if (totalConsumido <= 0 || totalAnimales <= 0) {
                showToast('Los totales deben ser mayores a cero.', 'warning');
                return;
            }
            if (dosis <= 0) {
                showToast('La dosis debe ser mayor a cero.', 'warning');
                return;
            }
            if (!categoriaErp || !itemErp || !umCod) {
                showToast('Faltan codigos ERP en la categoria o producto.', 'danger');
                return;
            }

            const duplicate = Array.from(detalleBody.querySelectorAll('tr[data-categoria]')).some((row) => {
                return row.dataset.categoria === categoriaId && row.dataset.item === itemId;
            });
            if (duplicate) {
                showToast('No se permiten duplicados de categoria y producto.', 'warning');
                return;
            }

            const emptyRow = detalleBody.querySelector('tr.text-muted');
            if (emptyRow) {
                emptyRow.remove();
            }

            const row = document.createElement('tr');
            row.dataset.categoria = categoriaId;
            row.dataset.item = itemId;
            row.innerHTML = `
                <td>
                    <button type="button" class="btn btn-link text-danger btn-sm btnEliminarDetalle">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </td>
                <td class="detalle-linea"></td>
                <td class="detalle-categoria-text">${categoriaText}</td>
                <td class="detalle-item-text">${itemText}</td>
                <td class="detalle-um-text">${umText}</td>
                <td class="detalle-totalconsumido-text">${totalConsumido.toFixed(2)}</td>
                <td class="detalle-totalanimales-text">${totalAnimales}</td>
                <td class="detalle-dosis-text">${dosis.toFixed(2)}</td>
                <td class="detalle-erp-text">PEND</td>
                <td class="d-none">
                    <input type="hidden" data-field="suplanimallinea" value="">
                    <input type="hidden" data-field="invcateganimalid" value="${categoriaId}">
                    <input type="hidden" data-field="sup_erpinvcateganimalcod" value="${categoriaErp}">
                    <input type="hidden" data-field="invitemid" value="${itemId}">
                    <input type="hidden" data-field="sup_erpinvitemcod" value="${itemErp}">
                    <input type="hidden" data-field="invunidmedid" value="${umId}">
                    <input type="hidden" data-field="sup_erpunidmedcod" value="${umCod}">
                    <input type="hidden" data-field="totalconsumido" value="${totalConsumido.toFixed(2)}">
                    <input type="hidden" data-field="totalanimales" value="${totalAnimales}">
                    <input type="hidden" data-field="dosisporanimal" value="${dosis.toFixed(2)}">
                    <input type="hidden" data-field="erpdocumentocod" value="PEND">
                </td>
            `;
            detalleBody.appendChild(row);
            rebuildRows();
            clearDetalleInputs();
        }

        detalleBody.addEventListener('click', (event) => {
            const btn = event.target.closest('.btnEliminarDetalle');
            if (!btn) return;
            rowToDelete = btn.closest('tr');
            openModal('deleteModal');
        });

        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                if (rowToDelete) {
                    rowToDelete.remove();
                    rowToDelete = null;
                    if (!detalleBody.querySelector('tr[data-categoria]')) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.className = 'text-muted';
                        emptyRow.innerHTML = '<td colspan="9" class="text-center py-3">No hay detalles agregados.</td>';
                        detalleBody.appendChild(emptyRow);
                    }
                    rebuildRows();
                }
                closeModal('deleteModal');
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', addDetalleRow);
        }

        if (totalConsumidoInput) totalConsumidoInput.addEventListener('input', calculateDosis);
        if (totalAnimalesInput) totalAnimalesInput.addEventListener('input', calculateDosis);
        if (itemSelect) {
            itemSelect.addEventListener('change', () => {
                const selected = itemSelect.options[itemSelect.selectedIndex];
                umInput.value = selected?.dataset.umdesc || '';
            });
        }

        if (fundoSelect) {
            fundoSelect.addEventListener('change', () => {
                updateErpFundo();
                refreshBodegas();
                syncFundoHidden();
            });
        }

        if (bodegaSelect) {
            bodegaSelect.addEventListener('change', () => {
                updateErpBodega();
                syncBodegaHidden();
            });
        }

        if (openConfirmBtn) {
            openConfirmBtn.addEventListener('click', () => {
                const registros = detalleBody.querySelectorAll('tr[data-categoria]').length;
                if (confirmFecha) confirmFecha.textContent = fechaInput?.value || '';
                if (confirmTotalRegistros) confirmTotalRegistros.textContent = String(registros);
                openModal('confirmModal');
            });
        }

        if (confirmSaveBtn) {
            confirmSaveBtn.addEventListener('click', () => {
                document.getElementById('suplanimalForm')?.requestSubmit();
            });
        }

        const openErpBtn = document.getElementById('openErpModalBtn');
        if (openErpBtn) {
            openErpBtn.addEventListener('click', () => openModal('erpModal'));
        }

        bindModalButtons();
        updateErpFundo();
        refreshBodegas();
        updateErpBodega();
        syncFundoHidden();
        syncBodegaHidden();
        rebuildRows();
    })();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
