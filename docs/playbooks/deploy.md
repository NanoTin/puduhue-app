# Playbook: Deploy a Producción

## Pre-requisitos
- Acceso a cPanel del hosting compartido.
- Acceso a phpMyAdmin.
- Archivos probados en ambiente local o staging.

---

## 1. Preparar los Archivos

### 1.1. Verificar cambios
```bash
# Revisar qué archivos cambiaron
git diff --name-only develop..feature/xxx
```

### 1.2. Confirmar que NO se subirán archivos sensibles
- ❌ `.env` (a menos que haya cambiado la estructura, en cuyo caso editar directamente en el servidor).
- ❌ `vendor/` (a menos que se hayan agregado/actualizado dependencias).
- ❌ Archivos temporales (`tmp_*`, backups).

### 1.3. Si hay cambios en Composer
```bash
composer install --no-dev
```
Se sube la carpeta `vendor/` completa al servidor.

---

## 2. Deploy de Archivos PHP

### 2.1. Vía File Manager de cPanel
1. Acceder a cPanel → File Manager.
2. Navegar a `/home/miempresa/public_html/webapp.miempresa.cl/`.
3. Subir los archivos modificados respetando la estructura de carpetas:
   - `apps/web-php/*.php` → vistas frontend.
   - `src/**/*.php` → capas de backend.
   - `apps/api-php/*.php` → API externa.
4. Verificar permisos (644 para archivos, 755 para carpetas).

### 2.2. Vía FTP/SFTP (alternativa)
1. Conectar con cliente FTP (FileZilla, WinSCP).
2. Host: según datos del hosting.
3. Subir los archivos modificados.

---

## 3. Deploy de Cambios en Base de Datos

### 3.1. Scripts nuevos de tablas (CREATE TABLE)
1. Abrir phpMyAdmin.
2. Seleccionar la base de datos del proyecto.
3. Ir a la pestaña "SQL".
4. Pegar y ejecutar el contenido de `database/tables/01_table_*.sql`.

### 3.2. Scripts de ALTER TABLE
1. Ejecutar los scripts de `database/alter_table/` en orden numérico.
2. Verificar que no haya errores de columna duplicada o FK faltante.

### 3.3. Scripts de Stored Procedures
1. Ejecutar los scripts de `database/sp/02_sp_*.sql`.
2. **Importante**: cada archivo contiene `DROP PROCEDURE IF EXISTS` + `CREATE PROCEDURE`, por lo que es seguro re-ejecutarlos.

### 3.4. Scripts de inicialización (solo en primera instalación)
- `database/init_empresa.sql`
- `database/init_root.sql`
- `database/init_menus.sql`
- `database/init_perfiles_menus.sql`

---

## 4. Verificación Post-Deploy

### 4.1. Smoke Tests
- [ ] Login funciona.
- [ ] Dashboard carga.
- [ ] Al menos 1 listado de maestro muestra datos.
- [ ] Al menos 1 transacción se puede crear.
- [ ] Exportar a Excel funciona.
- [ ] Si cambió API → probar 1 endpoint con Postman.
- [ ] Si cambió integración ERP → verificar un envío (no productivo si es posible).

### 4.2. Verificar Logs
- Revisar `storage/LOGS/` por errores recientes.
- Revisar `storage/APILog/` si se modificó la integración.

---

## 5. Rollback

### 5.1. Si fallan archivos PHP
- Resubir la versión anterior del archivo desde el último commit estable.

### 5.2. Si fallan cambios de BD
- Los SP se pueden revertir ejecutando la versión anterior del script.
- Para ALTER TABLE: preparar un script de rollback antes de ejecutar (ej. `DROP COLUMN` si se hizo `ADD COLUMN`).

> [!WARNING]
> Actualmente no existe un mecanismo automatizado de rollback. Antes de cada deploy, documentar qué archivos y scripts se modifican para facilitar la reversión manual.
