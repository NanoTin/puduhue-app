CREATE TABLE `usuariosempresas` (
  `usuarioid` int(11) NOT NULL,
  `empresaid` int(11) NOT NULL,
  `uedefault` tinyint(1) NOT NULL DEFAULT '0',
  `auditcreacionusuarioid` int(11) NOT NULL,
  `auditcreaciondispositivo` varchar(100) NOT NULL,
  `auditcreacionip` varchar(50) NOT NULL,
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuarioid`, `empresaid`),
  KEY `idx_usuariosempresas_usuarioid` (`usuarioid`),
  CONSTRAINT `fk_usuariosempresas_usuarioid` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`),
  KEY `idx_usuariosempresas_empresaid` (`empresaid`),
  CONSTRAINT `fk_usuariosempresas_empresaid` FOREIGN KEY (`empresaid`) REFERENCES `empresas` (`empresaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Asocia usuarios empresas';
