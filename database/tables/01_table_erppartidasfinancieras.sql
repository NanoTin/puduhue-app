CREATE TABLE IF NOT EXISTS `erppartidasfinancieras` (
  `erppartidafinancieraid` int(11) NOT NULL AUTO_INCREMENT,
  `erppartidafinancieracod` varchar(50) NOT NULL COMMENT 'Codigo ERP de partida financiera / DIMPARFIN',
  `erppartidafinancieradsc` varchar(100) NOT NULL COMMENT 'Nombre visible desde ERP',
  `erppartidafinancieradescripcion` varchar(255) NULL DEFAULT NULL COMMENT 'Descripcion adicional desde ERP',
  `erppartidafinancieraactivo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Activo segun ERP',
  `sincfechahora` datetime NULL DEFAULT NULL COMMENT 'Ultima sincronizacion ERP',
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`erppartidafinancieraid`),
  UNIQUE KEY `uq_erppartidasfinancieras_cod` (`erppartidafinancieracod`),
  KEY `idx_erppartidasfinancieras_activo` (`erppartidafinancieraactivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
