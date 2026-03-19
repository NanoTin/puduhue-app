-- SP para carga masiva de produccion de leche desde JSON
DELIMITER //
DROP PROCEDURE IF EXISTS sp_prodleche_carga_masiva//
CREATE PROCEDURE sp_prodleche_carga_masiva (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    DECLARE v_empresaid INT;
    DECLARE v_observacion VARCHAR(100);
    DECLARE v_rows JSON;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_row JSON;

    DECLARE v_fundoid INT;
    DECLARE v_fecha_str VARCHAR(20);
    DECLARE v_fecha DATE;
    DECLARE v_lechetipoid INT;
    DECLARE v_litros DECIMAL(12,2);
    DECLARE v_vacas DECIMAL(12,2);
    DECLARE v_ltsxvaca DECIMAL(12,4);
    DECLARE v_rownum INT;

    DECLARE v_tot_enc INT DEFAULT 0;
    DECLARE v_tot_det INT DEFAULT 0;
    DECLARE v_errors JSON DEFAULT JSON_ARRAY();

    DECLARE v_group_fundoid INT;
    DECLARE v_group_fecha DATE;
    DECLARE v_key VARCHAR(50);
    DECLARE v_erp_estable VARCHAR(50);
    DECLARE v_erp_lote VARCHAR(50);
    DECLARE v_erp_bodega VARCHAR(50);
    DECLARE v_erp_categ VARCHAR(50);
    DECLARE v_fundo_count INT;
    DECLARE v_existing_id INT;
    DECLARE v_total_litros DECIMAL(12,2);
    DECLARE v_total_vacas DECIMAL(12,2);
    DECLARE v_venta_litros DECIMAL(12,2);
    DECLARE v_venta_vacas DECIMAL(12,2);
    DECLARE v_venta_lxvaca DECIMAL(12,4);
    DECLARE v_prodleche_id INT;
    DECLARE v_det_rows INT;

    DECLARE done INT DEFAULT 0;

    DECLARE cur_groups CURSOR FOR
        SELECT fundoid, fecha
        FROM tmp_prodleche_rows
        GROUP BY fundoid, fecha;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    IF p_in_json IS NULL OR JSON_TYPE(p_in_json) = 'NULL' THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'p_in_json is required', 'totEncInsertados', 0, 'totDetInsertados', 0, 'errores', JSON_ARRAY());
        LEAVE sp_main;
    END IF;

    SET v_empresaid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.empresaid')), '') AS SIGNED);
    SET v_observacion = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.observacion'));
    SET v_rows = JSON_EXTRACT(p_in_json, '$.rows');

    IF v_empresaid IS NULL OR v_empresaid = 0 THEN
        SET p_out_json = JSON_OBJECT(
            'status', 400,
            'message', 'Empresaid invalido.',
            'totEncInsertados', 0,
            'totDetInsertados', 0,
            'errores', JSON_ARRAY(JSON_OBJECT('key', 'empresaid', 'mensaje', 'Empresaid invalido.'))
        );
        LEAVE sp_main;
    END IF;

    IF v_rows IS NULL OR JSON_LENGTH(v_rows) = 0 THEN
        SET p_out_json = JSON_OBJECT(
            'status', 400,
            'message', 'No hay filas para procesar.',
            'totEncInsertados', 0,
            'totDetInsertados', 0,
            'errores', JSON_ARRAY(JSON_OBJECT('key', 'rows', 'mensaje', 'El archivo no contiene registros.'))
        );
        LEAVE sp_main;
    END IF;

    CREATE TEMPORARY TABLE tmp_prodleche_rows (
        row_index INT NOT NULL,
        fundoid INT NULL,
        fecha DATE NULL,
        lechetipoid INT NULL,
        litros DECIMAL(12,2) NULL,
        vacas DECIMAL(12,2) NULL,
        lts_x_vaca DECIMAL(12,4) NULL
    );

    SET v_total = JSON_LENGTH(v_rows);
    read_rows: WHILE v_index < v_total DO
        SET v_row = JSON_EXTRACT(v_rows, CONCAT('$[', v_index, ']'));
        SET v_rownum = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_row, '$.row')), '') AS SIGNED);
        SET v_fundoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_row, '$.fundoid')), '') AS SIGNED);
        SET v_fecha_str = JSON_UNQUOTE(JSON_EXTRACT(v_row, '$.fecha'));
        SET v_fecha = NULL;
        IF v_fecha_str IS NOT NULL AND v_fecha_str <> '' THEN
            SET v_fecha = STR_TO_DATE(v_fecha_str, '%Y-%m-%d');
        END IF;
        SET v_lechetipoid = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_row, '$.lechetipoid')), '') AS SIGNED);
        SET v_litros = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_row, '$.litros')), '') AS DECIMAL(12,2));
        SET v_vacas = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_row, '$.vacas')), '') AS DECIMAL(12,2));
        SET v_ltsxvaca = CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(v_row, '$.lts_x_vaca')), '') AS DECIMAL(12,4));

        IF v_fundoid IS NULL AND v_fecha IS NULL AND v_lechetipoid IS NULL AND v_litros IS NULL AND v_vacas IS NULL AND v_ltsxvaca IS NULL THEN
            SET v_index = v_index + 1;
            ITERATE read_rows;
        END IF;

        IF v_fundoid IS NULL OR v_fecha IS NULL OR v_lechetipoid IS NULL THEN
            SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', JSON_OBJECT(
                'key', CONCAT('row:', COALESCE(v_rownum, v_index + 1)),
                'mensaje', 'Faltan campos obligatorios (fundoid/fecha/lechetipoid).'
            ));
        ELSE
            INSERT INTO tmp_prodleche_rows (row_index, fundoid, fecha, lechetipoid, litros, vacas, lts_x_vaca)
            VALUES (v_index + 1, v_fundoid, v_fecha, v_lechetipoid, v_litros, v_vacas, v_ltsxvaca);
        END IF;

        SET v_index = v_index + 1;
    END WHILE read_rows;

    IF (SELECT COUNT(*) FROM tmp_prodleche_rows) = 0 THEN
        SET p_out_json = JSON_OBJECT(
            'status', 400,
            'message', 'No hay filas validas para procesar.',
            'totEncInsertados', 0,
            'totDetInsertados', 0,
            'errores', v_errors
        );
        DROP TEMPORARY TABLE IF EXISTS tmp_prodleche_rows;
        LEAVE sp_main;
    END IF;

    OPEN cur_groups;
    read_loop: LOOP
        FETCH cur_groups INTO v_group_fundoid, v_group_fecha;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SET v_key = CONCAT(v_group_fundoid, '|', DATE_FORMAT(v_group_fecha, '%Y-%m-%d'));

        SELECT COUNT(*) INTO v_fundo_count FROM fundos WHERE fundoid = v_group_fundoid;
        IF v_fundo_count = 0 THEN
            SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', JSON_OBJECT(
                'key', v_key,
                'mensaje', 'Fundo no existe.'
            ));
            ITERATE read_loop;
        END IF;

        SELECT
            MAX(erpestablecimientocod),
            MAX(erplotecod),
            MAX(erpleche_invbodegacod),
            MAX(erpleche_invcateganimalcod)
        INTO v_erp_estable, v_erp_lote, v_erp_bodega, v_erp_categ
        FROM fundos
        WHERE fundoid = v_group_fundoid;

        SET v_existing_id = NULL;
        SELECT MIN(prodlecheid) INTO v_existing_id
        FROM prodleche
        WHERE empresaid = v_empresaid
          AND fundoid = v_group_fundoid
          AND DATE(prodlechefecha) = v_group_fecha
          AND prodlechestatus <> 'ANL';

        IF v_existing_id IS NOT NULL THEN
            SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', JSON_OBJECT(
                'key', v_key,
                'mensaje', 'Ya existe un registro para la empresa, fundo y fecha indicados.'
            ));
            ITERATE read_loop;
        END IF;

        SELECT
            COALESCE(SUM(litros), 0),
            COALESCE(SUM(vacas), 0),
            COALESCE(SUM(CASE WHEN lechetipoid = 1 THEN litros ELSE 0 END), 0),
            COALESCE(SUM(CASE WHEN lechetipoid = 1 THEN vacas ELSE 0 END), 0)
        INTO v_total_litros, v_total_vacas, v_venta_litros, v_venta_vacas
        FROM tmp_prodleche_rows
        WHERE fundoid = v_group_fundoid AND fecha = v_group_fecha;

        SET v_venta_lxvaca = IFNULL(v_venta_litros / NULLIF(v_venta_vacas, 0), 0);

        INSERT INTO prodleche (
            prodlechestatus,
            empresaid,
            fundoid,
            prodlechefecha,
            prodlechehoraini,
            prodlechehorafin,
            prodlechehorario,
            pl_erpestablecimientocod,
            pl_erplotecod,
            pl_erpleche_invbodegacod,
            pl_erpleche_invcateganimalcod,
            prodlechetotlitros,
            prodlechetotvacas,
            prodlecheventatotlitros,
            prodlecheventatotvacas,
            prodlecheventalitrosxvaca,
            prodlecheobservacion,
            auditcreacionusuarioid,
            auditcreaciondispositivo,
            auditcreacionip
        ) VALUES (
            'HST',
            v_empresaid,
            v_group_fundoid,
            TIMESTAMP(v_group_fecha, '00:00:00'),
            '12:00:00',
            '12:00:00',
            'PM',
            v_erp_estable,
            v_erp_lote,
            v_erp_bodega,
            v_erp_categ,
            v_total_litros,
            v_total_vacas,
            v_venta_litros,
            v_venta_vacas,
            v_venta_lxvaca,
            COALESCE(NULLIF(v_observacion, ''), 'Carga masiva desde ERP Finnegans'),
            p_in_usuarioid,
            p_in_dispositivo,
            p_in_ip
        );

        SET v_prodleche_id = LAST_INSERT_ID();

        INSERT INTO prodlechedetalle (
            prodlecheid,
            prodlechetipoid,
            pldetlitros,
            pldetvacas,
            pldetlitrosxvaca,
            prodlechecod,
            erpdocumentocod
        )
        SELECT
            v_prodleche_id,
            lechetipoid,
            COALESCE(SUM(litros), 0),
            COALESCE(SUM(vacas), 0),
            COALESCE(AVG(lts_x_vaca), 0),
            NULL,
            NULL
        FROM tmp_prodleche_rows
        WHERE fundoid = v_group_fundoid AND fecha = v_group_fecha
        GROUP BY lechetipoid;

        SET v_det_rows = ROW_COUNT();
        SET v_tot_enc = v_tot_enc + 1;
        SET v_tot_det = v_tot_det + v_det_rows;
    END LOOP;

    CLOSE cur_groups;
    DROP TEMPORARY TABLE IF EXISTS tmp_prodleche_rows;

    SET p_out_json = JSON_OBJECT(
        'status', IF(JSON_LENGTH(v_errors) > 0, 400, 200),
        'message', IF(JSON_LENGTH(v_errors) > 0, 'Carga masiva completada con errores.', 'Produccion de leche cargada exitosamente.'),
        'totEncInsertados', v_tot_enc,
        'totDetInsertados', v_tot_det,
        'errores', v_errors
    );
END//
DELIMITER ;
