<?php

class DashboardController
{
    private \DashboardService $service;
    private \UsuariosfundosService $usuariosfundosService;

    public function __construct()
    {
        // Cargar servicio si no está autoload
        $servicePath = dirname(__DIR__, 2) . '/Services/DashboardService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $usuariosfundosPath = dirname(__DIR__, 2) . '/Services/UsuariosfundosService.php';
        if (file_exists($usuariosfundosPath)) {
            require_once $usuariosfundosPath;
        }

        $this->service = new \DashboardService();
        $this->usuariosfundosService = new \UsuariosfundosService();
    }

    public function listar(bool $partial = false): void
    {
        \AuthMiddleware::requireAuth();
        $user = \AuthMiddleware::getUserContext();

        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(
            1,
            $user['usuarioId'],
            $user['empresaId'],
            1
        );
        $fundosOptions = is_array($fundosOptions) ? array_values($fundosOptions) : [];

        $filtroFundoId = isset($_GET['filtroFundoid']) ? (string)$_GET['filtroFundoid'] : '';
        $mostrarTodos = count($fundosOptions) > 1;

        if (!$mostrarTodos && !empty($fundosOptions)) {
            $filtroFundoId = (string)($fundosOptions[0]['fundoid'] ?? '');
        }

        $fundoId = $filtroFundoId !== '' ? (int)$filtroFundoId : null;

        $data = $this->service->obtenerResumen(
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip'],
            $fundoId
        );

        $cards = $data['cards'] ?? [];
        $charts = $data['charts'] ?? [];

        $temporada = $data['temporada'] ?? null;

        $viewFile = $this->viewPath('dashboard.php');
        require $viewFile;
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }
}
