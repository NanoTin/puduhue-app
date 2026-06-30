<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$consultas = $consultas ?? [];
$consultaSeleccionada = $consultaSeleccionada ?? '';
$columnas = $columnas ?? [];
$rows = $rows ?? [];

function apirequestlog_cell_value(array $row, string $field): string
{
    $value = $row[$field] ?? '';
    if ($value === null || $value === '') {
        return '-';
    }

    return (string)$value;
}

function apirequestlog_http_badge($code): string
{
    $status = (int)$code;
    $class = match (true) {
        $status >= 200 && $status < 300 => 'bg-success',
        $status === 401 || $status === 403 => 'bg-danger',
        $status >= 400 && $status < 500 => 'bg-warning text-dark',
        $status >= 500 => 'bg-danger',
        default => 'bg-secondary',
    };

    return '<span class="badge ' . $class . '">' . htmlspecialchars((string)$code) . '</span>';
}
?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Monitoreo API</h3>
        <span class="badge bg-secondary"><?= htmlspecialchars((string)count($rows)) ?> filas</span>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($consultas as $codigo => $consulta): ?>
            <?php $active = $consultaSeleccionada === $codigo; ?>
            <a href="?route=apirequestlog/listar&consulta=<?= urlencode((string)$codigo) ?>"
               class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <i class="bi <?= htmlspecialchars((string)($consulta['icono'] ?? 'bi-table')) ?>"></i>
                <?= htmlspecialchars((string)($consulta['titulo'] ?? $codigo)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <?php foreach ($columnas as $field => $label): ?>
                        <th><?= htmlspecialchars((string)$label) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= max(1, count($columnas)) ?>" class="text-center text-muted">
                            No se encontraron registros
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($columnas as $field => $label): ?>
                                <td>
                                    <?php if ($field === 'responsecode'): ?>
                                        <?= apirequestlog_http_badge($row[$field] ?? '') ?>
                                    <?php elseif (in_array($field, ['endpoint', 'recurso', 'tokenprefijo', 'tokenpermisos'], true)): ?>
                                        <code><?= htmlspecialchars(apirequestlog_cell_value($row, (string)$field)) ?></code>
                                    <?php else: ?>
                                        <?= htmlspecialchars(apirequestlog_cell_value($row, (string)$field)) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
