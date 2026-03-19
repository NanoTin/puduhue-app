<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/src/Config/Env.php';
require_once dirname(__DIR__, 2) . '/src/Config/Database.php';
require_once dirname(__DIR__, 2) . '/src/Services/ClientesService.php';
require_once dirname(__DIR__, 2) . '/src/Services/EmpresasService.php';
require_once dirname(__DIR__, 2) . '/src/Services/FundosestanquesclientesService.php';
require_once dirname(__DIR__, 2) . '/src/Services/FundosestanquesService.php';
require_once dirname(__DIR__, 2) . '/src/Services/FundosService.php';
require_once dirname(__DIR__, 2) . '/src/Services/FundostiposService.php';
require_once dirname(__DIR__, 2) . '/src/Services/InvbodegasService.php';
require_once dirname(__DIR__, 2) . '/src/Services/InvcateganimalService.php';
require_once dirname(__DIR__, 2) . '/src/Services/InvitemsService.php';
require_once dirname(__DIR__, 2) . '/src/Services/InvunidmedService.php';
require_once dirname(__DIR__, 2) . '/src/Services/MenusService.php';
require_once dirname(__DIR__, 2) . '/src/Services/PerfilesmenusService.php';
require_once dirname(__DIR__, 2) . '/src/Services/UsuariosService.php';
require_once dirname(__DIR__, 2) . '/src/Services/PerfilesService.php';
require_once dirname(__DIR__, 2) . '/src/Services/PptolechemensualService.php';
require_once dirname(__DIR__, 2) . '/src/Services/ProdlecheService.php';
require_once dirname(__DIR__, 2) . '/src/Services/ProdlechetiposService.php';
require_once dirname(__DIR__, 2) . '/src/Services/ProylechediariaService.php';
require_once dirname(__DIR__, 2) . '/src/Services/RetirolecheService.php';
require_once dirname(__DIR__, 2) . '/src/Services/SuplanimalService.php';
require_once dirname(__DIR__, 2) . '/src/Services/UsuariosempresasService.php';
require_once dirname(__DIR__, 2) . '/src/Services/UsuariosfundosService.php';
require_once dirname(__DIR__, 2) . '/src/Helpers/ExcelExporter.php';
require_once dirname(__DIR__, 2) . '/src/Middleware/AuthMiddleware.php';
Env::load(); // make .env values available for Database/Auth
AuthMiddleware::requireAuth();
$user = AuthMiddleware::getUserContext();

$module = $_POST['exportModule'] ?? '';

try
{
    $yesNo = static fn($v) => ((string)$v === '1' || $v === 1 || $v === true) ? 'Si' : 'No';

    // Registry (allowlist) de modulos exportables
    $exportRegistry = [
        'clientes' => [
            'serviceClass' => ClientesService::class,
            'serviceMethod' => 'listarClientes',
            'columns' => [
                'clienteid'          => 'ID',
                'clienterut'         => 'RUT',
                'clienterazonsocial' => 'Razon Social',
                'clienteemail'       => 'Email',
                'clientecontacto'    => 'Contacto',
                'clienteactivo'      => 'Activo',
            ],
            'allowedFilters' => [
                'filtroClienterut',
                'filtroClienterazonsocial',
                'filtroClienteemail',
                'filtroClienteactivo',
            ],
            'formatters' => [
                'clienteactivo' => $yesNo,
            ],
        ],
        'empresas' => [
            'serviceClass' => EmpresasService::class,
            'serviceMethod' => 'listarEmpresas',
            'columns' => [
                'empresaid'      => 'ID',
                'empresarut'     => 'RUT',
                'razonsocial'    => 'Razon Social',
                'giro'           => 'Giro',
                'contactonombre' => 'Contacto',
                'empresaemail'   => 'Email',
                'empresaactivo'  => 'Activo',
            ],
            'allowedFilters' => [
                'filtroRazonsocial',
                'filtroEmpresarut',
                'filtroEmpresaactivo',
            ],
            'formatters' => [
                'empresaactivo' => $yesNo,
            ],
        ],
        'fundosestanquesclientes' => [
            'serviceClass' => FundosestanquesclientesService::class,
            'serviceMethod' => 'listarFundosestanquesclientes',
            'columns' => [
                'fundonombre'        => 'Fundo',
                'fundoestanquedsc'   => 'Estanque',
                'clienterazonsocial' => 'Cliente',
                'estanqueclientecod' => 'Codigo Cliente',
                'fndestcliactivo'    => 'Activo',
            ],
            'allowedFilters' => [
                'filtroFundoId',
                'filtroClienteid',
                'filtroFndestcliactivo',
            ],
            'formatters' => [
                'fndestcliactivo' => $yesNo,
            ],
        ],
        'fundosestanques' => [
            'serviceClass' => FundosestanquesService::class,
            'serviceMethod' => 'listarFundosestanques',
            'columns' => [
                'fundoestanqueid'                  => 'ID',
                'fundos_fundonombre'               => 'Fundo',
                'fundoestanquedsc'                 => 'Descripcion',
                'estanquesmarcas_estanquemarcadsc' => 'Marca',
                'fundoestanqueorden'               => 'Orden',
                'fundoestanqueactivo'              => 'Activo',
            ],
            'allowedFilters' => [
                'filtroFundoid',
                'filtroFundoestanquedsc',
                'filtroEstanquemarcaid',
                'filtroFundoestanqueactivo',
            ],
            'formatters' => [
                'fundoestanqueactivo' => $yesNo,
            ],
        ],
        'fundostipos' => [
            'serviceClass' => FundostiposService::class,
            'serviceMethod' => 'listarFundostipos',
            'columns' => [
                'fundotipoid'  => 'ID',
                'fundotipodsc' => 'Descripcion',
            ],
            'allowedFilters' => [
                'filtroFundotipodsc',
            ],
        ],
        'fundos' => [
            'serviceClass' => FundosService::class,
            'serviceMethod' => 'listarFundos',
            'columns' => [
                'fundoid'                  => 'ID',
                'fundonombre'              => 'Nombre',
                'fundostipos_fundotipodsc' => 'Tipo',
                'empresas_razonsocial'     => 'Empresa',
                'erpestablecimientocod'    => 'ERP Establ.',
                'erplotecod'               => 'ERP Lote',
                'erpleche_invbodegacod'    => 'ERP Bodega Leche',
                'reporteorden'             => 'Reporte Orden',
                'fundopabco'               => 'PABCO',
                'fundorup'                 => 'RUP',
                'fundoemail'               => 'Email',
                'fundoactivo'              => 'Activo',
            ],
            'allowedFilters' => [
                'filtroFundonombre',
                'filtroFundotipoid',
                'filtroEmpresaid',
                'filtroFundopabco',
                'filtroFundoactivo',
            ],
            'formatters' => [
                'fundopabco'  => $yesNo,
                'fundoactivo' => $yesNo,
            ],
        ],
        'invbodegas' => [
            'serviceClass' => InvbodegasService::class,
            'serviceMethod' => 'listarInvbodegas',
            'columns' => [
                'invbodegaid'        => 'ID',
                'invbodegadsc'       => 'Descripcion',
                'erpinvbodegacod'    => 'ERP Codigo',
                'fundos_fundonombre' => 'Fundo',
                'invbodactivo'       => 'Activo',
            ],
            'allowedFilters' => [
                'filtroInvbodegadsc',
                'filtroErpinvbodegacod',
                'filtroFundoid',
                'filtroInvbodactivo',
            ],
            'formatters' => [
                'invbodactivo' => $yesNo,
            ],
        ],
        'invcateganimal' => [
            'serviceClass' => InvcateganimalService::class,
            'serviceMethod' => 'listarInvcateganimal',
            'columns' => [
                'invcateganimalid'        => 'ID',
                'invcateganimaldsc'       => 'Descripcion',
                'erpinvcateganimalcod'    => 'ERP Codigo',
                'invcateganimalkilosxcab' => 'Kilos x Cab',
                'invcateganimalactivo'    => 'Activo',
            ],
            'allowedFilters' => [
                'filtroInvcateganimaldsc',
                'filtroErpinvcateganimalcod',
                'filtroInvcateganimalactivo',
            ],
            'formatters' => [
                'invcateganimalactivo' => $yesNo,
            ],
        ],
        'invitems' => [
            'serviceClass' => InvitemsService::class,
            'serviceMethod' => 'listarInvitems',
            'columns' => [
                'invitemid'     => 'ID',
                'invitemdsc'    => 'Descripcion',
                'invunidmeddsc' => 'Unidad',
                'erpinvitemcod' => 'ERP Codigo',
                'invitemleche'  => 'Leche',
                'invitemactivo' => 'Activo',
            ],
            'allowedFilters' => [
                'filtroInvitemdsc',
                'filtroInvunidmedid',
                'filtroErpinvitemcod',
                'filtroInvitemleche',
                'filtroInvitemactivo',
            ],
            'formatters' => [
                'invitemleche'  => $yesNo,
                'invitemactivo' => $yesNo,
            ],
        ],
        'invunidmed' => [
            'serviceClass' => InvunidmedService::class,
            'serviceMethod' => 'listarInvunidmed',
            'columns' => [
                'invunidmedid'     => 'ID',
                'invunidmeddsc'    => 'Descripcion',
                'erpunidmedcod'    => 'Codigo ERP',
                'invunidmedactivo' => 'Activo',
            ],
            'allowedFilters' => [
                'filtroInvunidmeddsc',
                'filtroErpunidmedcod',
                'filtroInvunidmedactivo',
            ],
            'formatters' => [
                'invunidmedactivo' => $yesNo,
            ],
        ],
        'menus' => [
            'serviceClass' => MenusService::class,
            'serviceMethod' => 'listarMenus',
            'columns' => [
                'menuid'     => 'ID',
                'menupadre'  => 'Padre',
                'menudesc'   => 'Descripcion',
                'menuform'   => 'Formulario',
                'menunvlord' => 'Orden',
                'menuicono'  => 'Icono',
                'menuactivo' => 'Activo',
            ],
            'allowedFilters' => [
                'filtroMenupadre',
                'filtroMenudesc',
                'filtroMenuactivo',
            ],
            'formatters' => [
                'menuactivo' => $yesNo,
            ],
        ],
        'perfilesmenus' => [
            'serviceClass' => PerfilesmenusService::class,
            'serviceMethod' => 'listarPerfilesmenus',
            'columns' => [
                'perfilid'             => 'ID Perfil',
                'perfiles_perfildesc'  => 'Perfil',
                'menuid'               => 'ID Menu',
                'menus_menudesc'       => 'Menu',
                'perfilmenuactivo'     => 'Activo',
            ],
            'allowedFilters' => [
                'filtroPerfilid',
                'filtroMenuid',
                'filtroPerfilmenuactivo',
            ],
            'formatters' => [
                'perfilmenuactivo' => $yesNo,
            ],
        ],
        'usuarios' => [
            'serviceClass' => UsuariosService::class,
            'serviceMethod' => 'listarUsuarios',
            'columns' => [
                'usuarioid'        => 'Codigo',
                'usuariorut'       => 'RUT',
                'usuarionombre'    => 'Nombre',
                'usuarioemail'     => 'Email',
                'perfildesc'       => 'Perfil',
                'empresadefault'   => 'Empresa',
                'usuarioesadmin'   => 'Admin',
                'usuariobloqueado' => 'Bloqueado',
                'usuarioactivo'    => 'Activo',
            ],
            'allowedFilters' => [
                'filtroUsuariorut',
                'filtroUsuarionombre',
                'filtroUsuarioemail',
                'filtroPerfilid',
                'filtroUsuarioesadmin',
                'filtroUsuariobloqueado',
                'filtroUsuarioactivo',
            ],
            'formatters' => [
                'usuarioesadmin'   => $yesNo,
                'usuariobloqueado' => $yesNo,
                'usuarioactivo'    => $yesNo,
            ],
        ],
        'perfiles' => [
            'serviceClass' => PerfilesService::class,
            'serviceMethod' => 'listarPerfiles',
            'columns' => [
                'perfilid'      => 'ID',
                'perfildesc'    => 'Perfil',
                'perfilesroot'  => 'Root',
                'perfilesadmin' => 'Admin',
                'perfilactivo'  => 'Activo',
            ],
            'allowedFilters' => [
                'filtroPerfildesc',
                'filtroPerfilesroot',
                'filtroPerfilesadmin',
                'filtroPerfilactivo',
            ],
            'formatters' => [
                'perfilesroot'  => $yesNo,
                'perfilesadmin' => $yesNo,
                'perfilactivo'  => $yesNo,
            ],
        ],
        'prodlechetipos' => [
            'serviceClass' => ProdlechetiposService::class,
            'serviceMethod' => 'listarProdlechetipos',
            'columns' => [
                'prodlechetipoid'  => 'ID',
                'prodlechetipodsc' => 'Descripcion',
                'invitemdsc'       => 'Item',
                'prodlecheventa'   => 'Venta',
                'prodlecheorden'   => 'Orden',
                'prodlecheactivo'  => 'Activo',
            ],
            'allowedFilters' => [
                'filtroProdlechetipodsc',
                'filtroInvitemid',
                'filtroProdlecheventa',
                'filtroProdlecheactivo',
            ],
            'formatters' => [
                'prodlecheventa'  => $yesNo,
                'prodlecheactivo' => $yesNo,
            ],
        ],
        'prodleche' => [
            'serviceClass' => ProdlecheService::class,
            'serviceMethod' => 'listarProdleche',
            'columns' => [
                'prodlecheid'               => 'ID',
                'prodlechestatus'           => 'Estatus',
                'fundonombre'               => 'Fundo',
                'prodlechefecha'            => 'Fecha',
                'prodlechehorario'          => 'Horario',
                'prodlechetotlitros'        => 'Tot Litros',
                'prodlechetotvacas'         => 'Tot Vacas',
                'prodlecheventatotlitros'   => 'Planta Litros',
                'prodlecheventatotvacas'    => 'Planta Vacas',
                'prodlecheventalitrosxvaca' => 'Planta Lts/Vacas',
                'prodlecheobservacion'      => 'Observacion',
            ],
            'allowedFilters' => [
                'filtroProdlecheid',
                'filtroProdlechestatus',
                'filtroEmpresaid',
                'filtroFundoid',
                'filtroFechaDesde',
                'filtroFechaHasta',
                'filtroProdlecheobservacion',
                'filtroProdlechehorario',
            ],
            'dateFields' => [
                'prodlechefecha' => 'dd-mm-yyyy',
            ],
        ],
        'retiroleche' => [
            'serviceClass' => RetirolecheService::class,
            'serviceMethod' => 'listarRetiroleche',
            'columns' => [
                'retirolecheid'          => 'ID',
                'fundonombre'            => 'Fundo',
                'retirolechefecha'       => 'Fecha',
                'fundoestanquedsc'       => 'Estanque',
                'clienterazonsocial'     => 'Cliente',
                'estanqueclientecod'     => 'Cod Cliente',
                'retirolechelitros'      => 'Litros',
                'retirolechetemperatura' => 'T',
                'retirolecheobservacion' => 'Observacion',
                'retirolechefoto'        => 'Imagen',
            ],
            'allowedFilters' => [
                'filtroFundoid',
                'filtroRetirolechestatus',
                'filtroFechaDesde',
                'filtroFechaHasta',
            ],
            'dateFields' => [
                'retirolechefecha' => 'dd-mm-yyyy',
            ],
        ],
        'suplanimal' => [
            'serviceClass' => SuplanimalService::class,
            'serviceMethod' => 'listarSuplanimal',
            'columns' => [
                'suplanimalid'          => 'ID',
                'fundonombre'           => 'Fundo',
                'invbodegadsc'          => 'Bodega',
                'suplanimalfecha'       => 'Fecha',
                'suplanimalobservacion' => 'Observacion',
                'cant_detalles'         => 'Cant. Detalles',
                'cant_detalles_pend_erp' => 'Cant. Pend. ERP',
                'suplanimalstatus'      => 'Estado',
            ],
            'allowedFilters' => [
                'filtroEmpresaid',
                'filtroFundoid',
                'filtroSuplanimalestatus',
                'filtroInvbodegaid',
                'filtroFechaDesde',
                'filtroFechaHasta',
                'filtroSuplanimalobservacion',
            ],
            'dateFields' => [
                'suplanimalfecha' => 'dd-mm-yyyy',
            ],
        ],
        'usuariosempresas' => [
            'serviceClass' => UsuariosempresasService::class,
            'serviceMethod' => 'listarUsuariosempresas',
            'columns' => [
                'usuarios_usuarionombre' => 'Usuario',
                'empresas_razonsocial'   => 'Empresa',
                'uedefault'              => 'Default',
            ],
            'allowedFilters' => [
                'filtroUsuarioid',
                'filtroEmpresaid',
            ],
            'formatters' => [
                'uedefault' => $yesNo,
            ],
        ],
        'usuariosfundos' => [
            'serviceClass' => UsuariosfundosService::class,
            'serviceMethod' => 'listarUsuariosfundos',
            'columns' => [
                'usuarios_usuarionombre' => 'Usuario',
                'fundos_fundonombre'     => 'Fundo',
                'ufdefault'              => 'Default',
            ],
            'allowedFilters' => [
                'filtroUsuarioid',
                'filtroFundoid',
            ],
            'formatters' => [
                'ufdefault' => $yesNo,
            ],
        ],
        'pptolechemensual' => [
            'serviceClass' => PptolechemensualService::class,
            'serviceMethod' => 'listarPptolechemensual',
            'columns' => [
                'pptolecanio'       => 'Anio',
                'pptolecmes'        => 'Mes',
                'fundonombre'       => 'Fundo',
                'pptoleclitros'     => 'Litros',
                'pptolecvacas'      => 'Vacas',
                'pptolecltsxvc'     => 'Lts x Vaca',
                'pptolecfecha'      => 'Fecha',
                'pptolecdiasdelmes' => 'Dias Mes',
            ],
            'allowedFilters' => [
                'filtroPptolecanio',
                'filtroPptolecmes',
                'filtroFundoid',
            ],
            'dateFields' => [
                'pptolecfecha' => 'dd-mm-yyyy',
            ],
        ],
        'proylechediaria' => [
            'serviceClass' => ProylechediariaService::class,
            'serviceMethod' => 'listarProylechediaria',
            'serviceArgs' => 'noUserContext',
            'columns' => [
                'proylechefecha'            => 'Fecha',
                'proylecheanio'             => 'Anio',
                'proylechemes'              => 'Mes',
                'proylecheventatotlitros'   => 'Litros',
                'proylecheventatotvacas'    => 'Vacas',
                'proylecheventatotltsxvaca' => 'Lts x Vaca',
            ],
            'allowedFilters' => [
                'filtroProylecheanio',
                'filtroProylechemes',
            ],
            'dateFields' => [
                'proylechefecha' => 'dd-mm-yyyy',
            ],
        ],
    ];

    // Validar modulo
    if (!$module || !isset($exportRegistry[$module])) {
        http_response_code(400);
        echo 'Modulo no soportado.';
        exit;
    }

    $cfg = $exportRegistry[$module];
    // Construir filtros desde POST (solo allowlist y sin vacios)
    $filtros = [];
    foreach ($cfg['allowedFilters'] as $k) {
        if (isset($_POST[$k])){ // && $_POST[$k] !== '') {
            $filtros[$k] = $_POST[$k];
        }
    }

    // Ejecutar servicio nuevamente con filtros
    $serviceClass = $cfg['serviceClass'];
    $serviceMethod = $cfg['serviceMethod'];

    $service = new $serviceClass();
    if (($cfg['serviceArgs'] ?? '') === 'noUserContext') {
        $result = $service->$serviceMethod($filtros);
    } else {
        $result = $service->$serviceMethod($filtros, $user['usuarioId'], $user['dispositivo'], $user['ip']);
    }

    $rows = $result['rows'] ?? [];
    if (empty($rows)) {
        error_log('No hay datos para exportar. ServiceClass: ' . $serviceClass . ', Method: ' . $serviceMethod . ', Filtros: ' . json_encode($filtros));
        exit;
    }
    // Exportar
    $exportName = $module . '_' . date('Ymd');
    $formatters = $cfg['formatters'] ?? [];
    $dateFields = $cfg['dateFields'] ?? [];

    ExcelExporter::export($exportName, $cfg['columns'], $rows, $formatters, $dateFields);
    exit;
} catch (Exception $ex) {
    http_response_code(500);
    echo 'Error al exportar: ' . $ex->getMessage();
    exit;;
}
