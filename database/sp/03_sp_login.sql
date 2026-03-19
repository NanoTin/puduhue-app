DELIMITER //
DROP PROCEDURE IF EXISTS `sp_usuario_login_exitoso`//
DROP PROCEDURE IF EXISTS `sp_usuario_login_fallido`//
DROP PROCEDURE IF EXISTS `sp_usuario_login_obtenerdatos`//

CREATE PROCEDURE `sp_usuario_login_exitoso` (
    IN p_in_usuariocod INT,
    IN p_in_usuarioip VARCHAR(50),
    IN p_in_usuariodispositivo VARCHAR(100)
)   
BEGIN
    UPDATE usuarios
    SET 
        intentosfallidos = 0,
        usuarioultimologin = NOW()
    WHERE usuariocod = p_in_usuariocod;
END//

CREATE PROCEDURE `sp_usuario_login_fallido` (
    IN p_in_usuariocod VARCHAR(12),
    IN p_in_usuarioip VARCHAR(50),
    IN p_in_usuariodispositivo VARCHAR(100)
)
BEGIN
    UPDATE usuarios
    SET 
        intentosfallidos = intentosfallidos + 1
    WHERE usuariocod = p_in_usuariocod;
END//

CREATE PROCEDURE `sp_usuario_login_obtenerdatos` (
    IN p_in_usuariocod VARCHAR(12)
)
BEGIN
    SELECT 
        usuarioid,
        usuariocod,
        usuarionombre,
        usuariopwdhash,
        usuarioactivo,
        intentosfallidos,
        usuarioesroot,
        usuarioesadmin,
        usuariobloqueado,
        empresaiddefault,
        perfilid,
        (select fundoid from usuariosfundos sq where sq.usuarioid = u.usuarioid and sq.ufdefault = 1 limit 1) as fundoiddefault
    FROM usuarios u
    WHERE usuariocod = p_in_usuariocod;
END//
DELIMITER ;