<?php
// Listado de fundosestanques
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Fundos Estanques</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="fundosestanques">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroFundoid" value="<?= htmlspecialchars($filtros['filtroFundoid'] ?? '') ?>">
            <input type="hidden" name="filtroFundoestanquedsc" value="<?= htmlspecialchars($filtros['filtroFundoestanquedsc'] ?? '') ?>">
            <input type="hidden" name="filtroEstanquemarcaid" value="<?= htmlspecialchars($filtros['filtroEstanquemarcaid'] ?? '') ?>">
            <input type="hidden" name="filtroFundoestanqueactivo" value="<?= htmlspecialchars($filtros['filtroFundoestanqueactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=fundosestanques/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Estanque
        </a>
    </div>

    <form id="fundosestanques-filter-form" action="?route=fundosestanques/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="fundosestanques/listar">
        <div class="col-md-2">
            <input type="number" name="filtroFundoid" class="form-control" placeholder="Fundo ID"
                   value="<?= htmlspecialchars($filtros['filtroFundoid'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="filtroFundoestanquedsc" class="form-control" placeholder="Descripción"
                   value="<?= htmlspecialchars($filtros['filtroFundoestanquedsc'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="filtroEstanquemarcaid" class="form-control" placeholder="Marca ID"
                   value="<?= htmlspecialchars($filtros['filtroEstanquemarcaid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroFundoestanqueactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroFundoestanqueactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroFundoestanqueactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-filters" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fundo</th>
                    <th>Descripción</th>
                    <th>Marca</th>
                    <th>Orden</th>
                    <th>Activo</th>
                    <th style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fundosestanques)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($fundosestanques as $fe): ?>
                        <tr>
                            <td><?= htmlspecialchars($fe['fundoestanqueid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fe['fundos_fundonombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fe['fundoestanquedsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fe['estanquesmarcas_estanquemarcadsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fe['fundoestanqueorden'] ?? '') ?></td>
                            <td><?= !empty($fe['fundoestanqueactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=fundosestanques/editar&id=<?= urlencode($fe['fundoestanqueid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($fe['fundoestanqueactivo'])): ?>
                                    <form action="?route=fundosestanques/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="fundoestanqueid" value="<?= htmlspecialchars($fe['fundoestanqueid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea anular este estanque?');">
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
        const form = document.getElementById('fundosestanques-filter-form');
        const clearBtn = document.getElementById('btn-clear-filters');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'fundosestanquesAutoSearch';
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
