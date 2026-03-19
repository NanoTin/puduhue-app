-- Crear perfil ROOT usando SP (status esperado 200)
-- Init pwd: "Pdh202511.," (bcrypt hash generado)
SET @p_out_json = NULL;
CALL sp_perfiles_insertar(
  JSON_OBJECT(
    'perfildesc', 'Perfil ROOT',
    'perfilesroot', 1,
    'perfilesadmin', 0,
    'perfilactivo', 1
  ),
  0,              -- p_in_usuarioid (system)
  'system',       -- p_in_dispositivo
  '127.0.0.1',    -- p_in_ip
  @p_out_json
);

-- Seed usuario ROOT (bcrypt password: Pdh202511.,)
INSERT INTO usuarios (
    usuariocod,
    usuariorut,
    usuarionombre,
    usuariopwdhash,
    usuarioemail,
    usuariocelular,
    perfilid,
    empresaiddefault,
    usuarioesroot,
    usuarioesadmin,
    usuariobloqueado,
    usuariobloqueadodesc,
    intentosfallidos,
    usuarioultimologin,
    usuarioultimopwdcambio,
    usuariopwdproxvenc,
    usuarioapikeyhash,
    usuarioapikeyactiva,
    usuarioapikeyfechagen,
    usuarioapikeyultuso,
    usuarioapikeyipultuso,
    usuarioactivo,
    auditcreacionusuarioid,
    auditcreaciondispositivo,
    auditcreacionip,
    auditcreacionfechahora,
    auditedicionusuarioid,
    auditediciondispositivo,
    auditedicionip,
    auditedicionfechahora
) VALUES (
    'root',
    '99999999-9',
    'ROOT SYSTEM',
    '$2y$10$aqNmJt9HXNq6SYl.xgHlGOCIjx1w4BXTeCNxiinj9Bgl2JB61qCmy',
    'root@puduhue.local',
    '000000000',
    1,
    1,
    1,
    1,
    0,
    '',
    0,
    NOW(),
    NOW(),
    NOW(),
    '',
    0,
    NOW(),
    NOW(),
    '',
    1,
    '0',
    'system',
    '127.0.0.1',
    NOW(),
    '0',
    'system',
    '127.0.0.1',
    NOW()
);
