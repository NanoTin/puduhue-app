-- Existirá un sp que realice la acción de INS/UPD en la tabla proylechediariaconsolidada al cargar desde Excel
-- Si al cargar el archivo Excel no existe el registro, se inserta, si ya existe, se actualiza
DELIMITER //
DROP PROCEDURE IF EXISTS sp_proylechediariaconsolidada_carga_masiva//
CREATE PROCEDURE sp_proylechediariaconsolidada_carga_masiva (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener un array JSON con los siguientes campos:
    --  - proylechefecha: DATE (obligatorio)
    --  - proylecheventatotlitros: INT (obligatorio)
    --  - proylecheventatotvacas: INT (obligatorio)
    --  - proylecheventatotltsxvaca: DECIMAL(10,2) (obligatorio)
    -- Se debe recorrer el array JSON y por cada registro, realizar un INS/UPD en la tabla proylechediariaconsolidada
    DECLARE v_proylechefecha DATE;
    DECLARE v_proylecheventatotlitros INT;
    DECLARE v_proylecheventatotvacas INT;
    DECLARE v_proylecheventatotltsxvaca DECIMAL(10,2);
    DECLARE v_exists INT;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_total INT DEFAULT JSON_LENGTH(p_in_json);

    WHILE v_index < v_total DO
        SET v_proylechefecha = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$[', v_index, '].proylechefecha')));
        SET v_proylecheventatotlitros = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$[', v_index, '].proylecheventatotlitros')));
        SET v_proylecheventatotvacas = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$[', v_index, '].proylecheventatotvacas')));
        SET v_proylecheventatotltsxvaca = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, CONCAT('$[', v_index, '].proylecheventatotltsxvaca')));

        -- Verificar si el registro ya existe
        SELECT COUNT(*) INTO v_exists
        FROM proylechediariaconsolidada
        WHERE proylechefecha = v_proylechefecha;

        IF v_exists > 0 THEN
            -- Actualizar registro existente
            UPDATE proylechediariaconsolidada
            SET 
                proylecheventatotlitros = v_proylecheventatotlitros,
                proylecheventatotvacas = v_proylecheventatotvacas,
                proylecheventatotltsxvaca = v_proylecheventatotltsxvaca,
                auditedicionusuarioid = p_in_usuarioid,
                auditediciondispositivo = p_in_dispositivo,
                auditedicionip = p_in_ip
            WHERE proylechefecha = v_proylechefecha;
        ELSE
            -- Insertar nuevo registro
            INSERT INTO proylechediariaconsolidada (
                proylechefecha,
                proylecheventatotlitros,
                proylecheventatotvacas,
                proylecheventatotltsxvaca,
                auditcreacionusuarioid,
                auditcreaciondispositivo,
                auditcreacionip
            ) VALUES (
                v_proylechefecha,
                v_proylecheventatotlitros,
                v_proylecheventatotvacas,
                v_proylecheventatotltsxvaca,
                p_in_usuarioid,
                p_in_dispositivo,
                p_in_ip
            );
        END IF;
        SET v_index = v_index + 1;
    END WHILE;
    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'Carga masiva completada');        
END//

DELIMITER ;
