# Scope — Alcance del Proyecto Puduhue App Web

## 1. Alcance Actual (en producción)

### 1.1. Módulos Operativos

#### Transacciones
- **Producción de Leche** — CRUD completo + integración Finnegans + reportes + visualización.
- **Retiro de Leche** — CRUD completo + upload de voucher por estanque.
- **Suplementación Animal** — CRUD completo + integración Finnegans + visualización.

#### Administración
- Empresas, Usuarios, Usuarios-Empresas, Usuarios-Fundos, Menús, Perfiles, Perfiles-Menús, Cambio de contraseña.

#### Maestros
- Fundos, Fundos Tipos, Fundos Estanques, Fundos Estanques Clientes, Tipos de Leche, Inventario Bodegas, Inventario Ítems, Inventario Categorías Ganado, Inventario Unidades de Medida, Clientes.

#### Reportes y Proyecciones
- Reporte de Producción de Leche, Presupuesto Leche Mensual, Proyección Leche Diaria, Reporte Leche BI.

#### API Externa
- `POST /v1/prodleche-detalle/query` — Implementado.
- `POST /v1/suplanimal-detalle/query` — Base funcional.

#### Integraciones
- Finnegans ERP (Producción de Leche + Suplementación Animal).
- Power BI (vía API externa).
- reCAPTCHA Enterprise (login).

---

## 2. Mejoras Inmediatas (corto plazo)

> [!IMPORTANT]
> Estas mejoras se realizarán antes de agregar nuevos módulos.

### 2.1. UX/UI — Responsive Layout
- Corregir responsive del menú lateral para resoluciones < 768px.
- Corregir pantallas que no se adaptan correctamente a dispositivos móviles/tablet.
- Adoptar CSS BEM como estándar de nomenclatura CSS.

### 2.2. Grids de Datos (tablas de listado)
- Implementar paginación en todos los listados.
- Implementar ordenamiento por columnas (sort).
- Resolver lag de renderización cuando hay muchos registros.
- Evaluar e implementar solución de grid (DataTables, AG Grid lite, o similar compatible con Bootstrap 5).

### 2.3. Feedback al Usuario
- Migrar todos los `alert()` y `alert-danger`/`alert-success` intrusivos a **toasts no bloqueantes**.
- Unificar el mecanismo de feedback en un helper JS reutilizable.

### 2.4. Limpieza de Código
- Eliminar archivos backup/legacy: `prodleche_crear_bak_20251215.php`, `dashboard copy.php`.
- Eliminar/retirar archivos legacy de `src/Core/` (`Database.php`, `DBConfig.php`, `Env.php`).
- Mover archivos temporales de la raíz (`tmp_eval.php`, `tmp_output.html`, imágenes, `.xlsx`) a una carpeta de documentación del cliente.
- Eliminar carpeta `lib/` vacía.
- Resolver archivos vacíos: `usuariosempresas_editar.php`, `usuariosfundos_editar.php`.

### 2.5. Infraestructura de Desarrollo
- Crear `.gitignore`.
- Configurar ambiente de desarrollo local (XAMPP/Laragon u otro).
- Establecer flujo de deploy (al menos 2 ambientes: local y producción, o staging y producción en servidor).

### 2.6. Estandarización CSS
- Evaluar adopción de **Tailwind CSS** (compilado localmente, se sube solo el CSS resultante al hosting).
- Si se adopta Tailwind: definir workflow de compilación (`npx tailwindcss` en desarrollo, subir `output.css` al hosting).
- Si se mantiene Bootstrap 5: documentar clases personalizadas con BEM.

---

## 3. Nuevos Módulos (mediano plazo)

> [!WARNING]
> **Decisión pendiente**: Se está evaluando si estos módulos se integrarán en este mismo proyecto o si se iniciará un proyecto nuevo.

### 3.1. Módulo de Requerimientos
- Requerimientos de **Materiales**.
- Requerimientos de **Servicios**.
- Flujo de **Autorización** de requerimientos.

### 3.2. Módulo de Pre Orden de Compra
- Generación de Pre OC a partir de requerimientos aprobados.
- **Presupuesto de Compra** asociado a la Pre OC.
- Flujo de **Autorización** de Pre OC.

### 3.3. Integración OC → Finnegans
- Al aprobar una Pre OC, se integra a Finnegans como una **Orden de Compra real**.
- Requiere nuevo endpoint de integración con el ERP.

### 3.4. Módulo de Presupuestos
- Gestión integral del mundo de presupuestos asociados a las Pre OC.

---

## 4. Fuera de Alcance

- Migración a framework PHP (Laravel, Symfony, etc.) — no aplica por restricciones de hosting.
- Aplicación móvil nativa — no planificada actualmente.
- Multi-tenancy SaaS — el sistema es de uso exclusivo interno de Capac SpA.
- Migración de hosting — el cliente no desea invertir en infraestructura cloud.

---

## 5. Riesgos Identificados

| Riesgo | Impacto | Mitigación |
|--------|---------|------------|
| Deploy directo a producción sin staging | Alto | Configurar ambiente local o staging |
| Sin tests automatizados | Alto | Implementar tests mínimos para funciones críticas |
| Archivos legacy en producción | Medio | Limpieza planificada |
| No hay `.gitignore` (posible commit de `.env`, vendor, etc.) | Alto | Crear `.gitignore` inmediatamente |
| Grids sin paginación con datasets grandes | Medio | Implementar paginación server-side |
| Responsive layout incompleto | Medio | Auditoría CSS + correcciones |
