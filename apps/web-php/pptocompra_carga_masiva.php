<?php
$isPartial = $partial ?? false;
$temporadas = $temporadas ?? [];
$formData = $formData ?? [];
$preview = $preview ?? null;
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$fmtMoney = static function ($value, int $decimals = 0): string {
    if (!is_numeric($value)) {
        return '0';
    }
    return number_format((float)$value, $decimals, ',', '.');
};

$selectedTemporadaId = (string)($formData['temporadaid'] ?? ($preview['temporada']['temporadaid'] ?? ''));
$selectedTemporadaText = '';
foreach ($temporadas as $temporadaOpt) {
    if ((string)($temporadaOpt['temporadaid'] ?? '') === $selectedTemporadaId) {
        $selectedTemporadaText = (string)($temporadaOpt['temporadadescripcion'] ?? '');
        break;
    }
}
?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Carga Masiva Excel - Presupuesto de Compras</h3>
            <div class="text-muted">Seleccione temporada, lea el Excel y revise el resumen antes de confirmar.</div>
        </div>
        <a href="?route=pptocompra/listar" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="?route=pptocompra/carga_masiva" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                <input type="hidden" name="excel_action" value="preview">
                <div class="col-lg-4 col-md-5">
                    <label class="form-label">Temporada</label>
                    <select name="temporadaid" class="form-select" required>
                        <option value="">Seleccione temporada</option>
                        <?php foreach ($temporadas as $temporadaOpt): ?>
                            <option value="<?= htmlspecialchars($temporadaOpt['temporadaid'] ?? '') ?>" <?= $selectedTemporadaId === (string)($temporadaOpt['temporadaid'] ?? '') ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($temporadaOpt['temporadadescripcion'] ?? '') . ' | ' . ($temporadaOpt['temporadainicio'] ?? '') . ' a ' . ($temporadaOpt['temporadafin'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-5 col-md-7">
                    <label class="form-label">Archivo Excel</label>
                    <input type="file" name="pptocompra_excel" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <div class="form-text">Columnas: Subfamilia Codigo, Centro Costo Codigo, Año, Mes, Monto, Observacion Mes.</div>
                </div>
                <div class="col-lg-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Leer Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($preview)): ?>
        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase fw-semibold text-success mb-2">Total cargado</div>
                        <div class="display-6 fw-bold">$<?= htmlspecialchars($fmtMoney($preview['total'] ?? 0)) ?></div>
                        <div class="text-muted mt-2">
                            <?= htmlspecialchars(count($preview['payloads'] ?? [])) ?> presupuestos,
                            <?= htmlspecialchars(count($preview['detalle'] ?? [])) ?> líneas a cargar
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase fw-semibold text-muted mb-2">Temporada destino</div>
                        <div class="h4 mb-1"><?= htmlspecialchars($preview['temporada']['temporadadescripcion'] ?? '') ?></div>
                        <div class="text-muted">
                            <?= htmlspecialchars(($preview['temporada']['temporadainicio'] ?? '') . ' a ' . ($preview['temporada']['temporadafin'] ?? '')) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-warning shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase fw-semibold text-warning mb-2">Registros omitidos</div>
                        <div class="display-6 fw-bold"><?= htmlspecialchars(count($preview['omitidos'] ?? [])) ?></div>
                        <div class="text-muted mt-2">Existentes o no aplicables para esta carga.</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($preview['payloads'])): ?>
            <div class="alert alert-warning" role="alert">
                No hay presupuestos nuevos para guardar. Revise el reporte de omitidos.
            </div>
        <?php elseif (!empty($preview['omitidos'])): ?>
            <div class="alert alert-info" role="alert">
                Se cargarán solo los registros nuevos. Los presupuestos existentes fueron omitidos y no serán modificados.
            </div>
        <?php endif; ?>

        <div class="accordion mb-4" id="pptocompraCargaAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOmitidos">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOmitidos" aria-expanded="false" aria-controls="collapseOmitidos">
                        Registros omitidos
                    </button>
                </h2>
                <div id="collapseOmitidos" class="accordion-collapse collapse" aria-labelledby="headingOmitidos" data-bs-parent="#pptocompraCargaAccordion">
                    <div class="accordion-body">
                        <?php if (empty($preview['omitidos'])): ?>
                            <div class="text-muted">No hay registros omitidos.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fila</th>
                                            <th>PPTO existente</th>
                                            <th>Subfamilia</th>
                                            <th>Centro de Costo</th>
                                            <th class="text-end">Año</th>
                                            <th class="text-end">Mes</th>
                                            <th class="text-end">Monto</th>
                                            <th>Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (($preview['omitidos'] ?? []) as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['excel_row'] ?? '') ?></td>
                                                <td>
                                                    <?php if (!empty($row['pptocompraid'])): ?>
                                                        <a href="?route=pptocompra/detalle&pptocompraid=<?= urlencode((string)$row['pptocompraid']) ?>" target="_blank">
                                                            #<?= htmlspecialchars($row['pptocompraid']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($row['subfamiliadsc'] ?? '') ?>
                                                    <div class="small text-muted"><?= htmlspecialchars($row['subfamiliacod'] ?? '') ?></div>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($row['centrocostodsc'] ?? '') ?>
                                                    <div class="small text-muted"><?= htmlspecialchars($row['centrocostocod'] ?? '') ?></div>
                                                </td>
                                                <td class="text-end"><?= htmlspecialchars($row['ppoanio'] ?? '') ?></td>
                                                <td class="text-end"><?= htmlspecialchars($row['ppomes'] ?? '') ?></td>
                                                <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['ppomontoppto'] ?? 0)) ?></td>
                                                <td><?= htmlspecialchars($row['motivo'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSubfamilia">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSubfamilia" aria-expanded="false" aria-controls="collapseSubfamilia">
                        Resumen por subfamilia
                    </button>
                </h2>
                <div id="collapseSubfamilia" class="accordion-collapse collapse" aria-labelledby="headingSubfamilia" data-bs-parent="#pptocompraCargaAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Subfamilia</th>
                                        <th class="text-end">Total Temporada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($preview['resumenSubfamilia'] ?? []) as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['subfamiliacod'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row['subfamiliadsc'] ?? '') ?></td>
                                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['total'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="fw-bold table-light">
                                        <td colspan="2">Total</td>
                                        <td class="text-end">$<?= htmlspecialchars($fmtMoney($preview['total'] ?? 0)) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingCentro">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCentro" aria-expanded="false" aria-controls="collapseCentro">
                        Reporte por centro de costo y subfamilia
                    </button>
                </h2>
                <div id="collapseCentro" class="accordion-collapse collapse" aria-labelledby="headingCentro" data-bs-parent="#pptocompraCargaAccordion">
                    <div class="accordion-body">
                        <?php foreach (($preview['resumenCentro'] ?? []) as $centroIndex => $centro): ?>
                            <div class="border rounded-3 p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($centro['centrocostodsc'] ?? '') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($centro['centrocostocod'] ?? '') ?></div>
                                    </div>
                                    <div class="fw-bold">$<?= htmlspecialchars($fmtMoney($centro['total'] ?? 0)) ?></div>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Codigo</th>
                                                <th>Subfamilia</th>
                                                <th class="text-end">Total Temporada</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (($centro['subfamilias'] ?? []) as $subfamilia): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($subfamilia['subfamiliacod'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($subfamilia['subfamiliadsc'] ?? '') ?></td>
                                                    <td class="text-end">$<?= htmlspecialchars($fmtMoney($subfamilia['total'] ?? 0)) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingDetalle">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDetalle" aria-expanded="false" aria-controls="collapseDetalle">
                        Ver Detalle
                    </button>
                </h2>
                <div id="collapseDetalle" class="accordion-collapse collapse" aria-labelledby="headingDetalle" data-bs-parent="#pptocompraCargaAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fila</th>
                                        <th>Subfamilia</th>
                                        <th>Centro de Costo</th>
                                        <th class="text-end">Año</th>
                                        <th class="text-end">Mes</th>
                                        <th class="text-end">Monto</th>
                                        <th>Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($preview['detalle'] ?? []) as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['excel_row'] ?? '') ?></td>
                                            <td>
                                                <?= htmlspecialchars($row['subfamiliadsc'] ?? '') ?>
                                                <div class="small text-muted"><?= htmlspecialchars($row['subfamiliacod'] ?? '') ?></div>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['centrocostodsc'] ?? '') ?>
                                                <div class="small text-muted"><?= htmlspecialchars($row['centrocostocod'] ?? '') ?></div>
                                            </td>
                                            <td class="text-end"><?= htmlspecialchars($row['ppoanio'] ?? '') ?></td>
                                            <td class="text-end"><?= htmlspecialchars($row['ppomes'] ?? '') ?></td>
                                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['ppomontoppto'] ?? 0)) ?></td>
                                            <td><?= htmlspecialchars($row['ppoobservacion'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($preview['payloads'])): ?>
            <form action="?route=pptocompra/carga_masiva" method="POST" class="d-flex justify-content-end gap-2"
                  data-confirm="1"
                  data-confirm-message="¿Confirma cargar estos datos a la Temporada <?= htmlspecialchars($selectedTemporadaText, ENT_QUOTES) ?>?">
                <input type="hidden" name="excel_action" value="confirm">
                <input type="hidden" name="temporadaid" value="<?= htmlspecialchars($selectedTemporadaId) ?>">
                <input type="hidden" name="preview_payload" value="<?= htmlspecialchars($formData['preview_payload'] ?? '') ?>">
                <a href="?route=pptocompra/carga_masiva" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check2-circle"></i> Guardar / Confirmar
                </button>
            </form>
        <?php else: ?>
            <div class="d-flex justify-content-end">
                <a href="?route=pptocompra/carga_masiva" class="btn btn-outline-secondary">Volver a cargar Excel</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
