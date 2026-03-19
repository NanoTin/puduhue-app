<?php
$isPartial = $partial ?? false;
if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>
<link rel="stylesheet" href="assets/css/frm_mstr.css">

<div class="form-responsive-container">
    <h3 class="page-title">Cambio de contrasena</h3>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="?route=users/change-password" id="changePasswordForm" autocomplete="off">
        <input type="hidden" name="usuarioid" value="<?= htmlspecialchars((string)($usuarioId ?? 0)) ?>">

        <div class="form-grid-2">
            <div class="form-field">
                <label class="form-label" for="usuario_rut">Rut</label>
                <input type="text" id="usuario_rut" class="form-control" value="<?= htmlspecialchars((string)($usuarioRut ?? '')) ?>" disabled>
            </div>
            <div class="form-field">
                <label class="form-label" for="usuario_nombre">Nombre</label>
                <input type="text" id="usuario_nombre" class="form-control" value="<?= htmlspecialchars((string)($usuarioNombre ?? '')) ?>" disabled>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-field">
                <label class="form-label required" for="password">Nueva contrasena</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" aria-label="Show Password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="form-field">
                <label class="form-label required" for="password_confirm">Confirmar nueva contrasena</label>
                <div class="input-group">
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required autocomplete="new-password">
                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirmBtn" aria-label="Show Password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="?route=dashboard/listar" class="btn btn-secondary">Volver</a>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('changePasswordForm');
    const pwdInput = document.getElementById('password');
    const pwdConfirmInput = document.getElementById('password_confirm');
    const togglePwdBtn = document.getElementById('togglePasswordBtn');
    const togglePwdConfirmBtn = document.getElementById('togglePasswordConfirmBtn');

    const toggleVisibility = (input, button) => {
        if (!input || !button) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.setAttribute('aria-label', isPassword ? 'Hide Password' : 'Show Password');
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
    };

    togglePwdBtn?.addEventListener('click', () => toggleVisibility(pwdInput, togglePwdBtn));
    togglePwdConfirmBtn?.addEventListener('click', () => toggleVisibility(pwdConfirmInput, togglePwdConfirmBtn));

    const isValidPassword = (pwd) => {
        return pwd.length >= 5 &&
            /[A-Z]/.test(pwd) &&
            /[0-9]/.test(pwd) &&
            /[^A-Za-z0-9]/.test(pwd);
    };

    form?.addEventListener('submit', (evt) => {
        const pwd = pwdInput?.value || '';
        const pwdConfirm = pwdConfirmInput?.value || '';

        if (!isValidPassword(pwd)) {
            evt.preventDefault();
            if (window.ToastManager) {
                ToastManager.show('La contrasena debe tener al menos 5 caracteres, 1 mayuscula, 1 numero y 1 caracter especial.', 'danger');
            }
            return;
        }

        if (pwd !== pwdConfirm) {
            evt.preventDefault();
            if (window.ToastManager) {
                ToastManager.show('La confirmacion de contrasena no coincide.', 'danger');
            }
        }
    });
})();
</script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
