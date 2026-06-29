<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Usuarios - Centros de Costo</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="?route=usuarios-centros-costo/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Asignar
        </a>
    </div>

    <form id="usuarios-centros-filter-form" action="?route=usuarios-centros-costo/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="usuarios-centros-costo/listar">
        <div class="col-md-4">
            <select name="filtroUsuarioid" class="form-select">
                <option value="">Usuario</option>
                <?php foreach (($usuariosOptions ?? []) as $usuarioOpt): ?>
                    <option value="<?= htmlspecialchars($usuarioOpt['usuarioid']) ?>"
                        <?= (string)($filtros['filtroUsuarioid'] ?? '') === (string)($usuarioOpt['usuarioid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($usuarioOpt['usuarionombre'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select name="filtroCentrocostoid" class="form-select">
                <option value="">Centro de costo</option>
                <?php foreach (($centrosOptions ?? []) as $centroOpt): ?>
                    <option value="<?= htmlspecialchars($centroOpt['centrocostoid']) ?>"
                        <?= (string)($filtros['filtroCentrocostoid'] ?? '') === (string)($centroOpt['centrocostoid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($centroOpt['centrocostocod'] ?? '') . ' - ' . ($centroOpt['centrocostodsc'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroActivo" class="form-select">
                <option value="">Todos</option>
                <option value="1" <?= (string)($filtros['filtroActivo'] ?? '') === '1' ? 'selected' : '' ?>>Activos</option>
                <option value="0" <?= (string)($filtros['filtroActivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100" title="Buscar">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-usuarios-centros" class="btn btn-outline-secondary w-100" title="Limpiar">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Usuario</th>
                    <th>RUT</th>
                    <th>Centro de costo</th>
                    <th>Estado</th>
                    <th>Default</th>
                    <th class="col-actions-2xs">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($asignaciones)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No se encontraron registros</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($asignaciones as $fila): ?>
                        <?php
                        $estaActiva = !empty($fila['usucenactivo']);
                        $esDefault = $estaActiva && !empty($fila['usucendefault']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['usuarios_usuarionombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fila['usuarios_usuariorut'] ?? '') ?></td>
                            <td><?= htmlspecialchars(($fila['centrocostocod'] ?? '') . ' - ' . ($fila['centrocostodsc'] ?? '')) ?></td>
                            <td>
                                <?= $estaActiva
                                    ? '<span class="badge bg-success">Activo</span>'
                                    : '<span class="badge bg-secondary">Inactivo</span>' ?>
                            </td>
                            <td>
                                <?= $esDefault
                                    ? '<span class="badge bg-primary">Sí</span>'
                                    : '<span class="badge bg-light text-dark border">No</span>' ?>
                            </td>
                            <td class="text-nowrap">
                                <?php if ($estaActiva): ?>
                                    <?php if (!$esDefault): ?>
                                        <form action="?route=usuarios-centros-costo/editar" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Marcar este centro como default?">
                                            <input type="hidden" name="usucenid" value="<?= htmlspecialchars($fila['usucenid'] ?? '') ?>">
                                            <input type="hidden" name="accion" value="marcar_default">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-star"></i> Default
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="?route=usuarios-centros-costo/editar" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Desactivar esta asignación?">
                                        <input type="hidden" name="usucenid" value="<?= htmlspecialchars($fila['usucenid'] ?? '') ?>">
                                        <input type="hidden" name="accion" value="desactivar">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-pause-circle"></i> Desactivar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="?route=usuarios-centros-costo/editar" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Activar esta asignación?">
                                        <input type="hidden" name="usucenid" value="<?= htmlspecialchars($fila['usucenid'] ?? '') ?>">
                                        <input type="hidden" name="accion" value="activar">
                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-play-circle"></i> Activar
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

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('usuarios-centros-filter-form');
        const clearBtn = document.getElementById('btn-clear-usuarios-centros');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }
    });
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
