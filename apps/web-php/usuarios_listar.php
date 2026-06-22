<?php
// Listado de usuarios
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<div class="container-fluid px-4 py-3">
    <h3 class="mb-4">Usuarios</h3>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form action="export_excel.php" method="POST" target="_blank" class="m-0">
            <input type="hidden" name="exportModule" value="usuarios">

            <input type="hidden" name="filtroUsuariorut" value="<?= htmlspecialchars($filtros['filtroUsuariorut'] ?? '') ?>">
            <input type="hidden" name="filtroUsuarionombre" value="<?= htmlspecialchars($filtros['filtroUsuarionombre'] ?? '') ?>">
            <input type="hidden" name="filtroUsuarioemail" value="<?= htmlspecialchars($filtros['filtroUsuarioemail'] ?? '') ?>">
            <input type="hidden" name="filtroPerfilid" value="<?= htmlspecialchars($filtros['filtroPerfilid'] ?? '') ?>">
            <input type="hidden" name="filtroUsuarioesadmin" value="<?= htmlspecialchars($filtros['filtroUsuarioesadmin'] ?? '') ?>">
            <input type="hidden" name="filtroUsuariobloqueado" value="<?= htmlspecialchars($filtros['filtroUsuariobloqueado'] ?? '') ?>">
            <input type="hidden" name="filtroUsuarioactivo" value="<?= htmlspecialchars($filtros['filtroUsuarioactivo'] ?? '') ?>">

            <button class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
            </button>
        </form>

        <a href="?route=usuarios/crear" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Crear Usuario
        </a>
    </div>

    <form id="usuarios-filter-form" action="?route=usuarios/listar" method="GET" class="row g-2 mb-3">
        <input type="hidden" name="route" value="usuarios/listar">
        <div class="col-md-2">
            <input type="text" name="filtroUsuariorut" class="form-control" placeholder="RUT"
                   value="<?= htmlspecialchars($filtros['filtroUsuariorut'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="filtroUsuarionombre" class="form-control" placeholder="Nombre"
                   value="<?= htmlspecialchars($filtros['filtroUsuarionombre'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="filtroUsuarioemail" class="form-control" placeholder="Email"
                   value="<?= htmlspecialchars($filtros['filtroUsuarioemail'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="filtroPerfilid" class="form-control" placeholder="Perfil ID"
                   value="<?= htmlspecialchars($filtros['filtroPerfilid'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="filtroUsuarioactivo" class="form-select">
                <option value="">Estado</option>
                <option value="1" <?= ($filtros['filtroUsuarioactivo'] ?? '') === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= ($filtros['filtroUsuarioactivo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroUsuarioesadmin" class="form-select">
                <option value="">Admin</option>
                <option value="1" <?= ($filtros['filtroUsuarioesadmin'] ?? '') === '1' ? 'selected' : '' ?>>Si</option>
                <option value="0" <?= ($filtros['filtroUsuarioesadmin'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="filtroUsuariobloqueado" class="form-select">
                <option value="">Bloqueado</option>
                <option value="1" <?= ($filtros['filtroUsuariobloqueado'] ?? '') === '1' ? 'selected' : '' ?>>Si</option>
                <option value="0" <?= ($filtros['filtroUsuariobloqueado'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-secondary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>RUT</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Empresa</th>
                    <th>Admin</th>
                    <th>Bloqueado</th>
                    <th>Activo</th>
                    <th class="col-actions-xxl">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted">No se encontraron registros</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['usuarioid'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['usuariorut'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['usuarionombre'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['usuarioemail'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['perfildesc'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['empresadefault'] ?? '') ?></td>
                            <td><?= !empty($u['usuarioesadmin']) ? '<span class="badge bg-info">Si</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                            <td>
                                <?php if (!empty($u['usuariobloqueado'])): ?>
                                    <span class="badge bg-warning text-dark">Si</span>
                                <?php else: ?>
                                    <span class="badge bg-success">No</span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($u['usuarioactivo']) ? '<span class="badge bg-success">Si</span>' : '<span class="badge bg-danger">No</span>' ?></td>
                            <td>
                                <a class="btn btn-warning btn-sm" href="?route=usuarios/editar&id=<?= urlencode($u['usuarioid'] ?? '') ?>">
                                    <i class="bi bi-pencil-square"></i> Editar
                                </a>
                                <?php if (!empty($u['usuarioactivo'])): ?>
                                    <button
                                        type="button"
                                        class="btn btn-info btn-sm btn-api-token"
                                        data-bs-toggle="modal"
                                        data-bs-target="#apiTokenModal"
                                        data-usuario-id="<?= htmlspecialchars($u['usuarioid'] ?? '') ?>"
                                        data-usuario-nombre="<?= htmlspecialchars($u['usuarionombre'] ?? '') ?>"
                                    >
                                        <i class="bi bi-key"></i> Token API
                                    </button>
                                    <form action="?route=usuarios/anular" method="POST" class="d-inline" data-confirm="1" data-confirm-message="Desea anular este usuario?">
                                        <input type="hidden" name="usuarioid" value="<?= htmlspecialchars($u['usuarioid'] ?? '') ?>">
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

<div class="modal fade" id="apiTokenModal" tabindex="-1" aria-labelledby="apiTokenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="apiTokenModalLabel">Generar Token API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="api-token-form">
                    <input type="hidden" name="usuarioid" id="api-token-usuarioid">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="api-token-usuarionombre" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="api-token-nombre" class="form-label">Nombre del token</label>
                        <input type="text" class="form-control" name="tokennombre" id="api-token-nombre" maxlength="150" required>
                    </div>
                    <div class="mb-3">
                        <label for="api-token-dias" class="form-label">Dias de vigencia</label>
                        <input type="number" class="form-control" name="dias_vigencia" id="api-token-dias" min="1" max="3650" value="30" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="api-token-sin-expiracion" name="sin_expiracion">
                        <label class="form-check-label" for="api-token-sin-expiracion">Sin expiracion</label>
                    </div>
                    <div class="mb-3">
                        <label for="api-token-observacion" class="form-label">Observacion</label>
                        <textarea class="form-control" name="observacion" id="api-token-observacion" rows="2" maxlength="255"></textarea>
                    </div>
                </form>
                <div id="api-token-result" class="alert alert-warning d-none mt-3" role="alert">
                    <div class="fw-semibold mb-2">Token generado</div>
                    <div class="small mb-2">Copie este token ahora. No podra volver a visualizarse.</div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="api-token-plain" readonly>
                        <button type="button" class="btn btn-outline-dark" id="api-token-copy-btn">Copiar</button>
                    </div>
                    <div class="small text-muted mt-2" id="api-token-meta"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="api-token-submit-btn">Generar token</button>
            </div>
        </div>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('usuarios-filter-form');
        const clearBtn = document.getElementById('btn-clear-usuarios');
        const tokenModalEl = document.getElementById('apiTokenModal');
        const tokenForm = document.getElementById('api-token-form');
        const tokenResult = document.getElementById('api-token-result');
        const tokenPlainInput = document.getElementById('api-token-plain');
        const tokenMeta = document.getElementById('api-token-meta');
        const tokenSubmitBtn = document.getElementById('api-token-submit-btn');
        const tokenCopyBtn = document.getElementById('api-token-copy-btn');
        const tokenSinExpiracion = document.getElementById('api-token-sin-expiracion');
        const tokenDias = document.getElementById('api-token-dias');

        if (clearBtn && form) {
            clearBtn.addEventListener('click', function () {
                form.querySelectorAll('input[type="text"], select').forEach(function (el) {
                    el.value = '';
                });
                form.requestSubmit();
            });
        }

        if (form) {
            const autoKey = 'usuariospAutoSearch';
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

        document.querySelectorAll('.btn-api-token').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!tokenForm) {
                    return;
                }

                tokenForm.reset();
                document.getElementById('api-token-usuarioid').value = btn.dataset.usuarioId || '';
                document.getElementById('api-token-usuarionombre').value = btn.dataset.usuarioNombre || '';
                tokenDias.disabled = false;
                tokenResult.classList.add('d-none');
                tokenPlainInput.value = '';
                tokenMeta.textContent = '';
            });
        });

        tokenSinExpiracion?.addEventListener('change', function () {
            const disabled = !!tokenSinExpiracion.checked;
            tokenDias.disabled = disabled;
            tokenDias.required = !disabled;
            if (disabled) {
                tokenDias.value = '30';
            }
        });

        tokenSubmitBtn?.addEventListener('click', async function () {
            if (!tokenForm || !tokenForm.reportValidity()) {
                return;
            }

            tokenSubmitBtn.disabled = true;
            const previousLabel = tokenSubmitBtn.textContent;
            tokenSubmitBtn.textContent = 'Generando...';

            try {
                const body = new FormData(tokenForm);
                if (!tokenSinExpiracion.checked) {
                    body.set('sin_expiracion', '0');
                }

                const response = await fetch('?route=usuarios/generar-token-api', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body
                });

                const payload = await response.json();
                if (!response.ok || payload.status !== 200) {
                    throw new Error(payload.message || 'No se pudo generar el token API.');
                }

                tokenPlainInput.value = payload.data.token || '';
                const expira = payload.data.sin_expiracion
                    ? 'Sin expiracion'
                    : 'Expira: ' + (payload.data.tokenfechaexpira || 'N/D');
                tokenMeta.textContent = 'Prefijo: ' + (payload.data.tokenprefijo || '') + ' | ' + expira;
                tokenResult.classList.remove('d-none');

                if (window.ToastManager) {
                    ToastManager.show(payload.message || 'Token API generado.', 'success');
                }
            } catch (error) {
                if (window.ToastManager) {
                    ToastManager.show(error.message || 'No se pudo generar el token API.', 'danger');
                }
            } finally {
                tokenSubmitBtn.disabled = false;
                tokenSubmitBtn.textContent = previousLabel;
            }
        });

        tokenCopyBtn?.addEventListener('click', async function () {
            if (!tokenPlainInput || tokenPlainInput.value === '') {
                return;
            }

            try {
                await navigator.clipboard.writeText(tokenPlainInput.value);
            } catch (error) {
                tokenPlainInput.select();
                document.execCommand('copy');
            }

            if (window.ToastManager) {
                ToastManager.show('Token copiado al portapapeles.', 'success');
            }
        });

        tokenModalEl?.addEventListener('hidden.bs.modal', function () {
            if (!tokenForm) {
                return;
            }

            tokenForm.reset();
            tokenDias.disabled = false;
            tokenResult.classList.add('d-none');
            tokenPlainInput.value = '';
            tokenMeta.textContent = '';
        });
    });
</script>
