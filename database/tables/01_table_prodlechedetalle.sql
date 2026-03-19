CREATE TABLE `prodlechedetalle` (
  `prodlecheid` int(11) NOT NULL,
  `prodlechetipoid` int(11) NOT NULL,
  `pldetlitros` int(4) NOT NULL,
  `pldetvacas` int(4) NOT NULL,
  `pldetlitrosxvaca` float NOT NULL,
  `prodlechecod` varchar(20) NULL COMMENT 'PL-0000000000X',
  `erpdocumentocod` varchar(20) NULL COMMENT 'Lo que devuelve en el JSON atributo "documento"',
  `pldetfechareg` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pldetfechaedt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp(),
  PRIMARY KEY (`prodlecheid`, `prodlechetipoid`),
  UNIQUE KEY `uq_prodlechedetalle_prodlechecod` (`prodlechecod`),
  KEY `idx_prodlechedetalle_prodlecheid` (`prodlecheid`),
  CONSTRAINT `fk_prodlechedetalle_prodlecheid` FOREIGN KEY (`prodlecheid`) REFERENCES `prodleche` (`prodlecheid`),
  KEY `idx_prodlechedetalle_prodlechetipoid` (`prodlechetipoid`),
  CONSTRAINT `fk_prodlechedetalle_prodlechetipoid` FOREIGN KEY (`prodlechetipoid`) REFERENCES `prodlechetipos` (`prodlechetipoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Producci�n de Leche - Detalle';
