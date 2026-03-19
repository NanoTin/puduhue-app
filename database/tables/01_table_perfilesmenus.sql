CREATE TABLE `perfilesmenus` (
  `perfilid` int(11) NOT NULL,
  `menuid` int(11) NOT NULL,
  `perfilmenuactivo` tinyint(1) NOT NULL DEFAULT 1,
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT'' 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`perfilid`, `menuid`),
  KEY `idx_perfilesmenus_perfilid` (`perfilid`),
  CONSTRAINT `fk_perfilesmenus_perfilid` FOREIGN KEY (`perfilid`) REFERENCES `perfiles` (`perfilid`),
  KEY `idx_perfilesmenus_menuid` (`menuid`),
  CONSTRAINT `fk_perfilesmenus_menuid` FOREIGN KEY (`menuid`) REFERENCES `menus` (`menuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
