# ADR - API Externa Publica Versionada

## ADR-001 - Ubicacion y reutilizacion de arquitectura

### Contexto

El proyecto ya dispone de `src/` compartido, `apps/web-php/` como frontend interno y `apps/api-php/` existente pero vacio. La documentacion objetivo exige exponer una API publica versionada sin crear una arquitectura paralela.

### Decision

- El punto de entrada de la API publica vivira en `apps/api-php/`.
- La logica comun vivira en `src/`.
- Se crean componentes de API solo donde la responsabilidad no sea propia de la web:
  - router API
  - middleware bearer
  - response helper JSON
  - controllers API
  - services API

### Consecuencia

Se evita duplicar `src` dentro de `apps/api-php` y se preserva un unico nucleo de negocio y acceso a datos.

## ADR-002 - Modelo de tokens API

### Contexto

La tabla `usuarios` hoy contiene columnas `usuarioapikey*`, lo que modela como maximo una API key por usuario. Ademas existe `usuariostokens`, pero responde a otro flujo de tokens internos.

### Decision

- La API publica usara `usuarios` como entidad de identidad.
- Se crea una tabla hija `usuariosapitokens`.
- No se reutiliza `usuariostokens` para bearer tokens publicos.
- Las columnas `usuarioapikey*` de `usuarios` se consideran legado y quedan fuera del modelo final de API publica.

### Consecuencia

Se habilitan multiples tokens por usuario, revocacion individual, expiracion individual y trazabilidad de ultimo uso.

## ADR-003 - Acceso a datos de endpoints externos

### Contexto

Existen stored procedures para modulos internos, pero no hay un SP listo y completo para `prodleche-detalle/query` ni para `suplanimal-detalle/query`. El SP actual de `suplanimaldetalle` esta claramente incompleto para el caso publico.

### Decision

- Para la API externa inicial se prioriza `PDO` con SQL parametrizado.
- Se reutiliza la conexion `Database`.
- Los SP solo se reutilizaran si resuelven correctamente el caso publico sin introducir acoplamiento indebido.

### Consecuencia

La API queda mas simple de auditar, con queries explicitas, paginacion clara y menor dependencia de SP incompletos.

## ADR-004 - Autenticacion de API separada de la sesion web

### Contexto

`AuthMiddleware` actual esta orientado a sesion web interna, no a bearer tokens.

### Decision

- La API publica tendra middleware propio de autenticacion bearer.
- No se reutiliza `AuthMiddleware` como solucion final para `/api/v1/*`.

### Consecuencia

Se evita mezclar sesion web con autenticacion de integraciones.

## ADR-005 - Logging de requests

### Contexto

La API publica requiere trazabilidad operativa y de seguridad.

### Decision

- Crear `apirequestlog`.
- Registrar cada request con `requestid`, usuario, token, endpoint, body, response code y tiempo de ejecucion.
- Sanitizar headers sensibles antes de persistirlos.

### Consecuencia

Se habilita auditoria tecnica, soporte y analisis de uso.

## ADR-006 - Consolidacion de configuracion y acceso a datos

### Contexto

La auditoria del repositorio muestra duplicidad de clases entre `src/Core/*` y `src/Config/*`. En runtime, la aplicacion ya opera sobre `src/Config/Database.php` y `src/Config/Env.php`, mientras que `src/Core/Database.php` y `src/Core/DBConfig.php` no tienen referencias activas fuera de su propia capa.

### Decision

- Consolidar la capa operativa de configuracion y conexion en:
  - `src/Config/Database.php`
  - `src/Config/Env.php`
- Considerar `src/Core/Database.php`, `src/Core/DBConfig.php` y `src/Core/Env.php` como legado/deprecado.
- No eliminar aun esos archivos hasta completar la limpieza final y confirmar ausencia de dependencias fuera del flujo principal.

### Consecuencia

Se reduce ambiguedad de clases globales `Database` y `Env`, y la implementacion de la API externa queda anclada a la capa efectiva del proyecto.
