DELIMITER //
DROP PROCEDURE IF EXISTS sp_fundosestanques_insertar//
CREATE PROCEDURE sp_fundosestanques_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoid int(11);
  DECLARE v_fundoestanquedsc varchar(50);
  DECLARE v_estanquemarcaid int(11);
  DECLARE v_fundoestanqueorden int(4);
  DECLARE v_fundoestanqueactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_fundoestanquedsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanquedsc')),
    v_estanquemarcaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcaid')) AS SIGNED),
    v_fundoestanqueorden = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueorden')) AS SIGNED),
    v_fundoestanqueactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueactivo')) AS SIGNED);

  INSERT INTO `fundosestanques` (
    `fundoid`,
    `fundoestanquedsc`,
    `estanquemarcaid`,
    `fundoestanqueorden`,
    `fundoestanqueactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_fundoid,
    v_fundoestanquedsc,
    v_estanquemarcaid,
    v_fundoestanqueorden,
    v_fundoestanqueactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `fundosestanqueslog` (
    `fundoestanqueid`,
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

DROP PROCEDURE IF EXISTS sp_fundosestanques_editar//
CREATE PROCEDURE sp_fundosestanques_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoestanqueid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_fundoestanquedsc varchar(50);
  DECLARE v_estanquemarcaid int(11);
  DECLARE v_fundoestanqueorden int(4);
  DECLARE v_fundoestanqueactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoestanqueid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_fundoestanquedsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanquedsc')),
    v_estanquemarcaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanquemarcaid')) AS SIGNED),
    v_fundoestanqueorden = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueorden')) AS SIGNED),
    v_fundoestanqueactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `fundosestanques` WHERE `fundoestanqueid` = v_fundoestanqueid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('fundoestanqueid', `fundosestanques`.`fundoestanqueid`, 'fundoid', `fundosestanques`.`fundoid`, 'fundoestanquedsc', `fundosestanques`.`fundoestanquedsc`, 'estanquemarcaid', `fundosestanques`.`estanquemarcaid`, 'fundoestanqueorden', `fundosestanques`.`fundoestanqueorden`, 'fundoestanqueactivo', `fundosestanques`.`fundoestanqueactivo`, 'auditcreacionusuarioid', `fundosestanques`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `fundosestanques`.`auditcreaciondispositivo`, 'auditcreacionip', `fundosestanques`.`auditcreacionip`, 'auditcreacionfechahora', `fundosestanques`.`auditcreacionfechahora`, 'auditedicionusuarioid', `fundosestanques`.`auditedicionusuarioid`, 'auditediciondispositivo', `fundosestanques`.`auditediciondispositivo`, 'auditedicionip', `fundosestanques`.`auditedicionip`, 'auditedicionfechahora', `fundosestanques`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `fundosestanques`
  WHERE `fundoestanqueid` = v_fundoestanqueid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `fundosestanqueslog` (
    `fundoestanqueid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_fundoestanqueid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `fundosestanques`
  SET `fundoid` = v_fundoid,
    `fundoestanquedsc` = v_fundoestanquedsc,
    `estanquemarcaid` = v_estanquemarcaid,
    `fundoestanqueorden` = v_fundoestanqueorden,
    `fundoestanqueactivo` = v_fundoestanqueactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `fundoestanqueid` = v_fundoestanqueid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundosestanques_anular//
CREATE PROCEDURE sp_fundosestanques_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoestanqueid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoestanqueid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `fundosestanques` WHERE `fundoestanqueid` = v_fundoestanqueid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('fundoestanqueid', `fundosestanques`.`fundoestanqueid`, 'fundoid', `fundosestanques`.`fundoid`, 'fundoestanquedsc', `fundosestanques`.`fundoestanquedsc`, 'estanquemarcaid', `fundosestanques`.`estanquemarcaid`, 'fundoestanqueorden', `fundosestanques`.`fundoestanqueorden`, 'fundoestanqueactivo', `fundosestanques`.`fundoestanqueactivo`, 'auditcreacionusuarioid', `fundosestanques`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `fundosestanques`.`auditcreaciondispositivo`, 'auditcreacionip', `fundosestanques`.`auditcreacionip`, 'auditcreacionfechahora', `fundosestanques`.`auditcreacionfechahora`, 'auditedicionusuarioid', `fundosestanques`.`auditedicionusuarioid`, 'auditediciondispositivo', `fundosestanques`.`auditediciondispositivo`, 'auditedicionip', `fundosestanques`.`auditedicionip`, 'auditedicionfechahora', `fundosestanques`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `fundosestanques`
  WHERE `fundoestanqueid` = v_fundoestanqueid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `fundosestanqueslog` (
    `fundoestanqueid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_fundoestanqueid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `fundosestanques`
  SET `fundoestanqueactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `fundoestanqueid` = v_fundoestanqueid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundosestanques_listar//
CREATE PROCEDURE sp_fundosestanques_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroFundoid VARCHAR(255);
  DECLARE v_filtroFundoestanquedsc VARCHAR(255);
  DECLARE v_filtroEstanquemarcaid VARCHAR(255);
  DECLARE v_filtroFundoestanqueactivo VARCHAR(255);
  SET v_filtroFundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid'));
  SET v_filtroFundoestanquedsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoestanquedsc'));
  SET v_filtroEstanquemarcaid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEstanquemarcaid'));
  SET v_filtroFundoestanqueactivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoestanqueactivo')), 'null'),'');
  SELECT
    t.`fundoestanqueid`,
    fundos.fundonombre AS `fundos_fundonombre`,
    t.`fundoestanquedsc`,
    estanquesmarcas.estanquemarcadsc AS `estanquesmarcas_estanquemarcadsc`,
    t.`fundoestanqueorden`,
    t.`fundoestanqueactivo`
  FROM `fundosestanques` t
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  LEFT JOIN `estanquesmarcas` ON t.`estanquemarcaid` = `estanquesmarcas`.`estanquemarcaid`
  WHERE 1=1
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid)
    AND (v_filtroFundoestanquedsc IS NULL OR v_filtroFundoestanquedsc = '' OR t.`fundoestanquedsc` LIKE CONCAT('%', v_filtroFundoestanquedsc, '%'))
    AND (v_filtroEstanquemarcaid IS NULL OR v_filtroEstanquemarcaid = '' OR t.`estanquemarcaid` = v_filtroEstanquemarcaid)
    AND (v_filtroFundoestanqueactivo IS NULL OR v_filtroFundoestanqueactivo = '' OR t.`fundoestanqueactivo` = v_filtroFundoestanqueactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
