DELIMITER //

DROP PROCEDURE IF EXISTS sp_usuarioscentroscosto_listar//
CREATE PROCEDURE sp_usuarioscentroscosto_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(100),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroUsuarioid INT DEFAULT NULL;
  DECLARE v_filtroCentrocostoid INT DEFAULT NULL;
  DECLARE v_filtroActivo INT DEFAULT NULL;

  SET v_filtroUsuarioid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroUsuarioid')), 'null') AS SIGNED);
  SET v_filtroCentrocostoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentrocostoid')), 'null') AS SIGNED);
  SET v_filtroActivo = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroActivo')), 'null') AS SIGNED);

  SELECT
    uc.`usucenid`,
    uc.`usuarioid`,
    uc.`centrocostoid`,
    uc.`usucendefault`,
    uc.`usucenactivo`,
    u.`usuarionombre` AS `usuarios_usuarionombre`,
    u.`usuariorut` AS `usuarios_usuariorut`,
    cc.`centrocostocod`,
    cc.`centrocostodsc`,
    cc.`centrocostoactivo`,
    uc.`auditcreacionusuarioid`,
    uc.`auditcreaciondispositivo`,
    uc.`auditcreacionip`,
    uc.`auditcreacionfechahora`,
    uc.`auditedicionusuarioid`,
    uc.`auditediciondispositivo`,
    uc.`auditedicionip`,
    uc.`auditedicionfechahora`
  FROM `usuarioscentroscosto` uc
  INNER JOIN `usuarios` u
    ON u.`usuarioid` = uc.`usuarioid`
  INNER JOIN `centroscosto` cc
    ON cc.`centrocostoid` = uc.`centrocostoid`
  WHERE (v_filtroUsuarioid IS NULL OR v_filtroUsuarioid <= 0 OR uc.`usuarioid` = v_filtroUsuarioid)
    AND (v_filtroCentrocostoid IS NULL OR v_filtroCentrocostoid <= 0 OR uc.`centrocostoid` = v_filtroCentrocostoid)
    AND (v_filtroActivo IS NULL OR v_filtroActivo NOT IN (0, 1) OR uc.`usucenactivo` = v_filtroActivo)
  ORDER BY u.`usuarionombre` ASC, uc.`usucenactivo` DESC, uc.`usucendefault` DESC, cc.`centrocostodsc` ASC;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_usuarioscentroscosto_insertar//
CREATE PROCEDURE sp_usuarioscentroscosto_insertar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(100),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usuarioid INT;
  DECLARE v_centrocostoid INT;
  DECLARE v_usucendefault TINYINT(1) DEFAULT 0;
  DECLARE v_usucenid_existente INT DEFAULT NULL;
  DECLARE v_usucenactivo_existente TINYINT(1) DEFAULT NULL;
  DECLARE v_registro_bkp JSON DEFAULT NULL;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe informar la asignacion usuario-centro de costo.');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `usuarios`
    WHERE `usuarioid` = p_in_usuarioid
      AND `usuarioactivo` = 1
      AND `usuariobloqueado` = 0
  ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Usuario ejecutor invalido.');
    LEAVE sp_main;
  END IF;

  SET v_usuarioid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usuarioid')), 'null') AS SIGNED);
  SET v_centrocostoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), 'null') AS SIGNED);
  SET v_usucendefault = IFNULL(CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usucendefault')), 'null') AS SIGNED), 0);

  IF v_usuarioid IS NULL OR v_usuarioid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe seleccionar un usuario valido.');
    LEAVE sp_main;
  END IF;

  IF v_centrocostoid IS NULL OR v_centrocostoid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe seleccionar un centro de costo valido.');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `usuarioid` = v_usuarioid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El usuario seleccionado no existe.');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `centroscosto` WHERE `centrocostoid` = v_centrocostoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El centro de costo seleccionado no existe.');
    LEAVE sp_main;
  END IF;

  SELECT
    uc.`usucenid`,
    uc.`usucenactivo`,
    JSON_OBJECT(
      'usucenid', uc.`usucenid`,
      'usuarioid', uc.`usuarioid`,
      'centrocostoid', uc.`centrocostoid`,
      'usucendefault', uc.`usucendefault`,
      'usucenactivo', uc.`usucenactivo`,
      'auditcreacionusuarioid', uc.`auditcreacionusuarioid`,
      'auditcreaciondispositivo', uc.`auditcreaciondispositivo`,
      'auditcreacionip', uc.`auditcreacionip`,
      'auditcreacionfechahora', uc.`auditcreacionfechahora`,
      'auditedicionusuarioid', uc.`auditedicionusuarioid`,
      'auditediciondispositivo', uc.`auditediciondispositivo`,
      'auditedicionip', uc.`auditedicionip`,
      'auditedicionfechahora', uc.`auditedicionfechahora`
    )
  INTO v_usucenid_existente, v_usucenactivo_existente, v_registro_bkp
  FROM `usuarioscentroscosto` uc
  WHERE uc.`usuarioid` = v_usuarioid
    AND uc.`centrocostoid` = v_centrocostoid
  LIMIT 1;

  IF v_usucenid_existente IS NOT NULL AND v_usucenactivo_existente = 1 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La asignacion usuario-centro de costo ya existe activa.');
    LEAVE sp_main;
  END IF;

  IF v_usucendefault = 1 THEN
    UPDATE `usuarioscentroscosto`
    SET `usucendefault` = 0,
        `auditedicionusuarioid` = p_in_usuarioid,
        `auditediciondispositivo` = p_in_dispositivo,
        `auditedicionip` = p_in_ip,
        `auditedicionfechahora` = NOW()
    WHERE `usuarioid` = v_usuarioid
      AND `usucenactivo` = 1;
  END IF;

  IF v_usucenid_existente IS NOT NULL AND v_usucenactivo_existente = 0 THEN
    UPDATE `usuarioscentroscosto`
    SET `usucenactivo` = 1,
        `usucendefault` = v_usucendefault,
        `auditedicionusuarioid` = p_in_usuarioid,
        `auditediciondispositivo` = p_in_dispositivo,
        `auditedicionip` = p_in_ip,
        `auditedicionfechahora` = NOW()
    WHERE `usucenid` = v_usucenid_existente;

    INSERT INTO `usuarioscentroscostolog` (
      `usucenid`,
      `logusuarioid`,
      `logdispositivo`,
      `logip`,
      `logtipo`,
      `logparamjson`,
      `logregbkpjson`
    ) VALUES (
      v_usucenid_existente,
      p_in_usuarioid,
      p_in_dispositivo,
      p_in_ip,
      'UPD',
      p_in_json,
      v_registro_bkp
    );

    SET p_out_json = JSON_OBJECT(
      'status', 200,
      'message', 'Asignacion reactivada correctamente.',
      'id', v_usucenid_existente
    );
    LEAVE sp_main;
  END IF;

  INSERT INTO `usuarioscentroscosto` (
    `usuarioid`,
    `centrocostoid`,
    `usucendefault`,
    `usucenactivo`,
    `auditcreacionusuarioid`,
    `auditcreaciondispositivo`,
    `auditcreacionip`
  ) VALUES (
    v_usuarioid,
    v_centrocostoid,
    v_usucendefault,
    1,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip
  );

  SET v_usucenid_existente = LAST_INSERT_ID();

  INSERT INTO `usuarioscentroscostolog` (
    `usucenid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_usucenid_existente,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'INS',
    p_in_json,
    NULL
  );

  SET p_out_json = JSON_OBJECT(
    'status', 200,
    'message', 'Asignacion creada correctamente.',
    'id', v_usucenid_existente
  );
END//

DROP PROCEDURE IF EXISTS sp_usuarioscentroscosto_editar//
CREATE PROCEDURE sp_usuarioscentroscosto_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(100),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_usucenid INT;
  DECLARE v_accion VARCHAR(30);
  DECLARE v_usuarioid INT;
  DECLARE v_usucendefault_actual TINYINT(1);
  DECLARE v_usucenactivo_actual TINYINT(1);
  DECLARE v_default_otro_activo INT DEFAULT 0;
  DECLARE v_registro_bkp JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe informar la accion a ejecutar.');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `usuarios`
    WHERE `usuarioid` = p_in_usuarioid
      AND `usuarioactivo` = 1
      AND `usuariobloqueado` = 0
  ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Usuario ejecutor invalido.');
    LEAVE sp_main;
  END IF;

  SET v_usucenid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.usucenid')), 'null') AS SIGNED);
  SET v_accion = LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.accion'))));

  IF v_usucenid IS NULL OR v_usucenid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe informar una asignacion valida.');
    LEAVE sp_main;
  END IF;

  IF v_accion IS NULL OR v_accion = '' THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe informar la accion a ejecutar.');
    LEAVE sp_main;
  END IF;

  SELECT
    uc.`usuarioid`,
    uc.`usucendefault`,
    uc.`usucenactivo`,
    JSON_OBJECT(
      'usucenid', uc.`usucenid`,
      'usuarioid', uc.`usuarioid`,
      'centrocostoid', uc.`centrocostoid`,
      'usucendefault', uc.`usucendefault`,
      'usucenactivo', uc.`usucenactivo`,
      'auditcreacionusuarioid', uc.`auditcreacionusuarioid`,
      'auditcreaciondispositivo', uc.`auditcreaciondispositivo`,
      'auditcreacionip', uc.`auditcreacionip`,
      'auditcreacionfechahora', uc.`auditcreacionfechahora`,
      'auditedicionusuarioid', uc.`auditedicionusuarioid`,
      'auditediciondispositivo', uc.`auditediciondispositivo`,
      'auditedicionip', uc.`auditedicionip`,
      'auditedicionfechahora', uc.`auditedicionfechahora`
    )
  INTO v_usuarioid, v_usucendefault_actual, v_usucenactivo_actual, v_registro_bkp
  FROM `usuarioscentroscosto` uc
  WHERE uc.`usucenid` = v_usucenid
  LIMIT 1;

  IF v_usuarioid IS NULL OR v_usuarioid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La asignacion indicada no existe.');
    LEAVE sp_main;
  END IF;

  IF v_accion = 'desactivar' THEN
    IF v_usucenactivo_actual = 0 THEN
      SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La asignacion ya se encuentra inactiva.');
      LEAVE sp_main;
    END IF;

    UPDATE `usuarioscentroscosto`
    SET `usucenactivo` = 0,
        `usucendefault` = 0,
        `auditedicionusuarioid` = p_in_usuarioid,
        `auditediciondispositivo` = p_in_dispositivo,
        `auditedicionip` = p_in_ip,
        `auditedicionfechahora` = NOW()
    WHERE `usucenid` = v_usucenid;

    INSERT INTO `usuarioscentroscostolog` (
      `usucenid`,
      `logusuarioid`,
      `logdispositivo`,
      `logip`,
      `logtipo`,
      `logparamjson`,
      `logregbkpjson`
    ) VALUES (
      v_usucenid,
      p_in_usuarioid,
      p_in_dispositivo,
      p_in_ip,
      'ANL',
      p_in_json,
      v_registro_bkp
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Asignacion desactivada correctamente.', 'id', v_usucenid);
    LEAVE sp_main;
  END IF;

  IF v_accion = 'activar' THEN
    IF v_usucenactivo_actual = 1 THEN
      SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La asignacion ya se encuentra activa.');
      LEAVE sp_main;
    END IF;

    SELECT COUNT(1)
      INTO v_default_otro_activo
    FROM `usuarioscentroscosto`
    WHERE `usuarioid` = v_usuarioid
      AND `usucenid` <> v_usucenid
      AND `usucenactivo` = 1
      AND `usucendefault` = 1;

    UPDATE `usuarioscentroscosto`
    SET `usucenactivo` = 1,
        `usucendefault` = CASE
          WHEN v_usucendefault_actual = 1 AND v_default_otro_activo = 0 THEN 1
          ELSE 0
        END,
        `auditedicionusuarioid` = p_in_usuarioid,
        `auditediciondispositivo` = p_in_dispositivo,
        `auditedicionip` = p_in_ip,
        `auditedicionfechahora` = NOW()
    WHERE `usucenid` = v_usucenid;

    INSERT INTO `usuarioscentroscostolog` (
      `usucenid`,
      `logusuarioid`,
      `logdispositivo`,
      `logip`,
      `logtipo`,
      `logparamjson`,
      `logregbkpjson`
    ) VALUES (
      v_usucenid,
      p_in_usuarioid,
      p_in_dispositivo,
      p_in_ip,
      'UPD',
      p_in_json,
      v_registro_bkp
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Asignacion activada correctamente.', 'id', v_usucenid);
    LEAVE sp_main;
  END IF;

  IF v_accion = 'marcar_default' THEN
    IF v_usucenactivo_actual = 0 THEN
      SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No puede marcar como default una asignacion inactiva.');
      LEAVE sp_main;
    END IF;

    UPDATE `usuarioscentroscosto`
    SET `usucendefault` = 0,
        `auditedicionusuarioid` = p_in_usuarioid,
        `auditediciondispositivo` = p_in_dispositivo,
        `auditedicionip` = p_in_ip,
        `auditedicionfechahora` = NOW()
    WHERE `usuarioid` = v_usuarioid
      AND `usucenactivo` = 1
      AND `usucenid` <> v_usucenid;

    UPDATE `usuarioscentroscosto`
    SET `usucendefault` = 1,
        `usucenactivo` = 1,
        `auditedicionusuarioid` = p_in_usuarioid,
        `auditediciondispositivo` = p_in_dispositivo,
        `auditedicionip` = p_in_ip,
        `auditedicionfechahora` = NOW()
    WHERE `usucenid` = v_usucenid;

    INSERT INTO `usuarioscentroscostolog` (
      `usucenid`,
      `logusuarioid`,
      `logdispositivo`,
      `logip`,
      `logtipo`,
      `logparamjson`,
      `logregbkpjson`
    ) VALUES (
      v_usucenid,
      p_in_usuarioid,
      p_in_dispositivo,
      p_in_ip,
      'UPD',
      p_in_json,
      v_registro_bkp
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Centro default actualizado correctamente.', 'id', v_usucenid);
    LEAVE sp_main;
  END IF;

  SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La accion solicitada no es valida.');
END//

DELIMITER ;
