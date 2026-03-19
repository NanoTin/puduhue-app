<?php
// Listado de tipos de leche
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Tipos de Leche</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="prodlechetipos">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroProdlechetipodsc" value="<?= htmlspecialchars($filtros['filtroProdlechetipodsc'] ?? '') ?>">
            <input type="hidden" name="filtroInvitemid" value="<?= htmlspecialchars($filtros['filtroInvitemid'] ?? '') ?>">
            <input type="hidden" name="filtroProdlecheventa" value="<?= htmlspecialchars($filtros['filtroProdlecheventa'] ?? '') ?>">
            <input type="hidden" name="filtroProdlecheactivo" value="<?= htmlspecialchars($filtros['filtroProdlecheactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=prodlechetipos/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Tipo
        </a>
    </div>

    <form id="prodlechtip-filter-form" action="?route=prodlechetipos/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="prodlechetipos/listar">
        <div class="col-md-4">
            <input type="text" name="filtroProdlechetipodsc" class="form-control" placeholder="Descripción"
                   value="<?= htmlspecialchars($filtros['filtroProdlechetipodsc'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="filtroInvitemid" class="form-control" placeholder="Ítem ID"
                   value="<?= htmlspecialchars($filtros['filtroInvitemid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroProdlecheventa" class="form-select">
                <option value="">Venta</option>
                <option value="1" <?= ($filtros['filtroProdlecheventa'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= ($filtros['filtroProdlecheventa'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroProdlecheactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroProdlecheactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroProdlecheactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
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
                    <th>Ítem</th>
                    <th>Venta</th>
                    <th>Orden</th>
                    <th>Activo</th>
                    <th style="width: 190px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prodlechetipos)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($prodlechetipos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['prodlechetipoid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['prodlechetipodsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($p['invitemdsc'] ?? '') ?></td>
                            <td><?= !empty($p['prodlecheventa']) ? '<span class="badge bg-info">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            <td><?= htmlspecialchars($p['prodlecheorden'] ?? '') ?></td>
                            <td><?= !empty($p['prodlecheactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=prodlechetipos/editar&id=<?= urlencode($p['prodlechetipoid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($p['prodlecheactivo'])): ?>
                                    <form action="?route=prodlechetipos/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="prodlechetipoid" value="<?= htmlspecialchars($p['prodlechetipoid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea anular este tipo?');">
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
        const form = document.getElementById('prodlechtip-filter-form');
        const clearBtn = document.getElementById('btn-clear-prodlechtip');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'prodlechtipAutoSearch';
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
