<?php
$isPartial = $partial ?? false;
$formData = $formData ?? [];
$pptocompra = $pptocompra ?? [];
$tiposAjuste = $tiposAjuste ?? [];
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

if (!empty($errorMessage)): ?>
    <div class="container-fluid px-4 py-3">
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    </div>
<?php endif; ?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Ajuste de Presupuesto de Compras</h3>

    <div class="card mb-3">
        <div class="card-body">
            <strong>Presupuesto:</strong> <?= htmlspecialchars($pptocompra['pptocompraid'] ?? '') ?> |
            <strong>Temporada:</strong> <?= htmlspecialchars($pptocompra['temporadadescripcion'] ?? '') ?> |
            <strong>Subfamilia:</strong> <?= htmlspecialchars(($pptocompra['subfamiliacod'] ?? '') . ' - ' . ($pptocompra['subfamiliadsc'] ?? '')) ?> |
            <strong>Centro costo:</strong> <?= htmlspecialchars(($pptocompra['centrocostocod'] ?? '') . ' - ' . ($pptocompra['centrocostodsc'] ?? '')) ?>
        </div>
    </div>

    <form method="POST" action="?route=pptocompra/ajustar" class="row g-3" data-confirm="1" data-confirm-message="¿Desea registrar este ajuste?">
        <input type="hidden" name="pptocompraid" value="<?= htmlspecialchars((string)($formData['pptocompraid'] ?? $pptocompra['pptocompraid'] ?? '')) ?>">

        <div class="col-md-2">
            <label class="form-label" for="ppoanio">Año</label>
            <input type="number" name="ppoanio" id="ppoanio" class="form-control" min="2000" max="2200" required value="<?= htmlspecialchars((string)($formData['ppoanio'] ?? (int)date('Y'))) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="ppomes">Mes</label>
            <select name="ppomes" id="ppomes" class="form-select" required>
                <option value="">Mes</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= (string)($formData['ppomes'] ?? '') === (string)$m ? 'selected' : '' ?>><?= $m ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pptocompratransacciontipoid">Tipo ajuste</label>
            <select name="pptocompratransacciontipoid" id="pptocompratransacciontipoid" class="form-select" required>
                <option value="">Seleccione</option>
                <?php foreach (($tiposAjuste ?? []) as $tipo): ?>
                    <option value="<?= htmlspecialchars($tipo['pptocompratransacciontipoid'] ?? '') ?>" <?= ((string)($formData['pptocompratransacciontipoid'] ?? '') === (string)($tipo['pptocompratransacciontipoid'] ?? '')) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tipo['pptocompratransacciontipodsc'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pptocompramonto">Monto</label>
            <input type="number" name="pptocompramonto" id="pptocompramonto" class="form-control" step="0.01" min="0.01" required value="<?= htmlspecialchars((string)($formData['pptocompramonto'] ?? '')) ?>">
        </div>
        <div class="col-md-12">
            <label class="form-label" for="pptocompramotivo">Motivo</label>
            <textarea name="pptocompramotivo" id="pptocompramotivo" class="form-control" rows="2" maxlength="500" required><?= htmlspecialchars((string)($formData['pptocompramotivo'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pptocompregenciaorigen">Origen de referencia</label>
            <input type="text" name="pptocompregenciaorigen" id="pptocompregenciaorigen" class="form-control" maxlength="150" value="<?= htmlspecialchars((string)($formData['pptocompregenciaorigen'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pptocomprareflinea">Referencia línea</label>
            <input type="text" name="pptocomprareflinea" id="pptocomprareflinea" class="form-control" maxlength="150" value="<?= htmlspecialchars((string)($formData['pptocomprareflinea'] ?? '')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pptocompregruppomovimiento">Grupo movimiento</label>
            <input type="text" name="pptocompregruppomovimiento" id="pptocompregruppomovimiento" class="form-control" maxlength="50" value="<?= htmlspecialchars((string)($formData['pptocompregruppomovimiento'] ?? '')) ?>">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Registrar ajuste</button>
            <a href="?route=pptocompra/detalle&pptocompraid=<?= urlencode((string)($formData['pptocompraid'] ?? ($pptocompra['pptocompraid'] ?? ''))) ?>" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tipo = document.getElementById('pptocompratransacciontipoid');
        const monto = document.getElementById('pptocompramonto');

        if (!tipo || !monto) {
            return;
        }

        tipo.addEventListener('change', function () {
            const value = tipo.value || '';
            monto.value = '';
            monto.min = '0.01';
        });

        monto.addEventListener('input', function () {
            if (!monto.value) {
                return;
            }
            if (monto.value.indexOf('-') !== -1) {
                monto.value = monto.value.replace('-', '');
            }
        });
    });
</script>
<?php if (!$isPartial) { require 'footer.php'; } ?>
