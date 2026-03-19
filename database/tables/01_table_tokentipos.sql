CREATE TABLE `tokentipos` (
  `tokentipoid` int(11) NOT NULL AUTO_INCREMENT,
  `tokentipodsc` varchar(50) NOT NULL,
  PRIMARY KEY (`tokentipoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Tipos de Tokens para ser usados en la tabla "usuariostokens"';
