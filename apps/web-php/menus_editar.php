<?php
// Variables: $menu, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container mt-4">
    <h3 class="mb-3">Editar Menú</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>
    <?php
    $menuPadreActual = $menu['menupadre'] ?? '';
    ?>
    <form method="POST" action="?route=menus/editar" class="row g-3">
        <input type="hidden" name="menuid" value="<?= htmlspecialchars($menu['menuid'] ?? '') ?>">
        <div class="col-md-3">
            <label class="form-label" for="menunivel">Nivel</label>
            <select name="menunivel" id="menunivel" class="form-select" required>
                <option value="1" <?= (isset($menu['menunivel']) && (int)$menu['menunivel'] === 1) ? 'selected' : '' ?>>
                    1 - Menú Raíz
                </option>
                <option value="2" <?= (isset($menu['menunivel']) && (int)$menu['menunivel'] === 2) ? 'selected' : '' ?>>
                    2 - Submenú
                </option>
                <option value="3" <?= (isset($menu['menunivel']) && (int)$menu['menunivel'] === 3) ? 'selected' : '' ?>>
                    3 - Opción
                </option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="hidden" id="menupadreActual" value="<?= htmlspecialchars($menu['menupadre'] ?? '') ?>">
            <label class="form-label" for="menupadre">Menú Padre</label>
            <select name="menupadre" id="menupadre" class="form-select">
                <?php foreach (($menusPadreOptions ?? []) as $menuPadreOpt): ?>
                    <option value="<?= htmlspecialchars($menuPadreOpt['menuid']) ?>"
                        <?= ((string)$menuPadreOpt['menuid'] === (string)$menuPadreActual) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($menuPadreOpt['menudesc'] ?? '') ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Descripción</label>
            <input type="text" name="menudesc" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($menu['menudesc'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Formulario (route o archivo)</label>
            <input type="text" name="menuform" class="form-control" required maxlength="100"
                   value="<?= htmlspecialchars($menu['menuform'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Orden</label>
            <input type="number" name="menunvlord" class="form-control" required
                   value="<?= htmlspecialchars($menu['menunvlord'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Ícono (Bootstrap Icons)</label>
            <input type="text" name="menuicono" class="form-control" required maxlength="50"
                   value="<?= htmlspecialchars($menu['menuicono'] ?? '') ?>">
        </div>
        <div class="col-md-3 form-check mt-4">
            <input class="form-check-input" type="checkbox" value="1" id="menuactivo" name="menuactivo"
                <?= !empty($menu['menuactivo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="menuactivo">Activo</label>
        </div>

        <div class="col-12">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="?route=menus/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const nivel = document.getElementById('menunivel');
        const padre = document.getElementById('menupadre');

        const cargarPadres = async () => {
            const nivelVal = parseInt(nivel.value || '1', 10);

            // Nivel 1: no tiene padre
            if (nivelVal === 1) {
                padre.innerHTML = '<option value="">-- Sin Padre --</option>';
                padre.value = '';
                padre.disabled = true;
                return;
            }

            padre.disabled = true;
            padre.innerHTML = '<option value="">Cargando...</option>';

            const nivelPadre = nivelVal - 1;

            try {
                const resp = await fetch(`?route=menus/padres-por-nivel&nivel=${nivelPadre}`, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });

                const data = await resp.json();

                if (!data || data.ok !== true) {
                    throw new Error('Respuesta inválida del servidor');
                }

                const rows = Array.isArray(data.rows) ? data.rows : [];

                // Render opciones
                let html = '';
                for (const r of rows) {
                    const id = (r.menuid ?? '');
                    const desc = (r.menudesc ?? '');
                    // ojo: el service ya trae el "-- Sin Padre --" con menuid NULL si nivel=0;
                    // acá mantenemos nuestra opción fija y evitamos duplicado:
                    if (desc === '-- Sin Padre --') continue;

                    html += `<option value="${String(id)}">${String(desc)}</option>`;
                }

                padre.innerHTML = html;
                // Seleccionar el padre actual (viene desde la BD)
                const padreActual = document.getElementById('menupadreActual')?.value ?? '';
                if (padreActual !== '') {
                    padre.value = String(padreActual);
                }
                padre.disabled = false;

            } catch (err) {
                padre.innerHTML = '<option value="">Error al cargar</option>';
                padre.disabled = false;
                console.error(err);
            }
        };

        // Carga inicial y al cambiar nivel
        cargarPadres();
        nivel.addEventListener('change', cargarPadres);
    });
</script>