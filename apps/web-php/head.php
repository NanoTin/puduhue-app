<?php
// head.php
$rootPath = dirname(__DIR__, 2);
if (!class_exists('AuthMiddleware')) {
    $authPath = $rootPath . '/src/Middleware/AuthMiddleware.php';
    if (file_exists($authPath)) {
        require_once $authPath;
    }
}
require_once $rootPath . '/src/Helpers/CsrfHelper.php';

$userContext = class_exists('AuthMiddleware') ? AuthMiddleware::getUserContext() : [];
$csrfToken = CsrfHelper::generate('web');
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
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

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

    <link rel="stylesheet" href="assets/css/layout.css">
</head>
<body>
<script>
    (function () {
        try {
            if (localStorage.getItem('pdh_sidebar_collapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
        } catch (e) {
            // Storage can be unavailable in restricted browser modes.
        }
    })();
</script>
<header class="navbar navbar-dark bg-dark app-header">
    <div class="container-fluid d-flex align-items-center">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light d-md-none" id="mobileMenuBtn" type="button" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center" href="?route=dashboard/listar">
                <img src="assets/img/Logo_Puduhue2.png" alt="Puduhue Logo" class="me-2 app-brand-logo"> Puduhue
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
