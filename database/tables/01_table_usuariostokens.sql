CREATE TABLE `usuariostokens` (
  `requerimientoid` int(11) NOT NULL AUTO_INCREMENT,
  `tokentipoid` int(11) NOT NULL,
  `usuarioid` int(11) NOT NULL,
  `usuarioemail` varchar(100) NOT NULL COMMENT 'Email donde se envió el token',
  `usuariotoken` varchar(255) NOT NULL COMMENT 'Token que se compara con el parametro en la URL',
  `fechareq` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha Requerimiento o solicitud del Token',
  `fechaexp` datetime NOT NULL COMMENT 'Fecha Expiración del Token',
  `usado` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Cuando sea usado, cambia a 1 (True)',
  PRIMARY KEY (`requerimientoid`),
  KEY `idx_usuariostokens_tokentipoid` (`tokentipoid`),
  CONSTRAINT `fk_usuariostokens_tokentipoid` FOREIGN KEY (`tokentipoid`) REFERENCES `tokentipos` (`tokentipoid`),
  KEY `idx_usuariostokens_usuarioid` (`usuarioid`),
  CONSTRAINT `fk_usuariostokens_usuarioid` FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Solo para temas usuarios relacionado con la contraseña';
