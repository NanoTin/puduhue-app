# Modulo Compras - Contrato SP PreOC

> Contrato tecnico para implementar el corte funcional PreOC.
>
> Este documento define nombres, responsabilidades, parametros y salidas esperadas de los Stored Procedures de PreOC. No contiene SQL ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE.agent`
- `docs/CODEX_TASK_CONTEXT.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/modulo_compras_req_pendientes_contrato_sp.md`
- `docs/modulo_compras_presupuesto_definitivo.md`
- `docs/ADR/modulo-compras/ADR-001-modelo-presupuesto-compras.md`
- `docs/ADR/modulo-compras/ADR-002-compromiso-edicion-preoc.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `database/alter_table/09_modulo_compras_req_pendientes.sql`
- `database/alter_table/10_modulo_compras_preoc.sql`
- `database/alter_table/11_modulo_compras_presupuesto_sp.sql`

## 2. Objetivo

Implementar PreOC despues de cerrar REQ completo y pendientes de compra.

La PreOC:

- nace exclusivamente desde lineas `reqaprobados` pendientes o parciales;
- no crea un vinculo unico `reqaprobados -> PreOC`;
- registra compra/anulacion operativa en `reqaprobadoshistorial`;
- compromete presupuesto al enviar a aprobacion;
- confirma, revierte o borra reservas segun estado y aprobaciones;
- mantiene estados documentales separados de estados ERP;
- deja integracion ERP real fuera de este corte local.
- usa carrito transitorio separado de `reqaprobados` para seleccionar lineas antes de crear borrador;
- conserva historial documental de estados y de resoluciones de aprobadores separado del LOG tecnico.

## 3. Reglas transversales

- Todos los SP usan firma estandar:
  `p_in_json`, `p_in_usuarioid`, `p_in_dispositivo`, `p_in_ip`, `p_out_json`.
- Los SP de mantenimiento no deben incluir `BEGIN`, `COMMIT` ni `ROLLBACK`; PHP controla transacciones mediante `Database::callSpMaint()`.
- Los SP de consulta se consumen con `Database::callSpQuery()` y devuelven data mediante `SELECT`.
- Los campos `auditcreacion*` y `auditedicion*` nunca vienen desde `p_in_json`.
- PreOC es el unico flujo que compromete presupuesto.
- REQ no mueve presupuesto.
- La validacion de saldo PreOC es bloqueante al pasar `BRR -> PND`.
- No se guarda un unico `pptocompraid` en cabecera PreOC.
- `preocdetallereqitems.pptocompraid` guarda el presupuesto resuelto por linea.
- `preocpptoresumen` agrupa por presupuesto afectado.
- `reqaprobadoshistorial.preocdetid` referencia `preocdetallereqitems.preocdetreqitemid`; no renombrar.
- Mientras no exista DDL aprobado de adjuntos, no se permite cerrar `BRR -> PND`.
- El estado de vinculacion PreOC en REQ usa codigos `LNK_Parcial` y `LNK_Total`.

## 4. Codigo visible `preocdoc`

Formato definitivo:

- Prefijo fijo: `POC-`.
- Numero: `LPAD(preocid, 8, '0')`.
- Ejemplo: `POC-00000001`.

Reglas:

- Global, no editable por usuario, no reciclable.
- Se genera despues de obtener `preocid`.
- No cambia si la PreOC se anula.

## 5. Estados y permisos

Estados documentales:

- `BRR`
- `PND`
- `APR`
- `RCH`
- `ANL`

Estados ERP:

- sin estado mientras no aplica;
- `SNC`;
- `ERR`.

Permisos:

- Crear/editar/enviar PreOC requiere `usuariocomprador = 1`.
- Aprobar/rechazar PreOC requiere `usuariopermiteaprobpreoc = 1` y ser `preocaprobadoridpnd`.
- Anulacion especial requiere `usuariopermiteanularpreoc = 1` cuando el estado lo permita.
- Sincronizacion ERP posterior requerira `usuariopermitesynctrnerp = 1`; fuera de este corte.

## 6. SP de consulta

| SP | Objetivo | JSON entrada | SELECT esperado | Tablas principales |
|---|---|---|---|---|
| `sp_compras_preoc_listar_resumen` | Listar PreOC para pantalla principal. | `filtroBusqueda`, `filtroCompradorUsuarioId`, `filtroEstado`, `filtroEstadoErp`, `filtroAprobadorPendienteId`, `filtroFechaDesde`, `filtroFechaHasta`, `filtroProveedorId`, `filtroSoloVigentes` | Una fila por PreOC con cabecera, comprador, proveedor, estados, total, aprobador pendiente y flags UX. | `preoc`, `usuarios`, `erpproveedores`, `preocestados`, `preocestadoserp` |
| `sp_compras_preoc_listar_pendientes_aprobacion` | Listar PreOC pendientes de aprobacion del usuario login. | `filtroBusqueda`, `filtroFechaDesde`, `filtroFechaHasta`, `filtroProveedorId`, `filtroPrioridad` | PreOC en `PND` con `preocaprobadoridpnd = p_in_usuarioid`. | `preoc`, `preocfirmantes`, `usuarios`, `erpproveedores` |
| `sp_compras_preoc_consulta_por_id_resumen` | Consultar cabecera PreOC para ver/editar/aprobar. | `preocid` | Cabecera completa, proveedor, condicion pago, comprador, estados y totales. | `preoc`, `erpproveedores`, `erpcondicionespago`, `usuarios` |
| `sp_compras_preoc_consulta_por_id_detalle` | Consultar lineas origen REQ. | `preocid` | Lineas `preocdetallereqitems` con REQ origen, cantidades, precio, centro, subfamilia y presupuesto. | `preocdetallereqitems`, `reqaprobados`, `reqcompras`, `centroscosto`, `pptocompra` |
| `sp_compras_preoc_consulta_por_id_items` | Consultar items agrupados. | `preocid` | Items agrupados con cantidades, precio neto, neto total, impuestos y total. | `preocitems`, `invitems`, `invunidadesmedidas` |
| `sp_compras_preoc_consulta_por_id_imptos` | Consultar impuestos calculados por item agrupado. | `preocid` | Impuestos por item agrupado. | `preocimptos`, `preocitems` |
| `sp_compras_preoc_consulta_por_id_ppto` | Consultar resumen presupuestario. | `preocid` | Presupuestos afectados, montos, saldo antes/despues y estado. | `preocpptoresumen`, `pptocompra`, `subfamilias`, `centroscosto` |
| `sp_compras_preoc_consulta_por_id_firmantes` | Consultar firmantes PreOC. | `preocid` | Firmantes ordenados, tipo, estado, reemplazo y comentario. | `preocfirmantes`, `usuarios` |
| `sp_compras_preoc_consulta_por_id_comentarios` | Consultar comentarios funcionales. | `preocid` | Comentarios ordenados por fecha/hora. | `preoccomentarios`, `usuarios` |
| `sp_compras_preoc_consulta_por_id_movimientos_ppto` | Consultar movimientos presupuestarios asociados. | `preocid` | Movimientos `pptocompratransacciones` vinculados a `PREOCPPTORESUMEN:<id>`. | `pptocompratransacciones`, `preocpptoresumen` |
| `sp_compras_preoc_consulta_por_id_estados_historial` | Consultar historial documental de estados. | `preocid` | Estado anterior, estado nuevo, usuario, fecha/hora y observacion. | `preocestadoshistorial`, `preocestados`, `usuarios` |
| `sp_compras_preoc_consulta_por_id_resoluciones_historial` | Consultar historial de resoluciones de aprobadores por ciclo. | `preocid` | Ciclo, firmante, orden, tipo, estado, comentario, fecha/hora. | `preocfirmanteshistorial`, `usuarios` |
| `sp_compras_preoc_carrito_consulta_usuario` | Consultar carrito activo del usuario comprador. | opcional `preoccarritotoken` | Lineas agregadas al carrito, cantidades y datos REQ/item. | `preoccarrito`, `reqaprobados`, `reqcompras`, `reqcomprasdetalle` |

Reglas de filtros:

- `filtroFechaDesde` vacio usa fecha minima operativa en SP; el FE puede default a hoy menos 45 dias.
- `filtroFechaHasta` vacio usa fecha actual.
- `filtroBusqueda` aplica sobre `preocdoc`, comprador, proveedor, observaciones y numero ERP si existe.
- Si el usuario no es comprador pero accede al listado, `filtroCompradorUsuarioId` puede venir vacio y equivale a `TODOS`.

## 7. SP de mantenimiento

| SP | Objetivo | JSON entrada minimo | Salida `p_out_json` | Tablas afectadas |
|---|---|---|---|---|
| `sp_compras_preoc_crear` | Crear PreOC en `BRR`. | cabecera, `detalle[]`, `itemsAgrupados[]`, `firmantesManual[]`, `preoccarritotoken` opcional | `status`, `message`, `id`, `preocdoc`, `estado` | `preoc`, `preocdetallereqitems`, `preocitems`, `preocimptos`, `preocpptoresumen`, `preocitemsdimensiones`, `preocfirmantes`, `preoccomentarios`, `preoclog`, `preocestadoshistorial`, `preoccarrito` |
| `sp_compras_preoc_editar` | Editar PreOC permitida y guardar `BRR`. | `preocid`, cabecera editable, `detalle[]`, `itemsAgrupados[]`, `firmantesManual[]`, `comentario` opcional | `status`, `message`, `id`, `estado` | mismas tablas de crear, mas LOG e historial |
| `sp_compras_preoc_enviar_aprobacion` | Pasar `BRR -> PND` o reenviar ciclo presupuestario. | `preocid` | `status`, `message`, `id`, `estado`, `preocaprobadoridpnd` | `preoc`, `preocfirmantes`, `preocpptoresumen`, `pptocompratransacciones`, `preoclog` |
| `sp_compras_preoc_volver_borrador` | Pasar `PND -> BRR` sin aprobaciones. | `preocid`, `motivo` opcional | `status`, `message`, `id`, `estado` | `preoc`, `preocpptoresumen`, `pptocompratransacciones`, `preoclog` |
| `sp_compras_preoc_aprobar` | Aprobar firmante pendiente y avanzar flujo. | `preocid`, `comentario` opcional | `status`, `message`, `id`, `estado`, `preocaprobadoridpnd`, `aprobadoCompleto` | `preoc`, `preocfirmantes`, `preoccomentarios`, `preocpptoresumen`, `pptocompratransacciones`, `reqaprobados`, `reqaprobadoshistorial`, `preoclog` |
| `sp_compras_preoc_rechazar` | Rechazar PreOC con comentario obligatorio. | `preocid`, `comentario` | `status`, `message`, `id`, `estado` | `preoc`, `preocfirmantes`, `preoccomentarios`, `preocpptoresumen`, `pptocompratransacciones`, `preoclog` |
| `sp_compras_preoc_rearmar` | Pasar `RCH -> BRR` para corregir y reiniciar ciclo. | `preocid`, `comentario` | `status`, `message`, `id`, `estado` | `preoc`, `preocfirmantes`, `preocfirmanteshistorial`, `preoccomentarios`, `preocestadoshistorial`, `preoclog` |
| `sp_compras_preoc_anular` | Anular PreOC cuando el estado lo permita. | `preocid`, `comentario` | `status`, `message`, `id`, `estado`, `estadoErp` | `preoc`, `preoccomentarios`, `preocpptoresumen`, `pptocompratransacciones`, `preoclog` |
| `sp_compras_preoc_carrito_agregar` | Agregar linea aprobada al carrito del comprador. | `reqaprobadoid`, `cantidad`, `preoccarritotoken`, `preoctipo` | `status`, `message`, `id` | `preoccarrito` |
| `sp_compras_preoc_carrito_liberar` | Liberar linea o carrito activo del comprador. | `preoccarritoid` o `preoccarritotoken` | `status`, `message` | `preoccarrito` |

Los SP de PreOC deben invocar o coordinar con los SP presupuestarios ya contratados:

- `sp_compras_preoc_ppto_reservar`
- `sp_compras_preoc_ppto_confirmar`
- `sp_compras_preoc_ppto_revertir`
- `sp_compras_preoc_ppto_borrar_reserva_provisional`

La coordinacion transaccional final la controla PHP con `callSpMaint()`.

## 8. Payload cabecera

Campos de entrada:

- `preoctipo`: `1` Material, `2` Servicio.
- `preocfechaoc`: fecha seleccionada por usuario para presupuesto y ERP.
- `erpproveedorid`.
- `erpcondicionpagoid` opcional.
- `erpprovinciaid` opcional.
- `preocobsinterna` opcional.
- `preocobsoc` opcional.
- `preocprioridad`: `1` Normal, `2` Alta.
- `preoccarritotoken` opcional si la PreOC nace desde carrito.

Campos resueltos por SP/sistema:

- `preocid`.
- `preocdoc`.
- `preocfecha`.
- `compradorusuarioid = p_in_usuarioid`.
- `preocworkflowcod`.
- `erpmonedacod = 'PES'`.
- estados, aprobador pendiente, totales y auditoria.

## 9. Payload `detalle[]`

Cada elemento contiene:

- `reqaprobadoid`.
- `preocdetcantidad`.
- `erpprovinciaid` opcional por linea.
- `preocdetobs` opcional.

El SP resuelve:

- `invitemid`;
- `preocdetitemcod`;
- `preocdetdsc`;
- `centrocostoid`;
- `subfamiliaid`;
- `pptocompraid`;
- `preocdetdsccc`;
- `invunidmedid`;
- `preocdetprecioneto`;
- `preocdetsubtotalneto`.

Validaciones:

- `reqaprobadoid` existe.
- `reqaprobadocantidadpendiente > 0`.
- `preocdetcantidad > 0`.
- `preocdetcantidad <= reqaprobadocantidadpendiente`.
- No mezclar Material y Servicio.
- El item actual viene desde `reqaprobados`; no se cambia en PreOC.
- Presupuesto debe resolverse por `preocfechaoc`, `subfamiliaid`, `centrocostoid`.

## 10. Payload `itemsAgrupados[]`

Cada elemento contiene:

- `invitemid`.
- `preocitemprecioneto`.

Reglas:

- Debe existir un elemento por cada item agrupado derivado de `detalle[]`.
- El precio neto unitario se informa una vez por item agrupado.
- El SP distribuye `preocitemprecioneto` hacia todas las lineas `preocdetallereqitems` del mismo `invitemid`.
- En `BRR` se puede guardar precio cero; no se puede enviar a aprobacion con precio cero o faltante.
- Si una linea se elimina desde una PreOC editable antes de aprobacion, vuelve a quedar disponible por saldo pendiente; no depende del carrito convertido.

## 11. Items agrupados, precio e impuestos

Reglas:

- `preocitems` agrupa por `preocid + invitemid`.
- El comprador informa precio neto una vez por item agrupado.
- El precio agrupado se distribuye internamente hacia lineas `preocdetallereqitems`.
- No se puede enviar a aprobacion si algun item agrupado no tiene precio neto mayor a cero.
- Cada item debe tener `invitems.erptasaimpositivaid` informado.
- `preocimptos.imptoid` guarda el `erptasasimpositivas.erptasaimpositivaid` usado para el calculo.
- `preocimptotasa` guarda snapshot de `erptasasimpositivas.erptasaimpositivaporcentaje`.
- La tasa se calcula como porcentaje: por ejemplo `19.0000` equivale a `0.19`.
- `preocimptonetototal = preocitemnetototal`.
- `preocimptomonto = preocimptonetototal * (preocimptotasa / 100)`.
- `preocitems.preocitemimptostotal = SUM(preocimptos.preocimptomonto)` por item agrupado.
- `preoc.preocimptostotal = SUM(preocitems.preocitemimptostotal)`.
- `preoctotal = preocnettotal + preocimptostotal`.
- Si `invitems.erptasaimpositivaid` es NULL, la tasa no existe, esta inactiva o no tiene porcentaje, el item no se puede agregar/enviar y se informa contactar a Administracion.
- `preocimptos` queda como detalle tecnico para validar totales y preparar JSON ERP futuro; no implica llamada real a ERP.

## 12. Firmantes

La lista se genera desde:

1. Responsable de cada presupuesto afectado.
2. Administrador de cada presupuesto afectado.
3. Colaborador de cada presupuesto afectado, si existe.
4. Aprobadores por monto.
5. Firmantes manuales.

Reglas:

- Deduplicar usuarios.
- Los firmantes default no se pueden remover.
- Los manuales no pueden duplicar usuarios ya presentes.
- Aplica inactividad/reemplazo igual que REQ.
- `preocaprobadoridpnd` debe coincidir con el firmante pendiente vigente.
- Cada envio/reenvio a aprobacion abre un ciclo de aprobacion nuevo.
- Antes de reiniciar firmantes por rechazo/rearmado, el SP debe conservar snapshot en `preocfirmanteshistorial`.

## 13. Historial documental y de aprobadores

Tablas requeridas:

- `preocestadoshistorial`: registra estado anterior, estado nuevo, usuario, fecha/hora y observacion.
- `preocfirmanteshistorial`: registra snapshot de resoluciones por ciclo antes de reiniciar firmantes o al cerrar resoluciones relevantes.

Reglas:

- Todo cambio de `preoc.preocestadoid` inserta una fila en `preocestadoshistorial`.
- La creacion registra `NULL -> BRR`.
- El envio registra `BRR -> PND`.
- La aprobacion completa registra `PND -> APR`.
- El rechazo registra `PND -> RCH`.
- Rearmar registra `RCH -> BRR`.
- Anular registra `<estado anterior> -> ANL`.
- El LOG tecnico no reemplaza estos historiales funcionales.

## 14. Presupuesto

Eventos:

| Evento | SP presupuestario | Regla |
|---|---|---|
| Guardar `BRR` | ninguno | No reserva. |
| Enviar `BRR -> PND` | `sp_compras_preoc_ppto_reservar` | Valida saldo y crea `POC_RESERVA` negativa. |
| Volver `PND -> BRR` sin aprobaciones | `sp_compras_preoc_ppto_borrar_reserva_provisional` | Borra reservas provisionales sin reversa. |
| Aprobar completamente | `sp_compras_preoc_ppto_confirmar` | Confirma reserva. |
| Rechazar/anular | `sp_compras_preoc_ppto_revertir` | Reversa positiva cuando corresponde. |

Movimientos:

- `pptocompramoduloorigen = 'PREOC'`.
- `pptocompranrodocumentoorigen = preocid`.
- `pptocomprareflinea = 'PREOCPPTORESUMEN:<preocpptoresumenid>'`.
- Cada envio a aprobacion abre ciclo nuevo en `pptocompregruppomovimiento`: `PREOC:<preocid>:CICLO:<n>`.

## 15. Actualizacion de pendientes al aprobar

Al aprobar completamente:

- Insertar `reqaprobadoshistorial` por linea comprada:
  - `histtipo = 'COMPRA'`;
  - `histcantidadpendienteantes`;
  - `histcantidad = preocdetcantidad`;
  - `histprecioneto = preocdetprecioneto`;
  - `histitemcod`;
  - `histitemdsc`;
  - `preocid`;
  - `preocdetid = preocdetallereqitems.preocdetreqitemid`.
- Actualizar `reqaprobados`:
  - restar cantidad pendiente;
  - sumar cantidad comprada;
  - recalcular estado.
- Recalcular `reqcompras.reqcompraestadopreocid` para cada REQ origen:
  - `LNK_Parcial` si existe compra PreOC y aun queda saldo pendiente operativo asociado a compra;
  - `LNK_Total` si todas las lineas aprobadas comprables del REQ quedaron vinculadas por compra PreOC.
- Las anulaciones operativas sin compra PreOC no deben marcar vinculacion PreOC por si solas.

## 16. Carrito de seleccion PreOC

Reglas:

- El carrito vive en tabla separada `preoccarrito`; no se agregan columnas transitorias a `reqaprobados`.
- La consulta de pendientes para seleccionar PreOC debe excluir lineas con carrito activo de cualquier usuario.
- La consulta tambien debe excluir lineas ya tomadas por PreOC vigente en `BRR` o `PND`, mientras no hayan sido eliminadas/liberadas.
- Si el usuario tiene carrito activo anterior, el FE debe permitir continuar o liberar.
- Al crear PreOC desde carrito, las filas de carrito pasan a estado convertido/liberado y la disponibilidad queda controlada por `preocdetallereqitems`.
- La validacion final siempre se repite en `sp_compras_preoc_crear`.

## 17. Matriz de anulacion PreOC

| Estado | Condicion | Regla |
|---|---|---|
| `BRR` | Sin reserva | Permite anular sin movimiento presupuestario. |
| `PND` | Sin aprobaciones | Permite anular borrando reserva provisional sin reversa. |
| `PND` | Con alguna aprobacion | No permite anulacion directa; debe resolverse por rechazo. |
| `APR` | No sincronizada o ERP `ERR` | Permite anular con permiso especial y genera reversa positiva cuando corresponde. |
| `APR` + ERP `SNC` | Sincronizada | Permite anulacion local especial; no llama ERP y mantiene estado ERP `SNC`. |
| `RCH` | Rechazada | No se anula; puede rearmarse si corresponde. |
| `ANL` | Ya anulada | No repite accion. |

Reglas:

- Comentario obligatorio de mas de 10 caracteres.
- El modal/flujo debe advertir que la anulacion local no realiza cambios en ERP y que la accion ERP debe hacerse directamente en Finnegans cuando corresponda.

## 18. Adjuntos PreOC

Regla funcional vigente:

- Se requiere al menos un adjunto antes de enviar a aprobacion.
- No bloquea guardar borrador.

Bloqueo tecnico:

- El DDL de `preocadjuntos` y maestro de extensiones/tipos sigue preliminar/no autorizado.
- La implementacion de `sp_compras_preoc_enviar_aprobacion` debe validar adjuntos solo cuando exista DDL aprobado.
- Si el corte PreOC se implementa antes de aprobar DDL de adjuntos, debe reportarse bloqueo funcional o dejar el envio a aprobacion fuera de alcance.

## 19. ERP / Finnegans

Fuera de este corte:

- POST real a ERP/Finnegans.
- Reintentos ERP.
- Confirmacion de campos obligatorios definitivos.
- Conceptos/impuestos Finnegans.

El corte local puede mantener columnas ERP (`preocestadoerpid`, `erptransaccionid`, `erpnumerodoc`, `erperror`, `erprespuestajson`) sin ejecutar llamadas reales.

## 20. Fuera de alcance

- Cotizaciones.
- Multimoneda.
- Edicion despues de primera aprobacion.
- DDL nuevo no autorizado.
- Anulacion ERP remota.
- Ejecucion real contra ERP/Finnegans.

## 21. DDL adicional requerido antes de implementar PreOC completo

- `preoccarrito`.
- `preocestadoshistorial`.
- `preocfirmanteshistorial`.
- DDL aprobado de `preocadjuntos` y maestro de extensiones/tipos.
- Ajuste de `reqcompraestadopreoc` a codigos `LNK_Parcial` y `LNK_Total`.

## 22. Validaciones de implementacion

Cuando se implemente SQL:

- Validar sintaxis SQL antes de entregar.
- Confirmar que ningun SP contiene `BEGIN`, `COMMIT` ni `ROLLBACK`.
- No ejecutar SQL contra BD real sin autorizacion explicita.

Cuando se implemente PHP:

- Ejecutar `php -l` en PHP nuevo o modificado.
- Ejecutar `git diff --check`.
