DELIMITER //
DROP PROCEDURE IF EXISTS sp_tokentipos_insertar//
CREATE PROCEDURE sp_tokentipos_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_tokentipodsc varchar(50);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_tokentipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.tokentipodsc'));

  INSERT INTO `tokentipos` (
    `tokentipodsc`
  )
  VALUES (
    v_tokentipodsc
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_tokentipos_editar//
CREATE PROCEDURE sp_tokentipos_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_tokentipoid int(11);
  DECLARE v_tokentipodsc varchar(50);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_tokentipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.tokentipoid')) AS SIGNED),
    v_tokentipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.tokentipodsc'));

  IF NOT EXISTS (SELECT 1 FROM `tokentipos` WHERE `tokentipoid` = v_tokentipoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  UPDATE `tokentipos`
  SET `tokentipodsc` = v_tokentipodsc
  WHERE `tokentipoid` = v_tokentipoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_tokentipos_listar//
CREATE PROCEDURE sp_tokentipos_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroTokentipodsc VARCHAR(255);
  SET v_filtroTokentipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTokentipodsc'));
  SELECT
    t.`tokentipoid`,
    t.`tokentipodsc`
  FROM `tokentipos` t
  WHERE 1=1
    AND (v_filtroTokentipodsc IS NULL OR v_filtroTokentipodsc = '' OR t.`tokentipodsc` LIKE CONCAT('%', v_filtroTokentipodsc, '%'));
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
