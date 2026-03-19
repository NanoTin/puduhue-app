CREATE TABLE `empresaslog` (
  `empresaid` int(11) NOT NULL COMMENT 'PK de la tabla empresas',
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `logusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `logdispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `logip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `logfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logtipo` varchar(3) NOT NULL COMMENT 'INS/UPD/ANL/QRY son las opciones que soporta',
  `logparamjson` json NOT NULL COMMENT 'Cuando se ejecuta el sp, se almacena lo que contenga el parametro de entrada p_in_json',
  `logregbkpjson` json NOT NULL COMMENT 'Si el sp es de tipo UPD, se debe guardar el registro afectado antes de su modificacion',
  PRIMARY KEY (`logid`),
  KEY `idx_empresaslog_empresaid` (`empresaid`),
  CONSTRAINT `fk_empresaslog_empresaid` FOREIGN KEY (`empresaid`) REFERENCES `empresas` (`empresaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs genericos para empresas';
