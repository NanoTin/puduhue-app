/*
Incremental 07 - Bases compartidas Compras.

Objetivo:
- preparar tablas y permisos base para ejecutar después los incrementales de REQ, pendientes de compra y PreOC;
- no incluye tablas de REQ/PreOC ni SP funcionales.

Notas:
- No se define unicidad condicional para `usuarioreqautorizadorfuerapptocompraorden`;
  esta regla se valida en SP/BE posteriormente.
- No se incluyen índices funcionales ni columnas generadas para tal validación.
*/

ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `usuariopermiteaprobreq` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede aprobar REQ y formar listas de firmantes REQ'
    AFTER `usuarioactivo`,
  ADD COLUMN IF NOT EXISTS `usuariopermiteaprobpreoc` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede aprobar PreOC y formar listas de firmantes PreOC'
    AFTER `usuariopermiteaprobreq`,
  ADD COLUMN IF NOT EXISTS `usuariocomprador` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede crear/gestionar PreOC si tiene acceso al formulario'
    AFTER `usuariopermiteaprobpreoc`,
  ADD COLUMN IF NOT EXISTS `usuariopermiteanularpreoc` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede anular PreOC con permiso especial cuando el estado lo permite'
    AFTER `usuariocomprador`,
  ADD COLUMN IF NOT EXISTS `usuariopermiteeditarprecios` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede editar precios cuando el flujo lo permita'
    AFTER `usuariopermiteanularpreoc`,
  ADD COLUMN IF NOT EXISTS `usuariopermitecrearitem` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede crear item local urgente'
    AFTER `usuariopermiteeditarprecios`,
  ADD COLUMN IF NOT EXISTS `usuariopermiteeditaritem` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede editar precio cero, uso funcional y activo/inactivo'
    AFTER `usuariopermitecrearitem`,
  ADD COLUMN IF NOT EXISTS `usuariopermitesynctrnerp` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario puede ejecutar sincronizacion de transacciones ERP'
    AFTER `usuariopermiteeditaritem`,
  ADD COLUMN IF NOT EXISTS `usuarioreqautorizadorfuerapptocompra` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Usuario autorizador fuera de presupuesto para REQ'
    AFTER `usuariopermitesynctrnerp`,
  ADD COLUMN IF NOT EXISTS `usuarioreqautorizadorfuerapptocompraorden` int(11) NOT NULL DEFAULT 0
    COMMENT 'Orden relativo de autorizadores fuera de presupuesto'
    AFTER `usuarioreqautorizadorfuerapptocompra`;

CREATE INDEX IF NOT EXISTS `idx_usuarios_aprobreq`
  ON `usuarios` (`usuariopermiteaprobreq`, `usuarioactivo`);

CREATE INDEX IF NOT EXISTS `idx_usuarios_aprobpreoc`
  ON `usuarios` (`usuariopermiteaprobpreoc`, `usuarioactivo`);

CREATE INDEX IF NOT EXISTS `idx_usuarios_comprador`
  ON `usuarios` (`usuariocomprador`, `usuarioactivo`);

CREATE INDEX IF NOT EXISTS `idx_usuarios_reqautfuera`
  ON `usuarios` (`usuarioreqautorizadorfuerapptocompra`, `usuarioreqautorizadorfuerapptocompraorden`, `usuarioactivo`);

ALTER TABLE `invitems`
  ADD COLUMN IF NOT EXISTS `iteminglocal` tinyint(1) NOT NULL DEFAULT 0
    COMMENT 'Item ingresado localmente para resolver urgencia'
    AFTER `invitemcompra`;

CREATE INDEX IF NOT EXISTS `idx_invitems_iteminglocal`
  ON `invitems` (`iteminglocal`);

ALTER TABLE `pptocompra`
  ADD COLUMN IF NOT EXISTS `pptocompraresponsableid` int(11) NULL DEFAULT NULL
    COMMENT 'Responsable default PreOC desde presupuesto'
    AFTER `pptocompraactivo`,
  ADD COLUMN IF NOT EXISTS `pptocompraadministradorid` int(11) NULL DEFAULT NULL
    COMMENT 'Administrador default PreOC desde presupuesto'
    AFTER `pptocompraresponsableid`,
  ADD COLUMN IF NOT EXISTS `pptocompracolaboradorid` int(11) NULL DEFAULT NULL
    COMMENT 'Colaborador default opcional de PreOC'
    AFTER `pptocompraadministradorid`;

CREATE INDEX IF NOT EXISTS `idx_pptocompra_responsable`
  ON `pptocompra` (`pptocompraresponsableid`);

CREATE INDEX IF NOT EXISTS `idx_pptocompra_administrador`
  ON `pptocompra` (`pptocompraadministradorid`);

CREATE INDEX IF NOT EXISTS `idx_pptocompra_colaborador`
  ON `pptocompra` (`pptocompracolaboradorid`);

ALTER TABLE `pptocompra`
  ADD CONSTRAINT `fk_pptocompra_responsable`
    FOREIGN KEY (`pptocompraresponsableid`) REFERENCES `usuarios` (`usuarioid`),
  ADD CONSTRAINT `fk_pptocompra_administrador`
    FOREIGN KEY (`pptocompraadministradorid`) REFERENCES `usuarios` (`usuarioid`),
  ADD CONSTRAINT `fk_pptocompra_colaborador`
    FOREIGN KEY (`pptocompracolaboradorid`) REFERENCES `usuarios` (`usuarioid`);

CREATE TABLE IF NOT EXISTS `usuarioscentroscosto` (
  `usucenid` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `centrocostoid` int(11) NOT NULL,
  `usucendefault` tinyint(1) NOT NULL DEFAULT 0,
  `usucenactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`usucenid`),
  UNIQUE KEY `uq_usuarioscentroscosto_usuario_centro` (`usuarioid`, `centrocostoid`),
  KEY `idx_usuarioscentroscosto_usuario` (`usuarioid`, `usucenactivo`),
  KEY `idx_usuarioscentroscosto_centro` (`centrocostoid`, `usucenactivo`),
  CONSTRAINT `fk_usuarioscentroscosto_usuarioid`
    FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `fk_usuarioscentroscosto_centrocostoid`
    FOREIGN KEY (`centrocostoid`) REFERENCES `centroscosto` (`centrocostoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuarioscentroscostolog` (
  `usucenid` int(11) NOT NULL COMMENT 'PK de usuarioscentroscosto',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/QRY',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_usuarioscentroscostolog_usucenid` (`usucenid`),
  CONSTRAINT `fk_usuarioscentroscostolog_usucenid`
    FOREIGN KEY (`usucenid`) REFERENCES `usuarioscentroscosto` (`usucenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `funcionarios` (
  `funcionariorut` varchar(12) NOT NULL,
  `funcionarionombre` varchar(150) NOT NULL,
  `funcionarioemail` varchar(120) NULL DEFAULT NULL,
  `funcionariocelular` varchar(20) NULL DEFAULT NULL,
  `funcencos` int(11) NULL DEFAULT NULL,
  `funcionarioactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`funcionariorut`),
  KEY `idx_funcionarios_encos` (`funcencos`, `funcionarioactivo`),
  KEY `idx_funcionarios_nombre` (`funcionarionombre`),
  CONSTRAINT `fk_funcionarios_centro`
    FOREIGN KEY (`funcencos`) REFERENCES `centroscosto` (`centrocostoid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `funcionarioslog` (
  `funcionariorut` varchar(12) NOT NULL COMMENT 'PK de funcionarios',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/QRY',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_funcionarioslog_rut` (`funcionariorut`),
  CONSTRAINT `fk_funcionarioslog_funcionario`
    FOREIGN KEY (`funcionariorut`) REFERENCES `funcionarios` (`funcionariorut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aprobadoresperiodoinactividad` (
  `aprobadorperiodoid` int(11) NOT NULL AUTO_INCREMENT,
  `aprobadorusuarioid` int(11) NOT NULL,
  `aprobadorreemplazousuarioid` int(11) NOT NULL,
  `aprobadorperiodotipocod` varchar(30) NOT NULL,
  `aprobadorperiodomotivo` varchar(250) NULL DEFAULT NULL,
  `aprobadorperiodofechainicio` date NOT NULL,
  `aprobadorperiodofechafin` date NOT NULL,
  `aprobadorperiodoactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`aprobadorperiodoid`),
  KEY `idx_aprobadoresperiodo_usuario` (`aprobadorusuarioid`, `aprobadorperiodoactivo`, `aprobadorperiodofechainicio`, `aprobadorperiodofechafin`),
  KEY `idx_aprobadoresperiodo_reemplazo` (`aprobadorreemplazousuarioid`, `aprobadorperiodoactivo`),
  CONSTRAINT `fk_aprobadoresperiodo_usuario`
    FOREIGN KEY (`aprobadorusuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `fk_aprobadoresperiodo_reemplazo`
    FOREIGN KEY (`aprobadorreemplazousuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `chk_aprobadoresperiodo_fechas`
    CHECK (`aprobadorperiodofechainicio` <= `aprobadorperiodofechafin`),
  CONSTRAINT `chk_aprobadoresperiodo_reemplazante_distinto`
    CHECK (`aprobadorusuarioid` <> `aprobadorreemplazousuarioid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `aprobadoresperiodoinactividadlog` (
  `aprobadorperiodoid` int(11) NOT NULL COMMENT 'PK de aprobadoresperiodoinactividad',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/QRY',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_aprobadoresperiodoinactividadlog_periodoid` (`aprobadorperiodoid`),
  CONSTRAINT `fk_aprobadoresperiodoinactividadlog_periodoid`
    FOREIGN KEY (`aprobadorperiodoid`) REFERENCES `aprobadoresperiodoinactividad` (`aprobadorperiodoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erpcondicionespago` (
  `erpcondicionpagoid` int(11) NOT NULL AUTO_INCREMENT,
  `erpcondicionpagocod` varchar(50) NOT NULL,
  `erpcondicionpagonombre` varchar(150) NOT NULL,
  `erpcondicionpagodescripcion` varchar(255) NULL DEFAULT NULL,
  `erpcondicionpagotipo` int(11) NOT NULL DEFAULT 0,
  `erpcondicionpagoedicionfija` tinyint(1) NOT NULL DEFAULT 0,
  `erpcondicionpagoexigedocsdiferidos` tinyint(1) NOT NULL DEFAULT 0,
  `erpcondicionpagoporcentajeinteres` decimal(9,4) NOT NULL DEFAULT 0.0000,
  `erpcondicionpagoctaproveedores` varchar(50) NULL DEFAULT NULL,
  `erpcondicionpagoctadeudoresventas` varchar(50) NULL DEFAULT NULL,
  `erpcondicionpagoctadisponibilidad` varchar(50) NULL DEFAULT NULL,
  `erpcondicionpagoetaetd` int(11) NULL DEFAULT NULL,
  `erpcondicionpagoactivo` tinyint(1) NOT NULL DEFAULT 1,
  `sincfechahora` datetime NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`erpcondicionpagoid`),
  UNIQUE KEY `uq_erpcondicionespago_cod` (`erpcondicionpagocod`),
  KEY `idx_erpcondicionespago_activo` (`erpcondicionpagoactivo`),
  KEY `idx_erpcondicionespago_nombre` (`erpcondicionpagonombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erpcondicionespagodetalle` (
  `erpcondicionpagodetid` int(11) NOT NULL AUTO_INCREMENT,
  `erpcondicionpagoid` int(11) NOT NULL,
  `erpcondicionpagodetlinea` int(11) NOT NULL,
  `erpcondicionpagodetfecha` date NULL DEFAULT NULL,
  `erpcondicionpagodettipo` int(11) NULL DEFAULT NULL,
  `erpcondicionpagodetdias` int(11) NOT NULL DEFAULT 0,
  `erpcondicionpagodetporcentaje` decimal(9,4) NOT NULL DEFAULT 0.0000,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`erpcondicionpagodetid`),
  UNIQUE KEY `uq_erpcondicionespagodet_linea` (`erpcondicionpagoid`, `erpcondicionpagodetlinea`),
  KEY `idx_erpcondicionespagodet_condicion` (`erpcondicionpagoid`),
  CONSTRAINT `fk_erpcondicionespagodet_condicion`
    FOREIGN KEY (`erpcondicionpagoid`) REFERENCES `erpcondicionespago` (`erpcondicionpagoid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erpcondicionespagolog` (
  `erpcondicionpagoid` int(11) NOT NULL COMMENT 'PK de erpcondicionespago',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/QRY',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_erpcondicionespagolog_condicionid` (`erpcondicionpagoid`),
  CONSTRAINT `fk_erpcondicionespagolog_condicionid`
    FOREIGN KEY (`erpcondicionpagoid`) REFERENCES `erpcondicionespago` (`erpcondicionpagoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erpproveedores` (
  `erpproveedorid` int(11) NOT NULL AUTO_INCREMENT,
  `erpproveedorcod` varchar(50) NOT NULL,
  `erpproveedornombre` varchar(150) NOT NULL,
  `erpproveedordescripcion` varchar(255) NULL DEFAULT NULL,
  `erpproveedorrazonsocial` varchar(150) NULL DEFAULT NULL,
  `erpproveedoremail` varchar(150) NULL DEFAULT NULL,
  `erpcategoriafiscalcod` varchar(50) NULL DEFAULT NULL,
  `erpidenttributariacod` varchar(50) NULL DEFAULT NULL,
  `erpidenttributarianro` varchar(50) NULL DEFAULT NULL,
  `erpproveedorescliente` tinyint(1) NOT NULL DEFAULT 0,
  `erpproveedorescontratista` tinyint(1) NOT NULL DEFAULT 0,
  `erpproveedorrestriccioncondpagos` tinyint(1) NOT NULL DEFAULT 0,
  `erpconceptoproveedorcod` varchar(50) NULL DEFAULT NULL,
  `erpcuentaproveedorcod` varchar(50) NULL DEFAULT NULL,
  `erpmonedapagocod` varchar(50) NULL DEFAULT NULL,
  `erpproveedormediopago` varchar(50) NULL DEFAULT NULL,
  `erpproveedoractivo` tinyint(1) NOT NULL DEFAULT 1,
  `sincfechahora` datetime NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`erpproveedorid`),
  UNIQUE KEY `uq_erpproveedores_cod` (`erpproveedorcod`),
  KEY `idx_erpproveedores_activo` (`erpproveedoractivo`),
  KEY `idx_erpproveedores_nombre` (`erpproveedornombre`),
  KEY `idx_erpproveedores_categoriafiscal` (`erpcategoriafiscalcod`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erpproveedoreslog` (
  `erpproveedorid` int(11) NOT NULL COMMENT 'PK de erpproveedores',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/QRY',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_erpproveedoreslog_proveedorid` (`erpproveedorid`),
  CONSTRAINT `fk_erpproveedoreslog_proveedorid`
    FOREIGN KEY (`erpproveedorid`) REFERENCES `erpproveedores` (`erpproveedorid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erpproveedorescondicionespago` (
  `erpprovcondpagoid` int(11) NOT NULL AUTO_INCREMENT,
  `erpproveedorid` int(11) NOT NULL,
  `erpcondicionpagoid` int(11) NOT NULL,
  `erpprovcondpagodefault` tinyint(1) NOT NULL DEFAULT 0,
  `erpprovcondpagoactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`erpprovcondpagoid`),
  UNIQUE KEY `uq_erpproveedorescondicionespago_proveedor_condicion` (`erpproveedorid`, `erpcondicionpagoid`),
  KEY `idx_erpproveedorescondicionespago_proveedor` (`erpproveedorid`, `erpprovcondpagoactivo`),
  KEY `idx_erpproveedorescondicionespago_condicion` (`erpcondicionpagoid`, `erpprovcondpagoactivo`),
  CONSTRAINT `fk_erpproveedorescondicionespago_proveedor`
    FOREIGN KEY (`erpproveedorid`) REFERENCES `erpproveedores` (`erpproveedorid`) ON DELETE CASCADE,
  CONSTRAINT `fk_erpproveedorescondicionespago_condicion`
    FOREIGN KEY (`erpcondicionpagoid`) REFERENCES `erpcondicionespago` (`erpcondicionpagoid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
