DELIMITER //
DROP PROCEDURE IF EXISTS sp_perfilesmenus_insertar//
CREATE PROCEDURE sp_perfilesmenus_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_perfilid int(11);
  DECLARE v_menuid int(11);
  DECLARE v_perfilmenuactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED),
    v_menuid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuid')) AS SIGNED),
    v_perfilmenuactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilmenuactivo')) AS SIGNED);

  IF EXISTS (SELECT 1 FROM `perfilesmenus` WHERE `perfilid` = v_perfilid AND `menuid` = v_menuid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Duplicate primary key');
    LEAVE sp_main;
  END IF;

  INSERT INTO `perfilesmenus` (
    `perfilid`,
    `menuid`,
    `perfilmenuactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_perfilid,
    v_menuid,
    v_perfilmenuactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `perfilesmenuslog` (
    `perfilid`,
    `menuid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_perfilid,
    v_menuid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfilesmenus_editar//
CREATE PROCEDURE sp_perfilesmenus_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_perfilid int(11);
  DECLARE v_menuid int(11);
  DECLARE v_perfilmenuactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED),
    v_menuid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuid')) AS SIGNED),
    v_perfilmenuactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilmenuactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `perfilesmenus` WHERE `perfilid` = v_perfilid AND `menuid` = v_menuid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('perfilid', `perfilesmenus`.`perfilid`, 'menuid', `perfilesmenus`.`menuid`, 'perfilmenuactivo', `perfilesmenus`.`perfilmenuactivo`, 'auditcreacionusuarioid', `perfilesmenus`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `perfilesmenus`.`auditcreaciondispositivo`, 'auditcreacionip', `perfilesmenus`.`auditcreacionip`, 'auditcreacionfechahora', `perfilesmenus`.`auditcreacionfechahora`, 'auditedicionusuarioid', `perfilesmenus`.`auditedicionusuarioid`, 'auditediciondispositivo', `perfilesmenus`.`auditediciondispositivo`, 'auditedicionip', `perfilesmenus`.`auditedicionip`, 'auditedicionfechahora', `perfilesmenus`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `perfilesmenus`
  WHERE `perfilid` = v_perfilid AND `menuid` = v_menuid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `perfilesmenuslog` (
    `perfilid`,
    `menuid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_perfilid,
    v_menuid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `perfilesmenus`
  SET `perfilmenuactivo` = v_perfilmenuactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `perfilid` = v_perfilid AND `menuid` = v_menuid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfilesmenus_anular//
CREATE PROCEDURE sp_perfilesmenus_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_perfilid int(11);
  DECLARE v_menuid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED),
    v_menuid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.menuid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `perfilesmenus` WHERE `perfilid` = v_perfilid AND `menuid` = v_menuid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('perfilid', `perfilesmenus`.`perfilid`, 'menuid', `perfilesmenus`.`menuid`, 'perfilmenuactivo', `perfilesmenus`.`perfilmenuactivo`, 'auditcreacionusuarioid', `perfilesmenus`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `perfilesmenus`.`auditcreaciondispositivo`, 'auditcreacionip', `perfilesmenus`.`auditcreacionip`, 'auditcreacionfechahora', `perfilesmenus`.`auditcreacionfechahora`, 'auditedicionusuarioid', `perfilesmenus`.`auditedicionusuarioid`, 'auditediciondispositivo', `perfilesmenus`.`auditediciondispositivo`, 'auditedicionip', `perfilesmenus`.`auditedicionip`, 'auditedicionfechahora', `perfilesmenus`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `perfilesmenus`
  WHERE `perfilid` = v_perfilid AND `menuid` = v_menuid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `perfilesmenuslog` (
    `perfilid`,
    `menuid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_perfilid,
    v_menuid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `perfilesmenus`
  SET `perfilmenuactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `perfilid` = v_perfilid AND `menuid` = v_menuid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfilesmenus_listar//
CREATE PROCEDURE sp_perfilesmenus_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroPerfilid VARCHAR(255);
  DECLARE v_filtroMenuid VARCHAR(255);
  DECLARE v_filtroPerfilmenuactivo VARCHAR(255);
  SET v_filtroPerfilid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfilid'));
  SET v_filtroMenuid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroMenuid'));
  SET v_filtroPerfilmenuactivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfilmenuactivo')), 'null'),'');
  SELECT
    t.`perfilid`,
    t.`menuid`,
    perfiles.perfildesc AS `perfiles_perfildesc`,
    menus.menudesc AS `menus_menudesc`,
    t.`perfilmenuactivo`
  FROM `perfilesmenus` t
  LEFT JOIN `perfiles` ON t.`perfilid` = `perfiles`.`perfilid`
  LEFT JOIN `menus` ON t.`menuid` = `menus`.`menuid`
  WHERE 1=1
    AND (v_filtroPerfilid IS NULL OR v_filtroPerfilid = '' OR t.`perfilid` = v_filtroPerfilid)
    AND (v_filtroMenuid IS NULL OR v_filtroMenuid = '' OR t.`menuid` = v_filtroMenuid)
    AND (v_filtroPerfilmenuactivo IS NULL OR t.`perfilmenuactivo` = v_filtroPerfilmenuactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfilesmenus_consultar_por_perfilid//
CREATE PROCEDURE sp_perfilesmenus_consultar_por_perfilid(
  IN p_in_json JSON,
  IN p_in_usuarioid INT,
  IN p_in_dispositivo VARCHAR(50),
  IN p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroPerfilid VARCHAR(255);
  SET v_filtroPerfilid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfilid'));
  
  -- Valida si el filtro es nulo o vacío
  IF v_filtroPerfilid IS NULL OR v_filtroPerfilid = '' THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Perfil is required');
    LEAVE sp_main;
  END IF;

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
    m.`menuform`   AS menuform,
    m.`menuicono`  AS menuicono,
    m.`menuactivo` AS menuactivo
  FROM `menus` m
  INNER JOIN `perfilesmenus` pm ON pm.`menuid` = m.`menuid` AND pm.`perfilid` = v_filtroPerfilid AND pm.`perfilmenuactivo` = 1
  LEFT JOIN `menus` p  ON p.menuid  = m.menupadre     -- padre
  LEFT JOIN `menus` gp ON gp.menuid = p.menupadre     -- abuelo
  WHERE m.`menuactivo` = 1
  ORDER BY orden_path, m.menuid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
