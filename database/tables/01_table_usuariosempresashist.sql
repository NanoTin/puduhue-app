CREATE TABLE `usuariosempresashist` (
  `historialid` int(11) NOT NULL AUTO_INCREMENT,
  `histusuarioid` int(11) NOT NULL COMMENT 'Copia de la columna "usuariosempresas.usuarioid"',
  `histempresaid` int(11) NOT NULL COMMENT 'Copia de la columna "usuariosempresas.empresaid"',
  `histauditcreacionusuarioid` int(11) NOT NULL COMMENT 'Copia de la columna "usuariosempresas.auditcreacionusuarioid"',
  `histauditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Copia de la columna "usuariosempresas.auditcreaciondispositivo"',
  `histauditcreacionip` varchar(50) NOT NULL COMMENT 'Copia de la columna "usuariosempresas.auditcreacionip"',
  `histauditcreacionfechahora` datetime NOT NULL COMMENT 'Copia de la columna "usuariosempresas.auditcreacionfechahora"',
  `auditedicionusuarioid` int(11) NULL COMMENT 'Usuario que elimino la asociaci�n "usuario-empresa"',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'p_in_dispositivo',
  `auditedicionip` varchar(50) NOT NULL COMMENT 'p_in_ip',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`historialid`),
  KEY `idx_usuariosempresashist_histusuarioid` (`histusuarioid`),
  CONSTRAINT `fk_usuariosempresashist_histusuarioid` FOREIGN KEY (`histusuarioid`) REFERENCES `usuarios` (`usuarioid`),
  KEY `idx_usuariosempresashist_histempresaid` (`histempresaid`),
  CONSTRAINT `fk_usuariosempresashist_histempresaid` FOREIGN KEY (`histempresaid`) REFERENCES `empresas` (`empresaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Cuando elimina una asociaci�n, se copian los datos antes de eliminar de la tabla "usuariosempresas"';
