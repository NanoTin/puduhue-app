# 03 — Backend

## 1. Arquitectura General

### 1.1. Diagrama de Capas

```
┌──────────────────────────────────┐
│  Frontend Web Interno            │  apps/web-php/*.php
│  (PHP + HTML + Bootstrap)        │
└───────────────┬──────────────────┘
                │
┌───────────────▼──────────────────┐
│  Controllers/Web/                │  src/Controllers/Web/*Controller.php
│  (26 controladores)             │
└───────────────┬──────────────────┘
                │
┌───────────────▼──────────────────┐    ┌─────────────────────────────┐
│  Services/                       │    │  API Externa REST           │
│  (25 servicios + Api/)           │◄───┤  apps/api-php/index.php     │
└───────────────┬──────────────────┘    │  Controllers/Api/V1/        │
                │                       └─────────────────────────────┘
┌───────────────▼──────────────────┐
│  Models/                         │  src/Models/*Model.php
└───────────────┬──────────────────┘
                │
┌───────────────▼──────────────────┐
│  Database.php  (PDO)             │  src/Config/Database.php
│  callSpMaint() / callSpQuery()   │
└───────────────┬──────────────────┘
                │
┌───────────────▼──────────────────┐
│  MariaDB 10.11.15                │  Stored Procedures
│  (32 archivos SP)                │
└──────────────────────────────────┘
```

### 1.2. Puntos de Entrada

| Entry Point              | Archivo                    | Propósito                    |
|--------------------------|----------------------------|------------------------------|
| Frontend Web             | `apps/web-php/index.php`   | Front controller web         |
| API Externa              | `apps/api-php/index.php`   | Front controller API REST    |

### 1.3. Router Web (`src/Routes/web.php`)
- Patrón: `?route=modulo/accion`.
- Default: `dashboard`.
- Normaliza formatos legacy (`modulo_accion.php` → `modulo/accion`).
- `allowedMenuRoutes` desde `menu.json`.
- Acciones `crear/editar/anular` siempre permitidas (formularios invocados desde listados).
- Modo parcial (`partial=1` o AJAX) para contenido sin layout.
- `require_once` del controlador si la clase no está cargada.

---

## 2. Convenciones de Código

### 2.1. Carga de Dependencias
```php
// No hay autoload confiable en hosting — usar require_once
require_once __DIR__ . '/../../src/Config/Env.php';
require_once __DIR__ . '/../../src/Config/Database.php';
require_once __DIR__ . '/../../src/Helpers/Logger.php';
```

### 2.2. Nomenclatura

| Elemento            | Convención                           | Ejemplo                        |
|---------------------|--------------------------------------|--------------------------------|
| Clase Controller    | `PascalCase` + `Controller`          | `EmpresasController`           |
| Clase Service       | `PascalCase` + `Service`             | `EmpresasService`              |
| Clase Model         | `PascalCase` + `Model`               | `EmpresaModel` (README)        |
| Métodos             | `camelCase`                          | `crearForm()`, `editarPost()`  |
| Vistas              | `snake_case` + `.php`                | `empresas_listar.php`          |
| SP                  | `sp_<modulo>_<accion>`               | `sp_empresas_insertar`         |
| Tablas/Columnas     | `minúsculas sin separador`           | `prodlechedetalle`             |

### 2.3. Flujo de una Operación CRUD

```
Vista (form POST) → Router web.php → Controller → Service → Model → Database.callSpMaint()
                                                                        ↓
                                                               SP en MariaDB
                                                                        ↓
                                                               p_out_json
                                                                        ↓
                                                               COMMIT o ROLLBACK
```

### 2.4. Métodos de Controllers Web

| Acción     | Verbo HTTP | Método del Controller |
|------------|------------|------------------------|
| Listar     | GET        | `listar()`             |
| Crear form | GET        | `crearForm()`          |
| Crear POST | POST       | `crearPost()`          |
| Editar form| GET        | `editarForm()`         |
| Editar POST| POST       | `editarPost()`         |
| Anular     | POST       | `anularPost()`         |
| Detalle    | GET        | `detalle()`            |

---

## 3. Capa de Acceso a Datos

### 3.1. `src/Config/Database.php` (capa activa)

| Método                  | Uso                                     |
|-------------------------|-----------------------------------------|
| `callSpMaint()`         | SP de mantenimiento (INSERT/UPDATE/DELETE) con transacción |
| `callSpQuery()`         | SP de consulta (SELECT) sin transacción |
| `select()`              | SQL directo (consultas sencillas)       |
| `execute()`             | SQL directo (INSERT/UPDATE sencillo)    |

- Conexión PDO singleton.
- Manejo de transacciones: `BEGIN` → SP → analizar `p_out_json` → `COMMIT` o `ROLLBACK`.
- Los SP **nunca** contienen `BEGIN/COMMIT/ROLLBACK`.

### 3.2. Legacy retirado: `src/Core/`
- La capa duplicada `src/Core/` fue retirada; no forma parte del runtime.
- Usar `src/Config/Database.php` y `src/Config/Env.php` para acceso a datos y variables de entorno.
- Documentado en `ADR-006` como legado no operativo.

---

## 4. Integración ERP (Finnegans)

### 4.1. Componentes
- `src/api-external/FinnegansClient.php` — cliente HTTP.
- `src/api-external/DTOs/` — objetos de transferencia por módulo.
- Token: texto plano de 36 chars, vigencia 5 min, almacenado en tabla `erptokenactivo`.

### 4.2. Flujo de Integración
1. Consultar `erptokenactivo`.
2. Si vacío → solicitar nuevo token → guardar.
3. Si existe → usar token.
4. Enviar JSON al endpoint Finnegans.
5. Si `status = 400` (token inválido) → renovar → reintentar.
6. Log en `storage/APILog/`.
7. Si error de negocio → mostrar al usuario + ROLLBACK.

---

## 5. API Externa (REST)

### 5.1. Componentes Implementados
- Entry point: `apps/api-php/index.php` + `.htaccess`.
- Middleware: `src/Middleware/ApiBearerAuthMiddleware.php`.
- Helpers: `src/Helpers/ApiResponse.php`, `src/Helpers/ApiRequest.php`.
- Controllers: `src/Controllers/Api/V1/`.
- Servicios: `src/Services/Api/`.
- Log: `src/Services/ApiRequestLogService.php`.

### 5.2. Respuesta Estándar
```json
{
  "status": 200,
  "message": "OK",
  "data": [],
  "meta": {
    "request_id": "uuid",
    "page": 1,
    "page_size": 100,
    "total_registros": 0,
    "execution_ms": 12
  }
}
```

---

## 6. Estructura de Carpetas (Resumen)

```
apps/
  web-php/            # Frontend interno (82 archivos)
    assets/css|js|img
    partials/
    uploads/
  api-php/            # API externa (2 archivos)

src/
  Auth/               # AuthService
  Config/             # Database.php, Env.php (ACTIVOS)
  Controllers/
    Web/              # 26 controllers web
    Api/V1/           # Controllers API v1
  Core/               # LEGACY — no usar
  Helpers/            # Logger, ApiResponse, ApiRequest, ExcelExporter, ApiException
  Middleware/         # AuthMiddleware, ApiBearerAuthMiddleware
  Models/             # Modelos de datos
  Routes/             # web.php (router web)
  Services/           # 25 servicios + Api/
  api-external/       # FinnegansClient + DTOs

database/
  spec/               # CSVs de especificación
  tables/             # 51 archivos SQL CREATE TABLE
  sp/                 # 32 archivos SQL Stored Procedures
  alter_table/        # Scripts ALTER TABLE
  *.php               # Generadores de SQL desde CSV

storage/
  LOGS/               # Logs internos
  APILog/             # Logs integración ERP
  temp/               # Temporales
```
