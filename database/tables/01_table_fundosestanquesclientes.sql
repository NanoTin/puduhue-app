DROP TABLE IF EXISTS `fundosestanquesclientes`;

CREATE TABLE `fundosestanquesclientes` (
  `fundoestanqueid` int(11) NOT NULL,
  `clienteid` int(11) NOT NULL,
  `estanqueclientecod` int(11) NOT NULL COMMENT 'Es el codigo interno del Cliente que usa para identificar al Estanque',
  `fndestcliactivo` tinyint(1) NOT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`fundoestanqueid`, `clienteid`),
  UNIQUE KEY `uq_fundosestanquesclientes_estanqueclientecod` (`estanqueclientecod`),
  KEY `idx_fundosestanquesclientes_fundoestanqueid` (`fundoestanqueid`),
  CONSTRAINT `fk_fundosestanquesclientes_fundoestanqueid` FOREIGN KEY (`fundoestanqueid`) REFERENCES `fundosestanques` (`fundoestanqueid`),
  KEY `idx_fundosestanquesclientes_clienteid` (`clienteid`),
  CONSTRAINT `fk_fundosestanquesclientes_clienteid` FOREIGN KEY (`clienteid`) REFERENCES `clientes` (`clienteid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Asocia los Estanques de Leche del Fundo con Clientes para Retiro de Leche';
