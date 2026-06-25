CREATE TABLE IF NOT EXISTS `pptocompra` (
  `pptocompraid` int(11) NOT NULL AUTO_INCREMENT,
  `temporadaid` int(11) NOT NULL,
  `subfamiliaid` int(11) NOT NULL,
  `centrocostoid` int(11) NOT NULL,
  `pptocompraactivo` tinyint(1) NOT NULL DEFAULT 1,
  `pptocompraobservacion` varchar(500) NULL DEFAULT NULL,
  `pptocomprapresupuestado` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma inicial de ppomontoppto de la carga base (PPTO_CARGA)',
  `pptocompraajustespositivos` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma de montos PPTO_AJUSTE_POS',
  `pptocompraajustenegativos` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma de montos PPTO_AJUSTE_NEG (valor normalmente negativo)',
  `pptocompreproyectado` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Presupuesto reproyectado',
  `pptocompramontoconsumidopnd` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma consumos pendientes (POC_RESERVA)',
  `pptocompramontoconsumidocnf` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Monto consumido confirmado (POC_CONFIRMACION + POC_REVERSA, positivo)',
  `pptocomprasaldodisponible` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Saldo disponible calculado',
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NULL DEFAULT NULL COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`pptocompraid`),
  UNIQUE KEY `uq_pptocompra_key` (`temporadaid`, `subfamiliaid`, `centrocostoid`),
  KEY `idx_pptocompra_activo` (`pptocompraactivo`),
  KEY `idx_pptocompra_subfamilia` (`subfamiliaid`),
  KEY `idx_pptocompra_centrocosto` (`centrocostoid`),
  KEY `idx_pptocompra_temporada` (`temporadaid`),
  CONSTRAINT `fk_pptocompra_temporada`
    FOREIGN KEY (`temporadaid`) REFERENCES `temporadas` (`temporadaid`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_pptocompra_subfamilia`
    FOREIGN KEY (`subfamiliaid`) REFERENCES `subfamilias` (`subfamiliaid`)
    ON DELETE RESTRICT,
  CONSTRAINT `fk_pptocompra_centrocosto`
    FOREIGN KEY (`centrocostoid`) REFERENCES `centroscosto` (`centrocostoid`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
