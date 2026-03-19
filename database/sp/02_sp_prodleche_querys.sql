-- sp consulta diara de producción de leche por fundo entre fechas
DELIMITER //
DROP PROCEDURE IF EXISTS sp_prodleche_planta_consulta_diaria_por_fechas//
CREATE PROCEDURE sp_prodleche_planta_consulta_diaria_por_fechas (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener un array JSON con los siguientes campos:
    --  - fundoid: INT (opcional)
    --  - fecha_inicio: DATE (opcional)
    --  - fecha_fin: DATE (opcional)

    DECLARE v_fundoid INT DEFAULT NULL;
    DECLARE v_fecha_inicio DATE DEFAULT NULL;
    DECLARE v_fecha_fin DATE DEFAULT NULL;

    SET v_fundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid'));
    SET v_fecha_inicio = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fecha_inicio')), '1900-01-01');
    SET v_fecha_fin = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fecha_fin')), now());

    SELECT 
        prodlecheid,
        fundoid,
        fundos.fundonombre,
        DATE_FORMAT(prodlechefecha, '%Y-%m-%d') as prodlechefecha,
        prodlecheventatotlitros,
        prodlecheventatotvacas,
        prodlecheventalitrosxvaca
    FROM prodleche
    INNER JOIN fundos USING (fundoid)
    WHERE (v_fundoid IS NULL OR v_fundoid = '' OR fundoid = v_fundoid)
        AND (v_fecha_inicio IS NULL OR v_fecha_inicio = '' OR prodlechefecha >= v_fecha_inicio)
        AND (v_fecha_fin IS NULL OR v_fecha_fin = '' OR prodlechefecha <= v_fecha_fin)
    ORDER BY prodlechefecha ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

-- sp para listar los registros de producción de leche por planta por día para un año-mes específico
-- Agrupar por día
DROP PROCEDURE IF EXISTS sp_prodleche_planta_consulta_diaria_por_ames//
CREATE PROCEDURE sp_prodleche_planta_consulta_diaria_por_ames (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener un array JSON con los siguientes campos:
    --  - fundoid: INT (opcional)
    --  - anio: INT (obligatorio)
    --  - mes: INT (obligatorio)

    DECLARE v_fundoid INT DEFAULT NULL;
    DECLARE v_anio INT DEFAULT NULL;
    DECLARE v_mes INT DEFAULT NULL;
    DECLARE v_dias_en_mes INT;
    DECLARE v_dia INT DEFAULT 1;

    SET v_fundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid'));
    SET v_anio = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.anio'));
    SET v_mes = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.mes'));

    -- Valida que anio y mes no sean nulos
    IF v_anio IS NULL OR v_mes IS NULL THEN
        SET p_out_json = JSON_OBJECT('status', 400, 'message', 'El campo año y mes son obligatorios');
        LEAVE sp_main;
    END IF;
    -- Para el anio y mes especificados, crear tabla temporal con los días del mes
    -- Esta tabla temporal se usará para hacer un LEFT JOIN con la tabla prodleche
    SET v_dias_en_mes = DAY(LAST_DAY(STR_TO_DATE(CONCAT(v_anio, '-', v_mes, '-01'), '%Y-%m-%d')));
    -- Crear tabla temporal de días
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_dias_mes (dia INT);
    TRUNCATE TABLE temp_dias_mes;
    WHILE v_dia <= v_dias_en_mes DO
        INSERT INTO temp_dias_mes (dia) VALUES (v_dia);
        SET v_dia = v_dia + 1;
    END WHILE;

    SELECT 
        dia,
        fundoid,
        fundos.fundonombre,
        DATE_FORMAT(prodlechefecha, '%Y-%m-%d') as prodlechefecha,
        SUM(prodlecheventatotlitros) as prodlecheventatotlitros,
        ROUND(AVG(prodlecheventatotvacas),0) as prodlecheventatotvacas,
        SUM(prodlecheventatotlitros)/ AVG(prodlecheventatotvacas) as prodlecheventalitrosxvaca,
        reporteorden
    FROM temp_dias_mes
    LEFT JOIN prodleche ON DAY(prodlechefecha) = temp_dias_mes.dia
    INNER JOIN fundos USING (fundoid)
    WHERE (v_fundoid IS NULL OR v_fundoid = '' OR fundoid = v_fundoid)
        AND YEAR(prodlechefecha) = v_anio
        AND MONTH(prodlechefecha) = v_mes
        AND prodlechestatus <> 'ANL'
    GROUP BY dia, fundoid, fundos.fundonombre, prodlechefecha, reporteorden
    ORDER BY reporteorden ASC, prodlechefecha ASC;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

-- Consulta de producción de leche planta por temporada. Resultado agrupado por año-mes
DROP PROCEDURE IF EXISTS sp_prodleche_planta_consulta_por_temporada_ames//
CREATE PROCEDURE sp_prodleche_planta_consulta_por_temporada_ames (
    IN  p_in_json JSON,
    IN  p_in_usuarioid INT,
    IN  p_in_dispositivo VARCHAR(50),
    IN  p_in_ip VARCHAR(50),
    OUT p_out_json JSON
)
sp_main: BEGIN
    -- p_in_json debe contener un array JSON con los siguientes campos:
    --  - fundoid: INT (opcional)
    --  - temporadaid: INT (obligatorio)

    DECLARE v_fundoid INT DEFAULT NULL;
    DECLARE v_temporadainicio DATE;
    DECLARE v_temporadafin DATE;

    SET v_fundoid = JSON_UNQUOTE(JSON_EXTRACT(p_in_json, '$.fundoid'));

    -- Obtener fechas de la temporada. Solo existe una temporada activa por tipo codigo
    SELECT temporadainicio, temporadafin INTO v_temporadainicio, v_temporadafin
    FROM temporadas
    WHERE temporadatipocodigo = 'LECHE'
        AND temporadaactivo = 1;

    SELECT 
        YEAR(prodlechefecha) AS anio,
        MONTH(prodlechefecha) AS mes,
        SUM(prodlecheventatotlitros) AS total_litros,
        ROUND(AVG(prodlecheventatotvacas), 0) AS total_vacas,
        ROUND(AVG(prodlecheventalitrosxvaca), 2) AS promedio_litros_x_vaca
    FROM prodleche
    WHERE (v_fundoid IS NULL OR v_fundoid = '' OR fundoid = v_fundoid)
        AND prodlechefecha BETWEEN v_temporadainicio AND v_temporadafin
    GROUP BY anio, mes
    ORDER BY anio, mes;

    SET p_out_json = JSON_OBJECT('status', 200, 'message', 'OK');
END//

DELIMITER ;