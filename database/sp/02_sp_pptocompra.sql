DELIMITER //

DROP PROCEDURE IF EXISTS sp_pptocompra_listar//
CREATE PROCEDURE sp_pptocompra_listar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_filtroPptocompraid INT DEFAULT NULL;
    DECLARE v_filtroTemporadaid INT DEFAULT NULL;
    DECLARE v_filtroSubfamiliaid INT DEFAULT NULL;
    DECLARE v_filtroCentrocostoid INT DEFAULT NULL;
    DECLARE v_filtroTemporadacod VARCHAR(30);
    DECLARE v_filtroPptocompraactivo INT DEFAULT NULL;

    SET v_filtroPptocompraid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), 'null') AS SIGNED);
    SET v_filtroTemporadaid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTemporadaid')), 'null') AS SIGNED);
    SET v_filtroSubfamiliaid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroSubfamiliaid')), 'null') AS SIGNED);
    SET v_filtroCentrocostoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentrocostoid')), 'null') AS SIGNED);
    SET v_filtroTemporadacod = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTemporadatipo'));
    SET v_filtroPptocompraactivo = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraactivo')), 'null') AS SIGNED);

    SELECT
        pc.pptocompraid,
        pc.temporadaid,
        t.temporadatipocodigo,
        t.temporadadescripcion,
        t.temporadainicio,
        t.temporadafin,
        pc.subfamiliaid,
        sf.subfamiliacod,
        sf.subfamiliadsc,
        pc.centrocostoid,
        cc.centrocostocod,
        cc.centrocostodsc,
        pc.pptocompraactivo,
        IFNULL(m.monto_base, 0) AS presupuestado,
        IFNULL(a.ajuste_pos, 0) AS ajustespositivos,
        IFNULL(a.ajuste_neg, 0) AS ajustesnegativos,
        (IFNULL(m.monto_base, 0) + IFNULL(a.ajuste_pos, 0) + IFNULL(a.ajuste_neg, 0)) AS reproyectado,
        IFNULL(a.consumo_encurso, 0) AS consumosencurso,
        IFNULL(a.consumo_confirmado, 0) AS consumosconfirmados,
        (IFNULL(m.monto_base, 0)
            + IFNULL(a.ajuste_pos, 0)
            + IFNULL(a.ajuste_neg, 0)
            + IFNULL(a.consumo_encurso, 0)
            + IFNULL(a.consumo_confirmado, 0)) AS saldodisponible,
        IFNULL(x.total_periodos, 0) AS total_periodos
    FROM `pptocompra` pc
    INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
    INNER JOIN `subfamilias` sf ON sf.subfamiliaid = pc.subfamiliaid
    INNER JOIN `centroscosto` cc ON cc.centrocostoid = pc.centrocostoid
    LEFT JOIN (
        SELECT `pptocompraid`, SUM(ppomontoppto) AS monto_base
        FROM `pptocompramensual`
        GROUP BY `pptocompraid`
    ) m ON m.pptocompraid = pc.pptocompraid
    LEFT JOIN (
        SELECT
            tr.pptocompraid,
            SUM(CASE WHEN tr.pptocompratransacciontipoid = 'PPTO_AJUSTE_POS' THEN tr.pptocompramonto ELSE 0 END) AS ajuste_pos,
            SUM(CASE WHEN tr.pptocompratransacciontipoid = 'PPTO_AJUSTE_NEG' THEN tr.pptocompramonto ELSE 0 END) AS ajuste_neg,
            SUM(COALESCE(tr.pptocompramontoencurso, 0)) AS consumo_encurso,
            SUM(COALESCE(tr.pptocompramontoconfirmado, 0)) AS consumo_confirmado
        FROM `pptocompratransacciones` tr
        GROUP BY tr.pptocompraid
    ) a ON a.pptocompraid = pc.pptocompraid
    LEFT JOIN (
        SELECT `pptocompraid`, COUNT(*) AS total_periodos
        FROM `pptocompramensual`
        GROUP BY `pptocompraid`
    ) x ON x.pptocompraid = pc.pptocompraid
    WHERE
        (v_filtroPptocompraid IS NULL OR pc.pptocompraid = v_filtroPptocompraid)
        AND (v_filtroTemporadaid IS NULL OR pc.temporadaid = v_filtroTemporadaid)
        AND (v_filtroSubfamiliaid IS NULL OR pc.subfamiliaid = v_filtroSubfamiliaid)
        AND (v_filtroCentrocostoid IS NULL OR pc.centrocostoid = v_filtroCentrocostoid)
        AND (v_filtroTemporadacod IS NULL OR v_filtroTemporadacod = '' OR t.temporadatipocodigo = v_filtroTemporadacod)
        AND (v_filtroPptocompraactivo IS NULL OR v_filtroPptocompraactivo = '' OR pc.pptocompraactivo = v_filtroPptocompraactivo)
    ORDER BY t.temporadadescripcion ASC, sf.subfamiliacod ASC, cc.centrocostodsc ASC, pc.pptocompraid ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_por_id//
CREATE PROCEDURE sp_pptocompra_por_id (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;
    SET v_pptocompraid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), 'null') AS SIGNED);

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'filtroPptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    CALL sp_pptocompra_listar(
        JSON_OBJECT('filtroPptocompraid', v_pptocompraid),
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        @unused
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_mensual_listar//
CREATE PROCEDURE sp_pptocompra_mensual_listar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;

    SET v_pptocompraid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), 'null') AS SIGNED);
    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'filtroPptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT
        pm.pptocompramensualid,
        pm.pptocompraid,
        pm.ppoanio,
        pm.ppomes,
        pm.ppomontoppto,
        pm.ppoobservacion,
        pm.auditcreacionusuarioid,
        pm.auditcreaciondispositivo,
        pm.auditcreacionip,
        pm.auditcreacionfechahora,
        pm.auditedicionusuarioid,
        pm.auditediciondispositivo,
        pm.auditedicionip,
        pm.auditedicionfechahora
    FROM `pptocompramensual` pm
    WHERE pm.pptocompraid = v_pptocompraid
    ORDER BY pm.ppoanio ASC, pm.ppomes ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_movimientos_listar//
CREATE PROCEDURE sp_pptocompra_movimientos_listar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;
    DECLARE v_filtroTipo VARCHAR(30);

    SET v_pptocompraid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), 'null') AS SIGNED);
    SET v_filtroTipo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTipo'));

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'filtroPptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT
        tr.pptocompratransaccionid,
        tr.pptocompraid,
        tr.ppoanio,
        tr.ppomes,
        tr.ppoanomes,
        tr.pptocompratransacciontipoid,
        tr.pptocompramonto,
        tr.pptocompramontoencurso,
        tr.pptocompramontoconfirmado,
        tr.pptocompramotivo,
        tr.pptocompregenciaorigen,
        tr.pptocomprareflinea,
        tr.pptocompregruppomovimiento,
        tr.auditcreacionusuarioid,
        tr.auditcreaciondispositivo,
        tr.auditcreacionip,
        tr.auditcreacionfechahora,
        tt.pptocompratransacciontipodsc
    FROM `pptocompratransacciones` tr
    INNER JOIN `pptocompratransaccionestipo` tt
        ON tt.pptocompratransacciontipoid = tr.pptocompratransacciontipoid
    WHERE tr.pptocompraid = v_pptocompraid
      AND (v_filtroTipo IS NULL OR v_filtroTipo = '' OR tr.pptocompratransacciontipoid = v_filtroTipo)
    ORDER BY tr.auditcreacionfechahora DESC, tr.pptocompratransaccionid DESC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_crear//
CREATE PROCEDURE sp_pptocompra_crear (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_temporadaid INT DEFAULT NULL;
    DECLARE v_subfamiliaid INT DEFAULT NULL;
    DECLARE v_centrocostoid INT DEFAULT NULL;
    DECLARE v_temporadaInicio DATE;
    DECLARE v_temporadaFin DATE;
    DECLARE v_temporadaActivo TINYINT(1);
    DECLARE v_mensual JSON;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_registro JSON;
    DECLARE v_ppoanio INT;
    DECLARE v_ppomes INT;
    DECLARE v_ppomonto DECIMAL(18,2);
    DECLARE v_ppoobservacion VARCHAR(500);
    DECLARE v_fechaPeriodo DATE;
    DECLARE v_pptocompraid INT;

    SET v_temporadaid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.temporadaid')), 'null') AS SIGNED);
    SET v_subfamiliaid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.subfamiliaid')), 'null') AS SIGNED);
    SET v_centrocostoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), 'null') AS SIGNED);
    SET v_mensual = JSON_EXTRACT(p_in_json, '$.mensual');

    IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required');
        LEAVE sp_main;
    END IF;

    IF v_temporadaid IS NULL OR v_temporadaid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'temporadaid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_subfamiliaid IS NULL OR v_subfamiliaid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'subfamiliaid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_centrocostoid IS NULL OR v_centrocostoid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'centrocostoid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT
        t.temporadainicio,
        t.temporadafin,
        t.temporadaactivo
    INTO
        v_temporadaInicio,
        v_temporadaFin,
        v_temporadaActivo
    FROM `temporadas` t
    WHERE t.temporadaid = v_temporadaid
    LIMIT 1;

    IF v_temporadaInicio IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Temporada no encontrada.');
        LEAVE sp_main;
    END IF;

    IF v_temporadaActivo <> 1 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La temporada debe estar activa.');
        LEAVE sp_main;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM `subfamilias` sf WHERE sf.subfamiliaid = v_subfamiliaid AND sf.subfamiliaactivo = 1) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Subfamilia no encontrada o inactiva.');
        LEAVE sp_main;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM `centroscosto` cc WHERE cc.centrocostoid = v_centrocostoid AND cc.centrocostoactivo = 1) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Centro de costo no encontrado o inactivo.');
        LEAVE sp_main;
    END IF;

    IF EXISTS (
        SELECT 1
        FROM `pptocompra` pc
        WHERE pc.temporadaid = v_temporadaid
          AND pc.subfamiliaid = v_subfamiliaid
          AND pc.centrocostoid = v_centrocostoid
          AND pc.pptocompraactivo = 1
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Ya existe presupuesto activo para esta combinación.');
        LEAVE sp_main;
    END IF;

    IF v_mensual IS NULL OR JSON_TYPE(v_mensual) <> 'ARRAY' OR JSON_LENGTH(v_mensual) = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe enviar al menos un mes con monto de presupuesto.');
        LEAVE sp_main;
    END IF;

    SET v_total = JSON_LENGTH(v_mensual);
    SET v_index = 0;
    WHILE v_index < v_total DO
        SET v_registro = JSON_EXTRACT(v_mensual, CONCAT('$[', v_index, ']'));
        SET v_ppoanio = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoanio')), 'null') AS SIGNED);
        SET v_ppomes = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomes')), 'null') AS SIGNED);
        SET v_ppomonto = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomontoppto')), 'null') AS DECIMAL(18,2));

        IF v_ppoanio IS NULL OR v_ppomes IS NULL OR v_ppoanio < 2000 OR v_ppoanio > 2200 OR v_ppomes < 1 OR v_ppomes > 12 OR v_ppomonto IS NULL THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Registro mensual inválido.');
            LEAVE sp_main;
        END IF;

        SET v_fechaPeriodo = STR_TO_DATE(CONCAT(v_ppoanio, '-', LPAD(v_ppomes, 2, '0'), '-01'), '%Y-%m-%d');
        IF v_fechaPeriodo < v_temporadaInicio OR v_fechaPeriodo > v_temporadaFin THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Existe un periodo fuera del rango de la temporada.');
            LEAVE sp_main;
        END IF;

        SET v_index = v_index + 1;
    END WHILE;

    INSERT INTO `pptocompra` (
        `temporadaid`,
        `subfamiliaid`,
        `centrocostoid`,
        `auditcreacionusuarioid`,
        `auditcreaciondispositivo`,
        `auditcreacionip`
    ) VALUES (
        v_temporadaid,
        v_subfamiliaid,
        v_centrocostoid,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    );

    SET v_pptocompraid = LAST_INSERT_ID();

    SET v_index = 0;
    WHILE v_index < v_total DO
        SET v_registro = JSON_EXTRACT(v_mensual, CONCAT('$[', v_index, ']'));
        SET v_ppoanio = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoanio')), 'null') AS SIGNED);
        SET v_ppomes = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomes')), 'null') AS SIGNED);
        SET v_ppomonto = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomontoppto')), 'null') AS DECIMAL(18,2));
        SET v_ppoobservacion = JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoobservacion'));

        INSERT INTO `pptocompramensual` (
            `pptocompraid`,
            `ppoanio`,
            `ppomes`,
            `ppomontoppto`,
            `ppoobservacion`,
            `auditcreacionusuarioid`,
            `auditcreaciondispositivo`,
            `auditcreacionip`
        ) VALUES (
            v_pptocompraid,
            v_ppoanio,
            v_ppomes,
            v_ppomonto,
            v_ppoobservacion,
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        )
        ON DUPLICATE KEY UPDATE
            `ppomontoppto` = VALUES(`ppomontoppto`),
            `ppoobservacion` = VALUES(`ppoobservacion`),
            `auditedicionusuarioid` = p_in_usuarioid,
            `auditediciondispositivo` = p_in_dispositivo,
            `auditedicionip` = p_in_ip,
            `auditedicionfechahora` = NOW();

        SET v_index = v_index + 1;
    END WHILE;

    INSERT INTO `pptocompralog` (
        `pptocompraid`,
        `logusuarioid`,
        `logdispositivo`,
        `logip`,
        `logtipo`,
        `logparamjson`,
        `logregbkpjson`
    ) VALUES (
        v_pptocompraid,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        'INS',
        p_in_json,
        '{}'
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Presupuesto creado correctamente.');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_actualizar//
CREATE PROCEDURE sp_pptocompra_actualizar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;
    DECLARE v_efecto_monto DECIMAL(18,2) DEFAULT 0;
    DECLARE v_temporadaid INT;
    DECLARE v_subfamiliaid INT;
    DECLARE v_centrocostoid INT;
    DECLARE v_mensual JSON;
    DECLARE v_movimientos INT DEFAULT 0;
    DECLARE v_prev_bkpjson JSON;

    DECLARE v_index INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_registro JSON;
    DECLARE v_ppoanio INT;
    DECLARE v_ppomes INT;
    DECLARE v_ppomonto DECIMAL(18,2);
    DECLARE v_ppoobservacion VARCHAR(500);

    SET v_pptocompraid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraid')), 'null') AS SIGNED);
    SET v_temporadaid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.temporadaid')), 'null') AS SIGNED);
    SET v_subfamiliaid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.subfamiliaid')), 'null') AS SIGNED);
    SET v_centrocostoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), 'null') AS SIGNED);
    SET v_mensual = JSON_EXTRACT(p_in_json, '$.mensual');

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'pptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    IF v_temporadaid IS NULL OR v_subfamiliaid IS NULL OR v_centrocostoid IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'temporadaid, subfamiliaid y centrocostoid son obligatorios.');
        LEAVE sp_main;
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM `pptocompra` pc
        WHERE pc.pptocompraid = v_pptocompraid
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Presupuesto no encontrado.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_movimientos
      FROM `pptocompratransacciones` tr
      WHERE tr.pptocompraid = v_pptocompraid;

    IF v_movimientos > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No se puede editar la carga base de un presupuesto con movimientos registrados.');
        LEAVE sp_main;
    END IF;

    IF v_mensual IS NULL OR JSON_TYPE(v_mensual) <> 'ARRAY' OR JSON_LENGTH(v_mensual) = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe enviar al menos un mes con monto de presupuesto.');
        LEAVE sp_main;
    END IF;

    SELECT JSON_OBJECT(
        'pptocompraid', pc.pptocompraid,
        'temporadaid', pc.temporadaid,
        'subfamiliaid', pc.subfamiliaid,
        'centrocostoid', pc.centrocostoid,
        'auditcreacionusuarioid', pc.auditcreacionusuarioid,
        'auditcreaciondispositivo', pc.auditcreaciondispositivo,
        'auditcreacionip', pc.auditcreacionip
    ) INTO v_prev_bkpjson
    FROM `pptocompra` pc
    WHERE pc.pptocompraid = v_pptocompraid
    LIMIT 1;

    UPDATE `pptocompra`
      SET `temporadaid` = v_temporadaid,
          `subfamiliaid` = v_subfamiliaid,
          `centrocostoid` = v_centrocostoid,
          `auditedicionusuarioid` = p_in_usuarioid,
          `auditediciondispositivo` = p_in_dispositivo,
          `auditedicionip` = p_in_ip,
          `auditedicionfechahora` = NOW()
    WHERE `pptocompraid` = v_pptocompraid;

    DELETE FROM `pptocompramensual`
    WHERE `pptocompraid` = v_pptocompraid;

    SET v_total = JSON_LENGTH(v_mensual);
    SET v_index = 0;
    WHILE v_index < v_total DO
        SET v_registro = JSON_EXTRACT(v_mensual, CONCAT('$[', v_index, ']'));
        SET v_ppoanio = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoanio')), 'null') AS SIGNED);
        SET v_ppomes = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomes')), 'null') AS SIGNED);
        SET v_ppomonto = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomontoppto')), 'null') AS DECIMAL(18,2));
        SET v_ppoobservacion = JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoobservacion'));

        IF v_ppoanio IS NULL OR v_ppomes IS NULL OR v_ppoanio < 2000 OR v_ppoanio > 2200 OR v_ppomes < 1 OR v_ppomes > 12 OR v_ppomonto IS NULL THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Registro mensual inválido.');
            LEAVE sp_main;
        END IF;

        INSERT INTO `pptocompramensual` (
            `pptocompraid`,
            `ppoanio`,
            `ppomes`,
            `ppomontoppto`,
            `ppoobservacion`,
            `auditcreacionusuarioid`,
            `auditcreaciondispositivo`,
            `auditcreacionip`,
            `auditedicionusuarioid`,
            `auditediciondispositivo`,
            `auditedicionip`
        ) VALUES (
            v_pptocompraid,
            v_ppoanio,
            v_ppomes,
            v_ppomonto,
            v_ppoobservacion,
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip,
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        );

        SET v_index = v_index + 1;
    END WHILE;

    INSERT INTO `pptocompralog` (
        `pptocompraid`,
        `logusuarioid`,
        `logdispositivo`,
        `logip`,
        `logtipo`,
        `logparamjson`,
        `logregbkpjson`
    ) VALUES (
        v_pptocompraid,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        'EDT',
        p_in_json,
        v_prev_bkpjson
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Presupuesto actualizado correctamente.');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_anular//
CREATE PROCEDURE sp_pptocompra_anular (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;
    DECLARE v_prev_bkpjson JSON;

    SET v_pptocompraid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraid')), 'null') AS SIGNED);

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'pptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT JSON_OBJECT(
        'pptocompraid', pc.pptocompraid,
        'temporadaid', pc.temporadaid,
        'subfamiliaid', pc.subfamiliaid,
        'centrocostoid', pc.centrocostoid,
        'pptocompraactivo', pc.pptocompraactivo
    ) INTO v_prev_bkpjson
    FROM `pptocompra` pc
    WHERE pc.pptocompraid = v_pptocompraid
    LIMIT 1;

    IF v_prev_bkpjson IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Presupuesto no encontrado.');
        LEAVE sp_main;
    END IF;

    UPDATE `pptocompra`
      SET `pptocompraactivo` = 0,
          `auditedicionusuarioid` = p_in_usuarioid,
          `auditediciondispositivo` = p_in_dispositivo,
          `auditedicionip` = p_in_ip,
          `auditedicionfechahora` = NOW()
    WHERE `pptocompraid` = v_pptocompraid;

    INSERT INTO `pptocompralog` (
        `pptocompraid`,
        `logusuarioid`,
        `logdispositivo`,
        `logip`,
        `logtipo`,
        `logparamjson`,
        `logregbkpjson`
    ) VALUES (
        v_pptocompraid,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        'ANL',
        p_in_json,
        v_prev_bkpjson
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Presupuesto anulado correctamente.');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_ajustar//
CREATE PROCEDURE sp_pptocompra_ajustar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;
    DECLARE v_ppoanio INT DEFAULT NULL;
    DECLARE v_ppomes INT DEFAULT NULL;
    DECLARE v_monto DECIMAL(18,2) DEFAULT NULL;
    DECLARE v_tipo VARCHAR(30);
    DECLARE v_motivo VARCHAR(500);
    DECLARE v_referencia_origen VARCHAR(150);
    DECLARE v_referencia_linea VARCHAR(150);
    DECLARE v_grupo VARCHAR(50);
    DECLARE v_temporadaInicio DATE;
    DECLARE v_temporadaFin DATE;
    DECLARE v_fechaPeriodo DATE;
    DECLARE v_montoAjuste DECIMAL(18,2);

    SET v_pptocompraid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraid')), 'null') AS SIGNED);
    SET v_ppoanio = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.ppoanio')), 'null') AS SIGNED);
    SET v_ppomes = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.ppomes')), 'null') AS SIGNED);
    SET v_monto = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompramonto')), 'null') AS DECIMAL(18,2));
    SET v_tipo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompratransacciontipoid'));
    SET v_motivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompramotivo'));
    SET v_referencia_origen = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompregenciaorigen'));
    SET v_referencia_linea = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocomprareflinea'));
    SET v_grupo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompregruppomovimiento'));

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'pptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_ppoanio IS NULL OR v_ppomes IS NULL OR v_ppoanio < 2000 OR v_ppoanio > 2200 OR v_ppomes < 1 OR v_ppomes > 12 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Período invalido.');
        LEAVE sp_main;
    END IF;
    IF v_monto IS NULL OR v_monto <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El monto debe ser mayor a 0.');
        LEAVE sp_main;
    END IF;
    IF v_tipo IS NULL OR (v_tipo <> 'PPTO_AJUSTE_POS' AND v_tipo <> 'PPTO_AJUSTE_NEG') THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Tipo de ajuste invalido.');
        LEAVE sp_main;
    END IF;
    IF v_motivo IS NULL OR TRIM(v_motivo) = '' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El motivo es obligatorio.');
        LEAVE sp_main;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM `pptocompra` pc WHERE pc.pptocompraid = v_pptocompraid AND pc.pptocompraactivo = 1) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El presupuesto no existe o se encuentra anulado.');
        LEAVE sp_main;
    END IF;

    SELECT t.temporadainicio, t.temporadafin
      INTO v_temporadaInicio, v_temporadaFin
      FROM `pptocompra` pc
      INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
      WHERE pc.pptocompraid = v_pptocompraid
      LIMIT 1;

    SET v_fechaPeriodo = STR_TO_DATE(CONCAT(v_ppoanio, '-', LPAD(v_ppomes, 2, '0'), '-01'), '%Y-%m-%d');
    IF v_fechaPeriodo < v_temporadaInicio OR v_fechaPeriodo > v_temporadaFin THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Período fuera del rango de la temporada.');
        LEAVE sp_main;
    END IF;

    IF v_tipo = 'PPTO_AJUSTE_NEG' THEN
        SET v_montoAjuste = -ABS(v_monto);
    ELSE
        SET v_montoAjuste = ABS(v_monto);
    END IF;

    INSERT INTO `pptocompratransacciones` (
        `pptocompraid`,
        `ppoanio`,
        `ppomes`,
        `pptocompratransacciontipoid`,
        `pptocompramonto`,
        `pptocompramontoencurso`,
        `pptocompramontoconfirmado`,
        `pptocompramotivo`,
        `pptocompregenciaorigen`,
        `pptocomprareflinea`,
        `pptocompregruppomovimiento`,
        `auditcreacionusuarioid`,
        `auditcreaciondispositivo`,
        `auditcreacionip`
    ) VALUES (
        v_pptocompraid,
        v_ppoanio,
        v_ppomes,
        v_tipo,
        v_montoAjuste,
        0,
        0,
        v_motivo,
        v_referencia_origen,
        v_referencia_linea,
        v_grupo,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Ajuste registrado correctamente.');
END//

DELIMITER ;
