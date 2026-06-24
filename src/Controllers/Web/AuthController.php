<?php

require_once dirname(__DIR__, 2) . '/Config/Env.php';
require_once dirname(__DIR__, 2) . '/Config/Database.php';
require_once dirname(__DIR__, 2) . '/Helpers/CsrfHelper.php';
require_once dirname(__DIR__, 2) . '/Helpers/Logger.php';
require_once dirname(__DIR__, 2) . '/Services/UsuariosService.php';
require_once dirname(__DIR__, 2) . '/Auth/AuthService.php';

use App\Helpers\Logger;

class AuthController
{
    private ?\UsuariosService $usuariosService = null;
    private \AuthService $authService;

    public function __construct()
    {
        $this->authService = new \AuthService();
    }

    public function loginForm(): array
    {
        if (!empty($_SESSION['usuarioId'])) {
            header('Location: index.php?route=dashboard');
            exit;
        }

        return $this->buildLoginViewModel();
    }

    public function loginPost(): array
    {
        if (!empty($_SESSION['usuarioId'])) {
            header('Location: index.php?route=dashboard');
            exit;
        }

        $usernameInput = $_POST['username'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $rememberUser = !empty($_POST['remember_user']);
        $recaptchaToken = $_POST['recaptcha_token'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $viewModel = $this->buildLoginViewModel([
            'usernameValue' => $usernameInput,
            'rememberUserChecked' => $rememberUser,
        ]);

        if (!CsrfHelper::validate(CsrfHelper::tokenFromRequest(), 'login')) {
            return $this->withToast($viewModel, 'Sesion expirada o token CSRF invalido. Intente nuevamente.');
        }

        if ($usernameInput === '' || $passwordInput === '') {
            return $this->withToast($viewModel, 'Debe ingresar usuario y contrasena.');
        }

        if (!$this->authService->checkAndRegisterRateLimit($ip, 10, 600)) {
            Logger::error('Login rate limit alcanzado para IP ' . $ip);
            return $this->withToast($viewModel, 'Demasiados intentos. Espere unos minutos antes de reintentar.');
        }

        try {
            $usuarioCod = $this->authService->normalizeUsernameInput($usernameInput, $this->getUsuariosService());

            if ($viewModel['recaptchaEnabled']) {
                $this->authService->verifyRecaptchaToken($recaptchaToken, [
                    'apiKey' => $viewModel['recaptchaApiKey'],
                    'projectId' => $viewModel['recaptchaProjectId'],
                    'siteKey' => $viewModel['recaptchaSiteKey'],
                    'minScore' => $viewModel['recaptchaMinScore'],
                ]);
            }

            $pdo = \Database::getInstance()->getPdo();
            $userRow = $this->authService->fetchUserRow($pdo, $usuarioCod);

            if (empty($userRow)) {
                throw new RuntimeException('Usuario no existe');
            }

            if ((int)($userRow['usuarioactivo'] ?? 0) !== 1) {
                throw new RuntimeException('Usuario inactivo');
            }

            if ((int)($userRow['usuariobloqueado'] ?? 0) === 1) {
                throw new RuntimeException('Usuario bloqueado');
            }

            $perfilId = (int)($userRow['perfilid'] ?? 0);
            if ($perfilId === 0) {
                throw new RuntimeException('Usuario sin perfil asignado');
            }

            $empresaDefault = (int)($userRow['empresaiddefault'] ?? 0);
            if ($empresaDefault === 0) {
                throw new RuntimeException('Usuario no tiene una empresa predeterminada asignada');
            }

            $fundoDefault = (int)($userRow['fundoiddefault'] ?? 0);
            if ($fundoDefault === 0) {
                throw new RuntimeException('Usuario no tiene un fundo predeterminado asignado');
            }

            $usuarioIp = $ip;
            $usuarioDispositivo = class_exists('AuthMiddleware')
                ? AuthMiddleware::normalizeDispositivo($_SERVER['HTTP_USER_AGENT'] ?? '')
                : $this->normalizeDispositivo($_SERVER['HTTP_USER_AGENT'] ?? '');

            $hashDb = $userRow['usuariopwdhash'] ?? '';
            if (!password_verify($passwordInput, $hashDb)) {
                $this->authService->registerLoginAttempt($pdo, $usuarioCod, $usuarioIp, $usuarioDispositivo, false);
                throw new RuntimeException('Credenciales incorrectas');
            }

            $this->authService->registerLoginAttempt($pdo, $usuarioCod, $usuarioIp, $usuarioDispositivo, true);
            session_regenerate_id(true);
            unset($_SESSION['csrf_tokens']);

            $_SESSION['usuarioId'] = (int)($userRow['usuarioid'] ?? 0);
            $_SESSION['usuarioCodSession'] = $usuarioCod;
            $_SESSION['usuarioNombreSession'] = $userRow['usuarionombre'] ?? '';
            $_SESSION['perfilIdSession'] = $perfilId;
            $_SESSION['empresaIdSession'] = $empresaDefault;
            $_SESSION['fundoIdSession'] = $fundoDefault;
            $_SESSION['esRootSession'] = (int)($userRow['usuarioesroot'] ?? 0);
            $_SESSION['esAdminSession'] = (int)($userRow['usuarioesadmin'] ?? 0);
            $_SESSION['last_route'] = 'dashboard';
            $_SESSION['login_rate'] = ['ip' => $ip, 'count' => 0, 'start' => time()];

            $this->persistRememberUserCookie($rememberUser, $usuarioCod);

            header('Location: index.php?route=dashboard');
            exit;
        } catch (Throwable $e) {
            Logger::error('Login fallido: ' . $e->getMessage() . ' | usuario=' . ($usernameInput ?: 'vacio') . ' | ip=' . $ip);
            return $this->withToast($viewModel, 'No se pudo iniciar sesion. Credenciales incorrectas o reCAPTCHA invalido.');
        }
    }

    public function logout(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        AuthMiddleware::logout();
        header('Location: login.php');
        exit;
    }

    private function buildLoginViewModel(array $overrides = []): array
    {
        $rememberedUserCookie = $_COOKIE['remember_user'] ?? '';
        $recaptchaEnabled = !in_array(
            strtolower(trim((string)Env::get('RECAPTCHA_ENABLED', 'true'))),
            ['0', 'false', 'no', 'off'],
            true
        );

        return array_merge([
            'toastMessage' => null,
            'toastType' => 'danger',
            'recaptchaSiteKey' => Env::get('RECAPTCHA_SITE_KEY', ''),
            'recaptchaApiKey' => Env::get('RECAPTCHA_API_KEY', ''),
            'recaptchaProjectId' => Env::get('RECAPTCHA_PROJECT_ID', ''),
            'recaptchaMinScore' => (float)Env::get('RECAPTCHA_MIN_SCORE', 0.5),
            'recaptchaEnabled' => $recaptchaEnabled,
            'csrfToken' => CsrfHelper::generate('login'),
            'rememberedUserCookie' => $rememberedUserCookie,
            'usernameValue' => $rememberedUserCookie,
            'rememberUserChecked' => $rememberedUserCookie !== '',
        ], $overrides);
    }

    private function withToast(array $viewModel, string $message, string $type = 'danger'): array
    {
        $viewModel['toastMessage'] = $message;
        $viewModel['toastType'] = $type;
        return $viewModel;
    }

    private function persistRememberUserCookie(bool $rememberUser, string $usuarioCod): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if ($rememberUser) {
            setcookie('remember_user', $usuarioCod, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            return;
        }

        setcookie('remember_user', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function getUsuariosService(): \UsuariosService
    {
        if ($this->usuariosService === null) {
            $this->usuariosService = new \UsuariosService();
        }

        return $this->usuariosService;
    }

    private function normalizeDispositivo(?string $dispositivo): string
    {
        $dispositivo = trim((string)$dispositivo);
        if ($dispositivo === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($dispositivo, 0, 50);
        }

        return substr($dispositivo, 0, 50);
    }
}
