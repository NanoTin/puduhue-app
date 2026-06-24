# Checklist corte maestros ERP pre-items

## Objetivo

Dejar sincronizados los maestros ERP requeridos antes de tocar productos/invitems.

## Reglas

- No modificar `invitems`.
- No cambiar Produccion de Leche.
- No cambiar Suplementacion Animal.
- No cambiar Tipos de Leche.
- No activar sincronizacion de Productos todavia.
- Registrar conteos reales en `erplistadoendpointslog`.
- Mantener logs locales por maestro.
- Validar con `php -l` y `git diff --check`.
- No ejecutar SQL contra BD salvo instruccion explicita del usuario.

## Orden

1. Partidas Financieras
   - Tabla: `erppartidasfinancieras`
   - Log: `erppartidasfinancieraslog`
   - Endpoint: `ERP_PARTIDAS_FINANCIERAS_LIST`
   - Uso futuro: `DIMPARFIN`
   - Estado: implementado y validado desde pantalla.

2. Unidades de Medida
   - Reutilizar tabla existente `invunidadesmedidas`
   - No romper pantallas actuales
   - Endpoint: `ERP_UNIDADES_MEDIDA_LIST`
   - Sincronizacion controlada por `erpunidmedcod`
   - Estado: implementado y validado desde pantalla reutilizando `invunidadesmedidaslog`.

3. Familias
   - Tabla: `familias`
   - Log local: `familiaslog`
   - Endpoint: `ERP_FAMILIAS_LIST`
   - Estado: implementado y validado desde pantalla.

4. Subfamilias
   - Tabla: `subfamilias`
   - Log local: `subfamiliaslog`
   - Endpoint list: `ERP_SUBFAMILIAS_LIST`
   - Endpoint detalle: `ERP_SUBFAMILIAS_DETALLE`
   - El detalle resuelve `ProductoFamiliaCodigo`
   - Estado: implementado y validado desde pantalla despues de Familias.

5. Tasas Impositivas
   - Tabla: `erptasasimpositivas`
   - Log local: `erptasasimpositivaslog`
   - Endpoint list: `ERP_TASAS_IMPOSITIVAS_LIST`
   - Endpoint detalle: `ERP_TASAS_IMPOSITIVAS_DETALLE`
   - El detalle resuelve porcentaje
   - Estado: implementado y validado desde pantalla.

## Archivos de tablas del corte

- `database/tables/01_table_erppartidasfinancieras.sql`
- `database/tables/01_table_erppartidasfinancieraslog.sql`
- `database/tables/01_table_familias.sql`
- `database/tables/01_table_familiaslog.sql`
- `database/tables/01_table_subfamilias.sql`
- `database/tables/01_table_subfamiliaslog.sql`
- `database/tables/01_table_erptasasimpositivas.sql`
- `database/tables/01_table_erptasasimpositivaslog.sql`

Unidades de Medida no crea tabla nueva; reutiliza `invunidadesmedidas` e `invunidadesmedidaslog`.

## Nota de seguridad

El endpoint `ERP_PRODUCTOS_LIST` quedo bloqueado durante este corte pre-items. El bloqueo se levanta en el corte posterior de `invitems`, una vez agregadas las columnas ERP/locales y validada la compatibilidad de Produccion de Leche, Suplementacion Animal y Tipos de Leche.

## Cierre

- [x] Documentar tablas creadas.
- [x] Conectar endpoints al boton Ejecutar GET.
- [x] Probar sincronizacion desde pantalla.
- [x] Confirmar logs y conteos.
- [x] Planificar corte `invitems`.

## Siguiente corte sugerido

Planificacion aplicada para reactivar Productos:

- agregar columnas nuevas sin romper compatibilidad;
- poblar `invitemusocodigo` con default `BDG`;
- asignar usos funcionales requeridos;
- validar Produccion de Leche, Suplementacion Animal y Tipos de Leche;
- mantener `invitemleche` temporalmente;
- reactivar `ERP_PRODUCTOS_LIST` solo despues de validar la migracion.
