INSERT INTO menus (
    menuid, menupadre, menudesc, menuform,
    menunivel, menunvlord, menuicono, menuactivo,
    auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip,
    auditedicionusuarioid, auditediciondispositivo, auditedicionip
) VALUES
-- NIVEL 1
(10, NULL, 'Dashboard',        'dashboard.php',      1, 1, 'bi-speedometer2', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
(5,  NULL, 'Transacciones',    'transacciones.php',  1, 2, 'bi-arrow-left-right', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
(1,  NULL, 'Configuraciones',  'config.php',         1, 3, 'bi-gear', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),

-- NIVEL 2 (Transacciones)
(6,  5, 'Leche',               '#',                  2, 1, 'bi-droplet', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
(9,  5, 'Alimentación',        '#',                  2, 2, 'bi-basket', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),

-- NIVEL 3 (Leche)
(7,  6, 'Prod. Leche',         'prod_leche.php',     3, 1, 'bi-graph-up', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
(8,  6, 'Retiro Leche',        'retiro_leche.php',   3, 2, 'bi-truck', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
-- NIVEL 3 (Alimentación)
(11, 9, 'Suplem.',             'suplementos.php',    3, 1, 'bi-plus-circle', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),

-- NIVEL 2 (Configuraciones)
(2,  1, 'Administracion',      '#',                  2, 1, 'bi-shield-lock', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
(12, 1, 'Maestros',            '#',                  2, 2, 'bi-list-ul', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),

-- NIVEL 3 (Administracion)
(3,  2, 'Empresas',            'empresas.php',       3, 1, 'bi-building', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
(4,  2, 'Usuarios',            'usuarios.php',       3, 2, 'bi-people', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),

-- NIVEL 3 (Maestros)
(13, 12, 'Fundos',             'fundos.php',         3, 1, 'bi-tree', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1'),
(14, 12, 'Bodegas',            'bodegas.php',        3, 2, 'bi-box-seam', 1, 1, 'SCRIPT', '127.0.0.1', NULL, 'SCRIPT', '127.0.0.1');