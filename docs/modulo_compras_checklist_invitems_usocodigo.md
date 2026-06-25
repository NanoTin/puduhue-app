# Checklist corte invitems uso funcional

## Objetivo

Agregar `invitemusocodigo` como atributo local de transición antes de reactivar la sincronizacion de Productos ERP.

## Reglas

- Mantener `invitemleche` temporalmente por compatibilidad.
- No eliminar ni reemplazar flags historicos.
- Default para datos existentes y nuevos: `BDG`.
- Asignar `LCH`, `ALM` o `CMB` por pantalla o UPDATE controlado despues de revisar los items.
- Validar Produccion de Leche, Suplementacion Animal y Tipos de Leche antes de reactivar Productos.

## Codigos de uso

| Codigo | Uso |
|---|---|
| `BDG` | Bodega/base/default. |
| `LCH` | Produccion de Leche / Tipos de Leche. |
| `ALM` | Suplementacion Animal. |
| `CMB` | Combustible futuro. |

## Cambios implementados

- `database/tables/01_table_invitems.sql`
  - agrega `invitemusocodigo varchar(10) NOT NULL DEFAULT 'BDG'`;
  - agrega indice `idx_invitems_usocodigo`.
  - agrega columnas base para sincronizacion futura de Productos ERP:
    - `familiaid`;
    - `subfamiliaid`;
    - `erptasaimpositivaid`;
    - `erppartidafinancieraid`;
    - `invitemcompra`;
    - `invitemcostoestandar`;
    - `invitemcostoestandarfechahora`.
- `database/alter_table/06_invitems_usocodigo.sql`
  - script incremental para bases existentes.
- `database/sp/02_sp_invitems.sql`
  - inserta, edita, lista y respalda `invitemusocodigo` y columnas base de Producto ERP.
- Pantallas `invitems`
  - listar/filtrar/ver uso funcional;
  - crear/editar uso funcional.
  - crear/editar familia, subfamilia, tasa compra, partida financiera, compra y costo estandar.
  - si `invitemcostoestandar > 0`, la edicion manual del costo queda bloqueada en pantalla.
- Exportacion Excel de `invitems`
  - incluye columna y filtro de uso.
- Selectores consumidores
  - Tipos de Leche solicita `LCH` con fallback a `invitemleche = 1` si el item sigue en `BDG`.
  - Suplementacion Animal solicita `ALM` con fallback a `invitemstockeable = 1` si el item sigue en `BDG`.
- Sincronizacion ERP Productos
  - `ErpProductosSyncService` sincroniza `ERP_PRODUCTOS_LIST` y consulta `ERP_PRODUCTOS_DETALLE` por codigo.
  - El match se realiza por `invitems.erpinvitemcod`.
  - No sobrescribe `erpinvitemcod`, `invitemusocodigo` ni `invitemleche` en registros existentes.
  - Inserta productos nuevos con `invitemusocodigo = BDG` e `invitemleche = 0`.
  - Si un codigo ERP deja de venir en la lista, marca `invitemactivo = 0`.
  - Si falta una dependencia obligatoria, por ejemplo unidad de medida ERP `HA`, omite ese producto y deja la ejecucion en estado `PARCIAL`.
  - Registra conteos en `erplistadoendpointslog` y cambios locales en `invitemslog`.

## Validacion funcional pendiente

- [ ] Ejecutar script incremental en BD.
- [ ] Actualizar SP `sp_invitems_insertar`, `sp_invitems_editar`, `sp_invitems_listar`.
- [ ] Revisar listado `invitems`.
- [ ] Asignar `LCH` a items de leche requeridos.
- [ ] Asignar `ALM` a items de suplementacion requeridos.
- [ ] Validar crear/editar `invitems`.
- [ ] Validar que costo estandar solo sea editable cuando es cero.
- [ ] Validar `prodlechetipos` crear/editar.
- [ ] Validar Produccion de Leche crear/editar/visualizar.
- [ ] Validar Suplementacion Animal crear/editar/visualizar.
- [ ] Validar sincronizacion `ERP_PRODUCTOS_LIST` desde diagnostico ERP.
- [ ] Confirmar logs de `erplistadoendpointslog` e `invitemslog`.

## Regla DIMPARFIN

`erppartidafinancieraid` no se usa en Presupuesto. Se guarda en `invitems` para construir la entidad `DimensionDistribucion` de PreOC.

Origen en Producto Detalle ERP:

```json
"Dimensiones": [
  {
    "DimensionCodigo": "DIMPARFIN",
    "DimensionDistribucionCodigo": "ACO000"
  }
]
```

Regla:

- buscar dentro de `Dimensiones[]` el objeto con `DimensionCodigo = "DIMPARFIN"`;
- tomar `DimensionDistribucionCodigo`;
- resolverlo contra `erppartidasfinancieras.erppartidafinancieracod`;
- guardar la PK en `invitems.erppartidafinancieraid`.

Uso futuro en PreOC:

- `dimensionCodigo`: `DIMPARFIN`;
- `distribucionCodigo`: codigo ERP de la partida financiera;
- `distribucionItems[0].codigo`: mismo codigo ERP;
- `importe`: subtotal de la linea.
