DELIMITER //
DROP PROCEDURE IF EXISTS sp_empresas_insertar//
CREATE PROCEDURE sp_empresas_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_empresacod varchar(12);
  DECLARE v_empresarut varchar(12);
  DECLARE v_razonsocial varchar(100);
  DECLARE v_giro varchar(100);
  DECLARE v_empresaemail varchar(100);
  DECLARE v_contactonombre varchar(100);
  DECLARE v_contactoemail varchar(100);
  DECLARE v_contactocelular varchar(12);
  DECLARE v_empresaiderp varchar(50);
  DECLARE v_empapikeyhash varchar(255);
  DECLARE v_empapikeyactiva tinyint(1);
  DECLARE v_empapikeyfechagen datetime;
  DECLARE v_empapikeyultuso datetime;
  DECLARE v_empapikeyipultuso varchar(50);
  DECLARE v_empresaactivo tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_empresacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresacod')),
    v_empresarut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresarut')),
    v_razonsocial = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.razonsocial')),
    v_giro = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.giro')),
    v_empresaemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaemail')),
    v_contactonombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.contactonombre')),
    v_contactoemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.contactoemail')),
    v_contactocelular = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.contactocelular')),
    v_empresaiderp = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaiderp')),
    v_empapikeyhash = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyhash')),
    v_empapikeyactiva = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyactiva')) AS SIGNED),
    v_empapikeyfechagen = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyfechagen')) AS DATETIME),
    v_empapikeyultuso = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyultuso')) AS DATETIME),
    v_empapikeyipultuso = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyipultuso')),
    v_empresaactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaactivo')) AS SIGNED);

  INSERT INTO `empresas` (
    `empresacod`,
    `empresarut`,
    `razonsocial`,
    `giro`,
    `empresaemail`,
    `contactonombre`,
    `contactoemail`,
    `contactocelular`,
    `empresaiderp`,
    `empapikeyhash`,
    `empapikeyactiva`,
    `empapikeyfechagen`,
    `empapikeyultuso`,
    `empapikeyipultuso`,
    `empresaactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_empresacod,
    v_empresarut,
    v_razonsocial,
    v_giro,
    v_empresaemail,
    v_contactonombre,
    v_contactoemail,
    v_contactocelular,
    v_empresaiderp,
    v_empapikeyhash,
    v_empapikeyactiva,
    v_empapikeyfechagen,
    v_empapikeyultuso,
    v_empapikeyipultuso,
    v_empresaactivo,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `empresaslog` (
    `empresaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logfechahora`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    LAST_INSERT_ID(),
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    NOW(),
    'INS',
    p_in_json,
    NULL
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_empresas_editar//
CREATE PROCEDURE sp_empresas_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_empresaid int(11);
  DECLARE v_empresacod varchar(12);
  DECLARE v_empresarut varchar(12);
  DECLARE v_razonsocial varchar(100);
  DECLARE v_giro varchar(100);
  DECLARE v_empresaemail varchar(100);
  DECLARE v_contactonombre varchar(100);
  DECLARE v_contactoemail varchar(100);
  DECLARE v_contactocelular varchar(12);
  DECLARE v_empresaiderp varchar(50);
  DECLARE v_empapikeyhash varchar(255);
  DECLARE v_empapikeyactiva tinyint(1);
  DECLARE v_empapikeyfechagen datetime;
  DECLARE v_empapikeyultuso datetime;
  DECLARE v_empapikeyipultuso varchar(50);
  DECLARE v_empresaactivo tinyint(1);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_empresacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresacod')),
    v_empresarut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresarut')),
    v_razonsocial = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.razonsocial')),
    v_giro = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.giro')),
    v_empresaemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaemail')),
    v_contactonombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.contactonombre')),
    v_contactoemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.contactoemail')),
    v_contactocelular = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.contactocelular')),
    v_empresaiderp = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaiderp')),
    v_empapikeyhash = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyhash')),
    v_empapikeyactiva = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyactiva')) AS SIGNED),
    v_empapikeyfechagen = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyfechagen')) AS DATETIME),
    v_empapikeyultuso = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyultuso')) AS DATETIME),
    v_empapikeyipultuso = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empapikeyipultuso')),
    v_empresaactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaactivo')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `empresas` WHERE `empresaid` = v_empresaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('empresaid', `empresas`.`empresaid`, 'empresacod', `empresas`.`empresacod`, 'empresarut', `empresas`.`empresarut`, 'razonsocial', `empresas`.`razonsocial`, 'giro', `empresas`.`giro`, 'empresaemail', `empresas`.`empresaemail`, 'contactonombre', `empresas`.`contactonombre`, 'contactoemail', `empresas`.`contactoemail`, 'contactocelular', `empresas`.`contactocelular`, 'empresaiderp', `empresas`.`empresaiderp`, 'empapikeyhash', `empresas`.`empapikeyhash`, 'empapikeyactiva', `empresas`.`empapikeyactiva`, 'empapikeyfechagen', `empresas`.`empapikeyfechagen`, 'empapikeyultuso', `empresas`.`empapikeyultuso`, 'empapikeyipultuso', `empresas`.`empapikeyipultuso`, 'empresaactivo', `empresas`.`empresaactivo`, 'auditcreacionusuarioid', `empresas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `empresas`.`auditcreaciondispositivo`, 'auditcreacionip', `empresas`.`auditcreacionip`, 'auditcreacionfechahora', `empresas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `empresas`.`auditedicionusuarioid`, 'auditediciondispositivo', `empresas`.`auditediciondispositivo`, 'auditedicionip', `empresas`.`auditedicionip`, 'auditedicionfechahora', `empresas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `empresas`
  WHERE `empresaid` = v_empresaid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `empresaslog` (
    `empresaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logfechahora`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_empresaid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    NOW(),
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `empresas`
  SET `empresacod` = v_empresacod,
    `empresarut` = v_empresarut,
    `razonsocial` = v_razonsocial,
    `giro` = v_giro,
    `empresaemail` = v_empresaemail,
    `contactonombre` = v_contactonombre,
    `contactoemail` = v_contactoemail,
    `contactocelular` = v_contactocelular,
    `empresaiderp` = v_empresaiderp,
    `empapikeyhash` = v_empapikeyhash,
    `empapikeyactiva` = v_empapikeyactiva,
    `empapikeyfechagen` = v_empapikeyfechagen,
    `empapikeyultuso` = v_empapikeyultuso,
    `empapikeyipultuso` = v_empapikeyipultuso,
    `empresaactivo` = v_empresaactivo,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `empresaid` = v_empresaid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_empresas_anular//
CREATE PROCEDURE sp_empresas_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_empresaid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `empresas` WHERE `empresaid` = v_empresaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('empresaid', `empresas`.`empresaid`, 'empresacod', `empresas`.`empresacod`, 'empresarut', `empresas`.`empresarut`, 'razonsocial', `empresas`.`razonsocial`, 'giro', `empresas`.`giro`, 'empresaemail', `empresas`.`empresaemail`, 'contactonombre', `empresas`.`contactonombre`, 'contactoemail', `empresas`.`contactoemail`, 'contactocelular', `empresas`.`contactocelular`, 'empresaiderp', `empresas`.`empresaiderp`, 'empapikeyhash', `empresas`.`empapikeyhash`, 'empapikeyactiva', `empresas`.`empapikeyactiva`, 'empapikeyfechagen', `empresas`.`empapikeyfechagen`, 'empapikeyultuso', `empresas`.`empapikeyultuso`, 'empapikeyipultuso', `empresas`.`empapikeyipultuso`, 'empresaactivo', `empresas`.`empresaactivo`, 'auditcreacionusuarioid', `empresas`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `empresas`.`auditcreaciondispositivo`, 'auditcreacionip', `empresas`.`auditcreacionip`, 'auditcreacionfechahora', `empresas`.`auditcreacionfechahora`, 'auditedicionusuarioid', `empresas`.`auditedicionusuarioid`, 'auditediciondispositivo', `empresas`.`auditediciondispositivo`, 'auditedicionip', `empresas`.`auditedicionip`, 'auditedicionfechahora', `empresas`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `empresas`
  WHERE `empresaid` = v_empresaid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `empresaslog` (
    `empresaid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logfechahora`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_empresaid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    NOW(),
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `empresas`
  SET `empresaactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `empresaid` = v_empresaid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_empresas_listar//
CREATE PROCEDURE sp_empresas_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroEmpresarut VARCHAR(255);
  DECLARE v_filtroRazonsocial VARCHAR(255);
  DECLARE v_filtroEmpresaactivo VARCHAR(255);
  SET v_filtroEmpresarut = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresarut'));
  SET v_filtroRazonsocial = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroRazonsocial'));
  SET v_filtroEmpresaactivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresaactivo')), 'null'),'');

  SELECT
    t.`empresacod`,
    t.`empresaid`,
    t.`empresarut`,
    t.`razonsocial`,
    t.`giro`,
    t.`empresaemail`,
    t.`contactonombre`,
    t.`contactoemail`,
    t.`contactocelular`,
    t.`empresaiderp`,
    t.`empapikeyactiva`,
    t.`empapikeyfechagen`,
    t.`empapikeyultuso`,
    t.`empapikeyipultuso`,
    t.`empresaactivo`
  FROM `empresas` t
  WHERE 1=1
    AND (v_filtroEmpresarut IS NULL OR v_filtroEmpresarut = '' OR t.`empresarut` LIKE CONCAT('%', v_filtroEmpresarut, '%'))
    AND (v_filtroRazonsocial IS NULL OR v_filtroRazonsocial = '' OR t.`razonsocial` LIKE CONCAT('%', v_filtroRazonsocial, '%'))
    AND (v_filtroEmpresaactivo IS NULL OR v_filtroEmpresaactivo = '' OR t.`empresaactivo` = v_filtroEmpresaactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
