DELIMITER //
DROP PROCEDURE IF EXISTS sp_menus_insertar//
CREATE PROCEDURE sp_menus_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_menupadre int(11);
  DECLARE v_menudesc varchar(100);
  DECLARE v_menuform varchar(100);
  DECLARE v_menunivel int(4);
  DECLARE v_menunvlord int(4);
  DECLARE v_menuicono varchar(50);
  DECLARE v_menuactivo tinyint(1);
  DECLARE v_new_menuid int(11);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_menupadre = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menupadre')) AS SIGNED),
    v_menudesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menudesc')),
    v_menuform = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuform')),
    v_menunivel = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menunivel')) AS SIGNED),
    v_menunvlord = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menunvlord')) AS SIGNED),
    v_menuicono = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuicono')),
    v_menuactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuactivo')) AS SIGNED);

  INSERT INTO `menus` (
    `menupadre`,
    `menudesc`,
    `menuform`,
    `menunivel`,
    `menunvlord`,
    `menuicono`,
    `menuactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_menupadre,
    v_menudesc,
    v_menuform,
    v_menunivel,
    v_menunvlord,
    v_menuicono,
    v_menuactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET v_new_menuid = LAST_INSERT_ID();

  -- Generic log insert
  INSERT INTO `menuslog` (
    `menuid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_new_menuid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  -- Asignar menu a perfiles con perfilmenuactivo = 0
  INSERT INTO `perfilesmenus` (
    `perfilid`,
    `menuid`,
    `perfilmenuactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  SELECT
    p.`perfilid`,
    v_new_menuid,
    CASE WHEN p.`perfilesadmin` = 1 OR p.`perfilesroot` = 1 THEN 1 ELSE 0 END,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  FROM `perfiles` p;

  -- Insertar log para cada perfilmenu insertado
  INSERT INTO `perfilesmenuslog` (
    `perfilid`,
    `menuid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  )
  SELECT
    p.`perfilid`,
    v_new_menuid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  FROM `perfiles` p;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_menus_editar//
CREATE PROCEDURE sp_menus_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_menuid int(11);
  DECLARE v_menupadre int(11);
  DECLARE v_menudesc varchar(100);
  DECLARE v_menuform varchar(100);
  DECLARE v_menunivel int(4);
  DECLARE v_menunvlord int(4);
  DECLARE v_menuicono varchar(50);
  DECLARE v_menuactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_menuid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuid')) AS SIGNED),
    v_menupadre = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menupadre')) AS SIGNED),
    v_menudesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menudesc')),
    v_menuform = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuform')),
    v_menunivel = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menunivel')) AS SIGNED),
    v_menunvlord = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menunvlord')) AS SIGNED),
    v_menuicono = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuicono')),
    v_menuactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `menus` WHERE `menuid` = v_menuid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('menuid', `menus`.`menuid`, 'menupadre', `menus`.`menupadre`, 'menudesc', `menus`.`menudesc`, 'menuform', `menus`.`menuform`, 'menunvlord', `menus`.`menunvlord`, 'menuicono', `menus`.`menuicono`, 'menuactivo', `menus`.`menuactivo`, 'auditcreacionusuarioid', `menus`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `menus`.`auditcreaciondispositivo`, 'auditcreacionip', `menus`.`auditcreacionip`, 'auditcreacionfechahora', `menus`.`auditcreacionfechahora`, 'auditedicionusuarioid', `menus`.`auditedicionusuarioid`, 'auditediciondispositivo', `menus`.`auditediciondispositivo`, 'auditedicionip', `menus`.`auditedicionip`, 'auditedicionfechahora', `menus`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `menus`
  WHERE `menuid` = v_menuid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `menuslog` (
    `menuid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_menuid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `menus`
  SET `menupadre` = v_menupadre,
    `menudesc` = v_menudesc,
    `menuform` = v_menuform,
    `menunivel` = v_menunivel,
    `menunvlord` = v_menunvlord,
    `menuicono` = v_menuicono,
    `menuactivo` = v_menuactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `menuid` = v_menuid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_menus_anular//
CREATE PROCEDURE sp_menus_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_menuid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_menuid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `menus` WHERE `menuid` = v_menuid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('menuid', `menus`.`menuid`, 'menupadre', `menus`.`menupadre`, 'menudesc', `menus`.`menudesc`, 'menuform', `menus`.`menuform`, 'menunvlord', `menus`.`menunvlord`, 'menuicono', `menus`.`menuicono`, 'menuactivo', `menus`.`menuactivo`, 'auditcreacionusuarioid', `menus`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `menus`.`auditcreaciondispositivo`, 'auditcreacionip', `menus`.`auditcreacionip`, 'auditcreacionfechahora', `menus`.`auditcreacionfechahora`, 'auditedicionusuarioid', `menus`.`auditedicionusuarioid`, 'auditediciondispositivo', `menus`.`auditediciondispositivo`, 'auditedicionip', `menus`.`auditedicionip`, 'auditedicionfechahora', `menus`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `menus`
  WHERE `menuid` = v_menuid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `menuslog` (
    `menuid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_menuid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `menus`
  SET `menuactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `menuid` = v_menuid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_menus_listar//
CREATE PROCEDURE sp_menus_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroMenupadre VARCHAR(255);
  DECLARE v_filtroMenudesc VARCHAR(255);
  DECLARE v_filtroMenuactivo VARCHAR(255);
  SET v_filtroMenupadre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroMenupadre'));
  SET v_filtroMenudesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroMenudesc'));
  SET v_filtroMenuactivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroMenuactivo'));

  SELECT
    m.`menuid`        AS menuid,
    m.`menudesc`      AS menudesc,
    m.`menunivel`     AS menunivel,
    m.`menunvlord`    AS menunvlord,
    COALESCE(m.`menupadre`, 0) AS menupadre,
    p.`menudesc`      AS padrenom,
    CASE
        WHEN m.`menunivel` = 1 THEN
            CONCAT(LPAD(m.`menunvlord`, 2, '0'), '.00.00')
        WHEN m.`menunivel` = 2 THEN
            CONCAT(LPAD(p.`menunvlord`, 2, '0'), '.', LPAD(m.`menunvlord`, 2, '0'), '.00')
        WHEN m.`menunivel` = 3 THEN
            CONCAT(
                LPAD(gp.`menunvlord`, 2, '0'), '.',
                LPAD(p.`menunvlord`, 2, '0'), '.',
                LPAD(m.`menunvlord`, 2, '0')
            )
    END AS orden_path,
    m.`menuform`    AS menuform,
    m.`menuicono`  AS menuicono,
    m.`menuactivo` AS menuactivo
  FROM `menus` m
  LEFT JOIN `menus` p  ON p.menuid  = m.menupadre     -- padre
  LEFT JOIN `menus` gp ON gp.menuid = p.menupadre     -- abuelo
  WHERE 1=1
    AND (v_filtroMenupadre IS NULL OR v_filtroMenupadre = '' OR m.`menupadre` = v_filtroMenupadre)
    AND (v_filtroMenudesc IS NULL OR v_filtroMenudesc = '' OR m.`menudesc` LIKE CONCAT('%', v_filtroMenudesc, '%'))
    AND (v_filtroMenuactivo IS NULL OR v_filtroMenuactivo = '' OR m.`menuactivo` = v_filtroMenuactivo)
  ORDER BY orden_path, m.menuid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
