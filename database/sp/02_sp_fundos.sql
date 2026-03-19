DELIMITER //
DROP PROCEDURE IF EXISTS sp_fundos_insertar//
CREATE PROCEDURE sp_fundos_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundonombre varchar(50);
  DECLARE v_fundotipoid int(11);
  DECLARE v_empresaid int(11);
  DECLARE v_erpestablecimientocod varchar(50);
  DECLARE v_erplotecod varchar(50);
  DECLARE v_erpleche_invbodegacod varchar(50);
  DECLARE v_erpleche_invcateganimalcod varchar(50);
  DECLARE v_fundopabco tinyint(1);
  DECLARE v_fundorup varchar(50);
  DECLARE v_fundoregion varchar(50);
  DECLARE v_fundoprovincia varchar(50);
  DECLARE v_fundocomuna varchar(50);
  DECLARE v_fundodireccion varchar(100);
  DECLARE v_fundolatitud float;
  DECLARE v_fundolongitud float;
  DECLARE v_fundoemail varchar(100);
  DECLARE v_fundoactivo tinyint(1);
  DECLARE v_reporteorden int(4);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundonombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundonombre')),
    v_fundotipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundotipoid')) AS SIGNED),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_erpestablecimientocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpestablecimientocod')),
    v_erplotecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erplotecod')),
    v_erpleche_invbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpleche_invbodegacod')),
    v_erpleche_invcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpleche_invcateganimalcod')),
    v_fundopabco = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundopabco')) AS SIGNED),
    v_fundorup = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundorup')),
    v_fundoregion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoregion')),
    v_fundoprovincia = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoprovincia')),
    v_fundocomuna = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundocomuna')),
    v_fundodireccion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundodireccion')),
    v_fundolatitud = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundolatitud')) AS DECIMAL(20,6)),
    v_fundolongitud = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundolongitud')) AS DECIMAL(20,6)),
    v_fundoemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoemail')),
    v_fundoactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoactivo')) AS SIGNED),
    v_reporteorden = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reporteorden')) AS SIGNED);

  IF EXISTS(SELECT 1 FROM `fundos` WHERE `fundonombre` = v_fundonombre) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El nombre del fundo ya existe');
    LEAVE sp_main;
  END IF;

  IF EXISTS(SELECT 1 FROM `fundos` WHERE `reporteorden` = v_reporteorden) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Orden debe ser unico');
    LEAVE sp_main;
  END IF;

  INSERT INTO `fundos` (
    `fundonombre`,
    `fundotipoid`,
    `empresaid`,
    `erpestablecimientocod`,
    `erplotecod`,
    `erpleche_invbodegacod`,
    `erpleche_invcateganimalcod`,
    `fundopabco`,
    `fundorup`,
    `fundoregion`,
    `fundoprovincia`,
    `fundocomuna`,
    `fundodireccion`,
    `fundolatitud`,
    `fundolongitud`,
    `fundoemail`,
    `fundoactivo`,
    `reporteorden`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_fundonombre,
    v_fundotipoid,
    v_empresaid,
    v_erpestablecimientocod,
    v_erplotecod,
    v_erpleche_invbodegacod,
    v_erpleche_invcateganimalcod,
    v_fundopabco,
    v_fundorup,
    v_fundoregion,
    v_fundoprovincia,
    v_fundocomuna,
    v_fundodireccion,
    v_fundolatitud,
    v_fundolongitud,
    v_fundoemail,
    v_fundoactivo,
    v_reporteorden,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  -- Generic log insert
  INSERT INTO `fundoslog` (
    `fundoid`,
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

DROP PROCEDURE IF EXISTS sp_fundos_editar//
CREATE PROCEDURE sp_fundos_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoid int(11);
  DECLARE v_fundonombre varchar(50);
  DECLARE v_fundotipoid int(11);
  DECLARE v_empresaid int(11);
  DECLARE v_erpestablecimientocod varchar(50);
  DECLARE v_erplotecod varchar(50);
  DECLARE v_erpleche_invbodegacod varchar(50);
  DECLARE v_erpleche_invcateganimalcod varchar(50);
  DECLARE v_fundopabco tinyint(1);
  DECLARE v_fundorup varchar(50);
  DECLARE v_fundoregion varchar(50);
  DECLARE v_fundoprovincia varchar(50);
  DECLARE v_fundocomuna varchar(50);
  DECLARE v_fundodireccion varchar(100);
  DECLARE v_fundolatitud float;
  DECLARE v_fundolongitud float;
  DECLARE v_fundoemail varchar(100);
  DECLARE v_fundoactivo tinyint(1);
  DECLARE v_reporteorden int(4);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_fundonombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundonombre')),
    v_fundotipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundotipoid')) AS SIGNED),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_erpestablecimientocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpestablecimientocod')),
    v_erplotecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erplotecod')),
    v_erpleche_invbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpleche_invbodegacod')),
    v_erpleche_invcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpleche_invcateganimalcod')),
    v_fundopabco = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundopabco')) AS SIGNED),
    v_fundorup = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundorup')),
    v_fundoregion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoregion')),
    v_fundoprovincia = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoprovincia')),
    v_fundocomuna = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundocomuna')),
    v_fundodireccion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundodireccion')),
    v_fundolatitud = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundolatitud')) AS DECIMAL(20,6)),
    v_fundolongitud = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundolongitud')) AS DECIMAL(20,6)),
    v_fundoemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoemail')),
    v_fundoactivo = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoactivo')) AS SIGNED),
    v_reporteorden = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reporteorden')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `fundos` WHERE `fundoid` = v_fundoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  IF EXISTS(SELECT 1 FROM `fundos` WHERE `fundonombre` = v_fundonombre and `fundoid` <> v_fundoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El nombre del fundo ya existe');
    LEAVE sp_main;
  END IF;

  IF EXISTS(SELECT 1 FROM `fundos` WHERE `reporteorden` = v_reporteorden) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Orden debe ser unico');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('fundoid', `fundos`.`fundoid`, 'fundonombre', `fundos`.`fundonombre`, 'fundotipoid', `fundos`.`fundotipoid`, 'empresaid', `fundos`.`empresaid`, 'erpestablecimientocod', `fundos`.`erpestablecimientocod`, 'erplotecod', `fundos`.`erplotecod`, 'erpleche_invbodegacod', `fundos`.`erpleche_invbodegacod`, 'erpleche_invcateganimalcod', `fundos`.`erpleche_invcateganimalcod`, 'fundopabco', `fundos`.`fundopabco`, 'fundorup', `fundos`.`fundorup`, 'fundoregion', `fundos`.`fundoregion`, 'fundoprovincia', `fundos`.`fundoprovincia`, 'fundocomuna', `fundos`.`fundocomuna`, 'fundodireccion', `fundos`.`fundodireccion`, 'fundolatitud', `fundos`.`fundolatitud`, 'fundolongitud', `fundos`.`fundolongitud`, 'fundoemail', `fundos`.`fundoemail`, 'fundoactivo', `fundos`.`fundoactivo`, 'auditcreacionusuarioid', `fundos`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `fundos`.`auditcreaciondispositivo`, 'auditcreacionip', `fundos`.`auditcreacionip`, 'auditcreacionfechahora', `fundos`.`auditcreacionfechahora`, 'auditedicionusuarioid', `fundos`.`auditedicionusuarioid`, 'auditediciondispositivo', `fundos`.`auditediciondispositivo`, 'auditedicionip', `fundos`.`auditedicionip`, 'auditedicionfechahora', `fundos`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `fundos`
  WHERE `fundoid` = v_fundoid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `fundoslog` (
    `fundoid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_fundoid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `fundos`
  SET `fundonombre` = v_fundonombre,
    `fundotipoid` = v_fundotipoid,
    `empresaid` = v_empresaid,
    `erpestablecimientocod` = v_erpestablecimientocod,
    `erplotecod` = v_erplotecod,
    `erpleche_invbodegacod` = v_erpleche_invbodegacod,
    `erpleche_invcateganimalcod` = v_erpleche_invcateganimalcod,
    `fundopabco` = v_fundopabco,
    `fundorup` = v_fundorup,
    `fundoregion` = v_fundoregion,
    `fundoprovincia` = v_fundoprovincia,
    `fundocomuna` = v_fundocomuna,
    `fundodireccion` = v_fundodireccion,
    `fundolatitud` = v_fundolatitud,
    `fundolongitud` = v_fundolongitud,
    `fundoemail` = v_fundoemail,
    `fundoactivo` = v_fundoactivo,
    `reporteorden` = v_reporteorden,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `fundoid` = v_fundoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundos_anular//
CREATE PROCEDURE sp_fundos_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `fundos` WHERE `fundoid` = v_fundoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('fundoid', `fundos`.`fundoid`, 'fundonombre', `fundos`.`fundonombre`, 'fundotipoid', `fundos`.`fundotipoid`, 'empresaid', `fundos`.`empresaid`, 'erpestablecimientocod', `fundos`.`erpestablecimientocod`, 'erplotecod', `fundos`.`erplotecod`, 'erpleche_invbodegacod', `fundos`.`erpleche_invbodegacod`, 'erpleche_invcateganimalcod', `fundos`.`erpleche_invcateganimalcod`, 'fundopabco', `fundos`.`fundopabco`, 'fundorup', `fundos`.`fundorup`, 'fundoregion', `fundos`.`fundoregion`, 'fundoprovincia', `fundos`.`fundoprovincia`, 'fundocomuna', `fundos`.`fundocomuna`, 'fundodireccion', `fundos`.`fundodireccion`, 'fundolatitud', `fundos`.`fundolatitud`, 'fundolongitud', `fundos`.`fundolongitud`, 'fundoemail', `fundos`.`fundoemail`, 'fundoactivo', `fundos`.`fundoactivo`, 'auditcreacionusuarioid', `fundos`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `fundos`.`auditcreaciondispositivo`, 'auditcreacionip', `fundos`.`auditcreacionip`, 'auditcreacionfechahora', `fundos`.`auditcreacionfechahora`, 'auditedicionusuarioid', `fundos`.`auditedicionusuarioid`, 'auditediciondispositivo', `fundos`.`auditediciondispositivo`, 'auditedicionip', `fundos`.`auditedicionip`, 'auditedicionfechahora', `fundos`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `fundos`
  WHERE `fundoid` = v_fundoid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `fundoslog` (
    `fundoid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_fundoid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `fundos`
  SET `fundoactivo` = 0,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip,
    `auditedicionfechahora` = NOW()
  WHERE `fundoid` = v_fundoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundos_listar//
CREATE PROCEDURE sp_fundos_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroFundonombre VARCHAR(255);
  DECLARE v_filtroFundotipoid VARCHAR(255);
  DECLARE v_filtroEmpresaid VARCHAR(255);
  -- DECLARE v_filtroErpestablecimientocod VARCHAR(255);
  -- DECLARE v_filtroErplotecod VARCHAR(255);
  -- DECLARE v_filtroErpleche_invbodegacod VARCHAR(255);
  -- DECLARE v_filtroErpleche_invcateganimalcod VARCHAR(255);
  DECLARE v_filtroFundopabco VARCHAR(255);
  -- DECLARE v_filtroFundorup VARCHAR(255);
  DECLARE v_filtroFundoactivo VARCHAR(255);
  SET v_filtroFundonombre = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundonombre'));
  SET v_filtroFundotipoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundotipoid'));
  SET v_filtroEmpresaid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresaid'));
  -- SET v_filtroErpestablecimientocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErpestablecimientocod'));
  -- SET v_filtroErplotecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErplotecod'));
  -- SET v_filtroErpleche_invbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErpleche_invbodegacod'));
  -- SET v_filtroErpleche_invcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroErpleche_invcateganimalcod'));
  SET v_filtroFundopabco = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundopabco'));
  -- SET v_filtroFundorup = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundorup'));
  SET v_filtroFundoactivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoactivo')), 'NULL'), '');
  SELECT
    t.`fundoid`,
    t.`fundonombre`,
    t.`fundotipoid`,
    fundostipos.fundotipodsc AS `fundostipos_fundotipodsc`,
    t.`empresaid`,
    empresas.razonsocial AS `empresas_razonsocial`,
    t.`erpestablecimientocod`,
    t.`erplotecod`,
    t.`erpleche_invbodegacod`,
    t.`erpleche_invcateganimalcod`,
    t.`fundopabco`,
    t.`fundorup`,
    t.`fundoemail`,
    t.`fundoactivo`,
    t.`reporteorden`
  FROM `fundos` t
  LEFT JOIN `fundostipos` ON t.`fundotipoid` = `fundostipos`.`fundotipoid`
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  WHERE 1=1
    AND (v_filtroFundonombre IS NULL OR v_filtroFundonombre = '' OR t.`fundonombre` LIKE CONCAT('%', v_filtroFundonombre, '%'))
    AND (v_filtroFundotipoid IS NULL OR v_filtroFundotipoid = '' OR t.`fundotipoid` = v_filtroFundotipoid)
    AND (v_filtroEmpresaid IS NULL OR v_filtroEmpresaid = '' OR t.`empresaid` = v_filtroEmpresaid)
    -- AND (v_filtroErpestablecimientocod IS NULL OR v_filtroErpestablecimientocod = '' OR t.`erpestablecimientocod` LIKE CONCAT('%', v_filtroErpestablecimientocod, '%'))
    -- AND (v_filtroErplotecod IS NULL OR v_filtroErplotecod = '' OR t.`erplotecod` LIKE CONCAT('%', v_filtroErplotecod, '%'))
    -- AND (v_filtroErpleche_invbodegacod IS NULL OR v_filtroErpleche_invbodegacod = '' OR t.`erpleche_invbodegacod` LIKE CONCAT('%', v_filtroErpleche_invbodegacod, '%'))
    -- AND (v_filtroErpleche_invcateganimalcod IS NULL OR v_filtroErpleche_invcateganimalcod = '' OR t.`erpleche_invcateganimalcod` LIKE CONCAT('%', v_filtroErpleche_invcateganimalcod, '%'))
    AND (v_filtroFundopabco IS NULL OR v_filtroFundopabco = '' OR t.`fundopabco` = v_filtroFundopabco)
    -- AND (v_filtroFundorup IS NULL OR v_filtroFundorup = '' OR t.`fundorup` LIKE CONCAT('%', v_filtroFundorup, '%'))
    AND (v_filtroFundoactivo IS NULL OR t.`fundoactivo` = v_filtroFundoactivo);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

-- SP para consultar por id
DROP PROCEDURE IF EXISTS sp_fundos_consultar_por_id//
CREATE PROCEDURE sp_fundos_consultar_por_id(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundoid INT;

  SET v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED);

  IF(v_fundoid IS NULL OR v_fundoid = 0) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'fundoid is required');
    LEAVE sp_main;
  END IF;

  SELECT
    t.`fundoid`,
    t.`fundonombre`,
    t.`fundotipoid`,
    fundostipos.fundotipodsc AS `fundostipos_fundotipodsc`,
    t.`empresaid`,
    empresas.razonsocial AS `empresas_razonsocial`,
    t.`erpestablecimientocod`,
    t.`erplotecod`,
    t.`erpleche_invbodegacod`,
    t.`erpleche_invcateganimalcod`,
    t.`fundopabco`,
    t.`fundorup`,
    t.`fundoemail`,
    t.`fundoactivo`,
    t.`reporteorden`
  FROM `fundos` t
  LEFT JOIN `fundostipos` ON t.`fundotipoid` = `fundostipos`.`fundotipoid`
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  WHERE t.`fundoid` = v_fundoid
  LIMIT 1;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//


DELIMITER ;
