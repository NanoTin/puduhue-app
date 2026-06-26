/*
Incremental 10 - DDL PreOC.

Incluye:
- maestros de estado documental y estado ERP de PreOC
- cabecera PreOC, detalle originado desde REQ aprobados, agrupaciones e impuestos
- dimensiones, resumen de presupuesto, firmantes, comentarios y log de PreOC
- relación de aprobadores por monto
- FK desde reqaprobadoshistorial hacia PreOC/preocdetallereqitems (cuando existan)
*/

CREATE TABLE IF NOT EXISTS `preocestados` (
  `preocestadoid` int(11) NOT NULL AUTO_INCREMENT,
  `preocestadocod` varchar(20) NOT NULL,
  `preocestadosigla` varchar(20) NOT NULL,
  `preocestadodsc` varchar(100) NOT NULL,
  `preocestadoactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocestadoid`),
  UNIQUE KEY `uq_preocestados_cod` (`preocestadocod`),
  KEY `idx_preocestados_activo` (`preocestadoactivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `preocestados`
  (`preocestadocod`,`preocestadosigla`,`preocestadodsc`,`preocestadoactivo`,`auditcreacionusuarioid`,`auditcreaciondispositivo`,`auditcreacionip`,`auditcreacionfechahora`)
VALUES
  ('BRR', 'Borrador', 'Borrador', 1, 1, 'system', '127.0.0.1', NOW()),
  ('PND', 'Pendiente', 'Pendiente', 1, 1, 'system', '127.0.0.1', NOW()),
  ('APR', 'Aprobada', 'Aprobada', 1, 1, 'system', '127.0.0.1', NOW()),
  ('RCH', 'Rechazada', 'Rechazada', 1, 1, 'system', '127.0.0.1', NOW()),
  ('ANL', 'Anulada', 'Anulada', 1, 1, 'system', '127.0.0.1', NOW())
ON DUPLICATE KEY UPDATE
  `preocestadoactivo` = VALUES(`preocestadoactivo`),
  `preocestadosigla` = VALUES(`preocestadosigla`),
  `preocestadodsc` = VALUES(`preocestadodsc`),
  `auditedicionusuarioid` = VALUES(`auditcreacionusuarioid`),
  `auditediciondispositivo` = VALUES(`auditcreaciondispositivo`),
  `auditedicionip` = VALUES(`auditcreacionip`),
  `auditedicionfechahora` = NOW();

CREATE TABLE IF NOT EXISTS `preocestadoserp` (
  `preocestadoerpid` int(11) NOT NULL AUTO_INCREMENT,
  `preocestadoercod` varchar(20) NOT NULL,
  `preocestadodsc` varchar(100) NOT NULL,
  `preocestadoeractivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocestadoerpid`),
  UNIQUE KEY `uq_preocestadoserp_cod` (`preocestadoercod`),
  KEY `idx_preocestadoserp_activo` (`preocestadoeractivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `preocestadoserp`
  (`preocestadoercod`,`preocestadodsc`,`preocestadoeractivo`,`auditcreacionusuarioid`,`auditcreaciondispositivo`,`auditcreacionip`,`auditcreacionfechahora`)
VALUES
  ('SNC', 'Sincronizada', 1, 1, 'system', '127.0.0.1', NOW()),
  ('ERR', 'Error', 1, 1, 'system', '127.0.0.1', NOW())
ON DUPLICATE KEY UPDATE
  `preocestadoeractivo` = VALUES(`preocestadoeractivo`),
  `preocestadodsc` = VALUES(`preocestadodsc`),
  `auditedicionusuarioid` = VALUES(`auditcreacionusuarioid`),
  `auditediciondispositivo` = VALUES(`auditcreaciondispositivo`),
  `auditedicionip` = VALUES(`auditcreacionip`),
  `auditedicionfechahora` = NOW();

CREATE TABLE IF NOT EXISTS `preoc` (
  `preocid` int(11) NOT NULL AUTO_INCREMENT,
  `preocdoc` varchar(20) NOT NULL,
  `preoctipo` tinyint(1) NOT NULL COMMENT '1=OC (Material), 2=OCSS (Servicio)',
  `preocfecha` date NOT NULL DEFAULT (CURRENT_DATE),
  `preocfechaoc` date NOT NULL,
  `compradorusuarioid` int(11) NOT NULL,
  `erpproveedorid` int(11) NOT NULL,
  `erpcondicionpagoid` int(11) NULL DEFAULT NULL,
  `preocworkflowcod` varchar(50) NOT NULL,
  `erpmonedacod` varchar(10) NOT NULL,
  `erpprovinciaid` int(11) NULL DEFAULT NULL,
  `preocobsinterna` text NULL DEFAULT NULL,
  `preocobsoc` text NULL DEFAULT NULL,
  `preocprioridad` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Normal, 2=Alta',
  `preocestadoid` int(11) NOT NULL,
  `preocestadoerpid` int(11) NULL DEFAULT NULL,
  `preocaprobadoridpnd` int(11) NULL DEFAULT NULL,
  `preocaprobacionfecha` date NULL DEFAULT NULL,
  `preocnettotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preocimptostotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preoctotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `erptransaccionid` varchar(50) NULL DEFAULT NULL,
  `erpnumerodoc` varchar(50) NULL DEFAULT NULL,
  `erpsincfechahora` datetime NULL DEFAULT NULL,
  `erperror` text NULL DEFAULT NULL,
  `erprespuestajson` json NULL DEFAULT NULL,
  `preocvig` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocid`),
  UNIQUE KEY `uq_preoc_doc` (`preocdoc`),
  KEY `idx_preoc_fecha` (`preocfecha`),
  KEY `idx_preoc_fechaoc` (`preocfechaoc`),
  KEY `idx_preoc_comprador` (`compradorusuarioid`, `preocvig`),
  KEY `idx_preoc_estado` (`preocestadoid`),
  KEY `idx_preoc_estadoerp` (`preocestadoerpid`),
  KEY `idx_preoc_proveedor` (`erpproveedorid`, `preocvig`),
  KEY `idx_preoc_aprobadorpnd` (`preocaprobadoridpnd`),
  KEY `idx_preoc_monedacod` (`erpmonedacod`),
  KEY `idx_preoc_prioridad` (`preocprioridad`, `preocvig`),
  CONSTRAINT `fk_preoc_comprador`
    FOREIGN KEY (`compradorusuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `fk_preoc_proveedor`
    FOREIGN KEY (`erpproveedorid`) REFERENCES `erpproveedores` (`erpproveedorid`),
  CONSTRAINT `fk_preoc_condicionpago`
    FOREIGN KEY (`erpcondicionpagoid`) REFERENCES `erpcondicionespago` (`erpcondicionpagoid`) ON DELETE SET NULL,
  CONSTRAINT `fk_preoc_estado`
    FOREIGN KEY (`preocestadoid`) REFERENCES `preocestados` (`preocestadoid`),
  CONSTRAINT `fk_preoc_estadoerp`
    FOREIGN KEY (`preocestadoerpid`) REFERENCES `preocestadoserp` (`preocestadoerpid`),
  CONSTRAINT `fk_preoc_aprobadorpnd`
    FOREIGN KEY (`preocaprobadoridpnd`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `chk_preoc_tipo`
    CHECK (`preoctipo` IN (1,2)),
  CONSTRAINT `chk_preoc_prioridad`
    CHECK (`preocprioridad` IN (1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preocdetallereqitems` (
  `preocdetreqitemid` int(11) NOT NULL AUTO_INCREMENT,
  `preocid` int(11) NOT NULL,
  `reqaprobadoid` int(11) NOT NULL,
  `preocdetlinea` int(11) NOT NULL,
  `invitemid` int(11) NOT NULL,
  `preocdetitemcod` varchar(50) NOT NULL,
  `preocdetdsc` varchar(200) NOT NULL,
  `centrocostoid` int(11) NOT NULL,
  `pptocompraid` int(11) NOT NULL,
  `subfamiliaid` int(11) NOT NULL,
  `erpprovinciaid` int(11) NULL DEFAULT NULL,
  `preocdetdsccc` varchar(200) NOT NULL,
  `invunidmedid` int(11) NOT NULL,
  `preocdetcantidad` decimal(15,4) NOT NULL,
  `preocdetprecioneto` decimal(15,2) NOT NULL,
  `preocdetsubtotalneto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preocdetobs` text NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocdetreqitemid`),
  UNIQUE KEY `uq_preocdetallereqitems_preoc_linea` (`preocid`, `preocdetlinea`),
  KEY `idx_preocdetallereqitems_reqaprobado` (`reqaprobadoid`),
  KEY `idx_preocdetallereqitems_preoc` (`preocid`),
  KEY `idx_preocdetallereqitems_invitem` (`invitemid`),
  KEY `idx_preocdetallereqitems_ccosto` (`centrocostoid`),
  KEY `idx_preocdetallereqitems_pptocompra` (`pptocompraid`),
  KEY `idx_preocdetallereqitems_subfamilia` (`subfamiliaid`),
  CONSTRAINT `fk_preocdetallereqitems_preoc`
    FOREIGN KEY (`preocid`) REFERENCES `preoc` (`preocid`) ON DELETE CASCADE,
  CONSTRAINT `fk_preocdetallereqitems_reqaprobado`
    FOREIGN KEY (`reqaprobadoid`) REFERENCES `reqaprobados` (`reqaprobadoid`),
  CONSTRAINT `fk_preocdetallereqitems_invitem`
    FOREIGN KEY (`invitemid`) REFERENCES `invitems` (`invitemid`),
  CONSTRAINT `fk_preocdetallereqitems_centro`
    FOREIGN KEY (`centrocostoid`) REFERENCES `centroscosto` (`centrocostoid`),
  CONSTRAINT `fk_preocdetallereqitems_pptocompra`
    FOREIGN KEY (`pptocompraid`) REFERENCES `pptocompra` (`pptocompraid`),
  CONSTRAINT `fk_preocdetallereqitems_subfamilia`
    FOREIGN KEY (`subfamiliaid`) REFERENCES `subfamilias` (`subfamiliaid`),
  CONSTRAINT `fk_preocdetallereqitems_unidad`
    FOREIGN KEY (`invunidmedid`) REFERENCES `invunidadesmedidas` (`invunidmedid`),
  CONSTRAINT `chk_preocdetallereqitems_linea`
    CHECK (`preocdetlinea` >= 1),
  CONSTRAINT `chk_preocdetallereqitems_cantidad`
    CHECK (`preocdetcantidad` >= 0),
  CONSTRAINT `chk_preocdetallereqitems_precionetto`
    CHECK (`preocdetprecioneto` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preocitems` (
  `preocitemid` int(11) NOT NULL AUTO_INCREMENT,
  `preocid` int(11) NOT NULL,
  `invitemid` int(11) NOT NULL,
  `invunidmedid` int(11) NOT NULL,
  `preocitemcantidadtotal` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `preocitemprecioneto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preocitemnetototal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preocitemimptostotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preocitemtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocitemid`),
  UNIQUE KEY `uq_preocitems_preoc_invitem` (`preocid`, `invitemid`),
  KEY `idx_preocitems_preoc` (`preocid`),
  KEY `idx_preocitems_invitem` (`invitemid`),
  KEY `idx_preocitems_unidad` (`invunidmedid`),
  CONSTRAINT `fk_preocitems_preoc`
    FOREIGN KEY (`preocid`) REFERENCES `preoc` (`preocid`) ON DELETE CASCADE,
  CONSTRAINT `fk_preocitems_invitem`
    FOREIGN KEY (`invitemid`) REFERENCES `invitems` (`invitemid`),
  CONSTRAINT `fk_preocitems_unidad`
    FOREIGN KEY (`invunidmedid`) REFERENCES `invunidadesmedidas` (`invunidmedid`),
  CONSTRAINT `chk_preocitems_precio`
    CHECK (`preocitemprecioneto` >= 0),
  CONSTRAINT `chk_preocitems_totales`
    CHECK (`preocitemnetototal` = (`preocitemcantidadtotal` * `preocitemprecioneto`)),
  CONSTRAINT `chk_preocitems_grandtotal`
    CHECK (`preocitemtotal` = (`preocitemnetototal` + `preocitemimptostotal`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preocimptos` (
  `preocimptoid` int(11) NOT NULL AUTO_INCREMENT,
  `preocitemid` int(11) NOT NULL,
  `imptoid` int(11) NULL DEFAULT NULL,
  `preocimptoneto` decimal(15,2) NOT NULL,
  `preocimptocantidadtotal` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `preocimptonetototal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preocimptotasa` decimal(9,4) NOT NULL DEFAULT 0.0000,
  `preocimptomonto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocimptoid`),
  KEY `idx_preocimptos_preocitem` (`preocitemid`),
  KEY `idx_preocimptos_impto` (`imptoid`),
  CONSTRAINT `fk_preocimptos_preocitem`
    FOREIGN KEY (`preocitemid`) REFERENCES `preocitems` (`preocitemid`) ON DELETE CASCADE,
  CONSTRAINT `chk_preocimptos_tasa`
    CHECK (`preocimptotasa` >= 0),
  CONSTRAINT `chk_preocimptos_neto`
    CHECK (`preocimptoneto` >= 0),
  CONSTRAINT `chk_preocimptos_cantidad`
    CHECK (`preocimptocantidadtotal` >= 0),
  CONSTRAINT `chk_preocimptos_monto`
    CHECK (`preocimptomonto` >= 0),
  CONSTRAINT `chk_preocimptos_netototal`
    CHECK (`preocimptonetototal` = (`preocimptoneto` * `preocimptocantidadtotal`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preocitemsdimensiones` (
  `preocitemdimensionid` int(11) NOT NULL AUTO_INCREMENT,
  `preocitemid` int(11) NULL DEFAULT NULL,
  `preocdetreqitemid` int(11) NOT NULL,
  `dimensioncodigo` varchar(50) NOT NULL,
  `distribucioncodigo` varchar(50) NULL DEFAULT NULL,
  `tipocalculo` varchar(10) NOT NULL,
  `dimensionitemcodigo` varchar(50) NOT NULL,
  `dimensionporcentaje` decimal(9,4) NOT NULL DEFAULT 0.0000,
  `dimensionimporte` decimal(15,2) NULL DEFAULT NULL,
  `dimensionfuente` varchar(30) NOT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocitemdimensionid`),
  KEY `idx_preocitemsdimensiones_preocitem` (`preocitemid`),
  KEY `idx_preocitemsdimensiones_preocdet` (`preocdetreqitemid`),
  KEY `idx_preocitemsdimensiones_dimension` (`dimensioncodigo`, `dimensionitemcodigo`),
  CONSTRAINT `fk_preocitemsdimensiones_preocitem`
    FOREIGN KEY (`preocitemid`) REFERENCES `preocitems` (`preocitemid`) ON DELETE SET NULL,
  CONSTRAINT `fk_preocitemsdimensiones_preocdet`
    FOREIGN KEY (`preocdetreqitemid`) REFERENCES `preocdetallereqitems` (`preocdetreqitemid`) ON DELETE CASCADE,
  CONSTRAINT `chk_preocitemsdimensiones_porcentaje`
    CHECK (`dimensionporcentaje` >= 0),
  CONSTRAINT `chk_preocitemsdimensiones_fuente`
    CHECK (`dimensionfuente` <> '')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preocpptoresumen` (
  `preocpptoresumenid` int(11) NOT NULL AUTO_INCREMENT,
  `preocid` int(11) NOT NULL,
  `pptocompraid` int(11) NOT NULL,
  `preocpptomonto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `preocpptosaldoantes` decimal(15,2) NULL DEFAULT NULL,
  `preocpptosaldodespues` decimal(15,2) NULL DEFAULT NULL,
  `preocpptoestado` varchar(20) NOT NULL,
  `preocpptofechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocpptoresumenid`),
  UNIQUE KEY `uq_preocpptoresumen_preoc_pptocompra` (`preocid`, `pptocompraid`, `preocpptoestado`),
  KEY `idx_preocpptoresumen_preoc` (`preocid`),
  KEY `idx_preocpptoresumen_pptocompra` (`pptocompraid`),
  CONSTRAINT `fk_preocpptoresumen_preoc`
    FOREIGN KEY (`preocid`) REFERENCES `preoc` (`preocid`) ON DELETE CASCADE,
  CONSTRAINT `fk_preocpptoresumen_pptocompra`
    FOREIGN KEY (`pptocompraid`) REFERENCES `pptocompra` (`pptocompraid`),
  CONSTRAINT `chk_preocpptoresumen_montos`
    CHECK (`preocpptomonto` >= 0),
  CONSTRAINT `chk_preocpptoresumen_estado`
    CHECK (`preocpptoestado` <> '')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preocfirmantes` (
  `preocfirmanteid` int(11) NOT NULL AUTO_INCREMENT,
  `preocid` int(11) NOT NULL,
  `firmanteusuarioid` int(11) NOT NULL,
  `firmantetipo` varchar(20) NOT NULL,
  `firmanteorden` int(11) NOT NULL,
  `firmantedefault` tinyint(1) NOT NULL DEFAULT 0,
  `firmanteestado` varchar(5) NOT NULL,
  `firmantefechahora` datetime NULL DEFAULT NULL,
  `firmantecomentario` text NULL DEFAULT NULL,
  `firmantereemplazodeid` int(11) NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocfirmanteid`),
  UNIQUE KEY `uq_preocfirmantes_preoc_usuario` (`preocid`, `firmanteusuarioid`),
  KEY `idx_preocfirmantes_preoc` (`preocid`, `firmanteorden`),
  KEY `idx_preocfirmantes_usuario` (`firmanteusuarioid`),
  KEY `idx_preocfirmantes_reemplazo` (`firmantereemplazodeid`),
  CONSTRAINT `fk_preocfirmantes_preoc`
    FOREIGN KEY (`preocid`) REFERENCES `preoc` (`preocid`) ON DELETE CASCADE,
  CONSTRAINT `fk_preocfirmantes_usuario`
    FOREIGN KEY (`firmanteusuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `fk_preocfirmantes_reemplazo`
    FOREIGN KEY (`firmantereemplazodeid`) REFERENCES `preocfirmantes` (`preocfirmanteid`) ON DELETE SET NULL,
  CONSTRAINT `chk_preocfirmantes_orden`
    CHECK (`firmanteorden` >= 1),
  CONSTRAINT `chk_preocfirmantes_estado`
    CHECK (`firmanteestado` IN ('PND','APR','RCH','INA','NVG'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preoccomentarios` (
  `preoccomentarioid` int(11) NOT NULL AUTO_INCREMENT,
  `preocid` int(11) NOT NULL,
  `usuarioid` int(11) NOT NULL,
  `preoccomentariotipo` varchar(20) NOT NULL,
  `preoccomentariotxt` text NOT NULL,
  `preoccomentariofechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preoccomentarioid`),
  KEY `idx_preoccomentarios_preoc` (`preocid`),
  KEY `idx_preoccomentarios_usuario` (`usuarioid`),
  KEY `idx_preoccomentarios_tipo` (`preoccomentariotipo`),
  CONSTRAINT `fk_preoccomentarios_preoc`
    FOREIGN KEY (`preocid`) REFERENCES `preoc` (`preocid`) ON DELETE CASCADE,
  CONSTRAINT `fk_preoccomentarios_usuario`
    FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preoclog` (
  `preocid` int(11) NOT NULL COMMENT 'PK de preoc',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/APR/RCH/ERP/ERR/REV',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_preoclog_preoc` (`preocid`, `logfechahora`),
  CONSTRAINT `fk_preoclog_preoc`
    FOREIGN KEY (`preocid`) REFERENCES `preoc` (`preocid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `preocaprobadoresxmonto` (
  `preocaprobmontoid` int(11) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `montominimo` decimal(15,2) NOT NULL,
  `firmanteorden` int(11) NOT NULL DEFAULT 1,
  `preocaprobmontoactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`preocaprobmontoid`),
  KEY `idx_preocaprobadoresxmonto_usuario` (`usuarioid`, `preocaprobmontoactivo`, `montominimo`),
  KEY `idx_preocaprobadoresxmonto_orden` (`firmanteorden`, `preocaprobmontoactivo`),
  CONSTRAINT `fk_preocaprobadoresxmonto_usuario`
    FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `chk_preocaprobadoresxmonto_monto`
    CHECK (`montominimo` >= 0),
  CONSTRAINT `chk_preocaprobadoresxmonto_orden`
    CHECK (`firmanteorden` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `reqaprobadoshistorial`
  ADD CONSTRAINT IF NOT EXISTS `fk_reqaprobadoshistorial_preoc`
    FOREIGN KEY (`preocid`) REFERENCES `preoc` (`preocid`) ON DELETE SET NULL,
  ADD CONSTRAINT IF NOT EXISTS `fk_reqaprobadoshistorial_preocdetreqitem`
    FOREIGN KEY (`preocdetid`) REFERENCES `preocdetallereqitems` (`preocdetreqitemid`) ON DELETE SET NULL;
