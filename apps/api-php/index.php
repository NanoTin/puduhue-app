<?php

$root = dirname(__DIR__, 2);

require_once $root . '/src/Config/Env.php';
require_once $root . '/src/Config/Database.php';
require_once $root . '/src/Helpers/Logger.php';
require_once $root . '/src/Helpers/ApiException.php';
require_once $root . '/src/Helpers/ApiResponse.php';
require_once $root . '/src/Helpers/ApiRequest.php';
require_once $root . '/src/Middleware/ApiBearerAuthMiddleware.php';
require_once $root . '/src/Services/ApiRequestLogService.php';
require_once $root . '/src/Services/Api/ProdlecheDetalleApiService.php';
require_once $root . '/src/Services/Api/SuplanimalDetalleApiService.php';
require_once $root . '/src/Controllers/Api/V1/ProdlecheDetalleController.php';
require_once $root . '/src/Controllers/Api/V1/SuplanimalDetalleController.php';
require_once $root . '/src/Routes/api.php';

Env::load();

$timezone = Env::get('TIMEZONE', 'UTC');
date_default_timezone_set($timezone);

handleApiRequest();
