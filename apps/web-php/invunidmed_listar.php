<?php
// Listado de unidades de medida
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Unidades de Medida</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="invunidmed">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroInvunidmeddsc" value="<?= htmlspecialchars($filtros['filtroInvunidmeddsc'] ?? '') ?>">
            <input type="hidden" name="filtroErpunidmedcod" value="<?= htmlspecialchars($filtros['filtroErpunidmedcod'] ?? '') ?>">
            <input type="hidden" name="filtroInvunidmedactivo" value="<?= htmlspecialchars($filtros['filtroInvunidmedactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=invunidmed/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Unidad
        </a>
    </div>

    <form id="invunidmed-filter-form" action="?route=invunidmed/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="invunidmed/listar">
        <div class="col-md-4">
            <input type="text" name="filtroInvunidmeddsc" class="form-control" placeholder="Descripcion"
                   value="<?= htmlspecialchars($filtros['filtroInvunidmeddsc'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="filtroErpunidmedcod" class="form-control" placeholder="Codigo ERP"
                   value="<?= htmlspecialchars($filtros['filtroErpunidmedcod'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="filtroInvunidmedactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroInvunidmedactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroInvunidmedactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-invunidmed" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Descripcion</th>
                    <th>Codigo ERP</th>
                    <th>Activo</th>
                    <th style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invunidmed)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($invunidmed as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['invunidmedid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['invunidmeddsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['erpunidmedcod'] ?? '') ?></td>
                            <td><?= !empty($u['invunidmedactivo']) ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=invunidmed/editar&id=<?= urlencode($u['invunidmedid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($u['invunidmedactivo'])): ?>
                                    <form action="?route=invunidmed/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="invunidmedid" value="<?= htmlspecialchars($u['invunidmedid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Desea anular esta unidad?');">
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
        const form = document.getElementById('invunidmed-filter-form');
        const clearBtn = document.getElementById('btn-clear-invunidmed');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], input[type="number"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if(form) {
            const autokey = 'invunidmedAutoSearch';
            if(!sessionStorage.getItem(autokey)) {
                sessionStorage.setItem(autokey, '1');
                form.requestSubmit();
            }else {
                sessionStorage.removeItem(autokey);
            }
        }
    });
</script>
