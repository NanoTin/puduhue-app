DELIMITER //
DROP PROCEDURE IF EXISTS sp_retiroleche_insertar//
CREATE PROCEDURE sp_retiroleche_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_retirolechefecha datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_retirolechefecha = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechefecha')) AS DATETIME);

  INSERT INTO `retiroleche` (
    `empresaid`,
    `fundoid`,
    `retirolechefecha`,
    `prodlechestatus`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`,
    `auditcreacionfechahora`
  )
  VALUES (
    v_empresaid,
    v_fundoid,
    v_retirolechefecha,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `retirolechelog` (
    `retirolecheid`,
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

DROP PROCEDURE IF EXISTS sp_retiroleche_editar//
CREATE PROCEDURE sp_retiroleche_editar(
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
  DECLARE v_prodlechestatus varchar(3);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_retirolecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolecheid')) AS SIGNED),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_retirolechefecha = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolechefecha')) AS DATETIME),
    v_prodlechestatus = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechestatus'));

  IF NOT EXISTS (SELECT 1 FROM `retiroleche` WHERE `retirolecheid` = v_retirolecheid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('retirolecheid', `retiroleche`.`retirolecheid`, 'empresaid', `retiroleche`.`empresaid`, 'fundoid', `retiroleche`.`fundoid`, 'retirolechefecha', `retiroleche`.`retirolechefecha`, 'prodlechestatus', `retiroleche`.`prodlechestatus`, 'auditcreacionusuarioid', `retiroleche`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `retiroleche`.`auditcreaciondispositivo`, 'auditcreacionip', `retiroleche`.`auditcreacionip`, 'auditcreacionfechahora', `retiroleche`.`auditcreacionfechahora`, 'auditedicionusuarioid', `retiroleche`.`auditedicionusuarioid`, 'auditediciondispositivo', `retiroleche`.`auditediciondispositivo`, 'auditedicionip', `retiroleche`.`auditedicionip`, 'auditedicionfechahora', `retiroleche`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `retiroleche`
  WHERE `retirolecheid` = v_retirolecheid
  LIMIT 1;

  -- Generic log insert
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

  UPDATE `retiroleche`
  SET `empresaid` = v_empresaid,
    `fundoid` = v_fundoid,
    `retirolechefecha` = v_retirolechefecha,
    `prodlechestatus` = v_prodlechestatus,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `retirolecheid` = v_retirolecheid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_retiroleche_anular//
CREATE PROCEDURE sp_retiroleche_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_retirolecheid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_retirolecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.retirolecheid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `retiroleche` WHERE `retirolecheid` = v_retirolecheid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('retirolecheid', `retiroleche`.`retirolecheid`, 'empresaid', `retiroleche`.`empresaid`, 'fundoid', `retiroleche`.`fundoid`, 'retirolechefecha', `retiroleche`.`retirolechefecha`, 'prodlechestatus', `retiroleche`.`prodlechestatus`, 'auditcreacionusuarioid', `retiroleche`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `retiroleche`.`auditcreaciondispositivo`, 'auditcreacionip', `retiroleche`.`auditcreacionip`, 'auditcreacionfechahora', `retiroleche`.`auditcreacionfechahora`, 'auditedicionusuarioid', `retiroleche`.`auditedicionusuarioid`, 'auditediciondispositivo', `retiroleche`.`auditediciondispositivo`, 'auditedicionip', `retiroleche`.`auditedicionip`, 'auditedicionfechahora', `retiroleche`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `retiroleche`
  WHERE `retirolecheid` = v_retirolecheid
  LIMIT 1;

  -- Generic log insert
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

  UPDATE `retiroleche`
  SET -- TODO: set active/vigency column to 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `retirolecheid` = v_retirolecheid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_retiroleche_listar//
CREATE PROCEDURE sp_retiroleche_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroEmpresaid VARCHAR(255);
  DECLARE v_filtroFundoid VARCHAR(255);
  DECLARE v_filtroProdlechestatus VARCHAR(255);
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;
  SET v_filtroEmpresaid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresaid'));
  SET v_filtroFundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid'));
  SET v_filtroProdlechestatus = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlechestatus'));
  SET v_filtroFechaDesde = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), CURRENT_DATE());
  SELECT
    t.`retirolecheid`,
    empresas.razonsocial AS `empresas_razonsocial`,
    fundos.fundonombre AS `fundos_fundonombre`,
    t.`retirolechefecha`,
    t.`prodlechestatus`
  FROM `retiroleche` t
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  WHERE 1=1
    AND (t.`retirolechefecha` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta)
    AND (v_filtroEmpresaid IS NULL OR v_filtroEmpresaid = '' OR t.`empresaid` = v_filtroEmpresaid)
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid)
    AND (v_filtroProdlechestatus IS NULL OR v_filtroProdlechestatus = '' OR t.`prodlechestatus` LIKE CONCAT('%', v_filtroProdlechestatus, '%'));

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_retiroleche_consulta_por_id//
CREATE PROCEDURE sp_retiroleche_consulta_por_id(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroEmpresaid VARCHAR(255);
  DECLARE v_filtroFundoid VARCHAR(255);
  DECLARE v_filtroRetirolechefecha VARCHAR(255);
  DECLARE v_filtroProdlechestatus VARCHAR(255);
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;
  SET v_filtroEmpresaid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresaid'));
  SET v_filtroFundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid'));
  SET v_filtroRetirolechefecha = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroRetirolechefecha'));
  SET v_filtroProdlechestatus = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlechestatus'));
  SET v_filtroFechaDesde = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), CURRENT_DATE());
  SELECT
    t.`retirolecheid`,
    empresas.razonsocial AS `empresas_razonsocial`,
    fundos.fundonombre AS `fundos_fundonombre`,
    t.`retirolechefecha`,
    t.`prodlechestatus`
  FROM `retiroleche` t
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  WHERE 1=1
    AND (t.`retirolechefecha` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta)
    AND (v_filtroEmpresaid IS NULL OR v_filtroEmpresaid = '' OR t.`empresaid` = v_filtroEmpresaid)
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid)
    AND (v_filtroRetirolechefecha IS NULL OR v_filtroRetirolechefecha = '' OR t.`retirolechefecha` = v_filtroRetirolechefecha)
    AND (v_filtroProdlechestatus IS NULL OR v_filtroProdlechestatus = '' OR t.`prodlechestatus` LIKE CONCAT('%', v_filtroProdlechestatus, '%'));

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
