DELIMITER //
DROP PROCEDURE IF EXISTS sp_fundostipos_insertar//
CREATE PROCEDURE sp_fundostipos_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundotipodsc varchar(50);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundotipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundotipodsc'));

  INSERT INTO `fundostipos` (
    `fundotipodsc`
  )
  VALUES (
    v_fundotipodsc
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundostipos_editar//
CREATE PROCEDURE sp_fundostipos_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_fundotipoid int(11);
  DECLARE v_fundotipodsc varchar(50);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_fundotipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundotipoid')) AS SIGNED),
    v_fundotipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundotipodsc'));

  IF NOT EXISTS (SELECT 1 FROM `fundostipos` WHERE `fundotipoid` = v_fundotipoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  UPDATE `fundostipos`
  SET `fundotipodsc` = v_fundotipodsc
  WHERE `fundotipoid` = v_fundotipoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_fundostipos_listar//
CREATE PROCEDURE sp_fundostipos_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroFundotipodsc VARCHAR(255);
  SET v_filtroFundotipodsc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundotipodsc'));
  SELECT
    t.`fundotipoid`,
    t.`fundotipodsc`
  FROM `fundostipos` t
  WHERE 1=1
    AND (v_filtroFundotipodsc IS NULL OR v_filtroFundotipodsc = '' OR t.`fundotipodsc` LIKE CONCAT('%', v_filtroFundotipodsc, '%'));

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
