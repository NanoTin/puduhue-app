DELIMITER //
DROP PROCEDURE IF EXISTS sp_invitems_insertar//
CREATE PROCEDURE sp_invitems_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invitemdsc varchar(50);
  DECLARE v_invunidmedid int(11);
  DECLARE v_erpinvitemcod varchar(50);
  DECLARE v_invitemleche tinyint(1);
  DECLARE v_invitemstockeable tinyint(1);
  DECLARE v_invitemactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invitemdsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemdsc')),
    v_invunidmedid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedid')) AS SIGNED),
    v_erpinvitemcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpinvitemcod')),
    v_invitemleche = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemleche')) AS SIGNED),
    v_invitemstockeable = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemstockeable')) AS SIGNED),
    v_invitemactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemactivo')) AS SIGNED);

  INSERT INTO `invitems` (
    `invitemdsc`,
    `invunidmedid`,
    `erpinvitemcod`,
    `invitemleche`,
    `invitemstockeable`,
    `invitemactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_invitemdsc,
    v_invunidmedid,
    v_erpinvitemcod,
    v_invitemleche,
    v_invitemstockeable,
    v_invitemactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `invitemslog` (
    `invitemid`,
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

DROP PROCEDURE IF EXISTS sp_invitems_editar//
CREATE PROCEDURE sp_invitems_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invitemid int(11);
  DECLARE v_invitemdsc varchar(50);
  DECLARE v_invunidmedid int(11);
  DECLARE v_erpinvitemcod varchar(50);
  DECLARE v_invitemleche tinyint(1);
  DECLARE v_invitemstockeable tinyint(1);
  DECLARE v_invitemactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invitemid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemid')) AS SIGNED),
    v_invitemdsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemdsc')),
    v_invunidmedid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedid')) AS SIGNED),
    v_erpinvitemcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpinvitemcod')),
    v_invitemleche = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemleche')) AS SIGNED),
    v_invitemstockeable = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemstockeable')) AS SIGNED),
    v_invitemactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invitems` WHERE `invitemid` = v_invitemid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invitemid', `invitems`.`invitemid`, 'invitemdsc', `invitems`.`invitemdsc`, 'invunidmedid', `invitems`.`invunidmedid`, 'erpinvitemcod', `invitems`.`erpinvitemcod`, 'invitemleche', `invitems`.`invitemleche`, 'invitemstockeable', `invitems`.`invitemstockeable`, 'invitemactivo', `invitems`.`invitemactivo`, 'auditcreacionusuarioid', `invitems`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invitems`.`auditcreaciondispositivo`, 'auditcreacionip', `invitems`.`auditcreacionip`, 'auditcreacionfechahora', `invitems`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invitems`.`auditedicionusuarioid`, 'auditediciondispositivo', `invitems`.`auditediciondispositivo`, 'auditedicionip', `invitems`.`auditedicionip`, 'auditedicionfechahora', `invitems`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invitems`
  WHERE `invitemid` = v_invitemid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invitemslog` (
    `invitemid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invitemid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invitems`
  SET `invitemdsc` = v_invitemdsc,
    `invunidmedid` = v_invunidmedid,
    `erpinvitemcod` = v_erpinvitemcod,
    `invitemleche` = v_invitemleche,
    `invitemstockeable` = v_invitemstockeable,
    `invitemactivo` = v_invitemactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `invitemid` = v_invitemid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invitems_anular//
CREATE PROCEDURE sp_invitems_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_invitemid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_invitemid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `invitems` WHERE `invitemid` = v_invitemid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('invitemid', `invitems`.`invitemid`, 'invitemdsc', `invitems`.`invitemdsc`, 'invunidmedid', `invitems`.`invunidmedid`, 'erpinvitemcod', `invitems`.`erpinvitemcod`, 'invitemleche', `invitems`.`invitemleche`, 'invitemactivo', `invitems`.`invitemactivo`, 'auditcreacionusuarioid', `invitems`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `invitems`.`auditcreaciondispositivo`, 'auditcreacionip', `invitems`.`auditcreacionip`, 'auditcreacionfechahora', `invitems`.`auditcreacionfechahora`, 'auditedicionusuarioid', `invitems`.`auditedicionusuarioid`, 'auditediciondispositivo', `invitems`.`auditediciondispositivo`, 'auditedicionip', `invitems`.`auditedicionip`, 'auditedicionfechahora', `invitems`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `invitems`
  WHERE `invitemid` = v_invitemid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `invitemslog` (
    `invitemid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_invitemid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `invitems`
  SET `invitemactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `invitemid` = v_invitemid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_invitems_listar//
CREATE PROCEDURE sp_invitems_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroInvitemdsc VARCHAR(255);
  DECLARE v_filtroInvunidmedid VARCHAR(255);
  DECLARE v_filtroErpinvitemcod VARCHAR(255);
  DECLARE v_filtroInvitemleche VARCHAR(255);
  DECLARE v_filtroInvitemactivo VARCHAR(255);
  SET v_filtroInvitemdsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvitemdsc'));
  SET v_filtroInvunidmedid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvunidmedid'));
  SET v_filtroErpinvitemcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErpinvitemcod'));
  SET v_filtroInvitemleche = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvitemleche'));
  SET v_filtroInvitemactivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvitemactivo'));
  SELECT
    t.`invitemid`,
    t.`invitemdsc`,
    t.`invunidmedid`,
    invunidadesmedidas.invunidmeddsc AS `invunidmeddsc`,
    t.`erpinvitemcod`,
    t.`invitemleche`,
    t.`invitemstockeable`,
    t.`invitemactivo`
  FROM `invitems` t
  LEFT JOIN `invunidadesmedidas` ON t.`invunidmedid` = `invunidadesmedidas`.`invunidmedid`
  WHERE 1=1
    AND (v_filtroInvitemdsc IS NULL OR v_filtroInvitemdsc = '' OR t.`invitemdsc` LIKE CONCAT('%', v_filtroInvitemdsc, '%'))
    AND (v_filtroInvunidmedid IS NULL OR v_filtroInvunidmedid = '' OR t.`invunidmedid` = v_filtroInvunidmedid)
    AND (v_filtroErpinvitemcod IS NULL OR v_filtroErpinvitemcod = '' OR t.`erpinvitemcod` LIKE CONCAT('%', v_filtroErpinvitemcod, '%'))
    AND (v_filtroInvitemleche IS NULL OR v_filtroInvitemleche = '' OR t.`invitemleche` = v_filtroInvitemleche)
    AND (v_filtroInvitemactivo IS NULL OR v_filtroInvitemactivo = '' OR t.`invitemactivo` = v_filtroInvitemactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
