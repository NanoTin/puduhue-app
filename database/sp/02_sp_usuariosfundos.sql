DELIMITER //
DROP PROCEDURE IF EXISTS sp_usuariosfundos_insertar//
CREATE PROCEDURE sp_usuariosfundos_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid int(11);
  DECLARE v_fundoid int(11);
  DECLARE v_ufdefault tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Parametro es requerido.');
    LEAVE sp_main;
  END IF;

  SET     
    v_usuarioid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED),
    v_ufdefault = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.ufdefault')) AS SIGNED);

  IF EXISTS (SELECT 1 FROM `usuariosfundos` WHERE `usuarioid` = v_usuarioid AND `fundoid` = v_fundoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Usuario y Fundo seleccionado, ya existe.');
    LEAVE sp_main;
  END IF;

  IF v_ufdefault = 1 THEN
    UPDATE `usuariosfundos` SET `ufdefault` = 0 WHERE `usuarioid` = v_usuarioid;
    UPDATE `usuarios` SET `fundoiddefault` = v_fundoid WHERE `usuarioid` = v_usuarioid;
  END IF;

  INSERT INTO `usuariosfundos` (
    `usuarioid`,
    `fundoid`,
    `ufdefault`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_usuarioid,
    v_fundoid,
    v_ufdefault,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuariosfundos_eliminar//
CREATE PROCEDURE sp_usuariosfundos_eliminar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid int(11);
  DECLARE v_fundoid int(11);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'JSON es requerido');
    LEAVE sp_main;
  END IF;

  SET     v_usuarioid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')) AS SIGNED),
    v_fundoid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `usuariosfundos` WHERE `usuarioid` = v_usuarioid AND `fundoid` = v_fundoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Registro no encontrado');
    LEAVE sp_main;
  END IF;

  IF EXISTS (
    SELECT 1 FROM `usuariosfundos` WHERE `usuarioid` = v_usuarioid AND `fundoid` = v_fundoid AND `ufdefault` = 1
  ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No puede eliminar el fundo por defecto del usuario.');
    LEAVE sp_main;
  END IF;

  INSERT INTO `usuariosfundoshist` (
    `histusuarioid`,
    `histfundoid`,
    `histauditcreacionusuarioid`,
    `histauditcreaciondispositivo`,
    `histauditcreacionip`,
    `histauditcreacionfechahora`,
    `auditedicionusuarioid`,
    `auditediciondispositivo`,
    `auditedicionip`,
    `auditedicionfechahora`
  )
  SELECT
    `usuariosfundos`.`usuarioid`,
    `usuariosfundos`.`fundoid`,
    `usuariosfundos`.`auditcreacionusuarioid`,
    `usuariosfundos`.`auditcreaciondispositivo`,
    `usuariosfundos`.`auditcreacionip`,
    `usuariosfundos`.`auditcreacionfechahora`,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    NOW()
  FROM `usuariosfundos`
  WHERE `usuarioid` = v_usuarioid AND `fundoid` = v_fundoid;

  DELETE FROM `usuariosfundos` WHERE `usuarioid` = v_usuarioid AND `fundoid` = v_fundoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuariosfundos_listar//
CREATE PROCEDURE sp_usuariosfundos_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroUsuarioid VARCHAR(255);
  DECLARE v_filtroFundoid VARCHAR(255);
  SET v_filtroUsuarioid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioid'));
  SET v_filtroFundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid'));
  SELECT
    t.`usuarioid`,
    t.`fundoid`,
    t.`ufdefault`,
    usuarios.usuarionombre AS `usuarios_usuarionombre`,
    fundos.fundonombre AS `fundos_fundonombre`,
    t.`auditcreacionusuarioid`,
    t.`auditcreaciondispositivo`,
    t.`auditcreacionip`,
    t.`auditcreacionfechahora`
  FROM `usuariosfundos` t
  LEFT JOIN `usuarios` ON t.`usuarioid` = `usuarios`.`usuarioid`
  LEFT JOIN `fundos` ON t.`fundoid` = `fundos`.`fundoid`
  WHERE 1=1
    AND (v_filtroUsuarioid IS NULL OR v_filtroUsuarioid = '' OR t.`usuarioid` = v_filtroUsuarioid)
    AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR t.`fundoid` = v_filtroFundoid);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
