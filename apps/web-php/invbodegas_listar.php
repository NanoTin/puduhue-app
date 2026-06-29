<?php
// Listado de bodegas
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Inventario Bodegas</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="invbodegas">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroInvbodegadsc" value="<?= htmlspecialchars($filtros['filtroInvbodegadsc'] ?? '') ?>">
            <input type="hidden" name="filtroErpinvbodegacod" value="<?= htmlspecialchars($filtros['filtroErpinvbodegacod'] ?? '') ?>">
            <input type="hidden" name="filtroFundoid" value="<?= htmlspecialchars($filtros['filtroFundoid'] ?? '') ?>">
            <input type="hidden" name="filtroInvbodactivo" value="<?= htmlspecialchars($filtros['filtroInvbodactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=invbodegas/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Bodega
        </a>
    </div>

    <form action="?route=invbodegas/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="invbodegas/listar">
        <div class="col-md-3">
            <input type="text" name="filtroInvbodegadsc" class="form-control" placeholder="Descripción"
                   value="<?= htmlspecialchars($filtros['filtroInvbodegadsc'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="filtroErpinvbodegacod" class="form-control" placeholder="ERP Bodega"
                   value="<?= htmlspecialchars($filtros['filtroErpinvbodegacod'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="filtroFundoid" class="form-control" placeholder="Fundo ID"
                   value="<?= htmlspecialchars($filtros['filtroFundoid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroInvbodactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroInvbodactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroInvbodactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
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
                    <th>ID</th>
                    <th>Descripción</th>
                    <th>ERP Código</th>
                    <th>Fundo</th>
                    <th>Activo</th>
                    <th class="col-actions-lg">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invbodegas)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($invbodegas as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['invbodegaid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['invbodegadsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['erpinvbodegacod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['fundos_fundonombre'] ?? '') ?></td>
                            <td><?= !empty($b['invbodactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=invbodegas/editar&id=<?= urlencode($b['invbodegaid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($b['invbodactivo'])): ?>
                                    <form action="?route=invbodegas/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Desea anular esta bodega?">
                                        <input type="hidden" name="invbodegaid" value="<?= htmlspecialchars($b['invbodegaid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-circle"></i> Anular
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

<?php if (!$isPartial) { require 'footer.php'; } ?>
