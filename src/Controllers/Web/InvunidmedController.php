<?php

class InvunidmedController
{
    private \InvunidmedService $service;
    private \ErpPreItemsSyncService $preItemsSyncService;

    public function __construct()
    {
        $servicePath = dirname(__DIR__, 2) . '/Services/InvunidmedService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }
        $preItemsSyncServicePath = dirname(__DIR__, 2) . '/Services/ErpPreItemsSyncService.php';
        if (file_exists($preItemsSyncServicePath)) {
            require_once $preItemsSyncServicePath;
        }

        $this->service = new \InvunidmedService();
        $this->preItemsSyncService = new \ErpPreItemsSyncService();
    }

    public function listar(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        $filtros = [
            'filtroInvunidmeddsc'    => $_GET['filtroInvunidmeddsc'] ?? null,
            'filtroErpunidmedcod'    => $_GET['filtroErpunidmedcod'] ?? null,
            'filtroInvunidmedactivo' => $_GET['filtroInvunidmedactivo'] ?? null,
        ];

        $result = $this->service->listarInvunidmed(
            $filtros,
            $user['usuarioId'],
            $user['dispositivo'],
            $user['ip']
        );

        $invunidmed = $result['rows'] ?? [];
        $meta = $result['meta'] ?? null;

        $viewFile = $this->viewPath('invunidmed_listar.php');
        require $viewFile;
    }

    public function syncPost(bool $partial = false): void
    {
        AuthMiddleware::requireAuth();
        $user = AuthMiddleware::getUserContext();

        try {
            $resultado = $this->preItemsSyncService->sincronizarUnidadesMedida(
                $user['usuarioId'],
                $user['dispositivo'],
                $user['ip'],
                'MANUAL'
            );

            $estado = strtoupper((string)($resultado['estado'] ?? 'OK'));
            if ($estado === 'OK') {
                $this->setToast('Sincronización de Unidades de Medida completada.', 'success');
            } elseif ($estado === 'PARCIAL') {
                $omitidos = (int)($resultado['conteos']['omitidos'] ?? 0);
                $this->setToast('Sincronización completada parcialmente. Registros omitidos: ' . $omitidos . '. Revise el detalle técnico en logs.', 'warning');
            } else {
                $this->setToast('La sincronización de Unidades de Medida finalizó con errores.', 'danger');
            }
        } catch (\Throwable $e) {
            $this->setToast($e->getMessage(), 'danger');
        }

        header('Location: ?route=invunidmed/listar');
        exit;
    }

    public function crearForm(bool $partial = false): void
    {
        $this->bloquearGestionManual('crear');
    }

    public function crearPost(bool $partial = false): void
    {
        $this->bloquearGestionManual('crear');
    }

    public function editarForm(bool $partial = false): void
    {
        $this->bloquearGestionManual('editar');
    }

    public function editarPost(bool $partial = false): void
    {
        $this->bloquearGestionManual('editar');
    }

    public function anularPost(bool $partial = false): void
    {
        $this->bloquearGestionManual('anular');
    }

    private function bloquearGestionManual(string $accion): void
    {
        $this->setToast('La gestión manual de unidades de medida está bloqueada. Use sincronización ERP.', 'warning');
        header('Location: ?route=invunidmed/listar');
        exit;
    }

    private function viewPath(string $fileName): string
    {
        $basePath = dirname(__DIR__, 3);
        return $basePath . '/apps/web-php/' . $fileName;
    }

    private function setToast(string $message, string $type = 'info'): void
    {
        require_once dirname(__DIR__, 2) . '/Helpers/FlashMessageHelper.php';
        FlashMessageHelper::toast($message, $type);
    }
}
