<?php

class ApirequestlogController
{
    private \ApiRequestLogMonitorService $service;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/ApiRequestLogMonitorService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->service = new \ApiRequestLogMonitorService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();

        $consultas = $this->service->getConsultas();
        $consultaSeleccionada = trim((string)($_GET['consulta'] ?? $this->service->consultaPorDefecto()));
        $resultado = $this->service->ejecutarConsulta($consultaSeleccionada);

        $consultaSeleccionada = $resultado['codigo'];
        $definicion = $resultado['definicion'];
        $rows = $resultado['rows'];
        $columnas = $definicion['columnas'] ?? [];

        $viewFile = $this->viewPath('apirequestlog_listar.php');
        require $viewFile;
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }
}
