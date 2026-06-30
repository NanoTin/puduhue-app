# Modulo PreOC (Pre Orden de Compra) - Estructura de Datos v2

> Diseno funcional y logico vigente para Pre Orden de Compra.
>
> Fuentes normativas:
> - `docs/ADR/modulo-compras/ADR-001-modelo-presupuesto-compras.md`
> - `docs/ADR/modulo-compras/ADR-002-compromiso-edicion-preoc.md`
> - `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
> - `docs/modulo_compras_presupuesto_definitivo.md`
>
> Este documento reemplaza la version v1, que mantenia un modelo anterior con presupuesto jerarquico, `CSO`, estado ERP como estado documental y seleccion manual de presupuesto.

## 0. Decisiones vigentes

| # | Decision | Resolucion vigente |
|---|---|---|
| 1 | Creador de PreOC | Usuario comprador con permiso `usuariocomprador` |
| 2 | Origen de lineas | `reqaprobados` pendientes o parciales |
| 3 | Compra parcial | Permitida; se actualiza saldo pendiente del requerimiento aprobado |
| 4 | Cotizaciones | Fuera de alcance |
| 5 | Fechas PreOC | `preocfecha` es creacion interna no editable; `preocfechaoc` la selecciona el usuario para presupuesto y ERP |
| 6 | Moneda | Solo CLP / `PES` |
| 7 | Tipo ERP | Material=`OC`, Servicio=`OCSS` |
| 8 | Presupuesto | Resolucion automatica por `preocfechaoc`, subfamilia y centro de costo |
| 9 | Reserva presupuestaria | Se genera al pasar `BRR -> PND`, no al guardar borrador |
| 10 | Edicion | Solo mientras no exista ninguna aprobacion |
| 11 | Estado ERP | Columna separada del estado documental |
| 12 | Anulacion sincronizada | Local con permiso especial; mantiene estado ERP `SNC` |
| 13 | Workflow | Valor fijo, no maestro |
| 14 | Dimensiones ERP | Se guardan por item PreOC o req-item, pendiente confirmar con cliente/Finnegans |
| 15 | Firmantes | Default desde presupuestos + aprobadores por monto + manuales |
| 16 | Reordenamiento | Botones Subir/Bajar |
| 17 | Comentarios | Tabla funcional separada del LOG |
| 18 | Agrupacion de items | Se separa origen req-item, item agrupado e impuestos |
| 19 | Resumen presupuesto | `preocpptoresumen` como apoyo de consulta rapida por presupuesto |

## 1. Tablas nuevas o ajustadas

| Tabla | Tipo | Descripcion |
|---|---|---|
| `preoc` | Transaccional | Cabecera de la PreOC |
| `preocdetallereqitems` | Transaccional | Lineas origen desde requerimientos aprobados |
| `preocitems` | Transaccional | Items agrupados de la PreOC |
| `preocimptos` | Transaccional | Impuestos por item agrupado |
| `preocitemsdimensiones` | Transaccional | Dimensiones ERP por req-item origen; puede mantener referencia nullable a item agrupado |
| `preocpptoresumen` | Resumen | Resumen de presupuesto afectado por PreOC |
| `preocfirmantes` | Transaccional | Firmantes/aprobadores de PreOC |
| `preocfirmanteshistorial` | Historial funcional | Snapshot de resoluciones de aprobadores por ciclo |
| `preoccomentarios` | Funcional | Comentarios visibles de aprobacion, rechazo y anulacion |
| `preocestadoshistorial` | Historial funcional | Cambios de estado documental de PreOC |
| `preoclog` | LOG | Auditoria tecnica |
| `preoccarrito` | Transitorio | Carrito de seleccion de lineas aprobadas antes de crear PreOC |
| `preocestados` | Maestro | Estados documentales |
| `preocestadoserp` | Maestro | Estados de sincronizacion ERP |
| `preocaprobadoresxmonto` | Maestro | Reglas de aprobadores por monto neto |
| `PptoCompraTransacciones` | Transaccional | Movimientos presupuestarios, definido en presupuesto definitivo |

### Tablas relacionadas

| Tabla | Relacion |
|---|---|
| `reqaprobados` | Fuente de lineas aprobadas; no contiene `preocid` unico |
| `reqaprobadoshistorial` | Guarda vinculos de compra/anulacion con `preocid` y `preocdetid`, donde `preocdetid` referencia `preocdetallereqitems.preocdetreqitemid` |
| `PptoCompra` | Presupuesto resuelto automaticamente por linea |
| `usuarios` | Permisos de comprador, aprobacion PreOC y anulacion especial |
| `aprobadoresperiodoinactividad` | Reemplazo de aprobadores inactivos |

## 2. `preoc` - Cabecera

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `preocid` | INT PK AI | NO | PK interna |
| `preocdoc` | VARCHAR(20) | NO | Codigo visible `POC-00000001`: prefijo `POC-` + `preocid` con `LPAD` a 8 digitos |
| `preoctipo` | TINYINT | NO | 1=Material (`OC`), 2=Servicio (`OCSS`) |
| `preocfecha` | DATE | NO | Fecha de creacion interna definida por sistema/BD, no editable |
| `preocfechaoc` | DATE | NO | Fecha seleccionada por usuario para presupuesto y envio ERP |
| `compradorusuarioid` | INT FK | NO | Usuario comprador creador |
| `erpproveedorid` | INT FK | NO | Proveedor espejo ERP |
| `erpcondicionpagoid` | INT FK | SI | Condicion de pago precargada desde proveedor y editable antes de enviar |
| `preocworkflowcod` | VARCHAR(50) | NO | Workflow fijo de compra |
| `erpmonedacod` | VARCHAR(10) | NO | Moneda operativa; CLP=`PES` |
| `erpprovinciaid` | INT FK | SI | Provincia/destino global si aplica |
| `preocobsinterna` | TEXT | SI | Observacion interna de la PreOC |
| `preocobsoc` | TEXT | SI | Observacion para formato imprimible / OC |
| `preocprioridad` | TINYINT | NO | 1=Normal, 2=Alta; efecto visual/correo |
| `preocestadoid` | VARCHAR(20) FK | NO | Estado documental |
| `preocestadoerpid` | VARCHAR(20) FK | SI | Estado ERP separado |
| `preocaprobadoridpnd` | INT FK | SI | Aprobador pendiente vigente para consultas rapidas |
| `preocaprobacionfecha` | DATE | SI | Fecha de aprobacion completa |
| `preocnettotal` | DECIMAL(15,2) | NO | Total neto recalculado desde detalle |
| `preocimptostotal` | DECIMAL(15,2) | NO | Total impuestos calculados |
| `preoctotal` | DECIMAL(15,2) | NO | Total con impuestos |
| `erptransaccionid` | VARCHAR(50) | SI | ID transaccion ERP si aplica |
| `erpnumerodoc` | VARCHAR(50) | SI | Numero ERP / NumeroComprobante |
| `erpsincfechahora` | DATETIME | SI | Fecha/hora de sincronizacion correcta |
| `erperror` | TEXT | SI | Error ERP visible para consulta; se limpia al sincronizar OK |
| `erprespuestajson` | JSON | SI | Respuesta tecnica completa |
| `preocvig` | TINYINT(1) | NO | Vigente/baja logica |
| + auditoria |  |  | Columnas estandar del proyecto |

Notas:

- No se guarda un unico `pptocompraid` en cabecera, porque una PreOC puede contener lineas que resuelven distintos presupuestos por subfamilia y centro.
- El presupuesto se resuelve por linea usando `preocfechaoc`, subfamilia del item y centro de costo.
- `preocfecha` y `preocfechaoc` deben mostrarse ambas.
- `preocdoc` es global, no editable por usuario, no reciclable y no cambia aunque la PreOC se anule.
- `preocvig` se mantiene por consistencia tecnica con el patron de baja logica, aunque el estado documental concentra la regla funcional.
- La vista principal debe mostrar por defecto las PreOC del comprador login si `usuariocomprador = 1`. Si el usuario no tiene ese atributo, el filtro comprador inicia en `TODOS`.

## 3. Estados

### 3.1 Estados documentales

| Codigo | Descripcion | Efecto |
|---|---|---|
| `BRR` | Borrador | Editable; no reserva presupuesto |
| `PND` | Pendiente / En curso | Reserva presupuesto al enviar; editable solo si no tiene aprobaciones |
| `APR` | Aprobada | Confirma reserva y habilita sincronizacion ERP |
| `RCH` | Rechazada | Revierte presupuesto asociado |
| `ANL` | Anulada | Revierte presupuesto cuando corresponde |

Reglas:

- No existe `CSO`; se usa `RCH` con comentario obligatorio.
- `PND -> BRR` solo se permite si ningun firmante aprobo; libera o revierte reserva.
- `RCH -> BRR` se permite para corregir/rearmar; el rechazo ya hizo la reversa.
- `RCH -> BRR` reinicia el ciclo de aprobacion y antes conserva las resoluciones previas en `preocfirmanteshistorial`.
- Cuando existe al menos una aprobacion, la PreOC deja de ser editable.
- Una PreOC con al menos un firmante aprobado no puede anularse directamente; debe solicitarse rechazo.
- Una PreOC sincronizada puede anularse localmente con permiso especial y comentario obligatorio.

### 3.2 Estados ERP

| Codigo | Descripcion | Regla |
|---|---|---|
| Sin estado | No aplica | Mientras PreOC no este aprobada |
| `SNC` | Sincronizada | POST ERP exitoso |
| `ERR` | Error sincronizacion | POST ERP fallido; permite reintento |

Reglas:

- Si falla ERP, el estado documental permanece `APR` y el estado ERP queda `ERR`.
- El error ERP debe quedar en columna visible, no solo en LOG.
- Al sincronizar correctamente, se limpia error y se guardan numero ERP y fecha/hora.
- Si una PreOC sincronizada se anula localmente, estado documental pasa a `ANL` y estado ERP permanece `SNC`.

## 4. `preocdetallereqitems` - Lineas origen REQ

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `preocdetreqitemid` | INT PK AI | NO | PK interna |
| `preocid` | INT FK | NO | Cabecera |
| `reqaprobadoid` | INT FK | NO | Linea aprobada origen |
| `preocdetlinea` | INT | NO | Numero de linea |
| `invitemid` | INT FK | NO | Item actual desde pendiente de compra |
| `preocdetitemcod` | VARCHAR(50) | NO | Snapshot codigo item |
| `preocdetdsc` | VARCHAR(200) | NO | Snapshot descripcion item |
| `centrocostoid` | INT FK | NO | Centro de costo de la linea |
| `pptocompraid` | INT FK | NO | Presupuesto resuelto automaticamente |
| `subfamiliaid` | INT FK | NO | Subfamilia usada para presupuesto |
| `erpprovinciaid` | INT FK | SI | Provincia/destino por linea si aplica |
| `preocdetdsccc` | VARCHAR(200) | NO | Descripcion para ERP = nombre/descripcion del centro de costo |
| `invunidmedid` | INT FK | NO | Unidad de medida |
| `preocdetcantidad` | DECIMAL(15,4) | NO | Cantidad a comprar, menor o igual al saldo pendiente |
| `preocdetprecioneto` | DECIMAL(15,2) | NO | Precio real/neto usado por comprador |
| `preocdetsubtotalneto` | DECIMAL(15,2) | NO | Cantidad x precio |
| `preocdetobs` | TEXT | SI | Observacion por linea |

Reglas:

- Esta tabla representa los req-items-centro-subfamilia seleccionados por el comprador.
- El item se cambia, si corresponde, en pendientes de compra; no dentro de PreOC.
- La cantidad no puede superar `reqaprobados.reqaprobadocantidadpendiente`.
- Al enviar o confirmar la PreOC se registra movimiento en `reqaprobadoshistorial`.
- La compra parcial actualiza cantidad pendiente/comprada en `reqaprobados`.
- El campo `preocdetdsccc` se envia al ERP en `Items[n].Descripcion` para identificar centro de costo.
- `DIMPARFIN` viene del maestro de items.
- `DIMCTC` viene del codigo del centro de costo.

## 5. `preocitems` - Items agrupados

Tabla espejo de la agrupacion por item usada para precio, impuestos y total de OC.

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `preocitemid` | INT PK AI | NO | PK |
| `preocid` | INT FK | NO | Cabecera |
| `invitemid` | INT FK | NO | Item agrupado |
| `invunidmedid` | INT FK | NO | Unidad de medida |
| `preocitemcantidadtotal` | DECIMAL(15,4) | NO | Suma de cantidades de `preocdetallereqitems` para el item |
| `preocitemprecioneto` | DECIMAL(15,2) | NO | Precio neto unitario digitado/confirmado por comprador |
| `preocitemnetototal` | DECIMAL(15,2) | NO | Cantidad total x precio neto |
| `preocitemimptostotal` | DECIMAL(15,2) | NO | Suma de impuestos del item |
| `preocitemtotal` | DECIMAL(15,2) | NO | Neto total + impuestos |

Reglas:

- El comprador informa precio neto por item agrupado, no requerimiento por requerimiento.
- No se puede finalizar/enviar una PreOC si falta precio en algun item agrupado.
- Al cambiar precio, se actualizan totales agrupados y las lineas req-item relacionadas.
- La visualizacion debe mostrar variacion de precio: buscar primero historico en PreOC; si no existe, usar costo estandar/precio referencial del maestro de items.
- El modal de precio puede mostrar ultimo proveedor, ultimo precio, fecha ultima compra y variacion contra el precio actual.

## 6. `preocimptos`

Detalle de impuestos por item agrupado.

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `preocimptoid` | INT PK AI | NO | PK |
| `preocitemid` | INT FK | NO | Item agrupado |
| `imptoid` | INT FK | SI | Guarda el `erptasasimpositivas.erptasaimpositivaid` usado como tasa de compra |
| `preocimptoneto` | DECIMAL(15,2) | NO | Precio neto unitario base |
| `preocimptocantidadtotal` | DECIMAL(15,4) | NO | Cantidad total del item |
| `preocimptonetototal` | DECIMAL(15,2) | NO | Neto total base |
| `preocimptotasa` | DECIMAL(9,4) | NO | Tasa de impuesto |
| `preocimptomonto` | DECIMAL(15,2) | NO | Monto calculado para este impuesto |

Reglas:

- Para el corte vigente, cada item tiene una tasa impositiva de compra en `invitems.erptasaimpositivaid`.
- La tasa se resuelve contra `erptasasimpositivas.erptasaimpositivaporcentaje`.
- `preocimptotasa` guarda snapshot del porcentaje numerico, por ejemplo `19.0000`.
- Para calcular, el porcentaje se divide por 100.
- Si el item no tiene tasa configurada o la tasa no tiene porcentaje, no se puede agregar/enviar y se debe indicar contactar a Administracion.
- La suma de `preocimptomonto` alimenta `preocitems.preocitemimptostotal` y `preoc.preocimptostotal`.
- `preocimptos` se mantiene tambien como detalle tecnico para validar totales y preparar JSON ERP futuro.

## 7. `preocitemsdimensiones`

Tabla para representar `DimensionDistribucion`.

Definicion vigente: la distribucion cuelga operativamente del req-item origen (`preocdetallereqitems`), porque una PreOC puede agrupar el mismo item desde distintos centros/subfamilias/presupuestos. `preocitemid` puede quedar nullable como apoyo de consulta si se requiere reconstruir por item agrupado.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `preocitemdimensionid` | INT PK AI | NO | PK |
| `preocitemid` | INT FK | SI | Item agrupado, apoyo opcional de consulta |
| `preocdetreqitemid` | INT FK | NO | Req-item origen que define centro/subfamilia/presupuesto |
| `dimensioncodigo` | VARCHAR(50) | NO | Ej. `DIMPARFIN`, `DIMCTC` |
| `distribucioncodigo` | VARCHAR(50) | SI | Codigo distribucion si aplica |
| `tipocalculo` | VARCHAR(10) | NO | Tipo calculo ERP; ejemplo `2` |
| `dimensionitemcodigo` | VARCHAR(50) | NO | Codigo valor; ej. `LEC000`, `LMT-0002` |
| `dimensionporcentaje` | DECIMAL(9,4) | NO | Porcentaje |
| `dimensionimporte` | DECIMAL(15,2) | NO | Importe |
| `dimensionfuente` | VARCHAR(30) | NO | ITEM/CENTRO/SISTEMA |

Reglas:

- Se completan desde maestros y reglas internas.
- El comprador no las edita.
- Deben visualizarse desde la PreOC, por ejemplo con accion "ver dimensiones" por linea.
- Deben soportar mas de una distribucion por dimension si el ERP lo requiere.

## 8. `preocpptoresumen`

Resumen de apoyo/consulta rapida por presupuesto afectado. El libro oficial sigue siendo `PptoCompraTransacciones`.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `preocpptoresumenid` | INT PK AI | NO | PK |
| `preocid` | INT FK | NO | PreOC |
| `pptocompraid` | INT FK | NO | Presupuesto afectado |
| `preocpptomonto` | DECIMAL(15,2) | NO | Total neto afectado a ese presupuesto |
| `preocpptosaldoantes` | DECIMAL(15,2) | SI | Saldo disponible antes del evento registrado |
| `preocpptosaldodespues` | DECIMAL(15,2) | SI | Saldo disponible posterior |
| `preocpptoestado` | VARCHAR(20) | NO | RESERVA/CONFIRMADO/REVERTIDO u otro estado funcional |
| `preocpptofechahora` | DATETIME | NO | Momento del calculo |

Reglas:

- No incluye `preocdetid`, `preocdetreqitemid` ni `preocitemid`.
- La relacion con lineas se reconstruye por `preocid + pptocompraid`.
- Sirve para visualizacion rapida de presupuestos usados, montos y saldos.
- No reemplaza las transacciones del presupuesto.

## 9. Presupuesto y movimientos

La PreOC es el unico punto del flujo que compromete presupuesto.

| Evento | Efecto |
|---|---|
| Guardar `BRR` | No genera reserva |
| Enviar `BRR -> PND` | Valida saldo usando `preocfechaoc` y genera `POC_RESERVA` negativa |
| Eliminar linea en `PND` sin aprobaciones | Borra transaccion provisional asociada |
| Volver `PND -> BRR` sin aprobaciones | Libera/revierte reserva |
| Aprobar completamente | Confirma reserva con `POC_CONFIRMACION` |
| Rechazar/anular | Reversa positiva con `POC_REVERSA` cuando corresponde |

Reglas:

- La validacion de PreOC es bloqueante.
- Si no existe presupuesto para `preocfechaoc` + subfamilia + centro, no avanza.
- Si no hay saldo suficiente, no avanza.
- Las reservas y consumos se guardan en negativo.
- Las reversas se guardan en positivo.
- No se borra historia de movimientos confirmados.

## 10. `preocfirmantes`

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `preocfirmanteid` | INT PK AI | NO | PK |
| `preocid` | INT FK | NO | PreOC |
| `firmanteusuarioid` | INT FK | NO | Usuario aprobador |
| `firmantetipo` | VARCHAR(20) | NO | RESPONSABLE/ADMIN/COLABORADOR/MONTO/MANUAL/REEMPLAZO |
| `firmanteorden` | INT | NO | Orden secuencial |
| `firmantedefault` | TINYINT(1) | NO | 1 si no se puede remover |
| `firmanteestado` | VARCHAR(5) | NO | PND/APR/RCH/INA/NVG |
| `firmantefechahora` | DATETIME | SI | Fecha/hora del evento |
| `firmantecomentario` | TEXT | SI | Comentario funcional |
| `firmantereemplazodeid` | INT FK | SI | Firmante reemplazado por inactividad |

Reglas:

- Un aprobador no puede repetirse en la misma PreOC.
- El comentario de rechazo es obligatorio y debe tener mas de 10 caracteres.
- La aprobacion permite comentario opcional.
- Los firmantes default no se remueven.
- Aplica inactividad/reemplazo.

### 10.1 Generacion de firmantes

La lista se genera desde los presupuestos que componen la PreOC:

1. Responsable del presupuesto.
2. Administrador del presupuesto.
3. Colaborador del presupuesto, si existe.
4. Aprobadores por monto.
5. Aprobadores manuales.

Reglas:

- Se toman todos los `PptoCompra` resueltos por las lineas.
- Responsable y administrador son obligatorios en cada presupuesto.
- Colaborador es opcional.
- Si un usuario aparece mas de una vez, se conserva una sola fila.
- Los default de presupuesto no se pueden quitar.
- El comprador puede agregar aprobadores manuales.
- Los manuales no pueden duplicar usuarios ya presentes.

### 10.2 Aprobadores por monto

`preocaprobadoresxmonto` mantiene reglas para agregar aprobadores automaticos segun monto neto.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `preocaprobmontoid` | INT PK AI | NO | PK |
| `usuarioid` | INT FK | NO | Aprobador |
| `montominimo` | DECIMAL(15,2) | NO | Umbral |
| `firmanteorden` | INT | NO | Orden sugerido |
| `preocaprobmontoactivo` | TINYINT(1) | NO | Activo |
| + auditoria |  |  | Auditoria estandar |

### 10.3 Agregar y reordenar firmantes

Agregar manual:

- Boton con signo `+`.
- Modal de busqueda de usuarios.
- Filtrar usuarios activos con permiso de aprobacion PreOC.
- Al grabar, validar duplicidad.
- Si esta OK, agregar como manual removible y reordenable.

Reordenar:

- La grilla usa botones Subir y Bajar.
- El primer registro no puede subir.
- El ultimo registro no puede bajar.
- Si hay un solo registro, ambos botones quedan bloqueados.
- Al mover, se intercambia orden y se renumera sin huecos.

### 10.4 Resolver siguiente aprobador

La PreOC usa la misma logica que REQ:

1. Buscar siguiente firmante por orden.
2. Si el usuario no esta vigente:
   - marcar `NVG`,
   - registrar comentario funcional,
   - registrar LOG,
   - continuar con el siguiente.
3. Si el usuario esta vigente pero tiene periodo de inactividad:
   - marcar firmante original `INA`,
   - insertar reemplazante inmediatamente despues,
   - reordenar,
   - dejar reemplazante como pendiente.
4. Si el usuario esta vigente y sin inactividad:
   - asignarlo a `preocaprobadoridpnd`.

`preocaprobadoridpnd` es una denormalizacion controlada para consultas rapidas. Debe coincidir con el firmante pendiente vigente.

## 11. `preoccomentarios`

Tabla funcional separada del LOG tecnico.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `preoccomentarioid` | INT PK AI | NO | PK |
| `preocid` | INT FK | NO | PreOC |
| `usuarioid` | INT FK | NO | Usuario que comenta |
| `preoccomentariotipo` | VARCHAR(20) | NO | APR/RCH/ANL/INFO u otro |
| `preoccomentariotxt` | TEXT | NO | Comentario visible |
| `preoccomentariofechahora` | DATETIME | NO | Fecha/hora |

El rechazo y la anulacion requieren comentario funcional obligatorio de mas de 10 caracteres.

## 12. `preoclog`

Tipos minimos:

| Tipo | Evento |
|---|---|
| `INS` | Envio de PreOC a aprobacion |
| `UPD` | Modificacion confirmada antes de aprobaciones |
| `ANL` | Anulacion |
| `APR` | Aprobacion |
| `RCH` | Rechazo |
| `ERP` | Sincronizacion ERP exitosa |
| `ERR` | Error ERP |
| `REV` | Reversa/liberacion de presupuesto, si se define este codigo |

El LOG tecnico no reemplaza comentarios funcionales.

## 12.1 Historial funcional de estados

`preocestadoshistorial` registra cada cambio documental:

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `preocestadohistid` | INT PK AI | NO | PK |
| `preocid` | INT FK | NO | PreOC |
| `preocestadoanteriorid` | VARCHAR(20) FK | SI | Estado anterior; NULL al crear |
| `preocestadonuevoid` | VARCHAR(20) FK | NO | Estado nuevo |
| `usuarioid` | INT FK | NO | Usuario que ejecuta |
| `preocestadohistfechahora` | DATETIME | NO | Fecha/hora |
| `preocestadohistobs` | TEXT | SI | Observacion o motivo |

La vista PreOC debe exponer boton `Historial de Estados`.

## 12.2 Historial de resoluciones de aprobadores

`preocfirmanteshistorial` conserva snapshots de firmantes/resoluciones por ciclo antes de reiniciar la lista activa.

Campos minimos:

- `preocfirmantehistid`;
- `preocid`;
- `preoccicloaprobacion`;
- `firmanteusuarioid`;
- `firmantetipo`;
- `firmanteorden`;
- `firmantedefault`;
- `firmanteestado`;
- `firmantefechahora`;
- `firmantecomentario`;
- `firmantereemplazodeid`;
- auditoria/hist usuario y fecha.

La vista PreOC debe exponer boton `Historial de Resoluciones`.

## 12.3 Carrito PreOC

`preoccarrito` guarda la seleccion transitoria antes de crear una PreOC.

Reglas:

- No se agregan columnas de carrito a `reqaprobados`.
- La seleccion de pendientes excluye carritos activos de cualquier usuario.
- La seleccion excluye lineas ya tomadas por PreOC vigente en `BRR` o `PND`.
- Si el usuario tiene carrito activo previo, puede continuar o liberar.
- Al crear borrador desde carrito, el carrito pasa a convertido/liberado y la disponibilidad queda controlada por el detalle PreOC.
- Si una linea se elimina desde PreOC editable antes de aprobacion, vuelve a quedar disponible segun saldo pendiente.

## 13. Integracion ERP

### 13.1 Cuando se integra

- La integracion se intenta cuando PreOC queda `APR`.
- Si POST es exitoso: estado ERP `SNC`, guarda `erptransaccionid`, `erpnumerodoc`, `erpsincfechahora`, `erprespuestajson`.
- Si POST falla: estado documental sigue `APR`, estado ERP `ERR`, guarda `erperror` y `erprespuestajson`.
- El reintento ERP opera sobre PreOC documental `APR` con estado ERP `ERR`.

### 13.2 Mapeo base

| Dato PreOC | Dato ERP | Regla |
|---|---|---|
| `preoctipo = 1` | `TransaccionSubtipoCodigo` | `OC` |
| `preoctipo = 2` | `TransaccionSubtipoCodigo` | `OCSS` |
| `preocdoc` | `IdentificacionExterna` / `Nombre` | Codigo PreOC |
| `preocfechaoc` | `Fecha` | Fecha OC seleccionada por usuario |
| proveedor | `Proveedor` | Codigo/RUT ERP proveedor |
| condicion pago | `CondicionPagoCodigo` | Desde maestro/seleccion |
| workflow fijo | `WorkflowCodigo` | Valor fijo definido para compra |
| moneda | `MonedaCodigo` | `PES` |
| item | `Items[n].ProductoCodigo` | Codigo ERP item |
| cantidad | `Items[n].Cantidad` | Cantidad comprar |
| precio | `Items[n].Precio` | Precio neto |
| `preocdetdsccc` | `Items[n].Descripcion` | Centro de costo legible |
| dimensiones | `DimensionDistribucion` | Desde `preocitemsdimensiones` a nivel req-item origen |

Pendiente tecnico:

- Confirmar campos obligatorios definitivos del POST.
- Confirmar conceptos/impuestos activos requeridos por Finnegans.
- Confirmar comportamiento de `NumeroComprobante`.
- Confirmar endpoints/maestros de proveedores, condiciones de pago, monedas e impuestos si aun no estan cerrados.

## 14. Pantallas y experiencia

### 14.1 `preoc_listar`

Objetivo:

- Mostrar el listado principal de PreOC al entrar desde el menu de Compras.
- Tomar como base visual `apps/web-php/compras_req_listar.php`: header, barra de acciones, filtros GET, tabla responsive, badges de estado y acciones por fila.
- Mostrar por defecto las PreOC creadas por el comprador login cuando `usuariocomprador = 1`.
- Si el usuario login no es comprador, pero tiene acceso a la pantalla, el filtro comprador inicia vacio y equivale a `TODOS`.

Filtros:

- Comprador.
- Estado PreOC.
- Estado ERP.
- Aprobador pendiente.
- Fecha desde.
- Fecha hasta.
- Proveedor y otros filtros operativos que cierre el contrato FE.

Reglas:

- Si usuario login tiene `usuariocomprador = 1`, el filtro comprador inicia con ese usuario.
- Si no tiene `usuariocomprador = 1`, comprador inicia en `TODOS`.
- El combo comprador lista todos los usuarios con `usuariocomprador = 1`, independiente de su estado.
- El filtro aprobador pendiente aplica para `PND` o `TODOS`; estados cerrados no tienen aprobador pendiente.
- `filtroFechaDesde` inicia por defecto en fecha actual menos 45 dias.
- `filtroFechaHasta` inicia por defecto en fecha actual.
- Los defaults de fecha son de UX/listado; los SP deben aceptar filtros vacios segun contrato.

Acciones superiores:

- `Por aprobar`: visible si el usuario login tiene `usuariopermiteaprobpreoc = 1`. Al presionar, muestra solo PreOC en que `preocaprobadoridpnd` corresponde al usuario login.
- `Nueva PreOC`: lleva al flujo de creacion de PreOC. La accion requiere `usuariocomprador = 1`; tener acceso al menu no basta para crear.

Columnas minimas a definir en contrato FE:

- codigo PreOC (`preocdoc`);
- fechas `preocfecha` y `preocfechaoc`;
- comprador;
- proveedor;
- estado documental;
- estado ERP;
- prioridad;
- total;
- aprobador pendiente;
- acciones.

### 14.2 Seleccion de requerimientos aprobados

Flujo propuesto:

1. Desde `preoc_listar`, el comprador presiona Crear.
2. Se abre `reqcompra_aprobados_listar` o pantalla equivalente.
3. Antes de listar, el comprador selecciona tipo Material o Servicio.
4. Se listan requerimientos-items pendientes de compra compatibles con ese tipo.
5. Cada fila tiene checkbox para agregar al carrito.
6. La pantalla incluye filtros para buscar sin perder lo agregado.
7. El comprador puede ver y liberar el carrito mediante boton.
8. Cuando completa la seleccion, presiona Crear PreOC.

Notas:

- Se debe cuidar el flujo de volver/avanzar del navegador para no perder la seleccion o datos guardados.
- La seleccion debe poder persistirse como borrador o estado intermedio si el flujo lo requiere.
- La fuente de datos son exclusivamente lineas `reqaprobados` con cantidad pendiente mayor a cero, incluyendo parciales.
- No se debe crear un vinculo directo y unico desde `reqaprobados` hacia PreOC.
- El vinculo de compra/anulacion se registra en `reqaprobadoshistorial` cuando el flujo confirme el movimiento que corresponda.
- La pantalla de pendientes de compra debe cerrarse como contrato separado antes del contrato PreOC, porque ahi viven el cambio de item, la anulacion de saldo pendiente y la seleccion inicial para PreOC.

### 14.3 Crear/editar PreOC

Estructura visual:

La pantalla debe organizarse como formulario transaccional, tomando REQ v1 como base de patron, con estas secciones:

1. Cabecera.
2. Items.
3. Lista de firmantes.
4. Presupuesto de compras.

Flujo:

1. Comprador selecciona `preocfechaoc`, usada para resolver presupuesto y fecha ERP.
2. Comprador selecciona proveedor (`erpproveedorid`).
3. Condicion de pago (`erpcondicionpagoid`) se precarga desde proveedor y puede editarse antes de enviar.
4. Comprador informa observacion interna (`preocobsinterna`) para uso del usuario/equipo.
5. Comprador informa observacion para ERP/formato OC (`preocobsoc`).
6. Se muestran las lineas seleccionadas desde `reqaprobados` pendientes/parciales.
7. Comprador define cantidad a comprar por linea, sin exceder saldo pendiente.
8. El sistema agrupa por item en `preocitems`.
9. El comprador informa/confirma precio neto una vez por item agrupado.
10. El sistema distribuye internamente ese precio hacia las lineas origen para calcular subtotal por requerimiento-item, presupuesto afectado y totales generales.
11. El sistema calcula impuestos en `preocimptos`.
12. El sistema resuelve presupuesto por req-item y arma `preocpptoresumen`.
13. El sistema prepara dimensiones.
14. El sistema genera firmantes default desde presupuestos y reglas por monto.
15. El comprador puede agregar manuales y reordenar.
16. Guarda `BRR` o envia `PND`.

Reglas:

- No se edita despues de la primera aprobacion.
- Si vuelve a `BRR` antes de aprobaciones, libera reserva.
- Si se elimina una linea en `PND` sin aprobaciones, se borra reserva provisional de esa linea.
- Si hay aprobaciones, no hay edicion de lineas.
- No puede finalizar/enviar si falta precio en algun item agrupado.
- Debe mostrar grilla de presupuestos usados, montos de PreOC y comparacion contra saldo disponible.
- La lista de firmantes se crea por defecto desde los presupuestos de compra resueltos por las lineas.
- Si se extiende `ComprasCatalogosService`, los metodos deben ser especificos por caso de uso PreOC, por ejemplo compradores, aprobadores PreOC, proveedores, condiciones de pago y pendientes aprobados para seleccion.
- No crear SP auxiliares por cada combo o modal salvo que el contrato defina una regla transaccional critica.
- La revision de REQ v1 debe usarse como base para adaptar patrones de listado, acciones superiores, filtros, toasts, formularios, modales y grillas, sin heredar reglas que sean exclusivas de REQ.

### 14.4 Modal de precio por item

La grilla de items agrupados debe permitir editar precio unitario neto mediante accion por fila.

El modal puede mostrar:

- item,
- unidad,
- cantidad total,
- ultimo proveedor,
- ultimo precio,
- fecha ultima compra,
- costo estandar/precio referencial del maestro si no hay historico,
- variacion entre precio historico/referencial y precio actual.

Al confirmar:

- se actualiza `preocitems`,
- se actualizan las lineas req-item relacionadas,
- se recalculan impuestos, totales y presupuesto.

### 14.5 Visualizacion

Debe mostrar:

- prioridad,
- estado documental,
- estado ERP,
- `preocfecha` y `preocfechaoc`,
- numero ERP y fecha/hora de sincronizacion si aplica,
- error ERP visible si aplica,
- presupuesto resuelto por linea,
- resumen de presupuestos usados desde `preocpptoresumen`,
- items agrupados, impuestos y totales,
- variacion de precios,
- dimensiones por linea mediante accion "ver dimensiones",
- historial de firmantes,
- comentarios funcionales,
- movimientos presupuestarios asociados.

## 15. Proveedores

La integracion de proveedores sigue la misma logica de productos:

1. sincronizar `ERP_PROVEEDORES_LIST`;
2. guardar/cotejar proveedor base por codigo;
3. consultar `ERP_PROVEEDORES_DETALLE` por cada proveedor grabado;
4. completar campos de detalle y condiciones de pago asociadas.

Requisitos:

- maestro espejo ERP,
- tabla puente proveedor-condicion de pago,
- pantalla solo consulta y exportacion a Excel,
- busqueda/autocomplete en PreOC,
- condicion de pago precargable,
- datos necesarios para impuestos/categoria fiscal si aplica.

Campos observados:

- list: `codigo`, `nombre`, `descripcion`, `activo`.
- detalle: `Codigo`, `Nombre`, `Activo`, `RazonSocial`, `Email`, `CategoriaFiscalCodigo`, `IdentificacionTributariaCodigo`, `IdentificacionTributariaNumero`, `CondicionesPago`, `ConceptoProveedorCodigo`, `CuentaProveedorCodigo`, `MonedaID_Pago_Codigo`, `USR_MedioPago`.

Las condiciones de pago se sincronizan con `ERP_CONDICIONES_PAGO_LIST` y `ERP_CONDICIONES_PAGO_DETALLE`. El detalle incluye `Tipo`, `EdicionFija`, cuentas e items con `Dias` y `Porcentaje`.

## 16. Usuarios y permisos

| Permiso | Uso |
|---|---|
| `usuariocomprador` | Crear/editar PreOC si tiene acceso al formulario |
| `usuariopermiteaprobpreoc` | Puede ser firmante PreOC |
| `usuariopermiteanularpreoc` | Permite anulacion especial cuando el estado lo permite |

Reglas:

- Tener acceso al formulario no basta para crear PreOC; debe tener `usuariocomprador = 1`.
- La anulacion especial no permite saltarse la regla de firmantes aprobados.

## 17. Fuera de alcance

- Modulo de cotizaciones.
- Multimoneda.
- Edicion de PreOC despues de primera aprobacion.
- Edicion manual de dimensiones ERP por comprador.
- Recepcion ERP dentro del tracking del REQ.
- Anulacion ERP remota desde la app; la anulacion sincronizada definida aqui es local/documental.
