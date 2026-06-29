<?php
// Variables: $usuario, $errorMessage
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Editar Usuario</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=usuarios/editar" 
        autocomplete="off"
        data-confirm="1"
        data-confirm-message="¿Desea confirmar los datos ingresados?">
        <input type="hidden" name="usuarioid" value="<?= htmlspecialchars($usuario['usuarioid'] ?? '') ?>">
        <!-- Grid de 4 columnas que se adapta automaticamente -->
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label">Código</label>
                <input type="text" name="usuariocod" class="form-control" required maxlength="12" readonly
                    value="<?= htmlspecialchars($usuario['usuariocod'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">RUT</label>
                <input type="text" name="usuariorut" class="form-control" required maxlength="12" readondly
                    value="<?= htmlspecialchars($usuario['usuariorut'] ?? '') ?>">
            </div>
            <div class="form-field form-field-half">
                <label class="form-label">Nombre</label>
                <input type="text" name="usuarionombre" class="form-control" required maxlength="100"
                    value="<?= htmlspecialchars($usuario['usuarionombre'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Contraseña (dejar vacío para mantener)</label>
                <input type="password" name="usuariopwd" class="form-control">
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopwd2">Confirmar Contraseña</label>
                <input type="password" name="usuariopwd2" id="usuariopwd2" class="form-control" autocomplete="new-password">
            </div>
            <div class="form-field form-field-half">
                <label class="form-label">Email</label>
                <input type="email" name="usuarioemail" class="form-control" maxlength="100"
                    value="<?= htmlspecialchars($usuario['usuarioemail'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Celular</label>
                <input type="text" name="usuariocelular" class="form-control" maxlength="12"
                    value="<?= htmlspecialchars($usuario['usuariocelular'] ?? '') ?>">
            </div>
            <div class="form-field">
                <label class="form-label">Perfil</label>
                <select name="perfilid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($perfilesOptions ?? []) as $perfilOpt): ?>
                        <option value="<?= htmlspecialchars($perfilOpt['perfilid'] ?? '') ?>"
                            <?= (isset($usuario['perfilid']) && $usuario['perfilid'] == $perfilOpt['perfilid']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($perfilOpt['perfildesc'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">Empresa</label>
                <input type="text" name="empresadefault" class="form-control" readonly
                    value="<?= htmlspecialchars($usuario['empresadefault'] ?? '') ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="usuarioesadmin">Administrador</label>
                <select class="form-select" id="usuarioesadmin" name="usuarioesadmin">
                    <option value="1" <?= !empty($usuario['usuarioesadmin']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuarioesadmin']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariobloqueado">Bloqueado</label>
                <select class="form-select" id="usuariobloqueado" name="usuariobloqueado">
                    <option value="1" <?= !empty($usuario['usuariobloqueado']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariobloqueado']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field form-field-half">
                <label class="form-label">Motivo bloqueo</label>
                <input type="text" name="usuariobloqueadodesc" class="form-control" maxlength="100"
                    value="<?= htmlspecialchars($usuario['usuariobloqueadodesc'] ?? '') ?>">
            </div>

            <div class="form-field">
                <label class="form-label" for="usuarioactivo">Activo</label>
                <select class="form-select" id="usuarioactivo" name="usuarioactivo">
                    <option value="1" <?= !empty($usuario['usuarioactivo']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuarioactivo']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopermiteaprobreq">Aprueba REQ</label>
                <select class="form-select" id="usuariopermiteaprobreq" name="usuariopermiteaprobreq">
                    <option value="1" <?= !empty($usuario['usuariopermiteaprobreq']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariopermiteaprobreq']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopermiteaprobpreoc">Aprueba PreOC</label>
                <select class="form-select" id="usuariopermiteaprobpreoc" name="usuariopermiteaprobpreoc">
                    <option value="1" <?= !empty($usuario['usuariopermiteaprobpreoc']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariopermiteaprobpreoc']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariocomprador">Comprador</label>
                <select class="form-select" id="usuariocomprador" name="usuariocomprador">
                    <option value="1" <?= !empty($usuario['usuariocomprador']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariocomprador']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopermiteanularpreoc">Puede anular PreOC</label>
                <select class="form-select" id="usuariopermiteanularpreoc" name="usuariopermiteanularpreoc">
                    <option value="1" <?= !empty($usuario['usuariopermiteanularpreoc']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariopermiteanularpreoc']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopermiteeditarprecios">Puede editar precios</label>
                <select class="form-select" id="usuariopermiteeditarprecios" name="usuariopermiteeditarprecios">
                    <option value="1" <?= !empty($usuario['usuariopermiteeditarprecios']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariopermiteeditarprecios']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopermitecrearitem">Puede crear ítems</label>
                <select class="form-select" id="usuariopermitecrearitem" name="usuariopermitecrearitem">
                    <option value="1" <?= !empty($usuario['usuariopermitecrearitem']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariopermitecrearitem']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopermiteeditaritem">Puede editar ítems</label>
                <select class="form-select" id="usuariopermiteeditaritem" name="usuariopermiteeditaritem">
                    <option value="1" <?= !empty($usuario['usuariopermiteeditaritem']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariopermiteeditaritem']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopermitesynctrnerp">Puede sincronizar ERP</label>
                <select class="form-select" id="usuariopermitesynctrnerp" name="usuariopermitesynctrnerp">
                    <option value="1" <?= !empty($usuario['usuariopermitesynctrnerp']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuariopermitesynctrnerp']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuarioreqautorizadorfuerapptocompra">Autorizador fuera ppto REQ</label>
                <select class="form-select" id="usuarioreqautorizadorfuerapptocompra" name="usuarioreqautorizadorfuerapptocompra">
                    <option value="1" <?= !empty($usuario['usuarioreqautorizadorfuerapptocompra']) ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= empty($usuario['usuarioreqautorizadorfuerapptocompra']) ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuarioreqautorizadorfuerapptocompraorden">Orden autorizador fuera ppto</label>
                <input type="number" name="usuarioreqautorizadorfuerapptocompraorden" id="usuarioreqautorizadorfuerapptocompraorden" class="form-control" min="0"
                    value="<?= htmlspecialchars((string)($usuario['usuarioreqautorizadorfuerapptocompraorden'] ?? 0)) ?>">
            </div>
        </div>
        <!-- Botones de accion -->
        <div class="form-actions">
            <button type="submit"class="btn btn-primary">Guardar cambios</button>
            <a href="?route=usuarios/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>
<script>
    (function () {
        const form = document.querySelector('form[action="?route=usuarios/editar"]');
        if (!form) {
            return;
        }

        const pwdInput = form.querySelector('input[name="usuariopwd"]');
        const pwd2Input = document.getElementById('usuariopwd2');
        const authFueraInput = document.getElementById('usuarioreqautorizadorfuerapptocompra');
        const authFueraOrdenInput = document.getElementById('usuarioreqautorizadorfuerapptocompraorden');

        const isValidPassword = (pwd) => {
            return pwd.length >= 5 &&
                /[A-Z]/.test(pwd) &&
                /[0-9]/.test(pwd) &&
                /[^A-Za-z0-9]/.test(pwd);
        };

        form.addEventListener('submit', function (evt) {
            const pwd = pwdInput ? pwdInput.value : '';
            const pwdConfirm = pwd2Input ? pwd2Input.value : '';

            if (pwd !== '' && !isValidPassword(pwd)) {
                evt.preventDefault();
                window.ToastManager?.show('La contrasena debe tener al menos 5 caracteres, 1 mayuscula, 1 numero y 1 caracter especial.', 'warning');
                return;
            }

            if (pwd !== pwdConfirm) {
                evt.preventDefault();
                window.ToastManager?.show('La confirmacion de contrasena no coincide.', 'warning');
                return;
            }

            if (authFueraInput && authFueraOrdenInput && authFueraInput.value === '1' && (!authFueraOrdenInput.value || Number(authFueraOrdenInput.value) <= 0)) {
                evt.preventDefault();
                window.ToastManager?.show('Debe informar un orden mayor a cero para autorizador fuera de presupuesto.', 'warning');
            }
        });
    })();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
