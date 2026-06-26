# Modulo de Compras - Brechas Documentacion vs SQL Existente

> Revision puntual antes de programar.
>
> Alcance leido:
> - `docs/modulo_compras_req_estructura.md`
> - `docs/modulo_compras_preoc_estructura.md`
> - `docs/modulo_compras_presupuesto_definitivo.md`
> - `docs/modulo_compras_plan_bases_compartidas.md`
> - `docs/modulo_compras_backlog_bases_compartidas.md`
> - `database/tables/01_table_pptocompra.sql`
> - `database/tables/01_table_pptocompramensual.sql`
> - `database/tables/01_table_pptocompratransacciones.sql`
> - `database/tables/01_table_pptocompratransaccionestipo.sql`
> - `database/tables/01_table_usuarios.sql`
> - `database/tables/01_table_invitems.sql`
> - `database/tables/01_table_centroscosto.sql`
> - `database/tables/01_table_temporadas.sql`
> - `database/tables/01_table_familias.sql`
> - `database/tables/01_table_subfamilias.sql`
> - `database/tables/01_table_erptasasimpositivas.sql`
> - `database/tables/01_table_erppartidasfinancieras.sql`
> - `database/tables/01_table_erpmonedas.sql`
> - `database/alter_table/05_modulo_compras_bases.sql`
> - `database/sp/02_sp_pptocompra.sql`
>
> Documento siguiente recomendado:
> - `docs/modulo_compras_corte_sql_previo.md`

## 1. Resumen ejecutivo

El SQL existente cubre parcialmente las bases compartidas del modulo de Compras: presupuesto de compra, temporadas, familias, subfamilias, centros de costo, items y algunos catalogos ERP.

No existe aun SQL para REQ ni para PreOC. Las estructuras documentadas de requerimientos, pendientes de compra, firmantes, comentarios, snapshots, PreOC, impuestos, dimensiones y resumen presupuestario siguen siendo definiciones funcionales/documentales, no implementacion SQL.

Antes de programar conviene cerrar un corte de DDL incremental, separado por bloques:

1. bases compartidas faltantes,
2. REQ,
3. pendientes de compra,
4. PreOC,
5. SP/servicios de presupuesto para reserva, confirmacion y reversa.

## 2. Cobertura actual

| Bloque | Estado SQL | Observacion |
|---|---|---|
| Temporadas `PPTO_COMPRAS` | Parcial/avanzado | Existe tabla y alter con tipo de temporada e indices. |
| Presupuesto compra | Parcial/avanzado | Existen cabecera, mensual, transacciones, tipos y SP base. |
| Familias/subfamilias | Existe base | Existen tablas e indices principales. |
| Centros de costo | Existe base | Tiene jefe de CC, jefe tecnico y gerente produccion como atributos locales. |
| Items | Existe base parcial | Tiene familia, subfamilia, tasa impositiva, partida financiera, compra, costo estandar, uso y activo. |
| Usuarios | Existe base general | No contiene atributos nuevos de compras/aprobaciones. |
| Usuarios-centros | No existe | Requerido para resolver centros del solicitante. |
| Funcionarios | No encontrado | La documentacion lo define, pero no se encontro DDL asociado. |
| Aprobadores inactividad | No existe | Requerido por REQ y PreOC. |
| Proveedores | No existe tabla | Solo hay endpoints declarados en `init_erplistadoendpoints.sql`. |
| Condiciones de pago | No existe tabla | Solo hay endpoints declarados. |
| REQ | No existe | No hay tablas ni SP `reqcompras*`. |
| PreOC | No existe | No hay tablas ni SP `preoc*`. |
| ERP impuestos/monedas/partidas | Parcial | Existen tasas impositivas, monedas y partidas financieras. |

## 3. Brechas por bloque

### 3.1 Presupuesto de compra

Existe:

- `pptocompra`
- `pptocompramensual`
- `pptocompratransacciones`
- `pptocompratransaccionestipo`
- `pptocompralog`
- `sp_pptocompra_*`

Brechas contra documentacion:

- `pptocompra` no tiene:
  - `pptocompraresponsableid`,
  - `pptocompraadministradorid`,
  - `pptocompracolaboradorid`.
- Los SP de presupuesto no reciben, validan, guardan ni listan esos tres usuarios aprobadores.
- Los SP actuales resuelven carga, ajustes, traspasos y recalculo, pero no exponen aun operaciones especificas para:
  - reserva PreOC `BRR -> PND`,
  - confirmacion al aprobar PreOC,
  - reversa por rechazo/anulacion,
  - reversa al volver de `PND` a `BRR` antes de aprobaciones.
- Los tipos `POC_RESERVA`, `POC_CONFIRMACION` y `POC_REVERSA` existen, pero falta cerrar su uso operativo desde PreOC.

### 3.2 Usuarios

Existe tabla `usuarios`, pero faltan los atributos documentados:

- `usuariopermiteaprobreq`,
- `usuariopermiteaprobpreoc`,
- `usuariocomprador`,
- `usuariopermiteanularpreoc`,
- `usuariopermiteeditarprecios`,
- `usuariopermitecrearitem`,
- `usuariopermiteeditaritem`,
- `usuariopermitesynctrnerp`,
- `usuarioreqautorizadorfuerapptocompra`,
- `usuarioreqautorizadorfuerapptocompraorden`.

Tambien faltan indices/reglas para:

- listar compradores,
- listar aprobadores REQ activos,
- listar aprobadores PreOC activos,
- asegurar orden unico de autorizadores fuera de presupuesto cuando `usuarioreqautorizadorfuerapptocompra = 1`.
- Los SP `sp_usuarios_insertar`, `sp_usuarios_editar` y `sp_usuarios_listar` no reciben ni exponen estos atributos.

### 3.3 Usuarios-centros de costo

No existe `usuarioscentroscosto`.

Brechas documentadas:

- relacion `usuarioid + centrocostoid`,
- estado activo/inactivo,
- `usucendefault`,
- regla de un solo centro default por usuario,
- soporte para error controlado al crear REQ cuando el usuario no tiene centros asignados.

### 3.4 Funcionarios

No se encontro DDL de maestro de funcionarios. Se define crear tabla propia, mas BE, FE y carga masiva por Excel.

Brechas documentadas:

- PK por Rut validado, sin autoincremental,
- centro asociado `funcencos`,
- funcionario opcional al crear REQ,
- trazabilidad/log si se implementa como maestro mantenible.

### 3.5 Centros de costo

Existe `centroscosto` con:

- `centrocostocod`,
- `centrocostodsc`,
- `erpcentrocostocod`,
- `centrocostojefeusuarioid`,
- `centrocostojefetecnicoid`,
- `centrocostogerenteproduccionid`,
- `centrocostoactivo`.

Brechas o ajustes:

- La documentacion vigente descarta resolver empresa desde ERP. La tabla conserva `empresaid`; debe tratarse como local/no usado por integracion mientras no exista definicion cliente.
- No se observaron FK explicitas hacia `usuarios` para jefe/jefe tecnico/gerente produccion.
- Falta confirmar si `centrocostojefetecnicoid` es el nombre final o si se renombrara para expresar usuario aprobador.

### 3.6 Items

Existe `invitems` con:

- `familiaid`,
- `subfamiliaid`,
- `erptasaimpositivaid`,
- `erppartidafinancieraid`,
- `invitemcompra`,
- `invitemcostoestandar`,
- `invitemusocodigo`,
- `invitemactivo`.

Brechas contra documentacion:

- Falta `iteminglocal` o nombre equivalente para marcar item ingresado localmente.
- Material/Servicio no requiere atributo nuevo: se resuelve con `invitemstockeable` (`1` Material, `0` Servicio), complementado con `invitemcompra = 1` para REQ/PreOC.
- Los SP `sp_invitems_insertar`, `sp_invitems_editar` y `sp_invitems_listar` ya consideran familia, subfamilia, tasa, partida, compra, costo estandar y uso funcional.
- Los SP `sp_invitems_*` no validan permisos de crear/editar item y no limitan la edicion local a:
  - precio cuando es cero,
  - uso funcional,
  - activar/desactivar.

### 3.7 Proveedores y condiciones de pago

Existen endpoints en `init_erplistadoendpoints.sql`:

- `ERP_PROVEEDORES_LIST`
- `ERP_PROVEEDORES_DETALLE`
- `ERP_CONDICIONES_PAGO_LIST`
- `ERP_CONDICIONES_PAGO_DETALLE`

Definiciones y brechas:

- No existen tablas locales de proveedores.
- No existen tablas locales de condiciones de pago.
- Se definen como maestros espejo ERP con la misma logica de productos: primero `list`, luego detalle por codigo.
- Proveedores usa `ERP_PROVEEDORES_LIST` y `ERP_PROVEEDORES_DETALLE`.
- Condiciones de pago usa `ERP_CONDICIONES_PAGO_LIST` y `ERP_CONDICIONES_PAGO_DETALLE`.
- Se requiere tabla puente proveedor-condicion de pago porque el detalle de proveedor trae `CondicionesPago`.
- Ambas pantallas son de consulta y exportacion a Excel.
- PreOC no deberia avanzar sin proveedor y condicion de pago resolubles.

### 3.8 Aprobadores por periodo de inactividad

No existen:

- `aprobadoresperiodoinactividad`,
- `aprobadoresperiodoinactividadlog`.

Brechas:

- regla de no eliminar, solo inactivar,
- reemplazante activo,
- motivo/tipo de inactividad,
- vigencia por fechas,
- uso compartido por REQ y PreOC,
- soporte para resolver siguiente aprobador.

### 3.9 REQ

No existen tablas ni SP para:

- `reqcompras`,
- `reqcomprasdetalle`,
- `reqcomprasfirmantes`,
- `reqcomprascomentarios`,
- `reqcompraslog`,
- `reqcomprasestados`,
- `reqcompraestadopreoc`,
- `reqcompraspptosnapshot`,
- `reqaprobados`,
- `reqaprobadoshistorial`,
- `reqaprobadoscambios`.

Brechas principales:

- estados `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL`,
- estado PreOC separado,
- advertencia presupuestaria informativa,
- snapshot de analisis de presupuesto,
- firmantes default/manuales/fuera de presupuesto,
- aprobador pendiente en cabecera,
- historial de aprobados y anulaciones parciales,
- validacion de ultimo requerimiento por centro-item.

### 3.10 PreOC

No existen tablas ni SP para:

- `preoc`,
- `preocdetallereqitems`,
- `preocitems`,
- `preocimptos`,
- `preocitemsdimensiones`,
- `preocpptoresumen`,
- `preocfirmantes`,
- `preoccomentarios`,
- `preoclog`,
- `preocestados`,
- `preocestadoserp`,
- `preocaprobadoresxmonto`.

Brechas principales:

- cabecera con `preocfechaoc`, proveedor, condicion pago, moneda, observaciones, prioridad, estado ERP, aprobador pendiente y totales,
- detalle origen desde `reqaprobados`,
- agrupacion de items para precio y totales,
- impuestos multiples por item agrupado,
- dimensiones ERP por confirmar a nivel item o req-item,
- resumen presupuestario de apoyo,
- firmantes default por presupuesto y por monto,
- reserva/confirmacion/reversa presupuestaria.

## 4. Contradicciones o puntos sensibles detectados

1. `centroscosto.empresaid` existe, pero la definicion funcional vigente indica no separar centros por empresa desde ERP.
2. `pptocompra` esta funcionalmente definida como fuente de firmantes default PreOC, pero la tabla SQL aun no tiene los tres usuarios responsables.
3. La documentacion de PreOC depende de proveedores/condiciones de pago, pero hoy solo existen endpoints registrados, no maestros locales.
4. La documentacion de REQ y PreOC usa aprobador pendiente denormalizado en cabecera, pero no existe todavia ninguna tabla transaccional para materializar esa regla.
5. Las dimensiones ERP estan parcialmente cubiertas por items/centros. Se define que `preocitemsdimensiones` cuelgue operativamente de `preocdetallereqitems`, con `preocitemid` nullable como apoyo si se requiere consulta por item agrupado.

## 5. Recomendacion de corte SQL antes de programar

Orden sugerido:

1. Incremental de bases compartidas:
   - usuarios permisos compras,
   - usuarios-centros,
   - funcionarios,
   - aprobadores-periodo-inactividad,
   - `pptocompra` responsables PreOC,
   - `invitems.iteminglocal`,
   - proveedores/condiciones pago con logica list + detalle.
2. DDL REQ completo, con estados y logs.
3. DDL pendientes de compra (`reqaprobados*`).
4. DDL PreOC completo, con `preocitemsdimensiones` a nivel req-item origen.
5. SP/servicios presupuestarios para reserva, confirmacion y reversa.
6. Revalidar SP existentes para incluir columnas nuevas en listar/crear/editar/log.

No se recomienda iniciar pantallas o endpoints REQ/PreOC hasta cerrar, como minimo, el punto 1 y el DDL base de REQ.

El detalle del orden propuesto por incrementales queda desarrollado en `docs/modulo_compras_corte_sql_previo.md`.
