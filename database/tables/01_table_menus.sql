CREATE TABLE `menus` (
  `menuid` int(11) NOT NULL AUTO_INCREMENT,
  `menupadre` int(11) NULL DEFAULT NULL COMMENT 'Menu superior para dise�ar la estructura de los men�s tipo Arbol',
  `menudesc` varchar(100) NOT NULL COMMENT 'El nombre del menu debe ser unico.',
  `menuform` varchar(100) NOT NULL COMMENT 'Archivo PHP. Debe ser unico',
  `menunivel` int(4) NOT NULL DEFAULT 0 COMMENT 'Nivel del menu en la estructura de arbol',
  `menunvlord` int(4) NOT NULL DEFAULT 0 COMMENT 'Orden dentro del mismo nivel',
  `menuicono` varchar(50) NOT NULL COMMENT 'Icon name',
  `menuactivo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si esta activo el registro',
  `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
  `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
  `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
  `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
  `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
  `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de UPD',
  PRIMARY KEY (`menuid`),
  UNIQUE KEY `uq_menus_menudesc` (`menudesc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_menus_padre_orden
ON menus (menupadre, menunvlord);
