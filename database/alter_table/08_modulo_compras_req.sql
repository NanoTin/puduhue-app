/*
Incremental 08 - DDL REQ (Requerimientos de Compra).

Incluye:
- tablas maestras relacionadas a REQ,
- cabecera y detalle REQ,
- firmantes,
- comentarios funcionales,
- snapshot de analisis presupuestario.

No incluye:
- pendientes de compra (`reqaprobados*`), que pertenecen al incremental 09,
- tablas de PreOC,
- lógicas funcionales en SP,
- reglas condicionales de unicidad/estado por flujo (se tratan en SP/BE).
*/

CREATE TABLE IF NOT EXISTS `reqcomprasestados` (
  `reqcomprasestadocod` varchar(20) NOT NULL,
  `reqcomprasestadosigla` varchar(20) NOT NULL,
  `reqcomprasestadodsc` varchar(100) NOT NULL,
  `reqcomprasestadoactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`reqcomprasestadocod`),
  KEY `idx_reqcomprasestados_activo` (`reqcomprasestadoactivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reqcomprasestados`
  (`reqcomprasestadocod`,`reqcomprasestadodsc`,`reqcomprasestadosigla`,`reqcomprasestadoactivo`,`auditcreacionusuarioid`,`auditcreaciondispositivo`,`auditcreacionip`,`auditcreacionfechahora`)
VALUES
  ('BRR', 'Borrador', 'BRR', 1, 1, 'system', '127.0.0.1', NOW()),
  ('PND', 'Pendiente de aprobacion', 'PND', 1, 1, 'system', '127.0.0.1', NOW()),
  ('EDT', 'En edicion', 'EDT', 1, 1, 'system', '127.0.0.1', NOW()),
  ('APR', 'Aprobado', 'APR', 1, 1, 'system', '127.0.0.1', NOW()),
  ('RCH', 'Rechazado', 'RCH', 1, 1, 'system', '127.0.0.1', NOW()),
  ('ANL', 'Anulado', 'ANL', 1, 1, 'system', '127.0.0.1', NOW())
ON DUPLICATE KEY UPDATE
  `reqcomprasestadoactivo` = VALUES(`reqcomprasestadoactivo`),
  `reqcomprasestadodsc` = VALUES(`reqcomprasestadodsc`),
  `reqcomprasestadosigla` = VALUES(`reqcomprasestadosigla`),
  `auditedicionusuarioid` = VALUES(`auditcreacionusuarioid`),
  `auditediciondispositivo` = VALUES(`auditcreaciondispositivo`),
  `auditedicionip` = VALUES(`auditcreacionip`),
  `auditedicionfechahora` = NOW();

CREATE TABLE IF NOT EXISTS `reqcompraestadopreoc` (
  `reqcompraestadopreoccod` varchar(20) NOT NULL,
  `reqcompraestadopreocdsc` varchar(100) NOT NULL,
  `reqcompraestadopreocactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`reqcompraestadopreoccod`),
  KEY `idx_reqcompraestadopreoc_activo` (`reqcompraestadopreocactivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reqcompraestadopreoc`
  (`reqcompraestadopreoccod`,`reqcompraestadopreocdsc`,`reqcompraestadopreocactivo`,`auditcreacionusuarioid`,`auditcreaciondispositivo`,`auditcreacionip`,`auditcreacionfechahora`)
VALUES
  ('VNC_Parcial', 'Vinculado Parcial', 1, 1, 'system', '127.0.0.1', NOW()),
  ('VNC_Total', 'Vinculado Total', 1, 1, 'system', '127.0.0.1', NOW())
ON DUPLICATE KEY UPDATE
  `reqcompraestadopreocactivo` = VALUES(`reqcompraestadopreocactivo`),
  `reqcompraestadopreocdsc` = VALUES(`reqcompraestadopreocdsc`),
  `auditedicionusuarioid` = VALUES(`auditcreacionusuarioid`),
  `auditediciondispositivo` = VALUES(`auditcreaciondispositivo`),
  `auditedicionip` = VALUES(`auditcreacionip`),
  `auditedicionfechahora` = NOW();

CREATE TABLE IF NOT EXISTS `reqcompras` (
  `reqcompraid` int(11) NOT NULL AUTO_INCREMENT,
  `reqcompracod` varchar(20) NOT NULL,
  `reqcompratipo` tinyint(1) NOT NULL COMMENT '1=Material, 2=Servicio',
  `reqcomprafecha` date NOT NULL DEFAULT (CURRENT_DATE),
  `centrocostoid` int(11) NOT NULL,
  `funcionariorut` varchar(12) NULL DEFAULT NULL,
  `reqcompraobs` text NULL DEFAULT NULL,
  `reqcompraprioridad` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Normal, 2=Alta',
  `reqcompraestadoid` varchar(20) NOT NULL,
  `reqcompraestadopreocid` varchar(20) NULL DEFAULT NULL,
  `reqaprobadoridpnd` int(11) NULL DEFAULT NULL,
  `reqaprobacionfecha` date NULL DEFAULT NULL,
  `reqadvertenciapptocompra` tinyint(1) NOT NULL DEFAULT 0,
  `reqfuerapptocompra` tinyint(1) NOT NULL DEFAULT 0,
  `reqcompranettotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reqcompravig` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`reqcompraid`),
  UNIQUE KEY `uq_reqcompras_cod` (`reqcompracod`),
  KEY `idx_reqcompras_estado` (`reqcompraestadoid`),
  KEY `idx_reqcompras_estadopreoc` (`reqcompraestadopreocid`),
  KEY `idx_reqcompras_aprobadorpnd` (`reqaprobadoridpnd`),
  KEY `idx_reqcompras_centrocosto` (`centrocostoid`, `reqcompravig`),
  KEY `idx_reqcompras_funcionario` (`funcionariorut`),
  KEY `idx_reqcompras_fecha` (`reqcomprafecha`),
  KEY `idx_reqcompras_prioridad` (`reqcompraprioridad`, `reqcompravig`),
  CONSTRAINT `fk_reqcompras_centrocosto`
    FOREIGN KEY (`centrocostoid`) REFERENCES `centroscosto` (`centrocostoid`),
  CONSTRAINT `fk_reqcompras_funcionario`
    FOREIGN KEY (`funcionariorut`) REFERENCES `funcionarios` (`funcionariorut`) ON DELETE SET NULL,
  CONSTRAINT `fk_reqcompras_estado`
    FOREIGN KEY (`reqcompraestadoid`) REFERENCES `reqcomprasestados` (`reqcomprasestadocod`),
  CONSTRAINT `fk_reqcompras_estadopreoc`
    FOREIGN KEY (`reqcompraestadopreocid`) REFERENCES `reqcompraestadopreoc` (`reqcompraestadopreoccod`),
  CONSTRAINT `fk_reqcompras_aprobadorpnd`
    FOREIGN KEY (`reqaprobadoridpnd`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `chk_reqcompras_tipo`
    CHECK (`reqcompratipo` IN (1,2)),
  CONSTRAINT `chk_reqcompras_prioridad`
    CHECK (`reqcompraprioridad` IN (1,2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reqcomprasdetalle` (
  `reqcompradetid` int(11) NOT NULL AUTO_INCREMENT,
  `reqcompraid` int(11) NOT NULL,
  `reqcompradetlinea` int(11) NOT NULL,
  `invitemid` int(11) NOT NULL,
  `subfamiliaid` int(11) NOT NULL,
  `reqcompradetitemcod` varchar(50) NOT NULL,
  `reqcompradetdsc` varchar(200) NOT NULL,
  `invunidmedid` int(11) NOT NULL,
  `reqcompradetcantidad` decimal(15,4) NOT NULL,
  `reqitemcantanulada` decimal(15,4) NOT NULL DEFAULT 0.00,
  `reqcompradetprecioneto` decimal(15,2) NOT NULL,
  `reqcompradettotalneto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reqcompradetobs` text NULL DEFAULT NULL,
  `reqcompradetitemmodificado` tinyint(1) NOT NULL DEFAULT 0,
  `reqcompradetadvertenciappto` tinyint(1) NOT NULL DEFAULT 0,
  `reqcompradetultreqfecha` date NULL DEFAULT NULL,
  `reqcompradetultreqcantidad` decimal(15,4) NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`reqcompradetid`),
  UNIQUE KEY `uq_reqcomprasdetalle_req_linea` (`reqcompraid`, `reqcompradetlinea`),
  UNIQUE KEY `uq_reqcomprasdetalle_req_item` (`reqcompraid`, `invitemid`),
  KEY `idx_reqcomprasdetalle_req` (`reqcompraid`),
  KEY `idx_reqcomprasdetalle_invitem` (`invitemid`),
  KEY `idx_reqcomprasdetalle_subfamilia` (`subfamiliaid`),
  KEY `idx_reqcomprasdetalle_unidad` (`invunidmedid`),
  CONSTRAINT `fk_reqcomprasdetalle_req`
    FOREIGN KEY (`reqcompraid`) REFERENCES `reqcompras` (`reqcompraid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqcomprasdetalle_invitem`
    FOREIGN KEY (`invitemid`) REFERENCES `invitems` (`invitemid`),
  CONSTRAINT `fk_reqcomprasdetalle_subfamilia`
    FOREIGN KEY (`subfamiliaid`) REFERENCES `subfamilias` (`subfamiliaid`),
  CONSTRAINT `fk_reqcomprasdetalle_unidad`
    FOREIGN KEY (`invunidmedid`) REFERENCES `invunidadesmedidas` (`invunidmedid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reqcomprasfirmantes` (
  `reqcomprafirmanteid` int(11) NOT NULL AUTO_INCREMENT,
  `reqcompraid` int(11) NOT NULL,
  `firmanteusuarioid` int(11) NOT NULL,
  `firmanteorden` int(11) NOT NULL,
  `firmantetipo` varchar(20) NOT NULL,
  `firmantedefault` tinyint(1) NOT NULL DEFAULT 0,
  `firmantefuerapptocompra` tinyint(1) NOT NULL DEFAULT 0,
  `firmantemotivoinclusion` varchar(50) NULL DEFAULT NULL,
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
  PRIMARY KEY (`reqcomprafirmanteid`),
  UNIQUE KEY `uq_reqcomprasfirmantes_req_usuario` (`reqcompraid`, `firmanteusuarioid`),
  KEY `idx_reqcomprasfirmantes_req` (`reqcompraid`, `firmanteorden`),
  KEY `idx_reqcomprasfirmantes_usuario` (`firmanteusuarioid`),
  CONSTRAINT `fk_reqcomprasfirmantes_req`
    FOREIGN KEY (`reqcompraid`) REFERENCES `reqcompras` (`reqcompraid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqcomprasfirmantes_usuario`
    FOREIGN KEY (`firmanteusuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `fk_reqcomprasfirmantes_reemplazo`
    FOREIGN KEY (`firmantereemplazodeid`) REFERENCES `reqcomprasfirmantes` (`reqcomprafirmanteid`) ON DELETE SET NULL,
  CONSTRAINT `chk_reqcomprasfirmantes_orden`
    CHECK (`firmanteorden` >= 1),
  CONSTRAINT `chk_reqcomprasfirmantes_estado`
    CHECK (`firmanteestado` IN ('PND','APR','RCH','INA','NVG'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reqcomprascomentarios` (
  `reqcomentarioid` int(11) NOT NULL AUTO_INCREMENT,
  `reqcompraid` int(11) NOT NULL,
  `usuarioid` int(11) NOT NULL,
  `reqcomentariotipo` varchar(20) NOT NULL,
  `reqcomentariotxt` text NOT NULL,
  `reqcomentariofechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`reqcomentarioid`),
  KEY `idx_reqcomprascomentarios_req` (`reqcompraid`),
  KEY `idx_reqcomprascomentarios_usuario` (`usuarioid`),
  KEY `idx_reqcomprascomentarios_tipo` (`reqcomentariotipo`),
  CONSTRAINT `fk_reqcomprascomentarios_req`
    FOREIGN KEY (`reqcompraid`) REFERENCES `reqcompras` (`reqcompraid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqcomprascomentarios_usuario`
    FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reqcompraslog` (
  `reqcompraid` int(11) NOT NULL COMMENT 'PK de reqcompras',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/APR/RCH/EDT/CMB',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_reqcompraslog_req` (`reqcompraid`, `logfechahora`),
  CONSTRAINT `fk_reqcompraslog_req`
    FOREIGN KEY (`reqcompraid`) REFERENCES `reqcompras` (`reqcompraid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log tecnico de REQ';

CREATE TABLE IF NOT EXISTS `reqcompraspptosnapshot` (
  `reqpptosnapshotid` int(11) NOT NULL AUTO_INCREMENT,
  `reqcompraid` int(11) NOT NULL,
  `subfamiliaid` int(11) NOT NULL,
  `centrocostoid` int(11) NOT NULL,
  `pptocompraid` int(11) NULL DEFAULT NULL,
  `reqpptomonto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reqpptosaldodisponible` decimal(15,2) NULL DEFAULT NULL,
  `reqpptomontootroscurso` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reqpptomontoaprobadospend` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reqpptosaldoproyectado` decimal(15,2) NULL DEFAULT NULL,
  `reqpptoporcentajeuso` decimal(9,4) NULL DEFAULT NULL,
  `reqpptodeficit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reqpptoadvertencia` tinyint(1) NOT NULL DEFAULT 0,
  `reqpptofuerapptocompra` tinyint(1) NOT NULL DEFAULT 0,
  `reqpptofechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`reqpptosnapshotid`),
  KEY `idx_reqcompraspptosnapshot_req` (`reqcompraid`),
  KEY `idx_reqcompraspptosnapshot_subfamilia` (`subfamiliaid`),
  KEY `idx_reqcompraspptosnapshot_centrocosto` (`centrocostoid`),
  KEY `idx_reqcompraspptosnapshot_pptocompra` (`pptocompraid`),
  CONSTRAINT `fk_reqcompraspptosnapshot_req`
    FOREIGN KEY (`reqcompraid`) REFERENCES `reqcompras` (`reqcompraid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqcompraspptosnapshot_subfamilia`
    FOREIGN KEY (`subfamiliaid`) REFERENCES `subfamilias` (`subfamiliaid`),
  CONSTRAINT `fk_reqcompraspptosnapshot_centrocosto`
    FOREIGN KEY (`centrocostoid`) REFERENCES `centroscosto` (`centrocostoid`),
  CONSTRAINT `fk_reqcompraspptosnapshot_pptocompra`
    FOREIGN KEY (`pptocompraid`) REFERENCES `pptocompra` (`pptocompraid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
