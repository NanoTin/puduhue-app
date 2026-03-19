DELIMITER //
DROP PROCEDURE IF EXISTS sp_suplanimal_insertar//
CREATE PROCEDURE sp_suplanimal_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_suplanimalid int(11);
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_invbodegaid int(11);
  DECLARE v_suplanimalfecha datetime;
  DECLARE v_suplanimalobservacion varchar(50);
  DECLARE v_sup_erpestablecimientocod varchar(50);
  DECLARE v_sup_erplotecod varchar(50);
  DECLARE v_sup_erpinvbodegacod varchar(50);
  DECLARE v_detalle_count int(11);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_invbodegaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodegaid')) AS SIGNED),
    v_suplanimalfecha = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalfecha')) AS DATETIME),
    v_suplanimalobservacion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalobservacion')),
    v_sup_erpestablecimientocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpestablecimientocod')),
    v_sup_erplotecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erplotecod')),
    v_sup_erpinvbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpinvbodegacod'));

  SET v_detalle_count = JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.detalles'));
  IF v_detalle_count IS NULL OR v_detalle_count = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'detalles array is required');
    LEAVE sp_main;
  END IF;

  INSERT INTO `suplanimal` (
    `empresaid`,
    `suplanimalstatus`,
    `fundoid`,
    `invbodegaid`,
    `suplanimalfecha`,
    `suplanimalobservacion`,
    `sup_erpestablecimientocod`,
    `sup_erplotecod`,
    `sup_erpinvbodegacod`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_empresaid,
    'PND',
    v_fundoid,
    v_invbodegaid,
    v_suplanimalfecha,
    v_suplanimalobservacion,
    v_sup_erpestablecimientocod,
    v_sup_erplotecod,
    v_sup_erpinvbodegacod,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET v_suplanimalid = LAST_INSERT_ID();

  INSERT INTO `suplanimaldetalle` (
    `suplanimalid`,
    `suplanimallinea`,
    `invcateganimalid`,
    `sup_erpinvcateganimalcod`,
    `invitemid`,
    `sup_erpinvitemcod`,
    `invunidmedid`,
    `sup_erpunidmedcod`,
    `totalconsumido`,
    `totalanimales`,
    `dosisporanimal`,
    `erpdocumentocod`,
    `supdetfechareg`,
    `supdetfechaedt`
  )
  WITH RECURSIVE seq AS (
    SELECT 0 AS idx
    UNION ALL
    SELECT idx + 1 FROM seq WHERE idx + 1 < v_detalle_count
  )
  SELECT
    v_suplanimalid,
    COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].suplanimallinea'))), '') AS SIGNED), seq.idx + 1),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].invcateganimalid'))) AS SIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].sup_erpinvcateganimalcod'))),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].invitemid'))) AS SIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].sup_erpinvitemcod'))),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].invunidmedid'))) AS SIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].sup_erpunidmedcod'))),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].totalconsumido'))) AS DECIMAL(20,6)),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].totalanimales'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].dosisporanimal'))) AS DECIMAL(20,6)),
    COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].erpdocumentocod'))), ''), 'PEND'),
    NOW(),
    NOW()
  FROM seq;

  -- Generic log insert
  INSERT INTO `suplanimallog` (
    `suplanimalid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_suplanimalid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    '{}'
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK', 'suplanimalid', v_suplanimalid);
END//

DROP PROCEDURE IF EXISTS sp_suplanimal_editar//
CREATE PROCEDURE sp_suplanimal_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_suplanimalid int(11);
  DECLARE v_empresaid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_invbodegaid int(11);
  DECLARE v_suplanimalfecha datetime;
  DECLARE v_suplanimalobservacion varchar(50);
  DECLARE v_sup_erpestablecimientocod varchar(50);
  DECLARE v_sup_erplotecod varchar(50);
  DECLARE v_sup_erpinvbodegacod varchar(50);
  DECLARE v_detalle_count int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_suplanimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalid')) AS SIGNED),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_invbodegaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invbodegaid')) AS SIGNED),
    v_suplanimalfecha = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalfecha')) AS DATETIME),
    v_suplanimalobservacion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalobservacion')),
    v_sup_erpestablecimientocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpestablecimientocod')),
    v_sup_erplotecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erplotecod')),
    v_sup_erpinvbodegacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpinvbodegacod'));

  SET v_detalle_count = JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.detalles'));
  IF v_detalle_count IS NULL OR v_detalle_count = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'detalles array is required');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `suplanimal` WHERE `suplanimalid` = v_suplanimalid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('suplanimalid', `suplanimal`.`suplanimalid`, 'empresaid', `suplanimal`.`empresaid`, 'fundoid', `suplanimal`.`fundoid`, 'invbodegaid', `suplanimal`.`invbodegaid`, 'suplanimalfecha', `suplanimal`.`suplanimalfecha`, 'suplanimalobservacion', `suplanimal`.`suplanimalobservacion`, 'sup_erpestablecimientocod', `suplanimal`.`sup_erpestablecimientocod`, 'sup_erplotecod', `suplanimal`.`sup_erplotecod`, 'sup_erpinvbodegacod', `suplanimal`.`sup_erpinvbodegacod`, 'auditcreacionusuarioid', `suplanimal`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `suplanimal`.`auditcreaciondispositivo`, 'auditcreacionip', `suplanimal`.`auditcreacionip`, 'auditcreacionfechahora', `suplanimal`.`auditcreacionfechahora`, 'auditedicionusuarioid', `suplanimal`.`auditedicionusuarioid`, 'auditediciondispositivo', `suplanimal`.`auditediciondispositivo`, 'auditedicionip', `suplanimal`.`auditedicionip`, 'auditedicionfechahora', `suplanimal`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `suplanimal`
  WHERE `suplanimalid` = v_suplanimalid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `suplanimallog` (
    `suplanimalid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_suplanimalid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  DELETE FROM `suplanimaldetalle` WHERE `suplanimalid` = v_suplanimalid AND `erpdocumentocod` = 'PEND';

  INSERT INTO `suplanimaldetalle` (
    `suplanimalid`,
    `suplanimallinea`,
    `invcateganimalid`,
    `sup_erpinvcateganimalcod`,
    `invitemid`,
    `sup_erpinvitemcod`,
    `invunidmedid`,
    `sup_erpunidmedcod`,
    `totalconsumido`,
    `totalanimales`,
    `dosisporanimal`,
    `erpdocumentocod`
  )
  WITH RECURSIVE seq AS (
    SELECT 0 AS idx
    UNION ALL
    SELECT idx + 1 FROM seq WHERE idx + 1 < v_detalle_count
  )
  SELECT
    v_suplanimalid,
    COALESCE(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].suplanimallinea'))), '') AS SIGNED), seq.idx + 1),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].invcateganimalid'))) AS SIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].sup_erpinvcateganimalcod'))),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].invitemid'))) AS SIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].sup_erpinvitemcod'))),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].invunidmedid'))) AS SIGNED),
    JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].sup_erpunidmedcod'))),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].totalconsumido'))) AS DECIMAL(20,6)),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].totalanimales'))) AS SIGNED),
    CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].dosisporanimal'))) AS DECIMAL(20,6)),
    COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].erpdocumentocod'))), ''), 'PEND')
  FROM seq
  WHERE COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalles[', seq.idx, '].erpdocumentocod'))), ''), 'PEND') = 'PEND';

  -- Status = PND si todo el detalle queda como PEND y es igual a la cantidad de detalles.
  -- Status = CN si todo el detalle es distinto de PEND y es igual a la cantidad de detalles.
  UPDATE `suplanimal`
  SET 
    `suplanimalstatus` = CASE WHEN (
        (SELECT COUNT(*) FROM `suplanimaldetalle` WHERE `suplanimalid` = v_suplanimalid AND `erpdocumentocod` = 'PEND') = 0
      ) THEN 'CN'
      WHEN (
        (SELECT COUNT(*) FROM `suplanimaldetalle` WHERE `suplanimalid` = v_suplanimalid AND `erpdocumentocod` = 'PEND') = 
        (SELECT COUNT(*) FROM `suplanimaldetalle` WHERE `suplanimalid` = v_suplanimalid)
      ) THEN 'PND'
      ELSE 'PND' -- Por defecto queda PND si hay mezcla de estados.
    END,
    `suplanimalfecha` = v_suplanimalfecha,
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `suplanimalid` = v_suplanimalid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK', 'suplanimalid', v_suplanimalid);
END//

DROP PROCEDURE IF EXISTS sp_suplanimal_anular//
CREATE PROCEDURE sp_suplanimal_anular(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_suplanimalid int(11);
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_suplanimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `suplanimal` WHERE `suplanimalid` = v_suplanimalid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT('suplanimalid', `suplanimal`.`suplanimalid`, 'empresaid', `suplanimal`.`empresaid`, 'fundoid', `suplanimal`.`fundoid`, 'invbodegaid', `suplanimal`.`invbodegaid`, 'suplanimalfecha', `suplanimal`.`suplanimalfecha`, 'suplanimalobservacion', `suplanimal`.`suplanimalobservacion`, 'sup_erpestablecimientocod', `suplanimal`.`sup_erpestablecimientocod`, 'sup_erplotecod', `suplanimal`.`sup_erplotecod`, 'sup_erpinvbodegacod', `suplanimal`.`sup_erpinvbodegacod`, 'auditcreacionusuarioid', `suplanimal`.`auditcreacionusuarioid`, 'auditcreaciondispositivo', `suplanimal`.`auditcreaciondispositivo`, 'auditcreacionip', `suplanimal`.`auditcreacionip`, 'auditcreacionfechahora', `suplanimal`.`auditcreacionfechahora`, 'auditedicionusuarioid', `suplanimal`.`auditedicionusuarioid`, 'auditediciondispositivo', `suplanimal`.`auditediciondispositivo`, 'auditedicionip', `suplanimal`.`auditedicionip`, 'auditedicionfechahora', `suplanimal`.`auditedicionfechahora`)
  INTO v_prev_bkpjson
  FROM `suplanimal`
  WHERE `suplanimalid` = v_suplanimalid
  LIMIT 1;

  -- Generic log insert
  INSERT INTO `suplanimallog` (
    `suplanimalid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_suplanimalid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'ANL',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `suplanimal`
  SET
    `suplanimalstatus` = 'ANL',
    `auditedicionusuarioid` = p_in_usuarioid,
    `auditediciondispositivo` = p_in_dispositivo,
    `auditedicionip` = p_in_ip
  WHERE `suplanimalid` = v_suplanimalid;

  UPDATE `suplanimaldetalle`
  SET
    `erpdocumentocod` = 'ANULADO'
  WHERE `suplanimalid` = v_suplanimalid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_suplanimal_listar//
CREATE PROCEDURE sp_suplanimal_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroEmpresaid VARCHAR(255);
  DECLARE v_filtroFundoid VARCHAR(255);
  DECLARE v_filtroSuplanimalestatus VARCHAR(10);
  DECLARE v_filtroInvbodegaid VARCHAR(255);
  DECLARE v_filtroSuplanimalobservacion VARCHAR(255);
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;
  SET v_filtroEmpresaid = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresaid')), 'null'), 'NULL'), '');
  SET v_filtroFundoid = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid')), 'null'), 'NULL'), '');
  SET v_filtroSuplanimalestatus = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroSuplanimalestatus')), 'null'), 'NULL'), '');
  SET v_filtroInvbodegaid = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroInvbodegaid')), 'null'), 'NULL'), '');
  SET v_filtroSuplanimalobservacion = NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroSuplanimalobservacion')), 'null'), 'NULL'), '');
  SET v_filtroFechaDesde = COALESCE(NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), 'null'), 'NULL'), ''), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(NULLIF(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), 'null'), 'NULL'), ''), CURRENT_DATE());
  SELECT
    t.`suplanimalid`,
    t.`suplanimalstatus`,
    empresas.razonsocial AS `empresa`,
    fundos.fundonombre AS `fundonombre`,
    invbodegas.invbodegadsc AS `invbodegadsc`,
    t.`suplanimalfecha`,
    t.`suplanimalobservacion`,
    t.`sup_erpestablecimientocod`,
    t.`sup_erplotecod`,
    t.`sup_erpinvbodegacod`,
    (select count(`suplanimalid`) from suplanimaldetalle sd where sd.suplanimalid = t.suplanimalid) AS `cant_detalles`,
    (select count(`suplanimalid`) from suplanimaldetalle sd where sd.suplanimalid = t.suplanimalid AND sd.erpdocumentocod = 'PEND') AS `cant_detalles_pend_erp`
  FROM `suplanimal` t
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  LEFT JOIN `invbodegas` ON t.`invbodegaid` = `invbodegas`.`invbodegaid`
  WHERE 1=1
    AND (t.`suplanimalfecha` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta)
    AND (v_filtroEmpresaid IS NULL OR v_filtroEmpresaid = '' OR t.`empresaid` = v_filtroEmpresaid)
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid)
    AND (v_filtroSuplanimalestatus IS NULL OR v_filtroSuplanimalestatus = '' OR t.`suplanimalstatus` = v_filtroSuplanimalestatus)
    AND (v_filtroInvbodegaid IS NULL OR v_filtroInvbodegaid = '' OR t.`invbodegaid` = v_filtroInvbodegaid)
    AND (v_filtroSuplanimalobservacion IS NULL OR v_filtroSuplanimalobservacion = '' OR t.`suplanimalobservacion` LIKE CONCAT('%', v_filtroSuplanimalobservacion, '%'))
    AND t.fundoid IN (
      SELECT uf.fundoid
      FROM usuariosfundos uf
      WHERE uf.usuarioid = p_in_usuarioid
    )
    ORDER BY t.`suplanimalfecha` DESC, fundos.fundonombre ASC, t.`suplanimalid` DESC;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_suplanimal_consulta_por_id//
CREATE PROCEDURE sp_suplanimal_consulta_por_id(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_suplanimalid INT(11);
  SET v_suplanimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalid')) AS SIGNED);

  IF (v_suplanimalid IS NULL OR v_suplanimalid = 0) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'ID de suplanimal inválido.');
    LEAVE sp_main;
  END IF;

  SELECT
    t.`suplanimalid`,
    t.`empresaid`,
    t.`fundoid`,
    t.`invbodegaid`,
    empresas.razonsocial AS `empresa`,
    fundos.fundonombre AS `fundonombre`,
    invbodegas.invbodegadsc AS `invbodegadsc`,
    t.`suplanimalfecha`,
    t.`suplanimalobservacion`,
    t.`sup_erpestablecimientocod`,
    t.`sup_erplotecod`,
    t.`sup_erpinvbodegacod`
  FROM `suplanimal` t
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  LEFT JOIN `invbodegas` ON t.`invbodegaid` = `invbodegas`.`invbodegaid`
  WHERE t.`suplanimalid` = v_suplanimalid;
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
