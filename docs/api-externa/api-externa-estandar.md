# API Externa - Estandar General

## 1. Objetivo

Establecer el contrato funcional y tecnico de la API externa publica del proyecto, reutilizando la arquitectura existente y evitando una subarquitectura paralela.

## 2. Ubicacion tecnica real

### 2.1. Entry point

- La API publica debe exponerse desde `apps/api-php/`.
- El repositorio ya contiene esa carpeta y hoy esta vacia.
- La recomendacion es crear un front controller unico, por ejemplo `apps/api-php/index.php`.
- En shared hosting, la publicacion recomendada es un subdominio dedicado `api.puduhue.cl` apuntando a `apps/api-php`.
- Se incluye `.htaccess` en `apps/api-php` para redirigir todas las rutas al front controller.

### 2.2. Capa compartida

La API debe reutilizar `src/` porque ya contiene:

- `src/Config/Database.php`
- `src/Config/Env.php`
- `src/Auth/AuthService.php`
- `src/Middleware/AuthMiddleware.php`
- `src/Helpers/Logger.php`
- `src/Controllers/Web/*`
- `src/Services/*`

Observacion de auditoria:

- La capa legacy `src/Core/` no forma parte de la capa operativa vigente de la app.
- La referencia activa para runtime debe consolidarse en `src/Config/Database.php` y `src/Config/Env.php`.
- Los archivos de `src/Core/*` ya fueron retirados; no crear nuevas dependencias contra esa ruta.

### 2.3. Componentes nuevos permitidos

Solo si se separan responsabilidades correctamente:

- `src/Routes/api.php`
- `src/Controllers/Api/V1/*`
- `src/Services/Api/*`
- `src/Helpers/ApiResponse.php`
- `src/Helpers/Request.php`
- `src/Middleware/ApiBearerAuthMiddleware.php`

## 3. Versionado y convencion

- Host publico recomendado: `https://api.puduhue.cl`
- Base path preferente: `/v1/`
- Compatibilidad adicional: el router acepta tambien `/api/v1/` si otro ambiente decide publicar la API bajo un prefijo dentro del mismo host.
- Convencion de consulta: `POST /v1/{recurso}/query`
- Request body: JSON
- Response body: JSON

Endpoints iniciales:

- `POST /v1/prodleche-detalle/query`
- `POST /v1/suplanimal-detalle/query`

## 4. Autenticacion

- Header requerido: `Authorization: Bearer {token}`
- No usar token en querystring.
- No usar token como parametro visible en body.
- El token se asocia a un `usuarioid` existente de `usuarios`.

## 5. Modelo de seguridad y datos

### 5.1. Tabla `usuarios`

- Se reutiliza `usuarios` como entidad principal de identidad interna.
- No se debe crear una tabla nueva de clientes API.
- Las columnas actuales `usuarioapikeyhash`, `usuarioapikeyactiva`, `usuarioapikeyfechagen`, `usuarioapikeyultuso`, `usuarioapikeyipultuso` quedan desalineadas con el nuevo objetivo y deben considerarse legado para migracion.

### 5.2. Tabla nueva `usuariosapitokens`

Debe soportar:

- multiples tokens por usuario
- hash del token
- prefijo visible
- vigencia individual
- revocacion individual
- ultimo uso e IP ultimo uso
- auditoria de creacion y edicion

SQL propuesto:

```sql
CREATE TABLE `usuariosapitokens` (
  `usuarioapitokenid` bigint(20) NOT NULL AUTO_INCREMENT,
  `usuarioid` int(11) NOT NULL,
  `tokennombre` varchar(150) NOT NULL,
  `tokenhash` varchar(255) NOT NULL,
  `tokenprefijo` varchar(20) NOT NULL,
  `tokenactiva` tinyint(1) NOT NULL DEFAULT 1,
  `tokenfechaexpira` datetime NULL DEFAULT NULL,
  `tokenultuso` datetime NULL DEFAULT NULL,
  `tokenipultuso` varchar(50) NULL DEFAULT NULL,
  `observacion` varchar(255) NULL DEFAULT NULL,
  `auditcreacionusuarioid` int(11) NOT NULL,
  `auditcreaciondispositivo` varchar(100) NOT NULL,
  `auditcreacionip` varchar(50) NOT NULL,
  `auditcreacionfechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `auditedicionusuarioid` int(11) NULL DEFAULT NULL,
  `auditediciondispositivo` varchar(100) NULL DEFAULT NULL,
  `auditedicionip` varchar(50) NULL DEFAULT NULL,
  `auditedicionfechahora` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`usuarioapitokenid`),
  UNIQUE KEY `uq_usuariosapitokens_tokenhash` (`tokenhash`),
  KEY `idx_usuariosapitokens_usuarioid` (`usuarioid`),
  KEY `idx_usuariosapitokens_tokenactiva` (`tokenactiva`),
  KEY `idx_usuariosapitokens_tokenfechaexpira` (`tokenfechaexpira`),
  CONSTRAINT `fk_usuariosapitokens_usuarioid`
    FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.3. Tabla nueva `apirequestlog`

Debe registrar cada request procesado, exitoso o fallido.

```sql
CREATE TABLE `apirequestlog` (
  `apirequestlogid` bigint(20) NOT NULL AUTO_INCREMENT,
  `requestid` char(36) NOT NULL,
  `usuarioid` int(11) NULL DEFAULT NULL,
  `usuarioapitokenid` bigint(20) NULL DEFAULT NULL,
  `apiversion` varchar(20) NOT NULL,
  `recurso` varchar(100) NOT NULL,
  `metodohttp` varchar(10) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `iporigen` varchar(50) NULL DEFAULT NULL,
  `useragent` varchar(500) NULL DEFAULT NULL,
  `requestheadersjson` json NULL,
  `requestbodyjson` json NULL,
  `responsecode` int(11) NOT NULL,
  `responsetimems` int(11) NULL DEFAULT NULL,
  `fechahora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`apirequestlogid`),
  UNIQUE KEY `uq_apirequestlog_requestid` (`requestid`),
  KEY `idx_apirequestlog_usuarioid_fechahora` (`usuarioid`, `fechahora`),
  KEY `idx_apirequestlog_usuarioapitokenid` (`usuarioapitokenid`),
  KEY `idx_apirequestlog_responsecode` (`responsecode`),
  CONSTRAINT `fk_apirequestlog_usuarioid`
    FOREIGN KEY (`usuarioid`) REFERENCES `usuarios` (`usuarioid`),
  CONSTRAINT `fk_apirequestlog_usuarioapitokenid`
    FOREIGN KEY (`usuarioapitokenid`) REFERENCES `usuariosapitokens` (`usuarioapitokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 6. Request estandar

### 6.1. Headers

- `Authorization: Bearer {token}`
- `Content-Type: application/json`
- `Accept: application/json`

### 6.2. Body comun

Todo endpoint de consulta debe aceptar:

- `page`
- `page_size`

Ejemplo:

```json
{
  "page": 1,
  "page_size": 100
}
```

## 7. Response estandar

```json
{
  "status": 200,
  "message": "Consulta realizada correctamente",
  "data": [],
  "meta": {
    "request_id": "3f455b55-1b8d-4f5c-a56a-40cb4fd39d68",
    "page": 1,
    "page_size": 100,
    "total_registros": 0,
    "execution_ms": 12
  }
}
```

## 8. Errores estandar

### 8.1. 400

Payload invalido, JSON invalido o filtros incorrectos.

### 8.2. 401

Bearer ausente, invalido, revocado o expirado.

### 8.3. 403

Usuario asociado inactivo o sin autorizacion de negocio.

### 8.4. 405

Metodo HTTP no permitido.

### 8.5. 500

Error interno controlado. Nunca exponer stacktrace ni SQL.

## 9. Flujo estandar del request

1. Entrar por `apps/api-php/index.php`.
2. Resolver ruta versionada.
3. Generar `request_id`.
4. Leer headers, IP, body y hora inicio.
5. Validar metodo HTTP.
6. Validar `Authorization: Bearer`.
7. Buscar token activo en `usuariosapitokens`.
8. Validar expiracion y `usuarioactivo`.
9. Parsear JSON.
10. Validar `page`, `page_size` y filtros del recurso.
11. Ejecutar service de consulta con PDO parametrizado.
12. Construir respuesta JSON estandar.
13. Actualizar `tokenultuso` y `tokenipultuso`.
14. Insertar fila en `apirequestlog`.
15. Responder al cliente.

## 10. Acceso a datos

- Prioridad: `PDO` + SQL parametrizado.
- Reutilizar `Database` como singleton de conexion.
- No concatenar SQL inseguro.
- No usar la familia `sp_usuariostokens_*` para la API publica.
- Los SP existentes de negocio solo deben reutilizarse si ya resuelven filtros y seguridad de forma coherente; para la API externa inicial no se observa hoy un SP listo para `prodleche-detalle/query` ni `suplanimal-detalle/query`.

## 11. UI interna para generar tokens

Pantalla objetivo: `apps/web-php/usuarios_listar.php`

Requisitos funcionales:

- boton por fila para generar token API
- modal para:
  - nombre del token
  - cantidad de dias
  - checkbox "sin expiracion"
  - observacion opcional
- default de expiracion: 30 dias
- mostrar el token solo una vez
- permitir copiarlo
- no volver a mostrar el valor plano

## 12. Reutilizacion confirmada

Reutilizar:

- `src/Config/Database.php`
- `src/Config/Env.php`
- `src/Helpers/Logger.php`
- `src/Controllers/Web/UsuariosController.php`
- `src/Services/UsuariosService.php`
- `apps/web-php/partials/modal_confirm.php` como referencia visual, no como modal final
- estructura de vistas en `apps/web-php`

No reutilizar directamente:

- `src/Routes/web.php` como router de API
- `AuthMiddleware` de sesion web para bearer auth
- columnas `usuarioapikey*` de `usuarios` como modelo final
- `usuariostokens` para tokens API publicos
- `src/Core/*` como capa DB del runtime

## 13. Checklist de implementacion

- [ ] Crear front controller en `apps/api-php`
- [ ] Crear router API versionado
- [ ] Crear middleware bearer auth
- [ ] Crear helper de respuesta JSON
- [ ] Crear `usuariosapitokens`
- [ ] Crear `apirequestlog`
- [ ] Ajustar `UsuariosController` y `UsuariosService`
- [ ] Agregar modal/boton en `usuarios_listar.php`
- [ ] Implementar `prodleche-detalle/query`
- [ ] Implementar base de `suplanimal-detalle/query`
- [ ] Configurar subdominio `api.puduhue.cl` hacia `apps/api-php`
