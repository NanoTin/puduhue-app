CREATE TABLE IF NOT EXISTS `erplistadoendpointslog` (
  `erpendpointlogid` bigint(20) NOT NULL AUTO_INCREMENT,
  `erpendpointid` int(11) NOT NULL,
  `erpendpointlogtipoexec` varchar(10) NOT NULL COMMENT 'MANUAL | AUTO | TECNICO',
  `erpendpointlogfechaini` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `erpendpointlogfechafin` datetime NULL DEFAULT NULL,
  `erpendpointlogestado` varchar(20) NOT NULL COMMENT 'OK | ERROR | PARCIAL',
  `erpendpointlogmensaje` text NULL DEFAULT NULL,
  `erpendpointlogregistrosleidos` int(11) NOT NULL DEFAULT 0,
  `erpendpointlogregistrosinsertados` int(11) NOT NULL DEFAULT 0,
  `erpendpointlogregistrosactualizados` int(11) NOT NULL DEFAULT 0,
  `erpendpointlogregistrosinactivos` int(11) NOT NULL DEFAULT 0,
  `erpendpointlogrequestjson` json NULL DEFAULT NULL COMMENT 'Metadata de ejecucion sin credenciales',
  `erpendpointlogresponsejson` json NULL DEFAULT NULL COMMENT 'Respuesta resumida o diagnostico tecnico',
  `usuarioid` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`erpendpointlogid`),
  KEY `idx_erplistadoendpointslog_endpoint` (`erpendpointid`, `erpendpointlogfechaini`),
  KEY `idx_erplistadoendpointslog_estado` (`erpendpointlogestado`),
  KEY `idx_erplistadoendpointslog_usuario` (`usuarioid`),
  CONSTRAINT `fk_erplistadoendpointslog_endpoint`
    FOREIGN KEY (`erpendpointid`) REFERENCES `erplistadoendpoints` (`erpendpointid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
