CREATE TABLE IF NOT EXISTS `erppartidasfinancieraslog` (
  `erppartidafinancieraid` int(11) NOT NULL COMMENT 'PK de la tabla erppartidasfinancieras',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL',
  `logparamjson` json NOT NULL COMMENT 'Datos de sincronizacion aplicados',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_erppartidasfinancieraslog_id` (`erppartidafinancieraid`),
  CONSTRAINT `fk_erppartidasfinancieraslog_id`
    FOREIGN KEY (`erppartidafinancieraid`) REFERENCES `erppartidasfinancieras` (`erppartidafinancieraid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs genericos para erppartidasfinancieras';
