/*
Corte invitems - uso funcional local.

Objetivo:
- agregar `invitemusocodigo` como transicion desde flags historicos;
- dejar por defecto `BDG` para no reclasificar masivamente sin revision;
- permitir asignacion controlada por pantalla o UPDATE directo;
- mantener `invitemleche` temporalmente por compatibilidad.

No ejecutar Productos ERP antes de validar Produccion de Leche, Suplementacion Animal
y Tipos de Leche con esta columna.
*/

ALTER TABLE `invitems`
  ADD COLUMN IF NOT EXISTS `invitemusocodigo` varchar(10) NOT NULL DEFAULT 'BDG'
    COMMENT 'Uso funcional local: BDG=bodega/base, LCH=leche, ALM=suplementacion animal, CMB=combustible'
    AFTER `invitemstockeable`;

CREATE INDEX IF NOT EXISTS `idx_invitems_usocodigo`
  ON `invitems` (`invitemusocodigo`);

ALTER TABLE `invitems`
  ADD COLUMN IF NOT EXISTS `familiaid` int(11) NULL DEFAULT NULL
    COMMENT 'FK a familias sincronizada desde ERP Producto'
    AFTER `invitemusocodigo`,
  ADD COLUMN IF NOT EXISTS `subfamiliaid` int(11) NULL DEFAULT NULL
    COMMENT 'FK a subfamilias sincronizada desde ERP Producto'
    AFTER `familiaid`,
  ADD COLUMN IF NOT EXISTS `erptasaimpositivaid` int(11) NULL DEFAULT NULL
    COMMENT 'FK a tasa impositiva ERP para compras/PreOC'
    AFTER `subfamiliaid`,
  ADD COLUMN IF NOT EXISTS `erppartidafinancieraid` int(11) NULL DEFAULT NULL
    COMMENT 'FK a DIMPARFIN para DimensionDistribucion en PreOC'
    AFTER `erptasaimpositivaid`,
  ADD COLUMN IF NOT EXISTS `invitemcompra` tinyint(1) NOT NULL DEFAULT 0
    COMMENT '1=Producto utilizable en compras/PreOC'
    AFTER `erppartidafinancieraid`,
  ADD COLUMN IF NOT EXISTS `invitemcostoestandar` decimal(18,4) NOT NULL DEFAULT 0.0000
    COMMENT 'Costo estandar ERP o manual si ERP no informa valor'
    AFTER `invitemcompra`,
  ADD COLUMN IF NOT EXISTS `invitemcostoestandarfechahora` datetime NULL DEFAULT NULL
    COMMENT 'Fecha/hora ultima actualizacion costo estandar'
    AFTER `invitemcostoestandar`;

CREATE INDEX IF NOT EXISTS `idx_invitems_familiaid`
  ON `invitems` (`familiaid`);

CREATE INDEX IF NOT EXISTS `idx_invitems_subfamiliaid`
  ON `invitems` (`subfamiliaid`);

CREATE INDEX IF NOT EXISTS `idx_invitems_erptasaimpositivaid`
  ON `invitems` (`erptasaimpositivaid`);

CREATE INDEX IF NOT EXISTS `idx_invitems_erppartidafinancieraid`
  ON `invitems` (`erppartidafinancieraid`);

CREATE INDEX IF NOT EXISTS `idx_invitems_compra`
  ON `invitems` (`invitemcompra`);

ALTER TABLE `invitems`
  ADD CONSTRAINT `fk_invitems_familiaid`
    FOREIGN KEY (`familiaid`) REFERENCES `familias` (`familiaid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invitems_subfamiliaid`
    FOREIGN KEY (`subfamiliaid`) REFERENCES `subfamilias` (`subfamiliaid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invitems_erptasaimpositivaid`
    FOREIGN KEY (`erptasaimpositivaid`) REFERENCES `erptasasimpositivas` (`erptasaimpositivaid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invitems_erppartidafinancieraid`
    FOREIGN KEY (`erppartidafinancieraid`) REFERENCES `erppartidasfinancieras` (`erppartidafinancieraid`) ON DELETE SET NULL;
