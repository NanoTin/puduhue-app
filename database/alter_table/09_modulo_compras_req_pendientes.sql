/*
Incremental 09 - DDL Pendientes de compra (REQ aprobados).

Alcance:
- reqaprobados: lineas aprobadas listas para compra.
- reqaprobadoshistorial: compras/anulaciones sobre cantidad pendiente.
- reqaprobadoscambios: cambios de item ejecutados por comprador.

Notas:
- Las FK hacia PreOC se incorporan en el incremental 10, cuando exista la tabla
  de cabecera/detalle de PreOC.
*/

CREATE TABLE IF NOT EXISTS `reqaprobados` (
  `reqaprobadoid` int(11) NOT NULL AUTO_INCREMENT,
  `reqcompradetid` int(11) NOT NULL,
  `reqcompraid` int(11) NOT NULL,
  `invitemid` int(11) NOT NULL,
  `reqaprobadoitemcod` varchar(50) NOT NULL,
  `reqaprobadoitemdsc` varchar(200) NOT NULL,
  `invunidmedid` int(11) NOT NULL,
  `reqaprobadocantidadreq` decimal(15,4) NOT NULL,
  `reqaprobadocantidadpendiente` decimal(15,4) NOT NULL DEFAULT 0.00,
  `reqaprobadocantidadcomprada` decimal(15,4) NOT NULL DEFAULT 0.00,
  `reqaprobadocantidadanulada` decimal(15,4) NOT NULL DEFAULT 0.00,
  `reqaprobadoprecioneto` decimal(15,2) NOT NULL,
  `reqaprobadoestado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Pendiente,2=Parcial,3=Completa,4=Anulada',
  `reqaprobadofecha` date NOT NULL DEFAULT (CURRENT_DATE),
  `auditcreacionusuarioid` int(11) NOT NULL,
  `auditcreaciondispositivo` varchar(100) NOT NULL,
  `auditcreacionip` varchar(50) NOT NULL,
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL,
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL,
  `auditedicionip` varchar(50) NULL DEFAULT NULL,
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reqaprobadoid`),
  UNIQUE KEY `uq_reqaprobados_detalle` (`reqcompradetid`),
  KEY `idx_reqaprobados_req` (`reqcompraid`),
  KEY `idx_reqaprobados_invitem` (`invitemid`),
  KEY `idx_reqaprobados_unidad` (`invunidmedid`),
  KEY `idx_reqaprobados_estado` (`reqaprobadoestado`),
  CONSTRAINT `fk_reqaprobados_req`
    FOREIGN KEY (`reqcompraid`) REFERENCES `reqcompras` (`reqcompraid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqaprobados_detalle`
    FOREIGN KEY (`reqcompradetid`) REFERENCES `reqcomprasdetalle` (`reqcompradetid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqaprobados_invitem`
    FOREIGN KEY (`invitemid`) REFERENCES `invitems` (`invitemid`),
  CONSTRAINT `fk_reqaprobados_unidad`
    FOREIGN KEY (`invunidmedid`) REFERENCES `invunidadesmedidas` (`invunidmedid`),
  CONSTRAINT `chk_reqaprobados_estado`
    CHECK (`reqaprobadoestado` BETWEEN 1 AND 4),
  CONSTRAINT `chk_reqaprobados_cantidades`
    CHECK (
      `reqaprobadocantidadreq` = (`reqaprobadocantidadpendiente` + `reqaprobadocantidadcomprada` + `reqaprobadocantidadanulada`)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reqaprobadoshistorial` (
  `reqaprobadohistid` int(11) NOT NULL AUTO_INCREMENT,
  `reqaprobadoid` int(11) NOT NULL,
  `preocid` int(11) NULL DEFAULT NULL,
  `preocdetid` int(11) NULL DEFAULT NULL,
  `histtipo` varchar(20) NOT NULL,
  `histcantidadpendienteantes` decimal(15,4) NOT NULL,
  `histcantidad` decimal(15,4) NOT NULL,
  `histprecioneto` decimal(15,2) NULL DEFAULT NULL,
  `histitemcod` varchar(50) NULL DEFAULT NULL,
  `histitemdsc` varchar(200) NULL DEFAULT NULL,
  `histusuarioid` int(11) NOT NULL,
  `histfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `histobs` text NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL,
  `auditcreaciondispositivo` varchar(100) NOT NULL,
  `auditcreacionip` varchar(50) NOT NULL,
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL,
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL,
  `auditedicionip` varchar(50) NULL DEFAULT NULL,
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reqaprobadohistid`),
  KEY `idx_reqaprobadoshistorial_reqaprobado` (`reqaprobadoid`),
  KEY `idx_reqaprobadoshistorial_usuario` (`histusuarioid`),
  KEY `idx_reqaprobadoshistorial_tipo` (`histtipo`),
  CONSTRAINT `fk_reqaprobadoshistorial_reqaprobado`
    FOREIGN KEY (`reqaprobadoid`) REFERENCES `reqaprobados` (`reqaprobadoid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqaprobadoshistorial_usuario`
    FOREIGN KEY (`histusuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `chk_reqaprobadoshistorial_tipo`
    CHECK (`histtipo` IN ('COMPRA','ANULACION','AJUSTE'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reqaprobadoscambios` (
  `reqcambioid` int(11) NOT NULL AUTO_INCREMENT,
  `reqaprobadoid` int(11) NOT NULL,
  `invitemidoriginal` int(11) NOT NULL,
  `invitemidnuevo` int(11) NOT NULL,
  `reqcambioobs` text NOT NULL,
  `reqcambiofechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reqcambiousuarioid` int(11) NOT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL,
  `auditcreaciondispositivo` varchar(100) NOT NULL,
  `auditcreacionip` varchar(50) NOT NULL,
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL,
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL,
  `auditedicionip` varchar(50) NULL DEFAULT NULL,
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reqcambioid`),
  UNIQUE KEY `uq_reqaprobadoscambios_requerimiento_item` (`reqaprobadoid`, `invitemidoriginal`, `invitemidnuevo`),
  KEY `idx_reqaprobadoscambios_reqaprobado` (`reqaprobadoid`),
  KEY `idx_reqaprobadoscambios_item_original` (`invitemidoriginal`),
  KEY `idx_reqaprobadoscambios_item_nuevo` (`invitemidnuevo`),
  KEY `idx_reqaprobadoscambios_usuario` (`reqcambiousuarioid`),
  CONSTRAINT `fk_reqaprobadoscambios_reqaprobado`
    FOREIGN KEY (`reqaprobadoid`) REFERENCES `reqaprobados` (`reqaprobadoid`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqaprobadoscambios_item_original`
    FOREIGN KEY (`invitemidoriginal`) REFERENCES `invitems` (`invitemid`),
  CONSTRAINT `fk_reqaprobadoscambios_item_nuevo`
    FOREIGN KEY (`invitemidnuevo`) REFERENCES `invitems` (`invitemid`),
  CONSTRAINT `fk_reqaprobadoscambios_usuario`
    FOREIGN KEY (`reqcambiousuarioid`) REFERENCES `usuarios` (`usuarioid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
