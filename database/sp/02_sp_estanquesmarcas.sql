DELIMITER //
DROP PROCEDURE IF EXISTS sp_estanquesmarcas_insertar//
CREATE PROCEDURE sp_estanquesmarcas_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_estanquemarcadsc varchar(50);
  DECLARE v_estanquemodelodsc varchar(50);
  DECLARE v_estanquecapacidadlts int(4);
  DECLARE v_estanquemarcamodelo varchar(100);
  DECLARE v_estanquereglaminmm int(4);
  DECLARE v_estanquereglamaxmm int(4);
  DECLARE v_estanqueactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_estanquemarcadsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcadsc')),
    v_estanquemodelodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemodelodsc')),
    v_estanquecapacidadlts = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquecapacidadlts')) AS SIGNED),
    v_estanquemarcamodelo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcamodelo')),
    v_estanquereglaminmm = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquereglaminmm')) AS SIGNED),
    v_estanquereglamaxmm = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquereglamaxmm')) AS SIGNED),
    v_estanqueactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanqueactivo')) AS SIGNED);

  INSERT INTO `estanquesmarcas` (
    `estanquemarcadsc`,
    `estanquemodelodsc`,
    `estanquecapacidadlts`,
    `estanquemarcamodelo`,
    `estanquereglaminmm`,
    `estanquereglamaxmm`,
    `estanqueactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`,
    `auditcreacionfechahora`
  )
  VALUES (
    v_estanquemarcadsc,
    v_estanquemodelodsc,
    v_estanquecapacidadlts,
    v_estanquemarcamodelo,
    v_estanquereglaminmm,
    v_estanquereglamaxmm,
    v_estanqueactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    NOW()
  );

  -- Generic log insert
  INSERT INTO `estanquesmarcaslog` (
    `estanquemarcaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logfechahora`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    LAST_INSERT_ID(),
    p_in_usuarioid,
    NULL,
    NULL,
    p_in_dispositivo,
    p_in_ip,
    NOW(),
    'INS',
    p_in_json,
    NULL
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_estanquesmarcas_editar//
CREATE PROCEDURE sp_estanquesmarcas_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_estanquemarcaid int(11);
  DECLARE v_estanquemarcadsc varchar(50);
  DECLARE v_estanquemodelodsc varchar(50);
  DECLARE v_estanquecapacidadlts int(4);
  DECLARE v_estanquemarcamodelo varchar(100);
  DECLARE v_estanquereglaminmm int(4);
  DECLARE v_estanquereglamaxmm int(4);
  DECLARE v_estanqueactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_estanquemarcaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcaid')) AS SIGNED),
    v_estanquemarcadsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcadsc')),
    v_estanquemodelodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemodelodsc')),
    v_estanquecapacidadlts = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquecapacidadlts')) AS SIGNED),
    v_estanquemarcamodelo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcamodelo')),
    v_estanquereglaminmm = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquereglaminmm')) AS SIGNED),
    v_estanquereglamaxmm = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquereglamaxmm')) AS SIGNED),
    v_estanqueactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanqueactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `estanquesmarcas` WHERE `estanquemarcaid` = v_estanquemarcaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('estanquemarcaid', `estanquesmarcas`.`estanquemarcaid`, 'estanquemarcadsc', `estanquesmarcas`.`estanquemarcadsc`, 'estanquemodelodsc', `estanquesmarcas`.`estanquemodelodsc`, 'estanquecapacidadlts', `estanquesmarcas`.`estanquecapacidadlts`, 'estanquemarcamodelo', `estanquesmarcas`.`estanquemarcamodelo`, 'estanquereglaminmm', `estanquesmarcas`.`estanquereglaminmm`, 'estanquereglamaxmm', `estanquesmarcas`.`estanquereglamaxmm`, 'estanqueactivo', `estanquesmarcas`.`estanqueactivo`, 'auditcreacionusuarioid', `estanquesmarcas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `estanquesmarcas`.`auditcreaciondispositivo`, 'auditcreacionip', `estanquesmarcas`.`auditcreacionip`, 'auditcreacionfechahora', `estanquesmarcas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `estanquesmarcas`.`auditedicionusuarioid`, 'auditediciondispositivo', `estanquesmarcas`.`auditediciondispositivo`, 'auditedicionip', `estanquesmarcas`.`auditedicionip`, 'auditedicionfechahora', `estanquesmarcas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `estanquesmarcas`
  WHERE `estanquemarcaid` = v_estanquemarcaid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `estanquesmarcaslog` (
    `estanquemarcaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logfechahora`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_estanquemarcaid,
    p_in_usuarioid,
    NULL,
    NULL,
    p_in_dispositivo,
    p_in_ip,
    NOW(),
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `estanquesmarcas`
  SET `estanquemarcadsc` = v_estanquemarcadsc,
    `estanquemodelodsc` = v_estanquemodelodsc,
    `estanquecapacidadlts` = v_estanquecapacidadlts,
    `estanquemarcamodelo` = v_estanquemarcamodelo,
    `estanquereglaminmm` = v_estanquereglaminmm,
    `estanquereglamaxmm` = v_estanquereglamaxmm,
    `estanqueactivo` = v_estanqueactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `estanquemarcaid` = v_estanquemarcaid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_estanquesmarcas_anular//
CREATE PROCEDURE sp_estanquesmarcas_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_estanquemarcaid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_estanquemarcaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcaid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `estanquesmarcas` WHERE `estanquemarcaid` = v_estanquemarcaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('estanquemarcaid', `estanquesmarcas`.`estanquemarcaid`, 'estanquemarcadsc', `estanquesmarcas`.`estanquemarcadsc`, 'estanquemodelodsc', `estanquesmarcas`.`estanquemodelodsc`, 'estanquecapacidadlts', `estanquesmarcas`.`estanquecapacidadlts`, 'estanquemarcamodelo', `estanquesmarcas`.`estanquemarcamodelo`, 'estanquereglaminmm', `estanquesmarcas`.`estanquereglaminmm`, 'estanquereglamaxmm', `estanquesmarcas`.`estanquereglamaxmm`, 'estanqueactivo', `estanquesmarcas`.`estanqueactivo`, 'auditcreacionusuarioid', `estanquesmarcas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `estanquesmarcas`.`auditcreaciondispositivo`, 'auditcreacionip', `estanquesmarcas`.`auditcreacionip`, 'auditcreacionfechahora', `estanquesmarcas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `estanquesmarcas`.`auditedicionusuarioid`, 'auditediciondispositivo', `estanquesmarcas`.`auditediciondispositivo`, 'auditedicionip', `estanquesmarcas`.`auditedicionip`, 'auditedicionfechahora', `estanquesmarcas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `estanquesmarcas`
  WHERE `estanquemarcaid` = v_estanquemarcaid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `estanquesmarcaslog` (
    `estanquemarcaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logfechahora`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_estanquemarcaid,
    p_in_usuarioid,
    NULL,
    NULL,
    p_in_dispositivo,
    p_in_ip,
    NOW(),
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `estanquesmarcas`
  SET `estanqueactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `estanquemarcaid` = v_estanquemarcaid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_estanquesmarcas_listar//
CREATE PROCEDURE sp_estanquesmarcas_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroEstanquemarcamodelo VARCHAR(255);
  DECLARE v_filtroEstanqueactivo VARCHAR(255);
  SET v_filtroEstanquemarcamodelo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEstanquemarcamodelo'));
  SET v_filtroEstanqueactivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEstanqueactivo'));
  SELECT
    t.`estanquemarcaid`,
    t.`estanquemarcadsc`,
    t.`estanquemodelodsc`,
    t.`estanquecapacidadlts`,
    t.`estanquemarcamodelo`,
    t.`estanquereglaminmm`,
    t.`estanquereglamaxmm`,
    t.`estanqueactivo`
  FROM `estanquesmarcas` t
  WHERE 1=1
    AND (v_filtroEstanquemarcamodelo IS NULL OR v_filtroEstanquemarcamodelo = '' OR t.`estanquemarcamodelo` LIKE CONCAT('%', v_filtroEstanquemarcamodelo, '%'))
    AND (v_filtroEstanqueactivo IS NULL OR v_filtroEstanqueactivo = '' OR t.`estanqueactivo` = v_filtroEstanqueactivo);
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
