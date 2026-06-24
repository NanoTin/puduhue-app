<?php
$isPartial = $partial ?? false;
$pptocompra = $pptocompra ?? [];
$mensual = $mensual ?? [];
$movimientos = $movimientos ?? [];
$filtroTipo = $filtroTipo ?? '';
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$money = static function ($value, int $decimals = 2): string {
    if (!is_numeric($value)) {
        return '0';
    }
    return number_format((float)$value, $decimals, ',', '.');
};

$fmtDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    return substr((string)$value, 0, 10);
};
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Detalle Presupuesto de Compras</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>Presupuesto ID:</strong> <?= htmlspecialchars($pptocompra['pptocompraid'] ?? '') ?></div>
                <div class="col-md-3"><strong>Temporada:</strong> <?= htmlspecialchars(($pptocompra['temporadadescripcion'] ?? '') . ' - ' . ($pptocompra['temporadatipocodigo'] ?? '')) ?></div>
                <div class="col-md-3"><strong>Subfamilia:</strong> <?= htmlspecialchars(($pptocompra['subfamiliacod'] ?? '') . ' - ' . ($pptocompra['subfamiliadsc'] ?? '')) ?></div>
                <div class="col-md-3"><strong>Centro Costo:</strong> <?= htmlspecialchars(($pptocompra['centrocostocod'] ?? '') . ' - ' . ($pptocompra['centrocostodsc'] ?? '')) ?></div>
                <div class="col-md-3"><strong>Estado:</strong>
                    <?= !empty($pptocompra['pptocompraactivo']) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="?route=pptocompra/ajustar&pptocompraid=<?= urlencode((string)($pptocompra['pptocompraid'] ?? '')) ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-circle-dotted"></i> Ajustar
        </a>
        <a href="?route=pptocompra/listar" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>

    <h5 class="mb-3">Carga base mensual</h5>
    <div class="table-responsive mb-4">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Año</th>
                    <th>Mes</th>
                    <th class="text-end">Monto base</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mensual)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Sin detalle mensual</td></tr>
                <?php else: ?>
                    <?php foreach ($mensual as $line): ?>
                        <tr>
                            <td><?= htmlspecialchars($line['ppoanio'] ?? '') ?></td>
                            <td><?= htmlspecialchars($line['ppomes'] ?? '') ?></td>
                            <td class="text-end"><?= htmlspecialchars($money($line['ppomontoppto'] ?? 0, 2)) ?></td>
                            <td><?= htmlspecialchars($line['ppoobservacion'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h5 class="mb-2">Ajustes y movimientos</h5>
    <form class="row g-2 mb-2" action="?route=pptocompra/detalle" method="GET">
        <input type="hidden" name="route" value="pptocompra/detalle">
        <input type="hidden" name="pptocompraid" value="<?= htmlspecialchars((string)($pptocompra['pptocompraid'] ?? '')) ?>">
        <div class="col-md-4">
            <select name="filtroTipo" class="form-select">
                <option value="">Todos</option>
                <option value="PPTO_AJUSTE_POS" <?= $filtroTipo === 'PPTO_AJUSTE_POS' ? 'selected' : '' ?>>Ajuste positivo</option>
                <option value="PPTO_AJUSTE_NEG" <?= $filtroTipo === 'PPTO_AJUSTE_NEG' ? 'selected' : '' ?>>Ajuste negativo</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i> Filtrar
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Periodo</th>
                    <th>Tipo</th>
                    <th class="text-end">Monto</th>
                    <th>Motivo</th>
                    <th>Origen</th>
                    <th>Ref. Línea</th>
                    <th>Grupo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No se registran movimientos</td></tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td><?= $fmtDate($mov['auditcreacionfechahora'] ?? '') ?></td>
                            <td><?= htmlspecialchars(($mov['ppoanio'] ?? '') . '-' . str_pad((string)($mov['ppomes'] ?? ''), 2, '0', STR_PAD_LEFT)) ?></td>
                            <td><?= htmlspecialchars($mov['pptocompratransacciontipodsc'] ?? ($mov['pptocompratransacciontipoid'] ?? '')) ?></td>
                            <td class="<?= ((float)($mov['pptocompramonto'] ?? 0) >= 0) ? 'text-end text-success' : 'text-end text-danger' ?>">
                                <?= htmlspecialchars($money($mov['pptocompramonto'] ?? 0, 2)) ?>
                            </td>
                            <td><?= htmlspecialchars($mov['pptocompramotivo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($mov['pptocompregenciaorigen'] ?? '') ?></td>
                            <td><?= htmlspecialchars($mov['pptocomprareflinea'] ?? '') ?></td>
                            <td><?= htmlspecialchars($mov['pptocompregruppomovimiento'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
