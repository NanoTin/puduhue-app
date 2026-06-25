CREATE TABLE IF NOT EXISTS `pptocompratransacciones` (
  `pptocompratransaccionid` int(11) NOT NULL AUTO_INCREMENT,
  `pptocompraid` int(11) NOT NULL,
  `ppoanio` int(11) NOT NULL,
  `ppomes` int(11) NOT NULL COMMENT '1..12',
  `ppoanomes` char(7) AS (CONCAT(`ppoanio`, '-', LPAD(`ppomes`, 2, '0')))
    VIRTUAL,
  `pptocompratransacciontipoid` varchar(30) NOT NULL,
  `pptocompratransaccionfecha` date NULL DEFAULT NULL COMMENT 'Fecha funcional de la transaccion; no reemplaza auditcreacionfechahora',
  `pptocompramonto` decimal(18,2) NOT NULL,
  `pptocompramontoencurso` decimal(18,2) NOT NULL DEFAULT 0,
  `pptocompramontoconfirmado` decimal(18,2) NOT NULL DEFAULT 0,
  `pptocompramotivo` varchar(500) NOT NULL,
  `pptocompranrodocumentoorigen` int(11) NOT NULL DEFAULT 0 COMMENT 'Numero documento origen; 0 para eventos internos PPTO',
  `pptocompramoduloorigen` varchar(30) NOT NULL DEFAULT 'PPTO_COMPRA' COMMENT 'Modulo que origina la transaccion',
  `pptocompraestado` varchar(20) NOT NULL DEFAULT 'CONFIRMADO' COMMENT 'CONFIRMADO, PENDIENTE, RECHAZADA, REVERSA',
  `pptocompregenciaorigen` varchar(150) NULL DEFAULT NULL,
  `pptocomprareflinea` varchar(150) NULL DEFAULT NULL,
  `pptocompregruppomovimiento` varchar(50) NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  PRIMARY KEY (`pptocompratransaccionid`),
  KEY `idx_pptocompratransacciones_ppto` (`pptocompraid`),
  KEY `idx_pptocompratransacciones_tipo` (`pptocompratransacciontipoid`),
  KEY `idx_pptocompratransacciones_origen` (`pptocompramoduloorigen`, `pptocompranrodocumentoorigen`),
  KEY `idx_pptocompratransacciones_estado` (`pptocompraestado`),
  KEY `idx_pptocompratransacciones_transaccionfecha` (`pptocompratransaccionfecha`, `auditcreacionfechahora`),
  KEY `idx_pptocompratransacciones_anio_mes` (`ppoanio`, `ppomes`),
  KEY `idx_pptocompratransacciones_fecha` (`auditcreacionfechahora`),
  CONSTRAINT `fk_pptocompratransacciones_pptocompra`
    FOREIGN KEY (`pptocompraid`) REFERENCES `pptocompra` (`pptocompraid`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_pptocompratransacciones_tipo`
    FOREIGN KEY (`pptocompratransacciontipoid`) REFERENCES `pptocompratransaccionestipo` (`pptocompratransacciontipoid`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
