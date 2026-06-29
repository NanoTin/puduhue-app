CREATE TABLE IF NOT EXISTS `subfamilias` (
  `subfamiliaid` int(11) NOT NULL AUTO_INCREMENT,
  `familiaid` int(11) NULL DEFAULT NULL COMMENT 'FK a familias resuelta desde detalle ERP',
  `subfamiliacod` varchar(50) NOT NULL COMMENT 'Codigo ERP de subfamilia',
  `subfamiliadsc` varchar(100) NOT NULL COMMENT 'Nombre visible desde ERP',
  `subfamiliadescripcion` varchar(255) NULL DEFAULT NULL COMMENT 'Descripcion adicional desde ERP',
  `subfamiliaactivo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Activo segun ERP',
  `sincfechahora` datetime NULL DEFAULT NULL COMMENT 'Ultima sincronizacion ERP',
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`subfamiliaid`),
  UNIQUE KEY `uq_subfamilias_cod` (`subfamiliacod`),
  KEY `idx_subfamilias_familia` (`familiaid`),
  KEY `idx_subfamilias_activo` (`subfamiliaactivo`),
  CONSTRAINT `fk_subfamilias_familia`
    FOREIGN KEY (`familiaid`) REFERENCES `familias` (`familiaid`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
