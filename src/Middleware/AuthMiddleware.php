<?php

/**
 * AuthMiddleware
 *
 * Maneja la autenticación básica para las vistas Web.
 * - Verifica que exista sesión y usuarioId.
 * - Si no hay login, redirige a login.php.
 * - Provee getUserContext() para pasar datos a los Services.
 */
class AuthMiddleware
{
    /**
     * Verifica que exista sesión y un usuarioId válido; si no, redirige a login.php.
     */
    public static function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Mientras no exista flujo de login, permite un perfil invitado controlado (solo si se habilita explicitamente).
        $allowGuest = class_exists('Env') && Env::get('AUTH_ALLOW_GUEST', '0') === '1';
        if (!self::isLoggedIn() && $allowGuest) {
            $guestUserId   = (int)Env::get('AUTH_GUEST_USER_ID', 0);
            $guestPerfilId = (int)Env::get('AUTH_GUEST_PERFIL_ID', 0);
            $_SESSION['usuarioId']          = $guestUserId;
            $_SESSION['perfilId']           = $guestPerfilId;
            $_SESSION['perfilIdSession']    = $guestPerfilId;
            $_SESSION['empresaIdSession']   = 1;
            $_SESSION['esRootSession']      = 0;
            return;
        }

        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    /**
     * Retorna true si hay usuario logueado.
     */
    public static function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['usuarioId']);
    }

    /**
     * Obtiene datos de contexto del usuario logueado.
     */
    public static function getUserContext(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuarioId = isset($_SESSION['usuarioId']) ? (int)$_SESSION['usuarioId'] : 0;
        $usuarioCod = isset($_SESSION['usuarioCodSession']) ? strval($_SESSION['usuarioCodSession']) : '';
        $usuarioNom = isset($_SESSION['usuarioNombreSession']) ? strval($_SESSION['usuarioNombreSession']) : '';
        $empresaId = isset($_SESSION['empresaIdSession']) ? (int)$_SESSION['empresaIdSession'] : 0; 
        $fundoId = isset($_SESSION['fundoIdSession']) ? (int)$_SESSION['fundoIdSession'] : 0;
        $perfilId = isset($_SESSION['perfilIdSession']) ? (int)$_SESSION['perfilIdSession'] : 0;
        $esRoot = isset($_SESSION['esRootSession']) ? (int)$_SESSION['esRootSession'] : 0;
        $esAdmin = isset($_SESSION['esAdminSession']) ? (int)$_SESSION['esAdminSession'] : 0;
        if ($usuarioId === 0 && class_exists('Env') && Env::get('AUTH_ALLOW_GUEST', '0') === '1') {
            $usuarioId = (int)Env::get('AUTH_GUEST_USER_ID', 0);
        }
        $dispositivo = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip          = $_SERVER['REMOTE_ADDR'] ?? '';

        return [
            'usuarioId'   => $usuarioId,
            'usuarioCod'  => $usuarioCod,
            'usuarioNom'  => $usuarioNom,
            'empresaId'   => $empresaId,
            'fundoId'     => $fundoId,
            'perfilId'    => $perfilId,
            'esRoot'      => $esRoot,
            'esAdmin'     => $esAdmin,
            'dispositivo' => $dispositivo,
            'ip'          => $ip,
        ];
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
