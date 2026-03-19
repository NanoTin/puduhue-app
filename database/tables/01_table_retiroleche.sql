CREATE TABLE `retiroleche` (
  `empresaid` int(11) NOT NULL,
  `fundoid` int(11) NOT NULL,
  `retirolechefecha` datetime NOT NULL,
  `retirolechetotlitros` int(6) NOT NULL COMMENT 'Total de litros retirados en el dia',
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`empresaid`, `fundoid`, `retirolechefecha`),
  KEY `idx_retiroleche_empresaid` (`empresaid`),
  CONSTRAINT `fk_retiroleche_empresaid` FOREIGN KEY (`empresaid`) REFERENCES `empresas` (`empresaid`),
  KEY `idx_retiroleche_fundoid` (`fundoid`),
  CONSTRAINT `fk_retiroleche_fundoid` FOREIGN KEY (`fundoid`) REFERENCES `fundos` (`fundoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Retiro de Leche Planta - Encabezado';
