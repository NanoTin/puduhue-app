CREATE TABLE `erptokenactivo` (
  `access_token` varchar(50) NOT NULL,
  `generado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP on update current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT 'Siempre mantiene el ultimo token activo para integrar con el ERP.';
