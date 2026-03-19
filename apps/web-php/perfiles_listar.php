<?php
// Listado de perfiles
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Perfiles</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="perfiles">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroPerfildesc" value="<?= htmlspecialchars($filtros['filtroPerfildesc'] ?? '') ?>">
            <input type="hidden" name="filtroPerfilesroot" value="<?= htmlspecialchars($filtros['filtroPerfilesroot'] ?? '') ?>">
            <input type="hidden" name="filtroPerfilesadmin" value="<?= htmlspecialchars($filtros['filtroPerfilesadmin'] ?? '') ?>">
            <input type="hidden" name="filtroPerfilactivo" value="<?= htmlspecialchars($filtros['filtroPerfilactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=perfiles/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Perfil
        </a>
    </div>

    <form id="perfiles-filter-form" action="?route=perfiles/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="perfiles/listar">
        <div class="col-md-4">
            <input type="text" name="filtroPerfildesc" class="form-control" placeholder="Descripcion"
                   value="<?= htmlspecialchars($filtros['filtroPerfildesc'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroPerfilesroot" class="form-select">
                <option value="">Es ROOT?</option>
                <option value="1" <?= ($filtros['filtroPerfilesroot'] ?? '') === '1' ? 'selected' : '' ?>>Si</option>
                <option value="0" <?= ($filtros['filtroPerfilesroot'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroPerfilesadmin" class="form-select">
                <option value="">Es Admin?</option>
                <option value="1" <?= ($filtros['filtroPerfilesadmin'] ?? '') === '1' ? 'selected' : '' ?>>Si</option>
                <option value="0" <?= ($filtros['filtroPerfilesadmin'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroPerfilactivo" class="form-select">
                <option value="">Estado?</option>
                <option value="1" <?= ($filtros['filtroPerfilactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroPerfilactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-perfiles" class="btn btn-outline-secondary w-100">
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
                    <th>ROOT</th>
                    <th>Admin</th>
                    <th>Activo</th>
                    <th style="width: 190px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($perfiles)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($perfiles as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['perfilid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['perfildesc'] ?? '') ?></td>
                            <td><?= !empty($p['perfilesroot']) ? '<span class="badge bg-info">Si</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            <td><?= !empty($p['perfilesadmin']) ? '<span class="badge bg-info">Si</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            <td><?= !empty($p['perfilactivo']) ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=perfiles/editar&id=<?= urlencode($p['perfilid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($p['perfilactivo'])): ?>
                                    <form action="?route=perfiles/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="perfilid" value="<?= htmlspecialchars($p['perfilid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Desea anular este perfil?');">
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
        const form = document.getElementById('perfiles-filter-form');
        const clearBtn = document.getElementById('btn-clear-perfiles');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'perfilesAutoSearch';
            if (!sessionStorage.getItem(autoKey)) {
                sessionStorage.setItem(autoKey, '1');
                form.requestSubmit();
            } else {
                sessionStorage.removeItem(autoKey);
            }
        }
    });
</script>
