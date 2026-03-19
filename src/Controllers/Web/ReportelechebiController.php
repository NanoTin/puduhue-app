<?php

class ReportelechebiController
{
    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $viewFile = $this->viewPath('reporte_leche_bi.php');
        require $viewFile;
    }


    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }
}   