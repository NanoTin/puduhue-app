# 05 — Seguridad

## 1. Autenticación Web

### 1.1. Login
| Aspecto            | Detalle                                                    |
|--------------------|------------------------------------------------------------|
| Credencial         | RUT chileno (`XXXXXXXX-V`, sin puntos) + contraseña       |
| Validación RUT     | Formato + dígito verificador vía `RutHelper`               |
| Excepción          | Usuario ROOT no valida formato RUT                         |
| Captcha            | reCAPTCHA Enterprise (acción `LOGIN`)                      |
| Contraseña storage | Hash encriptado (no texto plano)                           |

### 1.2. Política de Contraseñas
- Mínimo 5 caracteres.
- Al menos 1 mayúscula.
- Al menos 1 número.
- Al menos 1 carácter especial.

### 1.3. Bloqueo de Cuenta
- Tras **3 intentos fallidos** → usuario bloqueado.
- Configurable vía `.env` (`LOGIN_MAX_ATTEMPTS`).

### 1.4. Recuperación de Contraseña
- Envío de email con token `tkn` en URL.
- Token con validación de expiración.
- Servicio: `PasswordResetService` (referenciado en README pero no encontrado como archivo independiente).

---

## 2. Sesión Web

| Aspecto              | Detalle                                |
|----------------------|----------------------------------------|
| Mecanismo            | Sesión PHP nativa (`session_start()`)  |
| Duración             | 3 horas                               |
| Al expirar           | Redirección a `login.php`             |
| Middleware           | `AuthMiddleware::requireAuth()`        |
| Contexto de usuario  | `AuthMiddleware::getUserContext()`      |
| Rutas públicas       | `login/mostrar`, `login/procesar`      |

---

## 3. Autenticación API Externa

### 3.1. Bearer Token
| Aspecto              | Detalle                                     |
|----------------------|---------------------------------------------|
| Header               | `Authorization: Bearer {token}`             |
| Middleware           | `ApiBearerAuthMiddleware`                   |
| Tabla                | `usuariosapitokens`                         |
| Hash                 | HMAC-SHA256 (`hash_hmac('sha256', $token, APP_KEY)`) |
| Prefijo visible      | `pudu_` + bloque secreto                    |
| Múltiples tokens     | ✅ por usuario                              |
| Revocación           | `tokenactiva = 0`                           |
| Expiración default   | 30 días (configurable)                      |
| Sin expiración       | `tokenfechaexpira = NULL`                   |
| Mostrar token        | Una sola vez al generar                     |

### 3.2. Prohibiciones
- ❌ Token en querystring.
- ❌ Token en body como parámetro visible.
- ❌ Token plano almacenado en BD.
- ❌ Reutilizar `usuariostokens` para API pública.

### 3.3. Columnas Legacy
Las columnas `usuarioapikey*` en tabla `usuarios` quedan como **legado** (ADR-002).

---

## 4. Integración ERP — Token Finnegans

| Aspecto              | Detalle                                     |
|----------------------|---------------------------------------------|
| Endpoint auth        | `https://api.finneg.com/api/oauth/token`    |
| Parámetros           | `grant_type`, `client_id`, `client_secret`  |
| Fuente parámetros    | `.env`                                      |
| Token                | GUID de 36 chars, vigencia 5 minutos        |
| Almacenamiento       | Tabla `erptokenactivo` (1 registro)         |
| Token inválido       | Solicitar nuevo → actualizar tabla → reintentar |
| Error oculto         | `status: 400, error: "invalid token"` NO se muestra al usuario |

---

## 5. Permisos y Roles

### 5.1. Usuario ROOT
- `usuarioesroot = 1`.
- Ve todas las empresas y fundos.
- No se puede eliminar, desactivar ni bloquear (trigger en BD).
- No valida formato RUT.

### 5.2. Perfil ROOT
- Tiene todos los menús asignados.
- No visible en mantenimiento de perfiles.
- Menús nuevos se agregan automáticamente con `activo = 1`.

### 5.3. Perfil Administrador
- Igual que ROOT pero sin gestionar menús.
- Visible en pantalla pero no editable ni desactivable.
- Menús nuevos se agregan con `activo = 1`.

### 5.4. Regla de Filtrado
- **Maestros/Administración**: NO se filtran por empresa/fundo del usuario.
- **Transacciones**: SÍ se filtran por asociaciones `usuariosempresas` y `usuariosfundos`.

---

## 6. Logging de Seguridad

| Tipo de log              | Destino                     | Contenido                        |
|--------------------------|-----------------------------|----------------------------------|
| Errores internos         | `storage/LOGS/`             | Excepciones, errores PHP         |
| Integración ERP          | `storage/APILog/`           | Token (ofuscado), JSON, respuesta|
| Cambios en BD            | Tablas `*log`               | p_in_json, estado previo          |
| Requests API externa     | `apirequestlog`             | request_id, IP, headers, response|
| Uso de tokens API        | `usuariosapitokens`         | `tokenultuso`, `tokenipultuso`   |

### 6.1. Sanitización
- Headers de `Authorization` se sanitizan antes de persistir en `apirequestlog`.
- Token ERP se puede ofuscar parcialmente en logs de `storage/APILog/`.
- Nunca exponer stacktrace, SQL ni rutas físicas en respuestas API.

---

## 7. Headers y Transporte

### 7.1. HTTPS
- Obligatorio en producción para ambos subdominios.
- No publicar API por HTTP plano.

### 7.2. CORS
- No habilitado por defecto.
- Habilitar solo si existe consumidor browser real.

### 7.3. `.htaccess`
- Desactiva listado de directorios.
- Redirige al front controller (`index.php`).
- Configurado en `apps/web-php/` y `apps/api-php/`.

---

## 8. Archivos Sensibles

| Archivo    | Contenido                                | Protección requerida        |
|------------|------------------------------------------|-----------------------------|
| `.env`     | DB creds, JWT secret, API keys, SMTP     | No versionar, no exponer    |
| `cfg.php`  | Configuración PHP                        | No exponer vía web          |
| `vendor/`  | Dependencias Composer                    | No versionar (idealmente)   |

> [!CAUTION]
> Actualmente no existe `.gitignore`. El archivo `.env` con credenciales podría estar versionado. Crear `.gitignore` es una acción de seguridad prioritaria.
