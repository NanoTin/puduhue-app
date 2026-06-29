DELIMITER //

DROP PROCEDURE IF EXISTS sp_centroscosto_listar//
CREATE PROCEDURE sp_centroscosto_listar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_filtroCentrocostocod VARCHAR(255);
  DECLARE v_filtroCentrocostodsc VARCHAR(255);
  DECLARE v_filtroCentrocostoactivo VARCHAR(255);

  SET v_filtroCentrocostocod = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentrocostocod')), 'null');
  SET v_filtroCentrocostodsc = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentrocostodsc')), 'null');
  SET v_filtroCentrocostoactivo = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentrocostoactivo')), 'null');

  SELECT
    cc.`centrocostoid`,
    cc.`empresaid`,
    e.`razonsocial` AS `empresas_razonsocial`,
    cc.`centrocostocod`,
    cc.`centrocostodsc`,
    cc.`centrocostodescripcion`,
    cc.`erpcentrocostocod`,
    cc.`centrocostojefeusuarioid`,
    uj.`usuarionombre` AS `centrocostojefeusuarionombre`,
    cc.`centrocostojefetecnicoid`,
    ut.`usuarionombre` AS `centrocostojefetecniconombre`,
    cc.`centrocostogerenteproduccionid`,
    ug.`usuarionombre` AS `centrocostogerenteproduccionnombre`,
    cc.`centrocostoactivo`,
    cc.`sincfechahora`,
    cc.`auditcreacionusuarioid`,
    cc.`auditcreaciondispositivo`,
    cc.`auditcreacionip`,
    cc.`auditcreacionfechahora`,
    cc.`auditedicionusuarioid`,
    cc.`auditediciondispositivo`,
    cc.`auditedicionip`,
    cc.`auditedicionfechahora`
  FROM `centroscosto` cc
  LEFT JOIN `empresas` e
    ON e.`empresaid` = cc.`empresaid`
  LEFT JOIN `usuarios` uj
    ON uj.`usuarioid` = cc.`centrocostojefeusuarioid`
  LEFT JOIN `usuarios` ut
    ON ut.`usuarioid` = cc.`centrocostojefetecnicoid`
  LEFT JOIN `usuarios` ug
    ON ug.`usuarioid` = cc.`centrocostogerenteproduccionid`
  WHERE (v_filtroCentrocostocod IS NULL OR v_filtroCentrocostocod = '' OR cc.`centrocostocod` LIKE CONCAT('%', v_filtroCentrocostocod, '%'))
    AND (v_filtroCentrocostodsc IS NULL OR v_filtroCentrocostodsc = '' OR CONCAT_WS(' ', cc.`centrocostodsc`, IFNULL(cc.`centrocostodescripcion`, '')) LIKE CONCAT('%', v_filtroCentrocostodsc, '%'))
    AND (v_filtroCentrocostoactivo IS NULL OR v_filtroCentrocostoactivo = '' OR cc.`centrocostoactivo` = v_filtroCentrocostoactivo)
  ORDER BY cc.`centrocostodsc` ASC;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_centroscosto_editar//
CREATE PROCEDURE sp_centroscosto_editar(
  IN  p_in_json JSON,
  IN  p_in_usuarioid INT,
  IN  p_in_dispositivo VARCHAR(50),
  IN  p_in_ip VARCHAR(50),
  OUT p_out_json JSON
)
sp_main: BEGIN
  DECLARE v_centrocostoid INT;
  DECLARE v_centrocostojefeusuarioid INT;
  DECLARE v_centrocostojefetecnicoid INT;
  DECLARE v_prev_bkpjson JSON;

  IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe informar los datos del centro de costo.');
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

  SET v_centrocostoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), 'null') AS SIGNED);
  SET v_centrocostojefeusuarioid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostojefeusuarioid')), 'null') AS SIGNED);
  SET v_centrocostojefetecnicoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostojefetecnicoid')), 'null') AS SIGNED);

  IF v_centrocostoid IS NULL OR v_centrocostoid <= 0 THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe indicar un centro de costo valido.');
    LEAVE sp_main;
  END IF;

  IF NOT EXISTS (SELECT 1 FROM `centroscosto` WHERE `centrocostoid` = v_centrocostoid) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Centro de costo no encontrado.');
    LEAVE sp_main;
  END IF;

  IF v_centrocostojefeusuarioid IS NOT NULL AND v_centrocostojefeusuarioid > 0 AND NOT EXISTS (
    SELECT 1
    FROM `usuarios`
    WHERE `usuarioid` = v_centrocostojefeusuarioid
      AND `usuarioactivo` = 1
      AND `usuariobloqueado` = 0
      AND `usuariopermiteaprobreq` = 1
  ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El jefe de centro debe ser un usuario activo habilitado para aprobar REQ.');
    LEAVE sp_main;
  END IF;

  IF v_centrocostojefetecnicoid IS NOT NULL AND v_centrocostojefetecnicoid > 0 AND NOT EXISTS (
    SELECT 1
    FROM `usuarios`
    WHERE `usuarioid` = v_centrocostojefetecnicoid
      AND `usuarioactivo` = 1
      AND `usuariobloqueado` = 0
      AND `usuariopermiteaprobreq` = 1
  ) THEN
    SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El jefe tecnico debe ser un usuario activo habilitado para aprobar REQ.');
    LEAVE sp_main;
  END IF;

  SELECT JSON_OBJECT(
    'centrocostoid', `centrocostoid`,
    'empresaid', `empresaid`,
    'centrocostocod', `centrocostocod`,
    'centrocostodsc', `centrocostodsc`,
    'centrocostodescripcion', `centrocostodescripcion`,
    'erpcentrocostocod', `erpcentrocostocod`,
    'centrocostojefeusuarioid', `centrocostojefeusuarioid`,
    'centrocostojefetecnicoid', `centrocostojefetecnicoid`,
    'centrocostogerenteproduccionid', `centrocostogerenteproduccionid`,
    'centrocostoactivo', `centrocostoactivo`,
    'sincfechahora', `sincfechahora`,
    'auditcreacionusuarioid', `auditcreacionusuarioid`,
    'auditcreaciondispositivo', `auditcreaciondispositivo`,
    'auditcreacionip', `auditcreacionip`,
    'auditcreacionfechahora', `auditcreacionfechahora`,
    'auditedicionusuarioid', `auditedicionusuarioid`,
    'auditediciondispositivo', `auditediciondispositivo`,
    'auditedicionip', `auditedicionip`,
    'auditedicionfechahora', `auditedicionfechahora`
  )
  INTO v_prev_bkpjson
  FROM `centroscosto`
  WHERE `centrocostoid` = v_centrocostoid
  LIMIT 1;

  INSERT INTO `centroscostolog` (
    `centrocostoid`,
    `logusuarioid`,
    `logdispositivo`,
    `logip`,
    `logtipo`,
    `logparamjson`,
    `logregbkpjson`
  ) VALUES (
    v_centrocostoid,
    p_in_usuarioid,
    p_in_dispositivo,
    p_in_ip,
    'EDT',
    p_in_json,
    v_prev_bkpjson
  );

  UPDATE `centroscosto`
  SET `centrocostojefeusuarioid` = v_centrocostojefeusuarioid,
      `centrocostojefetecnicoid` = v_centrocostojefetecnicoid,
      `auditedicionusuarioid` = p_in_usuarioid,
      `auditediciondispositivo` = p_in_dispositivo,
      `auditedicionip` = p_in_ip,
      `auditedicionfechahora` = NOW()
  WHERE `centrocostoid` = v_centrocostoid;

  SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Centro de costo actualizado correctamente.');
END//

DELIMITER ;
