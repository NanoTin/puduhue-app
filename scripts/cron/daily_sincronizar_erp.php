<?php

$root = dirname(__DIR__, 2);

require_once $root . '/src/Config/Env.php';
require_once $root . '/src/Config/Database.php';
require_once $root . '/src/Helpers/Logger.php';
require_once $root . '/src/Services/ProdlecheService.php';
require_once $root . '/src/Services/SuplanimalService.php';

Env::load();

$timezone = Env::get('TIMEZONE', 'UTC');
date_default_timezone_set($timezone);

$usuarioId = (int)Env::get('CRON_USUARIO_ID', 1);
$dispositivo = Env::get('CRON_DISPOSITIVO', 'CRON');
$ip = Env::get('CRON_IP', '127.0.0.1');

$db = Database::getInstance();
$prodlecheService = new ProdlecheService();
$suplanimalService = new SuplanimalService();

$totProdleche = 0;
$totSuplanimal = 0;
$totErrores = 0;

$prodlechePendientes = $db->select("SELECT prodlecheid FROM prodleche WHERE prodlechestatus = 'PND'");
foreach ($prodlechePendientes as $row) {
    $id = (int)($row['prodlecheid'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    try {
        $prodlecheService->sincronizarProdlecheConErp($id, $usuarioId, $dispositivo, $ip, 'CRON');
        $totProdleche++;
    } catch (\Throwable $e) {
        $totErrores++;
        Logger::error('CRON prodleche ' . $id . ': ' . $e->getMessage());
    }
}

$suplanimalPendientes = $db->select("SELECT suplanimalid FROM suplanimal WHERE suplanimalstatus = 'PND'");
foreach ($suplanimalPendientes as $row) {
    $id = (int)($row['suplanimalid'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    try {
        $suplanimalService->sincronizarSuplanimalConErp($id, $usuarioId, $dispositivo, $ip, 'CRON');
        $totSuplanimal++;
    } catch (\Throwable $e) {
        $totErrores++;
        Logger::error('CRON suplanimal ' . $id . ': ' . $e->getMessage());
    }
}

Logger::info(sprintf(
    'CRON sincronizacion ERP completado. Prodleche: %d, Suplanimal: %d, Errores: %d',
    $totProdleche,
    $totSuplanimal,
    $totErrores
));
