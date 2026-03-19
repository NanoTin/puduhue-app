# Auditoría de Código — Propuestas de Mejora

> [!NOTE]
> Este documento es el resultado de una auditoría completa al código del proyecto Puduhue App Web. **No se ha modificado ningún archivo**. Cada hallazgo es una propuesta para discusión, priorización vía ADR y planificación antes de ejecutar.

---

## Índice de Prioridad

| Icono | Significado |
|-------|-------------|
| 🔴    | Crítico — seguridad o pérdida de datos |
| 🟠    | Alto — afecta estabilidad u operación |
| 🟡    | Medio — deuda técnica significativa |
| 🟢    | Bajo — mejora de calidad/mantenibilidad |

---

## 1. Seguridad

### 🔴 1.1. Sin protección CSRF en formularios POST
**Archivos**: Todos los formularios en `apps/web-php/*.php` (create, edit, anular).
**Problema**: Los formularios POST no incluyen token CSRF. Un atacante podría enviar requests falsificados si el usuario está logueado.
**Propuesta**:
- Generar token CSRF en sesión al cargar formularios.
- Agregar `<input type="hidden" name="_csrf" value="...">` en todos los forms.
- Validar token en los controllers POST antes de procesar.
- Crear un helper `CsrfHelper::generate()` / `CsrfHelper::validate()`.

### 🔴 1.2. Login logic en la vista (`login.php`)
**Archivo**: `apps/web-php/login.php` (16.7 KB).
**Problema**: `login.php` contiene lógica de autenticación directa: validación de reCAPTCHA, consulta de usuario, verificación de contraseña, manejo de sesión, rate limiting, bloqueo de cuenta. Esto viola la separación de capas y dificulta la auditoría de seguridad.
**Propuesta**:
- Extraer toda la lógica POST a `AuthController::loginPost()`.
- La vista solo debe mostrar el formulario y mensajes de error provistos por el controller.
- Reutilizar `AuthService` para la lógica de autenticación.

### 🟠 1.3. Acceso directo a `$_SESSION` en vistas
**Archivos**: `menu.php` (línea 4), `footer.php` (líneas 257-261), `index.php` (líneas 54-60).
**Problema**: Vistas acceden a `$_SESSION` directamente, lo que acopla la lógica de sesión a la presentación.
**Propuesta**:
- Los controllers deben pasar las variables de sesión necesarias como variables PHP a la vista.
- Las vistas no deben leer ni escribir `$_SESSION` directamente.

### 🟠 1.4. `echo` directo de `$_POST` en `login.php`
**Archivo**: `login.php`, líneas 198 y 229.
**Problema**: Se usa `htmlspecialchars($_POST['username'] ?? ...)` para output, lo cual es correcto, pero el acceso directo a `$_POST` en la vista rompe la separación de capas.
**Propuesta**: Incluir en la migración del login al controller (propuesta 1.2).

---

## 2. UX/UI — Feedback y Alertas

### 🟡 2.1. Uso de `alert()` nativo de JavaScript
**Archivos afectados** (12 instancias en 5 archivos):
- `prodleche_crear.php` — 6 instancias (validaciones de fecha, litros, vacas, horas, campos ERP)
- `prodleche_editar.php` — 3 instancias (validaciones similares)
- `suplanimal_crear.php` — 1 instancia
- `suplanimal_editar.php` — 1 instancia
- `usuarios_crear.php` — 1 instancia (validación de contraseña)

**Problema**: `alert()` es bloqueante, no tiene estilo visual coherente con la app, y rompe la experiencia UX.
**Propuesta**:
- Crear función JS global `showToast(message, type)` en `footer.php` o en un archivo JS dedicado.
- Reemplazar todas las instancias por toasts de Bootstrap 5.
- Tipos: `success`, `error`, `warning`, `info`.

### 🟡 2.2. Uso de `confirm()` en anulación/eliminación
**Archivos**: Todos los `*_listar.php` (22+ archivos).
**Problema**: `confirm()` nativo es feo y no permite cancelar fácilmente. Aunque es aceptable para acciones destructivas, se podría mejorar.
**Propuesta**:
- Reemplazar `confirm()` por un modal de confirmación Bootstrap reutilizable.
- Ya existe un partial `partials/modal_confirm.php` que podría adaptarse.

### 🟡 2.3. Toast ya parcialmente implementado en `footer.php`
**Archivo**: `footer.php` (líneas 257-261).
**Hallazgo positivo**: Ya existe lógica para mostrar toasts desde `$_SESSION['toast']`. Los controllers ya pueden setear `$_SESSION['toast'] = ['type' => 'success', 'message' => '...']`.
**Propuesta**: Documentar este mecanismo y asegurarse de que TODOS los controllers lo usen consistentemente. Migrar los `alert()` de las vistas a este sistema.

---

## 3. UX/UI — Grids y Paginación

### 🟠 3.1. Sin paginación en el frontend web
**Archivos**: Todos los `*_listar.php`.
**Problema**: Los listados cargan TODOS los registros de una vez. Esto causa:
- Lag de renderización con muchos registros.
- Heavy load en la BD para cada visualización.
- Scroll extenso sin navegación.
**Propuesta** (en 2 fases):
1. **Fase 1 — Paginación server-side**: Agregar `LIMIT/OFFSET` a los SP de listado. Pasar `page` y `pageSize` como filtros en `p_in_json`.
2. **Fase 2 — Grid mejorado**: Integrar una librería JS de grid (Simple-DataTables o similar) para sort, filtrado y paginación client-side como complemento.

### 🟡 3.2. Sin ordenamiento por columnas
**Archivos**: Todos los `*_listar.php`.
**Problema**: Los headers de tabla no son clickeables para ordenar.
**Propuesta**: Combinar con solución de grid (3.1 Fase 2).

---

## 4. UX/UI — Responsive

### 🟡 4.1. Menú lateral no colapsa en mobile
**Archivo**: `menu.php`, `head.php`.
**Problema**: El sidebar es fijo y no tiene mecanismo de hamburguesa para resoluciones < 768px.
**Propuesta**:
- Agregar botón toggle en el header para mobile.
- Colapsar sidebar con CSS media query + JS toggle.
- Sidebar como offcanvas de Bootstrap en mobile.

### 🟡 4.2. Formularios de transacciones no responsive
**Archivos**: `prodleche_crear.php` (38 KB), `suplanimal_crear.php` (34 KB), `retiroleche_crear.php` (11 KB).
**Problema**: Los formularios complejos (con tablas de detalle inline) no se adaptan a pantallas < 768px. Las tablas de detalle editable son difíciles de usar en mobile.
**Propuesta**:
- Revisar y rehacer el grid de estos formularios con clases responsivas de Bootstrap (`col-12 col-md-6 col-lg-4`).
- Para tablas de detalle editable en mobile: evaluar layout card-stack en lugar de tabla.

---

## 5. CSS y Estilos

### 🟡 5.1. CSS inline extenso
**Archivos**: 31+ archivos con atributos `style="..."`.
**Problema**: Estilos inline dificultan la mantenibilidad, no se benefician de cache, y no siguen BEM.
**Propuesta**:
- Extraer estilos inline a clases CSS con nomenclatura BEM (`pdh-*`).
- Crear un archivo `assets/css/puduhue.css` para clases personalizadas.
- Eliminar todos los `style="..."` de las vistas.

### 🟡 5.2. Inconsistencia en contenedores
**Hallazgo**: Algunos listados usan `.container` y otros `.container-fluid px-4` o `.container mt-4`.
**Propuesta**: Estandarizar: listados → `.container-fluid px-4`, formularios → `.container mt-3`.

---

## 6. Arquitectura y Código

### 🟠 6.1. Código ERP duplicado entre servicios
**Archivos**: `ProdlecheService.php` (línea 430) y `SuplanimalService.php` (línea 477).
**Problema**: Ambos servicios contienen lógica idéntica para:
- Obtener/renovar token ERP (`SELECT access_token FROM erptokenactivo`).
- Llamar al endpoint de Finnegans.
- Manejar token expirado.
**Propuesta**:
- Centralizar en `src/api-external/FinnegansClient.php` (que ya existe).
- Los servicios deben delegar al client en lugar de reimplementar.

### 🟡 6.2. Archivos de edición vacíos
**Archivos**:
- `usuariosempresas_editar.php` — 44 bytes.
- `usuariosfundos_editar.php` — 42 bytes.
**Problema**: Archivos prácticamente vacíos pero referenciados por el router.
**Propuesta**:
- Si la edición no aplica para estas relaciones N:M (solo crear/eliminar), eliminar los archivos y quitar las rutas del router.
- Si la edición debe implementarse, construir las vistas.

### 🟡 6.3. Archivo backup en producción
**Archivos**:
- `prodleche_crear_bak_20251215.php` (17.9 KB).
- `dashboard copy.php` (4.5 KB).
**Problema**: Archivos backup no deberían existir en producción.
**Propuesta**: Eliminar y confiar en el control de versiones (Git).

### 🟡 6.4. Archivos legacy en `src/Core/`
**Archivos**:
- `src/Core/Database.php` (8.5 KB).
- `src/Core/DBConfig.php` (734 bytes).
- `src/Core/Env.php` (1.5 KB).
**Problema**: Documentados en ADR-006 como legacy. Sin referencias activas pero aún presentes.
**Propuesta**:
- Verificar con `grep` que no existen referencias.
- Mover a una carpeta `_deprecated/` o eliminar.

### 🟢 6.5. `AuthController.php` casi vacío
**Archivo**: `src/Controllers/Web/AuthController.php` (226 bytes).
**Problema**: Un controller de 226 bytes probablemente no contiene lógica. Es coherente con que el login está en la vista (propuesta 1.2).
**Propuesta**: Implementar la lógica de auth aquí como parte de la migración del login.

### 🟢 6.6. Carpeta `lib/` vacía
**Problema**: Carpeta sin contenido.
**Propuesta**: Eliminar si no se planea usar.

---

## 7. Archivos en la Raíz del Proyecto

### 🟡 7.1. Archivos sueltos que no son parte del código
**Archivos**:
- `tmp_eval.php` (112 bytes) — script temporal.
- `tmp_output.html` (55 KB) — salida HTML temporal.
- `control_produccion_leche_reporte.jpeg` (137 KB).
- `postman-api-prodlechedetalle-msg.png` (66 KB).
- `postman-token.png` (12 KB).
- `suplementacion_animal_crear_editar_propuesta.png` (44 KB).
- `ProdLeche_Datos_Historicos_cargar_2024_S2.xlsx` (84 KB).
- `files_create.bat` (2.7 KB).

**Propuesta**:
- Crear carpeta `docs/client/` para documentación del cliente (imágenes de referencia, capturas, Excel de datos).
- Mover las imágenes y Excel allí.
- Mover `files_create.bat` a `scripts/` (o `docs/playbooks/`).
- Eliminar `tmp_eval.php` y `tmp_output.html`.

---

## 8. Base de Datos y SPs

### 🟡 8.1. SP de listado sin paginación
**Archivos**: Todos los `02_sp_*.sql` con SPs `_listar_resumen` y `_listar_detalle`.
**Problema**: Los SPs devuelven todos los registros sin `LIMIT/OFFSET`.
**Propuesta**:
- Agregar parámetros `filtroPage` y `filtroPageSize` en `p_in_json`.
- Agregar `LIMIT ? OFFSET ?` al final de los `SELECT`.
- Retornar el total de registros (sin LIMIT) para controlar la paginación en frontend.

### 🟢 8.2. Regenerar SPs desde CSV
**Observación**: Los generadores (`generate_tables_from_csv.php`, `generate_sp_from_csv.php`) podrían incorporar la lógica de paginación automáticamente.
**Propuesta**: Actualizar el generador para incluir LIMIT/OFFSET en los SPs de listado generados.

---

## 9. Testing y DevOps

### 🟠 9.1. Sin ambiente de desarrollo local
**Problema**: Se deploya directamente a producción.
**Propuesta**: Ver `docs/playbooks/local_dev_setup.md` — ya documentado.

### 🟡 9.2. Sin tests automatizados
**Problema**: No existe PHPUnit ni ningún framework de testing.
**Propuesta**: Implementar tests por fases según `docs/06_testing.md`.

---

## 10. Resumen de Acciones Prioritarias

| #   | Propuesta                              | Prioridad | Esfuerzo | Riesgo |
|-----|----------------------------------------|-----------|----------|--------|
| 1.1 | CSRF tokens en formularios             | 🔴        | Medio    | Bajo   |
| 1.2 | Extraer login de vista a controller    | 🔴        | Medio    | Medio  |
| 3.1 | Paginación server-side                 | 🟠        | Alto     | Medio  |
| 6.1 | Unificar código ERP duplicado          | 🟠        | Bajo     | Bajo   |
| 9.1 | Configurar ambiente local              | 🟠        | Medio    | Bajo   |
| 2.1 | Migrar alertas a toasts                | 🟡        | Bajo     | Bajo   |
| 4.1 | Menú responsive                        | 🟡        | Medio    | Bajo   |
| 5.1 | Extraer CSS inline a clases BEM        | 🟡        | Alto     | Bajo   |
| 7.1 | Limpiar archivos raíz                  | 🟡        | Bajo     | Bajo   |
| 6.3 | Eliminar archivos backup               | 🟡        | Bajo     | Bajo   |
| 6.4 | Retirar archivos legacy `src/Core/`    | 🟡        | Bajo     | Bajo   |
| 8.1 | Paginación en SPs                      | 🟡        | Medio    | Medio  |

> [!TIP]
> Se recomienda abordar primero las propuestas 🔴 y 🟠, que son de seguridad e infraestructura. Las propuestas 🟡 se pueden abordar en sprints de mejora continua. Cada propuesta significativa debería documentarse como un **ADR** antes de implementarse.
