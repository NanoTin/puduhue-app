-- Existirá un sp que realice la acción de INS/UPD en la tabla pptoleche al cargar desde Excel
-- Si al cargar el archivo Excel no existe el registro, se inserta, si ya existe, se actualiza
DELIMITER //
DROP PROCEDURE IF EXISTS sp_pptolechemensual_ins_upd//
CREATE PROCEDURE sp_pptolechemensual_ins_upd (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener un array JSON con los siguientes campos:
    -- pptolecanio, pptolecmes, fundoid, pptoleclitros, pptolecvacas, pptolecltsxvc, pptolecfecha, pptolecdiasdelmes
    -- se debe recorrer el array y por cada elemento, realizar un INS/UPD en la tabla pptolechemensual
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_record JSON;
    SET v_total = JSON_LENGTH(p_in_json);
    WHILE v_index < v_total DO
        SET v_record = JSON_EXTRACT(p_in_json, CONCAT('$[', v_index, ']'));
        -- Verificar si el registro ya existe
        SELECT COUNT(*) INTO v_count
        FROM pptolechemensual
        WHERE pptolecanio = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecanio'))
          AND pptolecmes = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecmes'))
          AND fundoid = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.fundoid'));
        
        IF v_count > 0 THEN
            -- Realizar UPDATE
            UPDATE pptolechemensual
            SET 
                pptoleclitros = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptoleclitros')),
                pptolecvacas = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecvacas')),
                pptolecltsxvc = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecltsxvc')),
                pptolecfecha = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecfecha')),
                pptolecdiasdelmes = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecdiasdelmes')),
                auditedicionusuarioid = p_in_usuarioid,
                auditediciondispositivo = p_in_dispositivo,
                auditedicionip = p_in_ip
            WHERE pptolecanio = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecanio'))
              AND pptolecmes = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecmes'))
              AND fundoid = JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.fundoid'));
        ELSE
            -- Realizar INSERT
            INSERT INTO pptolechemensual (
                pptolecanio,
                pptolecmes,
                fundoid,
                pptoleclitros,
                pptolecvacas,
                pptolecltsxvc,
                pptolecfecha,
                pptolecdiasdelmes,
                auditcreacionusuarioid,
                auditcreaciondispositivo,
                auditcreacionip
            ) VALUES (
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecanio')),
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecmes')),
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.fundoid')),
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptoleclitros')),
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecvacas')),
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecltsxvc')),
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecfecha')),
                JSON_UNQUOTE(JSON_EXTRACT(v_record, '$.pptolecdiasdelmes')),
                p_in_usuarioid,
                p_in_dispositivo,
                p_in_ip);
        END IF;
        SET v_index = v_index + 1;
    END WHILE;
    SET p_out_json = JSON_OBJECT('status', 200,'message', CONCAT('Processed ', v_total, ' records.'));
END//

-- sp para listar los registros de pptolechemensual
DROP PROCEDURE IF EXISTS sp_pptolechemensual_listar//
CREATE PROCEDURE sp_pptolechemensual_listar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json puede contener filtros opcionales: filtroPptolecanio, filtroPptolecmes, filtroFundoid
    -- Declarar variables para filtros
    DECLARE v_filtroPptolecanio INT DEFAULT NULL;
    DECLARE v_filtroPptolecmes INT DEFAULT NULL;
    DECLARE v_filtroFundoid INT DEFAULT NULL;
    -- Extraer filtros del JSON de entrada
    SET v_filtroPptolecanio = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptolecanio')),'null');
    SET v_filtroPptolecmes = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroPptolecmes')),'null');
    SET v_filtroFundoid = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.filtroFundoid')),'null');
    -- Construir la consulta SQL dinámica

    SELECT 
        pptolecanio,
        pptolecmes,
        fundoid,
        fundonombre,
        pptoleclitros,
        pptolecvacas,
        pptolecltsxvc,
        pptolecfecha,
        pptolecdiasdelmes 
    FROM pptolechemensual
    INNER JOIN fundos USING (fundoid)
    WHERE (v_filtroPptolecanio IS NULL OR v_filtroPptolecanio = '' OR pptolecanio = v_filtroPptolecanio)
      AND (v_filtroPptolecmes IS NULL OR v_filtroPptolecmes = '' OR pptolecmes = v_filtroPptolecmes)
      AND (v_filtroFundoid IS NULL OR v_filtroFundoid = '' OR fundoid = v_filtroFundoid)
    ORDER BY pptolecanio, pptolecmes, fundonombre;
    
    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

-- sp para eliminar un registro de pptolechemensual
DROP PROCEDURE IF EXISTS sp_pptolechemensual_eliminar//
CREATE PROCEDURE sp_pptolechemensual_eliminar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener los campos: pptolecanio, pptolecmes, fundoid
    DECLARE v_pptolecanio INT;
    DECLARE v_pptolecmes INT;
    DECLARE v_fundoid INT;
    -- Extraer valores del JSON de entrada
    SET v_pptolecanio = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecanio'));
    SET v_pptolecmes = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecmes'));
    SET v_fundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid'));
    -- Eliminar el registro
    DELETE FROM pptolechemensual
    WHERE pptolecanio = v_pptolecanio
      AND pptolecmes = v_pptolecmes
      AND fundoid = v_fundoid;
    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Record deleted successfully.');
END//

-- sp para crear manualmente un registro en pptolechemensual
DROP PROCEDURE IF EXISTS sp_pptolechemensual_crear//
CREATE PROCEDURE sp_pptolechemensual_crear (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener los campos: pptolecanio, pptolecmes, fundoid, pptoleclitros, pptolecvacas, pptolecltsxvc, pptolecfecha, pptolecdiasdelmes
    -- Validar PK
    IF EXISTS (
        SELECT 1 FROM pptolechemensual
        WHERE pptolecanio = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecanio'))
          AND pptolecmes = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecmes'))
          AND fundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid'))
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'Record already exists with the given primary key.');
        LEAVE sp_main;
    END IF;
    INSERT INTO pptolechemensual (
        pptolecanio,
        pptolecmes,
        fundoid,
        pptoleclitros,
        pptolecvacas,
        pptolecltsxvc,
        pptolecfecha,
        pptolecdiasdelmes,
        auditcreacionusuarioid,
        auditcreaciondispositivo,
        auditcreacionip
    ) VALUES (
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecanio')),
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecmes')),
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid')),
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptoleclitros')),
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecvacas')),
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecltsxvc')),
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecfecha')),
        JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecdiasdelmes')),
        p_in_usuarioid,
        p_in_dispositivo,
        p_in_ip);
    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Record created successfully.');
END//

-- sp para actualizar manualmente un registro en pptolechemensual
DROP PROCEDURE IF EXISTS sp_pptolechemensual_actualizar//
CREATE PROCEDURE sp_pptolechemensual_actualizar (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener los campos: pptolecanio, pptolecmes, fundoid, pptoleclitros, pptolecvacas, pptolecltsxvc, pptolecfecha, pptolecdiasdelmes
    -- Validar existencia del registro
    IF NOT EXISTS (
        SELECT 1 FROM pptolechemensual
        WHERE pptolecanio = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecanio'))
          AND pptolecmes = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecmes'))
          AND fundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid'))
    ) THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'No record found with the given primary key.');
        LEAVE sp_main;
    END IF;
    UPDATE pptolechemensual
    SET 
        pptoleclitros = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptoleclitros')),
        pptolecvacas = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecvacas')),
        pptolecltsxvc = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecltsxvc')),
        pptolecfecha = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecfecha')),
        pptolecdiasdelmes = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecdiasdelmes')),
        auditedicionusuarioid = p_in_usuarioid,
        auditediciondispositivo = p_in_dispositivo,
        auditedicionip = p_in_ip
    WHERE pptolecanio = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecanio'))
      AND pptolecmes = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.pptolecmes'))
      AND fundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid'));
    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Record updated successfully.');
END//

DELIMITER ;