<?php
// Listado de Usuarios Fundos
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Usuarios - Fundos</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="?route=usuariosfundos/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Asociar
        </a>
    </div>

    <form id="usuariofundo-filter-form" action="?route=usuariosfundos/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="usuariosfundos/listar">
        <div class="col-md-3">
            <select name="filtroUsuarioid" class="form-select" >
                <option value="">Usuario</option>
                <?php foreach (($usuariosOptions ?? []) as $usuarioOpt): ?>
                    <option value="<?= htmlspecialchars($usuarioOpt['usuarioid']) ?>"
                        <?= ($filtros['filtroUsuarioid'] ?? '') == ($usuarioOpt['usuarioid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($usuarioOpt['usuarionombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="filtroFundoid" class="form-select" >
                <option value="">Fundo</option>
                <?php foreach (($fundosOptions ?? []) as $fundoOpt): ?>
                    <option value="<?= htmlspecialchars($fundoOpt['fundoid']) ?>"
                        <?= ($filtros['filtroFundoid'] ?? '') == ($fundoOpt['fundoid'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fundoOpt['fundonombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
        <div class="col-md-1">
            <button type="button" id="btn-clear-usuariofundo" class="btn btn-outline-secondary w-100">
                <i class="bi bi-eraser"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Usuario</th>
                    <th>Fundo</th>
                    <th>Default</th>
                    <th style="width: 140px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuariosfundos)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($usuariosfundos as $uf): ?>
                        <tr>
                            <td><?= htmlspecialchars($uf['usuarios_usuarionombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($uf['fundos_fundonombre'] ?? '') ?></td>
                            <td><?= !empty($uf['ufdefault']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <form action="?route=usuariosfundos/eliminar" method="POST" class="d-inline">
                                    <input type="hidden" name="usuarioid" value="<?= htmlspecialchars($uf['usuarioid'] ?? '') ?>">
                                    <input type="hidden" name="fundoid" value="<?= htmlspecialchars($uf['fundoid'] ?? '') ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar asociación?');">
                                        <i class="bi bi-x-circle"></i> Eliminar
                                    </button>
                                </form>
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
        const form = document.getElementById('usuariofundo-filter-form');
        const clearBtn = document.getElementById('btn-clear-usuariofundo');
        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('select, input[type="number"]').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'usuariofundoAutoSearch';
            const submitForm = () => form.requestSubmit();

            if (window.__hasToast) {
                sessionStorage.removeItem(autoKey);
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