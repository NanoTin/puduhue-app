<?php
if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isSecureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    if (!headers_sent()) {
        session_set_cookie_params([
            'httponly' => true,
            'secure'   => $isSecureCookie,
            'samesite' => 'Lax',
        ]);
    }

    session_start();
}

$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/src/Config/Env.php';
Env::load();

require_once $rootPath . '/src/Controllers/Web/AuthController.php';

$authController = new AuthController();
$viewModel = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? $authController->loginPost()
    : $authController->loginForm();

$toastMessage = $viewModel['toastMessage'] ?? null;
$toastType = $viewModel['toastType'] ?? 'danger';
$recaptchaSiteKey = $viewModel['recaptchaSiteKey'] ?? '';
$recaptchaEnabled = (bool)($viewModel['recaptchaEnabled'] ?? true);
$csrfToken = $viewModel['csrfToken'] ?? '';
$rememberedUserCookie = $viewModel['rememberedUserCookie'] ?? '';
$usernameValue = $viewModel['usernameValue'] ?? '';
$rememberUserChecked = (bool)($viewModel['rememberUserChecked'] ?? false);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Login</title>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
    <link rel="stylesheet" href="assets/css/login.css" />
</head>

<body>
    <div class="glass-container">
        <h2>Ingreso al sistema</h2>
        <form
            id="loginForm"
            method="post"
            autocomplete="on"
            action=""
            data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"
            data-recaptcha-enabled="<?php echo $recaptchaEnabled ? '1' : '0'; ?>"
        >
            <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="recaptcha_token" name="recaptcha_token" value="">
            <div class="input-group">
                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    required
                    value="<?php echo htmlspecialchars($usernameValue, ENT_QUOTES, 'UTF-8'); ?>"
                >
                <label for="username">Usuario (RUT sin puntos 12345678-K)</label>
            </div>
            <div class="input-group password-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
                <label for="password">Contrasena</label>
                <button
                    type="button"
                    class="password-toggle-btn"
                    id="togglePasswordBtn"
                    aria-label="Show Password"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
                        <path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
                    </svg>
                </button>
            </div>
            <div class="remember-forgot">
                <label for="remember_user">
                    <input
                        type="checkbox"
                        id="remember_user"
                        name="remember_user"
                        <?php echo $rememberUserChecked ? 'checked' : ''; ?>
                    >
                    Recordar usuario
                </label>
                <a href="#" aria-disabled="true">Olvido su contrasena?</a>
            </div>
            <button type="submit" class="login-btn" id="loginBtn">Iniciar Sesion</button>
        </form>
    </div>

    <?php if ($recaptchaEnabled): ?>
    <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></script>
    <?php endif; ?>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"
    ></script>
    <script src="assets/js/toast.js"></script>
    <script>
        (function () {
            const form = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const rememberInput = document.getElementById('remember_user');
            const recaptchaInput = document.getElementById('recaptcha_token');
            const loginBtn = document.getElementById('loginBtn');
            const siteKey = form?.dataset.sitekey || '';
            const recaptchaEnabled = form?.dataset.recaptchaEnabled !== '0';
            const remembered = localStorage.getItem('pdh_remember_user') || '<?php echo htmlspecialchars($rememberedUserCookie, ENT_QUOTES, 'UTF-8'); ?>';

            if (remembered && usernameInput) {
                usernameInput.value = remembered;
                if (rememberInput) {
                    rememberInput.checked = true;
                }
            }

            function showToast(message, type = 'danger') {
                if (window.ToastManager) {
                    ToastManager.show(message, type, 5000);
                }
            }

            function resetButton() {
                if (loginBtn) {
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Iniciar Sesion';
                }
            }

            function computeDv(body) {
                let sum = 0;
                let multiplier = 2;
                for (let i = body.length - 1; i >= 0; i--) {
                    sum += parseInt(body[i], 10) * multiplier;
                    multiplier = multiplier === 7 ? 2 : multiplier + 1;
                }
                const remainder = 11 - (sum % 11);
                if (remainder === 11) return '0';
                if (remainder === 10) return 'K';
                return String(remainder);
            }

            function normalizeRut(rutRaw) {
                const trimmed = rutRaw.trim();
                if (trimmed.toUpperCase() === 'ROOT') {
                    return 'ROOT';
                }
                const clean = trimmed.replace(/[^0-9kK]/g, '');
                const match = clean.match(/^(\d{1,8})([0-9K])$/i);
                if (!match) {
                    return null;
                }
                const body = match[1];
                const dv = match[2].toUpperCase();
                const expected = computeDv(body);
                if (dv !== expected) {
                    return null;
                }
                return `${body}-${dv}`;
            }

            async function fetchRecaptchaToken() {
                if (!recaptchaEnabled) {
                    return 'local-dev-recaptcha-disabled';
                }
                if (!siteKey || !(window.grecaptcha && window.grecaptcha.enterprise)) {
                    throw new Error('reCAPTCHA no configurado.');
                }
                const token = await grecaptcha.enterprise.execute(siteKey, { action: 'LOGIN' });
                if (!token) {
                    throw new Error('Error al obtener token de reCAPTCHA.');
                }
                return token;
            }

            form?.addEventListener('submit', async function (event) {
                if (recaptchaInput && recaptchaInput.value) {
                    return; // NO preventDefault
                }

                event.preventDefault();

                if (!loginBtn) return;

                loginBtn.disabled = true;
                loginBtn.textContent = 'Validando...';

                const rutNormalized = normalizeRut(usernameInput.value || '');
                if (!rutNormalized) {
                    showToast('RUT invalido. Use formato XXXXXXXX-V.');
                    resetButton();
                    return;
                }

                if (!passwordInput.value) {
                    showToast('Debe ingresar la contrasena.');
                    resetButton();
                    return;
                }

                usernameInput.value = rutNormalized;

                try {
                    const token = await fetchRecaptchaToken();
                    recaptchaInput.value = token;

                    if (rememberInput?.checked) {
                        localStorage.setItem('pdh_remember_user', rutNormalized);
                    } else {
                        localStorage.removeItem('pdh_remember_user');
                    }

                    form.submit();
                } catch (err) {
                    showToast(err?.message || 'No se pudo validar reCAPTCHA.');
                    resetButton();
                }
            });

            const eyeIcon = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
                    <path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
                </svg>
            `;
            const eyeSlashIcon = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M13.359 11.238C15.062 9.981 16 8 16 8s-3-5.5-8-5.5a8.2 8.2 0 0 0-2.79.486l1.2 1.2A6.7 6.7 0 0 1 8 3.5c3.314 0 5.88 2.99 6.463 4.5a14.3 14.3 0 0 1-1.65 2.216l.546.522z"/>
                    <path d="M11.297 9.176a3 3 0 0 0-4.474-4.474l1.061 1.06a1.5 1.5 0 0 1 2.353 2.353l1.06 1.06z"/>
                    <path d="M2.354 1.646a.5.5 0 1 0-.708.708l1.423 1.423C1.004 4.963 0 8 0 8s3 5.5 8 5.5a8.4 8.4 0 0 0 4.18-1.107l1.466 1.466a.5.5 0 0 0 .708-.708l-12-12zm2.263 2.263 1.744 1.744A3 3 0 0 0 8.347 10.49l1.458 1.458A6.6 6.6 0 0 1 8 12.5C4.686 12.5 2.12 9.51 1.537 8c.334-.86 1.18-2.34 2.08-3.34z"/>
                </svg>
            `;

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', () => {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    togglePasswordBtn.setAttribute('aria-label', isPassword ? 'Hide Password' : 'Show Password');
                    togglePasswordBtn.innerHTML = isPassword ? eyeSlashIcon : eyeIcon;
                });
            }
        })();
    </script>
    <?php if (!empty($toastMessage)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.ToastManager) {
                ToastManager.show('<?php echo htmlspecialchars($toastMessage, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($toastType, ENT_QUOTES, 'UTF-8'); ?>', 6000);
            }
        });
    </script>
    <?php endif; ?>
</body>

</html>
