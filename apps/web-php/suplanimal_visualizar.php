
<?php
$isPartial = $partial ?? false;
$empresasOptions = $empresasOptions ?? [];
$fundosOptions = $fundosOptions ?? [];
$invbodegasOptions = $invbodegasOptions ?? [];
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
        <h3 class="mb-3">Ver Suplementacion Animal</h3>

        <form id="suplanimalForm" method="POST" action="#" onsubmit="return false;">
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
                                >
                                    <?= htmlspecialchars($fundo['fundonombre'] ?? ($fundo['fundoid'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <label for="invbodegaid" class="form-label">Bodega</label>
                        <select name="invbodegaid" id="invbodegaid" class="form-select" disabled>
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
                    </div>
                    <div class="col-12 col-lg-6 col-xl-3">
                        <label for="suplanimalfecha" class="form-label">Fecha</label>
                        <input
                            type="date"
                            name="suplanimalfecha"
                            id="suplanimalfecha"
                            class="form-control"
                            value="<?= htmlspecialchars($fechaValue) ?>"
                            readonly
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
                            readonly
                        ><?= htmlspecialchars($formData['suplanimalobservacion'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>

            <section class="mb-4">
                <h5 class="section-title mb-3">Detalle de registros</h5>
                <div class="table-responsive">
                    <table class="detail-table" id="detalleTable">
                        <thead>
                        <tr>
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
                                <tr data-categoria="<?= htmlspecialchars($detalle['invcateganimalid'] ?? '') ?>" data-item="<?= htmlspecialchars($detalle['invitemid'] ?? '') ?>">
                                    <td class="detalle-linea"><?= htmlspecialchars($detalle['suplanimallinea'] ?? ($idx + 1)) ?></td>
                                    <td class="detalle-categoria-text"><?= htmlspecialchars($detalle['invcateganimaldsc'] ?? '') ?></td>
                                    <td class="detalle-item-text"><?= htmlspecialchars($detalle['invitemdsc'] ?? '') ?></td>
                                    <td class="detalle-um-text"><?= htmlspecialchars($detalle['invunidmeddsc'] ?? '') ?></td>
                                    <td class="detalle-totalconsumido-text"><?= htmlspecialchars($detalle['totalconsumido'] ?? '') ?></td>
                                    <td class="detalle-totalanimales-text"><?= htmlspecialchars($detalle['totalanimales'] ?? '') ?></td>
                                    <td class="detalle-dosis-text"><?= htmlspecialchars($detalle['dosisporanimal'] ?? '') ?></td>
                                    <td class="detalle-erp-text"><?= htmlspecialchars($detalle['erpdocumentocod'] ?? 'PEND') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="text-muted">
                                <td colspan="8" class="text-center py-3">No hay detalles agregados.</td>
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
                                <input
                                    type="text"
                                    name="sup_erpestablecimientocod"
                                    id="sup_erpestablecimientocod"
                                    class="form-control"
                                    value="<?= htmlspecialchars($formData['sup_erpestablecimientocod'] ?? '') ?>"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="sup_erplotecod" class="form-label">ERP Lote</label>
                                <input
                                    type="text"
                                    name="sup_erplotecod"
                                    id="sup_erplotecod"
                                    class="form-control"
                                    value="<?= htmlspecialchars($formData['sup_erplotecod'] ?? '') ?>"
                                    readonly
                                >
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="sup_erpinvbodegacod" class="form-label">ERP Bodega</label>
                                <input
                                    type="text"
                                    name="sup_erpinvbodegacod"
                                    id="sup_erpinvbodegacod"
                                    class="form-control"
                                    value="<?= htmlspecialchars($formData['sup_erpinvbodegacod'] ?? '') ?>"
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

            <div class="d-flex gap-2 justify-content-end">
                <a href="?route=suplanimal/listar" class="btn btn-outline-secondary">Volver</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const openErpBtn = document.getElementById('openErpModalBtn');

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
        if (openErpBtn) {
            openErpBtn.addEventListener('click', () => openModal('erpModal'));
        }

        bindModalButtons();
    })();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
