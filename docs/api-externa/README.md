# API Externa

## Objetivo

Definir la base documental de la API externa publica del proyecto Puduhue App Web, alineada a la estructura real del repositorio y al objetivo de exponer recursos versionados bajo un host dedicado.

## Estado auditado

- `apps/api-php/` existe y hoy esta vacio: es el lugar natural para el punto de entrada publico de la API.
- `src/` esta al mismo nivel que `apps/`, por lo que la API debe reutilizar esa capa compartida.
- Ya existen capas reutilizables en `src/Controllers`, `src/Services`, `src/Config`, `src/Auth`, `src/Middleware` y `src/Helpers`.
- El frontend interno sigue operando desde `apps/web-php/` con `src/Routes/web.php`.
- El modelo actual de `usuarios` aun contiene columnas `usuarioapikey*`; esa implementacion no cumple el objetivo confirmado de multiples tokens por usuario y debe quedar relegada para compatibilidad interna durante la migracion.
- Existe `usuariostokens`, pero su semantica actual es de tokens internos asociados a `tokentipos`; no debe reutilizarse para la API publica.

## Documentos de esta carpeta

- `api-externa-estandar.md`
  Establece contrato, versionado, respuesta JSON, ubicacion tecnica, componentes reutilizables y tablas nuevas.
- `api-externa-seguridad.md`
  Define autenticacion bearer, almacenamiento hash, logging, revocacion y controles defensivos.
- `endpoints/prodleche-detalle.md`
  Documento especifico del endpoint `POST /v1/prodleche-detalle/query`.
- `endpoints/suplanimal-detalle.md`
  Documento especifico del endpoint `POST /v1/suplanimal-detalle/query`.

## Alcance inicial confirmado

- Host publico recomendado: `https://api.puduhue.cl`
- Base path preferente en subdominio dedicado: `/v1/`
- Compatibilidad de router: tambien acepta `/api/v1/` si en otro ambiente se publica bajo prefijo
- Convencion: `POST /v1/{recurso}/query`
- Auth: `Authorization: Bearer {token}`
- Respuesta estandar: `status`, `message`, `data`, `meta`
- Recursos iniciales:
  - `prodleche-detalle`
  - `suplanimal-detalle`

## Regla de trabajo

Antes de implementar o modificar la API externa se debe revisar en este orden:

1. `docs/api-externa/api-externa-estandar.md`
2. `docs/api-externa/api-externa-seguridad.md`
3. `docs/api-externa/endpoints/{recurso}.md`
4. `adr/ADR.md`

## Decision de arquitectura

La API externa no debe crear una segunda aplicacion con su propia capa `src`. El entrypoint vivira en `apps/api-php`, pero controllers, services, DB helpers, autenticacion y utilidades deben vivir en `src/`, compartidos con la web cuando tenga sentido y separados solo cuando la responsabilidad sea propiamente de API.

## Publicacion recomendada

Configuracion real acordada:

- `webapp.puduhue.cl` apunta a `apps/web-php`
- `api.puduhue.cl` debe apuntar a `apps/api-php`

Con esa topologia, los endpoints quedan con esta forma:

- `https://api.puduhue.cl/v1/prodleche-detalle/query`
- `https://api.puduhue.cl/v1/suplanimal-detalle/query`

## Pendientes intencionales

- Crear router/front controller para `apps/api-php`.
- Crear autenticacion bearer desacoplada del login web.
- Crear tablas `usuariosapitokens` y `apirequestlog`.
- Agregar gestion de token API desde `apps/web-php/usuarios_listar.php`.
- Implementar los endpoints externos iniciales reutilizando PDO y `Database`.
