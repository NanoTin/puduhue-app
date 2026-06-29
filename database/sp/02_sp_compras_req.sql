DELIMITER //

DROP PROCEDURE IF EXISTS sp_compras_req_listar_resumen//
CREATE PROCEDURE sp_compras_req_listar_resumen (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_filtroBusqueda VARCHAR(255) DEFAULT NULL;
    DECLARE v_filtroEstado VARCHAR(20) DEFAULT NULL;
    DECLARE v_filtroFechaDesde DATE DEFAULT NULL;
    DECLARE v_filtroFechaHasta DATE DEFAULT NULL;
    DECLARE v_filtroCentroCostoId INT DEFAULT NULL;
    DECLARE v_filtroPrioridad INT DEFAULT NULL;
    DECLARE v_filtroSoloVigentes INT DEFAULT 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al listar REQ.');
    END;

    SET v_filtroBusqueda = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroBusqueda')), ''), 'null');
    SET v_filtroEstado = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroEstado')), ''), 'null');
    SET v_filtroFechaDesde = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), ''), 'null') AS DATE);
    SET v_filtroFechaHasta = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), ''), 'null') AS DATE);
    SET v_filtroCentroCostoId = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentroCostoId')), ''), 'null') AS SIGNED);
    SET v_filtroPrioridad = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPrioridad')), ''), 'null') AS SIGNED);
    SET v_filtroSoloVigentes = COALESCE(CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroSoloVigentes')), ''), 'null') AS SIGNED), 1);

    IF v_filtroFechaDesde IS NULL THEN
        SET v_filtroFechaDesde = '1900-01-01';
    END IF;
    IF v_filtroFechaHasta IS NULL THEN
        SET v_filtroFechaHasta = CURRENT_DATE();
    END IF;

    SELECT
        r.reqcompraid,
        r.reqcompracod,
        r.reqcompratipo,
        r.reqcomprafecha,
        r.centrocostoid,
        cc.centrocostocod,
        cc.centrocostodsc,
        r.funcionariorut,
        f.funcionarionombre,
        r.reqcompraobs,
        r.reqcompraprioridad,
        r.reqcompraestadoid,
        e.reqcomprasestadodsc,
        r.reqcompraestadopreocid,
        ep.reqcompraestadopreocdsc,
        r.reqaprobadoridpnd,
        up.usuarionombre AS reqaprobadorpendientenombre,
        r.reqaprobacionfecha,
        r.reqadvertenciapptocompra,
        r.reqfuerapptocompra,
        r.reqcompranettotal,
        r.reqcompravig,
        r.auditcreacionusuarioid,
        uc.usuarionombre AS creadornombre,
        COALESCE(d.lineas, 0) AS totalLineas,
        CASE
            WHEN r.reqcompraestadoid IN ('BRR', 'RCH') AND r.auditcreacionusuarioid = p_in_usuarioid THEN 1
            WHEN r.reqcompraestadoid = 'EDT' AND r.auditcreacionusuarioid = p_in_usuarioid THEN 1
            ELSE 0
        END AS puedeEditar,
        CASE
            WHEN r.reqcompraestadoid = 'PND' AND r.auditcreacionusuarioid = p_in_usuarioid THEN 1
            WHEN r.reqcompraestadoid = 'EDT' AND r.auditcreacionusuarioid = p_in_usuarioid THEN 1
            ELSE 0
        END AS puedeRetomarEdicion,
        CASE
            WHEN r.auditcreacionusuarioid = p_in_usuarioid
             AND r.reqcompraestadoid IN ('BRR', 'RCH', 'PND', 'EDT')
             AND NOT EXISTS (
                 SELECT 1
                   FROM reqcomprasfirmantes rfchk
                  WHERE rfchk.reqcompraid = r.reqcompraid
                    AND rfchk.firmanteestado = 'APR'
             ) THEN 1
            ELSE 0
        END AS puedeAnular
    FROM reqcompras r
    INNER JOIN centroscosto cc
        ON cc.centrocostoid = r.centrocostoid
    INNER JOIN reqcomprasestados e
        ON e.reqcomprasestadocod = r.reqcompraestadoid
    LEFT JOIN reqcompraestadopreoc ep
        ON ep.reqcompraestadopreoccod = r.reqcompraestadopreocid
    LEFT JOIN funcionarios f
        ON f.funcionariorut = r.funcionariorut
    LEFT JOIN usuarios uc
        ON uc.usuarioid = r.auditcreacionusuarioid
    LEFT JOIN usuarios up
        ON up.usuarioid = r.reqaprobadoridpnd
    LEFT JOIN (
        SELECT reqcompraid, COUNT(*) AS lineas
          FROM reqcomprasdetalle
         GROUP BY reqcompraid
    ) d
        ON d.reqcompraid = r.reqcompraid
    WHERE (v_filtroSoloVigentes = 0 OR r.reqcompravig = 1)
      AND (v_filtroEstado IS NULL OR r.reqcompraestadoid = v_filtroEstado)
      AND r.reqcomprafecha BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta
      AND (v_filtroCentroCostoId IS NULL OR r.centrocostoid = v_filtroCentroCostoId)
      AND (v_filtroPrioridad IS NULL OR r.reqcompraprioridad = v_filtroPrioridad)
      AND (
            v_filtroBusqueda IS NULL
            OR CONCAT_WS(' ',
                r.reqcompracod,
                r.reqcompraobs,
                cc.centrocostodsc,
                uc.usuarionombre,
                IFNULL(f.funcionarionombre, ''),
                IFNULL(up.usuarionombre, '')
            ) LIKE CONCAT('%', v_filtroBusqueda, '%')
      )
    ORDER BY r.reqcomprafecha DESC, r.reqcompraid DESC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_listar_pendientes_aprobacion//
CREATE PROCEDURE sp_compras_req_listar_pendientes_aprobacion (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_filtroBusqueda VARCHAR(255) DEFAULT NULL;
    DECLARE v_filtroFechaDesde DATE DEFAULT NULL;
    DECLARE v_filtroFechaHasta DATE DEFAULT NULL;
    DECLARE v_filtroCentroCostoId INT DEFAULT NULL;
    DECLARE v_filtroPrioridad INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al listar pendientes de aprobacion.');
    END;

    SET v_filtroBusqueda = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroBusqueda')), ''), 'null');
    SET v_filtroFechaDesde = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaDesde')), ''), 'null') AS DATE);
    SET v_filtroFechaHasta = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFechaHasta')), ''), 'null') AS DATE);
    SET v_filtroCentroCostoId = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroCentroCostoId')), ''), 'null') AS SIGNED);
    SET v_filtroPrioridad = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPrioridad')), ''), 'null') AS SIGNED);

    IF v_filtroFechaDesde IS NULL THEN
        SET v_filtroFechaDesde = '1900-01-01';
    END IF;
    IF v_filtroFechaHasta IS NULL THEN
        SET v_filtroFechaHasta = CURRENT_DATE();
    END IF;

    SELECT
        r.reqcompraid,
        r.reqcompracod,
        r.reqcomprafecha,
        r.reqcompratipo,
        r.reqcompraobs,
        r.reqcompraprioridad,
        r.reqcompraestadoid,
        r.reqcompraestadopreocid,
        r.reqadvertenciapptocompra,
        r.reqfuerapptocompra,
        r.reqcompranettotal,
        r.auditcreacionusuarioid,
        uc.usuarionombre AS creadornombre,
        r.funcionariorut,
        f.funcionarionombre,
        r.centrocostoid,
        cc.centrocostocod,
        cc.centrocostodsc
    FROM reqcompras r
    INNER JOIN centroscosto cc
        ON cc.centrocostoid = r.centrocostoid
    LEFT JOIN usuarios uc
        ON uc.usuarioid = r.auditcreacionusuarioid
    LEFT JOIN funcionarios f
        ON f.funcionariorut = r.funcionariorut
    WHERE r.reqcompravig = 1
      AND r.reqcompraestadoid = 'PND'
      AND r.reqaprobadoridpnd = p_in_usuarioid
      AND r.reqcomprafecha BETWEEN v_filtroFechaDesde AND v_filtroFechaHasta
      AND (v_filtroCentroCostoId IS NULL OR r.centrocostoid = v_filtroCentroCostoId)
      AND (v_filtroPrioridad IS NULL OR r.reqcompraprioridad = v_filtroPrioridad)
      AND (
            v_filtroBusqueda IS NULL
            OR CONCAT_WS(' ',
                r.reqcompracod,
                r.reqcompraobs,
                cc.centrocostodsc,
                uc.usuarionombre,
                IFNULL(f.funcionarionombre, '')
            ) LIKE CONCAT('%', v_filtroBusqueda, '%')
      )
    ORDER BY r.reqcomprafecha DESC, r.reqcompraid DESC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_consulta_por_id_resumen//
CREATE PROCEDURE sp_compras_req_consulta_por_id_resumen (
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
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al consultar cabecera REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_total
      FROM reqcompras
     WHERE reqcompraid = v_reqcompraid
       AND reqcompravig = 1;

    IF v_total = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;

    SELECT
        r.reqcompraid,
        r.reqcompracod,
        r.reqcompratipo,
        r.reqcomprafecha,
        r.centrocostoid,
        cc.centrocostocod,
        cc.centrocostodsc,
        cc.centrocostojefeusuarioid,
        cc.centrocostojefetecnicoid,
        r.funcionariorut,
        f.funcionarionombre,
        r.reqcompraobs,
        r.reqcompraprioridad,
        r.reqcompraestadoid,
        e.reqcomprasestadodsc,
        r.reqcompraestadopreocid,
        ep.reqcompraestadopreocdsc,
        r.reqaprobadoridpnd,
        up.usuarionombre AS reqaprobadorpendientenombre,
        r.reqaprobacionfecha,
        r.reqadvertenciapptocompra,
        r.reqfuerapptocompra,
        r.reqcompranettotal,
        r.reqcompravig,
        r.auditcreacionusuarioid,
        uc.usuarionombre AS creadornombre
    FROM reqcompras r
    INNER JOIN centroscosto cc
        ON cc.centrocostoid = r.centrocostoid
    INNER JOIN reqcomprasestados e
        ON e.reqcomprasestadocod = r.reqcompraestadoid
    LEFT JOIN reqcompraestadopreoc ep
        ON ep.reqcompraestadopreoccod = r.reqcompraestadopreocid
    LEFT JOIN funcionarios f
        ON f.funcionariorut = r.funcionariorut
    LEFT JOIN usuarios uc
        ON uc.usuarioid = r.auditcreacionusuarioid
    LEFT JOIN usuarios up
        ON up.usuarioid = r.reqaprobadoridpnd
    WHERE r.reqcompraid = v_reqcompraid
      AND r.reqcompravig = 1;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_consulta_por_id_detalle//
CREATE PROCEDURE sp_compras_req_consulta_por_id_detalle (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al consultar detalle REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT
        d.reqcompradetid,
        d.reqcompraid,
        d.reqcompradetlinea,
        d.invitemid,
        ii.erpinvitemcod,
        d.subfamiliaid,
        sf.subfamiliacod,
        sf.subfamiliadsc,
        d.reqcompradetitemcod,
        d.reqcompradetdsc,
        d.invunidmedid,
        um.invunidmeddsc,
        d.reqcompradetcantidad,
        d.reqitemcantanulada,
        d.reqcompradetprecioneto,
        d.reqcompradettotalneto,
        d.reqcompradetobs,
        d.reqcompradetitemmodificado,
        d.reqcompradetadvertenciappto,
        d.reqcompradetultreqfecha,
        d.reqcompradetultreqcantidad
    FROM reqcomprasdetalle d
    INNER JOIN invitems ii
        ON ii.invitemid = d.invitemid
    LEFT JOIN subfamilias sf
        ON sf.subfamiliaid = d.subfamiliaid
    LEFT JOIN invunidadesmedidas um
        ON um.invunidmedid = d.invunidmedid
    WHERE d.reqcompraid = v_reqcompraid
    ORDER BY d.reqcompradetlinea ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_consulta_por_id_firmantes//
CREATE PROCEDURE sp_compras_req_consulta_por_id_firmantes (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al consultar firmantes REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT
        rf.reqcomprafirmanteid,
        rf.reqcompraid,
        rf.firmanteusuarioid,
        u.usuarionombre,
        u.usuariorut,
        rf.firmanteorden,
        rf.firmantetipo,
        rf.firmantedefault,
        rf.firmantefuerapptocompra,
        rf.firmantemotivoinclusion,
        rf.firmanteestado,
        rf.firmantefechahora,
        rf.firmantecomentario,
        rf.firmantereemplazodeid
    FROM reqcomprasfirmantes rf
    INNER JOIN usuarios u
        ON u.usuarioid = rf.firmanteusuarioid
    WHERE rf.reqcompraid = v_reqcompraid
    ORDER BY rf.firmanteorden ASC, rf.reqcomprafirmanteid ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_consulta_por_id_comentarios//
CREATE PROCEDURE sp_compras_req_consulta_por_id_comentarios (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al consultar comentarios REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT
        rc.reqcomentarioid,
        rc.reqcompraid,
        rc.usuarioid,
        u.usuarionombre,
        rc.reqcomentariotipo,
        rc.reqcomentariotxt,
        rc.reqcomentariofechahora
    FROM reqcomprascomentarios rc
    INNER JOIN usuarios u
        ON u.usuarioid = rc.usuarioid
    WHERE rc.reqcompraid = v_reqcompraid
    ORDER BY rc.reqcomentariofechahora DESC, rc.reqcomentarioid DESC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_crear//
CREATE PROCEDURE sp_compras_req_crear (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_reqcompratipo INT DEFAULT NULL;
    DECLARE v_centrocostoid INT DEFAULT NULL;
    DECLARE v_funcionariorut VARCHAR(12) DEFAULT NULL;
    DECLARE v_reqcompraobs TEXT DEFAULT NULL;
    DECLARE v_reqcompraprioridad INT DEFAULT NULL;
    DECLARE v_accion VARCHAR(40) DEFAULT NULL;
    DECLARE v_detalle_len INT DEFAULT 0;
    DECLARE v_firmantes_len INT DEFAULT 0;
    DECLARE v_idx INT DEFAULT 0;
    DECLARE v_invitemid INT DEFAULT NULL;
    DECLARE v_cantidad DECIMAL(15,4) DEFAULT NULL;
    DECLARE v_detobs TEXT DEFAULT NULL;
    DECLARE v_subfamiliaid INT DEFAULT NULL;
    DECLARE v_invunidmedid INT DEFAULT NULL;
    DECLARE v_precio DECIMAL(15,4) DEFAULT NULL;
    DECLARE v_itemcod VARCHAR(50) DEFAULT NULL;
    DECLARE v_itemdsc VARCHAR(200) DEFAULT NULL;
    DECLARE v_totallinea DECIMAL(15,2) DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT 'BRR';
    DECLARE v_reqaprobadoridpnd INT DEFAULT NULL;
    DECLARE v_advertencia INT DEFAULT 0;
    DECLARE v_fuera INT DEFAULT 0;
    DECLARE v_snapshot_json JSON DEFAULT NULL;
    DECLARE v_snapshot_status INT DEFAULT NULL;
    DECLARE v_snapshot_message VARCHAR(255) DEFAULT NULL;
    DECLARE v_resultado_firmantes INT DEFAULT 0;
    DECLARE v_default_jefe INT DEFAULT NULL;
    DECLARE v_default_tecnico INT DEFAULT NULL;
    DECLARE v_manual_usuarioid INT DEFAULT NULL;
    DECLARE v_manual_orden INT DEFAULT NULL;
    DECLARE v_reemplazoid INT DEFAULT NULL;
    DECLARE v_totalreq DECIMAL(15,2) DEFAULT 0;
    DECLARE v_ultfecha DATE DEFAULT NULL;
    DECLARE v_ultcantidad DECIMAL(15,4) DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al crear REQ.');
    END;

    IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json es obligatorio.');
        LEAVE sp_main;
    END IF;

    SET v_reqcompratipo = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompratipo')), ''), 'null') AS SIGNED);
    SET v_centrocostoid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), ''), 'null') AS SIGNED);
    SET v_funcionariorut = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.funcionariorut')), ''), 'null');
    SET v_reqcompraobs = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraobs')), ''), 'null');
    SET v_reqcompraprioridad = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraprioridad')), ''), 'null') AS SIGNED);
    SET v_accion = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.accion')), ''), 'null');
    SET v_detalle_len = COALESCE(JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.detalle')), 0);
    SET v_firmantes_len = COALESCE(JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.firmantesManual')), 0);

    IF NOT EXISTS (
        SELECT 1
          FROM usuarios u
         WHERE u.usuarioid = p_in_usuarioid
           AND u.usuarioactivo = 1
           AND u.usuariobloqueado = 0
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Usuario ejecutor invalido.');
        LEAVE sp_main;
    END IF;

    IF v_accion NOT IN ('guardar_borrador', 'enviar_aprobacion') THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Accion invalida para crear REQ.');
        LEAVE sp_main;
    END IF;

    IF v_reqcompratipo IS NULL OR v_reqcompratipo NOT IN (1, 2) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompratipo es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_centrocostoid IS NULL OR v_centrocostoid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'centrocostoid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_reqcompraprioridad IS NULL OR v_reqcompraprioridad NOT IN (1, 2) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraprioridad es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_detalle_len <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe ingresar al menos un item.');
        LEAVE sp_main;
    END IF;

    IF NOT EXISTS (
        SELECT 1
          FROM usuarioscentroscosto uc
          INNER JOIN centroscosto cc
            ON cc.centrocostoid = uc.centrocostoid
         WHERE uc.usuarioid = p_in_usuarioid
           AND uc.centrocostoid = v_centrocostoid
           AND uc.usucenactivo = 1
           AND cc.centrocostoactivo = 1
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El usuario no tiene acceso al centro de costo seleccionado.');
        LEAVE sp_main;
    END IF;

    IF v_funcionariorut IS NOT NULL AND NOT EXISTS (
        SELECT 1
          FROM funcionarios f
         WHERE f.funcionariorut = v_funcionariorut
           AND f.funcionarioactivo = 1
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Funcionario no valido.');
        LEAVE sp_main;
    END IF;

    INSERT INTO reqcompras (
        reqcompracod,
        reqcompratipo,
        reqcomprafecha,
        centrocostoid,
        funcionariorut,
        reqcompraobs,
        reqcompraprioridad,
        reqcompraestadoid,
        reqaprobadoridpnd,
        reqadvertenciapptocompra,
        reqfuerapptocompra,
        reqcompranettotal,
        reqcompravig,
        auditcreacionusuarioid,
        auditcreaciondispositivo,
        auditcreacionip
    ) VALUES (
        'REQ-PEND',
        v_reqcompratipo,
        CURRENT_DATE(),
        v_centrocostoid,
        v_funcionariorut,
        v_reqcompraobs,
        v_reqcompraprioridad,
        'BRR',
        NULL,
        0,
        0,
        0,
        1,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    );

    SET v_reqcompraid = LAST_INSERT_ID();

    UPDATE reqcompras
       SET reqcompracod = CONCAT('REQ-', LPAD(v_reqcompraid, 8, '0'))
     WHERE reqcompraid = v_reqcompraid;

    SET v_idx = 0;
    WHILE v_idx < v_detalle_len DO
        SET v_invitemid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalle[', v_idx, '].invitemid'))), ''), 'null') AS SIGNED);
        SET v_cantidad = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalle[', v_idx, '].reqcompradetcantidad'))), ''), 'null') AS DECIMAL(15,4));
        SET v_detobs = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalle[', v_idx, '].reqcompradetobs'))), ''), 'null');
        SET v_subfamiliaid = NULL;
        SET v_invunidmedid = NULL;
        SET v_precio = NULL;
        SET v_itemcod = NULL;
        SET v_itemdsc = NULL;
        SET v_ultfecha = NULL;
        SET v_ultcantidad = NULL;

        IF v_invitemid IS NULL OR v_invitemid <= 0 THEN
            DELETE FROM reqcompras WHERE reqcompraid = v_reqcompraid;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': invitemid es obligatorio.'));
            LEAVE sp_main;
        END IF;
        IF v_cantidad IS NULL OR v_cantidad <= 0 THEN
            DELETE FROM reqcompras WHERE reqcompraid = v_reqcompraid;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': cantidad invalida.'));
            LEAVE sp_main;
        END IF;

        SELECT
            ii.subfamiliaid,
            ii.invunidmedid,
            ii.invitemcostoestandar,
            ii.erpinvitemcod,
            ii.invitemdsc
          INTO
            v_subfamiliaid,
            v_invunidmedid,
            v_precio,
            v_itemcod,
            v_itemdsc
          FROM invitems ii
         WHERE ii.invitemid = v_invitemid
           AND ii.invitemactivo = 1
           AND ii.invitemcompra = 1
           AND ii.subfamiliaid IS NOT NULL
           AND ii.invunidmedid IS NOT NULL
         LIMIT 1;

        IF v_subfamiliaid IS NULL OR v_invunidmedid IS NULL THEN
            DELETE FROM reqcompras WHERE reqcompraid = v_reqcompraid;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': item no valido para compras.'));
            LEAVE sp_main;
        END IF;

        IF (v_reqcompratipo = 1 AND NOT EXISTS (SELECT 1 FROM invitems ii WHERE ii.invitemid = v_invitemid AND ii.invitemstockeable = 1))
           OR (v_reqcompratipo = 2 AND NOT EXISTS (SELECT 1 FROM invitems ii WHERE ii.invitemid = v_invitemid AND ii.invitemstockeable = 0)) THEN
            DELETE FROM reqcompras WHERE reqcompraid = v_reqcompraid;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': el item no coincide con el tipo de REQ.'));
            LEAVE sp_main;
        END IF;

        IF COALESCE(v_precio, 0) <= 0 THEN
            DELETE FROM reqcompras WHERE reqcompraid = v_reqcompraid;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': item sin precio vigente, contacte a Administracion.'));
            LEAVE sp_main;
        END IF;

        IF EXISTS (
            SELECT 1
              FROM reqcomprasdetalle d
             WHERE d.reqcompraid = v_reqcompraid
               AND d.invitemid = v_invitemid
        ) THEN
            DELETE FROM reqcompras WHERE reqcompraid = v_reqcompraid;
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': item duplicado.'));
            LEAVE sp_main;
        END IF;

        SELECT d.reqcomprafecha, dd.reqcompradetcantidad
          INTO v_ultfecha, v_ultcantidad
          FROM reqcomprasdetalle dd
          INNER JOIN reqcompras d
            ON d.reqcompraid = dd.reqcompraid
         WHERE d.centrocostoid = v_centrocostoid
           AND dd.invitemid = v_invitemid
           AND d.reqcompraestadoid <> 'ANL'
         ORDER BY d.reqcomprafecha DESC, dd.reqcompradetid DESC
         LIMIT 1;

        SET v_totallinea = ROUND(v_cantidad * v_precio, 2);

        INSERT INTO reqcomprasdetalle (
            reqcompraid,
            reqcompradetlinea,
            invitemid,
            subfamiliaid,
            reqcompradetitemcod,
            reqcompradetdsc,
            invunidmedid,
            reqcompradetcantidad,
            reqcompradetprecioneto,
            reqcompradettotalneto,
            reqcompradetobs,
            reqcompradetitemmodificado,
            reqcompradetadvertenciappto,
            reqcompradetultreqfecha,
            reqcompradetultreqcantidad,
            auditcreacionusuarioid,
            auditcreaciondispositivo,
            auditcreacionip
        ) VALUES (
            v_reqcompraid,
            v_idx + 1,
            v_invitemid,
            v_subfamiliaid,
            COALESCE(v_itemcod, CONCAT('ITEM-', v_invitemid)),
            COALESCE(v_itemdsc, CONCAT('Item ', v_invitemid)),
            v_invunidmedid,
            v_cantidad,
            ROUND(v_precio, 2),
            v_totallinea,
            v_detobs,
            0,
            0,
            v_ultfecha,
            v_ultcantidad,
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        );

        SET v_idx = v_idx + 1;
        SET v_subfamiliaid = NULL;
        SET v_invunidmedid = NULL;
        SET v_precio = NULL;
        SET v_itemcod = NULL;
        SET v_itemdsc = NULL;
        SET v_ultfecha = NULL;
        SET v_ultcantidad = NULL;
    END WHILE;

    SELECT COALESCE(SUM(reqcompradettotalneto), 0)
      INTO v_totalreq
      FROM reqcomprasdetalle
     WHERE reqcompraid = v_reqcompraid;

    UPDATE reqcompras
       SET reqcompranettotal = v_totalreq
     WHERE reqcompraid = v_reqcompraid;

    CALL sp_compras_req_ppto_snapshot_actualizar(
        JSON_OBJECT('reqcompraid', v_reqcompraid),
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        v_snapshot_json
    );

    SET v_snapshot_status = CAST(JSON_UNQUOTE(JSON_EXTRACT(v_snapshot_json, '$.status')) AS SIGNED);
    SET v_snapshot_message = JSON_UNQUOTE(JSON_EXTRACT(v_snapshot_json, '$.message'));
    IF COALESCE(v_snapshot_status, 500) <> 200 THEN
        DELETE FROM reqcompras WHERE reqcompraid = v_reqcompraid;
        SET p_out_json = JSON_OBJECT('status', 400, 'message', COALESCE(v_snapshot_message, 'Error al analizar presupuesto REQ.'));
        LEAVE sp_main;
    END IF;

    SELECT reqadvertenciapptocompra, reqfuerapptocompra
      INTO v_advertencia, v_fuera
      FROM reqcompras
     WHERE reqcompraid = v_reqcompraid;

    DROP TEMPORARY TABLE IF EXISTS tmp_req_firmantes;
    CREATE TEMPORARY TABLE tmp_req_firmantes (
        firmanteusuarioid INT NOT NULL,
        firmanteorden INT NOT NULL,
        firmantetipo VARCHAR(20) NOT NULL,
        firmantedefault TINYINT(1) NOT NULL DEFAULT 0,
        firmantefuerapptocompra TINYINT(1) NOT NULL DEFAULT 0,
        firmantemotivoinclusion VARCHAR(50) NULL,
        PRIMARY KEY (firmanteusuarioid)
    );

    SELECT cc.centrocostojefeusuarioid, cc.centrocostojefetecnicoid
      INTO v_default_jefe, v_default_tecnico
      FROM centroscosto cc
     WHERE cc.centrocostoid = v_centrocostoid
     LIMIT 1;

    IF v_default_jefe IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuarios u WHERE u.usuarioid = v_default_jefe AND u.usuarioactivo = 1 AND u.usuariobloqueado = 0 AND u.usuariopermiteaprobreq = 1
    ) THEN
        SELECT COALESCE((
            SELECT api.aprobadorreemplazousuarioid
              FROM aprobadoresperiodoinactividad api
             WHERE api.aprobadorusuarioid = v_default_jefe
               AND api.aprobadorperiodoactivo = 1
               AND CURRENT_DATE() BETWEEN api.aprobadorperiodofechainicio AND api.aprobadorperiodofechafin
             LIMIT 1
        ), v_default_jefe)
          INTO v_reemplazoid;

        INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
        VALUES (COALESCE(v_reemplazoid, v_default_jefe), 10, 'JEF_CC', 1, 0, NULL)
        ON DUPLICATE KEY UPDATE firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden));
    END IF;

    SET v_reemplazoid = NULL;
    IF v_default_tecnico IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuarios u WHERE u.usuarioid = v_default_tecnico AND u.usuarioactivo = 1 AND u.usuariobloqueado = 0 AND u.usuariopermiteaprobreq = 1
    ) THEN
        SELECT COALESCE((
            SELECT api.aprobadorreemplazousuarioid
              FROM aprobadoresperiodoinactividad api
             WHERE api.aprobadorusuarioid = v_default_tecnico
               AND api.aprobadorperiodoactivo = 1
               AND CURRENT_DATE() BETWEEN api.aprobadorperiodofechainicio AND api.aprobadorperiodofechafin
             LIMIT 1
        ), v_default_tecnico)
          INTO v_reemplazoid;

        INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
        VALUES (COALESCE(v_reemplazoid, v_default_tecnico), 20, 'JEF_TEC', 1, 0, NULL)
        ON DUPLICATE KEY UPDATE firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden));
    END IF;

    SET v_idx = 0;
    WHILE v_idx < v_firmantes_len DO
        SET v_manual_usuarioid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.firmantesManual[', v_idx, '].usuarioid'))), ''), 'null') AS SIGNED);
        SET v_manual_orden = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.firmantesManual[', v_idx, '].firmanteorden'))), ''), 'null') AS SIGNED);

        IF v_manual_usuarioid IS NOT NULL
           AND v_manual_usuarioid > 0
           AND EXISTS (
               SELECT 1
                 FROM usuarios u
                WHERE u.usuarioid = v_manual_usuarioid
                  AND u.usuarioactivo = 1
                  AND u.usuariobloqueado = 0
                  AND u.usuariopermiteaprobreq = 1
           ) THEN
            SELECT COALESCE((
                SELECT api.aprobadorreemplazousuarioid
                  FROM aprobadoresperiodoinactividad api
                 WHERE api.aprobadorusuarioid = v_manual_usuarioid
                   AND api.aprobadorperiodoactivo = 1
                   AND CURRENT_DATE() BETWEEN api.aprobadorperiodofechainicio AND api.aprobadorperiodofechafin
                 LIMIT 1
            ), v_manual_usuarioid)
              INTO v_reemplazoid;

            INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
            VALUES (COALESCE(v_reemplazoid, v_manual_usuarioid), COALESCE(v_manual_orden, ((v_idx + 1) * 10) + 20), 'MANUAL', 0, 0, NULL)
            ON DUPLICATE KEY UPDATE firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden));
        END IF;

        SET v_idx = v_idx + 1;
    END WHILE;

    IF v_fuera = 1 THEN
        INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
        SELECT
            u.usuarioid,
            (1000 + (u.usuarioreqautorizadorfuerapptocompraorden * 10)),
            'FUERA_PPTO',
            0,
            1,
            'REQ_SIN_SALDO_PPTO'
        FROM usuarios u
        WHERE u.usuarioactivo = 1
          AND u.usuariobloqueado = 0
          AND u.usuariopermiteaprobreq = 1
          AND u.usuarioreqautorizadorfuerapptocompra = 1
          AND u.usuarioreqautorizadorfuerapptocompraorden > 0
        ORDER BY u.usuarioreqautorizadorfuerapptocompraorden
        ON DUPLICATE KEY UPDATE
            firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden)),
            firmantefuerapptocompra = GREATEST(firmantefuerapptocompra, VALUES(firmantefuerapptocompra)),
            firmantemotivoinclusion = COALESCE(firmantemotivoinclusion, VALUES(firmantemotivoinclusion));
    END IF;

    INSERT INTO reqcomprasfirmantes (
        reqcompraid,
        firmanteusuarioid,
        firmanteorden,
        firmantetipo,
        firmantedefault,
        firmantefuerapptocompra,
        firmantemotivoinclusion,
        firmanteestado,
        auditcreacionusuarioid,
        auditcreaciondispositivo,
        auditcreacionip
    )
    SELECT
        v_reqcompraid,
        t.firmanteusuarioid,
        ROW_NUMBER() OVER (ORDER BY t.firmanteorden ASC, t.firmanteusuarioid ASC),
        t.firmantetipo,
        t.firmantedefault,
        t.firmantefuerapptocompra,
        t.firmantemotivoinclusion,
        'PND',
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    FROM tmp_req_firmantes t;

    SELECT COUNT(*)
      INTO v_resultado_firmantes
      FROM reqcomprasfirmantes
     WHERE reqcompraid = v_reqcompraid;

    IF v_accion = 'enviar_aprobacion' AND v_resultado_firmantes > 0 THEN
        SELECT rf.firmanteusuarioid
          INTO v_reqaprobadoridpnd
          FROM reqcomprasfirmantes rf
         WHERE rf.reqcompraid = v_reqcompraid
         ORDER BY rf.firmanteorden ASC, rf.reqcomprafirmanteid ASC
         LIMIT 1;

        SET v_estado = 'PND';
    ELSE
        SET v_reqaprobadoridpnd = NULL;
        SET v_estado = 'BRR';
    END IF;

    UPDATE reqcompras
       SET reqcompraestadoid = v_estado,
           reqaprobadoridpnd = v_reqaprobadoridpnd,
           reqcompranettotal = v_totalreq,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    INSERT INTO reqcompraslog (
        reqcompraid,
        logusuarioid,
        logdispositivo,
        logip,
        logtipo,
        logparamjson,
        logregbkpjson
    ) VALUES (
        v_reqcompraid,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        'INS',
        p_in_json,
        NULL
    );

    IF v_accion = 'enviar_aprobacion' AND v_resultado_firmantes = 0 THEN
        INSERT INTO reqcomprascomentarios (
            reqcompraid,
            usuarioid,
            reqcomentariotipo,
            reqcomentariotxt,
            auditcreacionusuarioid,
            auditcreaciondispositivo,
            auditcreacionip
        ) VALUES (
            v_reqcompraid,
            p_in_usuarioid,
            'INFO',
            'No se encontraron firmantes activos para enviar a aprobacion. El REQ quedo en borrador.',
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        );
    END IF;

    DROP TEMPORARY TABLE IF EXISTS tmp_req_firmantes;

    SET p_out_json = JSON_OBJECT(
        'status', 200,
        'message', CASE
            WHEN v_estado = 'PND' THEN 'REQ enviado a aprobacion.'
            WHEN v_accion = 'enviar_aprobacion' AND v_resultado_firmantes = 0 THEN 'REQ guardado en borrador sin firmantes activos.'
            ELSE 'REQ guardado correctamente.'
        END,
        'id', v_reqcompraid,
        'reqcompracod', CONCAT('REQ-', LPAD(v_reqcompraid, 8, '0')),
        'estado', v_estado,
        'reqaprobadoridpnd', v_reqaprobadoridpnd,
        'advertenciapptocompra', v_advertencia,
        'fuerapptocompra', v_fuera
    );
END//

DROP PROCEDURE IF EXISTS sp_compras_req_editar//
CREATE PROCEDURE sp_compras_req_editar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_reqcompratipo INT DEFAULT NULL;
    DECLARE v_centrocostoid INT DEFAULT NULL;
    DECLARE v_funcionariorut VARCHAR(12) DEFAULT NULL;
    DECLARE v_reqcompraobs TEXT DEFAULT NULL;
    DECLARE v_reqcompraprioridad INT DEFAULT NULL;
    DECLARE v_accion VARCHAR(40) DEFAULT NULL;
    DECLARE v_estado_actual VARCHAR(20) DEFAULT NULL;
    DECLARE v_creador INT DEFAULT NULL;
    DECLARE v_total_apr INT DEFAULT 0;
    DECLARE v_detalle_len INT DEFAULT 0;
    DECLARE v_firmantes_len INT DEFAULT 0;
    DECLARE v_idx INT DEFAULT 0;
    DECLARE v_invitemid INT DEFAULT NULL;
    DECLARE v_cantidad DECIMAL(15,4) DEFAULT NULL;
    DECLARE v_detobs TEXT DEFAULT NULL;
    DECLARE v_subfamiliaid INT DEFAULT NULL;
    DECLARE v_invunidmedid INT DEFAULT NULL;
    DECLARE v_precio DECIMAL(15,4) DEFAULT NULL;
    DECLARE v_itemcod VARCHAR(50) DEFAULT NULL;
    DECLARE v_itemdsc VARCHAR(200) DEFAULT NULL;
    DECLARE v_totallinea DECIMAL(15,2) DEFAULT NULL;
    DECLARE v_estado_final VARCHAR(20) DEFAULT 'BRR';
    DECLARE v_reqaprobadoridpnd INT DEFAULT NULL;
    DECLARE v_advertencia INT DEFAULT 0;
    DECLARE v_fuera INT DEFAULT 0;
    DECLARE v_snapshot_json JSON DEFAULT NULL;
    DECLARE v_snapshot_status INT DEFAULT NULL;
    DECLARE v_snapshot_message VARCHAR(255) DEFAULT NULL;
    DECLARE v_resultado_firmantes INT DEFAULT 0;
    DECLARE v_default_jefe INT DEFAULT NULL;
    DECLARE v_default_tecnico INT DEFAULT NULL;
    DECLARE v_manual_usuarioid INT DEFAULT NULL;
    DECLARE v_manual_orden INT DEFAULT NULL;
    DECLARE v_reemplazoid INT DEFAULT NULL;
    DECLARE v_totalreq DECIMAL(15,2) DEFAULT 0;
    DECLARE v_ultfecha DATE DEFAULT NULL;
    DECLARE v_ultcantidad DECIMAL(15,4) DEFAULT NULL;
    DECLARE v_prev_json JSON DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al editar REQ.');
    END;

    IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' OR JSON_LENGTH(p_in_json) = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json es obligatorio.');
        LEAVE sp_main;
    END IF;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    SET v_reqcompratipo = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompratipo')), ''), 'null') AS SIGNED);
    SET v_centrocostoid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.centrocostoid')), ''), 'null') AS SIGNED);
    SET v_funcionariorut = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.funcionariorut')), ''), 'null');
    SET v_reqcompraobs = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraobs')), ''), 'null');
    SET v_reqcompraprioridad = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraprioridad')), ''), 'null') AS SIGNED);
    SET v_accion = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.accion')), ''), 'null');
    SET v_detalle_len = COALESCE(JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.detalle')), 0);
    SET v_firmantes_len = COALESCE(JSON_LENGTH(JSON_EXTRACT(p_in_json, '$.firmantesManual')), 0);

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_accion NOT IN ('guardar_borrador', 'reenviar_aprobacion') THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Accion invalida para editar REQ.');
        LEAVE sp_main;
    END IF;
    IF v_reqcompratipo IS NULL OR v_reqcompratipo NOT IN (1, 2) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompratipo es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_centrocostoid IS NULL OR v_centrocostoid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'centrocostoid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_reqcompraprioridad IS NULL OR v_reqcompraprioridad NOT IN (1, 2) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraprioridad es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_detalle_len <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Debe ingresar al menos un item.');
        LEAVE sp_main;
    END IF;

    SELECT
        r.reqcompraestadoid,
        r.auditcreacionusuarioid,
        JSON_OBJECT(
            'reqcompraid', r.reqcompraid,
            'reqcompracod', r.reqcompracod,
            'reqcompraestadoid', r.reqcompraestadoid,
            'centrocostoid', r.centrocostoid,
            'reqcompratipo', r.reqcompratipo,
            'reqcompraprioridad', r.reqcompraprioridad,
            'reqcompranettotal', r.reqcompranettotal
        )
      INTO v_estado_actual, v_creador, v_prev_json
      FROM reqcompras r
     WHERE r.reqcompraid = v_reqcompraid
       AND r.reqcompravig = 1
     LIMIT 1;

    IF v_estado_actual IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;

    IF v_estado_actual NOT IN ('BRR', 'RCH', 'EDT') THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El estado actual no permite edicion.');
        LEAVE sp_main;
    END IF;

    IF v_creador <> p_in_usuarioid THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Solo el creador puede editar el REQ.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_total_apr
      FROM reqcomprasfirmantes rf
     WHERE rf.reqcompraid = v_reqcompraid
       AND rf.firmanteestado = 'APR';

    IF v_total_apr > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ ya tiene aprobaciones efectivas y no puede editarse.');
        LEAVE sp_main;
    END IF;

    IF NOT EXISTS (
        SELECT 1
          FROM usuarioscentroscosto uc
          INNER JOIN centroscosto cc
            ON cc.centrocostoid = uc.centrocostoid
         WHERE uc.usuarioid = p_in_usuarioid
           AND uc.centrocostoid = v_centrocostoid
           AND uc.usucenactivo = 1
           AND cc.centrocostoactivo = 1
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El usuario no tiene acceso al centro de costo seleccionado.');
        LEAVE sp_main;
    END IF;

    IF v_funcionariorut IS NOT NULL AND NOT EXISTS (
        SELECT 1
          FROM funcionarios f
         WHERE f.funcionariorut = v_funcionariorut
           AND f.funcionarioactivo = 1
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Funcionario no valido.');
        LEAVE sp_main;
    END IF;

    DELETE FROM reqcomprasdetalle WHERE reqcompraid = v_reqcompraid;
    DELETE FROM reqcomprasfirmantes WHERE reqcompraid = v_reqcompraid;

    SET v_idx = 0;
    WHILE v_idx < v_detalle_len DO
        SET v_invitemid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalle[', v_idx, '].invitemid'))), ''), 'null') AS SIGNED);
        SET v_cantidad = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalle[', v_idx, '].reqcompradetcantidad'))), ''), 'null') AS DECIMAL(15,4));
        SET v_detobs = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.detalle[', v_idx, '].reqcompradetobs'))), ''), 'null');
        SET v_subfamiliaid = NULL;
        SET v_invunidmedid = NULL;
        SET v_precio = NULL;
        SET v_itemcod = NULL;
        SET v_itemdsc = NULL;
        SET v_ultfecha = NULL;
        SET v_ultcantidad = NULL;

        IF v_invitemid IS NULL OR v_invitemid <= 0 THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': invitemid es obligatorio.'));
            LEAVE sp_main;
        END IF;
        IF v_cantidad IS NULL OR v_cantidad <= 0 THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': cantidad invalida.'));
            LEAVE sp_main;
        END IF;

        SELECT
            ii.subfamiliaid,
            ii.invunidmedid,
            ii.invitemcostoestandar,
            ii.erpinvitemcod,
            ii.invitemdsc
          INTO
            v_subfamiliaid,
            v_invunidmedid,
            v_precio,
            v_itemcod,
            v_itemdsc
          FROM invitems ii
         WHERE ii.invitemid = v_invitemid
           AND ii.invitemactivo = 1
           AND ii.invitemcompra = 1
           AND ii.subfamiliaid IS NOT NULL
           AND ii.invunidmedid IS NOT NULL
         LIMIT 1;

        IF v_subfamiliaid IS NULL OR v_invunidmedid IS NULL THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': item no valido para compras.'));
            LEAVE sp_main;
        END IF;

        IF (v_reqcompratipo = 1 AND NOT EXISTS (SELECT 1 FROM invitems ii WHERE ii.invitemid = v_invitemid AND ii.invitemstockeable = 1))
           OR (v_reqcompratipo = 2 AND NOT EXISTS (SELECT 1 FROM invitems ii WHERE ii.invitemid = v_invitemid AND ii.invitemstockeable = 0)) THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': el item no coincide con el tipo de REQ.'));
            LEAVE sp_main;
        END IF;

        IF COALESCE(v_precio, 0) <= 0 THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': item sin precio vigente, contacte a Administracion.'));
            LEAVE sp_main;
        END IF;

        IF EXISTS (
            SELECT 1
              FROM reqcomprasdetalle d
             WHERE d.reqcompraid = v_reqcompraid
               AND d.invitemid = v_invitemid
        ) THEN
            SET p_out_json = JSON_OBJECT('status', 400, 'message', CONCAT('Linea ', v_idx + 1, ': item duplicado.'));
            LEAVE sp_main;
        END IF;

        SELECT d.reqcomprafecha, dd.reqcompradetcantidad
          INTO v_ultfecha, v_ultcantidad
          FROM reqcomprasdetalle dd
          INNER JOIN reqcompras d
            ON d.reqcompraid = dd.reqcompraid
         WHERE d.centrocostoid = v_centrocostoid
           AND dd.invitemid = v_invitemid
           AND d.reqcompraestadoid <> 'ANL'
           AND d.reqcompraid <> v_reqcompraid
         ORDER BY d.reqcomprafecha DESC, dd.reqcompradetid DESC
         LIMIT 1;

        SET v_totallinea = ROUND(v_cantidad * v_precio, 2);

        INSERT INTO reqcomprasdetalle (
            reqcompraid,
            reqcompradetlinea,
            invitemid,
            subfamiliaid,
            reqcompradetitemcod,
            reqcompradetdsc,
            invunidmedid,
            reqcompradetcantidad,
            reqcompradetprecioneto,
            reqcompradettotalneto,
            reqcompradetobs,
            reqcompradetitemmodificado,
            reqcompradetadvertenciappto,
            reqcompradetultreqfecha,
            reqcompradetultreqcantidad,
            auditcreacionusuarioid,
            auditcreaciondispositivo,
            auditcreacionip
        ) VALUES (
            v_reqcompraid,
            v_idx + 1,
            v_invitemid,
            v_subfamiliaid,
            COALESCE(v_itemcod, CONCAT('ITEM-', v_invitemid)),
            COALESCE(v_itemdsc, CONCAT('Item ', v_invitemid)),
            v_invunidmedid,
            v_cantidad,
            ROUND(v_precio, 2),
            v_totallinea,
            v_detobs,
            0,
            0,
            v_ultfecha,
            v_ultcantidad,
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        );

        SET v_idx = v_idx + 1;
        SET v_subfamiliaid = NULL;
        SET v_invunidmedid = NULL;
        SET v_precio = NULL;
        SET v_itemcod = NULL;
        SET v_itemdsc = NULL;
        SET v_ultfecha = NULL;
        SET v_ultcantidad = NULL;
    END WHILE;

    SELECT COALESCE(SUM(reqcompradettotalneto), 0)
      INTO v_totalreq
      FROM reqcomprasdetalle
     WHERE reqcompraid = v_reqcompraid;

    UPDATE reqcompras
       SET reqcompratipo = v_reqcompratipo,
           reqcomprafecha = CURRENT_DATE(),
           centrocostoid = v_centrocostoid,
           funcionariorut = v_funcionariorut,
           reqcompraobs = v_reqcompraobs,
           reqcompraprioridad = v_reqcompraprioridad,
           reqcompranettotal = v_totalreq,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    CALL sp_compras_req_ppto_snapshot_actualizar(
        JSON_OBJECT('reqcompraid', v_reqcompraid),
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        v_snapshot_json
    );

    SET v_snapshot_status = CAST(JSON_UNQUOTE(JSON_EXTRACT(v_snapshot_json, '$.status')) AS SIGNED);
    SET v_snapshot_message = JSON_UNQUOTE(JSON_EXTRACT(v_snapshot_json, '$.message'));
    IF COALESCE(v_snapshot_status, 500) <> 200 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', COALESCE(v_snapshot_message, 'Error al analizar presupuesto REQ.'));
        LEAVE sp_main;
    END IF;

    SELECT reqadvertenciapptocompra, reqfuerapptocompra
      INTO v_advertencia, v_fuera
      FROM reqcompras
     WHERE reqcompraid = v_reqcompraid;

    DROP TEMPORARY TABLE IF EXISTS tmp_req_firmantes;
    CREATE TEMPORARY TABLE tmp_req_firmantes (
        firmanteusuarioid INT NOT NULL,
        firmanteorden INT NOT NULL,
        firmantetipo VARCHAR(20) NOT NULL,
        firmantedefault TINYINT(1) NOT NULL DEFAULT 0,
        firmantefuerapptocompra TINYINT(1) NOT NULL DEFAULT 0,
        firmantemotivoinclusion VARCHAR(50) NULL,
        PRIMARY KEY (firmanteusuarioid)
    );

    SELECT cc.centrocostojefeusuarioid, cc.centrocostojefetecnicoid
      INTO v_default_jefe, v_default_tecnico
      FROM centroscosto cc
     WHERE cc.centrocostoid = v_centrocostoid
     LIMIT 1;

    IF v_default_jefe IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuarios u WHERE u.usuarioid = v_default_jefe AND u.usuarioactivo = 1 AND u.usuariobloqueado = 0 AND u.usuariopermiteaprobreq = 1
    ) THEN
        SELECT COALESCE((
            SELECT api.aprobadorreemplazousuarioid
              FROM aprobadoresperiodoinactividad api
             WHERE api.aprobadorusuarioid = v_default_jefe
               AND api.aprobadorperiodoactivo = 1
               AND CURRENT_DATE() BETWEEN api.aprobadorperiodofechainicio AND api.aprobadorperiodofechafin
             LIMIT 1
        ), v_default_jefe)
          INTO v_reemplazoid;
        INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
        VALUES (COALESCE(v_reemplazoid, v_default_jefe), 10, 'JEF_CC', 1, 0, NULL)
        ON DUPLICATE KEY UPDATE firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden));
    END IF;

    SET v_reemplazoid = NULL;
    IF v_default_tecnico IS NOT NULL AND EXISTS (
        SELECT 1 FROM usuarios u WHERE u.usuarioid = v_default_tecnico AND u.usuarioactivo = 1 AND u.usuariobloqueado = 0 AND u.usuariopermiteaprobreq = 1
    ) THEN
        SELECT COALESCE((
            SELECT api.aprobadorreemplazousuarioid
              FROM aprobadoresperiodoinactividad api
             WHERE api.aprobadorusuarioid = v_default_tecnico
               AND api.aprobadorperiodoactivo = 1
               AND CURRENT_DATE() BETWEEN api.aprobadorperiodofechainicio AND api.aprobadorperiodofechafin
             LIMIT 1
        ), v_default_tecnico)
          INTO v_reemplazoid;
        INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
        VALUES (COALESCE(v_reemplazoid, v_default_tecnico), 20, 'JEF_TEC', 1, 0, NULL)
        ON DUPLICATE KEY UPDATE firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden));
    END IF;

    SET v_idx = 0;
    WHILE v_idx < v_firmantes_len DO
        SET v_manual_usuarioid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.firmantesManual[', v_idx, '].usuarioid'))), ''), 'null') AS SIGNED);
        SET v_manual_orden = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$.firmantesManual[', v_idx, '].firmanteorden'))), ''), 'null') AS SIGNED);
        IF v_manual_usuarioid IS NOT NULL
           AND v_manual_usuarioid > 0
           AND EXISTS (
               SELECT 1
                 FROM usuarios u
                WHERE u.usuarioid = v_manual_usuarioid
                  AND u.usuarioactivo = 1
                  AND u.usuariobloqueado = 0
                  AND u.usuariopermiteaprobreq = 1
           ) THEN
            SELECT COALESCE((
                SELECT api.aprobadorreemplazousuarioid
                  FROM aprobadoresperiodoinactividad api
                 WHERE api.aprobadorusuarioid = v_manual_usuarioid
                   AND api.aprobadorperiodoactivo = 1
                   AND CURRENT_DATE() BETWEEN api.aprobadorperiodofechainicio AND api.aprobadorperiodofechafin
                 LIMIT 1
            ), v_manual_usuarioid)
              INTO v_reemplazoid;
            INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
            VALUES (COALESCE(v_reemplazoid, v_manual_usuarioid), COALESCE(v_manual_orden, ((v_idx + 1) * 10) + 20), 'MANUAL', 0, 0, NULL)
            ON DUPLICATE KEY UPDATE firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden));
        END IF;
        SET v_idx = v_idx + 1;
    END WHILE;

    IF v_fuera = 1 THEN
        INSERT INTO tmp_req_firmantes (firmanteusuarioid, firmanteorden, firmantetipo, firmantedefault, firmantefuerapptocompra, firmantemotivoinclusion)
        SELECT
            u.usuarioid,
            (1000 + (u.usuarioreqautorizadorfuerapptocompraorden * 10)),
            'FUERA_PPTO',
            0,
            1,
            'REQ_SIN_SALDO_PPTO'
        FROM usuarios u
        WHERE u.usuarioactivo = 1
          AND u.usuariobloqueado = 0
          AND u.usuariopermiteaprobreq = 1
          AND u.usuarioreqautorizadorfuerapptocompra = 1
          AND u.usuarioreqautorizadorfuerapptocompraorden > 0
        ORDER BY u.usuarioreqautorizadorfuerapptocompraorden
        ON DUPLICATE KEY UPDATE
            firmanteorden = LEAST(firmanteorden, VALUES(firmanteorden)),
            firmantefuerapptocompra = GREATEST(firmantefuerapptocompra, VALUES(firmantefuerapptocompra)),
            firmantemotivoinclusion = COALESCE(firmantemotivoinclusion, VALUES(firmantemotivoinclusion));
    END IF;

    INSERT INTO reqcomprasfirmantes (
        reqcompraid,
        firmanteusuarioid,
        firmanteorden,
        firmantetipo,
        firmantedefault,
        firmantefuerapptocompra,
        firmantemotivoinclusion,
        firmanteestado,
        auditcreacionusuarioid,
        auditcreaciondispositivo,
        auditcreacionip
    )
    SELECT
        v_reqcompraid,
        t.firmanteusuarioid,
        ROW_NUMBER() OVER (ORDER BY t.firmanteorden ASC, t.firmanteusuarioid ASC),
        t.firmantetipo,
        t.firmantedefault,
        t.firmantefuerapptocompra,
        t.firmantemotivoinclusion,
        'PND',
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip
    FROM tmp_req_firmantes t;

    SELECT COUNT(*)
      INTO v_resultado_firmantes
      FROM reqcomprasfirmantes
     WHERE reqcompraid = v_reqcompraid;

    IF v_accion = 'reenviar_aprobacion' AND v_resultado_firmantes > 0 THEN
        SELECT rf.firmanteusuarioid
          INTO v_reqaprobadoridpnd
          FROM reqcomprasfirmantes rf
         WHERE rf.reqcompraid = v_reqcompraid
         ORDER BY rf.firmanteorden ASC, rf.reqcomprafirmanteid ASC
         LIMIT 1;
        SET v_estado_final = 'PND';
    ELSE
        SET v_reqaprobadoridpnd = NULL;
        SET v_estado_final = 'BRR';
    END IF;

    UPDATE reqcompras
       SET reqcompraestadoid = v_estado_final,
           reqaprobadoridpnd = v_reqaprobadoridpnd,
           reqcompranettotal = v_totalreq,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    IF NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.comentario')), ''), 'null') IS NOT NULL THEN
        INSERT INTO reqcomprascomentarios (
            reqcompraid,
            usuarioid,
            reqcomentariotipo,
            reqcomentariotxt,
            auditcreacionusuarioid,
            auditcreaciondispositivo,
            auditcreacionip
        ) VALUES (
            v_reqcompraid,
            p_in_usuarioid,
            'INFO',
            JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.comentario')),
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        );
    END IF;

    INSERT INTO reqcompraslog (
        reqcompraid,
        logusuarioid,
        logdispositivo,
        logip,
        logtipo,
        logparamjson,
        logregbkpjson
    ) VALUES (
        v_reqcompraid,
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip,
        'UPD',
        p_in_json,
        v_prev_json
    );

    DROP TEMPORARY TABLE IF EXISTS tmp_req_firmantes;

    SET p_out_json = JSON_OBJECT(
        'status', 200,
        'message', CASE
            WHEN v_estado_final = 'PND' THEN 'REQ actualizado y reenviado a aprobacion.'
            ELSE 'REQ actualizado correctamente.'
        END,
        'id', v_reqcompraid,
        'estado', v_estado_final,
        'reqaprobadoridpnd', v_reqaprobadoridpnd,
        'advertenciapptocompra', v_advertencia,
        'fuerapptocompra', v_fuera
    );
END//

DROP PROCEDURE IF EXISTS sp_compras_req_tomar_edicion//
CREATE PROCEDURE sp_compras_req_tomar_edicion (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_creador INT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_apr INT DEFAULT 0;
    DECLARE v_prev_json JSON DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al tomar edicion del REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT r.auditcreacionusuarioid, r.reqcompraestadoid,
           JSON_OBJECT('reqcompraid', r.reqcompraid, 'estado', r.reqcompraestadoid, 'reqaprobadoridpnd', r.reqaprobadoridpnd)
      INTO v_creador, v_estado, v_prev_json
      FROM reqcompras r
     WHERE r.reqcompraid = v_reqcompraid
       AND r.reqcompravig = 1
     LIMIT 1;

    IF v_estado IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;
    IF v_creador <> p_in_usuarioid THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Solo el creador puede tomar edicion del REQ.');
        LEAVE sp_main;
    END IF;
    IF v_estado = 'EDT' THEN
        SET p_out_json = JSON_OBJECT('status', 200, 'message', 'REQ retomado en edicion.', 'id', v_reqcompraid, 'estado', 'EDT');
        LEAVE sp_main;
    END IF;
    IF v_estado <> 'PND' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Solo los REQ pendientes pueden pasar a edicion.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_apr
      FROM reqcomprasfirmantes rf
     WHERE rf.reqcompraid = v_reqcompraid
       AND rf.firmanteestado = 'APR';

    IF v_apr > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ ya tiene aprobaciones efectivas y no puede pasar a edicion.');
        LEAVE sp_main;
    END IF;

    UPDATE reqcompras
       SET reqcompraestadoid = 'EDT',
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    INSERT INTO reqcompraslog (
        reqcompraid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'EDT', p_in_json, v_prev_json
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'REQ tomado en edicion.', 'id', v_reqcompraid, 'estado', 'EDT');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_cancelar_edicion//
CREATE PROCEDURE sp_compras_req_cancelar_edicion (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_creador INT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_apr INT DEFAULT 0;
    DECLARE v_reqaprobadoridpnd INT DEFAULT NULL;
    DECLARE v_prev_json JSON DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al cancelar edicion del REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT r.auditcreacionusuarioid, r.reqcompraestadoid,
           JSON_OBJECT('reqcompraid', r.reqcompraid, 'estado', r.reqcompraestadoid, 'reqaprobadoridpnd', r.reqaprobadoridpnd)
      INTO v_creador, v_estado, v_prev_json
      FROM reqcompras r
     WHERE r.reqcompraid = v_reqcompraid
       AND r.reqcompravig = 1
     LIMIT 1;

    IF v_estado IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;
    IF v_creador <> p_in_usuarioid THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Solo el creador puede cancelar la edicion.');
        LEAVE sp_main;
    END IF;
    IF v_estado <> 'EDT' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no esta en edicion.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_apr
      FROM reqcomprasfirmantes rf
     WHERE rf.reqcompraid = v_reqcompraid
       AND rf.firmanteestado = 'APR';

    IF v_apr > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ ya tiene aprobaciones efectivas y no puede cancelar edicion.');
        LEAVE sp_main;
    END IF;

    SELECT firmanteusuarioid
      INTO v_reqaprobadoridpnd
      FROM reqcomprasfirmantes
     WHERE reqcompraid = v_reqcompraid
     ORDER BY firmanteorden ASC, reqcomprafirmanteid ASC
     LIMIT 1;

    UPDATE reqcompras
       SET reqcompraestadoid = 'PND',
           reqaprobadoridpnd = v_reqaprobadoridpnd,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    INSERT INTO reqcompraslog (
        reqcompraid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'EDT', p_in_json, v_prev_json
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Edicion cancelada.', 'id', v_reqcompraid, 'estado', 'PND');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_liberar_edicion//
CREATE PROCEDURE sp_compras_req_liberar_edicion (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_motivo VARCHAR(255) DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_creador INT DEFAULT NULL;
    DECLARE v_reqaprobadoridpnd INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al liberar edicion del REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    SET v_motivo = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.motivo')), ''), 'null');

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_motivo IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'motivo es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT reqcompraestadoid, auditcreacionusuarioid
      INTO v_estado, v_creador
      FROM reqcompras
     WHERE reqcompraid = v_reqcompraid
       AND reqcompravig = 1
     LIMIT 1;

    IF v_estado IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;
    IF v_estado <> 'EDT' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no esta en edicion.');
        LEAVE sp_main;
    END IF;
    IF v_creador <> p_in_usuarioid THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Solo el creador puede liberar la edicion en este corte.');
        LEAVE sp_main;
    END IF;

    SELECT firmanteusuarioid
      INTO v_reqaprobadoridpnd
      FROM reqcomprasfirmantes
     WHERE reqcompraid = v_reqcompraid
     ORDER BY firmanteorden ASC, reqcomprafirmanteid ASC
     LIMIT 1;

    UPDATE reqcompras
       SET reqcompraestadoid = 'PND',
           reqaprobadoridpnd = v_reqaprobadoridpnd,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    INSERT INTO reqcomprascomentarios (
        reqcompraid, usuarioid, reqcomentariotipo, reqcomentariotxt,
        auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, 'INFO', v_motivo,
        p_in_usuarioid, p_in_dispositivo, p_in_ip
    );

    INSERT INTO reqcompraslog (
        reqcompraid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'EDT', p_in_json, NULL
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Edicion liberada.', 'id', v_reqcompraid, 'estado', 'PND');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_aprobar//
CREATE PROCEDURE sp_compras_req_aprobar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_comentario TEXT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_aprobadorpnd INT DEFAULT NULL;
    DECLARE v_firmanteid INT DEFAULT NULL;
    DECLARE v_siguiente INT DEFAULT NULL;
    DECLARE v_prev_json JSON DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al aprobar REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    SET v_comentario = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.comentario')), ''), 'null');

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF NOT EXISTS (
        SELECT 1
          FROM usuarios u
         WHERE u.usuarioid = p_in_usuarioid
           AND u.usuarioactivo = 1
           AND u.usuariobloqueado = 0
           AND u.usuariopermiteaprobreq = 1
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El usuario no puede aprobar REQ.');
        LEAVE sp_main;
    END IF;

    SELECT r.reqcompraestadoid, r.reqaprobadoridpnd,
           JSON_OBJECT('reqcompraid', r.reqcompraid, 'estado', r.reqcompraestadoid, 'reqaprobadoridpnd', r.reqaprobadoridpnd)
      INTO v_estado, v_aprobadorpnd, v_prev_json
      FROM reqcompras r
     WHERE r.reqcompraid = v_reqcompraid
       AND r.reqcompravig = 1
     LIMIT 1;

    IF v_estado IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;
    IF v_estado = 'EDT' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ esta en edicion y no puede aprobarse.');
        LEAVE sp_main;
    END IF;
    IF v_estado <> 'PND' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Solo se pueden aprobar REQ pendientes.');
        LEAVE sp_main;
    END IF;
    IF v_aprobadorpnd <> p_in_usuarioid THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El usuario no es el aprobador pendiente.');
        LEAVE sp_main;
    END IF;

    SELECT rf.reqcomprafirmanteid
      INTO v_firmanteid
      FROM reqcomprasfirmantes rf
     WHERE rf.reqcompraid = v_reqcompraid
       AND rf.firmanteusuarioid = p_in_usuarioid
       AND rf.firmanteestado = 'PND'
     ORDER BY rf.firmanteorden ASC
     LIMIT 1;

    IF v_firmanteid IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No existe firmante pendiente para el usuario.');
        LEAVE sp_main;
    END IF;

    UPDATE reqcomprasfirmantes
       SET firmanteestado = 'APR',
           firmantefechahora = NOW(),
           firmantecomentario = v_comentario,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcomprafirmanteid = v_firmanteid;

    IF v_comentario IS NOT NULL THEN
        INSERT INTO reqcomprascomentarios (
            reqcompraid, usuarioid, reqcomentariotipo, reqcomentariotxt,
            auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip
        ) VALUES (
            v_reqcompraid, p_in_usuarioid, 'APR', v_comentario,
            p_in_usuarioid, p_in_dispositivo, p_in_ip
        );
    END IF;

    SELECT rf.firmanteusuarioid
      INTO v_siguiente
      FROM reqcomprasfirmantes rf
     WHERE rf.reqcompraid = v_reqcompraid
       AND rf.firmanteestado = 'PND'
     ORDER BY rf.firmanteorden ASC, rf.reqcomprafirmanteid ASC
     LIMIT 1;

    IF v_siguiente IS NULL THEN
        UPDATE reqcompras
           SET reqcompraestadoid = 'APR',
               reqaprobadoridpnd = NULL,
               reqaprobacionfecha = CURRENT_DATE(),
               auditedicionusuarioid = p_in_usuarioid,
               auditediciondispositivo = p_in_dispositivo,
               auditedicionip = p_in_ip
         WHERE reqcompraid = v_reqcompraid;

        INSERT INTO reqaprobados (
            reqcompradetid,
            reqcompraid,
            invitemid,
            reqaprobadoitemcod,
            reqaprobadoitemdsc,
            invunidmedid,
            reqaprobadocantidadreq,
            reqaprobadocantidadpendiente,
            reqaprobadocantidadcomprada,
            reqaprobadocantidadanulada,
            reqaprobadoprecioneto,
            reqaprobadoestado,
            reqaprobadofecha,
            auditcreacionusuarioid,
            auditcreaciondispositivo,
            auditcreacionip
        )
        SELECT
            d.reqcompradetid,
            d.reqcompraid,
            d.invitemid,
            d.reqcompradetitemcod,
            d.reqcompradetdsc,
            d.invunidmedid,
            d.reqcompradetcantidad,
            d.reqcompradetcantidad,
            0,
            0,
            d.reqcompradetprecioneto,
            1,
            CURRENT_DATE(),
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        FROM reqcomprasdetalle d
        WHERE d.reqcompraid = v_reqcompraid
          AND NOT EXISTS (
              SELECT 1
                FROM reqaprobados ra
               WHERE ra.reqcompradetid = d.reqcompradetid
          );

        SET p_out_json = JSON_OBJECT('status', 200, 'message', 'REQ aprobado completamente.', 'id', v_reqcompraid, 'estado', 'APR', 'reqaprobadoridpnd', NULL, 'aprobadoCompleto', 1);
    ELSE
        UPDATE reqcompras
           SET reqaprobadoridpnd = v_siguiente,
               auditedicionusuarioid = p_in_usuarioid,
               auditediciondispositivo = p_in_dispositivo,
               auditedicionip = p_in_ip
         WHERE reqcompraid = v_reqcompraid;

        SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Aprobacion registrada.', 'id', v_reqcompraid, 'estado', 'PND', 'reqaprobadoridpnd', v_siguiente, 'aprobadoCompleto', 0);
    END IF;

    INSERT INTO reqcompraslog (
        reqcompraid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'APR', p_in_json, v_prev_json
    );
END//

DROP PROCEDURE IF EXISTS sp_compras_req_rechazar//
CREATE PROCEDURE sp_compras_req_rechazar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_comentario TEXT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_aprobadorpnd INT DEFAULT NULL;
    DECLARE v_firmanteid INT DEFAULT NULL;
    DECLARE v_prev_json JSON DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al rechazar REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    SET v_comentario = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.comentario')), ''), 'null');

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_comentario IS NULL OR CHAR_LENGTH(TRIM(v_comentario)) <= 10 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El comentario de rechazo es obligatorio y debe tener mas de 10 caracteres.');
        LEAVE sp_main;
    END IF;

    SELECT r.reqcompraestadoid, r.reqaprobadoridpnd,
           JSON_OBJECT('reqcompraid', r.reqcompraid, 'estado', r.reqcompraestadoid, 'reqaprobadoridpnd', r.reqaprobadoridpnd)
      INTO v_estado, v_aprobadorpnd, v_prev_json
      FROM reqcompras r
     WHERE r.reqcompraid = v_reqcompraid
       AND r.reqcompravig = 1
     LIMIT 1;

    IF v_estado IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;
    IF v_estado = 'EDT' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ esta en edicion y no puede rechazarse.');
        LEAVE sp_main;
    END IF;
    IF v_estado <> 'PND' OR v_aprobadorpnd <> p_in_usuarioid THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El usuario no puede rechazar este REQ.');
        LEAVE sp_main;
    END IF;

    SELECT rf.reqcomprafirmanteid
      INTO v_firmanteid
      FROM reqcomprasfirmantes rf
     WHERE rf.reqcompraid = v_reqcompraid
       AND rf.firmanteusuarioid = p_in_usuarioid
       AND rf.firmanteestado = 'PND'
     ORDER BY rf.firmanteorden ASC
     LIMIT 1;

    IF v_firmanteid IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No existe firmante pendiente para el usuario.');
        LEAVE sp_main;
    END IF;

    UPDATE reqcomprasfirmantes
       SET firmanteestado = 'RCH',
           firmantefechahora = NOW(),
           firmantecomentario = v_comentario,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcomprafirmanteid = v_firmanteid;

    UPDATE reqcompras
       SET reqcompraestadoid = 'RCH',
           reqaprobadoridpnd = NULL,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    INSERT INTO reqcomprascomentarios (
        reqcompraid, usuarioid, reqcomentariotipo, reqcomentariotxt,
        auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, 'RCH', v_comentario,
        p_in_usuarioid, p_in_dispositivo, p_in_ip
    );

    INSERT INTO reqcompraslog (
        reqcompraid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'RCH', p_in_json, v_prev_json
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'REQ rechazado correctamente.', 'id', v_reqcompraid, 'estado', 'RCH');
END//

DROP PROCEDURE IF EXISTS sp_compras_req_anular//
CREATE PROCEDURE sp_compras_req_anular (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_reqcompraid INT DEFAULT NULL;
    DECLARE v_comentario TEXT DEFAULT NULL;
    DECLARE v_estado VARCHAR(20) DEFAULT NULL;
    DECLARE v_creador INT DEFAULT NULL;
    DECLARE v_apr INT DEFAULT 0;
    DECLARE v_prev_json JSON DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_out_json = JSON_OBJECT('status', 500, 'message', 'Error al anular REQ.');
    END;

    SET v_reqcompraid = CAST(NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.reqcompraid')), ''), 'null') AS SIGNED);
    SET v_comentario = NULLIF(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.comentario')), ''), 'null');

    IF v_reqcompraid IS NULL OR v_reqcompraid <= 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'reqcompraid es obligatorio.');
        LEAVE sp_main;
    END IF;
    IF v_comentario IS NULL OR CHAR_LENGTH(TRIM(v_comentario)) = 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El comentario de anulacion es obligatorio.');
        LEAVE sp_main;
    END IF;

    SELECT r.reqcompraestadoid, r.auditcreacionusuarioid,
           JSON_OBJECT('reqcompraid', r.reqcompraid, 'estado', r.reqcompraestadoid, 'reqaprobadoridpnd', r.reqaprobadoridpnd)
      INTO v_estado, v_creador, v_prev_json
      FROM reqcompras r
     WHERE r.reqcompraid = v_reqcompraid
       AND r.reqcompravig = 1
     LIMIT 1;

    IF v_estado IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ no existe o no esta vigente.');
        LEAVE sp_main;
    END IF;
    IF v_creador <> p_in_usuarioid THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Solo el creador puede anular el REQ.');
        LEAVE sp_main;
    END IF;
    IF v_estado NOT IN ('BRR', 'RCH', 'PND', 'EDT') THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El estado actual no permite anulacion.');
        LEAVE sp_main;
    END IF;

    SELECT COUNT(*)
      INTO v_apr
      FROM reqcomprasfirmantes rf
     WHERE rf.reqcompraid = v_reqcompraid
       AND rf.firmanteestado = 'APR';

    IF v_apr > 0 THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El REQ ya tiene aprobaciones efectivas y no puede anularse.');
        LEAVE sp_main;
    END IF;

    UPDATE reqcompras
       SET reqcompraestadoid = 'ANL',
           reqaprobadoridpnd = NULL,
           auditedicionusuarioid = p_in_usuarioid,
           auditediciondispositivo = p_in_dispositivo,
           auditedicionip = p_in_ip
     WHERE reqcompraid = v_reqcompraid;

    INSERT INTO reqcomprascomentarios (
        reqcompraid, usuarioid, reqcomentariotipo, reqcomentariotxt,
        auditcreacionusuarioid, auditcreaciondispositivo, auditcreacionip
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, 'ANL', v_comentario,
        p_in_usuarioid, p_in_dispositivo, p_in_ip
    );

    INSERT INTO reqcompraslog (
        reqcompraid, logusuarioid, logdispositivo, logip, logtipo, logparamjson, logregbkpjson
    ) VALUES (
        v_reqcompraid, p_in_usuarioid, p_in_dispositivo, p_in_ip, 'ANL', p_in_json, v_prev_json
    );

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'REQ anulado correctamente.', 'id', v_reqcompraid, 'estado', 'ANL');
END//

DELIMITER ;
