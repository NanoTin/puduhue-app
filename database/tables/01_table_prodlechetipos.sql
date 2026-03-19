CREATE TABLE `prodlechetipos` (
  `prodlechetipoid` int(11) NOT NULL AUTO_INCREMENT,
  `prodlechetipodsc` varchar(50) NOT NULL,
  `invitemid` int(11) NOT NULL COMMENT 'where invitemleche = 1',
  `prodlecheventa` tinyint(1) NOT NULL,
  `prodlecheorden` int(2) NOT NULL DEFAULT 0,
  `prodlecheactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`prodlechetipoid`),
  KEY `idx_prodlechetipos_invitemid` (`invitemid`),
  CONSTRAINT `fk_prodlechetipos_invitemid` FOREIGN KEY (`invitemid`) REFERENCES `invitems` (`invitemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
