CREATE TABLE IF NOT EXISTS `pptocompratransaccionestipo` (
  `pptocompratransacciontipoid` varchar(30) NOT NULL,
  `pptocompratransacciontipodsc` varchar(120) NOT NULL,
  `pptocompratransacciontipoactivo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`pptocompratransacciontipoid`),
  KEY `idx_pptocompratransaccionestipo_activo` (`pptocompratransacciontipoactivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
