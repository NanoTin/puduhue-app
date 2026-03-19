-- Perfil ROOT
INSERT INTO `perfilesmenus`
	(perfilid, menuid, perfilmenuactivo, auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip)
  SELECT
    1,
    m.`menuid`,
    1,
    1,
    'SCRIPT',
    '127.0.0.1'
  FROM `menus` m;
-- Perfil Administrador Sistema
INSERT INTO `perfilesmenus`
	(perfilid, menuid, perfilmenuactivo, auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip)
  SELECT
    2,
    m.`menuid`,
    1,
    1,
    'SCRIPT',
    '127.0.0.1'
  FROM `menus` m;