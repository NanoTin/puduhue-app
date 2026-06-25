<?php
// menu.php

$rootPath = dirname(__DIR__, 2);
if (!class_exists('AuthMiddleware')) {
    $authPath = $rootPath . '/src/Middleware/AuthMiddleware.php';
    if (file_exists($authPath)) {
        require_once $authPath;
    }
}

$menuUserContext = class_exists('AuthMiddleware') ? AuthMiddleware::getUserContext() : [];
$perfilId = (int)($menuUserContext['perfilId'] ?? 0);
$menuItems = [];

try {
    require_once $rootPath . '/src/Controllers/Web/PerfilesmenusController.php';
    $perfilController = new \PerfilesmenusController();
    $menuResponse = $perfilController->listarMenusPorPerfil((int)$perfilId);
    $menuItems = $menuResponse['rows'] ?? $menuResponse ?? [];
} catch (Throwable $e) {
    error_log('No se pudo obtener el menu: ' . $e->getMessage());
}

// Agrupar por menupadre, filtrando inactivos.
$childrenByParent = [];
foreach ($menuItems as $item) {
    if (empty($item['menuactivo']) || empty($item['perfilmenuactivo'])) {
        continue;
    }
    $parent = $item['menupadre'] ?? null;
    if ($parent === 0) {
        $parent = null;
    }
    $childrenByParent[$parent][] = $item;
}

foreach ($childrenByParent as &$group) {
    usort($group, fn($a, $b) => ($a['menunvlord'] ?? 0) <=> ($b['menunvlord'] ?? 0));
}
unset($group);

$rootItems = $childrenByParent[null] ?? [];

if (!function_exists('renderMenuChildren')) {
    function renderMenuChildren(array $childrenByParent, int $parentId, int $level = 2): void
    {
        if (!isset($childrenByParent[$parentId])) {
            return;
        }

        echo '<ul class="menu-children level-' . $level . ' list-unstyled mb-0">';
        foreach ($childrenByParent[$parentId] as $item) {
            $id = (int)($item['menuid'] ?? 0);
            $hasChildren = isset($childrenByParent[$id]);
            $icon = $item['menuicono'] ?? 'bi-circle';
            $href = !empty($item['menuform']) ? '?route=' . htmlspecialchars($item['menuform']) : '#';

            $titleAttr = htmlspecialchars($item['menudesc'] ?? '');
            echo '<li class="menu-item" data-menu-id="' . $id . '">';
            echo '<a href="' . $href . '" class="menu-link d-flex align-items-center" data-close-drawer="true" title="' . $titleAttr . '">';
            echo '<span class="icon-wrapper me-2"><i class="bi ' . htmlspecialchars($icon) . '"></i></span>';
            echo '<span class="menu-label">' . htmlspecialchars($item['menudesc'] ?? '') . '</span>';
            if ($hasChildren) {
                echo '<i class="bi bi-chevron-right ms-auto text-muted small"></i>';
            }
            echo '</a>';

            if ($hasChildren) {
                renderMenuChildren($childrenByParent, $id, $level + 1);
            }

            echo '</li>';
        }
        echo '</ul>';
    }
}

if (!function_exists('renderMenuRoot')) {
    function renderMenuRoot(array $items, array $childrenByParent): void
    {
        if (empty($items)) {
            echo '<div class="text-muted small px-2">Sin menus disponibles</div>';
            return;
        }

        echo '<nav class="sidebar-menu" aria-label="Menu principal">';
        foreach ($items as $item) {
            $id = (int)($item['menuid'] ?? 0);
            $hasChildren = isset($childrenByParent[$id]);
            $icon = $item['menuicono'] ?? 'bi-grid';
            $href = !empty($item['menuform']) ? '?route=' . htmlspecialchars($item['menuform']) : '#';

            $titleAttr = htmlspecialchars($item['menudesc'] ?? '');
            echo '<div class="menu-group' . ($hasChildren ? ' has-children' : '') . '" data-menu-id="' . $id . '" data-menu-title="' . $titleAttr . '">';
            if ($hasChildren) {
                echo '<button class="menu-accordion w-100 d-flex align-items-center justify-content-between" type="button" title="' . $titleAttr . '">';
                echo '<span class="d-flex align-items-center">';
                echo '<span class="icon-wrapper me-2"><i class="bi ' . htmlspecialchars($icon) . '"></i></span>';
                echo '<span class="menu-label">' . htmlspecialchars($item['menudesc'] ?? '') . '</span>';
                echo '</span>';
                echo '<i class="bi bi-chevron-down small chevron"></i>';
                echo '</button>';
                echo '<div class="submenu">';
                renderMenuChildren($childrenByParent, $id, 2);
                echo '</div>';
            } else {
                echo '<a href="' . $href . '" class="menu-link d-flex align-items-center" data-close-drawer="true" title="' . $titleAttr . '">';
                echo '<span class="icon-wrapper me-2"><i class="bi ' . htmlspecialchars($icon) . '"></i></span>';
                echo '<span class="menu-label">' . htmlspecialchars($item['menudesc'] ?? '') . '</span>';
                echo '</a>';
            }
            echo '</div>';
        }
        echo '</nav>';
    }
}
?>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar shadow-sm" id="appSidebar">
    <div class="sidebar-inner d-flex flex-column">
        <div class="sidebar-top d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="sidebar-dot"></span>
                <span class="text-white-50 text-uppercase fw-semibold small menu-label">Navegacion</span>
            </div>
            <button class="btn btn-outline-light btn-sm d-none d-md-inline-flex" id="collapseSidebarBtn" type="button" title="Colapsar barra">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>
        </div>
        <?php renderMenuRoot($rootItems, $childrenByParent); ?>
    </div>
</aside>

<div class="menu-flyout" id="menuFlyout"></div>

<main class="main-content flex-grow-1" id="mainContent">
