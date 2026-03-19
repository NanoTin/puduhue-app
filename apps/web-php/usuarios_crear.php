<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Crear Usuario</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>
    <div class="alert alert-danger d-none" role="alert" id="formError"></div>

    <form method="POST"
      action="?route=usuarios/crear"
      autocomplete="off"
      data-confirm="1"
      data-confirm-message="¿Desea confirmar los datos ingresados?">

         <!-- Grid de 4 columnas que se adapta automaticamente -->
        <div class="form-grid-4">
            <div class="form-field">
                <label class="form-label" for="usuariorut">RUT</label>
                <input type="text" name="usuariorut" id="usuariorut" class="form-control" required maxlength="12" placeholder="XXXXXXXX-X">
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariocod">Codigo</label>
                <input type="text" name="usuariocod" id="usuariocod" class="form-control" required maxlength="12" readonly>
            </div>
            <div class="form-field form-field-half">
                <label class="form-label" for="usuarionombre">Nombre</label>
                <input type="text" name="usuarionombre" id="usuarionombre" class="form-control" required maxlength="100">
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopwd">Contraseña</label>
                <input type="password" name="usuariopwd" id="usuariopwd" class="form-control" required autocomplete="new-password">
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariopwd2">Confirmar Contraseña</label>
                <input type="password" name="usuariopwd2" id="usuariopwd2" class="form-control" required autocomplete="new-password">
            </div>
            <div class="form-field form-field-half">
                <label class="form-label" for="usuarioemail">Email</label>
                <input type="email" name="usuarioemail" id="usuarioemail" class="form-control" maxlength="100">
            </div>
            <div class="form-field">
                <label class="form-label" for="usuariocelular">Celular</label>
                <input type="text" name="usuariocelular" id="usuariocelular" class="form-control" maxlength="12">
            </div>
            <div class="form-field">
                <label class="form-label" for="perfilid">Perfil</label>
                <select name="perfilid" id="perfilid" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <?php foreach (($perfilesOptions ?? []) as $perfilOpt): ?>
                        <option value="<?= htmlspecialchars($perfilOpt['perfilid']) ?>">
                            <?= htmlspecialchars($perfilOpt['perfildesc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-check-label" for="usuarioesadmin">Administrador de Sistema</label>
                <select class="form-select" id="usuarioesadmin" name="usuarioesadmin">
                    <option value="1">Si</option>
                    <option value="0" selected>No</option>
                </select>
            </div>
            <div class="form-field">
                <label class="form-check-label" for="usuarioactivo">Activo</label>
                <select class="form-select" id="usuarioactivo" name="usuarioactivo" readonly>
                    <option value="1" selected>Si</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <!-- Botones de accion -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=usuarios/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.querySelector('form[action="?route=usuarios/crear"]');
    if (!form) {
        return;
    }

    const rutInput = document.getElementById('usuariorut');
    const codInput = document.getElementById('usuariocod');
    const pwdInput = document.getElementById('usuariopwd');
    const pwd2Input = document.getElementById('usuariopwd2');
    const errorBox = document.getElementById('formError');

    const showError = (msg) => {
        if (!errorBox) {
            alert(msg);
            return;
        }
        errorBox.textContent = msg;
        errorBox.classList.remove('d-none');
    };

    const clearError = () => {
        if (errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }
    };

    const cleanRut = (value) => (value || '').toUpperCase().replace(/[^0-9K]/g, '');

    const formatRut = (clean) => {
        if (!clean) {
            return '';
        }
        if (clean.length <= 1) {
            return clean;
        }
        return clean.slice(0, -1) + '-' + clean.slice(-1);
    };

    const computeDv = (body) => {
        let sum = 0;
        let multiplier = 2;
        for (let i = body.length - 1; i >= 0; i--) {
            sum += parseInt(body.charAt(i), 10) * multiplier;
            multiplier = multiplier === 7 ? 2 : multiplier + 1;
        }
        const remainder = 11 - (sum % 11);
        if (remainder === 11) return '0';
        if (remainder === 10) return 'K';
        return String(remainder);
    };

    const isValidRut = (clean) => {
        if (!/^[0-9]{1,8}[0-9K]$/.test(clean)) {
            return false;
        }
        const body = clean.slice(0, -1);
        const dv = clean.slice(-1);
        return computeDv(body) === dv;
    };

    const isValidPassword = (pwd) => {
        return pwd.length >= 5 &&
            /[A-Z]/.test(pwd) &&
            /[0-9]/.test(pwd) &&
            /[^A-Za-z0-9]/.test(pwd);
    };

    const syncCodigo = () => {
        if (!rutInput || !codInput) {
            return;
        }
        const clean = cleanRut(rutInput.value);
        const formatted = formatRut(clean);
        codInput.value = formatted;
    };

    if (rutInput) {
        rutInput.addEventListener('input', syncCodigo);
        syncCodigo();
    }

    form.addEventListener('submit', (evt) => {
        clearError();

        const rutClean = cleanRut(rutInput ? rutInput.value : '');
        if (!rutClean || !isValidRut(rutClean)) {
            evt.preventDefault();
            showError('RUT invalido. Use XXXXXXXX-V con digito verificador correcto.');
            return;
        }

        const formattedRut = formatRut(rutClean);
        if (rutInput) {
            rutInput.value = formattedRut;
        }
        if (codInput) {
            codInput.value = formattedRut;
        }

        const pwd = pwdInput ? pwdInput.value : '';
        if (!isValidPassword(pwd)) {
            evt.preventDefault();
            showError('La contrasena debe tener al menos 5 caracteres, 1 mayuscula, 1 numero y 1 caracter especial.');
            return;
        }

        const pwdConfirm = pwd2Input ? pwd2Input.value : '';
        if (pwd !== pwdConfirm) {
            evt.preventDefault();
            showError('La confirmacion de contrasena no coincide.');
            return;
        }
    });
})();
</script>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
