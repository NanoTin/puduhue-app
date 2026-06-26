/*
Incremental 11 - SP y reglas presupuestarias para Compras.

Estado:
- SP presupuestarios implementados segun contrato tecnico cerrado en
  `docs/modulo_compras_plan_maestro.md`, seccion 4.2.1;
- validado manualmente en MariaDB local: OK.

Este archivo queda sin DDL de bases compartidas por diseno:
- el incremental 07 crea/ajusta usuarios, items, pptocompra, usuarios-centros,
  funcionarios, periodos de inactividad, proveedores y condiciones de pago;
- el incremental 08 crea REQ base;
- el incremental 09 crea pendientes de compra (`reqaprobados*`);
- el incremental 10 crea PreOC.

SP a implementar con firma estandar:
- `sp_compras_ppto_resolver`
  Resolver presupuesto por fecha, subfamilia y centro.
- `sp_compras_req_ppto_analizar`
  Analizar REQ sin bloquear ni generar movimientos.
- `sp_compras_req_ppto_snapshot_actualizar`
  Generar/actualizar snapshot REQ agrupado por subfamilia y centro.
- `sp_compras_preoc_ppto_reservar`
  Reservar PreOC al pasar de BRR a PND con movimiento `POC_RESERVA` negativo.
- `sp_compras_preoc_ppto_confirmar`
  Confirmar reserva al aprobar PreOC con movimiento `POC_CONFIRMACION`.
- `sp_compras_preoc_ppto_revertir`
  Revertir por rechazo/anulacion con movimiento `POC_REVERSA` positivo.
- `sp_compras_preoc_ppto_borrar_reserva_provisional`
  Borrar reservas provisionales si vuelve de PND a BRR antes de aprobaciones;
  no genera reversa.
- `sp_compras_ppto_recalcular_totales`
  Wrapper con firma estandar para recalcular `pptocompra` desde el libro oficial.

Reglas cerradas:
- REQ no mueve presupuesto.
- PreOC es el unico flujo que compromete presupuesto.
- Reservas/consumos son negativos; reversas son positivas.
- Los movimientos PreOC se registran agrupados por `preocpptoresumenid`.
- `pptocomprareflinea = 'PREOCPPTORESUMEN:<preocpptoresumenid>'`.
- Cada envio a aprobacion abre ciclo en `pptocompregruppomovimiento`,
  por ejemplo `PREOC:<preocid>:CICLO:<n>`.
- La idempotencia valida tipo, modulo, documento, presupuesto, referencia y ciclo.
- No usar `BEGIN`, `COMMIT` ni `ROLLBACK` dentro de SP; PHP controla la transaccion.
- Usar `SELECT ... FOR UPDATE` para validar saldo bajo la transaccion abierta por PHP.

No inventar SP, tablas, columnas ni reglas adicionales en este incremental sin
un nuevo contrato funcional/tecnico cerrado.
*/

DELIMITER //

DROP PROCEDURE IF EXISTS sp_compras_ppto_resolver//
CREATE PROCEDURE sp_compras_ppto_resolver (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_fecha DATE DEFAULT NULL;
    DECLARE v_subfamiliaid INT DEFAULT NULL;
    DECLARE v_centrocostoid INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al resolver presupuesto de compra.');
    END;

    SET v_fecha = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fecha')), ''), 'null') AS DATE);
    SET v_subfamiliaid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.subfamiliaid')), ''), 'null') AS SIGNED);
    SET v_centrocostoid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), ''), 'null') AS SIGNED);

    IF v_fecha IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'fecha es obligatoria.');
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
        pc.pptocompraid,
        pc.temporadaid,
        t.temporadadescripcion,
        t.temporadainicio,
        t.temporadafin,
        pc.subfamiliaid,
        pc.centrocostoid,
        pc.pptocomprasaldodisponible,
        pc.pptocomprapresupuestado,
        pc.pptocompraajustespositivos,
        pc.pptocompraajustenegativos,
        pc.pptocompreproyectado,
        pc.pptocompramontoconsumidopnd,
        pc.pptocompramontoconsumidocnf,
        pc.pptocompraresponsableid,
        pc.pptocompraadministradorid,
        pc.pptocompracolaboradorid
    FROM `pptocompra` pc
    INNER JOIN `temporadas` t ON t.temporadaid = pc.temporadaid
    WHERE t.temporadatipocodigo = 'PPTO_COMPRAS'
      AND v_fecha BETWEEN t.temporadainicio AND t.temporadafin
      AND pc.subfamiliaid = v_subfamiliaid
      AND pc.centrocostoid = v_centrocostoid
      AND pc.pptocompraactivo = 1
    ORDER BY t.temporadainicio DESC, pc.pptocompraid DESC
    LIMIT 1;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_ppto_analizar//
CREATE PROCEDURE sp_compras_req_ppto_analizar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_total INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al analizar presupuesto del REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_total
      FROM `reqcompras`
     WHERE `reqcompraid` = v_reqcompraid
       AND `reqcompravig` = 1;

    IF v_total = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;

    SELECT
        cur.reqcompraid,
        cur.subfamiliaid,
        cur.centrocostoid,
        pc.pptocompraid,
        IFNULL(pc.pptocomprasaldodisponible, 0) AS reqpptosaldodisponible,
        IFNULL(otros.monto_otros, 0) AS reqpptomontootroscurso,
        IFNULL(aprob.monto_aprobados, 0) AS reqpptomontoaprobadospend,
        cur.reqpptomonto,
        (IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto) AS reqpptosaldoproyectado,
        CASE
            WHEN IFNULL(pc.pptocomprasaldodisponible, 0) <= 0 THEN NULL
            ELSE ROUND((cur.reqpptomonto / pc.pptocomprasaldodisponible) * 100, 4)
        END AS reqpptoporcentajeuso,
        CASE
            WHEN pc.pptocompraid IS NULL THEN cur.reqpptomonto
            WHEN (IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto) < 0
                THEN ABS(IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto)
            ELSE 0
        END AS reqpptodeficit,
        CASE
            WHEN pc.pptocompraid IS NULL THEN 1
            WHEN (IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto) < 0 THEN 1
            ELSE 0
        END AS reqpptoadvertencia
    FROM (
        SELECT
            r.reqcompraid,
            d.subfamiliaid,
            r.centrocostoid,
            r.reqcomprafecha,
            SUM(d.reqcompradettotalneto) AS reqpptomonto
        FROM `reqcompras` r
        INNER JOIN `reqcomprasdetalle` d ON d.reqcompraid = r.reqcompraid
        WHERE r.reqcompraid = v_reqcompraid
        GROUP BY r.reqcompraid, d.subfamiliaid, r.centrocostoid, r.reqcomprafecha
    ) cur
    LEFT JOIN `temporadas` t
        ON t.temporadatipocodigo = 'PPTO_COMPRAS'
       AND cur.reqcomprafecha BETWEEN t.temporadainicio AND t.temporadafin
    LEFT JOIN `pptocompra` pc
        ON pc.temporadaid = t.temporadaid
       AND pc.subfamiliaid = cur.subfamiliaid
       AND pc.centrocostoid = cur.centrocostoid
       AND pc.pptocompraactivo = 1
    LEFT JOIN (
        SELECT
            d.subfamiliaid,
            r.centrocostoid,
            SUM(d.reqcompradettotalneto) AS monto_otros
        FROM `reqcompras` r
        INNER JOIN `reqcomprasdetalle` d ON d.reqcompraid = r.reqcompraid
        WHERE r.reqcompravig = 1
          AND r.reqcompraestadoid IN ('PND', 'EDT')
          AND r.reqcompraid <> v_reqcompraid
        GROUP BY d.subfamiliaid, r.centrocostoid
    ) otros
        ON otros.subfamiliaid = cur.subfamiliaid
       AND otros.centrocostoid = cur.centrocostoid
    LEFT JOIN (
        SELECT
            d.subfamiliaid,
            r.centrocostoid,
            SUM(ra.reqaprobadocantidadpendiente * ra.reqaprobadoprecioneto) AS monto_aprobados
        FROM `reqaprobados` ra
        INNER JOIN `reqcomprasdetalle` d ON d.reqcompradetid = ra.reqcompradetid
        INNER JOIN `reqcompras` r ON r.reqcompraid = ra.reqcompraid
        WHERE ra.reqaprobadocantidadpendiente > 0
        GROUP BY d.subfamiliaid, r.centrocostoid
    ) aprob
        ON aprob.subfamiliaid = cur.subfamiliaid
       AND aprob.centrocostoid = cur.centrocostoid;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_ppto_snapshot_actualizar//
CREATE PROCEDURE sp_compras_req_ppto_snapshot_actualizar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_advertencia INT DEFAULT 0;
    DECLARE v_grupos INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al actualizar snapshot presupuestario del REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_total
      FROM `reqcompras`
     WHERE `reqcompraid` = v_reqcompraid
       AND `reqcompravig` = 1;

    IF v_total = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;

    DELETE FROM `reqcompraspptosnapshot`
     WHERE `reqcompraid` = v_reqcompraid;

    INSERT INTO `reqcompraspptosnapshot` (
        `reqcompraid`,
        `subfamiliaid`,
        `centrocostoid`,
        `pptocompraid`,
        `reqpptomonto`,
        `reqpptosaldodisponible`,
        `reqpptomontootroscurso`,
        `reqpptomontoaprobadospend`,
        `reqpptosaldoproyectado`,
        `reqpptoporcentajeuso`,
        `reqpptodeficit`,
        `reqpptoadvertencia`,
        `reqpptofuerapptocompra`,
        `reqpptofechahora`,
        `auditcreacionusuarioid`,
        `auditcreaciondispositivo`,
        `auditcreacionip`
    )
    SELECT
        x.reqcompraid,
        x.subfamiliaid,
        x.centrocostoid,
        x.pptocompraid,
        x.reqpptomonto,
        x.reqpptosaldodisponible,
        x.reqpptomontootroscurso,
        x.reqpptomontoaprobadospend,
        x.reqpptosaldoproyectado,
        x.reqpptoporcentajeuso,
        x.reqpptodeficit,
        x.reqpptoadvertencia,
        x.reqpptoadvertencia,
        NOW(),
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    FROM (
        SELECT
            cur.reqcompraid,
            cur.subfamiliaid,
            cur.centrocostoid,
            pc.pptocompraid,
            cur.reqpptomonto,
            IFNULL(pc.pptocomprasaldodisponible, 0) AS reqpptosaldodisponible,
            IFNULL(otros.monto_otros, 0) AS reqpptomontootroscurso,
            IFNULL(aprob.monto_aprobados, 0) AS reqpptomontoaprobadospend,
            (IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto) AS reqpptosaldoproyectado,
            CASE
                WHEN IFNULL(pc.pptocomprasaldodisponible, 0) <= 0 THEN NULL
                ELSE ROUND((cur.reqpptomonto / pc.pptocomprasaldodisponible) * 100, 4)
            END AS reqpptoporcentajeuso,
            CASE
                WHEN pc.pptocompraid IS NULL THEN cur.reqpptomonto
                WHEN (IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto) < 0
                    THEN ABS(IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto)
                ELSE 0
            END AS reqpptodeficit,
            CASE
                WHEN pc.pptocompraid IS NULL THEN 1
                WHEN (IFNULL(pc.pptocomprasaldodisponible, 0) - IFNULL(otros.monto_otros, 0) - IFNULL(aprob.monto_aprobados, 0) - cur.reqpptomonto) < 0 THEN 1
                ELSE 0
            END AS reqpptoadvertencia
        FROM (
            SELECT
                r.reqcompraid,
                d.subfamiliaid,
                r.centrocostoid,
                r.reqcomprafecha,
                SUM(d.reqcompradettotalneto) AS reqpptomonto
            FROM `reqcompras` r
            INNER JOIN `reqcomprasdetalle` d ON d.reqcompraid = r.reqcompraid
            WHERE r.reqcompraid = v_reqcompraid
            GROUP BY r.reqcompraid, d.subfamiliaid, r.centrocostoid, r.reqcomprafecha
        ) cur
        LEFT JOIN `temporadas` t
            ON t.temporadatipocodigo = 'PPTO_COMPRAS'
           AND cur.reqcomprafecha BETWEEN t.temporadainicio AND t.temporadafin
        LEFT JOIN `pptocompra` pc
            ON pc.temporadaid = t.temporadaid
           AND pc.subfamiliaid = cur.subfamiliaid
           AND pc.centrocostoid = cur.centrocostoid
           AND pc.pptocompraactivo = 1
        LEFT JOIN (
            SELECT
                d.subfamiliaid,
                r.centrocostoid,
                SUM(d.reqcompradettotalneto) AS monto_otros
            FROM `reqcompras` r
            INNER JOIN `reqcomprasdetalle` d ON d.reqcompraid = r.reqcompraid
            WHERE r.reqcompravig = 1
              AND r.reqcompraestadoid IN ('PND', 'EDT')
              AND r.reqcompraid <> v_reqcompraid
            GROUP BY d.subfamiliaid, r.centrocostoid
        ) otros
            ON otros.subfamiliaid = cur.subfamiliaid
           AND otros.centrocostoid = cur.centrocostoid
        LEFT JOIN (
            SELECT
                d.subfamiliaid,
                r.centrocostoid,
                SUM(ra.reqaprobadocantidadpendiente * ra.reqaprobadoprecioneto) AS monto_aprobados
            FROM `reqaprobados` ra
            INNER JOIN `reqcomprasdetalle` d ON d.reqcompradetid = ra.reqcompradetid
            INNER JOIN `reqcompras` r ON r.reqcompraid = ra.reqcompraid
            WHERE ra.reqaprobadocantidadpendiente > 0
            GROUP BY d.subfamiliaid, r.centrocostoid
        ) aprob
            ON aprob.subfamiliaid = cur.subfamiliaid
           AND aprob.centrocostoid = cur.centrocostoid
    ) x;

    SELECT COUNT(*), IFNULL(MAX(reqpptoadvertencia), 0)
      INTO v_grupos, v_advertencia
      FROM `reqcompraspptosnapshot`
     WHERE `reqcompraid` = v_reqcompraid;

    UPDATE `reqcompras`
       SET `reqadvertenciapptocompra` = v_advertencia,
           `reqfuerapptocompra` = v_advertencia,
           `auditedicionusuarioid` = p_in_usuarioid,
           `auditediciondispositivo` = p_in_dispositivo,
           `auditedicionip` = p_in_ip,
           `auditedicionfechahora` = NOW()
     WHERE `reqcompraid` = v_reqcompraid;

    UPDATE `reqcomprasdetalle` d
    INNER JOIN `reqcompras` r ON r.reqcompraid = d.reqcompraid
       SET d.`reqcompradetadvertenciappto` = CASE
            WHEN EXISTS (
                SELECT 1
                  FROM `reqcompraspptosnapshot` s
                 WHERE s.reqcompraid = d.reqcompraid
                   AND s.subfamiliaid = d.subfamiliaid
                   AND s.centrocostoid = r.centrocostoid
                   AND s.reqpptoadvertencia = 1
            ) THEN 1 ELSE 0 END,
           d.`auditedicionusuarioid` = p_in_usuarioid,
           d.`auditediciondispositivo` = p_in_dispositivo,
           d.`auditedicionip` = p_in_ip,
           d.`auditedicionfechahora` = NOW()
     WHERE d.`reqcompraid` = v_reqcompraid;

    SET p_out_json = JSON_OBJECT(
        'status', 200,
        'message', 'Snapshot presupuestario actualizado.',
        'reqcompraid', v_reqcompraid,
        'advertencia', v_advertencia,
        'fuerapptocompra', v_advertencia,
        'grupos', v_grupos
    );
END//

DROP PROCEDURE IF EXISTS sp_compras_ppto_recalcular_totales//
CREATE PROCEDURE sp_compras_ppto_recalcular_totales (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_pptocompraid INT DEFAULT NULL;
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_actual_id INT DEFAULT NULL;
    DECLARE v_recalculados INT DEFAULT 0;

    DECLARE cur_pptos CURSOR FOR
        SELECT pc.pptocompraid
          FROM `pptocompra` pc
         WHERE v_pptocompraid IS NULL OR pc.pptocompraid = v_pptocompraid;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al recalcular presupuesto de compra.');
    END;

    SET v_pptocompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptocompraid')), ''), 'null') AS SIGNED);

    OPEN cur_pptos;
    ppto_loop: LOOP
        FETCH cur_pptos INTO v_actual_id;
        IF v_done = 1 THEN
            LEAVE ppto_loop;
        END IF;

        UPDATE `pptocompra` pc
        LEFT JOIN (
            SELECT pptocompraid, SUM(ppomontoppto) AS presupuestado
              FROM `pptocompramensual`
             WHERE pptocompraid = v_actual_id
             GROUP BY pptocompraid
        ) m ON m.pptocompraid = pc.pptocompraid
        LEFT JOIN (
            SELECT
                tr.pptocompraid,
                SUM(CASE WHEN tr.pptocompratransacciontipoid = 'PPTO_AJUSTE_POS' THEN tr.pptocompramonto ELSE 0 END) AS ajuste_pos,
                SUM(CASE WHEN tr.pptocompratransacciontipoid = 'PPTO_AJUSTE_NEG' THEN tr.pptocompramonto ELSE 0 END) AS ajuste_neg,
                SUM(CASE WHEN tr.pptocompratransacciontipoid IN ('PPTO_TRASPASO_ENTRADA', 'PPTO_TRASPASO_SALIDA') THEN tr.pptocompramonto ELSE 0 END) AS traspasos,
                SUM(COALESCE(tr.pptocompramontoencurso, 0)) AS consumo_pnd,
                SUM(COALESCE(tr.pptocompramontoconfirmado, 0)) AS consumo_cnf
              FROM `pptocompratransacciones` tr
             WHERE tr.pptocompraid = v_actual_id
             GROUP BY tr.pptocompraid
        ) a ON a.pptocompraid = pc.pptocompraid
           SET pc.pptocomprapresupuestado = IFNULL(m.presupuestado, 0),
               pc.pptocompraajustespositivos = IFNULL(a.ajuste_pos, 0),
               pc.pptocompraajustenegativos = IFNULL(a.ajuste_neg, 0),
               pc.pptocompreproyectado = IFNULL(m.presupuestado, 0) + IFNULL(a.ajuste_pos, 0) + IFNULL(a.ajuste_neg, 0) + IFNULL(a.traspasos, 0),
               pc.pptocompramontoconsumidopnd = IFNULL(a.consumo_pnd, 0),
               pc.pptocompramontoconsumidocnf = IFNULL(a.consumo_cnf, 0),
               pc.pptocomprasaldodisponible = IFNULL(m.presupuestado, 0) + IFNULL(a.ajuste_pos, 0) + IFNULL(a.ajuste_neg, 0) + IFNULL(a.traspasos, 0) + IFNULL(a.consumo_pnd, 0) + IFNULL(a.consumo_cnf, 0),
               pc.auditedicionusuarioid = p_in_usuarioid,
               pc.auditediciondispositivo = p_in_dispositivo,
               pc.auditedicionip = p_in_ip,
               pc.auditedicionfechahora = NOW()
         WHERE pc.pptocompraid = v_actual_id;

        SET v_recalculados = v_recalculados + ROW_COUNT();
    END LOOP;
    CLOSE cur_pptos;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Presupuestos recalculados.', 'recalculados', v_recalculados);
END//

DROP PROCEDURE IF EXISTS sp_compras_preoc_ppto_reservar//
CREATE PROCEDURE sp_compras_preoc_ppto_reservar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_preocid INT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20);
    DECLARE v_fechaoc DATE;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_pptocompraid INT;
    DECLARE v_monto DECIMAL(18,2);
    DECLARE v_saldo DECIMAL(18,2);
    DECLARE v_saldo_despues DECIMAL(18,2);
    DECLARE v_resumenid INT;
    DECLARE v_grupo VARCHAR(50);
    DECLARE v_ciclo INT DEFAULT 0;
    DECLARE v_reflinea VARCHAR(150);
    DECLARE v_insertados INT DEFAULT 0;
    DECLARE v_recalc_json JSON;

    DECLARE cur_pptos CURSOR FOR
        SELECT d.pptocompraid, SUM(d.preocdetsubtotalneto)
          FROM `preocdetallereqitems` d
         WHERE d.preocid = v_preocid
         GROUP BY d.pptocompraid
         ORDER BY d.pptocompraid;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al registrar reserva presupuestaria de PreOC.');
    END;

    SET v_preocid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.preocid')), ''), 'null') AS SIGNED);

    IF v_preocid IS NULL OR v_preocid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'preocid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*), MAX(preocestadoid), MAX(preocfechaoc)
      INTO v_total, v_estado, v_fechaoc
      FROM `preoc`
     WHERE `preocid` = v_preocid
       AND `preocvig` = 1;

    IF v_total = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La PreOC no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;
    IF v_estado NOT IN ('BRR', 'PND') THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La PreOC no esta en estado valido para reservar.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_total
      FROM `pptocompratransacciones` r
     WHERE r.pptocompramoduloorigen = 'PREOC'
       AND r.pptocompranrodocumentoorigen = v_preocid
       AND r.pptocompratransacciontipoid = 'POC_RESERVA'
       AND NOT EXISTS (
            SELECT 1
              FROM `pptocompratransacciones` x
             WHERE x.pptocompramoduloorigen = 'PREOC'
               AND x.pptocompranrodocumentoorigen = v_preocid
               AND x.pptocompregruppomovimiento = r.pptocompregruppomovimiento
               AND x.pptocompratransacciontipoid IN ('POC_CONFIRMACION', 'POC_REVERSA')
       );

    IF v_total > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Reserva presupuestaria ya registrada.', 'preocid', v_preocid);
        LEAVE sp_main;
    END IF;

    SELECT COUNT(DISTINCT pptocompregruppomovimiento) + 1
      INTO v_ciclo
      FROM `pptocompratransacciones`
     WHERE pptocompramoduloorigen = 'PREOC'
       AND pptocompranrodocumentoorigen = v_preocid
       AND pptocompratransacciontipoid = 'POC_RESERVA';

    SET v_grupo = CONCAT('PREOC:', v_preocid, ':CICLO:', v_ciclo);

    OPEN cur_pptos;
    reserva_loop: LOOP
        FETCH cur_pptos INTO v_pptocompraid, v_monto;
        IF v_done = 1 THEN
            LEAVE reserva_loop;
        END IF;

        SELECT COUNT(*)
          INTO v_total
          FROM `pptocompra`
         WHERE pptocompraid = v_pptocompraid
           AND pptocompraactivo = 1;

        IF v_total = 0 THEN
            CLOSE cur_pptos;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El presupuesto de compra no existe o no esta activo.', 'preocid', v_preocid, 'pptocompraid', v_pptocompraid);
            LEAVE sp_main;
        END IF;

        SELECT IFNULL(pptocomprasaldodisponible, 0)
          INTO v_saldo
          FROM `pptocompra`
         WHERE pptocompraid = v_pptocompraid
           AND pptocompraactivo = 1
         FOR UPDATE;

        IF v_saldo < v_monto THEN
            CLOSE cur_pptos;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Saldo presupuestario insuficiente para reservar PreOC.', 'preocid', v_preocid, 'pptocompraid', v_pptocompraid, 'saldo', v_saldo, 'monto', v_monto);
            LEAVE sp_main;
        END IF;

        SET v_saldo_despues = v_saldo - v_monto;

        INSERT INTO `preocpptoresumen` (
            `preocid`, `pptocompraid`, `preocpptomonto`, `preocpptosaldoantes`, `preocpptosaldodespues`, `preocpptoestado`, `preocpptofechahora`,
            `auditcreacionusuarioid`, `auditcreaciondispositivo`, `auditcreacionip`
        ) VALUES (
            v_preocid, v_pptocompraid, v_monto, v_saldo, v_saldo_despues, 'RESERVA', NOW(),
            p_in_usuarioid, p_in_dispositivo, p_in_ip
        )
        ON DUPLICATE KEY UPDATE
            `preocpptomonto` = VALUES(`preocpptomonto`),
            `preocpptosaldoantes` = VALUES(`preocpptosaldoantes`),
            `preocpptosaldodespues` = VALUES(`preocpptosaldodespues`),
            `preocpptofechahora` = NOW(),
            `auditedicionusuarioid` = p_in_usuarioid,
            `auditediciondispositivo` = p_in_dispositivo,
            `auditedicionip` = p_in_ip,
            `auditedicionfechahora` = NOW(),
            `preocpptoresumenid` = LAST_INSERT_ID(`preocpptoresumenid`);

        SET v_resumenid = LAST_INSERT_ID();
        SET v_reflinea = CONCAT('PREOCPPTORESUMEN:', v_resumenid);

        INSERT INTO `pptocompratransacciones` (
            `pptocompraid`, `ppoanio`, `ppomes`, `pptocompratransacciontipoid`, `pptocompratransaccionfecha`,
            `pptocompramonto`, `pptocompramontoencurso`, `pptocompramontoconfirmado`, `pptocompramotivo`,
            `pptocompranrodocumentoorigen`, `pptocompramoduloorigen`, `pptocompraestado`, `pptocompregenciaorigen`,
            `pptocomprareflinea`, `pptocompregruppomovimiento`, `auditcreacionusuarioid`, `auditcreaciondispositivo`, `auditcreacionip`
        ) VALUES (
            v_pptocompraid, YEAR(v_fechaoc), MONTH(v_fechaoc), 'POC_RESERVA', v_fechaoc,
            -ABS(v_monto), -ABS(v_monto), 0, CONCAT('Reserva PreOC ', v_preocid),
            v_preocid, 'PREOC', 'PENDIENTE', 'PREOC_RESERVA',
            v_reflinea, v_grupo, p_in_usuarioid, p_in_dispositivo, p_in_ip
        );

        CALL sp_compras_ppto_recalcular_totales(JSON_OBJECT('pptocompraid', v_pptocompraid), p_in_usuarioid, p_in_dispositivo, p_in_ip, v_recalc_json);
        SET v_insertados = v_insertados + 1;
    END LOOP;
    CLOSE cur_pptos;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Reserva presupuestaria registrada.', 'preocid', v_preocid, 'ciclo', v_grupo, 'reservas', v_insertados);
END//

DROP PROCEDURE IF EXISTS sp_compras_preoc_ppto_confirmar//
CREATE PROCEDURE sp_compras_preoc_ppto_confirmar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_preocid INT DEFAULT NULL;
    DECLARE v_fechaoc DATE;
    DECLARE v_grupo VARCHAR(50);
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_transid INT;
    DECLARE v_pptocompraid INT;
    DECLARE v_monto DECIMAL(18,2);
    DECLARE v_reflinea VARCHAR(150);
    DECLARE v_resumenid INT;
    DECLARE v_confirmados INT DEFAULT 0;
    DECLARE v_recalc_json JSON;

    DECLARE cur_reservas CURSOR FOR
        SELECT r.pptocompratransaccionid, r.pptocompraid, ABS(r.pptocompramonto), r.pptocomprareflinea
          FROM `pptocompratransacciones` r
         WHERE r.pptocompramoduloorigen = 'PREOC'
           AND r.pptocompranrodocumentoorigen = v_preocid
           AND r.pptocompratransacciontipoid = 'POC_RESERVA'
           AND r.pptocompregruppomovimiento = v_grupo
         ORDER BY r.pptocompratransaccionid;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al confirmar presupuesto de PreOC.');
    END;

    SET v_preocid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.preocid')), ''), 'null') AS SIGNED);

    IF v_preocid IS NULL OR v_preocid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'preocid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT MAX(preocfechaoc)
      INTO v_fechaoc
      FROM `preoc`
     WHERE preocid = v_preocid
       AND preocvig = 1;

    IF v_fechaoc IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La PreOC no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;

    SELECT MAX(r.pptocompregruppomovimiento)
      INTO v_grupo
      FROM `pptocompratransacciones` r
     WHERE r.pptocompramoduloorigen = 'PREOC'
       AND r.pptocompranrodocumentoorigen = v_preocid
       AND r.pptocompratransacciontipoid = 'POC_RESERVA'
       AND NOT EXISTS (
            SELECT 1
              FROM `pptocompratransacciones` x
             WHERE x.pptocompramoduloorigen = 'PREOC'
               AND x.pptocompranrodocumentoorigen = v_preocid
               AND x.pptocompregruppomovimiento = r.pptocompregruppomovimiento
               AND x.pptocompratransacciontipoid IN ('POC_CONFIRMACION', 'POC_REVERSA')
       );

    IF v_grupo IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 200, 'message', 'No existe reserva pendiente de confirmar.', 'preocid', v_preocid);
        LEAVE sp_main;
    END IF;

    OPEN cur_reservas;
    confirm_loop: LOOP
        FETCH cur_reservas INTO v_transid, v_pptocompraid, v_monto, v_reflinea;
        IF v_done = 1 THEN
            LEAVE confirm_loop;
        END IF;

        IF NOT EXISTS (
            SELECT 1 FROM `pptocompratransacciones`
             WHERE pptocompramoduloorigen = 'PREOC'
               AND pptocompranrodocumentoorigen = v_preocid
               AND pptocompratransacciontipoid = 'POC_CONFIRMACION'
               AND pptocomprareflinea = v_reflinea
               AND pptocompregruppomovimiento = v_grupo
        ) THEN
            INSERT INTO `pptocompratransacciones` (
                `pptocompraid`, `ppoanio`, `ppomes`, `pptocompratransacciontipoid`, `pptocompratransaccionfecha`,
                `pptocompramonto`, `pptocompramontoencurso`, `pptocompramontoconfirmado`, `pptocompramotivo`,
                `pptocompranrodocumentoorigen`, `pptocompramoduloorigen`, `pptocompraestado`, `pptocompregenciaorigen`,
                `pptocomprareflinea`, `pptocompregruppomovimiento`, `auditcreacionusuarioid`, `auditcreaciondispositivo`, `auditcreacionip`
            ) VALUES (
                v_pptocompraid, YEAR(v_fechaoc), MONTH(v_fechaoc), 'POC_CONFIRMACION', v_fechaoc,
                -ABS(v_monto), ABS(v_monto), -ABS(v_monto), CONCAT('Confirmacion PreOC ', v_preocid),
                v_preocid, 'PREOC', 'CONFIRMADO', 'PREOC_CONFIRMACION',
                v_reflinea, v_grupo, p_in_usuarioid, p_in_dispositivo, p_in_ip
            );
            SET v_confirmados = v_confirmados + 1;
        END IF;

        UPDATE `pptocompratransacciones`
           SET `pptocompraestado` = 'CONFIRMADO'
         WHERE `pptocompratransaccionid` = v_transid;

        SET v_resumenid = CAST(SUBSTRING_INDEX(v_reflinea, ':', -1) AS SIGNED);
        INSERT INTO `preocpptoresumen` (
            `preocpptoresumenid`, `preocid`, `pptocompraid`, `preocpptomonto`, `preocpptoestado`, `preocpptofechahora`,
            `auditcreacionusuarioid`, `auditcreaciondispositivo`, `auditcreacionip`
        ) VALUES (
            v_resumenid, v_preocid, v_pptocompraid, v_monto, 'CONFIRMADO', NOW(),
            p_in_usuarioid, p_in_dispositivo, p_in_ip
        )
        ON DUPLICATE KEY UPDATE
            `preocpptomonto` = VALUES(`preocpptomonto`),
            `preocpptoestado` = 'CONFIRMADO',
            `preocpptofechahora` = NOW(),
            `auditedicionusuarioid` = p_in_usuarioid,
            `auditediciondispositivo` = p_in_dispositivo,
            `auditedicionip` = p_in_ip,
            `auditedicionfechahora` = NOW();

        CALL sp_compras_ppto_recalcular_totales(JSON_OBJECT('pptocompraid', v_pptocompraid), p_in_usuarioid, p_in_dispositivo, p_in_ip, v_recalc_json);
    END LOOP;
    CLOSE cur_reservas;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Presupuesto de PreOC confirmado.', 'preocid', v_preocid, 'ciclo', v_grupo, 'confirmaciones', v_confirmados);
END//

DROP PROCEDURE IF EXISTS sp_compras_preoc_ppto_revertir//
CREATE PROCEDURE sp_compras_preoc_ppto_revertir (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_preocid INT DEFAULT NULL;
    DECLARE v_evento VARCHAR(20);
    DECLARE v_motivo VARCHAR(500);
    DECLARE v_fechaoc DATE;
    DECLARE v_grupo VARCHAR(50);
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_pptocompraid INT;
    DECLARE v_monto DECIMAL(18,2);
    DECLARE v_reflinea VARCHAR(150);
    DECLARE v_confirmada INT;
    DECLARE v_reversas INT DEFAULT 0;
    DECLARE v_recalc_json JSON;

    DECLARE cur_reservas CURSOR FOR
        SELECT r.pptocompraid, ABS(r.pptocompramonto), r.pptocomprareflinea,
               CASE WHEN EXISTS (
                    SELECT 1 FROM `pptocompratransacciones` c
                     WHERE c.pptocompramoduloorigen = 'PREOC'
                       AND c.pptocompranrodocumentoorigen = v_preocid
                       AND c.pptocompratransacciontipoid = 'POC_CONFIRMACION'
                       AND c.pptocompregruppomovimiento = r.pptocompregruppomovimiento
                       AND c.pptocomprareflinea = r.pptocomprareflinea
               ) THEN 1 ELSE 0 END
          FROM `pptocompratransacciones` r
         WHERE r.pptocompramoduloorigen = 'PREOC'
           AND r.pptocompranrodocumentoorigen = v_preocid
           AND r.pptocompratransacciontipoid = 'POC_RESERVA'
           AND r.pptocompregruppomovimiento = v_grupo
         ORDER BY r.pptocompratransaccionid;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al revertir presupuesto de PreOC.');
    END;

    SET v_preocid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.preocid')), ''), 'null') AS SIGNED);
    SET v_evento = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.evento'));
    SET v_motivo = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.motivo'));

    IF v_preocid IS NULL OR v_preocid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'preocid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_evento NOT IN ('RCH', 'ANL') THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'evento debe ser RCH o ANL.');
        LEAVE sp_main;
    END IF;
    IF v_motivo IS NULL OR TRIM(v_motivo) = '' THEN
        SET v_motivo = CONCAT('Reversa PreOC ', v_preocid, ' por ', v_evento);
    END IF;

    SELECT MAX(preocfechaoc)
      INTO v_fechaoc
      FROM `preoc`
     WHERE preocid = v_preocid
       AND preocvig = 1;

    IF v_fechaoc IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'La PreOC no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;

    SELECT MAX(r.pptocompregruppomovimiento)
      INTO v_grupo
      FROM `pptocompratransacciones` r
     WHERE r.pptocompramoduloorigen = 'PREOC'
       AND r.pptocompranrodocumentoorigen = v_preocid
       AND r.pptocompratransacciontipoid = 'POC_RESERVA'
       AND NOT EXISTS (
            SELECT 1
              FROM `pptocompratransacciones` x
             WHERE x.pptocompramoduloorigen = 'PREOC'
               AND x.pptocompranrodocumentoorigen = v_preocid
               AND x.pptocompregruppomovimiento = r.pptocompregruppomovimiento
               AND x.pptocompratransacciontipoid = 'POC_REVERSA'
       );

    IF v_grupo IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 200, 'message', 'No existe presupuesto pendiente de reversar.', 'preocid', v_preocid);
        LEAVE sp_main;
    END IF;

    OPEN cur_reservas;
    reversa_loop: LOOP
        FETCH cur_reservas INTO v_pptocompraid, v_monto, v_reflinea, v_confirmada;
        IF v_done = 1 THEN
            LEAVE reversa_loop;
        END IF;

        IF NOT EXISTS (
            SELECT 1 FROM `pptocompratransacciones`
             WHERE pptocompramoduloorigen = 'PREOC'
               AND pptocompranrodocumentoorigen = v_preocid
               AND pptocompratransacciontipoid = 'POC_REVERSA'
               AND pptocomprareflinea = v_reflinea
               AND pptocompregruppomovimiento = v_grupo
        ) THEN
            INSERT INTO `pptocompratransacciones` (
                `pptocompraid`, `ppoanio`, `ppomes`, `pptocompratransacciontipoid`, `pptocompratransaccionfecha`,
                `pptocompramonto`, `pptocompramontoencurso`, `pptocompramontoconfirmado`, `pptocompramotivo`,
                `pptocompranrodocumentoorigen`, `pptocompramoduloorigen`, `pptocompraestado`, `pptocompregenciaorigen`,
                `pptocomprareflinea`, `pptocompregruppomovimiento`, `auditcreacionusuarioid`, `auditcreaciondispositivo`, `auditcreacionip`
            ) VALUES (
                v_pptocompraid, YEAR(v_fechaoc), MONTH(v_fechaoc), 'POC_REVERSA', v_fechaoc,
                ABS(v_monto),
                CASE WHEN v_confirmada = 1 THEN 0 ELSE ABS(v_monto) END,
                CASE WHEN v_confirmada = 1 THEN ABS(v_monto) ELSE 0 END,
                v_motivo,
                v_preocid, 'PREOC', 'REVERSA', CONCAT('PREOC_', v_evento),
                v_reflinea, v_grupo, p_in_usuarioid, p_in_dispositivo, p_in_ip
            );
            SET v_reversas = v_reversas + 1;
        END IF;

        INSERT INTO `preocpptoresumen` (
            `preocid`, `pptocompraid`, `preocpptomonto`, `preocpptoestado`, `preocpptofechahora`,
            `auditcreacionusuarioid`, `auditcreaciondispositivo`, `auditcreacionip`
        ) VALUES (
            v_preocid, v_pptocompraid, v_monto, 'REVERTIDO', NOW(),
            p_in_usuarioid, p_in_dispositivo, p_in_ip
        )
        ON DUPLICATE KEY UPDATE
            `preocpptomonto` = VALUES(`preocpptomonto`),
            `preocpptoestado` = 'REVERTIDO',
            `preocpptofechahora` = NOW(),
            `auditedicionusuarioid` = p_in_usuarioid,
            `auditediciondispositivo` = p_in_dispositivo,
            `auditedicionip` = p_in_ip,
            `auditedicionfechahora` = NOW();

        CALL sp_compras_ppto_recalcular_totales(JSON_OBJECT('pptocompraid', v_pptocompraid), p_in_usuarioid, p_in_dispositivo, p_in_ip, v_recalc_json);
    END LOOP;
    CLOSE cur_reservas;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Presupuesto de PreOC reversado.', 'preocid', v_preocid, 'ciclo', v_grupo, 'reversas', v_reversas);
END//

DROP PROCEDURE IF EXISTS sp_compras_preoc_ppto_borrar_reserva_provisional//
CREATE PROCEDURE sp_compras_preoc_ppto_borrar_reserva_provisional (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_preocid INT DEFAULT NULL;
    DECLARE v_grupo VARCHAR(50);
    DECLARE v_aprobaciones INT DEFAULT 0;
    DECLARE v_borradas INT DEFAULT 0;
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_pptocompraid INT;
    DECLARE v_recalc_json JSON;

    DECLARE cur_pptos CURSOR FOR
        SELECT DISTINCT r.pptocompraid
          FROM `preocpptoresumen` r
         WHERE r.preocid = v_preocid
           AND r.preocpptoestado = 'RESERVA';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al borrar reserva provisional de PreOC.');
    END;

    SET v_preocid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.preocid')), ''), 'null') AS SIGNED);

    IF v_preocid IS NULL OR v_preocid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'preocid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_aprobaciones
      FROM `preocfirmantes`
     WHERE `preocid` = v_preocid
       AND `firmanteestado` = 'APR';

    IF v_aprobaciones > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No se puede borrar reserva provisional con aprobaciones registradas.');
        LEAVE sp_main;
    END IF;

    SELECT MAX(r.pptocompregruppomovimiento)
      INTO v_grupo
      FROM `pptocompratransacciones` r
     WHERE r.pptocompramoduloorigen = 'PREOC'
       AND r.pptocompranrodocumentoorigen = v_preocid
       AND r.pptocompratransacciontipoid = 'POC_RESERVA'
       AND NOT EXISTS (
            SELECT 1
              FROM `pptocompratransacciones` x
             WHERE x.pptocompramoduloorigen = 'PREOC'
               AND x.pptocompranrodocumentoorigen = v_preocid
               AND x.pptocompregruppomovimiento = r.pptocompregruppomovimiento
               AND x.pptocompratransacciontipoid IN ('POC_CONFIRMACION', 'POC_REVERSA')
       );

    IF v_grupo IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 200, 'message', 'No existe reserva provisional pendiente de borrar.', 'preocid', v_preocid);
        LEAVE sp_main;
    END IF;

    OPEN cur_pptos;
    ppto_loop: LOOP
        FETCH cur_pptos INTO v_pptocompraid;
        IF v_done = 1 THEN
            LEAVE ppto_loop;
        END IF;

        DELETE FROM `pptocompratransacciones`
         WHERE `pptocompramoduloorigen` = 'PREOC'
           AND `pptocompranrodocumentoorigen` = v_preocid
           AND `pptocompratransacciontipoid` = 'POC_RESERVA'
           AND `pptocompregruppomovimiento` = v_grupo
           AND `pptocompraid` = v_pptocompraid;

        SET v_borradas = v_borradas + ROW_COUNT();

        CALL sp_compras_ppto_recalcular_totales(JSON_OBJECT('pptocompraid', v_pptocompraid), p_in_usuarioid, p_in_dispositivo, p_in_ip, v_recalc_json);
    END LOOP;
    CLOSE cur_pptos;

    DELETE FROM `preocpptoresumen`
     WHERE `preocid` = v_preocid
       AND `preocpptoestado` = 'RESERVA';

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Reserva provisional borrada.', 'preocid', v_preocid, 'ciclo', v_grupo, 'movimientosBorrados', v_borradas);
END//

DELIMITER ;
