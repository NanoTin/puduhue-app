-- Tabla de temporadas para diferentes conceptos. columna "temporadatipocodigo" indica el concepto. Ej: LECHE, PPTO_COMPRAS
CREATE TABLE IF NOT EXISTS temporadas (
    temporadaid INT NOT NULL AUTO_INCREMENT,
    temporadatipocodigo VARCHAR(20) NOT NULL COMMENT 'Código del tipo de temporada, ej: LECHE, PPTO_COMPRAS, ETC',
    temporadadescripcion VARCHAR(100) NOT NULL,
    temporadainicio DATE NOT NULL,
    temporadafin DATE NOT NULL,
    temporadaactivo TINYINT(1) NOT NULL DEFAULT 1,
    temporadaactivatipocodigo VARCHAR(20)
        GENERATED ALWAYS AS (CASE WHEN temporadaactivo = 1 THEN temporadatipocodigo ELSE NULL END) STORED
        COMMENT 'Llave generada para permitir solo una temporada activa por tipo',
    `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
    `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
    `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
    `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
    `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
    `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp() COMMENT 'Fecha hora de UPD',
    PRIMARY KEY (temporadaid),
    UNIQUE KEY uq_temporadas_tipo_activo (temporadaactivatipocodigo),
    KEY idx_temporadas_tipo_rango (temporadatipocodigo, temporadainicio, temporadafin),
    KEY idx_temporadas_activo (temporadaactivo),
    CONSTRAINT chk_temporadas_rango CHECK (temporadainicio <= temporadafin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar temporada leche actual
INSERT INTO temporadas 
    (temporadatipocodigo, temporadadescripcion, temporadainicio, temporadafin, temporadaactivo, 
    auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip)
VALUES
    ('LECHE', 'Temporada Leche 25-26', '2025-07-01', '2026-06-30', 1, 
     1, 'system', '127.0.0.1');

-- Insertar temporada inicial para presupuesto de compras
INSERT INTO temporadas
    (temporadatipocodigo, temporadadescripcion, temporadainicio, temporadafin, temporadaactivo,
    auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip)
SELECT
    'PPTO_COMPRAS', 'Presupuesto Compras 25-26', '2025-07-01', '2026-06-30', 1,
    1, 'system', '127.0.0.1'
WHERE NOT EXISTS (
    SELECT 1
    FROM temporadas
    WHERE temporadatipocodigo = 'PPTO_COMPRAS'
);
