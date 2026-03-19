<?php
// Listado de Fundos Estanques Clientes
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Fundos Estanques - Clientes</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="fundosestanquesclientes">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroFundoId" value="<?= htmlspecialchars($filtros['filtroFundoId'] ?? '') ?>">
            <input type="hidden" name="filtroClienteid" value="<?= htmlspecialchars($filtros['filtroClienteid'] ?? '') ?>">
            <input type="hidden" name="filtroFndestcliactivo" value="<?= htmlspecialchars($filtros['filtroFndestcliactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=fundosestanquesclientes/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Asociacion
        </a>
    </div>

    <form id="fundosestanquesclientes-filter-form" action="?route=fundosestanquesclientes/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="fundosestanquesclientes/listar">
        <div class="col-md-4">
            <select name="filtroFundoId" class="form-select">
                <option value="">Fundo</option>
                <?php foreach (($fundosOptions ?? []) as $fundoOpt): ?>
                    <option value="<?= htmlspecialchars($fundoOpt['fundoid']) ?>"
                        <?= ($filtros['filtroFundoId'] ?? '') == ($fundoOpt['fundoid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fundoOpt['fundonombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select name="filtroClienteid" class="form-select">
                <option value="">Cliente</option>
                <?php foreach (($clientesOptions ?? []) as $clienteOpt): ?>
                    <option value="<?= htmlspecialchars($clienteOpt['clienteid']) ?>"
                        <?= ($filtros['filtroClienteid'] ?? '') == ($clienteOpt['clienteid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($clienteOpt['clienterazonsocial']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroFndestcliactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroFndestcliactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroFndestcliactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-fundosestanquesclientes" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Fundo</th>
                    <th>Estanque</th>
                    <th>Cliente</th>
                    <th>Codigo Cliente</th>
                    <th>Activo</th>
                    <th style="width: 180px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fundosestanquesclientes)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($fundosestanquesclientes as $fec): ?>
                        <tr>
                            <td><?= htmlspecialchars($fec['fundonombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fec['fundoestanquedsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fec['clienterazonsocial'] ?? '') ?></td>
                            <td><?= htmlspecialchars($fec['estanqueclientecod'] ?? '') ?></td>
                            <td><?= !empty($fec['fndestcliactivo']) ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=fundosestanquesclientes/editar&fundoestanqueid=<?= urlencode($fec['fundoestanqueid'] ?? '') ?>&clienteid=<?= urlencode($fec['clienteid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($fec['fndestcliactivo'])): ?>
                                    <form action="?route=fundosestanquesclientes/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="fundoestanqueid" value="<?= htmlspecialchars($fec['fundoestanqueid'] ?? '') ?>">
                                        <input type="hidden" name="clienteid" value="<?= htmlspecialchars($fec['clienteid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Desea anular este registro?');">
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
        const form = document.getElementById('fundosestanquesclientes-filter-form');
        const clearBtn = document.getElementById('btn-clear-fundosestanquesclientes');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'fundosestanquesclientesAutoSearch';
            const submitForm = () => form.requestSubmit();

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
