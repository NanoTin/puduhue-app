CREATE TABLE `usuariosfundoshist` (
  `historialid` int(11) NOT NULL AUTO_INCREMENT,
  `histusuarioid` int(11) NOT NULL COMMENT 'Copia de la columna "usuariosfundos.usuarioid"',
  `histfundoid` int(11) NOT NULL COMMENT 'Copia de la columna "usuariosfundos.empresaid"',
  `histauditcreacionusuarioid` int(11) NOT NULL COMMENT 'Copia de la columna "usuariosfundos.auditcreacionusuarioid"',
  `histauditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Copia de la columna "usuariosfundos.auditcreaciondispositivo"',
  `histauditcreacionip` varchar(50) NOT NULL COMMENT 'Copia de la columna "usuariosfundos.auditcreacionip"',
  `histauditcreacionfechahora` datetime NOT NULL COMMENT 'Copia de la columna "usuariosfundos.auditcreacionfechahora"',
  `auditedicionusuarioid` int(11) NULL COMMENT 'Usuario que elimino la asociaci�n "usuario-fundo"',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `auditedicionip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`historialid`),
  KEY `idx_usuariosfundoshist_histusuarioid` (`histusuarioid`),
  CONSTRAINT `fk_usuariosfundoshist_histusuarioid` FOREIGN KEY (`histusuarioid`) REFERENCES `usuarios` (`usuarioid`),
  KEY `idx_usuariosfundoshist_histfundoid` (`histfundoid`),
  CONSTRAINT `fk_usuariosfundoshist_histfundoid` FOREIGN KEY (`histfundoid`) REFERENCES `fundos` (`fundoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Cuando elimina una asociaci�n, se copian los datos antes de eliminar de la tabla "usuariosfundos"';
