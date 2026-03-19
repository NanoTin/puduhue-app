<?php
// Listado de fundos
// Variables: $fundos, $filtros, $meta, $partial
$isPartial = $partial ?? false;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Fundos</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="fundos">
            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroFundonombre" value="<?= htmlspecialchars($filtros['filtroFundonombre'] ?? '') ?>">
            <input type="hidden" name="filtroFundotipoid" value="<?= htmlspecialchars($filtros['filtroFundotipoid'] ?? '') ?>">
            <input type="hidden" name="filtroEmpresaid" value="<?= htmlspecialchars($filtros['filtroEmpresaid'] ?? '') ?>">
            <input type="hidden" name="filtroFundopabco" value="<?= htmlspecialchars($filtros['filtroFundopabco'] ?? '') ?>">
            <input type="hidden" name="filtroFundoactivo" value="<?= htmlspecialchars($filtros['filtroFundoactivo'] ?? '') ?>">
            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=fundos/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Fundo
        </a>
    </div>

    <form id="fundos-filter-form" action="?route=fundos/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="fundos/listar">
        <div class="col-md-3">
            <input type="text" name="filtroFundonombre" class="form-control" placeholder="Nombre"
                   value="<?= htmlspecialchars($filtros['filtroFundonombre'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="filtroFundotipoid" class="form-control" placeholder="Tipo ID"
                   value="<?= htmlspecialchars($filtros['filtroFundotipoid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="filtroEmpresaid" class="form-control" placeholder="Empresa ID"
                   value="<?= htmlspecialchars($filtros['filtroEmpresaid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroFundopabco" class="form-select">
                <option value="">PABCO (todos)</option>
                <option value="1" <?= ($filtros['filtroFundopabco'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= ($filtros['filtroFundopabco'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroFundoactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroFundoactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroFundoactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
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
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Empresa</th>
                    <th>ERP Establ.</th>
                    <th>ERP Lote</th>
                    <th>ERP Bodega Leche</th>
                    <th>Reporte Orden</th>
                    <th>PABCO</th>
                    <th>RUP</th>
                    <th>Email</th>
                    <th>Activo</th>
                    <th style="width: 190px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fundos)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No se encontraron registros</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fundos as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['fundoid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['fundonombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['fundostipos_fundotipodsc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['empresas_razonsocial'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['erpestablecimientocod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['erplotecod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['erpleche_invbodegacod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['reporteorden'] ?? '') ?></td>
                            <td><?= !empty($f['fundopabco']) ? '<span class="badge bg-info">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            <td><?= htmlspecialchars($f['fundorup'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['fundoemail'] ?? '') ?></td>
                            <td><?= !empty($f['fundoactivo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=fundos/editar&id=<?= urlencode($f['fundoid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($f['fundoactivo'])): ?>
                                    <form action="?route=fundos/anular" method="POST" class="d-inline">
                                        <input type="hidden" name="fundoid" value="<?= htmlspecialchars($f['fundoid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea anular este fundo?');">
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
        const form = document.getElementById('fundos-filter-form');
        const clearBtn = document.getElementById('btn-clear-fundos');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'fundosAutoSearch';
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
