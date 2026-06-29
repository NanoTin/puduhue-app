<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Centros de Costo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="?route=erpendpoints/diagnostico&endpointCodigo=ERP_CENTROS_COSTOS_LIST" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text"></i> Ver logs
        </a>

        <form action="?route=centroscosto/sync" method="POST" class="m-0 js-erp-sync-form" data-confirm="1" data-confirm-message="¿Desea sincronizar Centros de Costo desde ERP?">
            <?= CsrfHelper::input('web') ?>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-repeat"></i> Sincronizar ERP
            </button>
        </form>
    </div>

    <form action="?route=centroscosto/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="centroscosto/listar">
        <div class="col-md-3">
            <input type="text" name="filtroCentrocostocod" class="form-control" placeholder="Código"
                   value="<?= htmlspecialchars($filtros['filtroCentrocostocod'] ?? '') ?>">
        </div>
        <div class="col-md-5">
            <input type="text" name="filtroCentrocostodsc" class="form-control" placeholder="Nombre o descripción"
                   value="<?= htmlspecialchars($filtros['filtroCentrocostodsc'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroCentrocostoactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroCentrocostoactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroCentrocostoactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="alert alert-info" role="alert">
        Los campos espejo ERP son de solo lectura. Desde esta pantalla solo se administran los atributos locales de jefatura.
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Código ERP</th>
                    <th>Jefe Centro</th>
                    <th>Jefe Técnico</th>
                    <th>Activo</th>
                    <th>Últ. Sync</th>
                    <th class="col-actions-md">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($centroscosto)): ?>
                    <tr><td colspan="9" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($centroscosto as $centro): ?>
                        <tr>
                            <td><?= htmlspecialchars($centro['centrocostoid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($centro['centrocostocod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($centro['centrocostodsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($centro['erpcentrocostocod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($centro['centrocostojefeusuarionombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($centro['centrocostojefetecniconombre'] ?? '') ?></td>
                            <td><?= !empty($centro['centrocostoactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td><?= htmlspecialchars($centro['sincfechahora'] ?? '') ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=centroscosto/editar&id=<?= urlencode($centro['centrocostoid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
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
