<?php
// Listado de Producción de Leche
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

// Helpers to format dates and integers for display
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

$formatIntCl = static function ($value): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, 0, ',', '.');
};

$formatFloatCl = static function ($value, int $decimals = 2): string {
    if ($value === null || $value === '') {
        return '';
    }
    if (!is_numeric($value)) {
        return (string)$value;
    }
    return number_format((float)$value, $decimals, ',', '.');
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
    <h3 class="mb-4">Producción de Leche</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <?php $isRootUser = strtoupper($user['usuarioCod'] ?? '') === 'ROOT'; ?>
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="prodleche">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroProdlecheid" value="<?= htmlspecialchars($filtros['filtroProdlecheid'] ?? '') ?>">
            <input type="hidden" name="filtroProdlechestatus" value="<?= htmlspecialchars($filtros['filtroProdlechestatus'] ?? '') ?>">
            <input type="hidden" name="filtroEmpresaid" value="<?= htmlspecialchars($empresaIdWS ?? '') ?>">
            <input type="hidden" name="filtroFundoid" value="<?= htmlspecialchars($filtros['filtroFundoid'] ?? '') ?>">
            <input type="hidden" name="filtroFechaDesde" value="<?= htmlspecialchars($fechaDesdeValue) ?>">
            <input type="hidden" name="filtroFechaHasta" value="<?= htmlspecialchars($fechaHastaValue) ?>">
            <input type="hidden" name="filtroProdlecheobservacion" value="<?= htmlspecialchars($filtros['filtroProdlecheobservacion'] ?? '') ?>">
            <input type="hidden" name="filtroProdlechehorario" value="<?= htmlspecialchars($filtros['filtroProdlechehorario'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <div class="d-flex gap-2 flex-wrap">
            <?php if ($isRootUser): ?>
                <button type="button" id="btn-prodleche-carga" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-arrow-up"></i> Carga masiva (Excel)
                </button>
            <?php endif; ?>
            <a href="?route=prodleche/crear" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Crear Registro
            </a>
        </div>
    </div>

    <form id="prodleche-filter-form" action="?route=prodleche/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="prodleche/listar">
        <div class="col-md-2">
            <input type="text" name="filtroProdlecheid" class="form-control" placeholder="ID"
                   value="<?= htmlspecialchars($filtros['filtroProdlecheid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroProdlechestatus" class="form-select">
                <option value="">Estatus</option>
                <option value="CN" <?= ($filtros['filtroProdlechestatus'] ?? '') === 'CN' ? 'selected' : '' ?>>Confirmada</option>
                <option value="ANL" <?= ($filtros['filtroProdlechestatus'] ?? '') === 'ANL' ? 'selected' : '' ?>>Anulada</option>
                <option value="PND" <?= ($filtros['filtroProdlechestatus'] ?? '') === 'PND' ? 'selected' : '' ?>>Pendiente</option>
                <option value="HST" <?= ($filtros['filtroProdlechestatus'] ?? '') === 'HST' ? 'selected' : '' ?>>Histórica</option>
            </select>
        </div>
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
        <div class="col-md-2">
            <input type="date" name="filtroFechaDesde" class="form-control"
                   value="<?= htmlspecialchars($fechaDesdeValue) ?>">
        </div>
        <div class="col-md-2">
            <input type="date" name="filtroFechaHasta" class="form-control"
                   value="<?= htmlspecialchars($fechaHastaValue) ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="filtroProdlecheobservacion" class="form-control" placeholder="Observación"
                   value="<?= htmlspecialchars($filtros['filtroProdlecheobservacion'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroProdlechehorario" class="form-select">
                <option value="">Horario</option>
                <option value="AM" <?= ($filtros['filtroProdlechehorario'] ?? '') === 'AM' ? 'selected' : '' ?>>Mañana</option>
                <option value="PM" <?= ($filtros['filtroProdlechehorario'] ?? '') === 'PM' ? 'selected' : '' ?>>Tarde</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-menus" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Estatus</th>
                    <!-- <th>Empresa</th> -->
                    <th>Fundo</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Tot Litros</th>
                    <th>Tot Vacas</th>
                    <th>Planta Litros</th>
                    <th>Planta Vacas</th>
                    <th>Planta Lts/Vacas</th>
                    <th>Observación</th>
                    <th style="width: 190px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prodleche)): ?>
                    <tr><td colspan="11" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($prodleche as $p): ?>
                        <?php $estatus = $p['prodlechestatus'] ?? ''; ?>
                        <?php $isConfirmada = $estatus === 'CN'; ?>
                        <?php $isHistorica = $estatus === 'HST'; ?>
                        <?php $isAnulada = $estatus === 'ANL'; ?>
                        <?php $isPendiente = $estatus === 'PND'; ?>
                        <tr>
                            <td><?= htmlspecialchars($formatIntCl($p['prodlecheid'] ?? null)) ?></td>
                            <td>
                                <?php if ($isAnulada): ?>
                                    <span class="badge bg-danger text-white px-3 py-2 fw-semibold rounded-pill">ANL</span>
                                <?php elseif ($isConfirmada): ?>
                                    <span class="badge bg-success text-white px-3 py-2 fw-semibold rounded-pill">CN</span>
                                <?php elseif ($isPendiente): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 fw-semibold rounded-pill">PND</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($estatus) ?>
                                <?php endif; ?>
                            </td>
                            <!-- <td><?= htmlspecialchars($p['razonsocial'] ?? null) ?></td> -->
                            <td><?= htmlspecialchars($p['fundonombre'] ?? null) ?></td>
                            <td><?= htmlspecialchars($formatDate($p['prodlechefecha'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($p['prodlechehorario'] ?? '') ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['prodlechetotlitros'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['prodlechetotvacas'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['prodlecheventatotlitros'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatIntCl($p['prodlecheventatotvacas'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($formatFloatCl($p['prodlecheventalitrosxvaca'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($p['prodlecheobservacion'] ?? '') ?></td>
                            <td>
                                <?php if ($isConfirmada || $isHistorica || $isAnulada): ?>
                                    <button class="btn btn-secondary btn-sm" disabled title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                <?php else: ?>
                                    <a class="btn btn-warning btn-sm" href="?route=prodleche/editar&id=<?= urlencode($p['prodlecheid'] ?? '') ?>" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                <?php endif; ?>
                                <!-- Boton para visualizar el registro -->
                                <a class="btn btn-info btn-sm" href="?route=prodleche/visualizar&id=<?= urlencode($p['prodlecheid'] ?? '') ?>" title="Ver" aria-label="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (!$isConfirmada && !empty($estatus) && !$isAnulada && $estatus !== 'HST'): ?>
                                    <form action="?route=prodleche/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="prodlecheid" value="<?= htmlspecialchars($p['prodlecheid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea anular este registro?');" title="Anular" aria-label="Anular">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                <?php elseif ($isConfirmada || $isHistorica): ?>
                                    <button type="button" class="btn btn-secondary btn-sm" disabled title="Anular" aria-label="Anular">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php endif; ?>
                                <!-- Boton para sincronizar con el ERP siempre y cuando el estado sea PND. Si no, boton deshabilitado -->
                                <?php if (!empty($p['prodlechestatus']) && $p['prodlechestatus'] === 'PND'): ?>
                                    <form action="?route=prodleche/sync" method="POST" class="d-inline">
                                        <input type="hidden" name="prodlecheid" value="<?= htmlspecialchars($p['prodlecheid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('?Desea sincronizar este registro?');" <?= (!empty($p['prodlechestatus']) && $p['prodlechestatus'] === 'PND') ? '' : 'disabled' ?> title="Sincronizar ERP" aria-label="Sincronizar ERP">
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

<?php if ($isRootUser): ?>
    <div class="modal fade" id="prodlecheCargaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Carga masiva desde Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="prodlecheExcelInput" class="form-label">Archivo Excel (.xlsx / .csv)</label>
                        <input type="file" class="form-control" id="prodlecheExcelInput" accept=".xlsx,.xls,.csv">
                    </div>
                    <div id="prodlecheCargaResumen" class="alert alert-info d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btn-prodleche-procesar">Procesar</button>
                </div>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<?php endif; ?>

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
        const form = document.getElementById('prodleche-filter-form');
        const clearBtn = document.getElementById('btn-clear-prodleche');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'prodlecheAutoSearch';
            const submitForm = () => form.requestSubmit();

            // When a toast is present, delay the auto-search so the toast renders first
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

        const cargaBtn = document.getElementById('btn-prodleche-carga');
        const cargaModalEl = document.getElementById('prodlecheCargaModal');
        const excelInput = document.getElementById('prodlecheExcelInput');
        const procesarBtn = document.getElementById('btn-prodleche-procesar');
        const resumenEl = document.getElementById('prodlecheCargaResumen');
        const confirmModalEl = document.getElementById('confirmSubmitModal');
        const confirmBodyEl = document.getElementById('confirmSubmitModalBody');
        const confirmOkBtn = document.getElementById('confirmSubmitModalBtnOk');
        let pendingConfirmAction = null;

        const showResumen = (html, variant = 'info') => {
            if (!resumenEl) return;
            resumenEl.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-warning', 'alert-danger');
            resumenEl.classList.add(`alert-${variant}`);
            resumenEl.innerHTML = html;
        };

        const clearResumen = () => {
            if (!resumenEl) return;
            resumenEl.classList.add('d-none');
            resumenEl.innerHTML = '';
        };

        if (confirmOkBtn) {
            confirmOkBtn.addEventListener('click', function () {
                if (typeof pendingConfirmAction === 'function') {
                    const action = pendingConfirmAction;
                    pendingConfirmAction = null;
                    action();
                }
            });
        }

        const requestConfirm = (message, onConfirm) => {
            if (confirmBodyEl) {
                confirmBodyEl.textContent = message;
            }
            pendingConfirmAction = onConfirm;
            if (confirmModalEl) {
                bootstrap.Modal.getOrCreateInstance(confirmModalEl).show();
            } else if (typeof onConfirm === 'function') {
                onConfirm();
            }
        };

        const procesarCargaMasiva = async () => {
            if (!excelInput || !excelInput.files || excelInput.files.length === 0) {
                if (window.ToastManager) {
                    ToastManager.show('Seleccione un archivo Excel antes de procesar.', 'warning');
                }
                return;
            }

            const file = excelInput.files[0];
            const formData = new FormData();
            formData.append('prodleche_excel', file);

            if (procesarBtn) {
                procesarBtn.disabled = true;
                procesarBtn.textContent = 'Procesando...';
            }

            try {
                const response = await fetch('?route=prodleche/carga_masiva', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta inesperada (${response.status}). ${text.substring(0, 120)}`);
                }
                const data = await response.json();

                const status = Number(data.status || 500);
                const totEnc = Number(data.totEncInsertados || 0);
                const totDet = Number(data.totDetInsertados || 0);
                const errores = Array.isArray(data.errores) ? data.errores : [];

                const resumenParts = [
                    `<strong>Encabezados creados:</strong> ${totEnc}`,
                    `<strong>Detalles insertados:</strong> ${totDet}`,
                ];

                let errorsHtml = '';
                if (errores.length > 0) {
                    const items = errores
                        .map((err) => {
                            const key = err.key ?? '';
                            const mensaje = err.mensaje ?? '';
                            const safeKey = window.ToastManager ? ToastManager.escape(key) : key;
                            const safeMsg = window.ToastManager ? ToastManager.escape(mensaje) : mensaje;
                            return `<li><strong>${safeKey}</strong>: ${safeMsg}</li>`;
                        })
                        .join('');
                    errorsHtml = `<hr><div class="mb-1"><strong>Errores:</strong></div><ul class="mb-0">${items}</ul>`;
                }

                const resumenHtml = `<div>${resumenParts.join('<br>')}</div>${errorsHtml}`;
                showResumen(resumenHtml, status === 200 ? 'success' : 'warning');

                if (window.ToastManager) {
                    ToastManager.show(data.message || (status === 200 ? 'Carga masiva completada.' : 'Carga masiva con errores.'), status === 200 ? 'success' : 'warning');
                }
            } catch (error) {
                if (window.ToastManager) {
                    ToastManager.show('No se pudo procesar el archivo. Intente nuevamente.', 'danger');
                }
                const detail = error && error.message ? ` ${error.message}` : '';
                showResumen(`No se pudo procesar el archivo.${detail}`, 'danger');
            } finally {
                if (procesarBtn) {
                    procesarBtn.disabled = false;
                    procesarBtn.textContent = 'Procesar';
                }
            }
        };

        if (cargaBtn && cargaModalEl) {
            cargaBtn.addEventListener('click', function () {
                clearResumen();
                if (excelInput) {
                    excelInput.value = '';
                }
                bootstrap.Modal.getOrCreateInstance(cargaModalEl).show();
            });
        }

        if (procesarBtn) {
            procesarBtn.addEventListener('click', function () {
                requestConfirm('¿Desea procesar el archivo Excel seleccionado?', function () {
                    if (confirmModalEl) {
                        bootstrap.Modal.getOrCreateInstance(confirmModalEl).hide();
                    }
                    procesarCargaMasiva();
                });
            });
        }
        const syncLoader = document.getElementById('erp-sync-loader');
        const syncForms = document.querySelectorAll('form[action*="prodleche/sync"]');
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
