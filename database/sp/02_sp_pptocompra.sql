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
    DECLARE v_filtroPptocompraactivo INT DEFAULT NULL;

    SET v_filtroPptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), ''), 'null') AS SIGNED);
    SET v_filtroTemporadaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTemporadaid')), ''), 'null') AS SIGNED);
    SET v_filtroSubfamiliaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroSubfamiliaid')), ''), 'null') AS SIGNED);
    SET v_filtroCentrocostoid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentrocostoid')), ''), 'null') AS SIGNED);
    SET v_filtroPptocompraactivo = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraactivo')), ''), 'null') AS SIGNED);

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
        pc.pptocompraobservacion,
        IFNULL(pc.pptocomprapresupuestado, 0) AS presupuestado,
        IFNULL(pc.pptocompraajustespositivos, 0) AS ajustespositivos,
        IFNULL(pc.pptocompraajustenegativos, 0) AS ajustesnegativos,
        IFNULL(pc.pptocompreproyectado, 0) AS reproyectado,
        IFNULL(pc.pptocompramontoconsumidopnd, 0) AS consumosencurso,
        IFNULL(pc.pptocompramontoconsumidocnf, 0) AS consumosconfirmados,
        IFNULL(pc.pptocomprasaldodisponible, 0) AS saldodisponible,
        IFNULL(x.total_periodos, 0) AS total_periodos
    FROM `pptocompra` pc
    INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
    INNER JOIN `subfamilias` sf ON sf.subfamiliaid = pc.subfamiliaid
    INNER JOIN `centroscosto` cc ON cc.centrocostoid = pc.centrocostoid
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
        AND t.temporadatipocodigo = 'PPTO_COMPRAS'
        AND (v_filtroPptocompraactivo IS NULL OR pc.pptocompraactivo = v_filtroPptocompraactivo)
    ORDER BY t.temporadadescripcion ASC, sf.subfamiliacod ASC, cc.centrocostodsc ASC, pc.pptocompraid ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_listar_query//
CREATE PROCEDURE sp_pptocompra_listar_query (
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
    DECLARE v_filtroPptocompraactivo INT DEFAULT NULL;

    SET v_filtroPptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), ''), 'null') AS SIGNED);
    SET v_filtroTemporadaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTemporadaid')), ''), 'null') AS SIGNED);
    SET v_filtroSubfamiliaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroSubfamiliaid')), ''), 'null') AS SIGNED);
    SET v_filtroCentrocostoid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentrocostoid')), ''), 'null') AS SIGNED);
    SET v_filtroPptocompraactivo = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraactivo')), ''), 'null') AS SIGNED);

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
        pc.pptocompraobservacion,
        IFNULL(m.monto_base, 0) AS presupuestado,
        IFNULL(a.ajuste_pos, 0) AS ajustespositivos,
        IFNULL(a.ajuste_neg, 0) AS ajustesnegativos,
        (IFNULL(m.monto_base, 0) + IFNULL(a.ajuste_pos, 0) + IFNULL(a.ajuste_neg, 0) + IFNULL(a.traspasos, 0)) AS reproyectado,
        IFNULL(a.consumo_encurso, 0) AS consumosencurso,
        IFNULL(a.consumo_confirmado, 0) AS consumosconfirmados,
        (IFNULL(m.monto_base, 0)
            + IFNULL(a.ajuste_pos, 0)
            + IFNULL(a.ajuste_neg, 0)
            + IFNULL(a.traspasos, 0)
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
            SUM(CASE WHEN tr.pptocompratransacciontipoid IN ('PPTO_TRASPASO_ENTRADA', 'PPTO_TRASPASO_SALIDA') THEN tr.pptocompramonto ELSE 0 END) AS traspasos,
            SUM(CASE WHEN tr.pptocompratransacciontipoid = 'POC_RESERVA' THEN COALESCE(tr.pptocompramontoencurso, 0) ELSE 0 END) AS consumo_encurso,
            SUM(CASE WHEN tr.pptocompratransacciontipoid IN ('POC_CONFIRMACION', 'POC_REVERSA') THEN COALESCE(tr.pptocompramontoconfirmado, 0) ELSE 0 END) AS consumo_confirmado
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
        AND t.temporadatipocodigo = 'PPTO_COMPRAS'
        AND (v_filtroPptocompraactivo IS NULL OR pc.pptocompraactivo = v_filtroPptocompraactivo)
    ORDER BY t.temporadadescripcion ASC, sf.subfamiliacod ASC, cc.centrocostodsc ASC, pc.pptocompraid ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_listar_calculos//
CREATE PROCEDURE sp_pptocompra_listar_calculos (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    CALL sp_pptocompra_listar(p_in_json, p_in_usuarioid, p_in_dispositivo, p_in_ip, p_out_json);
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
    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), ''), 'null') AS SIGNED);

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'filtroPptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    CALL sp_pptocompra_listar(JSON_OBJECT('filtroPptocompraid', v_pptocompraid), p_in_usuarioid, p_in_dispositivo, p_in_ip, @unused);
    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_recalcular_totales//
CREATE PROCEDURE sp_pptocompra_recalcular_totales (
    IN p_pptocompraid INT
)
sp_main: BEGIN
    DECLARE v_pptocomprapresupuestado DECIMAL(18,2) DEFAULT 0;
    DECLARE v_pptocompraajustespositivos DECIMAL(18,2) DEFAULT 0;
    DECLARE v_pptocompraajustenegativos DECIMAL(18,2) DEFAULT 0;
    DECLARE v_pptocompratraspasos DECIMAL(18,2) DEFAULT 0;
    DECLARE v_pptocompramontoconsumidopnd DECIMAL(18,2) DEFAULT 0;
    DECLARE v_pptocompramontoconsumidocnf DECIMAL(18,2) DEFAULT 0;
    DECLARE v_pptocompreproyectado DECIMAL(18,2) DEFAULT 0;
    DECLARE v_pptocomprasaldodisponible DECIMAL(18,2) DEFAULT 0;
    DECLARE v_cargaAnio INT DEFAULT NULL;
    DECLARE v_cargaMes INT DEFAULT NULL;
    DECLARE v_cargaFecha DATE DEFAULT NULL;
    DECLARE v_cargaTransaccionid INT DEFAULT NULL;

    IF p_pptocompraid IS NULL OR p_pptocompraid <= 0 THEN
        LEAVE sp_main;
    END IF;

    SELECT IFNULL(SUM(ppomontoppto), 0)
      INTO v_pptocomprapresupuestado
      FROM `pptocompramensual`
      WHERE `pptocompraid` = p_pptocompraid;

    SELECT
        IFNULL(SUM(CASE WHEN `pptocompratransacciontipoid` = 'PPTO_AJUSTE_POS' THEN `pptocompramonto` ELSE 0 END), 0),
        IFNULL(SUM(CASE WHEN `pptocompratransacciontipoid` = 'PPTO_AJUSTE_NEG' THEN `pptocompramonto` ELSE 0 END), 0),
        IFNULL(SUM(CASE WHEN `pptocompratransacciontipoid` IN ('PPTO_TRASPASO_ENTRADA', 'PPTO_TRASPASO_SALIDA') THEN `pptocompramonto` ELSE 0 END), 0),
        IFNULL(SUM(CASE WHEN `pptocompratransacciontipoid` = 'POC_RESERVA' THEN `pptocompramontoencurso` ELSE 0 END), 0),
        IFNULL(SUM(CASE WHEN `pptocompratransacciontipoid` IN ('POC_CONFIRMACION', 'POC_REVERSA') THEN `pptocompramontoconfirmado` ELSE 0 END), 0)
      INTO
        v_pptocompraajustespositivos,
        v_pptocompraajustenegativos,
        v_pptocompratraspasos,
        v_pptocompramontoconsumidopnd,
        v_pptocompramontoconsumidocnf
      FROM `pptocompratransacciones`
      WHERE `pptocompraid` = p_pptocompraid;

    SET v_pptocompreproyectado = v_pptocomprapresupuestado
        + v_pptocompraajustespositivos
        + v_pptocompraajustenegativos
        + v_pptocompratraspasos;
    SET v_pptocomprasaldodisponible = v_pptocompreproyectado
        + v_pptocompramontoconsumidopnd
        + v_pptocompramontoconsumidocnf;

    SELECT pm.ppoanio, pm.ppomes
      INTO v_cargaAnio, v_cargaMes
      FROM `pptocompramensual` pm
     WHERE pm.pptocompraid = p_pptocompraid
     ORDER BY pm.ppoanio ASC, pm.ppomes ASC
     LIMIT 1;

    SELECT t.temporadainicio
      INTO v_cargaFecha
      FROM `pptocompra` pc
      INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
     WHERE pc.pptocompraid = p_pptocompraid
     LIMIT 1;

    IF v_cargaAnio IS NULL OR v_cargaMes IS NULL THEN
        SET v_cargaAnio = YEAR(IFNULL(v_cargaFecha, CURDATE()));
        SET v_cargaMes = MONTH(IFNULL(v_cargaFecha, CURDATE()));
    END IF;

    IF v_cargaFecha IS NULL THEN
        SET v_cargaFecha = STR_TO_DATE(CONCAT(v_cargaAnio, '-', LPAD(v_cargaMes, 2, '0'), '-01'), '%Y-%m-%d');
    END IF;

    SELECT tr.pptocompratransaccionid
      INTO v_cargaTransaccionid
      FROM `pptocompratransacciones` tr
     WHERE tr.pptocompraid = p_pptocompraid
       AND tr.pptocompratransacciontipoid = 'PPTO_CARGA'
     ORDER BY tr.pptocompratransaccionid ASC
     LIMIT 1;

    IF v_cargaTransaccionid IS NULL THEN
        INSERT INTO `pptocompratransacciones` (
            `pptocompraid`,
            `ppoanio`,
            `ppomes`,
            `pptocompratransacciontipoid`,
            `pptocompratransaccionfecha`,
            `pptocompramonto`,
            `pptocompramotivo`,
            `pptocompranrodocumentoorigen`,
            `pptocompramoduloorigen`,
            `pptocompraestado`,
            `auditcreacionusuarioid`,
            `auditcreaciondispositivo`,
            `auditcreacionip`
        )
        SELECT
            p_pptocompraid,
            v_cargaAnio,
            v_cargaMes,
            'PPTO_CARGA',
            v_cargaFecha,
            v_pptocomprapresupuestado,
            'Carga base del presupuesto',
            0,
            'PPTO_COMPRA',
            'CONFIRMADO',
            pc.auditcreacionusuarioid,
            pc.auditcreaciondispositivo,
            pc.auditcreacionip
        FROM `pptocompra` pc
        WHERE pc.pptocompraid = p_pptocompraid
        LIMIT 1;
    ELSE
        UPDATE `pptocompratransacciones`
           SET `ppoanio` = v_cargaAnio,
               `ppomes` = v_cargaMes,
               `pptocompratransaccionfecha` = v_cargaFecha,
               `pptocompramonto` = v_pptocomprapresupuestado,
               `pptocompramotivo` = 'Carga base del presupuesto',
               `pptocompranrodocumentoorigen` = 0,
               `pptocompramoduloorigen` = 'PPTO_COMPRA',
               `pptocompraestado` = 'CONFIRMADO'
         WHERE `pptocompratransaccionid` = v_cargaTransaccionid;

        DELETE FROM `pptocompratransacciones`
         WHERE `pptocompraid` = p_pptocompraid
           AND `pptocompratransacciontipoid` = 'PPTO_CARGA'
           AND `pptocompratransaccionid` <> v_cargaTransaccionid;
    END IF;

    UPDATE `pptocompra`
      SET
          `pptocomprapresupuestado` = v_pptocomprapresupuestado,
          `pptocompraajustespositivos` = v_pptocompraajustespositivos,
          `pptocompraajustenegativos` = v_pptocompraajustenegativos,
          `pptocompreproyectado` = v_pptocompreproyectado,
          `pptocompramontoconsumidopnd` = v_pptocompramontoconsumidopnd,
          `pptocompramontoconsumidocnf` = v_pptocompramontoconsumidocnf,
          `pptocomprasaldodisponible` = v_pptocomprasaldodisponible
      WHERE `pptocompraid` = p_pptocompraid;
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

    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), ''), 'null') AS SIGNED);
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

    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), ''), 'null') AS SIGNED);
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
        tr.pptocompratransaccionfecha,
        tr.pptocompramonto,
        tr.pptocompramontoencurso,
        tr.pptocompramontoconfirmado,
        tr.pptocompramotivo,
        tr.pptocompranrodocumentoorigen,
        tr.pptocompramoduloorigen,
        tr.pptocompraestado,
        tr.pptocompregenciaorigen,
        tr.pptocomprareflinea,
        tr.pptocompregruppomovimiento,
        tr.auditcreacionusuarioid,
        COALESCE(u.usuarionombre, CONCAT('Usuario #', tr.auditcreacionusuarioid)) AS auditcreacionusuarionombre,
        tr.auditcreaciondispositivo,
        tr.auditcreacionip,
        tr.auditcreacionfechahora,
        tt.pptocompratransacciontipodsc
    FROM `pptocompratransacciones` tr
    INNER JOIN `pptocompratransaccionestipo` tt
        ON tt.pptocompratransacciontipoid = tr.pptocompratransacciontipoid
    LEFT JOIN `usuarios` u
        ON u.usuarioid = tr.auditcreacionusuarioid
    WHERE tr.pptocompraid = v_pptocompraid
      AND (v_filtroTipo IS NULL OR v_filtroTipo = '' OR tr.pptocompratransacciontipoid = v_filtroTipo)
    ORDER BY tr.pptocompratransaccionfecha DESC, tr.auditcreacionfechahora DESC, tr.pptocompratransaccionid DESC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_movimientos_listar_calculos//
CREATE PROCEDURE sp_pptocompra_movimientos_listar_calculos (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;
    DECLARE v_filtroTipo VARCHAR(30);
    DECLARE v_basePresupuesto DECIMAL(18,2) DEFAULT 0;
    DECLARE v_ppoanio INT DEFAULT 0;
    DECLARE v_ppomes INT DEFAULT 0;
    DECLARE v_fechaCarga DATE DEFAULT NULL;
    DECLARE v_tieneCargaTabla TINYINT(1) DEFAULT 0;

    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptocompraid')), ''), 'null') AS SIGNED);
    SET v_filtroTipo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroTipo'));

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'filtroPptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM `pptocompra` pc WHERE pc.pptocompraid = v_pptocompraid) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Presupuesto no encontrado.');
        LEAVE sp_main;
    END IF;

    SELECT IFNULL(pc.pptocomprapresupuestado, 0), t.temporadainicio
      INTO v_basePresupuesto, v_fechaCarga
      FROM `pptocompra` pc
      INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
      WHERE pc.pptocompraid = v_pptocompraid
      LIMIT 1;

    SELECT EXISTS(
        SELECT 1
        FROM `pptocompratransacciones` tr
        WHERE tr.pptocompraid = v_pptocompraid
          AND tr.pptocompratransacciontipoid = 'PPTO_CARGA'
    )
      INTO v_tieneCargaTabla;

    SELECT COALESCE(MIN(pm.ppoanio), YEAR(IFNULL(v_fechaCarga, CURDATE()))),
           COALESCE(MIN(pm.ppomes), MONTH(IFNULL(v_fechaCarga, CURDATE())))
      INTO v_ppoanio, v_ppomes
      FROM `pptocompramensual` pm
      WHERE pm.pptocompraid = v_pptocompraid;

    SELECT y.*
    FROM (
        SELECT
            x.pptocompratransaccionid,
            x.pptocompraid,
            x.ppoanio,
            x.ppomes,
            x.ppoanomes,
            x.pptocompratransacciontipoid,
            x.pptocompratransaccionfecha,
            x.pptocompramonto,
            x.pptocompramontoencurso,
            x.pptocompramontoconfirmado,
            x.pptocompramotivo,
            x.pptocompranrodocumentoorigen,
            x.pptocompramoduloorigen,
            x.pptocompraestado,
            x.pptocompregenciaorigen,
            x.pptocomprareflinea,
            x.pptocompregruppomovimiento,
            x.auditcreacionusuarioid,
            x.auditcreacionusuarionombre,
            x.auditcreaciondispositivo,
            x.auditcreacionip,
            x.auditcreacionfechahora,
            x.pptocompratransacciontipodsc,
            SUM(x.impacto) OVER (
                ORDER BY
                    x.pptocompratransaccionfecha ASC,
                    CASE
                        WHEN x.pptocompratransaccionid IS NULL THEN 0
                        WHEN x.pptocompratransacciontipoid = 'PPTO_CARGA' THEN 0
                        ELSE 1
                    END ASC,
                    x.auditcreacionfechahora ASC,
                    x.pptocompratransaccionid ASC
            ) AS saldo_disponible
        FROM (
        SELECT
            NULL AS pptocompratransaccionid,
            pc.pptocompraid,
            v_ppoanio AS ppoanio,
            v_ppomes AS ppomes,
            CONCAT(v_ppoanio, '-', LPAD(v_ppomes, 2, '0')) AS ppoanomes,
            'PPTO_CARGA' AS pptocompratransacciontipoid,
            v_fechaCarga AS pptocompratransaccionfecha,
            v_basePresupuesto AS pptocompramonto,
            0 AS pptocompramontoencurso,
            0 AS pptocompramontoconfirmado,
            'Carga base del presupuesto' AS pptocompramotivo,
            0 AS pptocompranrodocumentoorigen,
            'PPTO_COMPRA' AS pptocompramoduloorigen,
            'CONFIRMADO' AS pptocompraestado,
            NULL AS pptocompregenciaorigen,
            'Carga base' AS pptocomprareflinea,
            NULL AS pptocompregruppomovimiento,
            pc.auditcreacionusuarioid AS auditcreacionusuarioid,
            COALESCE(u.usuarionombre, CONCAT('Usuario #', pc.auditcreacionusuarioid)) AS auditcreacionusuarionombre,
            pc.auditcreaciondispositivo AS auditcreaciondispositivo,
            pc.auditcreacionip AS auditcreacionip,
            pc.auditcreacionfechahora AS auditcreacionfechahora,
            'Carga base' AS pptocompratransacciontipodsc,
            v_basePresupuesto AS impacto
        FROM `pptocompra` pc
        LEFT JOIN `usuarios` u
            ON u.usuarioid = pc.auditcreacionusuarioid
        WHERE pc.pptocompraid = v_pptocompraid
          AND v_tieneCargaTabla = 0

        UNION ALL

        SELECT
            tr.pptocompratransaccionid,
            tr.pptocompraid,
            tr.ppoanio,
            tr.ppomes,
            tr.ppoanomes,
            tr.pptocompratransacciontipoid,
            tr.pptocompratransaccionfecha,
            tr.pptocompramonto,
            tr.pptocompramontoencurso,
            tr.pptocompramontoconfirmado,
            tr.pptocompramotivo,
            tr.pptocompranrodocumentoorigen,
            tr.pptocompramoduloorigen,
            tr.pptocompraestado,
            tr.pptocompregenciaorigen,
            tr.pptocomprareflinea,
            tr.pptocompregruppomovimiento,
            tr.auditcreacionusuarioid,
            COALESCE(u.usuarionombre, CONCAT('Usuario #', tr.auditcreacionusuarioid)) AS auditcreacionusuarionombre,
            tr.auditcreaciondispositivo,
            tr.auditcreacionip,
            tr.auditcreacionfechahora,
            tt.pptocompratransacciontipodsc,
            COALESCE(tr.pptocompramonto, 0) + COALESCE(tr.pptocompramontoencurso, 0) + COALESCE(tr.pptocompramontoconfirmado, 0) AS impacto
        FROM `pptocompratransacciones` tr
        INNER JOIN `pptocompratransaccionestipo` tt
            ON tt.pptocompratransacciontipoid = tr.pptocompratransacciontipoid
        LEFT JOIN `usuarios` u
            ON u.usuarioid = tr.auditcreacionusuarioid
        WHERE tr.pptocompraid = v_pptocompraid
        ) x
    ) y
    WHERE (v_filtroTipo IS NULL OR v_filtroTipo = '' OR y.pptocompratransacciontipoid = v_filtroTipo)
    ORDER BY
        y.pptocompratransaccionfecha DESC,
        y.auditcreacionfechahora DESC,
        y.pptocompratransaccionid DESC;

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
    DECLARE v_temporadaTipo VARCHAR(20);
    DECLARE v_observacion VARCHAR(500);
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

    SET v_temporadaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.temporadaid')), ''), 'null') AS SIGNED);
    SET v_subfamiliaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.subfamiliaid')), ''), 'null') AS SIGNED);
    SET v_centrocostoid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), ''), 'null') AS SIGNED);
    SET v_observacion = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraobservacion')), 'null');
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

    SELECT t.temporadainicio, t.temporadafin, t.temporadaactivo, t.temporadatipocodigo
      INTO v_temporadaInicio, v_temporadaFin, v_temporadaActivo, v_temporadaTipo
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
    IF v_temporadaTipo <> 'PPTO_COMPRAS' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La temporada debe ser de tipo PPTO_COMPRAS.');
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
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Ya existe presupuesto para esta combinación.');
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
        SET v_ppoanio = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoanio')), ''), 'null') AS SIGNED);
        SET v_ppomes = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomes')), ''), 'null') AS SIGNED);
        SET v_ppomonto = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomontoppto')), ''), 'null') AS DECIMAL(18,2));

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
        `pptocompraobservacion`,
        `auditcreacionusuarioid`,
        `auditcreaciondispositivo`,
        `auditcreacionip`
    ) VALUES (
        v_temporadaid,
        v_subfamiliaid,
        v_centrocostoid,
        v_observacion,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    );

    SET v_pptocompraid = LAST_INSERT_ID();

    SET v_index = 0;
    WHILE v_index < v_total DO
        SET v_registro = JSON_EXTRACT(v_mensual, CONCAT('$[', v_index, ']'));
        SET v_ppoanio = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoanio')), ''), 'null') AS SIGNED);
        SET v_ppomes = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomes')), ''), 'null') AS SIGNED);
        SET v_ppomonto = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomontoppto')), ''), 'null') AS DECIMAL(18,2));
        SET v_ppoobservacion = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoobservacion')), 'null');

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

    CALL sp_pptocompra_recalcular_totales(v_pptocompraid);

    INSERT INTO `pptocompralog` (`pptocompraid`, `logusuarioid`, `logdispositivo`, `logip`, `logtipo`, `logparamjson`, `logregbkpjson`)
    VALUES (v_pptocompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'INS', p_in_json, '{}');

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
    DECLARE v_temporadaid INT;
    DECLARE v_subfamiliaid INT;
    DECLARE v_centrocostoid INT;
    DECLARE v_observacion VARCHAR(500);
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
    DECLARE v_temporadaInicio DATE;
    DECLARE v_temporadaFin DATE;
    DECLARE v_temporadaActivo TINYINT(1);
    DECLARE v_temporadaTipo VARCHAR(20);
    DECLARE v_fechaPeriodo DATE;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al actualizar presupuesto de compras.');
    END;

    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraid')), ''), 'null') AS SIGNED);
    SET v_temporadaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.temporadaid')), ''), 'null') AS SIGNED);
    SET v_subfamiliaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.subfamiliaid')), ''), 'null') AS SIGNED);
    SET v_centrocostoid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), ''), 'null') AS SIGNED);
    SET v_observacion = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraobservacion')), 'null');
    SET v_mensual = JSON_EXTRACT(p_in_json, '$.mensual');

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'pptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_temporadaid IS NULL OR v_subfamiliaid IS NULL OR v_centrocostoid IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'temporadaid, subfamiliaid y centrocostoid son obligatorios.');
        LEAVE sp_main;
    END IF;

    SELECT t.temporadainicio, t.temporadafin, t.temporadaactivo, t.temporadatipocodigo
      INTO v_temporadaInicio, v_temporadaFin, v_temporadaActivo, v_temporadaTipo
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
    IF v_temporadaTipo <> 'PPTO_COMPRAS' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La temporada debe ser de tipo PPTO_COMPRAS.');
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
    IF NOT EXISTS (SELECT 1 FROM `pptocompra` pc WHERE pc.pptocompraid = v_pptocompraid) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Presupuesto no encontrado.');
        LEAVE sp_main;
    END IF;
    IF EXISTS (
        SELECT 1
        FROM `pptocompra` pc
        WHERE pc.temporadaid = v_temporadaid
          AND pc.subfamiliaid = v_subfamiliaid
          AND pc.centrocostoid = v_centrocostoid
          AND pc.pptocompraid <> v_pptocompraid
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Ya existe presupuesto para esta combinación.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_movimientos
      FROM `pptocompratransacciones` tr
     WHERE tr.pptocompraid = v_pptocompraid
       AND tr.pptocompratransacciontipoid <> 'PPTO_CARGA';

    IF v_movimientos > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No se puede editar la carga base de un presupuesto con movimientos registrados.');
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
        SET v_ppoanio = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoanio')), ''), 'null') AS SIGNED);
        SET v_ppomes = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomes')), ''), 'null') AS SIGNED);
        SET v_ppomonto = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomontoppto')), ''), 'null') AS DECIMAL(18,2));

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

    SELECT JSON_OBJECT(
        'pptocompraid', pc.pptocompraid,
        'temporadaid', pc.temporadaid,
        'subfamiliaid', pc.subfamiliaid,
        'centrocostoid', pc.centrocostoid,
        'pptocompraobservacion', pc.pptocompraobservacion,
        'auditcreacionusuarioid', pc.auditcreacionusuarioid,
        'auditcreaciondispositivo', pc.auditcreaciondispositivo,
        'auditcreacionip', pc.auditcreacionip
    ) INTO v_prev_bkpjson
    FROM `pptocompra` pc
    WHERE pc.pptocompraid = v_pptocompraid
    LIMIT 1;

    START TRANSACTION;

    UPDATE `pptocompra`
      SET `temporadaid` = v_temporadaid,
          `subfamiliaid` = v_subfamiliaid,
          `centrocostoid` = v_centrocostoid,
          `pptocompraobservacion` = v_observacion,
          `auditedicionusuarioid` = p_in_usuarioid,
          `auditediciondispositivo` = p_in_dispositivo,
          `auditedicionip` = p_in_ip,
          `auditedicionfechahora` = NOW()
    WHERE `pptocompraid` = v_pptocompraid;

    DELETE FROM `pptocompramensual`
    WHERE `pptocompraid` = v_pptocompraid;

    SET v_index = 0;
    WHILE v_index < v_total DO
        SET v_registro = JSON_EXTRACT(v_mensual, CONCAT('$[', v_index, ']'));
        SET v_ppoanio = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoanio')), ''), 'null') AS SIGNED);
        SET v_ppomes = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomes')), ''), 'null') AS SIGNED);
        SET v_ppomonto = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppomontoppto')), ''), 'null') AS DECIMAL(18,2));
        SET v_ppoobservacion = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_registro, '$.ppoobservacion')), 'null');

        IF v_ppoanio IS NULL OR v_ppomes IS NULL OR v_ppoanio < 2000 OR v_ppoanio > 2200 OR v_ppomes < 1 OR v_ppomes > 12 OR v_ppomonto IS NULL THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Registro mensual inválido.');
            LEAVE sp_main;
        END IF;

        SET v_fechaPeriodo = STR_TO_DATE(CONCAT(v_ppoanio, '-', LPAD(v_ppomes, 2, '0'), '-01'), '%Y-%m-%d');
        IF v_fechaPeriodo < v_temporadaInicio OR v_fechaPeriodo > v_temporadaFin THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Existe un periodo fuera del rango de la temporada.');
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

    CALL sp_pptocompra_recalcular_totales(v_pptocompraid);

    INSERT INTO `pptocompralog` (`pptocompraid`, `logusuarioid`, `logdispositivo`, `logip`, `logtipo`, `logparamjson`, `logregbkpjson`)
    VALUES (v_pptocompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'EDT', p_in_json, v_prev_bkpjson);

    COMMIT;

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

    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraid')), ''), 'null') AS SIGNED);

    IF v_pptocompraid IS NULL OR v_pptocompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'pptocompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT JSON_OBJECT(
        'pptocompraid', pc.pptocompraid,
        'temporadaid', pc.temporadaid,
        'subfamiliaid', pc.subfamiliaid,
        'centrocostoid', pc.centrocostoid,
        'pptocompraactivo', pc.pptocompraactivo,
        'pptocompraobservacion', pc.pptocompraobservacion
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

    INSERT INTO `pptocompralog` (`pptocompraid`, `logusuarioid`, `logdispositivo`, `logip`, `logtipo`, `logparamjson`, `logregbkpjson`)
    VALUES (v_pptocompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'ANL', p_in_json, v_prev_bkpjson);

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

    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraid')), ''), 'null') AS SIGNED);
    SET v_ppoanio = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.ppoanio')), ''), 'null') AS SIGNED);
    SET v_ppomes = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.ppomes')), ''), 'null') AS SIGNED);
    SET v_monto = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompramonto')), ''), 'null') AS DECIMAL(18,2));
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
        `pptocompratransaccionfecha`,
        `pptocompramonto`,
        `pptocompramontoencurso`,
        `pptocompramontoconfirmado`,
        `pptocompramotivo`,
        `pptocompranrodocumentoorigen`,
        `pptocompramoduloorigen`,
        `pptocompraestado`,
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
        v_fechaPeriodo,
        v_montoAjuste,
        0,
        0,
        v_motivo,
        0,
        'PPTO_COMPRA',
        'CONFIRMADO',
        v_referencia_origen,
        v_referencia_linea,
        v_grupo,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    );

    CALL sp_pptocompra_recalcular_totales(v_pptocompraid);
    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Ajuste registrado correctamente.');
END//

DROP PROCEDURE IF EXISTS sp_pptocompra_traspasar//
CREATE PROCEDURE sp_pptocompra_traspasar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid_origen INT DEFAULT NULL;
    DECLARE v_pptocompraid_destino INT DEFAULT NULL;
    DECLARE v_ppoanio INT DEFAULT NULL;
    DECLARE v_ppomes INT DEFAULT NULL;
    DECLARE v_monto DECIMAL(18,2) DEFAULT NULL;
    DECLARE v_motivo VARCHAR(500);
    DECLARE v_referencia_linea VARCHAR(150);
    DECLARE v_grupo VARCHAR(50);
    DECLARE v_temporadaInicioOrigen DATE;
    DECLARE v_temporadaFinOrigen DATE;
    DECLARE v_temporadaInicioDestino DATE;
    DECLARE v_temporadaFinDestino DATE;
    DECLARE v_fechaPeriodo DATE;
    DECLARE v_saldoOrigen DECIMAL(18,2) DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al registrar traspaso de presupuesto.');
    END;

    SET v_pptocompraid_origen = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraidOrigen')), ''), 'null') AS SIGNED);
    SET v_pptocompraid_destino = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraidDestino')), ''), 'null') AS SIGNED);
    SET v_ppoanio = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.ppoanio')), ''), 'null') AS SIGNED);
    SET v_ppomes = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.ppomes')), ''), 'null') AS SIGNED);
    SET v_monto = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompramonto')), ''), 'null') AS DECIMAL(18,2));
    SET v_motivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompramotivo'));
    SET v_referencia_linea = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocomprareflinea'));
    SET v_grupo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompregruppomovimiento'));

    IF v_pptocompraid_origen IS NULL OR v_pptocompraid_origen <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'pptocompraid de origen es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_pptocompraid_destino IS NULL OR v_pptocompraid_destino <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'pptocompraid de destino es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_pptocompraid_origen = v_pptocompraid_destino THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El origen y destino deben ser diferentes.');
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
    IF v_motivo IS NULL OR TRIM(v_motivo) = '' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La justificación del traspaso es obligatoria.');
        LEAVE sp_main;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM `pptocompra` pc WHERE pc.pptocompraid = v_pptocompraid_origen AND pc.pptocompraactivo = 1) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El presupuesto origen no existe o se encuentra anulado.');
        LEAVE sp_main;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM `pptocompra` pc WHERE pc.pptocompraid = v_pptocompraid_destino AND pc.pptocompraactivo = 1) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El presupuesto destino no existe o se encuentra anulado.');
        LEAVE sp_main;
    END IF;

    SELECT t.temporadainicio, t.temporadafin
      INTO v_temporadaInicioOrigen, v_temporadaFinOrigen
      FROM `pptocompra` pc
      INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
      WHERE pc.pptocompraid = v_pptocompraid_origen
      LIMIT 1;

    SELECT t.temporadainicio, t.temporadafin
      INTO v_temporadaInicioDestino, v_temporadaFinDestino
      FROM `pptocompra` pc
      INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
      WHERE pc.pptocompraid = v_pptocompraid_destino
      LIMIT 1;

    SET v_fechaPeriodo = STR_TO_DATE(CONCAT(v_ppoanio, '-', LPAD(v_ppomes, 2, '0'), '-01'), '%Y-%m-%d');

    IF v_fechaPeriodo < v_temporadaInicioOrigen OR v_fechaPeriodo > v_temporadaFinOrigen THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Período fuera del rango de la temporada del origen.');
        LEAVE sp_main;
    END IF;
    IF v_fechaPeriodo < v_temporadaInicioDestino OR v_fechaPeriodo > v_temporadaFinDestino THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Período fuera del rango de la temporada del destino.');
        LEAVE sp_main;
    END IF;

    START TRANSACTION;

    SELECT IFNULL(pc.pptocomprasaldodisponible, 0)
      INTO v_saldoOrigen
      FROM `pptocompra` pc
     WHERE pc.pptocompraid = v_pptocompraid_origen
     LIMIT 1
     FOR UPDATE;

    SELECT pc.pptocompraid
      INTO v_pptocompraid_destino
      FROM `pptocompra` pc
     WHERE pc.pptocompraid = v_pptocompraid_destino
     LIMIT 1
     FOR UPDATE;

    IF v_saldoOrigen < v_monto THEN
        ROLLBACK;
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El presupuesto origen no tiene saldo disponible suficiente.');
        LEAVE sp_main;
    END IF;

    IF v_grupo IS NULL OR TRIM(v_grupo) = '' THEN
        SET v_grupo = CONCAT('TRASP_', v_pptocompraid_origen, '_', v_pptocompraid_destino, '_', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    END IF;

    INSERT INTO `pptocompratransacciones` (
        `pptocompraid`,
        `ppoanio`,
        `ppomes`,
        `pptocompratransacciontipoid`,
        `pptocompratransaccionfecha`,
        `pptocompramonto`,
        `pptocompramontoencurso`,
        `pptocompramontoconfirmado`,
        `pptocompramotivo`,
        `pptocompranrodocumentoorigen`,
        `pptocompramoduloorigen`,
        `pptocompraestado`,
        `pptocompregenciaorigen`,
        `pptocomprareflinea`,
        `pptocompregruppomovimiento`,
        `auditcreacionusuarioid`,
        `auditcreaciondispositivo`,
        `auditcreacionip`
    ) VALUES (
        v_pptocompraid_origen,
        v_ppoanio,
        v_ppomes,
        'PPTO_TRASPASO_SALIDA',
        CURDATE(),
        -ABS(v_monto),
        0,
        0,
        v_motivo,
        0,
        'PPTO_COMPRA',
        'CONFIRMADO',
        'TRASP_ORIGEN',
        v_referencia_linea,
        v_grupo,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    );

    INSERT INTO `pptocompratransacciones` (
        `pptocompraid`,
        `ppoanio`,
        `ppomes`,
        `pptocompratransacciontipoid`,
        `pptocompratransaccionfecha`,
        `pptocompramonto`,
        `pptocompramontoencurso`,
        `pptocompramontoconfirmado`,
        `pptocompramotivo`,
        `pptocompranrodocumentoorigen`,
        `pptocompramoduloorigen`,
        `pptocompraestado`,
        `pptocompregenciaorigen`,
        `pptocomprareflinea`,
        `pptocompregruppomovimiento`,
        `auditcreacionusuarioid`,
        `auditcreaciondispositivo`,
        `auditcreacionip`
    ) VALUES (
        v_pptocompraid_destino,
        v_ppoanio,
        v_ppomes,
        'PPTO_TRASPASO_ENTRADA',
        CURDATE(),
        ABS(v_monto),
        0,
        0,
        v_motivo,
        0,
        'PPTO_COMPRA',
        'CONFIRMADO',
        'TRASP_DESTINO',
        v_referencia_linea,
        v_grupo,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    );

    CALL sp_pptocompra_recalcular_totales(v_pptocompraid_origen);
    CALL sp_pptocompra_recalcular_totales(v_pptocompraid_destino);

    COMMIT;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Traspaso registrado correctamente.');
END//

DELIMITER ;
