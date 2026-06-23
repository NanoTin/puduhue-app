# 01 — Discovery (Levantamiento del Proyecto)

## 1. Información General

| Campo                  | Detalle                                                                 |
|------------------------|-------------------------------------------------------------------------|
| **Razón social**       | Capac SpA                                                               |
| **Nombre comercial**   | Puduhue                                                                 |
| **Rubro**              | Agropecuario — gestión de ganado para reproducción y obtención de leche |
| **Tipo de sistema**    | Aplicación web interna (NO SaaS)                                        |
| **Usuarios**           | ~30 usuarios, todos internos                                            |
| **Hosting**            | Hosting compartido con cPanel, sin SSH                                  |
| **URL Web**            | `webapp.puduhue.cl` → `apps/web-php/`                                   |
| **URL API**            | `api.puduhue.cl` → `apps/api-php/`                                      |

---

## 2. Stack Tecnológico

| Componente         | Tecnología                     |
|--------------------|--------------------------------|
| Lenguaje           | PHP 8.4.x                     |
| Base de datos      | MariaDB 10.11.15               |
| Frontend CSS       | Bootstrap 5 + Bootstrap Icons  |
| Frontend JS        | Vanilla JS                     |
| Servidor           | Apache (cPanel, shared hosting) |
| Extensiones PHP    | `mysqli`, `curl`, `mbstring`   |
| ERP integrado      | Finnegans (API REST)           |
| Reportes externos  | Power BI (vía API externa)     |
| Excel              | PhpSpreadsheet (Composer)      |
| Captcha            | reCAPTCHA Enterprise           |

### Restricciones del Entorno

- Sin acceso SSH al servidor.
- Scripts SQL se ejecutan manualmente vía phpMyAdmin.
- Composer se usa solo en desarrollo local; `vendor/` se sube completo al hosting.
- No hay autoload confiable en hosting; se usa `require_once` para clases clave.
- No hay ambiente de desarrollo local configurado; los cambios se suben directamente a producción.

---

## 3. Módulos Funcionales Actuales

### 3.1. Transacciones

| Módulo                  | Descripción                                                                | Integración ERP |
|-------------------------|----------------------------------------------------------------------------|-----------------|
| Producción de Leche     | Registro por tipo de leche, litros, vacas, cálculo litros/vaca             | ✅ Finnegans    |
| Retiro de Leche         | Registro de retiros por camión, con voucher por estanque                   | ❌              |
| Suplementación Animal   | Consumo de productos de bodega por categoría animal y lote, dosis/animal   | ✅ Finnegans    |

### 3.2. Administración

| Módulo              | Vistas (listar/crear/editar) |
|----------------------|------------------------------|
| Empresas             | ✅                           |
| Usuarios             | ✅ + cambio de contraseña + generación de token API |
| Usuarios Empresas    | ✅ (crear/listar, editar vacío) |
| Usuarios Fundos      | ✅ (crear/listar, editar vacío) |
| Menús                | ✅                           |
| Perfiles             | ✅                           |
| Perfiles Menús       | ✅                           |

### 3.3. Maestros

| Módulo                       | Vistas |
|------------------------------|--------|
| Fundos                       | ✅     |
| Fundos Tipos                 | ✅     |
| Fundos Estanques             | ✅     |
| Fundos Estanques Clientes    | ✅     |
| Tipos de Leche               | ✅     |
| Inventario Bodegas           | ✅     |
| Inventario Ítems (Productos) | ✅     |
| Inventario Categorías Ganado | ✅     |
| Inventario Unidades de Medida| ✅     |
| Clientes                     | ✅     |

### 3.4. Reportes y Proyecciones

| Módulo                        | Descripción                    |
|-------------------------------|--------------------------------|
| Reporte Producción de Leche   | Reporte con filtros y gráficos |
| Presupuesto Leche Mensual     | CRUD de PPto leche mensual     |
| Proyección Leche Diaria       | CRUD de proyección diaria      |
| Reporte Leche BI              | Integración con Power BI       |

### 3.5. API Externa (REST)

| Endpoint                                 | Estado       |
|------------------------------------------|--------------|
| `POST /v1/prodleche-detalle/query`       | Implementado |
| `POST /v1/suplanimal-detalle/query`      | Base funcional |

---

## 4. Integración con Finnegans (ERP)

- **Autenticación**: Token de texto plano (GUID, 36 chars), vigencia 5 min.
- **Tabla de token**: `erptokenactivo` (1 registro activo).
- **Flujo**: Obtener/reusar token → enviar JSON al endpoint → procesar respuesta → log.
- **Logs**: Se guardan en `storage/APILog/` (archivos `.log`), no en BD.
- **DTOs**: `src/api-external/DTOs/` — Request y Response por módulo.
- **Regla Producción de Leche**: 1 cabecera → N detalles → N envíos (1 por tipo de leche con litros > 0).
- **Regla Suplementación**: Agrupación por (Categoría Animal + Total Animales) → 1 JSON por grupo.

---

## 5. Arquitectura de Capas

```
apps/web-php/    →  Frontend interno (PHP + HTML + Bootstrap)
apps/api-php/    →  API externa REST (JSON)
     ↓                ↓
src/Controllers/Web/*  |  src/Controllers/Api/V1/*
     ↓                      ↓
src/Services/*  ←──────────→  (compartidos)
     ↓
src/Models/*
     ↓
Stored Procedures (MariaDB vía PDO — Database.php)
```

### Capas del `src/`

| Capa                | Descripción                                                |
|---------------------|------------------------------------------------------------|
| `Config/`           | `Database.php`, `Env.php` — capa operativa vigente         |
| `Core/`             | `Database.php`, `DBConfig.php`, `Env.php` — **LEGACY**     |
| `Auth/`             | `AuthService.php`                                          |
| `Middleware/`       | `AuthMiddleware.php`, `ApiBearerAuthMiddleware.php`        |
| `Controllers/Web/`  | 26 controladores web                                      |
| `Controllers/Api/V1/`| Controladores API v1                                     |
| `Services/`         | 25 servicios + subcarpeta `Api/`                           |
| `Models/`           | Modelos de acceso a datos vía SP                           |
| `Routes/`           | `web.php` (router web)                                     |
| `Helpers/`          | `Logger`, `ApiResponse`, `ApiRequest`, `ExcelExporter`, etc. |
| `api-external/`     | `FinnegansClient.php` + DTOs                               |

---

## 6. Modelo de Datos (resumen)

- **51 tablas SQL** definidas en `database/tables/`.
- **32 archivos de SP** en `database/sp/`.
- **4 CSVs de especificación**: `Tables.csv`, `Columns.csv`, `Audit Columns.csv`, `Generic Log Table.csv`.
- Convenciones: PK autoincremental, columnas de auditoría estándar, tablas LOG (append-only), tablas HIST (para DELETE físico).
- Generadores: `generate_tables_from_csv.php` y `generate_sp_from_csv.php`.

---

## 7. Seguridad (resumen)

- Login por RUT chileno (formato `XXXXXXXX-V`) + contraseña encriptada.
- Bloqueo tras 3 intentos fallidos.
- Sesión PHP de 3 horas + `AuthMiddleware`.
- API externa: Bearer token (HMAC-SHA256, múltiples por usuario, revocación individual).
- reCAPTCHA Enterprise en login.
- JWT configurado en `.env` (para API).

---

## 8. Estado del Proyecto al Momento del Levantamiento

### Documentación existente (pre-auditoría)
- `README.md` — 1345 líneas, muy completo.
- `PUDUHUE.agent` — 450 líneas de reglas backend/DB.
- `PUDUHUE_FRONT.agent` — 720 líneas de reglas frontend.
- `CHANGELOG.md` — Registro de cambios (versión Unreleased).
- `docs/ADR/ADR-INDEX.md` — indice central de ADRs.
- `docs/api-externa/` — 4 documentos técnicos de la API externa.

### Hallazgos de la auditoría
- No existe `.gitignore`.
- Archivos legacy/backup en producción: `prodleche_crear_bak_20251215.php`, `dashboard copy.php`.
- Carpeta `lib/` vacía.
- Archivos temporales en raíz: `tmp_eval.php`, `tmp_output.html`, imágenes y `.xlsx` sueltos.
- `src/Core/` contiene 3 archivos legacy (`Database.php`, `DBConfig.php`, `Env.php`).
- No hay ambiente de desarrollo local; se deploya directo a producción.
- No hay tests automatizados.
- Archivos de vista `usuariosempresas_editar.php` y `usuariosfundos_editar.php` son vacíos/mínimos (44 y 42 bytes).
