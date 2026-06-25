<?php
// Listado de ítems de inventario
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Ítems de Inventario</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="invitems">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroInvitemdsc" value="<?= htmlspecialchars($filtros['filtroInvitemdsc'] ?? '') ?>">
            <input type="hidden" name="filtroInvunidmedid" value="<?= htmlspecialchars($filtros['filtroInvunidmedid'] ?? '') ?>">
            <input type="hidden" name="filtroErpinvitemcod" value="<?= htmlspecialchars($filtros['filtroErpinvitemcod'] ?? '') ?>">
            <input type="hidden" name="filtroInvitemleche" value="<?= htmlspecialchars($filtros['filtroInvitemleche'] ?? '') ?>">
            <input type="hidden" name="filtroInvitemusocodigo" value="<?= htmlspecialchars($filtros['filtroInvitemusocodigo'] ?? '') ?>">
            <input type="hidden" name="filtroInvitemactivo" value="<?= htmlspecialchars($filtros['filtroInvitemactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=erpendpoints/diagnostico&endpointCodigo=ERP_PRODUCTOS_LIST" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text"></i> Ver logs
        </a>

        <form action="?route=invitems/sync" method="POST" class="m-0 js-erp-sync-form" data-confirm="1" data-confirm-message="¿Desea sincronizar Productos desde ERP?">
            <?= CsrfHelper::input('web') ?>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-repeat"></i> Sincronizar ERP
            </button>
        </form>
    </div>

    <form action="?route=invitems/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="invitems/listar">
        <div class="col-md-3">
            <input type="text" name="filtroInvitemdsc" class="form-control" placeholder="Descripción"
                   value="<?= htmlspecialchars($filtros['filtroInvitemdsc'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="filtroInvunidmedid" class="form-control" placeholder="Unidad ID"
                   value="<?= htmlspecialchars($filtros['filtroInvunidmedid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="filtroErpinvitemcod" class="form-control" placeholder="ERP Ítem"
                   value="<?= htmlspecialchars($filtros['filtroErpinvitemcod'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroInvitemleche" class="form-select">
                <option value="">Leche</option>
                <option value="1" <?= ($filtros['filtroInvitemleche'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= ($filtros['filtroInvitemleche'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroInvitemusocodigo" class="form-select">
                <option value="">Uso</option>
                <option value="BDG" <?= ($filtros['filtroInvitemusocodigo'] ?? '') === 'BDG' ? 'selected' : '' ?>>BDG</option>
                <option value="LCH" <?= ($filtros['filtroInvitemusocodigo'] ?? '') === 'LCH' ? 'selected' : '' ?>>LCH</option>
                <option value="ALM" <?= ($filtros['filtroInvitemusocodigo'] ?? '') === 'ALM' ? 'selected' : '' ?>>ALM</option>
                <option value="CMB" <?= ($filtros['filtroInvitemusocodigo'] ?? '') === 'CMB' ? 'selected' : '' ?>>CMB</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroInvitemactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroInvitemactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroInvitemactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Descripción</th>
                    <th>Unidad</th>
                    <th>ERP Código</th>
                    <th>Leche</th>
                    <th>Uso</th>
                    <th>Familia</th>
                    <th>Subfamilia</th>
                    <th>Compra</th>
                    <th>Costo Est.</th>
                    <th>Activo</th>
                    <th class="col-actions-xl">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invitems)): ?>
                    <tr><td colspan="12" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($invitems as $i): ?>
                        <tr>
                            <td><?= htmlspecialchars($i['invitemid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($i['invitemdsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($i['invunidmeddsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($i['erpinvitemcod'] ?? '') ?></td>
                            <td><?= !empty($i['invitemleche']) ? '<span class="badge bg-info">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($i['invitemusocodigo'] ?? 'BDG') ?></span></td>
                            <td><?= htmlspecialchars($i['familiadsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($i['subfamiliadsc'] ?? '') ?></td>
                            <td><?= !empty($i['invitemcompra']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            <td><?= htmlspecialchars(number_format((float)($i['invitemcostoestandar'] ?? 0), 4, ',', '.')) ?></td>
                            <td><?= !empty($i['invitemactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=invitems/editar&id=<?= urlencode($i['invitemid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($i['invitemactivo'])): ?>
                                    <form action="?route=invitems/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Desea anular este ítem?">
                                        <input type="hidden" name="invitemid" value="<?= htmlspecialchars($i['invitemid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-circle"></i> Anular
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="erp-sync-loader" class="erp-sync-loader d-none" role="status" aria-live="polite" aria-busy="true">
    <div class="erp-sync-card">
        <div class="spinner-border text-primary" role="presentation"></div>
        <span>Sincronizando con el ERP. Espere...</span>
    </div>
</div>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<script>
    (function () {
        const syncLoader = document.getElementById('erp-sync-loader');
        const syncForms = document.querySelectorAll('.js-erp-sync-form');
        let syncInProgress = false;

        syncForms.forEach(function (syncForm) {
            syncForm.addEventListener('submit', function () {
                if (syncForm.dataset.confirmed !== '1') {
                    return;
                }

                const submitButton = syncForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                }

                if (syncLoader) {
                    syncLoader.classList.remove('d-none');
                }

                syncInProgress = true;
            });
        });

        window.addEventListener('beforeunload', function (event) {
            if (!syncInProgress) {
                return;
            }

            event.preventDefault();
            event.returnValue = 'Hay una sincronización ERP en curso. Cerrar la ventana puede dejar la validación visual incompleta.';
            return event.returnValue;
        });
    })();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
