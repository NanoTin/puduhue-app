<?php
$isPartial = $partial ?? false;
$formData = $formData ?? [];
$pptocompra = $pptocompra ?? [];
$presupuestosDestino = $presupuestosDestino ?? [];
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$periodoBase = $pptocompra['temporadainicio'] ?? date('Y-m-01');
try {
    $periodoDate = new DateTime((string)$periodoBase);
} catch (Exception $e) {
    $periodoDate = new DateTime();
}
$periodoAnio = (int)$periodoDate->format('Y');
$periodoMes = (int)$periodoDate->format('n');

$money = static function ($value, int $decimals = 0): string {
    if (!is_numeric($value)) {
        return '0';
    }
    return number_format((float)$value, $decimals, ',', '.');
};

if (!empty($errorMessage)): ?>
    <div class="container-fluid px-4 py-3">
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    </div>
<?php endif; ?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Traspaso entre Presupuestos de Compras</h3>

    <div class="card mb-3">
        <div class="card-body">
            <strong>PPTO Origen:</strong> <?= htmlspecialchars($pptocompra['pptocompraid'] ?? '') ?> |
            <strong>Temporada:</strong> <?= htmlspecialchars($pptocompra['temporadadescripcion'] ?? '') ?> |
            <strong>Subfamilia:</strong> <?= htmlspecialchars(($pptocompra['subfamiliadsc'] ?? '') . ' (' . ($pptocompra['subfamiliacod'] ?? '') . ')') ?> |
            <strong>Centro costo:</strong> <?= htmlspecialchars(($pptocompra['centrocostodsc'] ?? '') . ' (' . ($pptocompra['centrocostocod'] ?? '') . ')') ?> |
            <div class="small text-muted mt-2">Período de traspaso (automático): <?= htmlspecialchars($periodoDate->format('Y-m')); ?></div>
        </div>
    </div>

    <form method="POST" action="?route=pptocompra/traspasar" class="row g-3" data-confirm="1" data-confirm-message="¿Desea registrar este traspaso?">
        <input type="hidden" name="pptocompraidOrigen" value="<?= htmlspecialchars((string)($formData['pptocompraidOrigen'] ?? $pptocompra['pptocompraid'] ?? '')) ?>">
        <input type="hidden" name="ppoanio" value="<?= htmlspecialchars((string)($formData['ppoanio'] ?? $periodoAnio)) ?>">
        <input type="hidden" name="ppomes" value="<?= htmlspecialchars((string)($formData['ppomes'] ?? $periodoMes)) ?>">

        <div class="col-md-4">
            <label class="form-label" for="pptocompraidDestino">PPTO Destino</label>
            <select name="pptocompraidDestino" id="pptocompraidDestino" class="form-select" required>
                <option value="">Seleccione presupuesto vigente</option>
                <?php foreach ($presupuestosDestino as $destino): ?>
                    <?php
                        $destinoId = (string)($destino['pptocompraid'] ?? '');
                        $destinoLabel = '#' . $destinoId
                            . ' | ' . (string)($destino['temporadadescripcion'] ?? '')
                            . ' | ' . (string)($destino['subfamiliadsc'] ?? '') . ' (' . (string)($destino['subfamiliacod'] ?? '') . ')'
                            . ' | ' . (string)($destino['centrocostodsc'] ?? '') . ' (' . (string)($destino['centrocostocod'] ?? '') . ')'
                            . ' | Disp. $' . $money($destino['saldodisponible'] ?? 0);
                    ?>
                    <option value="<?= htmlspecialchars($destinoId) ?>" <?= ((string)($formData['pptocompraidDestino'] ?? '') === $destinoId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($destinoLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($presupuestosDestino)): ?>
                <div class="form-text text-warning">No hay otros presupuestos vigentes disponibles para traspasar.</div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pptocompramonto">Monto</label>
            <input type="number" name="pptocompramonto" id="pptocompramonto" class="form-control" step="0.01" min="0.01" required value="<?= htmlspecialchars((string)($formData['pptocompramonto'] ?? '')) ?>">
        </div>
        <div class="col-md-12">
            <label class="form-label" for="pptocompramotivo">Justificación</label>
            <textarea name="pptocompramotivo" id="pptocompramotivo" class="form-control" rows="2" maxlength="500" required><?= htmlspecialchars((string)($formData['pptocompramotivo'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pptocompregruppomovimiento">Grupo movimiento</label>
            <input type="text" name="pptocompregruppomovimiento" id="pptocompregruppomovimiento" class="form-control" maxlength="50" value="<?= htmlspecialchars((string)($formData['pptocompregruppomovimiento'] ?? '')) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pptocomprareflinea">Referencia línea</label>
            <input type="text" name="pptocomprareflinea" id="pptocomprareflinea" class="form-control" maxlength="150" value="<?= htmlspecialchars((string)($formData['pptocomprareflinea'] ?? '')) ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Registrar traspaso</button>
            <a href="?route=pptocompra/detalle&pptocompraid=<?= urlencode((string)($formData['pptocompraidOrigen'] ?? ($pptocompra['pptocompraid'] ?? ''))) ?>" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monto = document.getElementById('pptocompramonto');
        if (!monto) {
            return;
        }

        monto.addEventListener('input', function () {
            if (!monto.value) {
                return;
            }

            monto.value = monto.value.replace('-', '');
        });
    });
</script>
<?php if (!$isPartial) { require 'footer.php'; } ?>
