-- Tabla consolidada, sin fundo, diaria para la proyección de leche. Anio y Mes son default de proylechefecha
CREATE TABLE IF NOT EXISTS proylechediariaconsolidada (
    proylechefecha DATE NOT NULL,
    proylecheventatotlitros INT NOT NULL,
    proylecheventatotvacas INT NOT NULL,
    proylecheventatotltsxvaca DECIMAL(10,2) NOT NULL,
    proylecheanio INT NOT NULL DEFAULT (YEAR(proylechefecha)),
    proylechemes INT NOT NULL DEFAULT (MONTH(proylechefecha)),
    `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
    `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
    `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
    `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
    `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
    `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp() COMMENT 'Fecha hora de UPD',
    PRIMARY KEY (proylechefecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;