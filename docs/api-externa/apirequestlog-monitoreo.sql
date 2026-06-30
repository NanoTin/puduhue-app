-- Consultas de monitoreo API externa.
-- Ejecutar manualmente en phpMyAdmin sobre la base productiva cuando se requiera revisar actividad.

-- 1) IPs con mas errores recientes.
SELECT
  iporigen,
  responsecode,
  COUNT(*) AS total,
  MIN(fechahora) AS primera_vez,
  MAX(fechahora) AS ultima_vez
FROM apirequestlog
WHERE fechahora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND responsecode IN (401, 403, 404, 405, 500)
GROUP BY iporigen, responsecode
HAVING COUNT(*) >= 10
ORDER BY total DESC, ultima_vez DESC;

-- 2) Uso de tokens desde IPs distintas a la ultima registrada en usuariosapitokens.
SELECT
  l.usuarioapitokenid,
  t.tokennombre,
  t.tokenprefijo,
  t.tokenipultuso,
  l.iporigen,
  COUNT(*) AS total,
  MIN(l.fechahora) AS primera_vez,
  MAX(l.fechahora) AS ultima_vez
FROM apirequestlog l
INNER JOIN usuariosapitokens t ON t.usuarioapitokenid = l.usuarioapitokenid
WHERE l.fechahora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND l.usuarioapitokenid IS NOT NULL
GROUP BY l.usuarioapitokenid, t.tokennombre, t.tokenprefijo, t.tokenipultuso, l.iporigen
ORDER BY l.usuarioapitokenid, ultima_vez DESC;

-- 3) Endpoints desconocidos o ruido de escaneo reciente.
SELECT
  endpoint,
  metodohttp,
  responsecode,
  COUNT(*) AS total,
  MAX(fechahora) AS ultima_vez
FROM apirequestlog
WHERE fechahora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND (recurso IS NULL OR recurso = '')
GROUP BY endpoint, metodohttp, responsecode
ORDER BY total DESC, ultima_vez DESC
LIMIT 50;

-- 4) Uso por token y recurso.
SELECT
  l.usuarioapitokenid,
  t.tokennombre,
  t.tokenprefijo,
  t.tokenpermisos,
  l.recurso,
  l.responsecode,
  COUNT(*) AS total,
  MAX(l.fechahora) AS ultima_vez
FROM apirequestlog l
LEFT JOIN usuariosapitokens t ON t.usuarioapitokenid = l.usuarioapitokenid
WHERE l.fechahora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY l.usuarioapitokenid, t.tokennombre, t.tokenprefijo, t.tokenpermisos, l.recurso, l.responsecode
ORDER BY ultima_vez DESC, total DESC;

