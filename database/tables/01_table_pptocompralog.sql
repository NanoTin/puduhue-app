CREATE TABLE IF NOT EXISTS `pptocompralog` (
  `pptocompraid` int(11) NOT NULL COMMENT 'PK de pptocompra',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/EDT/ANL',
  `logparamjson` json NOT NULL COMMENT 'Parametros de entrada',
  `logregbkpjson` json NULL DEFAULT NULL COMMENT 'Registro previo si aplica',
  PRIMARY KEY (`logid`),
  KEY `idx_pptocompralog_pptocompraid` (`pptocompraid`),
  CONSTRAINT `fk_pptocompralog_pptocompraid`
    FOREIGN KEY (`pptocompraid`) REFERENCES `pptocompra` (`pptocompraid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de auditoria de presupuesto de compras';
