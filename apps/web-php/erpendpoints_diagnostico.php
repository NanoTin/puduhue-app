<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$endpointCodigoSeleccionado = $endpointCodigo ?? '';
$endpointOptions = $endpointsActivos ?? [];
$diagnosticoRows = $planDiagnostico ?? [];
?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Diagnóstico Endpoints ERP</h3>
        <span class="badge bg-secondary">Dry-run</span>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form action="?route=erpendpoints/diagnostico" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="erpendpoints/diagnostico">
        <div class="col-md-5 col-lg-4">
            <select name="endpointCodigo" class="form-select">
                <option value="">Todos los endpoints activos</option>
                <?php foreach ($endpointOptions as $endpoint): ?>
                    <?php $codigo = (string)($endpoint['erpendpointcodigo'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($codigo) ?>" <?= $endpointCodigoSeleccionado === $codigo ? 'selected' : '' ?>>
                        <?= htmlspecialchars($codigo . ' - ' . ($endpoint['erpendpointdescripcion'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Grupo</th>
                    <th>Orden</th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Propósito</th>
                    <th>Recurso</th>
                    <th>URL preview</th>
                    <th>Hijos</th>
                    <th>On-demand</th>
                    <th>Auto</th>
                    <th>Último estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($diagnosticoRows)): ?>
                    <tr>
                        <td colspan="13" class="text-center text-muted">No se encontraron endpoints activos</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($diagnosticoRows as $row): ?>
                        <?php
                            $endpoint = $row['endpoint'] ?? [];
                            $hijos = $row['hijos'] ?? [];
                            $baseConfigurada = (bool)($row['baseUrlConfigurada'] ?? false);
                            $baseKey = $row['baseUrlKey'] ?? null;
                            $endpointId = (int)($endpoint['erpendpointid'] ?? 0);
                            $endpointCodigo = (string)($endpoint['erpendpointcodigo'] ?? '');
                            $permiteOndemand = !empty($row['ejecucionPermitida']['ondemand']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($endpoint['erpendpointgrupoid'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($endpoint['erpendpointorden'] ?? '')) ?></td>
                            <td><code><?= htmlspecialchars((string)($endpoint['erpendpointcodigo'] ?? '')) ?></code></td>
                            <td><?= htmlspecialchars((string)($endpoint['erpendpointdescripcion'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($endpoint['erpendpointtipo'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($endpoint['erpendpointproposito'] ?? '')) ?></td>
                            <td><code><?= htmlspecialchars((string)($endpoint['erpendpointrecurso'] ?? '')) ?></code></td>
                            <td>
                                <code><?= htmlspecialchars((string)($row['urlPreview'] ?? '')) ?></code>
                                <?php if ($baseConfigurada): ?>
                                    <span class="badge bg-success ms-1"><?= htmlspecialchars((string)$baseKey) ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark ms-1">Sin base</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($hijos)): ?>
                                    <span class="text-muted">-</span>
                                <?php else: ?>
                                    <?php foreach ($hijos as $hijo): ?>
                                        <div><code><?= htmlspecialchars((string)($hijo['erpendpointcodigo'] ?? '')) ?></code></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= !empty($row['ejecucionPermitida']['ondemand']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?>
                            </td>
                            <td>
                                <?= !empty($row['ejecucionPermitida']['auto']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?>
                            </td>
                            <td>
                                <?php if (!empty($endpoint['erpendpointultestado'])): ?>
                                    <span class="badge bg-info"><?= htmlspecialchars((string)$endpoint['erpendpointultestado']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if ($permiteOndemand): ?>
                                        <form action="?route=erpendpoints/ejecutar" method="POST" class="m-0 js-erp-exec-form" data-confirm="1" data-confirm-message="¿Desea ejecutar este GET contra el ERP?">
                                            <?= CsrfHelper::input('web') ?>
                                            <input type="hidden" name="endpointCodigo" value="<?= htmlspecialchars($endpointCodigo) ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="bi bi-play-fill"></i> Ejecutar GET
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm js-erp-log"
                                            data-endpoint-id="<?= htmlspecialchars((string)$endpointId) ?>"
                                            data-endpoint-codigo="<?= htmlspecialchars($endpointCodigo) ?>"
                                            data-endpoint-descripcion="<?= htmlspecialchars((string)($endpoint['erpendpointdescripcion'] ?? '')) ?>">
                                        <i class="bi bi-journal-text"></i> Log
                                    </button>
                                </div>
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

<div class="modal fade" id="erpEndpointLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Log ERP <span class="text-muted" id="erpEndpointLogTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form class="row g-2 mb-3" id="erpEndpointLogFilter">
                    <input type="hidden" id="erpEndpointLogEndpointId">
                    <div class="col-md-3">
                        <label for="erpEndpointLogFechaDesde" class="form-label">Fecha desde</label>
                        <input type="date" class="form-control" id="erpEndpointLogFechaDesde">
                    </div>
                    <div class="col-md-3">
                        <label for="erpEndpointLogFechaHasta" class="form-label">Fecha hasta</label>
                        <input type="date" class="form-control" id="erpEndpointLogFechaHasta">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Mensaje</th>
                                <th>Leídos</th>
                                <th>Insertados</th>
                                <th>Actualizados</th>
                                <th>Inactivos</th>
                                <th>Usuario</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody id="erpEndpointLogRows">
                            <tr>
                                <td colspan="11" class="text-center text-muted">Seleccione un registro</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="erpEndpointLogDetail" class="border rounded p-3 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                        <h6 class="mb-0">Detalle técnico</h6>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="erpEndpointLogDetailClose">
                            <i class="bi bi-x-lg"></i> Cerrar detalle
                        </button>
                    </div>
                    <div id="erpEndpointLogOmitidos" class="mb-3"></div>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="text-muted">Request</h6>
                            <pre class="bg-light border rounded p-2 small mb-0" id="erpEndpointLogRequestJson"></pre>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="text-muted">Response</h6>
                            <pre class="bg-light border rounded p-2 small mb-0" id="erpEndpointLogResponseJson"></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('erpEndpointLogModal');
        const rowsEl = document.getElementById('erpEndpointLogRows');
        const titleEl = document.getElementById('erpEndpointLogTitle');
        const endpointIdEl = document.getElementById('erpEndpointLogEndpointId');
        const fechaDesdeEl = document.getElementById('erpEndpointLogFechaDesde');
        const fechaHastaEl = document.getElementById('erpEndpointLogFechaHasta');
        const filterForm = document.getElementById('erpEndpointLogFilter');
        const detailEl = document.getElementById('erpEndpointLogDetail');
        const detailCloseEl = document.getElementById('erpEndpointLogDetailClose');
        const requestJsonEl = document.getElementById('erpEndpointLogRequestJson');
        const responseJsonEl = document.getElementById('erpEndpointLogResponseJson');
        const omitidosEl = document.getElementById('erpEndpointLogOmitidos');
        const syncLoader = document.getElementById('erp-sync-loader');
        const execForms = document.querySelectorAll('.js-erp-exec-form');
        let syncInProgress = false;
        let currentLogRows = [];

        window.addEventListener('beforeunload', function (event) {
            if (!syncInProgress) {
                return;
            }

            event.preventDefault();
            event.returnValue = 'Hay una sincronización ERP en curso. Cerrar la ventana puede dejar la validación visual incompleta.';
            return event.returnValue;
        });

        if (syncLoader && execForms.length) {
            execForms.forEach(function (execForm) {
                execForm.addEventListener('submit', async function (event) {
                    if (event.defaultPrevented || execForm.dataset.confirmed !== '1') {
                        return;
                    }

                    event.preventDefault();
                    const submitButton = execForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                    }
                    syncLoader.classList.remove('d-none');
                    syncInProgress = true;

                    try {
                        const response = await fetch(execForm.action, {
                            method: 'POST',
                            body: new FormData(execForm),
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'text/html'
                            }
                        });
                        syncInProgress = false;
                        window.location.href = response.url || window.location.href;
                    } catch (error) {
                        syncInProgress = false;
                        syncLoader.classList.add('d-none');
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                        alert('No se pudo completar la sincronización ERP. Revise el log técnico antes de reintentar.');
                    }
                });
            });
        }

        if (!modalEl || !rowsEl || !window.bootstrap) {
            return;
        }

        const modal = new bootstrap.Modal(modalEl);

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function estadoBadge(estado) {
            const value = String(estado || '');
            const cls = value === 'OK' ? 'bg-success' : (value === 'ERROR' ? 'bg-danger' : 'bg-warning text-dark');
            return `<span class="badge ${cls}">${escapeHtml(value || '-')}</span>`;
        }

        function parseJsonMaybe(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }
            if (typeof value === 'object') {
                return value;
            }
            try {
                return JSON.parse(value);
            } catch (error) {
                return value;
            }
        }

        function formatJson(value) {
            const parsed = parseJsonMaybe(value);
            if (parsed === null || parsed === undefined || parsed === '') {
                return '-';
            }
            if (typeof parsed === 'string') {
                return parsed;
            }
            return JSON.stringify(parsed, null, 2);
        }

        function renderOmitidos(responseJson) {
            const response = parseJsonMaybe(responseJson);
            const omitidos = response && typeof response === 'object' && Array.isArray(response.omitidos)
                ? response.omitidos
                : [];

            if (omitidos.length === 0) {
                omitidosEl.innerHTML = '<div class="text-muted small">Sin registros omitidos informados.</div>';
                return;
            }

            omitidosEl.innerHTML = `
                <h6 class="text-muted">Registros omitidos (${omitidos.length})</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Motivo</th>
                                <th>Dato asociado</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${omitidos.map((row) => `
                                <tr>
                                    <td><code>${escapeHtml(row.codigo || '-')}</code></td>
                                    <td>${escapeHtml(row.motivo || '-')}</td>
                                    <td>${escapeHtml(row.unidadCodigo ?? row.valor ?? '-')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function ocultarDetalle() {
            if (detailEl) {
                detailEl.classList.add('d-none');
            }
        }

        function mostrarDetalle(index) {
            const row = currentLogRows[index];
            if (!row || !detailEl || !requestJsonEl || !responseJsonEl || !omitidosEl) {
                return;
            }

            requestJsonEl.textContent = formatJson(row.erpendpointlogrequestjson);
            responseJsonEl.textContent = formatJson(row.erpendpointlogresponsejson);
            renderOmitidos(row.erpendpointlogresponsejson);
            detailEl.classList.remove('d-none');
            detailEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function setRows(rows) {
            currentLogRows = Array.isArray(rows) ? rows : [];
            ocultarDetalle();

            if (!Array.isArray(rows) || rows.length === 0) {
                rowsEl.innerHTML = '<tr><td colspan="11" class="text-center text-muted">Sin registros de log</td></tr>';
                return;
            }

            rowsEl.innerHTML = rows.map((row, index) => `
                <tr>
                    <td>${escapeHtml(row.erpendpointlogfechaini)}</td>
                    <td>${escapeHtml(row.erpendpointlogfechafin || '-')}</td>
                    <td>${escapeHtml(row.erpendpointlogtipoexec)}</td>
                    <td>${estadoBadge(row.erpendpointlogestado)}</td>
                    <td>${escapeHtml(row.erpendpointlogmensaje || '')}</td>
                    <td>${escapeHtml(row.erpendpointlogregistrosleidos ?? 0)}</td>
                    <td>${escapeHtml(row.erpendpointlogregistrosinsertados ?? 0)}</td>
                    <td>${escapeHtml(row.erpendpointlogregistrosactualizados ?? 0)}</td>
                    <td>${escapeHtml(row.erpendpointlogregistrosinactivos ?? 0)}</td>
                    <td>${escapeHtml(row.usuarioid || '-')}</td>
                    <td>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-erp-log-detail" data-log-index="${index}">
                            <i class="bi bi-search"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        async function cargarLogs() {
            const endpointId = endpointIdEl.value;
            if (!endpointId) {
                return;
            }

            rowsEl.innerHTML = '<tr><td colspan="11" class="text-center text-muted">Cargando...</td></tr>';
            ocultarDetalle();
            const params = new URLSearchParams({
                route: 'erpendpoints/log',
                endpointId,
            });
            if (fechaDesdeEl.value) {
                params.set('fechaDesde', fechaDesdeEl.value);
            }
            if (fechaHastaEl.value) {
                params.set('fechaHasta', fechaHastaEl.value);
            }

            try {
                const response = await fetch(`?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const payload = await response.json();
                if (!response.ok || payload.status !== 200) {
                    throw new Error(payload.message || 'No se pudo consultar el log.');
                }
                setRows(payload.rows || []);
            } catch (error) {
                rowsEl.innerHTML = `<tr><td colspan="11" class="text-center text-danger">${escapeHtml(error.message)}</td></tr>`;
            }
        }

        rowsEl.addEventListener('click', (event) => {
            const button = event.target.closest('.js-erp-log-detail');
            if (!button) {
                return;
            }

            mostrarDetalle(Number(button.dataset.logIndex || -1));
        });

        if (detailCloseEl) {
            detailCloseEl.addEventListener('click', ocultarDetalle);
        }

        document.querySelectorAll('.js-erp-log').forEach((button) => {
            button.addEventListener('click', () => {
                endpointIdEl.value = button.dataset.endpointId || '';
                fechaDesdeEl.value = '';
                fechaHastaEl.value = '';
                titleEl.textContent = `- ${button.dataset.endpointCodigo || ''}`;
                setRows([]);
                modal.show();
                cargarLogs();
            });
        });

        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            cargarLogs();
        });
    });
})();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
