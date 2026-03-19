-- Presupuesto mensual de leche, vacas y litros por vaca
CREATE TABLE IF NOT EXISTS pptolechemensual (
    pptolecanio INT NOT NULL,
    pptolecmes INT NOT NULL,
    fundoid INT NOT NULL,
    pptoleclitros INT NOT NULL,
    pptolecvacas INT NOT NULL,
    pptolecltsxvc DECIMAL(10,2) NOT NULL,
    pptolecfecha DATETIME NOT NULL COMMENT 'Fecha inicial del mes presupuestado',
    pptolecdiasdelmes INT NOT NULL COMMENT 'Nro de dias del mes presupuestado',
    `auditcreacionusuarioid` int(11) NOT NULL COMMENT 'p_in_usuarioid',
    `auditcreaciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditcreacionip` varchar(50) NOT NULL COMMENT 'IP del PC',
    `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha hora de INS',
    `auditedicionusuarioid` int(11) NULL COMMENT 'p_in_usuarioid',
    `auditediciondispositivo` varchar(100) NOT NULL COMMENT 'Dispositivo nombre',
    `auditedicionip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP del PC',
    `auditedicionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp() COMMENT 'Fecha hora de UPD',
    PRIMARY KEY (pptolecanio, pptolecmes, fundoid),
    CONSTRAINT `fk_pptolechemensual_fundoid` FOREIGN KEY (fundoid) REFERENCES fundos (fundoid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;