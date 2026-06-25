<?php
// Listado de unidades de medida
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Unidades de Medida</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="invunidmed">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroInvunidmeddsc" value="<?= htmlspecialchars($filtros['filtroInvunidmeddsc'] ?? '') ?>">
            <input type="hidden" name="filtroErpunidmedcod" value="<?= htmlspecialchars($filtros['filtroErpunidmedcod'] ?? '') ?>">
            <input type="hidden" name="filtroInvunidmedactivo" value="<?= htmlspecialchars($filtros['filtroInvunidmedactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=erpendpoints/diagnostico&endpointCodigo=ERP_UNIDADES_MEDIDA_LIST" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-journal-text"></i> Ver logs
        </a>

        <form action="?route=invunidmed/sync" method="POST" class="m-0 js-erp-sync-form" data-confirm="1" data-confirm-message="¿Desea sincronizar Unidades de Medida desde ERP?">
            <?= CsrfHelper::input('web') ?>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-arrow-repeat"></i> Sincronizar ERP
            </button>
        </form>
    </div>

    <form id="invunidmed-filter-form" action="?route=invunidmed/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="invunidmed/listar">
        <div class="col-md-4">
            <input type="text" name="filtroInvunidmeddsc" class="form-control" placeholder="Descripcion"
                   value="<?= htmlspecialchars($filtros['filtroInvunidmeddsc'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="filtroErpunidmedcod" class="form-control" placeholder="Codigo ERP"
                   value="<?= htmlspecialchars($filtros['filtroErpunidmedcod'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="filtroInvunidmedactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroInvunidmedactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroInvunidmedactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-invunidmed" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Descripcion</th>
                    <th>Codigo ERP</th>
                    <th>Activo</th>
                    <th class="col-actions-lg">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invunidmed)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($invunidmed as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['invunidmedid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['invunidmeddsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['erpunidmedcod'] ?? '') ?></td>
                            <td><?= !empty($u['invunidmedactivo']) ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <span class="text-muted">Bloqueado (ERP)</span>
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

<?php if (!$isPartial) { require 'footer.php'; } ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('invunidmed-filter-form');
        const clearBtn = document.getElementById('btn-clear-invunidmed');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], input[type="number"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if(form) {
            const autokey = 'invunidmedAutoSearch';
            if(!sessionStorage.getItem(autokey)) {
                sessionStorage.setItem(autokey, '1');
                form.requestSubmit();
            }else {
                sessionStorage.removeItem(autokey);
            }
        }

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
    });
</script>
