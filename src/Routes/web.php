<?php

/**
 * PUDUHUE APP – WEB ROUTER (versión final producción)
 *
 * Enruta todas las solicitudes del frontend interno PHP.
 */

use App\Helpers\Logger;

// Autoload Composer + App
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/Helpers/CsrfHelper.php';
require_once dirname(__DIR__) . '/Helpers/FlashMessageHelper.php';

// Cargar helpers básicos si existen
\Env::load();

/**
 * Cargar el menú desde JSON
 */
$menusJsonFile = dirname(__DIR__, 2) . '/apps/web-php/menu.json';

$menusTree = [];
if (file_exists($menusJsonFile)) {
    $menusTree = json_decode(file_get_contents($menusJsonFile), true);
    if (!is_array($menusTree)) {
        $menusTree = [];
    }
}

/**
 * Convierte el árbol de menú en una lista plana:
 *   "empresas/listar" => true
 *   "usuarios/editar" => true
 */
function buildMenuRouteIndex(array $tree): array
{
    $index = [];

    $walk = function ($items) use (&$index, &$walk) {
        foreach ($items as $node) {
            if (!empty($node['menuform'])) {
                $clean = str_replace('.php', '', $node['menuform']);
                $clean = str_replace('_', '/', $clean);
                $index[strtolower($clean)] = true;
            }
            if (!empty($node['children'])) {
                $walk($node['children']);
            }
        }
    };

    $walk($tree);
    return $index;
}

$allowedMenuRoutes = buildMenuRouteIndex($menusTree);

// ---------------------------------------------------------------
//               PUNTO CENTRAL DE MANEJO DE SOLICITUDES
// ---------------------------------------------------------------

function handleWebRequest(array $menuData = []): void
{
    global $allowedMenuRoutes;

    // Si se entrega un menú desde el Front Controller, úsalo
    if (!empty($menuData)) {
        $allowedMenuRoutes = buildMenuRouteIndex($menuData);
    }

    // Toda página interna requiere sesión activa
    \AuthMiddleware::requireAuth();
    $user = \AuthMiddleware::getUserContext(); // datos del usuario logueado

    // Route por GET
    $rawRoute = $_GET['route'] ?? null;
    $route = $rawRoute ?? 'dashboard';

    // Normalizar route
    $route = trim(strtolower($route));

    // Si el menuform = "#", es un placeholder: volver a la última ruta válida (o dashboard).
    if ($route === '' || $route === '#' || str_starts_with($route, '#')) {
        $fallbackRoute = $_SESSION['last_route'] ?? 'dashboard';
        header('Location: ?route=' . urlencode($fallbackRoute));
        exit;
    }

    // Convertir formatos alternativos:
    // empresas_listar.php → empresas/listar
    if (str_ends_with($route, '.php')) {
        $route = str_replace('.php', '', $route);
    }
    // Solo normalizar "_" cuando no viene como ruta explicita con "/"
    if (!str_contains($route, '/')) {
        $route = str_replace('_', '/', $route);
    }

    // Extraer módulo + acción
    $parts = explode('/', $route);
    $module = $parts[0] ?? '';
    $action = $parts[1] ?? 'listar';

    $allowActions = ['crear', 'editar', 'anular', 'eliminar', 'sync', 'visualizar', 'carga_masiva']; // aplica a cualquier módulo
    
    // Acciones especiales por módulo (no vienen del menú)
    $allowActionsByModule = [
        'menus' => ['padres-por-nivel'],
        'usuarios' => ['cambiopassword', 'change-password', 'generar-token-api'],
        'users' => ['change-password'],
        'companies' => ['list-for-change'],
        'empresas' => ['cambiar-empresa'],
        'auth' => ['logout'],
    ];
    $routeKey = "$module/$action";
    
    $extraAllowed = in_array($action, $allowActions, true)
    || (isset($allowActionsByModule[$module]) && in_array($action, $allowActionsByModule[$module], true));

    // Validación de menú
    if (!$extraAllowed && !isset($allowedMenuRoutes[$routeKey])) {
        http_response_code(404);
        echo "<h2>404 – Módulo no encontrado</h2>";
        return;
    }

    // Guardar última ruta válida para usarla si se navega a un placeholder (#).
    $_SESSION['last_route'] = $routeKey;

    /*if (!in_array($action, $allowActions, true) && !isset($allowedMenuRoutes[$routeKey])) {
        http_response_code(404);
        echo "<h2>404 – Módulo no encontrado</h2>";
        return;
    }*/
    /*if (!isset($allowedMenuRoutes["$module/$action"])) {
        http_response_code(404);
        echo "<h2>404 – Módulo no encontrado</h2>";
        return;
    }*/

    // MAPEO DE MÓDULOS → CONTROLADORES (globales)
    $map = [
        'dashboard'         => \DashboardController::class,
        'reportelechebi'    => \ReportelechebiController::class,
        'empresas'          => \EmpresasController::class,
        'usuarios'          => \UsuariosController::class,
        'users'             => \UsuariosController::class,
        'companies'         => \EmpresasController::class,
        'auth'              => \AuthController::class,
        'fundos'            => \FundosController::class,
        'menus'             => \MenusController::class,
        'clientes'          => \ClientesController::class,
        'fundostipos'       => \FundostiposController::class,
        'fundosestanques'   => \FundosestanquesController::class,
        'fundosestanquesclientes' => \FundosestanquesclientesController::class,
        'invbodegas'        => \InvbodegasController::class,
        'invcateganimal'    => \InvcateganimalController::class,
        'invitems'          => \InvitemsController::class,
        'perfiles'          => \PerfilesController::class,
        'prodleche'         => \ProdlecheController::class,
        'prodlechereporte'  => \ProdlechereporteController::class,
        'prodlechetipos'    => \ProdlechetiposController::class,
        'retiroleche'       => \RetirolecheController::class,
        'suplanimal'        => \SuplanimalController::class,
        'usuariosempresas'  => \UsuariosempresasController::class,
        'usuariosfundos'    => \UsuariosfundosController::class,
        'perfilesmenus'     => \PerfilesmenusController::class,
        'invunidmed'        => \InvunidmedController::class,
        'pptolechemensual'  => \PptolechemensualController::class,
        'proylechediaria'   => \ProylechediariaController::class,
    ];

    if (!isset($map[$module])) {
        http_response_code(404);
        echo "<h2>404 – Controlador no encontrado</h2>";
        return;
    }

    $controllerClass = $map[$module];
    if (!class_exists($controllerClass)) {
        $controllerFile = basename(str_replace('\\', '/', $controllerClass)) . '.php';
        $controllerPath = dirname(__DIR__) . '/Controllers/Web/' . $controllerFile;
        if (file_exists($controllerPath)) {
            require_once $controllerPath;
        }
    }

    if (!class_exists($controllerClass)) {
        http_response_code(500);
        echo "<h2>Error interno: El controlador no existe.</h2>";
        return;
    }

    $controller = new $controllerClass();

    // ACCIÓN DEL CONTROLADOR
    $method = match ($action) {
        'listar'   => 'listar',
        'crear'    => ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'crearPost' : 'crearForm',
        'editar'   => ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'editarPost' : 'editarForm',
        'visualizar' => 'visualizarForm',
        'anular'   => 'anularPost',
        'eliminar' => 'eliminarPost',
        'detalle'  => 'detalle',
        'sync'     => 'syncPost',
        'carga_masiva' => 'cargaMasivaPost',
        'change-password' => ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'cambioClaveGuardar' : 'cambioClaveForm',
        'cambiopassword' => ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'cambioClaveGuardar' : 'cambioClaveForm',
        'generar-token-api' => 'generarTokenApiPost',
        'list-for-change' => 'listForChange',
        'cambiar-empresa' => 'cambiarEmpresaPost',
        'logout' => 'logout',

        // AJAX: cargar padres por nivel (solo Menus)
        'padres-por-nivel' => 'padresPorNivel',
        default    => null,
    };

    if (!$method || !method_exists($controller, $method)) {
        http_response_code(404);
        echo "<h2>404 – Acción no encontrada</h2>";
        return;
    }

    if (isCsrfProtectedWebPost($method) && !CsrfHelper::validate(CsrfHelper::tokenFromRequest(), 'web')) {
        handleCsrfFailure($module);
        return;
    }

    try {
        $partial = isset($_GET['partial']) ? true : false;
        $controller->$method($partial);
    } catch (Throwable $e) {
        Logger::error("Error en router: " . $e->getMessage());
        http_response_code(500);
        echo "<h2>Error interno del servidor</h2>";
    }
}

function isCsrfProtectedWebPost(string $method): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return false;
    }

    return in_array($method, [
        'crearPost',
        'editarPost',
        'anularPost',
        'eliminarPost',
        'syncPost',
        'cargaMasivaPost',
        'cambioClaveGuardar',
        'generarTokenApiPost',
        'cambiarEmpresaPost',
    ], true);
}

function handleCsrfFailure(string $module): void
{
    $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 403,
            'message' => 'Sesion expirada o token CSRF invalido.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }

    FlashMessageHelper::toast('Sesion expirada o token CSRF invalido. Intente nuevamente.', 'danger');
    $fallbackRoute = $module !== '' ? $module . '/listar' : 'dashboard';
    header('Location: ?route=' . urlencode($fallbackRoute));
}
