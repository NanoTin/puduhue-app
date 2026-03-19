<?php
// Listado de categorías de animal
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Categorías de Animal</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="invcateganimal">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroInvcateganimaldsc" value="<?= htmlspecialchars($filtros['filtroInvcateganimaldsc'] ?? '') ?>">
            <input type="hidden" name="filtroErpinvcateganimalcod" value="<?= htmlspecialchars($filtros['filtroErpinvcateganimalcod'] ?? '') ?>">
            <input type="hidden" name="filtroInvcateganimalactivo" value="<?= htmlspecialchars($filtros['filtroInvcateganimalactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=invcateganimal/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Categoría
        </a>
    </div>

    <form id="invcatgnd-filter-form" action="?route=invcateganimal/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="invcateganimal/listar">
        <div class="col-md-4">
            <input type="text" name="filtroInvcateganimaldsc" class="form-control" placeholder="Descripción"
                   value="<?= htmlspecialchars($filtros['filtroInvcateganimaldsc'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="filtroErpinvcateganimalcod" class="form-control" placeholder="ERP Categoría"
                   value="<?= htmlspecialchars($filtros['filtroErpinvcateganimalcod'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="filtroInvcateganimalactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroInvcateganimalactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroInvcateganimalactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
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
                    <th>Descripción</th>
                    <th>ERP Código</th>
                    <th>Kilos x Cab</th>
                    <th>Activo</th>
                    <th style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invcateganimal)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($invcateganimal as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['invcateganimalid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($c['invcateganimaldsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($c['erpinvcateganimalcod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($c['invcateganimalkilosxcab'] ?? '') ?></td>
                            <td><?= !empty($c['invcateganimalactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=invcateganimal/editar&id=<?= urlencode($c['invcateganimalid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($c['invcateganimalactivo'])): ?>
                                    <form action="?route=invcateganimal/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="invcateganimalid" value="<?= htmlspecialchars($c['invcateganimalid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea anular esta categoría?');">
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
        const form = document.getElementById('invcatgnd-filter-form');
        const clearBtn = document.getElementById('btn-clear-invcatgnd');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'invcatgndAutoSearch';
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
                submitForm();
            } else {
                sessionStorage.removeItem(autoKey);
            }
        }
    });
</script>
