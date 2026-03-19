CREATE TABLE `perfiles` (
  `perfilid` int(11) NOT NULL AUTO_INCREMENT,
  `perfildesc` varchar(100) NOT NULL COMMENT 'El nombre del perfil debe ser unico.',
  `perfilesroot` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Solo puede haber un perfil ROOT',
  `perfilesadmin` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Solo puede haber un perfil Admin',
  `perfilactivo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si esta activo el registro',
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`perfilid`),
  UNIQUE KEY `uq_perfiles_perfildesc` (`perfildesc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
