-- Endurecimiento API externa: permisos por token.
-- Ejecutar en phpMyAdmin antes o junto con el despliegue del PHP actualizado.

ALTER TABLE `usuariosapitokens`
  ADD COLUMN IF NOT EXISTS `tokenpermisos` varchar(500) NOT NULL DEFAULT 'prodleche-detalle:query'
  AFTER `tokenipultuso`;

UPDATE `usuariosapitokens`
SET `tokenpermisos` = 'prodleche-detalle:query'
WHERE `tokenpermisos` IS NULL OR TRIM(`tokenpermisos`) = '';

