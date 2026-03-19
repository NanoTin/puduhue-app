DELIMITER //
DROP PROCEDURE IF EXISTS sp_prodleche_insertar//
CREATE PROCEDURE sp_prodleche_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_prodlecheid int(11);
  DECLARE v_prodlechestatus varchar(3);
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_prodlechefecha datetime;
  DECLARE v_prodlechehoraini time;
  DECLARE v_prodlechehorafin time;
  DECLARE v_prodlechehorario char(2);
  DECLARE v_pl_erpestablecimientocod varchar(50);
  DECLARE v_pl_erplotecod varchar(50);
  DECLARE v_pl_erpleche_invbodegacod varchar(50);
  DECLARE v_pl_erpleche_invcateganimalcod varchar(50);
  DECLARE v_prodlechetotlitros int(6);
  DECLARE v_prodlechetotvacas int(4);
  DECLARE v_prodlecheventatotlitros int(6);
  DECLARE v_prodlecheventatotvacas int(4);
  DECLARE v_prodlecheventalitrosxvaca float;
  DECLARE v_prodlecheobservacion varchar(50);
  DECLARE v_detalle_count int(11);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  -- Validar que no exista un registro con Empresa-Fundo-Fecha-Horario igual
  IF EXISTS(select * from `prodleche`
            WHERE `empresaid` = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED)
              AND `fundoid` = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED)
              AND `prodlechefecha` = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechefecha')) AS DATETIME)
              AND `prodlechehorario` = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehorario'))
           ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Ya existe un registro con la misma Empresa, Fundo, Fecha y Horario');
    LEAVE sp_main;
  END IF;

  SET     v_prodlechestatus = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechestatus')),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_prodlechefecha = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechefecha')) AS DATETIME),
    v_prodlechehoraini = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehoraini')) AS TIME),
    v_prodlechehorafin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehorafin')) AS TIME),
    v_prodlechehorario = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehorario')),
    v_pl_erpestablecimientocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erpestablecimientocod')),
    v_pl_erplotecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erplotecod')),
    v_pl_erpleche_invbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erpleche_invbodegacod')),
    v_pl_erpleche_invcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erpleche_invcateganimalcod')),
    v_prodlechetotlitros = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetotlitros')) AS SIGNED),
    v_prodlechetotvacas = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetotvacas')) AS SIGNED),
    v_prodlecheventatotlitros = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventatotlitros')) AS SIGNED),
    v_prodlecheventatotvacas = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventatotvacas')) AS SIGNED),
    v_prodlecheventalitrosxvaca = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventalitrosxvaca')) AS DECIMAL(20,6)),
    v_prodlecheobservacion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheobservacion'));

  SET v_detalle_count = JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.detalles'));
  IF v_detalle_count IS NULL OR v_detalle_count = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'detalles array is required');
    LEAVE sp_main;
  END IF;

  INSERT INTO `prodleche` (
    `prodlechestatus`,
    `empresaid`,
    `fundoid`,
    `prodlechefecha`,
    `prodlechehoraini`,
    `prodlechehorafin`,
    `prodlechehorario`,
    `pl_erpestablecimientocod`,
    `pl_erplotecod`,
    `pl_erpleche_invbodegacod`,
    `pl_erpleche_invcateganimalcod`,
    `prodlechetotlitros`,
    `prodlechetotvacas`,
    `prodlecheventatotlitros`,
    `prodlecheventatotvacas`,
    `prodlecheventalitrosxvaca`,
    `prodlecheobservacion`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    'PND',
    v_empresaid,
    v_fundoid,
    v_prodlechefecha,
    v_prodlechehoraini,
    v_prodlechehorafin,
    v_prodlechehorario,
    v_pl_erpestablecimientocod,
    v_pl_erplotecod,
    v_pl_erpleche_invbodegacod,
    v_pl_erpleche_invcateganimalcod,
    v_prodlechetotlitros,
    v_prodlechetotvacas,
    v_prodlecheventatotlitros,
    v_prodlecheventatotvacas,
    v_prodlecheventalitrosxvaca,
    v_prodlecheobservacion,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET v_prodlecheid = LAST_INSERT_ID();

  INSERT INTO `prodlechedetalle` (
    `prodlecheid`,
    `prodlechetipoid`,
    `pldetlitros`,
    `pldetvacas`,
    `pldetlitrosxvaca`,
    `prodlechecod`,
    `erpdocumentocod`
  )
  WITH RECURSIVE seq AS (
    SELECT 0 AS idx
    UNION ALL
    SELECT idx + 1 FROM seq WHERE idx + 1 < v_detalle_count
  )
  SELECT
    v_prodlecheid,
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].prodlechetipoid'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].pldetlitros'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].pldetvacas'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].pldetlitrosxvaca'))) AS DECIMAL(20,6)),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].prodlechecod'))),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].erpdocumentocod')))
  FROM seq;

  -- Generic log insert
  INSERT INTO `prodlechelog` (
    `prodlecheid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_prodlecheid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK', 'prodlecheid', v_prodlecheid);
END//

DROP PROCEDURE IF EXISTS sp_prodleche_editar//
CREATE PROCEDURE sp_prodleche_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_prodlecheid int(11);
  DECLARE v_prodlechestatus varchar(3);
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_prodlechefecha datetime;
  DECLARE v_prodlechehoraini time;
  DECLARE v_prodlechehorafin time;
  DECLARE v_prodlechehorario char(2);
  DECLARE v_pl_erpestablecimientocod varchar(50);
  DECLARE v_pl_erplotecod varchar(50);
  DECLARE v_pl_erpleche_invbodegacod varchar(50);
  DECLARE v_pl_erpleche_invcateganimalcod varchar(50);
  DECLARE v_prodlechetotlitros int(6);
  DECLARE v_prodlechetotvacas int(4);
  DECLARE v_prodlecheventatotlitros int(6);
  DECLARE v_prodlecheventatotvacas int(4);
  DECLARE v_prodlecheventalitrosxvaca float;
  DECLARE v_prodlecheobservacion varchar(50);
  DECLARE v_detalle_count int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_prodlecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheid')) AS SIGNED),
    v_prodlechestatus = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechestatus')),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_prodlechefecha = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechefecha')) AS DATETIME),
    v_prodlechehoraini = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehoraini')) AS TIME),
    v_prodlechehorafin = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehorafin')) AS TIME),
    v_prodlechehorario = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehorario')),
    v_pl_erpestablecimientocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erpestablecimientocod')),
    v_pl_erplotecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erplotecod')),
    v_pl_erpleche_invbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erpleche_invbodegacod')),
    v_pl_erpleche_invcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pl_erpleche_invcateganimalcod')),
    v_prodlechetotlitros = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetotlitros')) AS SIGNED),
    v_prodlechetotvacas = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetotvacas')) AS SIGNED),
    v_prodlecheventatotlitros = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventatotlitros')) AS SIGNED),
    v_prodlecheventatotvacas = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventatotvacas')) AS SIGNED),
    v_prodlecheventalitrosxvaca = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheventalitrosxvaca')) AS DECIMAL(20,6)),
    v_prodlecheobservacion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheobservacion'));

  SET v_detalle_count = JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.detalles'));
  IF v_detalle_count IS NULL OR v_detalle_count = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'detalles array is required');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `prodleche` WHERE `prodlecheid` = v_prodlecheid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  -- Validar que no exista un registro con Empresa-Fundo-Fecha-Horario igual y distinto prodlecheid
  IF EXISTS(select * from `prodleche`
            WHERE `empresaid` = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED)
              AND `fundoid` = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED)
              AND `prodlechefecha` = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechefecha')) AS DATETIME)
              AND `prodlechehorario` = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechehorario'))
              AND `prodlecheid` <> v_prodlecheid
           ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Ya existe un registro con la misma Empresa, Fundo, Fecha y Horario');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('prodlecheid', `prodleche`.`prodlecheid`, 'prodlechestatus', `prodleche`.`prodlechestatus`, 'empresaid', `prodleche`.`empresaid`, 'fundoid', `prodleche`.`fundoid`, 'prodlechefecha', `prodleche`.`prodlechefecha`, 'prodlechehoraini', `prodleche`.`prodlechehoraini`, 'prodlechehorafin', `prodleche`.`prodlechehorafin`, 'prodlechehorario', `prodleche`.`prodlechehorario`, 'pl_erpestablecimientocod', `prodleche`.`pl_erpestablecimientocod`, 'pl_erplotecod', `prodleche`.`pl_erplotecod`, 'pl_erpleche_invbodegacod', `prodleche`.`pl_erpleche_invbodegacod`, 'pl_erpleche_invcateganimalcod', `prodleche`.`pl_erpleche_invcateganimalcod`, 'prodlechetotlitros', `prodleche`.`prodlechetotlitros`, 'prodlechetotvacas', `prodleche`.`prodlechetotvacas`, 'prodlecheventatotlitros', `prodleche`.`prodlecheventatotlitros`, 'prodlecheventatotvacas', `prodleche`.`prodlecheventatotvacas`, 'prodlecheventalitrosxvaca', `prodleche`.`prodlecheventalitrosxvaca`, 'prodlecheobservacion', `prodleche`.`prodlecheobservacion`, 'auditcreacionusuarioid', `prodleche`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `prodleche`.`auditcreaciondispositivo`, 'auditcreacionip', `prodleche`.`auditcreacionip`, 'auditcreacionfechahora', `prodleche`.`auditcreacionfechahora`, 'auditedicionusuarioid', `prodleche`.`auditedicionusuarioid`, 'auditediciondispositivo', `prodleche`.`auditediciondispositivo`, 'auditedicionip', `prodleche`.`auditedicionip`, 'auditedicionfechahora', `prodleche`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `prodleche`
  WHERE `prodlecheid` = v_prodlecheid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `prodlechelog` (
    `prodlecheid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_prodlecheid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `prodleche`
  SET `prodlechestatus` = 'PND',
    `empresaid` = v_empresaid,
    `fundoid` = v_fundoid,
    `prodlechefecha` = v_prodlechefecha,
    `prodlechehoraini` = v_prodlechehoraini,
    `prodlechehorafin` = v_prodlechehorafin,
    `prodlechehorario` = v_prodlechehorario,
    `pl_erpestablecimientocod` = v_pl_erpestablecimientocod,
    `pl_erplotecod` = v_pl_erplotecod,
    `pl_erpleche_invbodegacod` = v_pl_erpleche_invbodegacod,
    `pl_erpleche_invcateganimalcod` = v_pl_erpleche_invcateganimalcod,
    `prodlechetotlitros` = v_prodlechetotlitros,
    `prodlechetotvacas` = v_prodlechetotvacas,
    `prodlecheventatotlitros` = v_prodlecheventatotlitros,
    `prodlecheventatotvacas` = v_prodlecheventatotvacas,
    `prodlecheventalitrosxvaca` = v_prodlecheventalitrosxvaca,
    `prodlecheobservacion` = v_prodlecheobservacion,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `prodlecheid` = v_prodlecheid;

  INSERT INTO `prodlechedetalle` (
    `prodlecheid`,
    `prodlechetipoid`,
    `pldetlitros`,
    `pldetvacas`,
    `pldetlitrosxvaca`,
    `prodlechecod`,
    `erpdocumentocod`
  )
  WITH RECURSIVE seq AS (
    SELECT 0 AS idx
    UNION ALL
    SELECT idx + 1 FROM seq WHERE idx + 1 < v_detalle_count
  )
  SELECT
    v_prodlecheid,
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].prodlechetipoid'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].pldetlitros'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].pldetvacas'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].pldetlitrosxvaca'))) AS DECIMAL(20,6)),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].prodlechecod'))),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].erpdocumentocod')))
  FROM seq
  ON DUPLICATE KEY UPDATE
    `pldetlitros` = VALUES(`pldetlitros`),
    `pldetvacas` = VALUES(`pldetvacas`),
    `pldetlitrosxvaca` = VALUES(`pldetlitrosxvaca`),
    `erpdocumentocod` = VALUES(`erpdocumentocod`);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_prodleche_anular//
CREATE PROCEDURE sp_prodleche_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_prodlecheid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET v_prodlecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `prodleche` WHERE `prodlecheid` = v_prodlecheid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('prodlecheid', `prodleche`.`prodlecheid`, 'prodlechestatus', `prodleche`.`prodlechestatus`, 'empresaid', `prodleche`.`empresaid`, 'fundoid', `prodleche`.`fundoid`, 'prodlechefecha', `prodleche`.`prodlechefecha`, 'prodlechehoraini', `prodleche`.`prodlechehoraini`, 'prodlechehorafin', `prodleche`.`prodlechehorafin`, 'prodlechehorario', `prodleche`.`prodlechehorario`, 'pl_erpestablecimientocod', `prodleche`.`pl_erpestablecimientocod`, 'pl_erplotecod', `prodleche`.`pl_erplotecod`, 'pl_erpleche_invbodegacod', `prodleche`.`pl_erpleche_invbodegacod`, 'pl_erpleche_invcateganimalcod', `prodleche`.`pl_erpleche_invcateganimalcod`, 'prodlechetotlitros', `prodleche`.`prodlechetotlitros`, 'prodlechetotvacas', `prodleche`.`prodlechetotvacas`, 'prodlecheventatotlitros', `prodleche`.`prodlecheventatotlitros`, 'prodlecheventatotvacas', `prodleche`.`prodlecheventatotvacas`, 'prodlecheventalitrosxvaca', `prodleche`.`prodlecheventalitrosxvaca`, 'prodlecheobservacion', `prodleche`.`prodlecheobservacion`, 'auditcreacionusuarioid', `prodleche`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `prodleche`.`auditcreaciondispositivo`, 'auditcreacionip', `prodleche`.`auditcreacionip`, 'auditcreacionfechahora', `prodleche`.`auditcreacionfechahora`, 'auditedicionusuarioid', `prodleche`.`auditedicionusuarioid`, 'auditediciondispositivo', `prodleche`.`auditediciondispositivo`, 'auditedicionip', `prodleche`.`auditedicionip`, 'auditedicionfechahora', `prodleche`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `prodleche`
  WHERE `prodlecheid` = v_prodlecheid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `prodlechelog` (
    `prodlecheid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_prodlecheid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `prodleche`
  SET
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `prodlecheid` = v_prodlecheid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_prodleche_listar//
CREATE PROCEDURE sp_prodleche_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroProdlecheid VARCHAR(255);
  DECLARE v_filtroProdlechestatus VARCHAR(255);
  DECLARE v_filtroEmpresaid VARCHAR(255);
  DECLARE v_filtroFundoid VARCHAR(255);
  DECLARE v_filtroProdlechehorario VARCHAR(255);
  DECLARE v_filtroProdlecheobservacion VARCHAR(255);
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;
  -- JSON null arrives as the literal "null" (lowercase), not "NULL", so normalize both and blanks to SQL NULL
  SET v_filtroProdlecheid = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlecheid')), 'null'), 'NULL'), '');
  SET v_filtroProdlechestatus = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlechestatus')), 'null'), 'NULL'), '');
  SET v_filtroEmpresaid = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresaid')), 'null'), 'NULL'), '');
  SET v_filtroFundoid = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid')), 'null'), 'NULL'), '');
  SET v_filtroProdlechehorario = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlechehorario')), 'null'), 'NULL'), '');
  SET v_filtroProdlecheobservacion = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlecheobservacion')), 'null'), 'NULL'), '');
  SET v_filtroFechaDesde = COALESCE(NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), 'null'), 'NULL'), ''), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), 'null'), 'NULL'), ''), CURRENT_DATE());
  SELECT
    t.`prodlecheid`,
    t.`prodlechestatus`,
    t.`empresaid`,
    empresas.razonsocial AS `razonsocial`,
    t.`fundoid`,
    fundos.fundonombre AS `fundonombre`,
    t.`prodlechefecha`,
    t.`prodlechehoraini`,
    t.`prodlechehorafin`,
    t.`prodlechehorario`,
    t.`pl_erpestablecimientocod`,
    t.`pl_erplotecod`,
    t.`pl_erpleche_invbodegacod`,
    t.`pl_erpleche_invcateganimalcod`,
    t.`prodlechetotlitros`,
    t.`prodlechetotvacas`,
    t.`prodlecheventatotlitros`,
    t.`prodlecheventatotvacas`,
    t.`prodlecheventalitrosxvaca`,
    t.`prodlecheobservacion`
  FROM `prodleche` t
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  WHERE 1=1
    AND (t.`prodlechefecha` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta)
    AND (v_filtroProdlecheid IS NULL OR v_filtroProdlecheid = '' OR t.`prodlecheid` = v_filtroProdlecheid)
    AND (v_filtroProdlechestatus IS NULL OR v_filtroProdlechestatus = '' OR t.`prodlechestatus` LIKE CONCAT('%', v_filtroProdlechestatus, '%'))
    AND (v_filtroEmpresaid IS NULL OR v_filtroEmpresaid = '' OR t.`empresaid` = v_filtroEmpresaid)
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid)
    AND (v_filtroProdlechehorario IS NULL OR v_filtroProdlechehorario = '' OR t.`prodlechehorario` LIKE CONCAT('%', v_filtroProdlechehorario, '%'))
    AND (v_filtroProdlecheobservacion IS NULL OR v_filtroProdlecheobservacion = '' OR t.`prodlecheobservacion` LIKE CONCAT('%', v_filtroProdlecheobservacion, '%'))
    AND t.fundoid IN (
      SELECT uf.fundoid
      FROM usuariosfundos uf
      WHERE uf.usuarioid = p_in_usuarioid
    )
  ORDER BY t.`prodlechefecha` DESC, t.`prodlechehorario` DESC, fundos.fundonombre ASC, t.`prodlecheid` DESC;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_prodleche_consulta_por_id//
CREATE PROCEDURE sp_prodleche_consulta_por_id(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroProdlecheid int(11);

  SET v_filtroProdlecheid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroProdlecheid')), 0) AS SIGNED);

  IF v_filtroProdlecheid = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'ID is required');
    LEAVE sp_main;
  END IF;

  SELECT
    t.`prodlecheid`,
    t.`prodlechestatus`, 
    CASE WHEN t.`prodlechestatus` = 'CN' THEN 'CONFIRMADO' WHEN t.`prodlechestatus` = 'AN' THEN 'ANULADO' WHEN t.`prodlechestatus` = 'PND' THEN 'PENDIENTE ERP' ELSE 'DESCONOCIDO' END AS `prodlechestatus_desc`,
    empresas.razonsocial AS `empresas_razonsocial`,
    fundos.fundonombre AS `fundos_fundonombre`,
    t.`prodlechefecha`,
    t.`prodlechehoraini`,
    t.`prodlechehorafin`,
    t.`prodlechehorario`,
    t.`pl_erpestablecimientocod`,
    t.`pl_erplotecod`,
    t.`pl_erpleche_invbodegacod`,
    t.`pl_erpleche_invcateganimalcod`,
    t.`prodlechetotlitros`,
    t.`prodlechetotvacas`,
    t.`prodlecheventatotlitros`,
    t.`prodlecheventatotvacas`,
    t.`prodlecheventalitrosxvaca`,
    t.`prodlecheobservacion`
  FROM `prodleche` t
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  WHERE (t.`prodlecheid` = v_filtroProdlecheid);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
