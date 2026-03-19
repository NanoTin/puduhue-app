<?php
// head.php
$rootPath = dirname(__DIR__, 2);
if (!class_exists('AuthMiddleware')) {
    $authPath = $rootPath . '/src/Middleware/AuthMiddleware.php';
    if (file_exists($authPath)) {
        require_once $authPath;
    }
}

$userContext = class_exists('AuthMiddleware') ? AuthMiddleware::getUserContext() : [];
$usuarioNombre = $userContext['usuarioNom'] ?? '';
$empresaId = (int)($userContext['empresaId'] ?? 0);
$empresaNombre = '';

if ($empresaId > 0) {
    $empresaServicePath = $rootPath . '/src/Services/EmpresasService.php';
    if (file_exists($empresaServicePath)) {
        require_once $empresaServicePath;
        $empresaService = new \EmpresasService();
        $empresaNombre = $empresaService->obtenerNombreEmpresa($empresaId) ?? '';
    }
}

if ($empresaNombre === '' && $empresaId > 0) {
    $empresaNombre = 'Empresa #' . $empresaId;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Puduhue App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    >

    <style>
        :root {
            --sidebar-width: 270px;
            --sidebar-collapsed-width: 76px;
            --header-height: 56px;
            --sidebar-bg: #0f172a;
            --sidebar-border: #1e293b;
            --accent: #22c55e;
        }
        body {
            background-color: #f4f6f9;
            padding-top: var(--header-height);
        }
        .app-header {
            min-height: var(--header-height);
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.25);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1040;
        }
        .app-shell {
            min-height: calc(100vh - var(--header-height));
            padding-top: 0;
        }
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0b1224 0%, #0f172a 100%);
            color: #e5e7eb;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 1rem 0.75rem 1.25rem;
            border-right: 1px solid var(--sidebar-border);
            transition: width 0.2s ease, transform 0.25s ease;
            z-index: 1031;
        }
        .sidebar-inner {
            height: 100%;
        }
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
            z-index: 1030;
        }
        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .menu-group {
            position: relative;
        }
        .menu-accordion {
            background: none;
            border: 0;
            color: #e5e7eb;
            border-radius: 10px;
            padding: 0.65rem 0.7rem;
            transition: background-color 0.15s ease;
            width: 100%;
            text-align: left;
        }
        .menu-accordion:hover,
        .menu-link:hover {
            background-color: rgba(255, 255, 255, 0.06);
        }
        .menu-accordion .chevron {
            transition: transform 0.2s ease;
        }
        .menu-group.open > .menu-accordion .chevron {
            transform: rotate(180deg);
        }
        .menu-link {
            color: #e5e7eb;
            border-radius: 10px;
            padding: 0.55rem 0.65rem;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
            position: relative;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .menu-link .bi {
            font-size: 1rem;
        }
        .menu-children.level-2 {
            padding-left: 0.25rem;
            margin-top: 0.35rem;
            border-left: 1px solid rgba(255, 255, 255, 0.06);
            margin-left: 0.35rem;
        }
        .menu-children.level-3 {
            padding-left: 0.75rem;
        }
        .submenu {
            display: none;
            padding-left: 0.25rem;
        }
        .menu-group.open > .submenu {
            display: block;
        }
        .icon-wrapper {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.06);
        }
        .sidebar-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.7);
            display: inline-block;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: margin-left 0.2s ease;
            min-height: calc(100vh - var(--header-height));
            margin-top: 0;
        }
        body.sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
        }
        body.sidebar-collapsed .main-content {
            margin-left: var(--sidebar-collapsed-width);
        }
        body.sidebar-collapsed .menu-label {
            display: none;
        }
        body.sidebar-collapsed .menu-accordion .chevron {
            display: none;
        }
        body.sidebar-collapsed .submenu {
            position: static;
            display: none;
            background: transparent;
            padding: 0;
            box-shadow: none;
        }
        body.sidebar-collapsed .menu-group.open > .submenu {
            display: none;
        }
        body.sidebar-collapsed .menu-label {
            display: none;
        }
        .menu-flyout {
            position: fixed;
            top: var(--header-height);
            left: var(--sidebar-width);
            max-height: calc(100vh - var(--header-height));
            overflow-y: auto;
            width: 280px;
            background: linear-gradient(180deg, #0b1224 0%, #0f172a 100%);
            color: #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
            padding: 0.5rem 0.75rem 0.75rem;
            z-index: 1042;
            display: none;
        }
        .menu-flyout.visible {
            display: block;
        }
        .menu-flyout-header {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.35rem 0.35rem 0.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 0.35rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .menu-flyout .menu-link {
            white-space: nowrap;
        }
        .menu-flyout .menu-children {
            padding-left: 0;
            border-left: none;
            margin-left: 0;
        }
        .menu-flyout .menu-children.level-2 {
            padding-left: 0.25rem;
        }
        .menu-flyout .menu-children.level-3 {
            padding-left: 0.75rem;
        }
        .menu-flyout .menu-label {
            display: inline !important; /* ensure labels show even when body is collapsed */
        }
        .menu-flyout .chevron {
            display: inline;
        }
        body.sidebar-open .sidebar {
            transform: translateX(0);
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.25);
        }
        body.sidebar-open .sidebar-backdrop {
            opacity: 1;
            pointer-events: auto;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-width);
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            body.sidebar-collapsed .main-content {
                margin-left: 0;
            }
            .menu-flyout {
                left: 0;
                width: calc(100% - 1rem);
                margin-left: 0.5rem;
                margin-right: 0.5rem;
            }
        }
    </style>
</head>
<body>
<header class="navbar navbar-dark bg-dark app-header">
    <div class="container-fluid d-flex align-items-center">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light d-md-none" id="mobileMenuBtn" type="button" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="?route=dashboard/listar">
                <img src="assets/img/Logo_Puduhue2.png" alt="Puduhue Logo" class="me-2" style="width: 32px; height: 32px;"> Puduhue
            </a>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3 text-white-50 small">
            <div class="text-end">
                <div class="fw-semibold text-white"><?= htmlspecialchars((string)$usuarioNombre) ?></div>
                <button
                    type="button"
                    class="btn btn-link p-0 text-white-50 text-decoration-none"
                    id="changeCompanyTrigger"
                    data-bs-toggle="modal"
                    data-bs-target="#companySwitchModal"
                    data-current-company-id="<?= htmlspecialchars((string)$empresaId) ?>"
                >
                    <?= htmlspecialchars((string)($empresaNombre !== '' ? $empresaNombre : 'Empresa no definida')) ?>
                </button>
            </div>
            <div class="dropdown">
                <button
                    class="btn btn-outline-light btn-sm dropdown-toggle"
                    type="button"
                    id="userMenuBtn"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Menu de usuario"
                >
                    <i class="bi bi-person-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuBtn">
                    <li>
                        <a class="dropdown-item" href="?route=users/change-password">Cambio Contraseña</a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="?route=auth/logout">Cerrar sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<div class="modal fade" id="companySwitchModal" tabindex="-1" aria-labelledby="companySwitchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="companySwitchModalLabel">Cambiar empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="companySwitchMessage" class="text-muted small mb-3"></div>
                <div id="companySwitchList" class="d-flex flex-column gap-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="companySwitchSave">Guardar</button>
            </div>
        </div>
    </div>
</div>

<div class="app-shell d-flex position-relative">
