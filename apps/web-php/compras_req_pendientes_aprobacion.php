<?php
$isPartial = $partial ?? false;
$reqs = $reqs ?? [];
$meta = $meta ?? null;
$filtros = $filtros ?? [];
$centrosOptions = $centrosOptions ?? [];
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$fmtDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    try {
        return (new DateTime((string)$value))->format('d-m-Y');
    } catch (Exception $e) {
        return (string)$value;
    }
};

$fmtMoney = static function ($value): string {
    return number_format((float)$value, 0, ',', '.');
};
?>

<div class="container-fluid px-4 py-3 pdh-page">
    <div class="pdh-page__header">
        <div>
            <h3 class="mb-1">REQ pendientes de aprobación</h3>
            <div class="pdh-page__subtitle">Documentos actualmente asignados al usuario aprobador conectado.</div>
        </div>
        <div class="pdh-page__actions">
            <a class="btn btn-outline-secondary btn-sm" href="?route=compras-req/listar">
                <i class="bi bi-arrow-left"></i> Volver al listado
            </a>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form class="row g-2 pdh-filter-bar" method="GET" action="?route=compras-req/pendientes-aprobacion">
        <input type="hidden" name="route" value="compras-req/pendientes-aprobacion">
        <div class="col-12 col-md-4">
            <input
                class="form-control"
                type="text"
                name="filtroBusqueda"
                placeholder="Codigo, observacion o solicitante"
                value="<?= htmlspecialchars((string)($filtros['filtroBusqueda'] ?? '')) ?>"
            >
        </div>
        <div class="col-6 col-md-2">
            <input class="form-control" type="date" name="filtroFechaDesde" value="<?= htmlspecialchars((string)($filtros['filtroFechaDesde'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <input class="form-control" type="date" name="filtroFechaHasta" value="<?= htmlspecialchars((string)($filtros['filtroFechaHasta'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="filtroCentroCostoId">
                <option value="">Centro de costo</option>
                <?php foreach ($centrosOptions as $centro): ?>
                    <option value="<?= htmlspecialchars((string)($centro['centrocostoid'] ?? '')) ?>" <?= ((string)($filtros['filtroCentroCostoId'] ?? '') === (string)($centro['centrocostoid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($centro['centrocostodsc'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4 col-md-1">
            <select class="form-select" name="filtroPrioridad">
                <option value="">Prio.</option>
                <option value="1" <?= (($filtros['filtroPrioridad'] ?? '') === '1') ? 'selected' : '' ?>>Normal</option>
                <option value="2" <?= (($filtros['filtroPrioridad'] ?? '') === '2') ? 'selected' : '' ?>>Alta</option>
            </select>
        </div>
        <div class="col-2 col-md-1">
            <button class="btn btn-secondary w-100" type="submit"><i class="bi bi-search"></i></button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle pdh-data-table">
            <thead class="table-dark">
                <tr>
                    <th>Código</th>
                    <th>Fecha</th>
                    <th>Centro</th>
                    <th>Solicitante</th>
                    <th>Prioridad</th>
                    <th class="text-end">Total</th>
                    <th>Presupuesto</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reqs)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No hay REQ pendientes de aprobación.</td></tr>
                <?php else: ?>
                    <?php foreach ($reqs as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($row['reqcompracod'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($fmtDate($row['reqcomprafecha'] ?? '')) ?></td>
                            <td>
                                <?= htmlspecialchars((string)($row['centrocostodsc'] ?? '')) ?>
                                <div class="compras-req-table-note"><?= htmlspecialchars((string)($row['centrocostocod'] ?? '')) ?></div>
                            </td>
                            <td><?= htmlspecialchars((string)($row['funcionarionombre'] ?? ($row['creadornombre'] ?? ''))) ?></td>
                            <td>
                                <?php if ((int)($row['reqcompraprioridad'] ?? 1) === 2): ?>
                                    <span class="badge bg-danger-subtle text-danger-emphasis">Alta</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqcompranettotal'] ?? 0)) ?></td>
                            <td>
                                <?php if (!empty($row['reqfuerapptocompra'])): ?>
                                    <span class="compras-req-pill compras-req-pill--danger">Fuera PPTO</span>
                                <?php elseif (!empty($row['reqadvertenciapptocompra'])): ?>
                                    <span class="compras-req-pill compras-req-pill--warning">Advertencia</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">OK</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a class="btn btn-outline-primary btn-sm" href="?route=compras-req/ver&id=<?= urlencode((string)($row['reqcompraid'] ?? '')) ?>">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
