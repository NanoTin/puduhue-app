DELIMITER //
DROP PROCEDURE IF EXISTS sp_suplanimaldetalle_insertar//
CREATE PROCEDURE sp_suplanimaldetalle_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_suplanimalid int(11);
  DECLARE v_suplanimallinea int(4);
  DECLARE v_invcateganimalid int(11);
  DECLARE v_sup_erpinvcateganimalcod varchar(50);
  DECLARE v_invitemid int(11);
  DECLARE v_sup_erpinvitemcod varchar(50);
  DECLARE v_invunidmedid int(11);
  DECLARE v_sup_erpunidmedcod varchar(50);
  DECLARE v_totalconsumido float;
  DECLARE v_totalanimales int(4);
  DECLARE v_dosisporanimal float;
  DECLARE v_erpdocumentocod varchar(20);
  DECLARE v_supdetfechareg datetime;
  DECLARE v_supdetfechaedt datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_suplanimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalid')) AS SIGNED),
    v_suplanimallinea = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimallinea')) AS SIGNED),
    v_invcateganimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalid')) AS SIGNED),
    v_sup_erpinvcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpinvcateganimalcod')),
    v_invitemid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemid')) AS SIGNED),
    v_sup_erpinvitemcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpinvitemcod')),
    v_invunidmedid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedid')) AS SIGNED),
    v_sup_erpunidmedcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpunidmedcod')),
    v_totalconsumido = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.totalconsumido')) AS DECIMAL(20,6)),
    v_totalanimales = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.totalanimales')) AS SIGNED),
    v_dosisporanimal = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.dosisporanimal')) AS DECIMAL(20,6)),
    v_erpdocumentocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpdocumentocod')),
    v_supdetfechareg = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.supdetfechareg')) AS DATETIME),
    v_supdetfechaedt = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.supdetfechaedt')) AS DATETIME);

  IF EXISTS (SELECT 1 FROM `suplanimaldetalle` WHERE `suplanimalid` = v_suplanimalid AND `suplanimallinea` = v_suplanimallinea) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Duplicate primary key');
    LEAVE sp_main;
  END IF;

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
  VALUES (
    v_suplanimalid,
    v_suplanimallinea,
    v_invcateganimalid,
    v_sup_erpinvcateganimalcod,
    v_invitemid,
    v_sup_erpinvitemcod,
    v_invunidmedid,
    v_sup_erpunidmedcod,
    v_totalconsumido,
    v_totalanimales,
    v_dosisporanimal,
    v_erpdocumentocod,
    v_supdetfechareg,
    v_supdetfechaedt
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_suplanimaldetalle_editar//
CREATE PROCEDURE sp_suplanimaldetalle_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_suplanimalid int(11);
  DECLARE v_suplanimallinea int(4);
  DECLARE v_invcateganimalid int(11);
  DECLARE v_sup_erpinvcateganimalcod varchar(50);
  DECLARE v_invitemid int(11);
  DECLARE v_sup_erpinvitemcod varchar(50);
  DECLARE v_invunidmedid int(11);
  DECLARE v_sup_erpunidmedcod varchar(50);
  DECLARE v_totalconsumido float;
  DECLARE v_totalanimales int(4);
  DECLARE v_dosisporanimal float;
  DECLARE v_erpdocumentocod varchar(20);
  DECLARE v_supdetfechareg datetime;
  DECLARE v_supdetfechaedt datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_suplanimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimalid')) AS SIGNED),
    v_suplanimallinea = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.suplanimallinea')) AS SIGNED),
    v_invcateganimalid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invcateganimalid')) AS SIGNED),
    v_sup_erpinvcateganimalcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpinvcateganimalcod')),
    v_invitemid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invitemid')) AS SIGNED),
    v_sup_erpinvitemcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpinvitemcod')),
    v_invunidmedid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.invunidmedid')) AS SIGNED),
    v_sup_erpunidmedcod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.sup_erpunidmedcod')),
    v_totalconsumido = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.totalconsumido')) AS DECIMAL(20,6)),
    v_totalanimales = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.totalanimales')) AS SIGNED),
    v_dosisporanimal = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.dosisporanimal')) AS DECIMAL(20,6)),
    v_erpdocumentocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpdocumentocod')),
    v_supdetfechareg = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.supdetfechareg')) AS DATETIME),
    v_supdetfechaedt = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.supdetfechaedt')) AS DATETIME);

  IF NOT EXISTS (SELECT 1 FROM `suplanimaldetalle` WHERE `suplanimalid` = v_suplanimalid AND `suplanimallinea` = v_suplanimallinea) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  UPDATE `suplanimaldetalle`
  SET `invcateganimalid` = v_invcateganimalid,
    `sup_erpinvcateganimalcod` = v_sup_erpinvcateganimalcod,
    `invitemid` = v_invitemid,
    `sup_erpinvitemcod` = v_sup_erpinvitemcod,
    `invunidmedid` = v_invunidmedid,
    `sup_erpunidmedcod` = v_sup_erpunidmedcod,
    `totalconsumido` = v_totalconsumido,
    `totalanimales` = v_totalanimales,
    `dosisporanimal` = v_dosisporanimal,
    `erpdocumentocod` = v_erpdocumentocod,
    `supdetfechareg` = v_supdetfechareg,
    `supdetfechaedt` = v_supdetfechaedt
  WHERE `suplanimalid` = v_suplanimalid AND `suplanimallinea` = v_suplanimallinea;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_suplanimaldetalle_listar_detalle//
CREATE PROCEDURE sp_suplanimaldetalle_listar_detalle(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;
  SET v_filtroFechaDesde = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), CURRENT_DATE());
  SELECT
    t.`suplanimalid`,
    t.`suplanimallinea`
  FROM `suplanimaldetalle` t
  WHERE 1=1
    AND (t.`supdetfechareg` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta);
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_suplanimaldetalle_consulta_por_id_detalle//
CREATE PROCEDURE sp_suplanimaldetalle_consulta_por_id_detalle(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;
  SET v_filtroFechaDesde = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), CURRENT_DATE());
  SELECT
    t.`suplanimalid`,
    t.`suplanimallinea`
  FROM `suplanimaldetalle` t
  WHERE 1=1
    AND (t.`supdetfechareg` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta);
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
