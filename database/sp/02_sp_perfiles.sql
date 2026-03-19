DELIMITER //
DROP PROCEDURE IF EXISTS sp_perfiles_insertar//
CREATE PROCEDURE sp_perfiles_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_perfildesc varchar(100);
  DECLARE v_perfilesroot tinyint(1);
  DECLARE v_perfilesadmin tinyint(1);
  DECLARE v_perfilactivo tinyint(1);
  DECLARE v_new_perfilid int(11);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_perfildesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfildesc')),
    v_perfilesroot = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilesroot')) AS SIGNED),
    v_perfilesadmin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilesadmin')) AS SIGNED),
    v_perfilactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilactivo')) AS SIGNED);

  INSERT INTO `perfiles` (
    `perfildesc`,
    `perfilesroot`,
    `perfilesadmin`,
    `perfilactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_perfildesc,
    v_perfilesroot,
    v_perfilesadmin,
    v_perfilactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET v_new_perfilid = LAST_INSERT_ID();

  -- Generic log insert
  INSERT INTO `perfileslog` (
    `perfilid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_new_perfilid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  -- Asignar todos los menus con perfilmenuactivo = 0
  INSERT INTO `perfilesmenus` (
    `perfilid`,
    `menuid`,
    `perfilmenuactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  SELECT
    v_new_perfilid,
    m.`menuid`,
    0,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  FROM `menus` m;

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
    v_new_perfilid,
    m.`menuid`,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    JSON_OBJECT('perfilid', v_new_perfilid, 'menuid', m.`menuid`, 'perfilmenuactivo', 0),
    '{}'
  FROM `menus` m;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfiles_editar//
CREATE PROCEDURE sp_perfiles_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_perfilid int(11);
  DECLARE v_perfildesc varchar(100);
  DECLARE v_perfilesroot tinyint(1);
  DECLARE v_perfilesadmin tinyint(1);
  DECLARE v_perfilactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED),
    v_perfildesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfildesc')),
    v_perfilesroot = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilesroot')) AS SIGNED),
    v_perfilesadmin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilesadmin')) AS SIGNED),
    v_perfilactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `perfiles` WHERE `perfilid` = v_perfilid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('perfilid', `perfiles`.`perfilid`, 'perfildesc', `perfiles`.`perfildesc`, 'perfilesroot', `perfiles`.`perfilesroot`, 'perfilesadmin', `perfiles`.`perfilesadmin`, 'perfilactivo', `perfiles`.`perfilactivo`, 'auditcreacionusuarioid', `perfiles`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `perfiles`.`auditcreaciondispositivo`, 'auditcreacionip', `perfiles`.`auditcreacionip`, 'auditcreacionfechahora', `perfiles`.`auditcreacionfechahora`, 'auditedicionusuarioid', `perfiles`.`auditedicionusuarioid`, 'auditediciondispositivo', `perfiles`.`auditediciondispositivo`, 'auditedicionip', `perfiles`.`auditedicionip`, 'auditedicionfechahora', `perfiles`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `perfiles`
  WHERE `perfilid` = v_perfilid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `perfileslog` (
    `perfilid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_perfilid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `perfiles`
  SET `perfildesc` = v_perfildesc,
    `perfilesroot` = v_perfilesroot,
    `perfilesadmin` = v_perfilesadmin,
    `perfilactivo` = v_perfilactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `perfilid` = v_perfilid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfiles_anular//
CREATE PROCEDURE sp_perfiles_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_perfilid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `perfiles` WHERE `perfilid` = v_perfilid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('perfilid', `perfiles`.`perfilid`, 'perfildesc', `perfiles`.`perfildesc`, 'perfilesroot', `perfiles`.`perfilesroot`, 'perfilesadmin', `perfiles`.`perfilesadmin`, 'perfilactivo', `perfiles`.`perfilactivo`, 'auditcreacionusuarioid', `perfiles`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `perfiles`.`auditcreaciondispositivo`, 'auditcreacionip', `perfiles`.`auditcreacionip`, 'auditcreacionfechahora', `perfiles`.`auditcreacionfechahora`, 'auditedicionusuarioid', `perfiles`.`auditedicionusuarioid`, 'auditediciondispositivo', `perfiles`.`auditediciondispositivo`, 'auditedicionip', `perfiles`.`auditedicionip`, 'auditedicionfechahora', `perfiles`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `perfiles`
  WHERE `perfilid` = v_perfilid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `perfileslog` (
    `perfilid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_perfilid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `perfiles`
  SET `perfilactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `perfilid` = v_perfilid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfiles_listar//
CREATE PROCEDURE sp_perfiles_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroPerfildesc VARCHAR(255);
  DECLARE v_filtroPerfilesroot VARCHAR(255);
  DECLARE v_filtroPerfilesadmin VARCHAR(255);
  DECLARE v_filtroPerfilactivo VARCHAR(255);
  SET v_filtroPerfildesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfildesc'));
  SET v_filtroPerfilesroot = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfilesroot'));
  SET v_filtroPerfilesadmin = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfilesadmin'));
  SET v_filtroPerfilactivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfilactivo'));
  SELECT
    t.`perfilid`,
    t.`perfildesc`,
    t.`perfilesroot`,
    t.`perfilesadmin`,
    t.`perfilactivo`
  FROM `perfiles` t
  WHERE 1=1
    AND (v_filtroPerfildesc IS NULL OR v_filtroPerfildesc = '' OR t.`perfildesc` LIKE CONCAT('%', v_filtroPerfildesc, '%'))
    AND (v_filtroPerfilesroot IS NULL OR v_filtroPerfilesroot = '' OR t.`perfilesroot` = v_filtroPerfilesroot)
    AND (v_filtroPerfilesadmin IS NULL OR v_filtroPerfilesadmin = '' OR t.`perfilesadmin` = v_filtroPerfilesadmin)
    AND (v_filtroPerfilactivo IS NULL OR v_filtroPerfilactivo = '' OR t.`perfilactivo` = v_filtroPerfilactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_perfiles_consultar_por_id//
CREATE PROCEDURE sp_perfiles_consultar_por_id(
  IN p_in_json JSON,
  IN p_in_usuarioid INT,
  IN p_in_dispositivo VARCHAR(50),
  IN p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_perfilid int(11);
  SET     v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED);
  IF NOT EXISTS (SELECT 1 FROM `perfiles` WHERE `perfilid` = v_perfilid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;
  IF v_perfilid IS NULL THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'perfilid is required');
    LEAVE sp_main;
  END IF;
  
  SELECT
    t.`perfilid`,
    t.`perfildesc`,
    t.`perfilesroot`,
    t.`perfilesadmin`,
    t.`perfilactivo`
  FROM `perfiles` t
  WHERE t.`perfilid` = v_perfilid;
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
