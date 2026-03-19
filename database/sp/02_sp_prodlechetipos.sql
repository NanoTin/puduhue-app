DELIMITER //
DROP PROCEDURE IF EXISTS sp_prodlechetipos_insertar//
CREATE PROCEDURE sp_prodlechetipos_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_prodlechetipodsc varchar(50);
  DECLARE v_invitemid int(11);
  DECLARE v_prodlecheventa tinyint(1);
  DECLARE v_prodlecheorden int(2);
  DECLARE v_prodlecheactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_prodlechetipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetipodsc')),
    v_invitemid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemid')) AS SIGNED),
    v_prodlecheventa = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventa')) AS SIGNED),
    v_prodlecheorden = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheorden')) AS SIGNED),
    v_prodlecheactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheactivo')) AS SIGNED);

  INSERT INTO `prodlechetipos` (
    `prodlechetipodsc`,
    `invitemid`,
    `prodlecheventa`,
    `prodlecheorden`,
    `prodlecheactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_prodlechetipodsc,
    v_invitemid,
    v_prodlecheventa,
    v_prodlecheorden,
    v_prodlecheactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `prodlechetiposlog` (
    `prodlechetipoid`,
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

DROP PROCEDURE IF EXISTS sp_prodlechetipos_editar//
CREATE PROCEDURE sp_prodlechetipos_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_prodlechetipoid int(11);
  DECLARE v_prodlechetipodsc varchar(50);
  DECLARE v_invitemid int(11);
  DECLARE v_prodlecheventa tinyint(1);
  DECLARE v_prodlecheorden int(2);
  DECLARE v_prodlecheactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_prodlechetipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetipoid')) AS SIGNED),
    v_prodlechetipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetipodsc')),
    v_invitemid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemid')) AS SIGNED),
    v_prodlecheventa = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventa')) AS SIGNED),
    v_prodlecheorden = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheorden')) AS SIGNED),
    v_prodlecheactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `prodlechetipos` WHERE `prodlechetipoid` = v_prodlechetipoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('prodlechetipoid', `prodlechetipos`.`prodlechetipoid`, 'prodlechetipodsc', `prodlechetipos`.`prodlechetipodsc`, 'invitemid', `prodlechetipos`.`invitemid`, 'prodlecheventa', `prodlechetipos`.`prodlecheventa`, 'prodlecheorden', `prodlechetipos`.`prodlecheorden`, 'prodlecheactivo', `prodlechetipos`.`prodlecheactivo`, 'auditcreacionusuarioid', `prodlechetipos`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `prodlechetipos`.`auditcreaciondispositivo`, 'auditcreacionip', `prodlechetipos`.`auditcreacionip`, 'auditcreacionfechahora', `prodlechetipos`.`auditcreacionfechahora`, 'auditedicionusuarioid', `prodlechetipos`.`auditedicionusuarioid`, 'auditediciondispositivo', `prodlechetipos`.`auditediciondispositivo`, 'auditedicionip', `prodlechetipos`.`auditedicionip`, 'auditedicionfechahora', `prodlechetipos`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `prodlechetipos`
  WHERE `prodlechetipoid` = v_prodlechetipoid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `prodlechetiposlog` (
    `prodlechetipoid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_prodlechetipoid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `prodlechetipos`
  SET `prodlechetipodsc` = v_prodlechetipodsc,
    `invitemid` = v_invitemid,
    `prodlecheventa` = v_prodlecheventa,
    `prodlecheorden` = v_prodlecheorden,
    `prodlecheactivo` = v_prodlecheactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `prodlechetipoid` = v_prodlechetipoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_prodlechetipos_anular//
CREATE PROCEDURE sp_prodlechetipos_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_prodlechetipoid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_prodlechetipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetipoid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `prodlechetipos` WHERE `prodlechetipoid` = v_prodlechetipoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('prodlechetipoid', `prodlechetipos`.`prodlechetipoid`, 'prodlechetipodsc', `prodlechetipos`.`prodlechetipodsc`, 'invitemid', `prodlechetipos`.`invitemid`, 'prodlecheventa', `prodlechetipos`.`prodlecheventa`, 'prodlecheactivo', `prodlechetipos`.`prodlecheactivo`, 'auditcreacionusuarioid', `prodlechetipos`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `prodlechetipos`.`auditcreaciondispositivo`, 'auditcreacionip', `prodlechetipos`.`auditcreacionip`, 'auditcreacionfechahora', `prodlechetipos`.`auditcreacionfechahora`, 'auditedicionusuarioid', `prodlechetipos`.`auditedicionusuarioid`, 'auditediciondispositivo', `prodlechetipos`.`auditediciondispositivo`, 'auditedicionip', `prodlechetipos`.`auditedicionip`, 'auditedicionfechahora', `prodlechetipos`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `prodlechetipos`
  WHERE `prodlechetipoid` = v_prodlechetipoid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `prodlechetiposlog` (
    `prodlechetipoid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_prodlechetipoid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `prodlechetipos`
  SET `prodlecheactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `prodlechetipoid` = v_prodlechetipoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_prodlechetipos_listar//
CREATE PROCEDURE sp_prodlechetipos_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroProdlechetipodsc VARCHAR(255);
  DECLARE v_filtroInvitemid VARCHAR(255);
  DECLARE v_filtroProdlecheventa VARCHAR(255);
  DECLARE v_filtroProdlecheactivo VARCHAR(255);
  SET v_filtroProdlechetipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlechetipodsc'));
  SET v_filtroInvitemid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvitemid'));
  SET v_filtroProdlecheventa = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlecheventa'));
  SET v_filtroProdlecheactivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlecheactivo'));
  SELECT
    t.`prodlechetipoid`,
    t.`prodlechetipodsc`,
    t.`invitemid`,
    invitems.invitemdsc AS `invitemdsc`,
    t.`prodlecheventa`,
    t.`prodlecheorden`,
    t.`prodlecheactivo`
  FROM `prodlechetipos` t
  LEFT JOIN `invitems` ON t.`invitemid` = `invitems`.`invitemid`
  WHERE 1=1
    AND (v_filtroProdlechetipodsc IS NULL OR v_filtroProdlechetipodsc = '' OR t.`prodlechetipodsc` LIKE CONCAT('%', v_filtroProdlechetipodsc, '%'))
    AND (v_filtroInvitemid IS NULL OR v_filtroInvitemid = '' OR t.`invitemid` = v_filtroInvitemid)
    AND (v_filtroProdlecheventa IS NULL OR v_filtroProdlecheventa = '' OR t.`prodlecheventa` = v_filtroProdlecheventa)
    AND (v_filtroProdlecheactivo IS NULL OR v_filtroProdlecheactivo = '' OR t.`prodlecheactivo` = v_filtroProdlecheactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
