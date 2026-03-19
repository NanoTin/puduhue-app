DELIMITER //
DROP PROCEDURE IF EXISTS sp_erptokenactivo_insertar//
CREATE PROCEDURE sp_erptokenactivo_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_access_token varchar(50);
  DECLARE v_generado datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_access_token = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.access_token')),
    v_generado = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.generado')) AS DATETIME);

  IF EXISTS (SELECT 1 FROM `erptokenactivo` WHERE `access_token` = v_access_token) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Duplicate primary key');
    LEAVE sp_main;
  END IF;

  DELETE FROM `erptokenactivo`;

  INSERT INTO `erptokenactivo` (
    `access_token`,
    `generado`
  )
  VALUES (
    v_access_token,
    v_generado
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_erptokenactivo_editar//
CREATE PROCEDURE sp_erptokenactivo_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_access_token varchar(50);
  DECLARE v_generado datetime;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_access_token = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.access_token')),
    v_generado = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.generado')) AS DATETIME);

  IF NOT EXISTS (SELECT 1 FROM `erptokenactivo` WHERE `access_token` = v_access_token) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  UPDATE `erptokenactivo`
  SET `generado` = v_generado
  WHERE `access_token` = v_access_token;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_erptokenactivo_listar//
CREATE PROCEDURE sp_erptokenactivo_listar(
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
    t.`access_token`,
    t.`generado`
  FROM `erptokenactivo` t
  WHERE 1=1
    AND (t.`generado` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta);
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
