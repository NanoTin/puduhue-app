-- Tabla de temporadas para diferentes conceptos. columna "temporadatipocodigo" indica el concepto. Ej: LECHE
CREATE TABLE IF NOT EXISTS temporadas (
    temporadaid INT NOT NULL AUTO_INCREMENT,
    temporadatipocodigo VARCHAR(20) NOT NULL COMMENT 'Código del tipo de temporada, ej: LECHE, VENTAS, ETC',
    temporadadescripcion VARCHAR(100) NOT NULL,
    temporadainicio DATE NOT NULL,
    temporadafin DATE NOT NULL,
    temporadaactivo TINYINT(1) NOT NULL DEFAULT 1,
    `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
    `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
    `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
    `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
    `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
    `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp() COMMENT 'Fecha hora de UPD',
    PRIMARY KEY (temporadaid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Solo puede haber una temporada activa por tipo de temporada
CREATE UNIQUE INDEX idx_temporada_tipo_activa ON temporadas (temporadatipocodigo)
    WHERE temporadaactivo = 1;

-- Insertar temporada leche actual
INSERT INTO temporadas 
    (temporadatipocodigo, temporadadescripcion, temporadainicio, temporadafin, temporadaactivo, 
    auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip)
VALUES
    ('LECHE', 'Temporada Leche 25-26', '2025-07-01', '2026-06-30', 1, 
     1, 'system', '127.0.0.1');