DELIMITER //
DROP PROCEDURE IF EXISTS sp_usuarios_insertar//
CREATE PROCEDURE sp_usuarios_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuariocod varchar(12);
  DECLARE v_usuariorut varchar(12);
  DECLARE v_usuarionombre varchar(100);
  DECLARE v_usuariopwdhash varchar(255);
  DECLARE v_usuarioemail varchar(100);
  DECLARE v_usuariocelular varchar(12);
  DECLARE v_perfilid int(11);
  DECLARE v_usuarioesadmin tinyint(1);
  DECLARE v_usuariobloqueado tinyint(1);
  DECLARE v_usuariobloqueadodesc varchar(100);
  DECLARE v_usuarioapikeyhash varchar(255);
  DECLARE v_usuarioapikeyactiva tinyint(1);
  DECLARE v_usuarioapikeyfechagen datetime;
  DECLARE v_usuarioapikeyultuso datetime;
  DECLARE v_usuarioapikeyipultuso varchar(50);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  IF EXISTS(SELECT 1 FROM `usuarios` WHERE `usuariorut` = v_usuariorut) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Usuario ya existe.');
    LEAVE sp_main;
  END IF;

  SET     v_usuariocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariocod')),
    v_usuariorut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariorut')),
    v_usuarionombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarionombre')),
    v_usuariopwdhash = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariopwdhash')),
    v_usuarioemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioemail')),
    v_usuariocelular = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariocelular')),
    v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED),
    v_usuarioesadmin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioesadmin')) AS SIGNED),
    v_usuariobloqueado = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariobloqueado')) AS SIGNED),
    v_usuariobloqueadodesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariobloqueadodesc')),
    v_usuarioapikeyhash = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyhash')),
    v_usuarioapikeyactiva = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyactiva')) AS SIGNED),
    v_usuarioapikeyfechagen = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyfechagen')) AS DATETIME),
    v_usuarioapikeyultuso = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyultuso')) AS DATETIME),
    v_usuarioapikeyipultuso = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyipultuso'));

  IF EXISTS (SELECT 1 FROM `usuarios` WHERE `usuariorut` = v_usuariorut) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record with same usuariorut already exists');
    LEAVE sp_main;
  END IF;

  INSERT INTO `usuarios` (
    `usuariocod`,
    `usuariorut`,
    `usuarionombre`,
    `usuariopwdhash`,
    `usuarioemail`,
    `usuariocelular`,
    `perfilid`,
    `empresaiddefault`,
    `usuarioesroot`,
    `usuarioesadmin`,
    `usuariobloqueado`,
    `usuariobloqueadodesc`,
    `usuarioapikeyhash`,
    `usuarioapikeyactiva`,
    `usuarioapikeyfechagen`,
    `usuarioapikeyultuso`,
    `usuarioapikeyipultuso`,
    `usuarioactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_usuariocod,
    v_usuariorut,
    v_usuarionombre,
    v_usuariopwdhash,
    v_usuarioemail,
    v_usuariocelular,
    v_perfilid,
    NULL,
    0,
    v_usuarioesadmin,
    v_usuariobloqueado,
    v_usuariobloqueadodesc,
    v_usuarioapikeyhash,
    v_usuarioapikeyactiva,
    v_usuarioapikeyfechagen,
    v_usuarioapikeyultuso,
    v_usuarioapikeyipultuso,
    1,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `usuarioslog` (
    `usuarioid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    LAST_INSERT_ID(),
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuarios_editar//
CREATE PROCEDURE sp_usuarios_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid int(11);
  DECLARE v_usuariocod varchar(12);
  DECLARE v_usuariorut varchar(12);
  DECLARE v_usuarionombre varchar(100);
  DECLARE v_usuariopwdhash varchar(255);
  DECLARE v_usuarioemail varchar(100);
  DECLARE v_usuariocelular varchar(12);
  DECLARE v_perfilid int(11);
  DECLARE v_usuarioesroot tinyint(1);
  DECLARE v_usuarioesadmin tinyint(1);
  DECLARE v_usuariobloqueado tinyint(1);
  DECLARE v_usuariobloqueadodesc varchar(100);
  DECLARE v_usuarioapikeyhash varchar(255);
  DECLARE v_usuarioapikeyactiva tinyint(1);
  DECLARE v_usuarioapikeyfechagen datetime;
  DECLARE v_usuarioapikeyultuso datetime;
  DECLARE v_usuarioapikeyipultuso varchar(50);
  DECLARE v_usuarioactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_usuarioid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')) AS SIGNED),
    v_usuariocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariocod')),
    v_usuariorut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariorut')),
    v_usuarionombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarionombre')),
    v_usuariopwdhash = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariopwdhash')),
    v_usuarioemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioemail')),
    v_usuariocelular = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariocelular')),
    v_perfilid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.perfilid')) AS SIGNED),
    v_usuarioesroot = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioesroot')) AS SIGNED),
    v_usuarioesadmin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioesadmin')) AS SIGNED),
    v_usuariobloqueado = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariobloqueado')) AS SIGNED),
    v_usuariobloqueadodesc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariobloqueadodesc')),
    v_usuarioapikeyhash = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyhash')),
    v_usuarioapikeyactiva = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyactiva')) AS SIGNED),
    v_usuarioapikeyfechagen = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyfechagen')) AS DATETIME),
    v_usuarioapikeyultuso = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyultuso')) AS DATETIME),
    v_usuarioapikeyipultuso = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioapikeyipultuso')),
    v_usuarioactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `usuarioid` = v_usuarioid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('usuarioid', `usuarios`.`usuarioid`, 'usuariocod', `usuarios`.`usuariocod`, 'usuariorut', `usuarios`.`usuariorut`, 'usuarionombre', `usuarios`.`usuarionombre`, 'usuariopwdhash', `usuarios`.`usuariopwdhash`, 'usuarioemail', `usuarios`.`usuarioemail`, 'usuariocelular', `usuarios`.`usuariocelular`, 'perfilid', `usuarios`.`perfilid`, 'empresaiddefault', `usuarios`.`empresaiddefault`, 'usuarioesroot', `usuarios`.`usuarioesroot`, 'usuarioesadmin', `usuarios`.`usuarioesadmin`, 'usuariobloqueado', `usuarios`.`usuariobloqueado`, 'usuariobloqueadodesc', `usuarios`.`usuariobloqueadodesc`, 'usuarioapikeyhash', `usuarios`.`usuarioapikeyhash`, 'usuarioapikeyactiva', `usuarios`.`usuarioapikeyactiva`, 'usuarioapikeyfechagen', `usuarios`.`usuarioapikeyfechagen`, 'usuarioapikeyultuso', `usuarios`.`usuarioapikeyultuso`, 'usuarioapikeyipultuso', `usuarios`.`usuarioapikeyipultuso`, 'usuarioactivo', `usuarios`.`usuarioactivo`, 'auditcreacionusuarioid', `usuarios`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `usuarios`.`auditcreaciondispositivo`, 'auditcreacionip', `usuarios`.`auditcreacionip`, 'auditcreacionfechahora', `usuarios`.`auditcreacionfechahora`, 'auditedicionusuarioid', `usuarios`.`auditedicionusuarioid`, 'auditediciondispositivo', `usuarios`.`auditediciondispositivo`, 'auditedicionip', `usuarios`.`auditedicionip`, 'auditedicionfechahora', `usuarios`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `usuarios`
  WHERE `usuarioid` = v_usuarioid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `usuarioslog` (
    `usuarioid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_usuarioid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `usuarios`
  SET `usuariocod` = v_usuariocod,
    `usuariorut` = v_usuariorut,
    `usuarionombre` = v_usuarionombre,
    `usuariopwdhash` = v_usuariopwdhash,
    `usuarioemail` = v_usuarioemail,
    `usuariocelular` = v_usuariocelular,
    `perfilid` = v_perfilid,
    `usuarioesroot` = v_usuarioesroot,
    `usuarioesadmin` = v_usuarioesadmin,
    `usuariobloqueado` = v_usuariobloqueado,
    `usuariobloqueadodesc` = v_usuariobloqueadodesc,
    `usuarioapikeyhash` = v_usuarioapikeyhash,
    `usuarioapikeyactiva` = v_usuarioapikeyactiva,
    `usuarioapikeyfechagen` = v_usuarioapikeyfechagen,
    `usuarioapikeyultuso` = v_usuarioapikeyultuso,
    `usuarioapikeyipultuso` = v_usuarioapikeyipultuso,
    `usuarioactivo` = v_usuarioactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `usuarioid` = v_usuarioid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuarios_anular//
CREATE PROCEDURE sp_usuarios_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_usuarioid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `usuarioid` = v_usuarioid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('usuarioid', `usuarios`.`usuarioid`, 'usuariocod', `usuarios`.`usuariocod`, 'usuariorut', `usuarios`.`usuariorut`, 'usuarionombre', `usuarios`.`usuarionombre`, 'usuariopwdhash', `usuarios`.`usuariopwdhash`, 'usuarioemail', `usuarios`.`usuarioemail`, 'usuariocelular', `usuarios`.`usuariocelular`, 'perfilid', `usuarios`.`perfilid`, 'empresaiddefault', `usuarios`.`empresaiddefault`, 'usuarioesroot', `usuarios`.`usuarioesroot`, 'usuarioesadmin', `usuarios`.`usuarioesadmin`, 'usuariobloqueado', `usuarios`.`usuariobloqueado`, 'usuariobloqueadodesc', `usuarios`.`usuariobloqueadodesc`, 'usuarioapikeyhash', `usuarios`.`usuarioapikeyhash`, 'usuarioapikeyactiva', `usuarios`.`usuarioapikeyactiva`, 'usuarioapikeyfechagen', `usuarios`.`usuarioapikeyfechagen`, 'usuarioapikeyultuso', `usuarios`.`usuarioapikeyultuso`, 'usuarioapikeyipultuso', `usuarios`.`usuarioapikeyipultuso`, 'usuarioactivo', `usuarios`.`usuarioactivo`, 'auditcreacionusuarioid', `usuarios`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `usuarios`.`auditcreaciondispositivo`, 'auditcreacionip', `usuarios`.`auditcreacionip`, 'auditcreacionfechahora', `usuarios`.`auditcreacionfechahora`, 'auditedicionusuarioid', `usuarios`.`auditedicionusuarioid`, 'auditediciondispositivo', `usuarios`.`auditediciondispositivo`, 'auditedicionip', `usuarios`.`auditedicionip`, 'auditedicionfechahora', `usuarios`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `usuarios`
  WHERE `usuarioid` = v_usuarioid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `usuarioslog` (
    `usuarioid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_usuarioid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `usuarios`
  SET `usuarioactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `usuarioid` = v_usuarioid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuarios_cambio_clave//
CREATE PROCEDURE sp_usuarios_cambio_clave(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid int(11);
  DECLARE v_usuariopwdhash varchar(255);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET v_usuarioid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')) AS SIGNED),
      v_usuariopwdhash = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariopwdhash'));

  IF v_usuarioid IS NULL OR v_usuarioid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Usuario invalido');
    LEAVE sp_main;
  END IF;

  IF v_usuariopwdhash IS NULL OR v_usuariopwdhash = '' THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Password invalida');
    LEAVE sp_main;
  END IF;

  IF p_in_usuarioid IS NULL OR p_in_usuarioid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 401, 'message', 'Sesion invalida');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `usuarioid` = p_in_usuarioid AND `usuarioactivo` = 1) THEN
    SET p_out_json = JSON_OBJECT('status', 403, 'message', 'Usuario no autorizado');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `usuarioid` = v_usuarioid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('usuarioid', `usuarios`.`usuarioid`, 'usuariocod', `usuarios`.`usuariocod`, 'usuariorut', `usuarios`.`usuariorut`, 'usuarionombre', `usuarios`.`usuarionombre`, 'usuariopwdhash', `usuarios`.`usuariopwdhash`, 'usuarioemail', `usuarios`.`usuarioemail`, 'usuariocelular', `usuarios`.`usuariocelular`, 'perfilid', `usuarios`.`perfilid`, 'empresaiddefault', `usuarios`.`empresaiddefault`, 'usuarioesroot', `usuarios`.`usuarioesroot`, 'usuarioesadmin', `usuarios`.`usuarioesadmin`, 'usuariobloqueado', `usuarios`.`usuariobloqueado`, 'usuariobloqueadodesc', `usuarios`.`usuariobloqueadodesc`, 'usuarioapikeyhash', `usuarios`.`usuarioapikeyhash`, 'usuarioapikeyactiva', `usuarios`.`usuarioapikeyactiva`, 'usuarioapikeyfechagen', `usuarios`.`usuarioapikeyfechagen`, 'usuarioapikeyultuso', `usuarios`.`usuarioapikeyultuso`, 'usuarioapikeyipultuso', `usuarios`.`usuarioapikeyipultuso`, 'usuarioactivo', `usuarios`.`usuarioactivo`, 'auditcreacionusuarioid', `usuarios`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `usuarios`.`auditcreaciondispositivo`, 'auditcreacionip', `usuarios`.`auditcreacionip`, 'auditcreacionfechahora', `usuarios`.`auditcreacionfechahora`, 'auditedicionusuarioid', `usuarios`.`auditedicionusuarioid`, 'auditediciondispositivo', `usuarios`.`auditediciondispositivo`, 'auditedicionip', `usuarios`.`auditedicionip`, 'auditedicionfechahora', `usuarios`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `usuarios`
  WHERE `usuarioid` = v_usuarioid
  LIMIT 1;

  INSERT INTO `usuarioslog` (
    `usuarioid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_usuarioid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `usuarios`
  SET `usuariopwdhash` = v_usuariopwdhash,
    `usuarioultimopwdcambio` = NOW(),
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `usuarioid` = v_usuarioid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuarios_listar//
CREATE PROCEDURE sp_usuarios_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroUsuariorut VARCHAR(255);
  DECLARE v_filtroUsuarionombre VARCHAR(255);
  DECLARE v_filtroUsuarioemail VARCHAR(255);
  DECLARE v_filtroPerfilid VARCHAR(255);
  DECLARE v_filtroUsuarioesadmin VARCHAR(255);
  DECLARE v_filtroUsuariobloqueado VARCHAR(255);
  DECLARE v_filtroUsuarioactivo VARCHAR(255);

  SET v_filtroUsuariorut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuariorut'));
  SET v_filtroUsuarionombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarionombre'));
  SET v_filtroUsuarioemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioemail'));
  SET v_filtroPerfilid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPerfilid'));
  SET v_filtroUsuarioesadmin = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioesadmin'));
  SET v_filtroUsuariobloqueado = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuariobloqueado'));
  SET v_filtroUsuarioactivo = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioactivo')), 'null');
  
  SELECT
    t.`usuarioid`,
    t.`usuariocod`,
    t.`usuariorut`,
    t.`usuarionombre`,
    t.`usuarioemail`,
    t.`usuariocelular`,
    t.`perfilid`,
    perfiles.perfildesc AS `perfildesc`,
    t.`empresaiddefault`,
    empresas.razonsocial AS `empresadefault`,
    t.`usuarioesadmin`,
    t.`usuariobloqueado`,
    t.`usuariobloqueadodesc`,
    t.`usuarioapikeyactiva`,
    t.`usuarioapikeyfechagen`,
    t.`usuarioapikeyultuso`,
    t.`usuarioapikeyipultuso`,
    t.`usuarioactivo`
  FROM `usuarios` t
  LEFT JOIN `perfiles` ON t.`perfilid` = `perfiles`.`perfilid`
  LEFT JOIN `empresas` ON t.`empresaiddefault` = `empresas`.`empresaid`
  WHERE 1=1
    AND (v_filtroUsuariorut IS NULL OR v_filtroUsuariorut = '' OR t.`usuariorut` LIKE CONCAT('%', v_filtroUsuariorut, '%'))
    AND (v_filtroUsuarionombre IS NULL OR v_filtroUsuarionombre = '' OR t.`usuarionombre` LIKE CONCAT('%', v_filtroUsuarionombre, '%'))
    AND (v_filtroUsuarioemail IS NULL OR v_filtroUsuarioemail = '' OR t.`usuarioemail` LIKE CONCAT('%', v_filtroUsuarioemail, '%'))
    AND (v_filtroPerfilid IS NULL OR v_filtroPerfilid = '' OR t.`perfilid` = v_filtroPerfilid)
    AND (v_filtroUsuarioesadmin IS NULL OR v_filtroUsuarioesadmin = '' OR t.`usuarioesadmin` = v_filtroUsuarioesadmin)
    AND (v_filtroUsuariobloqueado IS NULL OR v_filtroUsuariobloqueado = '' OR t.`usuariobloqueado` = v_filtroUsuariobloqueado)
    AND (v_filtroUsuarioactivo IS NULL OR v_filtroUsuarioactivo = '' OR t.`usuarioactivo` = v_filtroUsuarioactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
