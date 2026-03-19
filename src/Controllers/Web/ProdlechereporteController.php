<?php

class ProdlechereporteController
{
    private \ProdlechereporteService $service;
    private \UsuariosfundosService $usuariosfundosService;
    private \PptolechemensualService $pptolechemensualService;
    private \ProylechediariaService $proylechediariaService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ProdlechereporteService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $usuariosfundosPath = dirname(__DIR__, 2) . '/Services/UsuariosfundosService.php';
        if (file_exists($usuariosfundosPath)) {
            require_once $usuariosfundosPath;
        }

        $pptolechePath = dirname(__DIR__, 2) . '/Services/PptolechemensualService.php';
        if (file_exists($pptolechePath)) {
            require_once $pptolechePath;
        }

        $proylechePath = dirname(__DIR__, 2) . '/Services/ProylechediariaService.php';
        if (file_exists($proylechePath)) {
            require_once $proylechePath;
        }

        $this->service = new \ProdlechereporteService();
        $this->usuariosfundosService = new \UsuariosfundosService();
        $this->pptolechemensualService = new \PptolechemensualService();
        $this->proylechediariaService = new \ProylechediariaService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $anioActual = isset($_GET['filtroAnio']) && $_GET['filtroAnio'] !== ''
            ? (int)$_GET['filtroAnio']
            : (int)date('Y');
        $mesActual = isset($_GET['filtroMes']) && $_GET['filtroMes'] !== ''
            ? (int)$_GET['filtroMes']
            : (int)date('n');
        $fundoId = isset($_GET['filtroFundoid']) && $_GET['filtroFundoid'] !== '' ? (int)$_GET['filtroFundoid'] : null;

        $result = $this->service->listarProdlecheReporte(
            $anioActual,
            $mesActual,
            $fundoId,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $prodlecheReporte = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $fundosOptions = $this->usuariosfundosService->listarFundosPorUsuarioFormSelect(
            1,
            $user['usuarioId'],
            $user['empresaId'],
            1
        );

        $presupuestoRows = $this->pptolechemensualService->listarPresupuestoMensual(
            $anioActual,
            $mesActual,
            $fundoId
        );

        $proyeccionLecheRows = $this->proylechediariaService->listarProyLecheDiariaPorAMes(
            $anioActual,
            $mesActual
        );

        $viewFile = $this->viewPath('prodlechereporte.php');
        require $viewFile;
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }
}
