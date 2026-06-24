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
