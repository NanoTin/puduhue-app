DELIMITER //
DROP PROCEDURE IF EXISTS sp_invbodegas_insertar//
CREATE PROCEDURE sp_invbodegas_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invbodegadsc varchar(50);
  DECLARE v_erpinvbodegacod varchar(50);
  DECLARE v_fundoid int(11);
  DECLARE v_invbodactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invbodegadsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodegadsc')),
    v_erpinvbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpinvbodegacod')),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_invbodactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodactivo')) AS SIGNED);

  INSERT INTO `invbodegas` (
    `invbodegadsc`,
    `erpinvbodegacod`,
    `fundoid`,
    `invbodactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_invbodegadsc,
    v_erpinvbodegacod,
    v_fundoid,
    v_invbodactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `invbodegaslog` (
    `invbodegaid`,
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

DROP PROCEDURE IF EXISTS sp_invbodegas_editar//
CREATE PROCEDURE sp_invbodegas_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invbodegaid int(11);
  DECLARE v_invbodegadsc varchar(50);
  DECLARE v_erpinvbodegacod varchar(50);
  DECLARE v_fundoid int(11);
  DECLARE v_invbodactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invbodegaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodegaid')) AS SIGNED),
    v_invbodegadsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodegadsc')),
    v_erpinvbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpinvbodegacod')),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_invbodactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invbodegas` WHERE `invbodegaid` = v_invbodegaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invbodegaid', `invbodegas`.`invbodegaid`, 'invbodegadsc', `invbodegas`.`invbodegadsc`, 'erpinvbodegacod', `invbodegas`.`erpinvbodegacod`, 'fundoid', `invbodegas`.`fundoid`, 'invbodactivo', `invbodegas`.`invbodactivo`, 'auditcreacionusuarioid', `invbodegas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invbodegas`.`auditcreaciondispositivo`, 'auditcreacionip', `invbodegas`.`auditcreacionip`, 'auditcreacionfechahora', `invbodegas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invbodegas`.`auditedicionusuarioid`, 'auditediciondispositivo', `invbodegas`.`auditediciondispositivo`, 'auditedicionip', `invbodegas`.`auditedicionip`, 'auditedicionfechahora', `invbodegas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invbodegas`
  WHERE `invbodegaid` = v_invbodegaid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invbodegaslog` (
    `invbodegaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invbodegaid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invbodegas`
  SET `invbodegadsc` = v_invbodegadsc,
    `erpinvbodegacod` = v_erpinvbodegacod,
    `fundoid` = v_fundoid,
    `invbodactivo` = v_invbodactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `invbodegaid` = v_invbodegaid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invbodegas_anular//
CREATE PROCEDURE sp_invbodegas_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invbodegaid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invbodegaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodegaid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invbodegas` WHERE `invbodegaid` = v_invbodegaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invbodegaid', `invbodegas`.`invbodegaid`, 'invbodegadsc', `invbodegas`.`invbodegadsc`, 'erpinvbodegacod', `invbodegas`.`erpinvbodegacod`, 'fundoid', `invbodegas`.`fundoid`, 'invbodactivo', `invbodegas`.`invbodactivo`, 'auditcreacionusuarioid', `invbodegas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invbodegas`.`auditcreaciondispositivo`, 'auditcreacionip', `invbodegas`.`auditcreacionip`, 'auditcreacionfechahora', `invbodegas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invbodegas`.`auditedicionusuarioid`, 'auditediciondispositivo', `invbodegas`.`auditediciondispositivo`, 'auditedicionip', `invbodegas`.`auditedicionip`, 'auditedicionfechahora', `invbodegas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invbodegas`
  WHERE `invbodegaid` = v_invbodegaid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invbodegaslog` (
    `invbodegaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invbodegaid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invbodegas`
  SET `invbodactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `invbodegaid` = v_invbodegaid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invbodegas_listar//
CREATE PROCEDURE sp_invbodegas_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroInvbodegadsc VARCHAR(255);
  DECLARE v_filtroErpinvbodegacod VARCHAR(255);
  DECLARE v_filtroFundoid VARCHAR(255);
  DECLARE v_filtroInvbodactivo VARCHAR(255);
  SET v_filtroInvbodegadsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvbodegadsc'));
  SET v_filtroErpinvbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErpinvbodegacod'));
  SET v_filtroFundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid'));
  SET v_filtroInvbodactivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvbodactivo'));
  SELECT
    t.`invbodegaid`,
    t.`invbodegadsc`,
    t.`erpinvbodegacod`,
    t.`fundoid`,
    fundos.fundonombre AS `fundos_fundonombre`,
    t.`invbodactivo`
  FROM `invbodegas` t
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  WHERE 1=1
    AND (v_filtroInvbodegadsc IS NULL OR v_filtroInvbodegadsc = '' OR t.`invbodegadsc` LIKE CONCAT('%', v_filtroInvbodegadsc, '%'))
    AND (v_filtroErpinvbodegacod IS NULL OR v_filtroErpinvbodegacod = '' OR t.`erpinvbodegacod` LIKE CONCAT('%', v_filtroErpinvbodegacod, '%'))
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid)
    AND (v_filtroInvbodactivo IS NULL OR v_filtroInvbodactivo = '' OR t.`invbodactivo` = v_filtroInvbodactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
