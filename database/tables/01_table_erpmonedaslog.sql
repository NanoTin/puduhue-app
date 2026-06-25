CREATE TABLE IF NOT EXISTS `erpmonedaslog` (
  `erpmonedaid` int(11) NOT NULL COMMENT 'PK de la tabla erpmonedas',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL',
  `logparamjson` json NOT NULL COMMENT 'Datos de sincronizacion aplicados',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_erpmonedaslog_erpmonedaid` (`erpmonedaid`),
  CONSTRAINT `fk_erpmonedaslog_erpmonedaid`
    FOREIGN KEY (`erpmonedaid`) REFERENCES `erpmonedas` (`erpmonedaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs genericos para erpmonedas';
