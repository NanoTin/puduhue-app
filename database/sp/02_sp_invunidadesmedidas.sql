DELIMITER //
DROP PROCEDURE IF EXISTS sp_invunidadesmedidas_insertar//
CREATE PROCEDURE sp_invunidadesmedidas_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invunidmeddsc varchar(50);
  DECLARE v_erpunidmedcod varchar(50);
  DECLARE v_invunidmedactivo int(11);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invunidmeddsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmeddsc')),
    v_erpunidmedcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpunidmedcod')),
    v_invunidmedactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedactivo')) AS SIGNED);

  INSERT INTO `invunidadesmedidas` (
    `invunidmeddsc`,
    `erpunidmedcod`,
    `invunidmedactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_invunidmeddsc,
    v_erpunidmedcod,
    v_invunidmedactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `invunidadesmedidaslog` (
    `invunidmedid`,
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

DROP PROCEDURE IF EXISTS sp_invunidadesmedidas_editar//
CREATE PROCEDURE sp_invunidadesmedidas_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invunidmedid int(11);
  DECLARE v_invunidmeddsc varchar(50);
  DECLARE v_erpunidmedcod varchar(50);
  DECLARE v_invunidmedactivo int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invunidmedid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedid')) AS SIGNED),
    v_invunidmeddsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmeddsc')),
    v_erpunidmedcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpunidmedcod')),
    v_invunidmedactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invunidadesmedidas` WHERE `invunidmedid` = v_invunidmedid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invunidmedid', `invunidadesmedidas`.`invunidmedid`, 'invunidmeddsc', `invunidadesmedidas`.`invunidmeddsc`, 'erpunidmedcod', `invunidadesmedidas`.`erpunidmedcod`, 'invunidmedactivo', `invunidadesmedidas`.`invunidmedactivo`, 'auditcreacionusuarioid', `invunidadesmedidas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invunidadesmedidas`.`auditcreaciondispositivo`, 'auditcreacionip', `invunidadesmedidas`.`auditcreacionip`, 'auditcreacionfechahora', `invunidadesmedidas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invunidadesmedidas`.`auditedicionusuarioid`, 'auditediciondispositivo', `invunidadesmedidas`.`auditediciondispositivo`, 'auditedicionip', `invunidadesmedidas`.`auditedicionip`, 'auditedicionfechahora', `invunidadesmedidas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invunidadesmedidas`
  WHERE `invunidmedid` = v_invunidmedid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invunidadesmedidaslog` (
    `invunidmedid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invunidmedid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invunidadesmedidas`
  SET `invunidmeddsc` = v_invunidmeddsc,
    `erpunidmedcod` = v_erpunidmedcod,
    `invunidmedactivo` = v_invunidmedactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `invunidmedid` = v_invunidmedid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invunidadesmedidas_anular//
CREATE PROCEDURE sp_invunidadesmedidas_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invunidmedid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invunidmedid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invunidadesmedidas` WHERE `invunidmedid` = v_invunidmedid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invunidmedid', `invunidadesmedidas`.`invunidmedid`, 'invunidmeddsc', `invunidadesmedidas`.`invunidmeddsc`, 'erpunidmedcod', `invunidadesmedidas`.`erpunidmedcod`, 'invunidmedactivo', `invunidadesmedidas`.`invunidmedactivo`, 'auditcreacionusuarioid', `invunidadesmedidas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invunidadesmedidas`.`auditcreaciondispositivo`, 'auditcreacionip', `invunidadesmedidas`.`auditcreacionip`, 'auditcreacionfechahora', `invunidadesmedidas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invunidadesmedidas`.`auditedicionusuarioid`, 'auditediciondispositivo', `invunidadesmedidas`.`auditediciondispositivo`, 'auditedicionip', `invunidadesmedidas`.`auditedicionip`, 'auditedicionfechahora', `invunidadesmedidas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invunidadesmedidas`
  WHERE `invunidmedid` = v_invunidmedid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invunidadesmedidaslog` (
    `invunidmedid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invunidmedid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invunidadesmedidas`
  SET `invunidmedactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `invunidmedid` = v_invunidmedid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invunidadesmedidas_listar//
CREATE PROCEDURE sp_invunidadesmedidas_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroInvunidmeddsc VARCHAR(255);
  DECLARE v_filtroErpunidmedcod VARCHAR(255);
  DECLARE v_filtroInvunidmedactivo VARCHAR(255);
  SET v_filtroInvunidmeddsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvunidmeddsc'));
  SET v_filtroErpunidmedcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErpunidmedcod'));
  SET v_filtroInvunidmedactivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvunidmedactivo')), 'null'),'');
  SELECT
    t.`invunidmedid`,
    t.`invunidmeddsc`,
    t.`erpunidmedcod`,
    t.`invunidmedactivo`
  FROM `invunidadesmedidas` t
  WHERE 1=1
    AND (v_filtroInvunidmeddsc IS NULL OR v_filtroInvunidmeddsc = '' OR t.`invunidmeddsc` LIKE CONCAT('%', v_filtroInvunidmeddsc, '%'))
    AND (v_filtroErpunidmedcod IS NULL OR v_filtroErpunidmedcod = '' OR t.`erpunidmedcod` LIKE CONCAT('%', v_filtroErpunidmedcod, '%'))
    AND (v_filtroInvunidmedactivo IS NULL OR t.`invunidmedactivo` = v_filtroInvunidmedactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invunidadesmedidas_consultar_por_id//
CREATE PROCEDURE sp_invunidadesmedidas_consultar_por_id(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invunidmedid INT;
  SET     v_invunidmedid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedid')) AS SIGNED);
  SELECT
    t.`invunidmedid`,
    t.`invunidmeddsc`,
    t.`erpunidmedcod`,
    t.`invunidmedactivo`
  FROM `invunidadesmedidas` t
  WHERE t.`invunidmedid` = v_invunidmedid
  LIMIT 1;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
