<?php
// Listado de menús
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Menús</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="menus">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroMenupadre" value="<?= htmlspecialchars($filtros['filtroMenupadre'] ?? '') ?>">
            <input type="hidden" name="filtroMenudesc" value="<?= htmlspecialchars($filtros['filtroMenudesc'] ?? '') ?>">
            <input type="hidden" name="filtroMenuactivo" value="<?= htmlspecialchars($filtros['filtroMenuactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=menus/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Menú
        </a>
    </div>

    <form id="menus-filter-form" action="?route=menus/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="menus/listar">
        <div class="col-md-3">
            <input type="number" name="filtroMenupadre" class="form-control" placeholder="Padre ID"
                   value="<?= htmlspecialchars($filtros['filtroMenupadre'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="filtroMenudesc" class="form-control" placeholder="Descripción"
                   value="<?= htmlspecialchars($filtros['filtroMenudesc'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="filtroMenuactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroMenuactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroMenuactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-menus" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Padre</th>
                    <th>Descripción</th>
                    <th>Formulario</th>
                    <th>Orden</th>
                    <th>Icono</th>
                    <th>Activo</th>
                    <th class="col-actions-md">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menus)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No se encontraron registros</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($menus as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['menuid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['menupadre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['menudesc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['menuform'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['menunvlord'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['menuicono'] ?? '') ?></td>
                            <td><?= !empty($m['menuactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=menus/editar&id=<?= urlencode($m['menuid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($m['menuactivo'])): ?>
                                    <form action="?route=menus/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Desea anular este menú?">
                                        <input type="hidden" name="menuid" value="<?= htmlspecialchars($m['menuid'] ?? '') ?>">
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('menus-filter-form');
        const clearBtn = document.getElementById('btn-clear-menus');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'menusAutoSearch';
            const submitForm = () => form.requestSubmit();

            // When a toast is present, delay the auto-search so the toast renders first
            if (window.__hasToast) {
                if (!sessionStorage.getItem(autoKey)) {
                    sessionStorage.setItem(autoKey, '1');
                    setTimeout(submitForm, 1200);
                }
                return;
            }
            
            if (!sessionStorage.getItem(autoKey)) {
                sessionStorage.setItem(autoKey, '1');
                form.requestSubmit();
            } else {
                sessionStorage.removeItem(autoKey);
            }
        }
    });
</script>
