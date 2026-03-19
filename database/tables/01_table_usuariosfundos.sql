CREATE TABLE `usuariosfundos` (
  `usuarioid` int(11) NOT NULL,
  `fundoid` int(11) NOT NULL,
  `ufdefault` tinyint(1) NOT NULL DEFAULT '0',
  `auditcreacionusuarioid` int(11) NOT NULL,
  `auditcreaciondispositivo` varchar(100) NOT NULL,
  `auditcreacionip` varchar(50) NOT NULL,
  `auditcreacionfechahora` datetime NOT NULL,
  PRIMARY KEY (`usuarioid`, `fundoid`),
  KEY `idx_usuariosfundos_usuarioid` (`usuarioid`),
  CONSTRAINT `fk_usuariosfundos_usuarioid` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`),
  KEY `idx_usuariosfundos_fundoid` (`fundoid`),
  CONSTRAINT `fk_usuariosfundos_fundoid` FOREIGN KEY (`fundoid`) REFERENCES `fundos` (`fundoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
