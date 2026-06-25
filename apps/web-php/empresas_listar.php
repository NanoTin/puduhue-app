<?php
// empresas_listar.php
// Variables disponibles:
// $empresas -> array con las filas
// $filtros  -> filtros recibidos via GET
// $meta     -> metadata opcional del SP
// $partial  -> render parcial (sin layout)

$isPartial = $partial ?? false;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Empresas</h3>

    <!-- Barra de acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="empresas">

            <!-- reenviar mismos filtros usados en la pantalla -->
            <input type="hidden" name="filtroRazonsocial" value="<?= htmlspecialchars($filtros['filtroRazonsocial'] ?? '') ?>">
            <input type="hidden" name="filtroEmpresarut" value="<?= htmlspecialchars($filtros['filtroEmpresarut'] ?? '') ?>">
            <input type="hidden" name="filtroEmpresaactivo" value="<?= htmlspecialchars($filtros['filtroEmpresaactivo'] ?? '') ?>">

            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>
        <a href="?route=empresas/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Empresa
        </a>
    </div>

    <div class="mb-3">
        <form action="?route=empresas/listar" method="GET" class="d-flex flex-wrap gap-2">
            <input type="hidden" name="route" value="empresas/listar">

            <input type="text" name="filtroRazonsocial"
                   placeholder="Razón Social"
                   class="form-control"
                   value="<?= htmlspecialchars($filtros['filtroRazonsocial'] ?? '') ?>">

            <input type="text" name="filtroEmpresarut"
                   placeholder="RUT"
                   class="form-control"
                   value="<?= htmlspecialchars($filtros['filtroEmpresarut'] ?? '') ?>">

            <select name="filtroEmpresaactivo" class="form-select">
                <option value="">(Todos)</option>
                <option value="1" <?= ($filtros['filtroEmpresaactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroEmpresaactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>

            <button class="btn btn-secondary">
                <i class="bi bi-search"></i> Buscar
            </button>
        </form>
    </div>

    <!-- Tabla -->
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>RUT</th>
                    <th>Razón Social</th>
                    <th>Giro</th>
                    <th>Contacto</th>
                    <th>Email</th>
                    <th>Activo</th>
                    <th class="col-actions-lg">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($empresas)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            No se encontraron registros
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($empresas as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['empresaid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['empresarut'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['razonsocial'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['giro'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['contactonombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['empresaemail'] ?? '') ?></td>
                            <td>
                                <?php if (($e['empresaactivo'] ?? 0) == 1): ?>
                                    <span class="badge bg-success">Sí</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="?route=empresas/editar&id=<?= urlencode($e['empresaid'] ?? '') ?>" 
                                   class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>

                                <?php if (($e['empresaactivo'] ?? 0) == 1): ?>
                                    <form action="?route=empresas/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="¿Desea anular esta empresa?">
                                        <input type="hidden" name="empresaid" value="<?= htmlspecialchars($e['empresaid'] ?? '') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-circle"></i> Anular
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Inactiva</span>
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
