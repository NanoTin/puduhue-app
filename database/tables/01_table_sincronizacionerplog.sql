-- Tabla para mantener LOG de la sicronizaciones a traves de proceso CRON o manuales hacia el ERP
CREATE TABLE IF NOT EXISTS `sincronizacionerplog` (
  `sincronizacionlogid` INT(11) NOT NULL AUTO_INCREMENT,
  `sincronizaciontipoexec` VARCHAR(10) NOT NULL, -- MANUAL | CRON
  `entidad` VARCHAR(10) NOT NULL, -- Opciones: ["PRDLCH", "SUPANML"]
  `entidadid` INT(11) NOT NULL,
  `accion` VARCHAR(3) NOT NULL, -- INS: Registro nuevo, UPD: Registro actualizado, DEL: Registro eliminado
  `estado` VARCHAR(20) NOT NULL, -- success or error
  `mensaje` TEXT NULL, -- Puede ser del CATCH o de la API Response
  `jsondatos` JSON NULL,
  `fechaini` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fechafin` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  `usuarioid` INT(11) NOT NULL,
  PRIMARY KEY (`sincronizacionlogid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;