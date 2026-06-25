# Playbook: Crear Nuevo Módulo CRUD

## Objetivo
Crear un módulo CRUD completo (Listar, Crear, Editar, Anular) siguiendo las convenciones del proyecto.

---

## 1. Definir el Módulo

Antes de generar código, confirmar:

| Pregunta                              | Ejemplo                    |
|---------------------------------------|----------------------------|
| Nombre del módulo (singular)          | `clientes`                 |
| Tabla principal                       | `clientes`                 |
| ¿Es Maestro o Transacción?           | Maestro                    |
| ¿Tiene tabla LOG?                     | Sí → `clienteslog`         |
| ¿Tiene tabla HIST?                    | No                         |
| ¿Tiene columnas de auditoría?         | Sí                         |
| ¿Tiene integración ERP?              | No                         |
| ¿Filtros de listado? (Columns.csv)   | `filtroClienteNombre`      |

---

## 2. Base de Datos

### 2.1. Crear Tabla
1. Agregar la tabla en `database/spec/Tables.csv`.
2. Agregar las columnas en `database/spec/Columns.csv`.
3. Ejecutar `database/generate_tables_from_csv.php` para generar `database/tables/01_table_<modulo>.sql`.
4. Si la tabla tiene LOG, se genera automáticamente `01_table_<modulo>log.sql`.
5. Ejecutar el SQL generado en phpMyAdmin (o en BD local).

### 2.2. Crear Stored Procedures
1. Ejecutar `database/generate_sp_from_csv.php` para generar `database/sp/02_sp_<modulo>.sql`.
2. Se generan automáticamente:
   - `sp_<modulo>_insertar`
   - `sp_<modulo>_editar`
   - `sp_<modulo>_anular`
   - `sp_<modulo>_listar_resumen`
   - `sp_<modulo>_consulta_por_id_resumen`
3. Revisar y ajustar los SP generados si es necesario.
4. Ejecutar en phpMyAdmin (o BD local).

---

## 3. Backend PHP

### 3.1. Crear Model
Archivo: `src/Models/<Modulo>Model.php`

- Debe extender patrones existentes (ver `EmpresaModel.php`).
- Métodos típicos: `listar()`, `obtenerPorId()`, `crear()`, `editar()`, `anular()`.
- Todos usan `Database::callSpMaint()` o `Database::callSpQuery()`.

### 3.2. Crear Service
Archivo: `src/Services/<Modulo>Service.php`

- Instancia el Model.
- Orquesta la lógica de negocio.
- Métodos: `listar()`, `obtenerPorId()`, `crear()`, `editar()`, `anular()`.
- Para Maestros: NO filtra por empresa/fundo del usuario.
- Para Transacciones: SÍ aplica filtros por usuario/empresa/fundo.

### 3.3. Crear Controller Web
Archivo: `src/Controllers/Web/<Modulo>Controller.php`

- Métodos estándar: `listar()`, `crearForm()`, `crearPost()`, `editarForm()`, `editarPost()`, `anularPost()`.
- Cargar dependencias con `require_once`.
- Usar `AuthMiddleware::getUserContext()` si es transacción.

### 3.4. Registrar Ruta
Archivo: `src/Routes/web.php`

- Agregar el módulo al mapa `$map`:
```php
'<modulo>' => [
    'controller' => '<Modulo>Controller',
    'file'       => __DIR__ . '/../Controllers/Web/<Modulo>Controller.php'
],
```

---

## 4. Frontend (Vistas)

### 4.1. Crear Vistas
Archivos en `apps/web-php/`:

- `<modulo>_listar.php`
- `<modulo>_crear.php`
- `<modulo>_editar.php`

Usar como referencia: `empresas_listar.php`, `empresas_crear.php`, `empresas_editar.php`.

### 4.2. Reglas de Vistas
- Layout: `require 'head.php'` + `require 'menu.php'` + contenido + `require 'footer.php'`.
- Filtros según `Columns.csv` (`spListar_Filter_Column = TRUE`).
- Columnas de tabla según `Columns.csv` (`spListar_Select_Column = TRUE`).
- No incluir PK en formulario de creación.
- Incluir hidden PK en formulario de edición.
- No incluir campos de auditoría (`auditcreacion*`, `auditedicion*`).
- Botones: Crear, Editar (por fila), Anular, Exportar a Excel.
- Feedback con toasts (no alerts).
- En controladores, usar `FlashMessageHelper::toast($message, $type)` o el método local `setToast()` que delega en ese helper. Tipos válidos: `success`, `danger`, `warning`, `info`.
- En listados, las acciones destructivas o sensibles deben usar `data-confirm="1"` y `data-confirm-message="..."`; el modal común se carga desde `footer.php`.

---

## 5. Menú

### 5.1. Agregar al Menú
1. Insertar en tabla `menus` vía phpMyAdmin:
   - `menudesc`: nombre visible.
   - `menupadre`: ID del grupo padre.
   - `menuform`: `<modulo>_listar.php`.
   - `menuicono`: clase de Bootstrap Icons (ej. `building`).
2. Asignar al perfil correspondiente en `perfilesmenus`.
3. Actualizar `apps/web-php/menu.json` (si se usa como backup).

---

## 6. Verificación

- [ ] Tabla creada en BD (local y producción).
- [ ] SPs creados y ejecutables.
- [ ] Model/Service/Controller creados.
- [ ] Ruta registrada en `web.php`.
- [ ] Vista listar muestra datos.
- [ ] Vista crear guarda correctamente.
- [ ] Vista editar carga y guarda correctamente.
- [ ] Anular cambia el estado del registro.
- [ ] Exportar a Excel funciona.
- [ ] Menú muestra la nueva opción.
- [ ] Responsive: verificar en resolución < 768px.
