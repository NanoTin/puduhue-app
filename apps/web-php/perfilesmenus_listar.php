<?php
// Listado de perfiles menus
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Perfiles - Menus</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="perfilesmenus">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroPerfilid" value="<?= htmlspecialchars($filtros['filtroPerfilid'] ?? '') ?>">
            <input type="hidden" name="filtroMenuid" value="<?= htmlspecialchars($filtros['filtroMenuid'] ?? '') ?>">
            <input type="hidden" name="filtroPerfilmenuactivo" value="<?= htmlspecialchars($filtros['filtroPerfilmenuactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=perfilesmenus/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Asignacion
        </a>
    </div>

    <form id="perfilesmenus-filter-form" action="?route=perfilesmenus/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="perfilesmenus/listar">
        <div class="col-md-3">
            <select name="filtroPerfilid" class="form-select">
                <option value="">Perfil</option>
                <?php foreach (($perfilesOptions ?? []) as $perfilOpt): ?>
                    <option value="<?= htmlspecialchars($perfilOpt['perfilid']) ?>"
                        <?= ($filtros['filtroPerfilid'] ?? '') == ($perfilOpt['perfilid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($perfilOpt['perfildesc'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" name="filtroMenuid" class="form-control" placeholder="Menu ID"
                   value="<?= htmlspecialchars($filtros['filtroMenuid'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="filtroPerfilmenuactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroPerfilmenuactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroPerfilmenuactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-perfilesmenus" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID Perfil</th>
                    <th>Perfil</th>
                    <th>ID Menu</th>
                    <th>Menu</th>
                    <th>Activo</th>
                    <th class="col-actions-lg">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($perfilesmenus)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($perfilesmenus as $pm): ?>
                        <tr>
                            <td><?= htmlspecialchars($pm['perfilid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($pm['perfiles_perfildesc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($pm['menuid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($pm['menus_menudesc'] ?? '') ?></td>
                            <td><?= !empty($pm['perfilmenuactivo']) ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=perfilesmenus/editar&perfilid=<?= urlencode($pm['perfilid'] ?? '') ?>&menuid=<?= urlencode($pm['menuid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($pm['perfilmenuactivo'])): ?>
                                    <form action="?route=perfilesmenus/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="Desea anular esta asignacion?">
                                        <input type="hidden" name="perfilid" value="<?= htmlspecialchars($pm['perfilid'] ?? '') ?>">
                                        <input type="hidden" name="menuid" value="<?= htmlspecialchars($pm['menuid'] ?? '') ?>">
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
        const form = document.getElementById('perfilesmenus-filter-form');
        const clearBtn = document.getElementById('btn-clear-perfilesmenus');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], input[type="number"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if(form) {
            const autokey = 'perfilesmenusAutoSearch';
            if(!sessionStorage.getItem(autokey)) {
                sessionStorage.setItem(autokey, '1');
                form.requestSubmit();
            }else {
                sessionStorage.removeItem(autokey);
            }
        }
    });
</script>
