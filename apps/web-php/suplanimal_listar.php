
<?php
// Listado de Suplementacion Animal
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$formatDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    try {
        return (new DateTime($value))->format('d-m-Y');
    } catch (Exception $e) {
        return (string)$value;
    }
};

$today = new DateTime('today');
$defaultFechaHasta = $today->format('Y-m-d');
$defaultFechaDesde = (clone $today)->modify('-15 days')->format('Y-m-d');

$fechaDesdeValue = $filtros['filtroFechaDesde'] ?? '';
if ($fechaDesdeValue === '') {
    $fechaDesdeValue = $defaultFechaDesde;
}
$fechaHastaValue = $filtros['filtroFechaHasta'] ?? '';
if ($fechaHastaValue === '') {
    $fechaHastaValue = $defaultFechaHasta;
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Suplementacion Animal</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="suplanimal">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroEmpresaid" value="<?= htmlspecialchars($empresaIdWS ?? '') ?>">
            <input type="hidden" name="filtroFundoid" value="<?= htmlspecialchars($filtros['filtroFundoid'] ?? '') ?>">
            <input type="hidden" name="filtroSuplanimalestatus" value="<?= htmlspecialchars($filtros['filtroSuplanimalestatus'] ?? '') ?>">
            <input type="hidden" name="filtroInvbodegaid" value="<?= htmlspecialchars($filtros['filtroInvbodegaid'] ?? '') ?>">
            <input type="hidden" name="filtroFechaDesde" value="<?= htmlspecialchars($filtros['filtroFechaDesde'] ?? '') ?>">
            <input type="hidden" name="filtroFechaHasta" value="<?= htmlspecialchars($filtros['filtroFechaHasta'] ?? '') ?>">
            <input type="hidden" name="filtroSuplanimalobservacion" value="<?= htmlspecialchars($filtros['filtroSuplanimalobservacion'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=suplanimal/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Registro
        </a>
    </div>

    <form id="suplanimal-filter-form" action="?route=suplanimal/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="suplanimal/listar">
        <div class="col-md-2" style="display: none;">
            <input type="number" name="filtroEmpresaid" class="form-control" placeholder="Empresa ID"
                   value="<?= htmlspecialchars($empresaIdWS ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroFundoid" class="form-select" >
                <option value="">Fundo</option>
                <?php foreach (($fundosOptions ?? []) as $fundoOpt): ?>
                    <option value="<?= htmlspecialchars($fundoOpt['fundoid']) ?>"
                        <?= ($filtros['filtroFundoid'] ?? '') == ($fundoOpt['fundoid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fundoOpt['fundonombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2" style="display: none;">
            <input type="number" name="filtroInvbodegaid" class="form-control" placeholder="Bodega ID"
                   value="<?= htmlspecialchars($filtros['filtroInvbodegaid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroSuplanimalestatus" class="form-select" >
                <option value="">Estado</option>
                <option value="CN" <?= ($filtros['filtroSuplanimalestatus'] ?? '') === 'CN' ? 'selected' : '' ?>>Confirmada</option>
                <option value="ANL" <?= ($filtros['filtroSuplanimalestatus'] ?? '') === 'ANL' ? 'selected' : '' ?>>Anulada</option>
                <option value="PND" <?= ($filtros['filtroSuplanimalestatus'] ?? '') === 'PND' ? 'selected' : '' ?>>Pendiente</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="filtroFechaDesde" class="form-control"
                   value="<?= htmlspecialchars($fechaDesdeValue) ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="filtroFechaHasta" class="form-control"
                   value="<?= htmlspecialchars($fechaHastaValue) ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="filtroSuplanimalobservacion" class="form-control" placeholder="Observacion"
                   value="<?= htmlspecialchars($filtros['filtroSuplanimalobservacion'] ?? '') ?>">
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-suplanimal" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <!--<th>Empresa</th>-->
                    <th>Fundo</th>
                    <th>Bodega</th>
                    <th>Fecha</th>
                    <th>Observacion</th>
                    <th>Cant. Detalles</th>
                    <th>Pend. ERP</th>
                    <th>Estado</th>
                    <th style="width: 190px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suplanimal)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($suplanimal as $s): ?>
                        <?php $estatus = $s['suplanimalstatus'] ?? ''; ?>
                        <?php $isConfirmada = $estatus === 'CN'; ?>
                        <?php $isAnulada = $estatus === 'ANL'; ?>
                        <?php $cntdetalles = (int)($s['cant_detalles'] ?? 0); ?>
                        <?php $cntpnderp = (int)($s['cant_detalles_pend_erp'] ?? 0); ?>
                        <tr>
                            <td><?= htmlspecialchars($s['suplanimalid'] ?? '') ?></td>
                            <!--<td><?= htmlspecialchars($s['empresa'] ?? $s['empresaid'] ?? '') ?></td>-->
                            <td><?= htmlspecialchars($s['fundonombre'] ?? $s['fundoid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($s['invbodegadsc'] ?? $s['invbodegaid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($formatDate($s['suplanimalfecha'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($s['suplanimalobservacion'] ?? '') ?></td>
                            <td style="text-align:right"><?= htmlspecialchars($s['cant_detalles'] ?? '') ?></td>
                            <td style="text-align:right"><?= htmlspecialchars($s['cant_detalles_pend_erp'] ?? '') ?></td>
                            <td><?= htmlspecialchars($estatus) ?></td>
                            <td>
                                <?php if ($isConfirmada || $isAnulada): ?>
                                    <button class="btn btn-secondary btn-sm" disabled title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                <?php else: ?>
                                    <a class="btn btn-warning btn-sm" href="?route=suplanimal/editar&id=<?= urlencode($s['suplanimalid'] ?? '') ?>" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                <?php endif; ?>
                                <a class="btn btn-info btn-sm" href="?route=suplanimal/visualizar&id=<?= urlencode($s['suplanimalid'] ?? '') ?>" title="Ver" aria-label="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if  ($isConfirmada || $isAnulada || ($estatus === 'PND' && $cntdetalles != $cntpnderp)): ?> 
                                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Anular" aria-label="Anular">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php elseif (!$isConfirmada && !empty($estatus) && $estatus !== 'ANL'): ?>
                                    <form action="?route=suplanimal/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="suplanimalid" value="<?= htmlspecialchars($s['suplanimalid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea anular el registro Nro. <?= htmlspecialchars($s['suplanimalid'] ?? '') ?>?');" title="Anular" aria-label="Anular">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <!-- Boton para sincronizar con el ERP siempre y cuando el estado sea PND. Si no, boton deshabilitado -->
                                <?php if (!empty($s['suplanimalstatus']) && $s['suplanimalstatus'] === 'PND'): ?>
                                    <form action="?route=suplanimal/sync" method="POST" class="d-inline">
                                        <input type="hidden" name="suplanimalid" value="<?= htmlspecialchars($s['suplanimalid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('¿Desea sincronizar este registro?');" <?= (!empty($s['suplanimalstatus']) && $s['suplanimalstatus'] === 'PND') ? '' : 'disabled' ?> title="Sincronizar ERP" aria-label="Sincronizar ERP">
                                            <i class="bi bi-arrow-repeat"></i>
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

<?php if (!$isPartial) { require 'footer.php'; } ?>
<div id="erp-sync-loader" class="erp-sync-loader d-none" role="status" aria-live="polite" aria-busy="true">
    <div class="erp-sync-card">
        <div class="spinner-border text-primary" role="presentation"></div>
        <span>Sincronizando con el ERP. Espere...</span>
    </div>
</div>
<style>
    .erp-sync-loader {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1055;
    }
    .erp-sync-card {
        background: #ffffff;
        color: #111111;
        padding: 14px 18px;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('suplanimal-filter-form');
        const clearBtn = document.getElementById('btn-clear-suplanimal');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], input[type="number"], input[type="date"]').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'suplanimalAutoSearch';
            const submitForm = () => form.requestSubmit();

            if (window.__hasToast) {
                sessionStorage.removeItem(autoKey);
                return;
            }

            if (!sessionStorage.getItem(autoKey)) {
                sessionStorage.setItem(autoKey, '1');
                submitForm();
            } else {
                sessionStorage.removeItem(autoKey);
            }
        }

        const syncLoader = document.getElementById('erp-sync-loader');
        const syncForms = document.querySelectorAll('form[action*="suplanimal/sync"]');
        if (syncLoader && syncForms.length) {
            syncForms.forEach(function (syncForm) {
                syncForm.addEventListener('submit', function () {
                    const submitButton = syncForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                    }
                    syncLoader.classList.remove('d-none');
                });
            });
        }
    });
</script>
