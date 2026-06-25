/*
Base compartida para Modulo de Compras.

Objetivo:
- dejar `temporadas` preparada para `PPTO_COMPRAS`;
- hacer compatible la regla "una temporada activa por tipo" con MariaDB;
- habilitar busqueda por tipo y fecha para presupuesto, REQ y PreOC.

Antes de ejecutar en una base con datos, revisar duplicados activos:

SELECT temporadatipocodigo, COUNT(*) AS activas
FROM temporadas
WHERE temporadaactivo = 1
GROUP BY temporadatipocodigo
HAVING COUNT(*) > 1;
*/

ALTER TABLE `temporadas`
  ADD COLUMN IF NOT EXISTS `temporadaactivatipocodigo` varchar(20)
    GENERATED ALWAYS AS (
      CASE WHEN `temporadaactivo` = 1 THEN `temporadatipocodigo` ELSE NULL END
    ) STORED
    COMMENT 'Llave generada para permitir solo una temporada activa por tipo'
    AFTER `temporadaactivo`;

CREATE UNIQUE INDEX IF NOT EXISTS `uq_temporadas_tipo_activo`
  ON `temporadas` (`temporadaactivatipocodigo`);

CREATE INDEX IF NOT EXISTS `idx_temporadas_tipo_rango`
  ON `temporadas` (`temporadatipocodigo`, `temporadainicio`, `temporadafin`);

CREATE INDEX IF NOT EXISTS `idx_temporadas_activo`
  ON `temporadas` (`temporadaactivo`);

INSERT INTO `temporadas`
  (
    `temporadatipocodigo`,
    `temporadadescripcion`,
    `temporadainicio`,
    `temporadafin`,
    `temporadaactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
SELECT
  'PPTO_COMPRAS',
  'Presupuesto Compras 25-26',
  '2025-07-01',
  '2026-06-30',
  1,
  1,
  'system',
  '127.0.0.1'
WHERE NOT EXISTS (
  SELECT 1
  FROM `temporadas`
  WHERE `temporadatipocodigo` = 'PPTO_COMPRAS'
);

INSERT INTO `pptocompratransaccionestipo` (
    `pptocompratransacciontipoid`,
    `pptocompratransacciontipodsc`,
    `pptocompratransacciontipoactivo`
) VALUES
('PPTO_CARGA', 'Carga inicial mensual', 1),
('PPTO_AJUSTE_POS', 'Ajuste positivo', 1),
('PPTO_AJUSTE_NEG', 'Ajuste negativo', 1),
('PPTO_TRASPASO_SALIDA', 'Traspaso hacia otro presupuesto', 1),
('PPTO_TRASPASO_ENTRADA', 'Traspaso desde otro presupuesto', 1),
('POC_RESERVA', 'Reserva provisional', 1),
('POC_CONFIRMACION', 'Confirmación de reserva', 1),
('POC_REVERSA', 'Reversa de confirmación', 1)
ON DUPLICATE KEY UPDATE
  `pptocompratransacciontipodsc` = VALUES(`pptocompratransacciontipodsc`),
  `pptocompratransacciontipoactivo` = VALUES(`pptocompratransacciontipoactivo`);

ALTER TABLE `pptocompra`
  ADD COLUMN IF NOT EXISTS `pptocompraobservacion` varchar(500) NULL DEFAULT NULL AFTER `pptocompraactivo`,
  ADD COLUMN IF NOT EXISTS `pptocomprapresupuestado` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma inicial de ppomontoppto de la carga base (PPTO_CARGA)' AFTER `pptocompraactivo`,
  ADD COLUMN IF NOT EXISTS `pptocompraajustespositivos` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma de montos PPTO_AJUSTE_POS' AFTER `pptocomprapresupuestado`,
  ADD COLUMN IF NOT EXISTS `pptocompraajustenegativos` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma de montos PPTO_AJUSTE_NEG (valor normalmente negativo)' AFTER `pptocompraajustespositivos`,
  ADD COLUMN IF NOT EXISTS `pptocompreproyectado` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Presupuesto reproyectado' AFTER `pptocompraajustenegativos`,
  ADD COLUMN IF NOT EXISTS `pptocompramontoconsumidopnd` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Suma consumos pendientes (POC_RESERVA)' AFTER `pptocompreproyectado`,
  ADD COLUMN IF NOT EXISTS `pptocompramontoconsumidocnf` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Monto consumido confirmado (POC_CONFIRMACION + POC_REVERSA, positivo)' AFTER `pptocompramontoconsumidopnd`,
  ADD COLUMN IF NOT EXISTS `pptocomprasaldodisponible` decimal(18,2) NOT NULL DEFAULT 0 COMMENT 'Saldo disponible calculado' AFTER `pptocompramontoconsumidocnf`;

ALTER TABLE `pptocompratransacciones`
  ADD COLUMN IF NOT EXISTS `pptocompratransaccionfecha` date NULL DEFAULT NULL COMMENT 'Fecha funcional de la transaccion; no reemplaza auditcreacionfechahora' AFTER `pptocompratransacciontipoid`,
  ADD COLUMN IF NOT EXISTS `pptocompranrodocumentoorigen` int(11) NOT NULL DEFAULT 0 COMMENT 'Numero documento origen; 0 para eventos internos PPTO' AFTER `pptocompramotivo`,
  ADD COLUMN IF NOT EXISTS `pptocompramoduloorigen` varchar(30) NOT NULL DEFAULT 'PPTO_COMPRA' COMMENT 'Modulo que origina la transaccion' AFTER `pptocompranrodocumentoorigen`,
  ADD COLUMN IF NOT EXISTS `pptocompraestado` varchar(20) NOT NULL DEFAULT 'CONFIRMADO' COMMENT 'CONFIRMADO, PENDIENTE, RECHAZADA, REVERSA' AFTER `pptocompramoduloorigen`;

CREATE INDEX IF NOT EXISTS `idx_pptocompratransacciones_origen`
  ON `pptocompratransacciones` (`pptocompramoduloorigen`, `pptocompranrodocumentoorigen`);

CREATE INDEX IF NOT EXISTS `idx_pptocompratransacciones_estado`
  ON `pptocompratransacciones` (`pptocompraestado`);

CREATE INDEX IF NOT EXISTS `idx_pptocompratransacciones_transaccionfecha`
  ON `pptocompratransacciones` (`pptocompratransaccionfecha`, `auditcreacionfechahora`);

UPDATE `pptocompratransacciones` tr
INNER JOIN `pptocompra` pc ON pc.pptocompraid = tr.pptocompraid
INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
SET tr.pptocompratransaccionfecha = CASE
    WHEN tr.pptocompratransacciontipoid = 'PPTO_CARGA' THEN t.temporadainicio
    WHEN tr.pptocompratransacciontipoid IN ('PPTO_AJUSTE_POS', 'PPTO_AJUSTE_NEG') THEN STR_TO_DATE(CONCAT(tr.ppoanio, '-', LPAD(tr.ppomes, 2, '0'), '-01'), '%Y-%m-%d')
    WHEN tr.pptocompratransacciontipoid IN ('PPTO_TRASPASO_ENTRADA', 'PPTO_TRASPASO_SALIDA') THEN DATE(tr.auditcreacionfechahora)
    ELSE DATE(tr.auditcreacionfechahora)
END
WHERE tr.pptocompratransaccionfecha IS NULL;

INSERT INTO `pptocompratransacciones` (
    `pptocompraid`,
    `ppoanio`,
    `ppomes`,
    `pptocompratransacciontipoid`,
    `pptocompratransaccionfecha`,
    `pptocompramonto`,
    `pptocompramotivo`,
    `pptocompranrodocumentoorigen`,
    `pptocompramoduloorigen`,
    `pptocompraestado`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
)
SELECT
    pc.pptocompraid,
    pm.ppoanio,
    pm.ppomes,
    'PPTO_CARGA',
    t.temporadainicio,
    IFNULL(x.monto_base, 0),
    'Carga base del presupuesto',
    0,
    'PPTO_COMPRA',
    'CONFIRMADO',
    pc.auditcreacionusuarioid,
    pc.auditcreaciondispositivo,
    pc.auditcreacionip
FROM `pptocompra` pc
INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
INNER JOIN (
    SELECT `pptocompraid`, MIN(CONCAT(`ppoanio`, LPAD(`ppomes`, 2, '0'))) AS primer_periodo, SUM(`ppomontoppto`) AS monto_base
    FROM `pptocompramensual`
    GROUP BY `pptocompraid`
) x ON x.pptocompraid = pc.pptocompraid
INNER JOIN `pptocompramensual` pm
    ON pm.pptocompraid = pc.pptocompraid
   AND CONCAT(pm.ppoanio, LPAD(pm.ppomes, 2, '0')) = x.primer_periodo
WHERE NOT EXISTS (
    SELECT 1
    FROM `pptocompratransacciones` tr
    WHERE tr.pptocompraid = pc.pptocompraid
      AND tr.pptocompratransacciontipoid = 'PPTO_CARGA'
);

UPDATE `pptocompra` pc
LEFT JOIN (
    SELECT `pptocompraid`, SUM(`ppomontoppto`) AS monto_base
    FROM `pptocompramensual`
    GROUP BY `pptocompraid`
) m ON m.pptocompraid = pc.pptocompraid
LEFT JOIN (
    SELECT
        tr.pptocompraid,
        SUM(CASE WHEN tr.pptocompratransacciontipoid = 'PPTO_AJUSTE_POS' THEN tr.pptocompramonto ELSE 0 END) AS ajuste_pos,
        SUM(CASE WHEN tr.pptocompratransacciontipoid = 'PPTO_AJUSTE_NEG' THEN tr.pptocompramonto ELSE 0 END) AS ajuste_neg,
        SUM(CASE WHEN tr.pptocompratransacciontipoid IN ('PPTO_TRASPASO_ENTRADA', 'PPTO_TRASPASO_SALIDA') THEN tr.pptocompramonto ELSE 0 END) AS traspasos,
        SUM(CASE WHEN tr.pptocompratransacciontipoid = 'POC_RESERVA' THEN tr.pptocompramontoencurso ELSE 0 END) AS consumo_pnd,
        SUM(CASE WHEN tr.pptocompratransacciontipoid IN ('POC_CONFIRMACION', 'POC_REVERSA') THEN tr.pptocompramontoconfirmado ELSE 0 END) AS consumo_cnf
    FROM `pptocompratransacciones` tr
    GROUP BY tr.pptocompraid
) a ON a.pptocompraid = pc.pptocompraid
SET
    pc.pptocomprapresupuestado = IFNULL(m.monto_base, 0),
    pc.pptocompraajustespositivos = IFNULL(a.ajuste_pos, 0),
    pc.pptocompraajustenegativos = IFNULL(a.ajuste_neg, 0),
    pc.pptocompreproyectado = IFNULL(m.monto_base, 0) + IFNULL(a.ajuste_pos, 0) + IFNULL(a.ajuste_neg, 0) + IFNULL(a.traspasos, 0),
    pc.pptocompramontoconsumidopnd = IFNULL(a.consumo_pnd, 0),
    pc.pptocompramontoconsumidocnf = IFNULL(a.consumo_cnf, 0),
    pc.pptocomprasaldodisponible = IFNULL(m.monto_base, 0) + IFNULL(a.ajuste_pos, 0) + IFNULL(a.ajuste_neg, 0) + IFNULL(a.traspasos, 0) + IFNULL(a.consumo_pnd, 0) + IFNULL(a.consumo_cnf, 0);
