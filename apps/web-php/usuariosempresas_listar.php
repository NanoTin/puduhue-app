<?php
// Listado de Usuarios Empresas
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-4">Usuarios - Empresas</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="?route=usuariosempresas/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Asociar
        </a>
    </div>

    <form action="?route=usuariosempresas/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="usuariosempresas/listar">
        <div class="col-md-3">
            <input type="number" name="filtroUsuarioid" class="form-control" placeholder="Usuario ID"
                   value="<?= htmlspecialchars($filtros['filtroUsuarioid'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <input type="number" name="filtroEmpresaid" class="form-control" placeholder="Empresa ID"
                   value="<?= htmlspecialchars($filtros['filtroEmpresaid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Usuario</th>
                    <th>Empresa</th>
                    <th>Default</th>
                    <th style="width: 140px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuariosempresas)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No se encontraron registros</td></tr>
                <?php else: ?>
                    <?php foreach ($usuariosempresas as $ue): ?>
                        <tr>
                            <td><?= htmlspecialchars($ue['usuarios_usuarionombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($ue['empresas_razonsocial'] ?? '') ?></td>
                            <td><?= !empty($ue['uedefault']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <form action="?route=usuariosempresas/eliminar" method="POST" class="d-inline">
                                    <input type="hidden" name="usuarioid" value="<?= htmlspecialchars($ue['usuarioid'] ?? '') ?>">
                                    <input type="hidden" name="empresaid" value="<?= htmlspecialchars($ue['empresaid'] ?? '') ?>">
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
