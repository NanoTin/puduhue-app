CREATE TABLE `invbodegas` (
  `invbodegaid` int(11) NOT NULL AUTO_INCREMENT,
  `invbodegadsc` varchar(50) NOT NULL,
  `erpinvbodegacod` varchar(50) NOT NULL COMMENT 'C�digo que corresponde al ERP, para despues usar en las integraciones',
  `fundoid` int(11) NOT NULL,
  `invbodactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`invbodegaid`),
  KEY `idx_invbodegas_fundoid` (`fundoid`),
  CONSTRAINT `fk_invbodegas_fundoid` FOREIGN KEY (`fundoid`) REFERENCES `fundos` (`fundoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
