DELIMITER //
DROP PROCEDURE IF EXISTS sp_invcateganimal_insertar//
CREATE PROCEDURE sp_invcateganimal_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invcateganimaldsc varchar(50);
  DECLARE v_erpinvcateganimalcod varchar(50);
  DECLARE v_invcateganimalkilosxcab float;
  DECLARE v_invcateganimalactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invcateganimaldsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimaldsc')),
    v_erpinvcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpinvcateganimalcod')),
    v_invcateganimalkilosxcab = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalkilosxcab')) AS DECIMAL(20,6)),
    v_invcateganimalactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalactivo')) AS SIGNED);

  INSERT INTO `invcateganimal` (
    `invcateganimaldsc`,
    `erpinvcateganimalcod`,
    `invcateganimalkilosxcab`,
    `invcateganimalactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_invcateganimaldsc,
    v_erpinvcateganimalcod,
    v_invcateganimalkilosxcab,
    v_invcateganimalactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `invcateganimallog` (
    `invcateganimalid`,
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

DROP PROCEDURE IF EXISTS sp_invcateganimal_editar//
CREATE PROCEDURE sp_invcateganimal_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invcateganimalid int(11);
  DECLARE v_invcateganimaldsc varchar(50);
  DECLARE v_erpinvcateganimalcod varchar(50);
  DECLARE v_invcateganimalkilosxcab float;
  DECLARE v_invcateganimalactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invcateganimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalid')) AS SIGNED),
    v_invcateganimaldsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimaldsc')),
    v_erpinvcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpinvcateganimalcod')),
    v_invcateganimalkilosxcab = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalkilosxcab')) AS DECIMAL(20,6)),
    v_invcateganimalactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invcateganimal` WHERE `invcateganimalid` = v_invcateganimalid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invcateganimalid', `invcateganimal`.`invcateganimalid`, 'invcateganimaldsc', `invcateganimal`.`invcateganimaldsc`, 'erpinvcateganimalcod', `invcateganimal`.`erpinvcateganimalcod`, 'invcateganimalkilosxcab', `invcateganimal`.`invcateganimalkilosxcab`, 'invcateganimalactivo', `invcateganimal`.`invcateganimalactivo`, 'auditcreacionusuarioid', `invcateganimal`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invcateganimal`.`auditcreaciondispositivo`, 'auditcreacionip', `invcateganimal`.`auditcreacionip`, 'auditcreacionfechahora', `invcateganimal`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invcateganimal`.`auditedicionusuarioid`, 'auditediciondispositivo', `invcateganimal`.`auditediciondispositivo`, 'auditedicionip', `invcateganimal`.`auditedicionip`, 'auditedicionfechahora', `invcateganimal`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invcateganimal`
  WHERE `invcateganimalid` = v_invcateganimalid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invcateganimallog` (
    `invcateganimalid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invcateganimalid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invcateganimal`
  SET `invcateganimaldsc` = v_invcateganimaldsc,
    `erpinvcateganimalcod` = v_erpinvcateganimalcod,
    `invcateganimalkilosxcab` = v_invcateganimalkilosxcab,
    `invcateganimalactivo` = v_invcateganimalactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `invcateganimalid` = v_invcateganimalid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invcateganimal_anular//
CREATE PROCEDURE sp_invcateganimal_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invcateganimalid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invcateganimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invcateganimal` WHERE `invcateganimalid` = v_invcateganimalid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invcateganimalid', `invcateganimal`.`invcateganimalid`, 'invcateganimaldsc', `invcateganimal`.`invcateganimaldsc`, 'erpinvcateganimalcod', `invcateganimal`.`erpinvcateganimalcod`, 'invcateganimalkilosxcab', `invcateganimal`.`invcateganimalkilosxcab`, 'invcateganimalactivo', `invcateganimal`.`invcateganimalactivo`, 'auditcreacionusuarioid', `invcateganimal`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invcateganimal`.`auditcreaciondispositivo`, 'auditcreacionip', `invcateganimal`.`auditcreacionip`, 'auditcreacionfechahora', `invcateganimal`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invcateganimal`.`auditedicionusuarioid`, 'auditediciondispositivo', `invcateganimal`.`auditediciondispositivo`, 'auditedicionip', `invcateganimal`.`auditedicionip`, 'auditedicionfechahora', `invcateganimal`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invcateganimal`
  WHERE `invcateganimalid` = v_invcateganimalid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invcateganimallog` (
    `invcateganimalid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invcateganimalid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invcateganimal`
  SET `invcateganimalactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `invcateganimalid` = v_invcateganimalid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invcateganimal_listar//
CREATE PROCEDURE sp_invcateganimal_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroInvcateganimaldsc VARCHAR(255);
  DECLARE v_filtroErpinvcateganimalcod VARCHAR(255);
  DECLARE v_filtroInvcateganimalactivo VARCHAR(255);
  SET v_filtroInvcateganimaldsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvcateganimaldsc'));
  SET v_filtroErpinvcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErpinvcateganimalcod'));
  SET v_filtroInvcateganimalactivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvcateganimalactivo'));
  SELECT
    t.`invcateganimalid`,
    t.`invcateganimaldsc`,
    t.`erpinvcateganimalcod`,
    t.`invcateganimalkilosxcab`,
    t.`invcateganimalactivo`
  FROM `invcateganimal` t
  WHERE 1=1
    AND (v_filtroInvcateganimaldsc IS NULL OR v_filtroInvcateganimaldsc = '' OR t.`invcateganimaldsc` LIKE CONCAT('%', v_filtroInvcateganimaldsc, '%'))
    AND (v_filtroErpinvcateganimalcod IS NULL OR v_filtroErpinvcateganimalcod = '' OR t.`erpinvcateganimalcod` LIKE CONCAT('%', v_filtroErpinvcateganimalcod, '%'))
    AND (v_filtroInvcateganimalactivo IS NULL OR v_filtroInvcateganimalactivo = '' OR t.`invcateganimalactivo` = v_filtroInvcateganimalactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
