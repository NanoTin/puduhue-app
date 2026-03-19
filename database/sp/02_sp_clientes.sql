DELIMITER //
DROP PROCEDURE IF EXISTS sp_clientes_insertar//
CREATE PROCEDURE sp_clientes_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_clienterut varchar(12);
  DECLARE v_clienterazonsocial varchar(100);
  DECLARE v_clienteemail varchar(100);
  DECLARE v_clientecontacto varchar(100);
  DECLARE v_clienteactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_clienterut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienterut')),
    v_clienterazonsocial = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienterazonsocial')),
    v_clienteemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteemail')),
    v_clientecontacto = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clientecontacto')),
    v_clienteactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteactivo')) AS SIGNED);

  IF EXISTS (SELECT 1 FROM `clientes` WHERE `clienterut` = v_clienterut) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record with same clienterut already exists');
    LEAVE sp_main;
  END IF;

  INSERT INTO `clientes` (
    `clienterut`,
    `clienterazonsocial`,
    `clienteemail`,
    `clientecontacto`,
    `clienteactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_clienterut,
    v_clienterazonsocial,
    v_clienteemail,
    v_clientecontacto,
    v_clienteactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `clienteslog` (
    `clienteid`,
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

DROP PROCEDURE IF EXISTS sp_clientes_editar//
CREATE PROCEDURE sp_clientes_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_clienteid int(11);
  DECLARE v_clienterut varchar(12);
  DECLARE v_clienterazonsocial varchar(100);
  DECLARE v_clienteemail varchar(100);
  DECLARE v_clientecontacto varchar(100);
  DECLARE v_clienteactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_clienteid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteid')) AS SIGNED),
    v_clienterut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienterut')),
    v_clienterazonsocial = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienterazonsocial')),
    v_clienteemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteemail')),
    v_clientecontacto = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clientecontacto')),
    v_clienteactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `clientes` WHERE `clienteid` = v_clienteid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('clienteid', `clientes`.`clienteid`, 'clienterut', `clientes`.`clienterut`, 'clienterazonsocial', `clientes`.`clienterazonsocial`, 'clienteemail', `clientes`.`clienteemail`, 'clientecontacto', `clientes`.`clientecontacto`, 'clienteactivo', `clientes`.`clienteactivo`, 'auditcreacionusuarioid', `clientes`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `clientes`.`auditcreaciondispositivo`, 'auditcreacionip', `clientes`.`auditcreacionip`, 'auditcreacionfechahora', `clientes`.`auditcreacionfechahora`, 'auditedicionusuarioid', `clientes`.`auditedicionusuarioid`, 'auditediciondispositivo', `clientes`.`auditediciondispositivo`, 'auditedicionip', `clientes`.`auditedicionip`, 'auditedicionfechahora', `clientes`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `clientes`
  WHERE `clienteid` = v_clienteid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `clienteslog` (
    `clienteid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_clienteid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `clientes`
  SET `clienterut` = v_clienterut,
    `clienterazonsocial` = v_clienterazonsocial,
    `clienteemail` = v_clienteemail,
    `clientecontacto` = v_clientecontacto,
    `clienteactivo` = v_clienteactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `clienteid` = v_clienteid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_clientes_anular//
CREATE PROCEDURE sp_clientes_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_clienteid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_clienteid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `clientes` WHERE `clienteid` = v_clienteid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('clienteid', `clientes`.`clienteid`, 'clienterut', `clientes`.`clienterut`, 'clienterazonsocial', `clientes`.`clienterazonsocial`, 'clienteemail', `clientes`.`clienteemail`, 'clientecontacto', `clientes`.`clientecontacto`, 'clienteactivo', `clientes`.`clienteactivo`, 'auditcreacionusuarioid', `clientes`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `clientes`.`auditcreaciondispositivo`, 'auditcreacionip', `clientes`.`auditcreacionip`, 'auditcreacionfechahora', `clientes`.`auditcreacionfechahora`, 'auditedicionusuarioid', `clientes`.`auditedicionusuarioid`, 'auditediciondispositivo', `clientes`.`auditediciondispositivo`, 'auditedicionip', `clientes`.`auditedicionip`, 'auditedicionfechahora', `clientes`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `clientes`
  WHERE `clienteid` = v_clienteid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `clienteslog` (
    `clienteid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_clienteid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `clientes`
  SET `clienteactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `clienteid` = v_clienteid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_clientes_listar//
CREATE PROCEDURE sp_clientes_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroClienterut VARCHAR(255);
  DECLARE v_filtroClienterazonsocial VARCHAR(255);
  DECLARE v_filtroClienteemail VARCHAR(255);
  DECLARE v_filtroClienteactivo VARCHAR(255);
  SET v_filtroClienterut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroClienterut'));
  SET v_filtroClienterazonsocial = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroClienterazonsocial'));
  SET v_filtroClienteemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroClienteemail'));
  SET v_filtroClienteactivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroClienteactivo')),'null'), '');
  SELECT
    t.`clienteid`,
    t.`clienterut`,
    t.`clienterazonsocial`,
    t.`clienteemail`,
    t.`clientecontacto`,
    t.`clienteactivo`
  FROM `clientes` t
  WHERE 1=1
    AND (v_filtroClienterut IS NULL OR v_filtroClienterut = '' OR t.`clienterut` LIKE CONCAT('%', v_filtroClienterut, '%'))
    AND (v_filtroClienterazonsocial IS NULL OR v_filtroClienterazonsocial = '' OR t.`clienterazonsocial` LIKE CONCAT('%', v_filtroClienterazonsocial, '%'))
    AND (v_filtroClienteemail IS NULL OR v_filtroClienteemail = '' OR t.`clienteemail` LIKE CONCAT('%', v_filtroClienteemail, '%'))
    AND (v_filtroClienteactivo IS NULL OR v_filtroClienteactivo = '' OR t.`clienteactivo` = v_filtroClienteactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
