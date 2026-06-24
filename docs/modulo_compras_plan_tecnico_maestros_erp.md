# Modulo de Compras - Plan Tecnico de Maestros ERP y Bases Compartidas

> Documento de acuerdos tecnicos previos al desarrollo de pantallas, backend, tablas y stored procedures.
>
> Fuentes:
> - `docs/modulo_compras_plan_bases_compartidas.md`
> - `docs/modulo_compras_backlog_bases_compartidas.md`
> - `docs/modulo_compras_requerimientos_finales.md`
> - `docs/modulo_compras_presupuesto_definitivo.md`
> - `docs/modulo_compras_maestros_erp.md`
> - `docs/modulo_compras_mapeo_finnegans.md`
> - `docs/inputs/erp_maestros_endpoints/erp_endpoints_levantamiento_comentarios.md`
> - `docs/inputs/erp_maestros_endpoints/erp_listado_endpoints_v20260623.csv`
> - JSON reales en `docs/inputs/erp_maestros_endpoints/json_ejemplos_con_datos/`

## 1. Objetivo

Ordenar el desarrollo tecnico de los maestros ERP y bases compartidas requeridas antes de construir REQ, pendientes de compra, presupuesto operativo y PreOC.

El foco de esta etapa es dejar disponibles:

- maestro de endpoints ERP,
- sincronizacion de maestros espejo,
- tablas base transversales,
- extension controlada de `invitems`,
- centros de costo con atributos locales de aprobacion,
- reglas de ejecucion on-demand y automatica.

## 2. Principios aprobados

1. Los datos que vienen desde Finnegans son espejo ERP y no tienen CRUD libre en Puduhue App.
2. Los atributos locales autorizados se administran en Puduhue App y no deben ser sobrescritos por la sincronizacion ERP.
3. La PK interna de cada maestro sera un ID autoincremental.
4. El codigo ERP se guarda como dato unico adicional, no como PK principal.
5. Las tablas nuevas de maestros simples deben seguir el patron:
   - ID,
   - descripcion o nombre,
   - codigo ERP o externo,
   - activo,
   - fecha/hora de sincronizacion si aplica,
   - columnas de auditoria,
   - tabla log.
6. `invitems` se extiende, no se reemplaza.
7. `invitemleche` se mantiene temporalmente por compatibilidad, pero la seleccion funcional futura debe migrar a `invitemusocodigo`.
8. Leche y Suplementacion Animal mantienen por ahora sus endpoints POST actuales desde `.env`.
9. El nuevo maestro de endpoints parte por sincronizaciones GET de maestros ERP, pero queda preparado para futuros POST como Combustible.

## 3. Maestro `ERP_ListadoEndPoints`

### 3.1 Alcance inicial

El maestro administrara endpoints de Finnegans como recursos/path, sin `ACCESS_TOKEN`.

Nombre fisico inicial:

- `erplistadoendpoints`
- `erplistadoendpointslog`

El token:

- se calcula dinamicamente,
- se reutiliza si no vencio,
- se apoya en la logica existente de `FinnegansClient` y `erptokenactivo`,
- se agrega a cada llamada como `?ACCESS_TOKEN={token}`.

No se migra inicialmente:

- `ERP_API_URL_PRODLECH`,
- `ERP_API_URL_SUPLANML`.

Esos POST operativos actuales quedan fuera del primer alcance para evitar mezclar la base de maestros con integraciones transaccionales ya funcionando.

### 3.2 Columnas propuestas

| Columna | Uso |
|---|---|
| `erpendpointid` | PK AI. |
| `erpendpointcodigo` | Codigo funcional interno unico para semillas y servicios. |
| `erpendpointdescripcion` | Nombre funcional obligatorio. |
| `erpendpointrecurso` | Path/recurso sin token, por ejemplo `/api/producto/list`. |
| `erpendpointmetodo` | `GET` / `POST`. |
| `erpendpointtipo` | `BASE_ERP`, `CUSTOM`, `REPORT_VIEWER`. |
| `erpendpointproposito` | `MAESTRO_GET`, `DETALLE_GET`, `TRANSACCION_POST`. |
| `erpendpointgrupoid` | Grupo de ejecucion. |
| `erpendpointorden` | Orden dentro del grupo. |
| `erpendpointpadreid` | FK nullable al endpoint padre para consultas por codigo. |
| `erpendpointrequierecodigo` | 1 si el recurso usa un codigo de la fila padre. |
| `erpendpointpermiteondemand` | Habilita boton de sincronizacion manual. |
| `erpendpointpermiteauto` | Habilita sincronizacion automatica. |
| `erpendpointfrecuencia` | `DIARIO`, `SEMANAL`, `MENSUAL` o NULL. |
| `erpendpointdiaevento` | Dia de semana o dia del mes segun frecuencia. |
| `erpendpointhoraevento` | Hora sugerida si se usa scheduler propio. |
| `erpendpointformulariocall` | Formulario asociado para permisos/visibilidad. |
| `erpendpointjsonarchivoejemplo` | Nombre del JSON de ejemplo levantado. |
| `erpendpointultsync` | Fecha/hora ultima ejecucion. |
| `erpendpointultestado` | `OK`, `ERROR`, `PARCIAL` o NULL. |
| `erpendpointulterror` | Ultimo error resumido. |
| `erpendpointactivo` | Vigencia del endpoint. |
| Auditoria | Columnas estandar del proyecto. |

### 3.3 Tabla log

Debe existir `ERP_ListadoEndPointsLog` o nombre equivalente normalizado.

Datos minimos:

| Columna | Uso |
|---|---|
| `erpendpointlogid` | PK AI. |
| `erpendpointid` | FK al endpoint ejecutado. |
| `erpendpointlogtipoexec` | `MANUAL`, `AUTO`, `TECNICO`. |
| `erpendpointlogfechaini` | Inicio. |
| `erpendpointlogfechafin` | Fin. |
| `erpendpointlogestado` | `OK`, `ERROR`, `PARCIAL`. |
| `erpendpointlogmensaje` | Mensaje resumido. |
| `erpendpointlogregistrosleidos` | Cantidad leida desde ERP. |
| `erpendpointlogregistrosinsertados` | Cantidad insertada. |
| `erpendpointlogregistrosactualizados` | Cantidad actualizada. |
| `erpendpointlogregistrosinactivos` | Cantidad marcada inactiva. |
| `erpendpointlogrequestjson` | Request o metadata de ejecucion, sin credenciales. |
| `erpendpointlogresponsejson` | Respuesta resumida o diagnostico tecnico. |
| `usuarioid` | Usuario que ejecuto si aplica. |

El log no debe guardar tokens ni secretos.

## 4. Reglas de ejecucion por grupo

### 4.1 Grupo `0`

Los endpoints del grupo `0` son bases independientes y pueden ejecutarse individualmente.

Ejemplos:

- Dimensiones,
- Partidas financieras,
- Centros de costo,
- Monedas.

### 4.2 Grupos `1+`

Los grupos mayores o iguales a `1` se ejecutan completos y respetando `erpendpointorden`.

Ejemplo grupo `1`:

1. Tasas impositivas list,
2. tasas impositivas por codigo,
3. unidades de medida,
4. familias,
5. subfamilias list,
6. subfamilias por codigo,
7. productos list,
8. productos por codigo.

Cuando el usuario presiona sincronizar en Productos, se debe ejecutar el grupo completo para asegurar dependencias.

### 4.3 Endpoints hijos

Los endpoints de consulta por codigo deben relacionarse con su padre mediante `erpendpointpadreid`.

Ejemplos:

- `producto/list` es padre de `producto/{codigo}`.
- `productoSubfamilia/list` es padre de `productoSubfamilia/{codigo}`.
- `condicionPago/list` es padre de `condicionPago/{codigo}`.
- `PDHProveedor/list` es padre de `PDHProveedor/{codigo}`.

Un endpoint hijo no debe exponerse como boton on-demand normal. Se ejecuta como parte del flujo del padre/grupo.

## 5. Endpoints levantados y dependencias

| Grupo | Orden | Descripcion | Recurso | Tipo | Proposito | JSON |
|---:|---:|---|---|---|---|---|
| 0 | 1 | Dimensiones | `/api/reports/DIMENSIONES` | `REPORT_VIEWER` | `MAESTRO_GET` | `dimensiones_list.json` |
| 0 | 2 | Partidas Financieras | `/api/partidafinanciera/list` | `BASE_ERP` | `MAESTRO_GET` | `partidas_financieras_list.json` |
| 0 | 3 | Centros de Costos | `/api/PDHCentroCosto/list` | `CUSTOM` | `MAESTRO_GET` | `centros_costos_list.json` |
| 0 | 4 | Monedas | `/api/moneda/list` | `BASE_ERP` | `MAESTRO_GET` | `monedas_list.json` |
| 1 | 1 | Tasas Impositivas | `/api/tasaImpositiva/list` | `BASE_ERP` | `MAESTRO_GET` | `tasas_impositivas_list.json` |
| 1 | 2 | Tasas Impositivas por Codigo | `/api/tasaImpositiva/{codigo}` | `BASE_ERP` | `DETALLE_GET` | `tasas_impositivas_cod_TASA19c.json` |
| 1 | 3 | Unidades de Medida | `/api/unidad/list` | `BASE_ERP` | `MAESTRO_GET` | `unidades_medidas_list.json` |
| 1 | 4 | Familias | `/api/productoFamilia/list` | `BASE_ERP` | `MAESTRO_GET` | `familias_list.json` |
| 1 | 5 | Sub Familias | `/api/productoSubfamilia/list` | `BASE_ERP` | `MAESTRO_GET` | `sub_familias_list.json` |
| 1 | 6 | Sub Familias por Codigo | `/api/productoSubfamilia/{codigo}` | `BASE_ERP` | `DETALLE_GET` | `sub_familias_cod_AZOXI.json` |
| 1 | 7 | Productos | `/api/producto/list` | `BASE_ERP` | `MAESTRO_GET` | `productos_list.json` |
| 1 | 8 | Productos por Codigo | `/api/producto/{codigo}` | `BASE_ERP` | `DETALLE_GET` | `productos_cod_CON004.json` |
| 2 | 1 | Conceptos | `/api/Concepto/list` | `BASE_ERP` | `MAESTRO_GET` | `conceptos_list.json` |
| 2 | 2 | Cuentas Contables | `/api/cuenta/list` | `BASE_ERP` | `MAESTRO_GET` | `cuentas_contables_list.json` |
| 2 | 3 | Condiciones de Pago | `/api/condicionPago/list` | `BASE_ERP` | `MAESTRO_GET` | `condiciones_pago_list.json` |
| 2 | 4 | Condiciones de Pago por Codigo | `/api/condicionPago/{codigo}` | `BASE_ERP` | `DETALLE_GET` | `condiciones_pago_cod_30.json` |
| 2 | 5 | Proveedores | `/api/PDHProveedor/list` | `CUSTOM` | `MAESTRO_GET` | `proveedores_list.json` |
| 2 | 6 | Proveedores por Codigo | `/api/PDHProveedor/{codigo}` | `CUSTOM` | `DETALLE_GET` | `proveedores_cod_82392600-6.json` |

Nota: el CSV original parecia tener invertidos los JSON de tasas impositivas list/detalle. La tabla anterior usa la estructura real observada en los archivos JSON.

## 6. Maestros nuevos o extendidos

### 6.1 Maestros nuevos simples

Se proponen como espejos ERP:

- `centroscosto`,
- `familias`,
- `subfamilias`,
- `erptasasimpositivas`,
- `erpconceptos`,
- `erpcuentascontables`,
- `erpcondicionespago`,
- `erpproveedores`,
- `erpmonedas`,
- `erpdimensiones`,
- `erppartidasfinancieras`.

La nomenclatura final puede ajustarse al patron existente del proyecto antes de crear archivos.

### 6.2 Campos base por maestro espejo

| Campo base | Regla |
|---|---|
| `<maestro>id` | PK AI local. |
| `<maestro>dsc` o `<maestro>nombre` | Nombre visible. |
| `erp<maestro>cod` | Codigo ERP unico. |
| `<maestro>activo` | Activo segun ERP. |
| `<maestro>syncfechahora` | Ultima fecha/hora de sincronizacion. |
| Auditoria | Columnas estandar. |
| Tabla log | Insercion, actualizacion, anulacion/inactivacion y sincronizacion. |

### 6.3 Relaciones especificas

| Maestro | Relacion |
|---|---|
| `subfamilias` | FK a `familias`, completada con `productoSubfamilia/{codigo}`. |
| `invitems` | FK a unidad, familia, subfamilia, tasa compra y partida financiera si aplica. |
| `erpproveedores` | FK a condicion de pago default, concepto proveedor, cuenta proveedor y moneda pago si aplica. |
| `erpcondicionespago` | Dias desde `CondicionPagoItems[]` cuando venga por detalle. |
| `erptasasimpositivas` | Porcentaje desde consulta por codigo. |

## 7. Extension de `invitems`

### 7.1 Estado actual

`invitems` ya es una tabla transversal usada por:

- Produccion de Leche mediante `prodlechetipos.invitemid`,
- Suplementacion Animal mediante `suplanimaldetalle.invitemid`,
- API externa de suplementacion,
- futuras compras,
- futuro combustible.

Por lo tanto, no se debe reemplazar la tabla ni cambiar su PK.

### 7.2 Uso funcional

Se aprueba crear un nuevo atributo de uso funcional:

- `invitemusocodigo`

Valores iniciales propuestos:

| Codigo | Uso |
|---|---|
| `LCH` | Produccion de leche. |
| `ALM` | Alimentacion animal, usado por Suplementacion Animal. |
| `CMB` | Combustible. |
| `BDG` | Bodega / inventario general. |
| `SRV` | Servicios. |

`ALM` corresponde al uso funcional del item. El modulo que lo consume hoy es Suplementacion Animal.

### 7.3 Compatibilidad con `invitemleche`

`invitemleche` se mantiene durante la transicion.

Regla inicial de migracion sugerida:

- `invitemleche = 1` -> `invitemusocodigo = 'LCH'`.
- `invitemleche = 0` e `invitemstockeable = 1` -> candidato a `ALM`, sujeto a revision de datos.

Antes de aplicar una migracion masiva a `ALM`, se debe revisar si existen items stockeables que no correspondan a alimentacion animal.

### 7.4 Filtros a migrar

Uso actual:

- `ProdlechetiposController` usa `listarInvitemsFormSelect(1, null, 1)` para filtrar por `invitemleche = 1`.
- `SuplanimalController` usa `listarInvitemsFormSelect(null, 1, 1)` para filtrar por `invitemstockeable = 1`.
- `ProdlecheController` usa `prodlechetipos`, no selecciona items directamente.

Uso futuro:

- Tipos de leche deben filtrar por `invitemusocodigo = 'LCH'`.
- Suplementacion Animal debe filtrar por `invitemusocodigo = 'ALM'` y activo.
- Compras debe filtrar por items comprables, tipo material/servicio, subfamilia y precio referencial.
- Combustible debe filtrar por `invitemusocodigo = 'CMB'`.

### 7.5 Columnas ERP/locales candidatas en `invitems`

| Columna | Origen | Uso |
|---|---|---|
| `invitemusocodigo` | Local | Filtrar modulo/uso funcional. |
| `familiaid` | ERP | Clasificacion. |
| `subfamiliaid` | ERP | Clave de presupuesto. |
| `erptasaimpositivacompraid` | ERP | Tasa compra. |
| `erppartidafinancieraid` o codigo | ERP | `DIMPARFIN` por item. |
| `invitemprecioestandar` | ERP/local segun decision | Valorizacion REQ/PreOC. |
| `invitemcompra` | ERP | `CheckSeCompra`. |
| `invitemstockeable` | ERP/local existente | Material vs servicio. |
| `invitemtipo` | Derivado/local | Material/Servicio si se decide persistir. |
| `invitemorigen` | Local | `ERP` / `LOCAL`, si se permiten excepciones. |

La sincronizacion ERP no debe sobrescribir `invitemusocodigo` si queda definido como atributo local.

## 8. Centros de costo

### 8.1 Base ERP

El maestro de centros de costo se sincroniza desde `PDHCentroCosto/list`.

Campos base:

- ID local,
- descripcion/nombre,
- codigo ERP,
- activo,
- fecha/hora de sincronizacion,
- auditoria,
- log.

### 8.2 Atributos locales de aprobacion

Se agregan como atributos locales:

- jefe de centro,
- jefe tecnico,
- gerente de produccion.

Decision pendiente:

- si estos atributos apuntan a `usuarios`,
- o si apuntan a `funcionarios` y luego se resuelve el `usuarioid` para firmar.

Criterio preliminar:

- la estructura organizacional vive mejor en `funcionarios`,
- la firma y acceso al sistema requieren `usuarios`,
- puede convenir vincular `usuarios.funcionarioid` cuando se diseñe aprobaciones, ausencias y reemplazos.

## 9. Plan de desarrollo propuesto

### Fase 0 - Validaciones previas

1. Revisar nombres definitivos de tablas nuevas segun convencion del proyecto.
2. Revisar datos actuales de `invitems` para estimar migracion `LCH` / `ALM`.
3. Confirmar si un item puede tener un unico uso funcional o multiples usos.
4. Confirmar si `DIMCTC` corresponde al maestro de centros de costo sincronizado.
5. Confirmar si `DIMPARFIN` se tomara inicialmente desde la dimension del producto.

### Fase 1 - Base comun ERP

1. Crear tabla `ERP_ListadoEndPoints`.
2. Crear tabla log de endpoints.
3. Sembrar endpoints levantados desde CSV/JSON.
4. Crear servicio base de ejecucion GET con token dinamico.
5. Crear logica de grupo, orden y endpoint hijo.
6. Crear pantalla/listado de endpoints.
7. Agregar accion on-demand controlada por permisos.

### Fase 2 - Maestros base de dependencias

1. Dimensiones.
2. Partidas financieras.
3. Centros de costo.
4. Monedas.
5. Tasas impositivas list/detalle.
6. Unidades de medida.
7. Familias.
8. Subfamilias list/detalle.

### Fase 3 - Extension de items

1. Crear catalogo de usos de item.
2. Agregar `invitemusocodigo` a `invitems`.
3. Agregar FKs/campos para familia, subfamilia, tasa compra, partida financiera y precio.
4. Crear migracion inicial compatible con `invitemleche`.
5. Ajustar SP/listados de `invitems`.
6. Bloquear crear/editar base ERP en UI.
7. Permitir editar solo atributos locales autorizados.
8. Sincronizar productos list/detalle respetando dependencias.
9. Migrar filtros de Produccion de Leche y Suplementacion Animal con cuidado.

### Fase 4 - Maestros PreOC

1. Conceptos.
2. Cuentas contables.
3. Condiciones de pago list/detalle.
4. Proveedores list/detalle.
5. Seleccion de condicion de pago default del proveedor:
   - si hay una default, usarla;
   - si no hay default, usar la primera;
   - si hay una sola, usar esa.

### Fase 5 - Integracion con compras

1. Usar subfamilia + centro de costo + temporada para presupuesto.
2. Usar precio referencial de item para REQ.
3. Bloquear items sin precio referencial en REQ.
4. Resolver `DIMPARFIN` y `DIMCTC` para PreOC.
5. Preparar generacion de JSON OC/OCSS cuando la PreOC este aprobada.

## 10. Riesgos y cuidados

1. `invitems` impacta modulos existentes; toda migracion debe ser compatible.
2. `suplanimaldetalle` guarda snapshot ERP; no se debe romper integracion existente.
3. `prodlechetipos` depende de `invitemid`; no se debe cambiar esa relacion.
4. Los endpoints list no contienen todos los datos; se requiere detalle por codigo.
5. La sincronizacion de productos no debe ejecutarse antes de unidades, familias, subfamilias y tasas.
6. La sincronizacion no debe borrar fisicamente maestros usados por transacciones; debe inactivar.
7. No guardar tokens ni secretos en logs.
8. La definicion final de aprobadores por centro de costo se cerrara al disenar aprobaciones.

## 11. Pendientes de conversacion

1. Confirmar si `invitemusocodigo` sera unico por item o si se requiere una tabla detalle de multiples usos.
2. Confirmar nombres fisicos definitivos para tablas nuevas.
3. Confirmar si `DIMCTC` es el mismo catalogo de centros de costo.
4. Confirmar si `DIMPARFIN` se resuelve desde producto, presupuesto u otra regla.
5. Confirmar si los campos de aprobadores de centro de costo apuntaran inicialmente a `funcionarios` o `usuarios`.
6. Confirmar permisos exactos para ver/ejecutar sincronizacion on-demand.
