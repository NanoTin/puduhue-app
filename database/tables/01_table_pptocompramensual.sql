CREATE TABLE IF NOT EXISTS `pptocompramensual` (
  `pptocompramensualid` int(11) NOT NULL AUTO_INCREMENT,
  `pptocompraid` int(11) NOT NULL,
  `ppoanio` int(11) NOT NULL,
  `ppomes` int(11) NOT NULL COMMENT '1..12',
  `ppomontoppto` decimal(18,2) NOT NULL DEFAULT 0,
  `ppoobservacion` varchar(500) NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`pptocompramensualid`),
  UNIQUE KEY `uq_pptocompramensual_periodo` (`pptocompraid`, `ppoanio`, `ppomes`),
  KEY `idx_pptocompramensual_ppto` (`pptocompraid`),
  KEY `idx_pptocompramensual_anio_mes` (`ppoanio`, `ppomes`),
  CONSTRAINT `fk_pptocompramensual_pptocompra`
    FOREIGN KEY (`pptocompraid`) REFERENCES `pptocompra` (`pptocompraid`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
