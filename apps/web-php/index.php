<?php
/**
 * Puduhue App - Front Controller
 *
 * Responsable de:
 *  - Cargar autoload
 *  - Cargar configuracion .env
 *  - Iniciar sesion
 *  - Validar sesion (excepto login)
 *  - Cargar menu dinamico
 *  - Delegar la ejecucion al Router Web (Routes/web.php)
 */

$isSecureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params([
    'httponly' => true,
    'secure'   => $isSecureCookie,
    'samesite' => 'Lax',
]);
session_start();

// ---------------------------------------------------------
// 1) Autoload
// ---------------------------------------------------------
$root = dirname(__DIR__, 2); // /home/.../webapp.miempresa.cl
require_once $root . '/vendor/autoload.php';

// ---------------------------------------------------------
// 2) Cargar configuracion (.env)
// ---------------------------------------------------------
require_once $root . '/src/Config/Env.php';
Env::load(); // carga variables globales

// ---------------------------------------------------------
// 3) Middlewares principales
// ---------------------------------------------------------
require_once $root . '/src/Middleware/AuthMiddleware.php';
require_once $root . '/src/Helpers/Logger.php';
require_once $root . '/src/Controllers/Web/PerfilesmenusController.php';

use App\Helpers\Logger;

AuthMiddleware::requireAuth();
$user = AuthMiddleware::getUserContext();
// ---------------------------------------------------------
// 4) Si no esta logueado, redirigir a login.php (excepto si ya estas en login.php)
//    Mientras no exista login, se permite un usuario/perfil de respaldo.
// ---------------------------------------------------------
$currentFile = basename($_SERVER['PHP_SELF']);
$allowGuest = Env::get('AUTH_ALLOW_GUEST', '0') === '1'; // Login ya implementado; guest solo si se habilita explicitamente
if ($currentFile !== 'login.php' && !AuthMiddleware::isLoggedIn()) {
    if ($allowGuest) {
        $guestUserId   = (int)Env::get('AUTH_GUEST_USER_ID', 0);
        $guestPerfilId = (int)Env::get('AUTH_GUEST_PERFIL_ID', 0);
        $_SESSION['usuarioId']          = $guestUserId;
        $_SESSION['perfilId']           = $guestPerfilId;
        $_SESSION['perfilIdSession']    = $guestPerfilId;
        $_SESSION['empresaIdSession']   = 1;
        $_SESSION['esRootSession']      = 0;
        $_SESSION['usuarioNombreSession'] = 'Invitado';
        $_SESSION['esAdminSession']      = 0;
    } else {
        header("Location: login.php");
        exit;
    }
}

// ---------------------------------------------------------
// 5) Cargar menu dinamico desde BD
// ---------------------------------------------------------
$menuData = [];
$perfilId = $user['perfilId'] ?? 0;

if (!function_exists('buildMenuTreeFromRows')) {
    function buildMenuTreeFromRows(array $items): array
    {
        $byParent = [];
        foreach ($items as $item) {
            if (empty($item['menuactivo']) || empty($item['perfilmenuactivo'])) {
                continue;
            }
            $parent = $item['menupadre'] ?? null;
            $byParent[$parent][] = $item;
        }

        foreach ($byParent as &$group) {
            usort($group, fn($a, $b) => ($a['menunvlord'] ?? 0) <=> ($b['menunvlord'] ?? 0));
        }
        unset($group);

        $build = function ($parent) use (&$build, &$byParent): array {
            if (!isset($byParent[$parent])) {
                return [];
            }

            $branch = [];
            foreach ($byParent[$parent] as $item) {
                $node = [
                    'menuid'     => $item['menuid'] ?? 0,
                    'menudesc'   => $item['menudesc'] ?? '',
                    'menuform'   => $item['menuform'] ?? '',
                    'menuicono'  => $item['menuicono'] ?? '',
                    'menunivel'  => $item['menunivel'] ?? 0,
                    'menunvlord' => $item['menunvlord'] ?? 0,
                ];

                $children = $build($item['menuid'] ?? null);
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                $branch[] = $node;
            }
            return $branch;
        };

        return $build(null);
    }
}

try {
    $perfilController = new \PerfilesmenusController();
    $menuResponse = $perfilController->listarMenusPorPerfil((int)$perfilId);
    $menuRows = $menuResponse['rows'] ?? $menuResponse ?? [];
    $menuData = buildMenuTreeFromRows($menuRows);
} catch (Throwable $e) {
    // Si falla la carga del menu, continuar sin bloquear la app.
    Logger::error('No se pudo cargar el menu desde BD: ' . $e->getMessage());
}

// ---------------------------------------------------------
// 6) Cargar Router Web
// ---------------------------------------------------------
require_once $root . '/src/Routes/web.php';

// Despacha la peticion
handleWebRequest($menuData);
