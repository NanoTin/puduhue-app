DELIMITER //
DROP PROCEDURE IF EXISTS sp_retirolechedetalle_insertar//
CREATE PROCEDURE sp_retirolechedetalle_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_fundoestanqueid int(11);
  DECLARE v_clienteid int(11);
  DECLARE v_estanqueclientecod int(11);
  DECLARE v_retirolechefecha datetime;
  DECLARE v_retirolechehoraini time;
  DECLARE v_retirolechehorafin time;
  DECLARE v_retirolechelitros int(6);
  DECLARE v_retirolechetemperatura float;
  DECLARE v_retirolecheobservacion varchar(100);
  DECLARE v_retirolechefoto varchar(255);
  DECLARE v_retirolechestatus varchar(3);
  DECLARE v_retirolecheid int(11);
  DECLARE v_headerfecha datetime;
  DECLARE v_total_litros int(6);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_fundoestanqueid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoestanqueid')) AS SIGNED),
    v_clienteid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.clienteid')) AS SIGNED),
    v_estanqueclientecod = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.estanqueclientecod')) AS SIGNED),
    v_retirolechefecha = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechefecha')) AS DATETIME),
    v_retirolechehoraini = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechehoraini')) AS TIME),
    v_retirolechehorafin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechehorafin')) AS TIME),
    v_retirolechelitros = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechelitros')) AS SIGNED),
    v_retirolechetemperatura = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechetemperatura')) AS DECIMAL(20,6)),
    v_retirolecheobservacion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolecheobservacion')),
    v_retirolechefoto = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechefoto')),
    v_retirolechestatus = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechestatus'));

  IF v_retirolechestatus IS NULL OR v_retirolechestatus = '' OR (v_retirolechestatus NOT IN ('CN', 'ANL')) THEN
    SET v_retirolechestatus = 'CN';
  END IF;

  SET v_headerfecha = CAST(DATE(v_retirolechefecha) AS DATETIME);

  INSERT INTO `retirolechedetalle` (
    `empresaid`,
    `fundoid`,
    `fundoestanqueid`,
    `clienteid`,
    `estanqueclientecod`,
    `retirolechefecha`,
    `retirolechehoraini`,
    `retirolechehorafin`,
    `retirolechelitros`,
    `retirolechetemperatura`,
    `retirolecheobservacion`,
    `retirolechefoto`,
    `retirolechestatus`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_empresaid,
    v_fundoid,
    v_fundoestanqueid,
    v_clienteid,
    v_estanqueclientecod,
    v_retirolechefecha,
    v_retirolechehoraini,
    v_retirolechehorafin,
    v_retirolechelitros,
    v_retirolechetemperatura,
    v_retirolecheobservacion,
    v_retirolechefoto,
    v_retirolechestatus,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET v_retirolecheid = LAST_INSERT_ID();

  INSERT INTO `retirolechelog` (
    `retirolecheid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_retirolecheid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  SELECT IFNULL(SUM(`retirolechelitros`), 0)
  INTO v_total_litros
  FROM `retirolechedetalle`
  WHERE `empresaid` = v_empresaid
    AND `fundoid` = v_fundoid
    AND DATE(`retirolechefecha`) = DATE(v_retirolechefecha)
    AND `retirolechestatus` = 'CN';

  INSERT INTO `retiroleche` (
    `empresaid`,
    `fundoid`,
    `retirolechefecha`,
    `retirolechetotlitros`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  ) VALUES (
    v_empresaid,
    v_fundoid,
    v_headerfecha,
    v_total_litros,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  )
  ON DUPLICATE KEY UPDATE
    `retirolechetotlitros` = v_total_litros,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK', 'retirolecheid', v_retirolecheid);
END//

DROP PROCEDURE IF EXISTS sp_retirolechedetalle_editar//
CREATE PROCEDURE sp_retirolechedetalle_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_retirolecheid int(11);
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_retirolechefecha datetime;
  DECLARE v_retirolechehoraini time;
  DECLARE v_retirolechehorafin time;
  DECLARE v_retirolechelitros int(6);
  DECLARE v_retirolechetemperatura float;
  DECLARE v_retirolecheobservacion varchar(100);
  DECLARE v_retirolechefoto varchar(255);
  DECLARE v_prev_bkpjson JSON;
  DECLARE v_total_litros int(6);
  DECLARE v_headerfecha datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET
    v_retirolecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolecheid')) AS SIGNED),
    v_retirolechehoraini = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechehoraini')) AS TIME),
    v_retirolechehorafin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechehorafin')) AS TIME),
    v_retirolechelitros = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechelitros')) AS SIGNED),
    v_retirolechetemperatura = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechetemperatura')) AS DECIMAL(20,6)),
    v_retirolecheobservacion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolecheobservacion')),
    v_retirolechefoto = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechefoto'));

  IF NOT EXISTS (SELECT 1 FROM `retirolechedetalle` WHERE `retirolecheid` = v_retirolecheid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT
    `empresaid`,
    `fundoid`,
    `retirolechefecha`,
    JSON_OBJECT(
      'retirolecheid', `retirolechedetalle`.`retirolecheid`,
      'empresaid', `retirolechedetalle`.`empresaid`,
      'fundoid', `retirolechedetalle`.`fundoid`,
      'fundoestanqueid', `retirolechedetalle`.`fundoestanqueid`,
      'clienteid', `retirolechedetalle`.`clienteid`,
      'estanqueclientecod', `retirolechedetalle`.`estanqueclientecod`,
      'retirolechefecha', `retirolechedetalle`.`retirolechefecha`,
      'retirolechehoraini', `retirolechedetalle`.`retirolechehoraini`,
      'retirolechehorafin', `retirolechedetalle`.`retirolechehorafin`,
      'retirolechelitros', `retirolechedetalle`.`retirolechelitros`,
      'retirolechetemperatura', `retirolechedetalle`.`retirolechetemperatura`,
      'retirolecheobservacion', `retirolechedetalle`.`retirolecheobservacion`,
      'retirolechefoto', `retirolechedetalle`.`retirolechefoto`,
      'retirolechestatus', `retirolechedetalle`.`retirolechestatus`,
      'auditcreacionusuarioid', `retirolechedetalle`.`auditcreacionusuarioid`,
      'auditcreaciondispositivo', `retirolechedetalle`.`auditcreaciondispositivo`,
      'auditcreacionip', `retirolechedetalle`.`auditcreacionip`,
      'auditcreacionfechahora', `retirolechedetalle`.`auditcreacionfechahora`,
      'auditedicionusuarioid', `retirolechedetalle`.`auditedicionusuarioid`,
      'auditediciondispositivo', `retirolechedetalle`.`auditediciondispositivo`,
      'auditedicionip', `retirolechedetalle`.`auditedicionip`,
      'auditedicionfechahora', `retirolechedetalle`.`auditedicionfechahora`
    )
  INTO v_empresaid, v_fundoid, v_retirolechefecha, v_prev_bkpjson
  FROM `retirolechedetalle`
  WHERE `retirolecheid` = v_retirolecheid
  LIMIT 1;

  INSERT INTO `retirolechelog` (
    `retirolecheid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_retirolecheid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `retirolechedetalle`
  SET `retirolechehoraini` = v_retirolechehoraini,
    `retirolechehorafin` = v_retirolechehorafin,
    `retirolechelitros` = v_retirolechelitros,
    `retirolechetemperatura` = v_retirolechetemperatura,
    `retirolecheobservacion` = v_retirolecheobservacion,
    `retirolechefoto` = v_retirolechefoto,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `retirolecheid` = v_retirolecheid;

  SET v_headerfecha = CAST(DATE(v_retirolechefecha) AS DATETIME);
  SELECT IFNULL(SUM(`retirolechelitros`), 0)
  INTO v_total_litros
  FROM `retirolechedetalle`
  WHERE `empresaid` = v_empresaid
    AND `fundoid` = v_fundoid
    AND DATE(`retirolechefecha`) = DATE(v_retirolechefecha)
    AND `retirolechestatus` = 'CN';

  INSERT INTO `retiroleche` (
    `empresaid`,
    `fundoid`,
    `retirolechefecha`,
    `retirolechetotlitros`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  ) VALUES (
    v_empresaid,
    v_fundoid,
    v_headerfecha,
    v_total_litros,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  )
  ON DUPLICATE KEY UPDATE
    `retirolechetotlitros` = v_total_litros,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_retirolechedetalle_anular//
CREATE PROCEDURE sp_retirolechedetalle_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_retirolecheid int(11);
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_retirolechefecha datetime;
  DECLARE v_prev_bkpjson JSON;
  DECLARE v_total_litros int(6);
  DECLARE v_headerfecha datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET v_retirolecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolecheid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `retirolechedetalle` WHERE `retirolecheid` = v_retirolecheid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT
    `empresaid`,
    `fundoid`,
    `retirolechefecha`,
    JSON_OBJECT(
      'retirolecheid', `retirolechedetalle`.`retirolecheid`,
      'empresaid', `retirolechedetalle`.`empresaid`,
      'fundoid', `retirolechedetalle`.`fundoid`,
      'fundoestanqueid', `retirolechedetalle`.`fundoestanqueid`,
      'clienteid', `retirolechedetalle`.`clienteid`,
      'estanqueclientecod', `retirolechedetalle`.`estanqueclientecod`,
      'retirolechefecha', `retirolechedetalle`.`retirolechefecha`,
      'retirolechehoraini', `retirolechedetalle`.`retirolechehoraini`,
      'retirolechehorafin', `retirolechedetalle`.`retirolechehorafin`,
      'retirolechelitros', `retirolechedetalle`.`retirolechelitros`,
      'retirolechetemperatura', `retirolechedetalle`.`retirolechetemperatura`,
      'retirolecheobservacion', `retirolechedetalle`.`retirolecheobservacion`,
      'retirolechefoto', `retirolechedetalle`.`retirolechefoto`,
      'retirolechestatus', `retirolechedetalle`.`retirolechestatus`,
      'auditcreacionusuarioid', `retirolechedetalle`.`auditcreacionusuarioid`,
      'auditcreaciondispositivo', `retirolechedetalle`.`auditcreaciondispositivo`,
      'auditcreacionip', `retirolechedetalle`.`auditcreacionip`,
      'auditcreacionfechahora', `retirolechedetalle`.`auditcreacionfechahora`,
      'auditedicionusuarioid', `retirolechedetalle`.`auditedicionusuarioid`,
      'auditediciondispositivo', `retirolechedetalle`.`auditediciondispositivo`,
      'auditedicionip', `retirolechedetalle`.`auditedicionip`,
      'auditedicionfechahora', `retirolechedetalle`.`auditedicionfechahora`
    )
  INTO v_empresaid, v_fundoid, v_retirolechefecha, v_prev_bkpjson
  FROM `retirolechedetalle`
  WHERE `retirolecheid` = v_retirolecheid
  LIMIT 1;

  INSERT INTO `retirolechelog` (
    `retirolecheid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_retirolecheid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `retirolechedetalle`
  SET `retirolechestatus` = 'ANL',
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `retirolecheid` = v_retirolecheid;

  SET v_headerfecha = CAST(DATE(v_retirolechefecha) AS DATETIME);
  SELECT IFNULL(SUM(`retirolechelitros`), 0)
  INTO v_total_litros
  FROM `retirolechedetalle`
  WHERE `empresaid` = v_empresaid
    AND `fundoid` = v_fundoid
    AND DATE(`retirolechefecha`) = DATE(v_retirolechefecha)
    AND `retirolechestatus` = 'CN';

  INSERT INTO `retiroleche` (
    `empresaid`,
    `fundoid`,
    `retirolechefecha`,
    `retirolechetotlitros`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  ) VALUES (
    v_empresaid,
    v_fundoid,
    v_headerfecha,
    v_total_litros,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  )
  ON DUPLICATE KEY UPDATE
    `retirolechetotlitros` = v_total_litros,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_retirolechedetalle_listar//
CREATE PROCEDURE sp_retirolechedetalle_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroFundoid VARCHAR(255);
  DECLARE v_filtroFundoestanqueid VARCHAR(255);
  DECLARE v_filtroClienteid VARCHAR(255);
  DECLARE v_filtroRetirolechestatus VARCHAR(255);
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;

  SET v_filtroFundoid = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid')),'null'),'');
  SET v_filtroFundoestanqueid = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoestanqueid')),'null'),'');
  SET v_filtroClienteid = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroClienteid')),'null'),'');
  SET v_filtroRetirolechestatus = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroRetirolechestatus')),'null'),'');
  SET v_filtroFechaDesde = COALESCE(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')),'null'),''), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')),'null'),''), CURRENT_DATE());

  SELECT
    t.`retirolecheid`,
    t.`empresaid`,
    t.`fundoid`,
    f.`fundonombre`,
    t.`fundoestanqueid`,
    fe.`fundoestanquedsc`,
    t.`clienteid`,
    c.`clienterazonsocial`,
    t.`estanqueclientecod`,
    t.`retirolechefecha`,
    t.`retirolechehoraini`,
    t.`retirolechehorafin`,
    t.`retirolechelitros`,
    t.`retirolechetemperatura`,
    t.`retirolecheobservacion`,
    t.`retirolechefoto`,
    t.`retirolechestatus`
  FROM `retirolechedetalle` t
  LEFT JOIN `fundos` f ON t.`fundoid` = f.`fundoid`
  LEFT JOIN `fundosestanques` fe ON t.`fundoestanqueid` = fe.`fundoestanqueid`
  LEFT JOIN `clientes` c ON t.`clienteid` = c.`clienteid`
  WHERE 1=1
    AND (DATE(t.`retirolechefecha`) BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta)
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid)
    AND (v_filtroFundoestanqueid IS NULL OR v_filtroFundoestanqueid = '' OR t.`fundoestanqueid` = v_filtroFundoestanqueid)
    AND (v_filtroClienteid IS NULL OR v_filtroClienteid = '' OR t.`clienteid` = v_filtroClienteid)
    AND (v_filtroRetirolechestatus IS NULL OR v_filtroRetirolechestatus = '' OR t.`retirolechestatus` = v_filtroRetirolechestatus)
    AND t.`fundoid` IN (
      SELECT sq.`fundoid`
      FROM `usuariosfundos` sq
      WHERE sq.`usuarioid` = p_in_usuarioid);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_retirolechedetalle_consulta_por_id//
CREATE PROCEDURE sp_retirolechedetalle_consulta_por_id(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_retirolecheid int(11);
  SET v_retirolecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolecheid')) AS SIGNED);

  SELECT
    t.`retirolecheid`,
    t.`empresaid`,
    t.`fundoid`,
    f.`fundonombre`,
    t.`fundoestanqueid`,
    fe.`fundoestanquedsc`,
    t.`clienteid`,
    c.`clienterazonsocial`,
    t.`estanqueclientecod`,
    t.`retirolechefecha`,
    t.`retirolechehoraini`,
    t.`retirolechehorafin`,
    t.`retirolechelitros`,
    t.`retirolechetemperatura`,
    t.`retirolecheobservacion`,
    t.`retirolechefoto`,
    t.`retirolechestatus`
  FROM `retirolechedetalle` t
  LEFT JOIN `fundos` f ON t.`fundoid` = f.`fundoid`
  LEFT JOIN `fundosestanques` fe ON t.`fundoestanqueid` = fe.`fundoestanqueid`
  LEFT JOIN `clientes` c ON t.`clienteid` = c.`clienteid`
  WHERE t.`retirolecheid` = v_retirolecheid
  LIMIT 1;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
