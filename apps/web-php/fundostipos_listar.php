<?php
// Listado de tipos de fundo
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Tipos de Fundo</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="fundostipos">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroFundotipodsc" value="<?= htmlspecialchars($filtros['filtroFundotipodsc'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=fundostipos/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Tipo
        </a>
    </div>

    <form action="?route=fundostipos/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="fundostipos/listar">
        <div class="col-md-6">
            <input type="text" name="filtroFundotipodsc" class="form-control" placeholder="Descripción"
                   value="<?= htmlspecialchars($filtros['filtroFundotipodsc'] ?? '') ?>">
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
                    <th style="width: 150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fundostipos)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">No se encontraron registros</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fundostipos as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['fundotipoid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($t['fundotipodsc'] ?? '') ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=fundostipos/editar&id=<?= urlencode($t['fundotipoid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
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
