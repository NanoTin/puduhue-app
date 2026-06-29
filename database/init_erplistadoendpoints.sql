INSERT INTO `erplistadoendpoints` (
  `erpendpointcodigo`,
  `erpendpointdescripcion`,
  `erpendpointrecurso`,
  `erpendpointmetodo`,
  `erpendpointtipo`,
  `erpendpointproposito`,
  `erpendpointgrupoid`,
  `erpendpointorden`,
  `erpendpointrequierecodigo`,
  `erpendpointpermiteondemand`,
  `erpendpointpermiteauto`,
  `erpendpointfrecuencia`,
  `erpendpointformulariocall`,
  `erpendpointjsonarchivoejemplo`,
  `erpendpointactivo`,
  `auditcreacionusuarioid`,
  `auditcreaciondispositivo`,
  `auditcreacionip`
) VALUES
  ('ERP_DIMENSIONES_LIST', 'Dimensiones', '/api/reports/DIMENSIONES', 'GET', 'REPORT_VIEWER', 'MAESTRO_GET', 0, 1, 0, 1, 1, 'MENSUAL', NULL, 'dimensiones_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_PARTIDAS_FINANCIERAS_LIST', 'Partidas Financieras', '/api/partidafinanciera/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 0, 2, 0, 1, 1, 'MENSUAL', NULL, 'partidas_financieras_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_CENTROS_COSTOS_LIST', 'Centros de Costos', '/api/PDHCentroCosto/list', 'GET', 'CUSTOM', 'MAESTRO_GET', 0, 3, 0, 1, 1, 'MENSUAL', NULL, 'centros_costos_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_MONEDAS_LIST', 'Monedas', '/api/moneda/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 0, 4, 0, 1, 1, 'MENSUAL', NULL, 'monedas_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_TASAS_IMPOSITIVAS_LIST', 'Tasas Impositivas', '/api/tasaImpositiva/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 1, 1, 0, 1, 1, 'MENSUAL', NULL, 'tasas_impositivas_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_TASAS_IMPOSITIVAS_DETALLE', 'Tasas Impositivas Consulta por Codigo', '/api/tasaImpositiva/{codigo}', 'GET', 'BASE_ERP', 'DETALLE_GET', 1, 2, 1, 0, 1, 'MENSUAL', NULL, 'tasas_impositivas_cod_TASA19c.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_UNIDADES_MEDIDA_LIST', 'Unidades de Medida', '/api/unidad/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 1, 3, 0, 1, 1, 'MENSUAL', 'invunidmed_listar.php', 'unidades_medidas_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_FAMILIAS_LIST', 'Familias', '/api/productoFamilia/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 1, 4, 0, 1, 1, 'MENSUAL', NULL, 'familias_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_SUBFAMILIAS_LIST', 'Sub Familias', '/api/productoSubfamilia/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 1, 5, 0, 1, 1, 'MENSUAL', NULL, 'sub_familias_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_SUBFAMILIAS_DETALLE', 'Sub Familias Consulta por Codigo', '/api/productoSubfamilia/{codigo}', 'GET', 'BASE_ERP', 'DETALLE_GET', 1, 6, 1, 0, 1, 'MENSUAL', NULL, 'sub_familias_cod_AZOXI.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_PRODUCTOS_LIST', 'Productos', '/api/producto/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 1, 7, 0, 1, 1, 'MENSUAL', 'invitems_listar.php', 'productos_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_PRODUCTOS_DETALLE', 'Productos Consulta por Codigo', '/api/producto/{codigo}', 'GET', 'BASE_ERP', 'DETALLE_GET', 1, 8, 1, 0, 1, 'MENSUAL', NULL, 'productos_cod_CON004.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_CONCEPTOS_LIST', 'Conceptos', '/api/Concepto/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 2, 1, 0, 1, 1, 'MENSUAL', NULL, 'conceptos_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_CUENTAS_CONTABLES_LIST', 'Cuentas Contables', '/api/cuenta/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 2, 2, 0, 1, 1, 'MENSUAL', NULL, 'cuentas_contables_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_CONDICIONES_PAGO_LIST', 'Condiciones de Pago', '/api/condicionPago/list', 'GET', 'BASE_ERP', 'MAESTRO_GET', 2, 3, 0, 1, 1, 'MENSUAL', NULL, 'condiciones_pago_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_CONDICIONES_PAGO_DETALLE', 'Condiciones de Pago Consulta por Codigo', '/api/condicionPago/{codigo}', 'GET', 'BASE_ERP', 'DETALLE_GET', 2, 4, 1, 0, 1, 'MENSUAL', NULL, 'condiciones_pago_cod_30.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_PROVEEDORES_LIST', 'Proveedores', '/api/PDHProveedor/list', 'GET', 'CUSTOM', 'MAESTRO_GET', 2, 5, 0, 1, 1, 'MENSUAL', NULL, 'proveedores_list.json', 1, 1, 'SCRIPT', '127.0.0.1'),
  ('ERP_PROVEEDORES_DETALLE', 'Proveedores Consulta por Codigo', '/api/PDHProveedor/{codigo}', 'GET', 'CUSTOM', 'DETALLE_GET', 2, 6, 1, 0, 1, 'MENSUAL', NULL, 'proveedores_cod_82392600-6.json', 1, 1, 'SCRIPT', '127.0.0.1')
ON DUPLICATE KEY UPDATE
  `erpendpointdescripcion` = VALUES(`erpendpointdescripcion`),
  `erpendpointrecurso` = VALUES(`erpendpointrecurso`),
  `erpendpointmetodo` = VALUES(`erpendpointmetodo`),
  `erpendpointtipo` = VALUES(`erpendpointtipo`),
  `erpendpointproposito` = VALUES(`erpendpointproposito`),
  `erpendpointgrupoid` = VALUES(`erpendpointgrupoid`),
  `erpendpointorden` = VALUES(`erpendpointorden`),
  `erpendpointrequierecodigo` = VALUES(`erpendpointrequierecodigo`),
  `erpendpointpermiteondemand` = VALUES(`erpendpointpermiteondemand`),
  `erpendpointpermiteauto` = VALUES(`erpendpointpermiteauto`),
  `erpendpointfrecuencia` = VALUES(`erpendpointfrecuencia`),
  `erpendpointformulariocall` = VALUES(`erpendpointformulariocall`),
  `erpendpointjsonarchivoejemplo` = VALUES(`erpendpointjsonarchivoejemplo`),
  `erpendpointactivo` = VALUES(`erpendpointactivo`),
  `auditedicionusuarioid` = 1,
  `auditediciondispositivo` = 'SCRIPT',
  `auditedicionip` = '127.0.0.1';

UPDATE `erplistadoendpoints` hijo
INNER JOIN `erplistadoendpoints` padre
  ON padre.`erpendpointcodigo` = 'ERP_TASAS_IMPOSITIVAS_LIST'
SET hijo.`erpendpointpadreid` = padre.`erpendpointid`
WHERE hijo.`erpendpointcodigo` = 'ERP_TASAS_IMPOSITIVAS_DETALLE';

UPDATE `erplistadoendpoints` hijo
INNER JOIN `erplistadoendpoints` padre
  ON padre.`erpendpointcodigo` = 'ERP_SUBFAMILIAS_LIST'
SET hijo.`erpendpointpadreid` = padre.`erpendpointid`
WHERE hijo.`erpendpointcodigo` = 'ERP_SUBFAMILIAS_DETALLE';

UPDATE `erplistadoendpoints` hijo
INNER JOIN `erplistadoendpoints` padre
  ON padre.`erpendpointcodigo` = 'ERP_PRODUCTOS_LIST'
SET hijo.`erpendpointpadreid` = padre.`erpendpointid`
WHERE hijo.`erpendpointcodigo` = 'ERP_PRODUCTOS_DETALLE';

UPDATE `erplistadoendpoints` hijo
INNER JOIN `erplistadoendpoints` padre
  ON padre.`erpendpointcodigo` = 'ERP_CONDICIONES_PAGO_LIST'
SET hijo.`erpendpointpadreid` = padre.`erpendpointid`
WHERE hijo.`erpendpointcodigo` = 'ERP_CONDICIONES_PAGO_DETALLE';

UPDATE `erplistadoendpoints` hijo
INNER JOIN `erplistadoendpoints` padre
  ON padre.`erpendpointcodigo` = 'ERP_PROVEEDORES_LIST'
SET hijo.`erpendpointpadreid` = padre.`erpendpointid`
WHERE hijo.`erpendpointcodigo` = 'ERP_PROVEEDORES_DETALLE';
