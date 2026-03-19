DELIMITER //
DROP PROCEDURE IF EXISTS sp_prodlechedetalle_editar//
CREATE PROCEDURE sp_prodlechedetalle_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_prodlecheid int(11);
  DECLARE v_prodlechetipoid int(11);
  DECLARE v_pldetlitros int(4);
  DECLARE v_pldetvacas int(4);
  DECLARE v_pldetlitrosxvaca float;
  DECLARE v_prodlechecod varchar(20);
  DECLARE v_erpdocumentocod varchar(20);
  DECLARE v_pldetfechareg datetime;
  DECLARE v_pldetfechaedt datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_prodlecheid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlecheid')) AS SIGNED),
    v_prodlechetipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechetipoid')) AS SIGNED),
    v_pldetlitros = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pldetlitros')) AS SIGNED),
    v_pldetvacas = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pldetvacas')) AS SIGNED),
    v_pldetlitrosxvaca = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pldetlitrosxvaca')) AS DECIMAL(20,6)),
    v_prodlechecod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.prodlechecod')),
    v_erpdocumentocod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.erpdocumentocod')),
    v_pldetfechareg = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pldetfechareg')) AS DATETIME),
    v_pldetfechaedt = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pldetfechaedt')) AS DATETIME);

  IF NOT EXISTS (SELECT 1 FROM `prodlechedetalle` WHERE `prodlecheid` = v_prodlecheid AND `prodlechetipoid` = v_prodlechetipoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  UPDATE `prodlechedetalle`
  SET `pldetlitros` = v_pldetlitros,
    `pldetvacas` = v_pldetvacas,
    `pldetlitrosxvaca` = v_pldetlitrosxvaca,
    `prodlechecod` = v_prodlechecod,
    `erpdocumentocod` = v_erpdocumentocod,
    `pldetfechareg` = v_pldetfechareg,
    `pldetfechaedt` = v_pldetfechaedt
  WHERE `prodlecheid` = v_prodlecheid AND `prodlechetipoid` = v_prodlechetipoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_prodlechedetalle_listar_detalle//
CREATE PROCEDURE sp_prodlechedetalle_listar_detalle(
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
    t.`prodlecheid`,
    t.`prodlechetipoid`
  FROM `prodlechedetalle` t
  WHERE 1=1
    AND (t.`pldetfechareg` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta);
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_prodlechedetalle_consulta_por_id_detalle//
CREATE PROCEDURE sp_prodlechedetalle_consulta_por_id_detalle(
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
    t.`prodlecheid`,
    t.`prodlechetipoid`
  FROM `prodlechedetalle` t
  WHERE 1=1
    AND (t.`pldetfechareg` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta);
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
