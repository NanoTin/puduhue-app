CREATE TABLE `estanquesmarcas` (
  `estanquemarcaid` int(11) NOT NULL AUTO_INCREMENT,
  `estanquemarcadsc` varchar(50) NOT NULL,
  `estanquemodelodsc` varchar(50) NOT NULL,
  `estanquecapacidadlts` int(4) NOT NULL,
  `estanquemarcamodelo` varchar(100) NOT NULL COMMENT 'Columna calculada que concatena las columnas "estanquemarcadsc"+"estanquemodelodsc"',
  `estanquereglaminmm` int(4) NOT NULL,
  `estanquereglamaxmm` int(4) NOT NULL,
  `estanqueactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`estanquemarcaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Marcas y Modelos de Estanques de Leche';
