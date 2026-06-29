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

$estadoBadge = static function ($estado): string {
    return match ((string)$estado) {
        'BRR' => 'bg-secondary',
        'PND' => 'bg-warning text-dark',
        'EDT' => 'bg-info text-dark',
        'APR' => 'bg-success',
        'RCH' => 'bg-danger',
        'ANL' => 'bg-dark',
        default => 'bg-light text-dark',
    };
};
?>

<div class="container-fluid px-4 py-3 pdh-page">
    <div class="pdh-page__header">
        <div>
            <h3 class="mb-1">Requerimientos de compra</h3>
            <div class="pdh-page__subtitle">Listado principal del flujo REQ del módulo Compras.</div>
        </div>
        <div class="pdh-page__actions">
            <a class="btn btn-outline-secondary btn-sm" href="?route=compras-req/pendientes-aprobacion">
                <i class="bi bi-hourglass-split"></i> Por aprobar
            </a>
            <a class="btn btn-primary btn-sm" href="?route=compras-req/crear">
                <i class="bi bi-plus-circle"></i> Nuevo REQ
            </a>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form class="row g-2 pdh-filter-bar" method="GET" action="?route=compras-req/listar">
        <input type="hidden" name="route" value="compras-req/listar">
        <div class="col-12 col-md-3">
            <input
                class="form-control"
                type="text"
                name="filtroBusqueda"
                placeholder="Codigo, observacion, solicitante o aprobador"
                value="<?= htmlspecialchars((string)($filtros['filtroBusqueda'] ?? '')) ?>"
            >
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select" name="filtroEstado">
                <option value="">Estado</option>
                <?php foreach (['BRR' => 'Borrador', 'PND' => 'Pendiente', 'EDT' => 'En edición', 'APR' => 'Aprobado', 'RCH' => 'Rechazado', 'ANL' => 'Anulado'] as $cod => $label): ?>
                    <option value="<?= htmlspecialchars($cod) ?>" <?= (($filtros['filtroEstado'] ?? '') === $cod) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
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
        <div class="col-6 col-md-1">
            <select class="form-select" name="filtroPrioridad">
                <option value="">Prioridad</option>
                <option value="1" <?= (($filtros['filtroPrioridad'] ?? '') === '1') ? 'selected' : '' ?>>Normal</option>
                <option value="2" <?= (($filtros['filtroPrioridad'] ?? '') === '2') ? 'selected' : '' ?>>Alta</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <input class="form-control" type="date" name="filtroFechaDesde" value="<?= htmlspecialchars((string)($filtros['filtroFechaDesde'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-2">
            <input class="form-control" type="date" name="filtroFechaHasta" value="<?= htmlspecialchars((string)($filtros['filtroFechaHasta'] ?? '')) ?>">
        </div>
        <div class="col-6 col-md-1">
            <select class="form-select" name="filtroSoloVigentes">
                <option value="1" <?= (($filtros['filtroSoloVigentes'] ?? '1') === '1') ? 'selected' : '' ?>>Vigentes</option>
                <option value="0" <?= (($filtros['filtroSoloVigentes'] ?? '') === '0') ? 'selected' : '' ?>>Todos</option>
            </select>
        </div>
        <div class="col-6 col-md-1">
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
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th class="text-end">Total neto</th>
                    <th>Presupuesto</th>
                    <th>Aprobador pendiente</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reqs)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No se encontraron requerimientos.</td></tr>
                <?php else: ?>
                    <?php foreach ($reqs as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string)($row['reqcompracod'] ?? '')) ?></div>
                                <div class="compras-req-table-note"><?= ((int)($row['totalLineas'] ?? 0)) ?> líneas</div>
                            </td>
                            <td><?= htmlspecialchars($fmtDate($row['reqcomprafecha'] ?? '')) ?></td>
                            <td>
                                <?= htmlspecialchars((string)($row['centrocostodsc'] ?? '')) ?>
                                <div class="compras-req-table-note"><?= htmlspecialchars((string)($row['centrocostocod'] ?? '')) ?></div>
                            </td>
                            <td>
                                <?= htmlspecialchars((string)($row['funcionarionombre'] ?? ($row['creadornombre'] ?? ''))) ?>
                            </td>
                            <td><span class="badge <?= htmlspecialchars($estadoBadge((string)($row['reqcompraestadoid'] ?? ''))) ?>"><?= htmlspecialchars((string)($row['reqcomprasestadodsc'] ?? ($row['reqcompraestadoid'] ?? ''))) ?></span></td>
                            <td>
                                <?php if ((int)($row['reqcompraprioridad'] ?? 1) === 2): ?>
                                    <span class="badge bg-danger-subtle text-danger-emphasis"><i class="bi bi-exclamation-circle"></i> Alta</span>
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
                            <td><?= htmlspecialchars((string)($row['reqaprobadorpendientenombre'] ?? '')) ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a class="btn btn-outline-primary btn-sm" href="?route=compras-req/ver&id=<?= urlencode((string)($row['reqcompraid'] ?? '')) ?>" title="Ver" aria-label="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!empty($row['puedeEditar']) && in_array((string)($row['reqcompraestadoid'] ?? ''), ['BRR', 'RCH', 'EDT'], true)): ?>
                                        <a class="btn btn-outline-secondary btn-sm" href="?route=compras-req/editar&id=<?= urlencode((string)($row['reqcompraid'] ?? '')) ?>" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($row['puedeRetomarEdicion']) && (string)($row['reqcompraestadoid'] ?? '') === 'PND'): ?>
                                        <form method="POST" action="?route=compras-req/tomar-edicion" class="d-inline">
                                            <?= CsrfHelper::input('web') ?>
                                            <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($row['reqcompraid'] ?? '')) ?>">
                                            <button class="btn btn-outline-info btn-sm" type="submit" title="Tomar edición" aria-label="Tomar edición">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!empty($row['puedeAnular'])): ?>
                                        <form method="POST" action="?route=compras-req/anular" class="d-inline" data-confirm="1" data-confirm-message="¿Desea anular este REQ?">
                                            <?= CsrfHelper::input('web') ?>
                                            <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($row['reqcompraid'] ?? '')) ?>">
                                            <input type="hidden" name="comentario" value="Anulación solicitada desde listado.">
                                            <button class="btn btn-outline-danger btn-sm" type="submit" title="Anular" aria-label="Anular">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
