DELIMITER //
DROP PROCEDURE IF EXISTS sp_usuariostokens_insertar//
CREATE PROCEDURE sp_usuariostokens_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_tokentipoid int(11);
  DECLARE v_usuarioid varchar(12);
  DECLARE v_usuarioemail varchar(100);
  DECLARE v_usuariotoken varchar(255);
  DECLARE v_fechareq datetime;
  DECLARE v_fechaexp datetime;
  DECLARE v_usado tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_tokentipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.tokentipoid')) AS SIGNED),
    v_usuarioid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')),
    v_usuarioemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioemail')),
    v_usuariotoken = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariotoken')),
    v_fechareq = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fechareq')) AS DATETIME),
    v_fechaexp = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fechaexp')) AS DATETIME),
    v_usado = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usado')) AS SIGNED);

  INSERT INTO `usuariostokens` (
    `tokentipoid`,
    `usuarioid`,
    `usuarioemail`,
    `usuariotoken`,
    `fechareq`,
    `fechaexp`,
    `usado`
  )
  VALUES (
    v_tokentipoid,
    v_usuarioid,
    v_usuarioemail,
    v_usuariotoken,
    v_fechareq,
    v_fechaexp,
    v_usado
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuariostokens_editar//
CREATE PROCEDURE sp_usuariostokens_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_requerimientoid int(11);
  DECLARE v_tokentipoid int(11);
  DECLARE v_usuarioid varchar(12);
  DECLARE v_usuarioemail varchar(100);
  DECLARE v_usuariotoken varchar(255);
  DECLARE v_fechareq datetime;
  DECLARE v_fechaexp datetime;
  DECLARE v_usado tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_requerimientoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.requerimientoid')) AS SIGNED),
    v_tokentipoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.tokentipoid')) AS SIGNED),
    v_usuarioid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')),
    v_usuarioemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioemail')),
    v_usuariotoken = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuariotoken')),
    v_fechareq = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fechareq')) AS DATETIME),
    v_fechaexp = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fechaexp')) AS DATETIME),
    v_usado = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usado')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `usuariostokens` WHERE `requerimientoid` = v_requerimientoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  UPDATE `usuariostokens`
  SET `tokentipoid` = v_tokentipoid,
    `usuarioid` = v_usuarioid,
    `usuarioemail` = v_usuarioemail,
    `usuariotoken` = v_usuariotoken,
    `fechareq` = v_fechareq,
    `fechaexp` = v_fechaexp,
    `usado` = v_usado
  WHERE `requerimientoid` = v_requerimientoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuariostokens_listar//
CREATE PROCEDURE sp_usuariostokens_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroTokentipoid VARCHAR(255);
  DECLARE v_filtroUsuarioid VARCHAR(255);
  DECLARE v_filtroUsuarioemail VARCHAR(255);
  DECLARE v_filtroFechareq VARCHAR(255);
  DECLARE v_filtroFechaexp VARCHAR(255);
  DECLARE v_filtroUsado VARCHAR(255);
  DECLARE v_filtroFechaDesde DATE;
  DECLARE v_filtroFechaHasta DATE;
  SET v_filtroTokentipoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTokentipoid'));
  SET v_filtroUsuarioid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioid'));
  SET v_filtroUsuarioemail = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioemail'));
  SET v_filtroFechareq = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechareq'));
  SET v_filtroFechaexp = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaexp'));
  SET v_filtroUsado = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsado'));
  SET v_filtroFechaDesde = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), '1900-01-01');
  SET v_filtroFechaHasta = COALESCE(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), CURRENT_DATE());
  SELECT
    t.`requerimientoid`,
    tokentipos.tokentipodsc AS `tokentipos_tokentipodsc`,
    usuarios.usuarionombre AS `usuarios_usuarionombre`,
    t.`usuarioemail`,
    t.`fechareq`,
    t.`fechaexp`,
    t.`usado`
  FROM `usuariostokens` t
  LEFT JOIN `tokentipos` ON t.`tokentipoid` = `tokentipos`.`tokentipoid`
  LEFT JOIN `usuarios` ON t.`usuarioid` = `usuarios`.`usuarioid`
  WHERE 1=1
    AND (t.`fechareq` BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta)
    AND (v_filtroTokentipoid IS NULL OR v_filtroTokentipoid = '' OR t.`tokentipoid` = v_filtroTokentipoid)
    AND (v_filtroUsuarioid IS NULL OR v_filtroUsuarioid = '' OR t.`usuarioid` LIKE CONCAT('%', v_filtroUsuarioid, '%'))
    AND (v_filtroUsuarioemail IS NULL OR v_filtroUsuarioemail = '' OR t.`usuarioemail` LIKE CONCAT('%', v_filtroUsuarioemail, '%'))
    AND (v_filtroFechareq IS NULL OR v_filtroFechareq = '' OR t.`fechareq` = v_filtroFechareq)
    AND (v_filtroFechaexp IS NULL OR v_filtroFechaexp = '' OR t.`fechaexp` = v_filtroFechaexp)
    AND (v_filtroUsado IS NULL OR v_filtroUsado = '' OR t.`usado` = v_filtroUsado);
  -- TODO: adjust filters/joins as needed
  -- No data returned in p_out_json; consume result set via PDO
  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
