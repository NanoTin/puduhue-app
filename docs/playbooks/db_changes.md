# Playbook: Cambios en Base de Datos

## Objetivo
Procedimiento seguro para aplicar cambios estructurales en la base de datos (nuevas tablas, ALTER TABLE, nuevos SPs).

---

## 1. Tipos de Cambios

| Tipo                      | Archivo                              | Riesgo  |
|---------------------------|--------------------------------------|---------|
| Nueva tabla               | `database/tables/01_table_*.sql`     | Bajo    |
| ALTER TABLE (add column)  | `database/alter_table/XX_*.sql`      | Medio   |
| ALTER TABLE (drop column) | `database/alter_table/XX_*.sql`      | **Alto**|
| Nuevo SP                  | `database/sp/02_sp_*.sql`            | Bajo    |
| Modificar SP existente    | `database/sp/02_sp_*.sql`            | Medio   |
| Datos iniciales           | `database/init_*.sql`                | Bajo    |

---

## 2. Procedimiento

### 2.1. Actualizar la Fuente de Verdad (CSVs)

Si el cambio nace de una nueva columna o tabla:
1. Editar `database/spec/Tables.csv` (si es tabla nueva).
2. Editar `database/spec/Columns.csv` (si es columna nueva/modificada).
3. Regenerar scripts:
   ```bash
   php database/generate_tables_from_csv.php
   php database/generate_sp_from_csv.php
   ```
4. Revisar los archivos generados antes de ejecutar.

### 2.2. Crear Script ALTER TABLE

Para cambios en tablas existentes, crear un archivo numerado:
```
database/alter_table/XX_descripcion.sql
```

Ejemplo:
```sql
-- database/alter_table/05_agregar_columna_telefono_clientes.sql
-- Fecha: 2026-03-19
-- Descripción: Agregar columna de teléfono a tabla clientes

ALTER TABLE `clientes`
ADD COLUMN `clientetelefono` VARCHAR(20) NULL DEFAULT NULL
AFTER `clientenombre`;
```

> [!IMPORTANT]
> Siempre incluir un comentario con fecha y descripción del cambio.

### 2.3. Preparar Rollback

Antes de ejecutar, documentar el rollback:
```sql
-- ROLLBACK:
-- ALTER TABLE `clientes` DROP COLUMN `clientetelefono`;
```

---

## 3. Ejecución

### 3.1. En Ambiente Local
1. Abrir phpMyAdmin local o cliente SQL.
2. Seleccionar la BD de desarrollo.
3. Ejecutar el script.
4. Verificar que no haya errores.
5. Probar la funcionalidad afectada.

### 3.2. En Producción
1. Abrir phpMyAdmin del hosting (cPanel).
2. Seleccionar la BD de producción.
3. **Antes de ejecutar**: verificar que el script fue probado en local.
4. Ir a pestaña "SQL".
5. Pegar y ejecutar el script.
6. Verificar resultado.

---

## 4. Stored Procedures

### 4.1. Actualizar SP Existente
Los scripts de SP usan `DROP PROCEDURE IF EXISTS` + `CREATE PROCEDURE`, por lo que es seguro re-ejecutar:

```sql
DELIMITER $$
DROP PROCEDURE IF EXISTS `sp_clientes_insertar`$$
CREATE PROCEDURE `sp_clientes_insertar`(...)
BEGIN
  -- nuevo contenido
END$$
DELIMITER ;
```

### 4.2. Verificar
- Llamar al SP manualmente desde phpMyAdmin con datos de prueba.
- O probar desde la aplicación web.

---

## 5. Registro del Cambio

Después de aplicar cambios:

1. **Actualizar `CHANGELOG.md`**:
   ```markdown
   ### Added
   - Se agregó columna `clientetelefono` a tabla `clientes`.
   - Se actualizó SP `sp_clientes_insertar` para incluir `clientetelefono`.
   ```

2. **Commit en Git** los archivos modificados:
   - `database/alter_table/XX_*.sql`
   - `database/sp/02_sp_*.sql`
   - `database/spec/Columns.csv` (si cambió)
   - `CHANGELOG.md`

---

## 6. Checklist

- [ ] CSV de especificación actualizado (si aplica).
- [ ] Script ALTER TABLE creado con rollback documentado.
- [ ] SP regenerado o editado manualmente.
- [ ] Probado en ambiente local.
- [ ] Ejecutado en producción.
- [ ] Verificación post-cambio.
- [ ] CHANGELOG.md actualizado.
- [ ] Commit en Git.
