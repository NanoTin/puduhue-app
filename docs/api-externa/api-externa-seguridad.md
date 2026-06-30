# API Externa - Seguridad

## 1. Objetivo

Definir los controles minimos de seguridad para la API publica bajo el subdominio recomendado `api.puduhue.cl`, alineados al repositorio actual y al modelo confirmado de tokens por usuario interno.

## 2. Riesgos detectados en la auditoria

- `usuarios` contiene una sola API key legacy por usuario, lo que no permite multiples tokens ni revocacion granular.
- `usuariostokens` existe pero su uso actual es distinto y no modela bearer tokens publicos.
- `apps/api-php` aun no tiene front controller ni controles de entrada.
- No existe hoy un middleware bearer dedicado ni un helper JSON estandar para API publica.

## 3. Transporte

- HTTPS obligatorio en ambientes publicados.
- No publicar la API externa por HTTP plano.

## 4. Autenticacion bearer

### 4.1. Regla

Usar solo:

`Authorization: Bearer {token}`

### 4.2. Prohibiciones

- No usar querystring.
- No usar token en body como parametro visible.
- No guardar token plano en base de datos.

### 4.3. Validacion minima

- Header presente.
- Esquema `Bearer` correcto.
- Token recibido no vacio.
- Token encontrado por verificacion de hash.
- `tokenactiva = 1`.
- `tokenfechaexpira` nula o futura.
- Usuario asociado activo.
- `tokenpermisos` vacio, `*`, o incluye el permiso exacto `{recurso}:{accion}`.

## 5. Generacion y almacenamiento de tokens

### 5.1. Generacion

- Usar `random_bytes`.
- Recomendada longitud minima: 32 bytes aleatorios antes de codificar.
- Formato sugerido de presentacion: prefijo tecnico + bloque secreto legible, por ejemplo `pudu_` + hex o base64url.

### 5.2. Almacenamiento

- Guardar solo `tokenhash`.
- Guardar `tokenprefijo` para referencia visual parcial.
- Mostrar el token plano una sola vez.

### 5.3. Hash

Opciones validas:

- `password_hash` si se verificara token por barrido controlado
- hash deterministico fuerte, por ejemplo `hash_hmac('sha256', token, app_secret)`, si se necesita lookup directo por igualdad

Decision recomendada para este proyecto:

- usar `hash_hmac('sha256', $plainToken, APP_KEY)` o secreto equivalente
- razon: la API necesitara localizar el token eficientemente sin recorrer multiples filas con `password_verify`

## 6. Expiracion y revocacion

- Default: 30 dias.
- Permitir dias configurables por el usuario interno.
- Permitir "sin expiracion" con `tokenfechaexpira = NULL`.
- Revocar debe marcar `tokenactiva = 0` y registrar auditoria.
- Para reportes automatizados de Power BI, usar tokens de vida larga o sin expiracion, pero dedicados por consumidor y con `tokenpermisos` acotado.
- Ejemplo de permiso inicial: `prodleche-detalle:query`.

## 7. Logging

Registrar en `apirequestlog`:

- `requestid`
- `usuarioid`
- `usuarioapitokenid`
- `apiversion`
- `recurso`
- `metodohttp`
- `endpoint`
- `iporigen`
- `useragent`
- `requestheadersjson`
- `requestbodyjson`
- `responsecode`
- `responsetimems`
- `fechahora`

Reglas:

- Sanitizar `Authorization` antes de persistir headers.
- No guardar secretos completos.
- Mantener `request_id` en la respuesta al cliente para soporte.

## 8. Respuestas seguras

Nunca exponer:

- stacktrace
- errores PDO crudos
- SQL
- rutas fisicas del servidor
- nombres internos de clases como detalle de error

Siempre exponer:

- `status`
- `message`
- `data`
- `meta.request_id`

## 9. Validaciones defensivas

- Rechazar metodos distintos de `POST` en los endpoints `/query`.
- Rechazar JSON invalido.
- Limitar `page_size`.
- Limitar arrays de IDs.
- Validar rangos de fechas.
- Considerar rango maximo de consulta para evitar extracciones completas.

## 10. Captura de IP

- Prioridad inicial: `REMOTE_ADDR`.
- Si en produccion hay proxy controlado, evaluar `X-Forwarded-For` solo cuando la infraestructura sea confiable.
- Actualizar `usuariosapitokens.tokenipultuso`.
- Registrar `apirequestlog.iporigen`.

## 11. CORS y publicacion

- No habilitar CORS por defecto.
- Habilitarlo solo si existe consumidor browser real.
- La API es publica en red, no publica en datos.

## 12. Endurecimiento recomendado

- Limite de `page_size`, por ejemplo 500.
- Rate limit posterior por token o IP.
- Timeout de consultas.
- Indices en columnas de fecha y filtros frecuentes.
- Separar claramente auth de sesion web y auth bearer.
