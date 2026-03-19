DELIMITER //
DROP PROCEDURE IF EXISTS sp_fundosestanquesclientes_insertar//
CREATE PROCEDURE sp_fundosestanquesclientes_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoestanqueid int(11);
  DECLARE v_clienteid int(11);
  DECLARE v_estanqueclientecod int(11);
  DECLARE v_fndestcliactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoestanqueid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueid')) AS SIGNED),
    v_clienteid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteid')) AS SIGNED),
    v_estanqueclientecod = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanqueclientecod')) AS SIGNED),
    v_fndestcliactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fndestcliactivo')) AS SIGNED);

  IF EXISTS (SELECT 1 FROM `fundosestanquesclientes` WHERE `fundoestanqueid` = v_fundoestanqueid AND `clienteid` = v_clienteid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Duplicate primary key');
    LEAVE sp_main;
  END IF;

  IF EXISTS (SELECT 1 FROM `fundosestanquesclientes` WHERE `estanqueclientecod` = v_estanqueclientecod) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Codigo cliente ya existe');
    LEAVE sp_main;
  END IF;

  INSERT INTO `fundosestanquesclientes` (
    `fundoestanqueid`,
    `clienteid`,
    `estanqueclientecod`,
    `fndestcliactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_fundoestanqueid,
    v_clienteid,
    v_estanqueclientecod,
    v_fndestcliactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `fundosestanquesclienteslog` (
    `fundoestanqueid`,
    `clienteid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_fundoestanqueid,
    v_clienteid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundosestanquesclientes_editar//
CREATE PROCEDURE sp_fundosestanquesclientes_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoestanqueid int(11);
  DECLARE v_clienteid int(11);
  DECLARE v_fndestcliactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoestanqueid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueid')) AS SIGNED),
    v_clienteid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteid')) AS SIGNED),
    v_fndestcliactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fndestcliactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `fundosestanquesclientes` WHERE `fundoestanqueid` = v_fundoestanqueid AND `clienteid` = v_clienteid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('fundoestanqueid', `fundosestanquesclientes`.`fundoestanqueid`, 'clienteid', `fundosestanquesclientes`.`clienteid`, 'estanqueclientecod', `fundosestanquesclientes`.`estanqueclientecod`, 'fndestcliactivo', `fundosestanquesclientes`.`fndestcliactivo`, 'auditcreacionusuarioid', `fundosestanquesclientes`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `fundosestanquesclientes`.`auditcreaciondispositivo`, 'auditcreacionip', `fundosestanquesclientes`.`auditcreacionip`, 'auditcreacionfechahora', `fundosestanquesclientes`.`auditcreacionfechahora`, 'auditedicionusuarioid', `fundosestanquesclientes`.`auditedicionusuarioid`, 'auditediciondispositivo', `fundosestanquesclientes`.`auditediciondispositivo`, 'auditedicionip', `fundosestanquesclientes`.`auditedicionip`, 'auditedicionfechahora', `fundosestanquesclientes`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `fundosestanquesclientes`
  WHERE `fundoestanqueid` = v_fundoestanqueid AND `clienteid` = v_clienteid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `fundosestanquesclienteslog` (
    `fundoestanqueid`,
    `clienteid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_fundoestanqueid,
    v_clienteid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `fundosestanquesclientes`
  SET `fndestcliactivo` = v_fndestcliactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `fundoestanqueid` = v_fundoestanqueid AND `clienteid` = v_clienteid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundosestanquesclientes_anular//
CREATE PROCEDURE sp_fundosestanquesclientes_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoestanqueid int(11);
  DECLARE v_clienteid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoestanqueid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueid')) AS SIGNED),
    v_clienteid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `fundosestanquesclientes` WHERE `fundoestanqueid` = v_fundoestanqueid AND `clienteid` = v_clienteid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('fundoestanqueid', `fundosestanquesclientes`.`fundoestanqueid`, 'clienteid', `fundosestanquesclientes`.`clienteid`, 'estanqueclientecod', `fundosestanquesclientes`.`estanqueclientecod`, 'fndestcliactivo', `fundosestanquesclientes`.`fndestcliactivo`, 'auditcreacionusuarioid', `fundosestanquesclientes`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `fundosestanquesclientes`.`auditcreaciondispositivo`, 'auditcreacionip', `fundosestanquesclientes`.`auditcreacionip`, 'auditcreacionfechahora', `fundosestanquesclientes`.`auditcreacionfechahora`, 'auditedicionusuarioid', `fundosestanquesclientes`.`auditedicionusuarioid`, 'auditediciondispositivo', `fundosestanquesclientes`.`auditediciondispositivo`, 'auditedicionip', `fundosestanquesclientes`.`auditedicionip`, 'auditedicionfechahora', `fundosestanquesclientes`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `fundosestanquesclientes`
  WHERE `fundoestanqueid` = v_fundoestanqueid AND `clienteid` = v_clienteid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `fundosestanquesclienteslog` (
    `fundoestanqueid`,
    `clienteid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_fundoestanqueid,
    v_clienteid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `fundosestanquesclientes`
  SET `fndestcliactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `fundoestanqueid` = v_fundoestanqueid AND `clienteid` = v_clienteid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundosestanquesclientes_listar//
CREATE PROCEDURE sp_fundosestanquesclientes_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroClienteid VARCHAR(255);
  DECLARE v_filtroFundoId VARCHAR(255);
  DECLARE v_filtroFndestcliactivo VARCHAR(255);
  SET v_filtroClienteid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroClienteid'));
  SET v_filtroFundoId = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoId'));
  SET v_filtroFndestcliactivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFndestcliactivo')),'null'),'');
  SELECT
    t.`fundoestanqueid` AS `fundoestanqueid`,
    fe.`fundoestanquedsc` AS `fundoestanquedsc`,
    t.`clienteid` AS `clienteid`,
    c.`clienterazonsocial` AS `clienterazonsocial`,
    fe.`fundoid`,
    f.`fundonombre` AS `fundonombre`,
    t.`estanqueclientecod`,
    t.`fndestcliactivo`
  FROM `fundosestanquesclientes` t
  LEFT JOIN `fundosestanques` fe ON t.`fundoestanqueid` = fe.`fundoestanqueid`
  LEFT JOIN `clientes` c ON t.`clienteid` = c.`clienteid`
  INNER JOIN `fundos` f ON fe.`fundoid` = f.`fundoid`
  WHERE 1=1
    AND (v_filtroClienteid IS NULL OR v_filtroClienteid = '' OR t.`clienteid` = v_filtroClienteid)
    AND (v_filtroFundoId IS NULL OR v_filtroFundoId = '' OR fe.`fundoid` = v_filtroFundoId)
    AND (v_filtroFndestcliactivo IS NULL OR v_filtroFndestcliactivo = '' OR t.`fndestcliactivo` = v_filtroFndestcliactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
