DELIMITER //
DROP PROCEDURE IF EXISTS sp_usuariosempresas_insertar//
CREATE PROCEDURE sp_usuariosempresas_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid int(11);
  DECLARE v_empresaid int(11);
  DECLARE v_uedefault tinyint(1);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     
    v_usuarioid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')) AS SIGNED),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED),
    v_uedefault = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.uedefault')) AS SIGNED);

  IF EXISTS (SELECT 1 FROM `usuariosempresas` WHERE `usuarioid` = v_usuarioid AND `empresaid` = v_empresaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Duplicate primary key');
    LEAVE sp_main;
  END IF;

  -- Validar variable usuarioid
  IF NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `usuarioid` = v_usuarioid) or v_usuarioid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Invalid usuarioid');
    LEAVE sp_main;
  END IF;

  -- Validar variable empresaid
  IF NOT EXISTS (SELECT 1 FROM `empresas` WHERE `empresaid` = v_empresaid) or v_empresaid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Invalid empresaid');
    LEAVE sp_main;
  END IF;

  IF v_uedefault = 1 THEN
    UPDATE `usuariosempresas` SET `uedefault` = 0 WHERE `usuarioid` = v_usuarioid;
    UPDATE `usuarios` SET `empresaiddefault` = v_empresaid WHERE `usuarioid` = v_usuarioid;
  END IF;

  INSERT INTO `usuariosempresas` (
    `usuarioid`,
    `empresaid`,
    `uedefault`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  )
  VALUES (
    v_usuarioid,
    v_empresaid,
    v_uedefault,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuariosempresas_eliminar//
CREATE PROCEDURE sp_usuariosempresas_eliminar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid int(11);
  DECLARE v_empresaid int(11);

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
    LEAVE sp_main;
  END IF;

  SET     v_usuarioid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')) AS SIGNED),
    v_empresaid = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')) AS SIGNED);

  IF NOT EXISTS (SELECT 1 FROM `usuariosempresas` WHERE `usuarioid` = v_usuarioid AND `empresaid` = v_empresaid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record not found');
    LEAVE sp_main;
  END IF;

  IF EXISTS (
    SELECT 1 FROM `usuariosempresas` WHERE `usuarioid` = v_usuarioid AND `empresaid` = v_empresaid AND `uedefault` = 1
  ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Cannot delete default empresa for user');
    LEAVE sp_main;
  END IF;

  INSERT INTO `usuariosempresashist` (
    `histusuarioid`,
    `histempresaid`,
    `histauditcreacionusuarioid`,
    `histauditcreaciondispositivo`,
    `histauditcreacionip`,
    `histauditcreacionfechahora`,
    `auditedicionusuarioid`,
    `auditediciondispositivo`,
    `auditedicionip`
  )
  SELECT
    `usuariosempresas`.`usuarioid`,
    `usuariosempresas`.`empresaid`,
    `usuariosempresas`.`auditcreacionusuarioid`,
    `usuariosempresas`.`auditcreaciondispositivo`,
    `usuariosempresas`.`auditcreacionip`,
    `usuariosempresas`.`auditcreacionfechahora`,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  FROM `usuariosempresas`
  WHERE `usuarioid` = v_usuarioid AND `empresaid` = v_empresaid;

  DELETE FROM `usuariosempresas` WHERE `usuarioid` = v_usuarioid AND `empresaid` = v_empresaid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuariosempresas_listar//
CREATE PROCEDURE sp_usuariosempresas_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroUsuarioid VARCHAR(255);
  DECLARE v_filtroEmpresaid VARCHAR(255);
  SET v_filtroUsuarioid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioid'));
  SET v_filtroEmpresaid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEmpresaid'));
  SELECT
    t.`usuarioid`,
    t.`empresaid`,
    t.`uedefault`,
    usuarios.usuarionombre AS `usuarios_usuarionombre`,
    empresas.razonsocial AS `empresas_razonsocial`,
    t.`auditcreacionusuarioid`,
    t.`auditcreaciondispositivo`,
    t.`auditcreacionip`,
    t.`auditcreacionfechahora`
  FROM `usuariosempresas` t
  LEFT JOIN `usuarios` ON t.`usuarioid` = `usuarios`.`usuarioid`
  LEFT JOIN `empresas` ON t.`empresaid` = `empresas`.`empresaid`
  WHERE 1=1
    AND (v_filtroUsuarioid IS NULL OR v_filtroUsuarioid = '' OR t.`usuarioid` = v_filtroUsuarioid)
    AND (v_filtroEmpresaid IS NULL OR v_filtroEmpresaid = '' OR t.`empresaid` = v_filtroEmpresaid);

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//
DELIMITER ;
