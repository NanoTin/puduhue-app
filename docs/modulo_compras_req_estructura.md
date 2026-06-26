# Modulo REQ (Requerimiento de Compra) - Estructura de Datos v4

> Diseno funcional y logico vigente para Requerimientos de Compra.
>
> Fuente normativa principal: `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`.
>
> Este documento reemplaza la version v3, que mantenia estados y reglas ya superadas (`CSO`, `VNC` como estado principal, presupuesto REQ bloqueante e inactividad basada en funcionarios).

## 0. Decisiones vigentes

| # | Decision | Resolucion vigente |
|---|---|---|
| 1 | Estados REQ | `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL` |
| 2 | Cambios solicitados | No existe `CSO`; se usa `RCH` con comentario obligatorio |
| 3 | Vinculo con PreOC | No es estado principal; se maneja en columna separada de estado PreOC |
| 4 | REQ rechazado | Corregible y reenviable |
| 5 | Reenvio tras rechazo | Recalcula firmantes default, conserva manuales activos y reaplica inactividad |
| 6 | Edicion | No se permite editar si ya existe al menos una aprobacion o si esta aprobado |
| 7 | Concurrencia | Aprobar/Rechazar siempre revalida estado en backend/SP |
| 8 | Presupuesto REQ | Informativo, no bloqueante y no genera movimientos |
| 9 | Fecha funcional REQ | La define sistema/BD; se actualiza en cada edicion permitida |
| 10 | Firmantes default REQ | Jefe de centro y jefe tecnico del centro, no removibles |
| 11 | Inactividad | Basada en usuarios aprobadores, no en funcionarios |
| 12 | Centro operativo | Sale de `usuarioscentroscosto`; funcionario es opcional |
| 13 | Items precio cero | No se pueden agregar al REQ |
| 14 | Pendientes de compra | `reqaprobados` no apunta a una PreOC unica; el vinculo vive en historial |
| 15 | Anulacion de saldo | Solo sobre cantidad pendiente y con motivo obligatorio |
| 16 | Fuera de presupuesto | Si falta saldo, se agregan autorizadores fuera de presupuesto al final |
| 17 | Siguiente aprobador | Se resuelve con funcion interna que valida vigencia e inactividad |
| 18 | Creacion REQ | Item y firmante manual se agregan con boton `+` y modal |

## 1. Tablas nuevas o ajustadas

| Tabla | Tipo | Descripcion |
|---|---|---|
| `reqcompras` | Transaccional | Cabecera del Requerimiento |
| `reqcomprasdetalle` | Transaccional | Detalle de items del Requerimiento |
| `reqcomprasfirmantes` | Transaccional | Lista de aprobadores por REQ |
| `reqcomprascomentarios` | Funcional | Comentarios visibles de aprobacion, rechazo y anulacion |
| `reqcompraslog` | LOG | Auditoria tecnica del REQ |
| `reqcomprasestados` | Maestro | Catalogo de estados documentales |
| `reqcompraestadopreoc` | Maestro | Catalogo de estado de vinculacion con PreOC |
| `reqcompraspptosnapshot` | Referencia | Copia actualizable del calculo presupuestario del REQ |
| `reqaprobados` | Transaccional | Lineas aprobadas listas para compra |
| `reqaprobadoshistorial` | Transaccional | Historial de compras/anulaciones por linea aprobada |
| `reqaprobadoscambios` | Transaccional | Cambios de item realizados por comprador |
| `aprobadoresperiodoinactividad` | Transaccional | Periodos de inactividad y reemplazos de usuarios aprobadores |
| `aprobadoresperiodoinactividadlog` | LOG | Trazabilidad de creacion, edicion e inactivacion de periodos |
| `usuarioscentroscosto` | Asociacion | Centros de costo accesibles por usuario |
| `funcionarios` | Maestro | Funcionarios/solicitantes, con RUT como PK funcional |

### Tablas existentes a modificar

| Tabla | Cambio |
|---|---|
| `usuarios` | Separar permisos de aprobacion REQ, aprobacion PreOC, comprador, anular PreOC, editar precios, crear/editar item y sincronizar transacciones ERP |
| `invitems` | Mantener tipo, precio referencial, comprable, uso funcional, estado local y marca `iteminglocal` |
| `centroscosto` | Mantener atributos locales de jefe de centro y jefe tecnico |

## 2. `reqcompras` - Cabecera del Requerimiento

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `reqcompraid` | INT PK AI | NO | PK interna |
| `reqcompracod` | VARCHAR(20) | NO | Codigo visible, por ejemplo `REQ-000001` |
| `reqcompratipo` | TINYINT | NO | 1=Material, 2=Servicio; nunca mixto |
| `reqcomprafecha` | DATE | NO | Fecha funcional definida por sistema/BD; se actualiza en cada edicion permitida |
| `centrocostoid` | INT FK | NO | Centro elegido desde `usuarioscentroscosto` |
| `funcionariorut` | VARCHAR(20) FK | SI | Solicitante asignado opcional |
| `reqcompraobs` | TEXT | SI | Observacion general |
| `reqcompraprioridad` | TINYINT | NO | 1=Normal, 2=Alta; efecto visual y correo |
| `reqcompraestadoid` | INT FK | NO | Estado documental |
| `reqcompraestadopreocid` | INT FK | SI | Estado de vinculacion con PreOC |
| `reqaprobadoridpnd` | INT FK | SI | Usuario aprobador pendiente cuando esta en `PND`; se limpia al rechazar o aprobar completamente |
| `reqaprobacionfecha` | DATE | SI | Fecha de aprobacion completa, para KPI |
| `reqadvertenciapptocompra` | TINYINT(1) | NO | 1 si existe advertencia presupuestaria informativa |
| `reqfuerapptocompra` | TINYINT(1) | NO | 1 si requiere autorizador fuera de presupuesto |
| `reqcompranettotal` | DECIMAL(15,2) | NO | Total neto recalculado desde detalle |
| `reqcompravig` | TINYINT(1) | NO | Vigente/baja logica |
| + auditoria |  |  | Columnas estandar del proyecto |

Notas:

- `empresaid` no se resuelve desde el centro de costo ERP. El ERP usa una sola base de centros para todas las empresas; una separacion por empresa quedaria como dato local futuro si el cliente lo solicita.
- Si el usuario no tiene centros asignados, no puede crear REQ y se informa: "No tiene centro(s) asignado(s). Informar a Administracion."
- Si el usuario tiene varios centros, puede elegir cualquiera activo entre sus centros asignados.

## 3. `reqcomprasdetalle` - Detalle de items

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `reqcompradetid` | INT PK AI | NO | PK interna |
| `reqcompraid` | INT FK | NO | FK a cabecera |
| `reqcompradetlinea` | INT | NO | Numero de linea |
| `invitemid` | INT FK | NO | Item seleccionado |
| `subfamiliaid` | INT FK | NO | Subfamilia del item al momento del REQ |
| `reqcompradetitemcod` | VARCHAR(50) | NO | Snapshot del codigo del item |
| `reqcompradetdsc` | VARCHAR(200) | NO | Snapshot de descripcion al momento del REQ |
| `invunidmedid` | INT FK | NO | Unidad de medida |
| `reqcompradetcantidad` | DECIMAL(15,4) | NO | Cantidad requerida |
| `reqitemcantanulada` | DECIMAL(15,4) | NO | Cantidad anulada acumulada por comprador desde pendientes |
| `reqcompradetprecioneto` | DECIMAL(15,2) | NO | Precio neto unitario usado en el REQ |
| `reqcompradettotalneto` | DECIMAL(15,2) | NO | Cantidad x precio |
| `reqcompradetobs` | TEXT | SI | Observacion por linea |
| `reqcompradetitemmodificado` | TINYINT(1) | NO | 1 si el item fue cambiado en pendientes de compra |
| `reqcompradetadvertenciappto` | TINYINT(1) | NO | 1 si esta linea participa en una advertencia presupuestaria |
| `reqcompradetultreqfecha` | DATE | SI | Fecha del ultimo REQ para el mismo centro e item |
| `reqcompradetultreqcantidad` | DECIMAL(15,4) | SI | Cantidad solicitada en ese ultimo REQ |

Reglas:

- Mostrar solo items comprables, activos y del mismo tipo del REQ.
- Un item con precio cero no puede agregarse al REQ. Debe indicarse que se contacte a Administracion.
- No se permite mezclar Material y Servicio.
- La prioridad visual del REQ no altera reglas de aprobacion.
- `subfamiliaid` facilita el cruce con `reqcompraspptosnapshot` para saber que items originaron cada grupo presupuestario.
- La fecha ultimo requerimiento se obtiene buscando la maxima fecha para el mismo centro de costo e item.
- La cantidad ultimo requerimiento corresponde a la cantidad solicitada para esa fecha.
- Los datos de ultimo requerimiento son informativos y deben mostrarse en grilla y tarjetas de visualizacion.

## 4. Estados

### 4.1 Estados documentales del REQ

| Codigo | Descripcion | Editable | Notas |
|---|---|---|---|
| `BRR` | Borrador | Si | Creado o guardado sin enviar |
| `PND` | Pendiente de aprobacion | No directa | Enviado a firmantes |
| `EDT` | En edicion | Si | Creador editando antes de que exista aprobacion |
| `APR` | Aprobado | No | Todos los firmantes aprobaron |
| `RCH` | Rechazado | Si | Corregible y reenviable |
| `ANL` | Anulado | No | Definitivo |

### 4.2 Estado de vinculacion con PreOC

| Codigo | Descripcion | Regla |
|---|---|---|
| Sin estado | Sin vinculo | No aplica o no tiene compras asociadas |
| `VNC_Parcial` | Vinculado parcial | Existe compra parcial o saldo pendiente/anulado |
| `VNC_Total` | Vinculado total | Toda la cantidad requerida quedo comprada o anulada operativamente |

## 5. Flujo REQ

1. Crear/guardar `BRR`.
2. Seleccionar centro desde `usuarioscentroscosto`.
3. Funcionario solicitante es opcional.
4. Agregar items compatibles con el tipo REQ.
5. Calcular presupuesto informativo agrupando por subfamilia + centro de costo.
6. Guardar advertencias y snapshot actualizable de presupuesto.
7. Generar firmantes default: jefe de centro y jefe tecnico, si existen.
8. Permitir firmantes manuales activos con permiso de aprobar REQ.
9. Si no hay firmantes, advertir que el documento quedara como borrador si continua.
10. Al finalizar/enviar, si hay falta de saldo, agregar autorizadores fuera de presupuesto al final.
11. Enviar `BRR -> PND` solo si existe al menos un firmante activo.
12. Resolver siguiente aprobador con validacion de vigencia e inactividad.
13. Aprobar secuencialmente.
14. Al aprobar el ultimo firmante habilitado: estado `APR`, limpiar aprobador pendiente, guardar fecha de aprobacion y crear `reqaprobados`.
15. Al rechazar: estado `RCH`, limpiar aprobador pendiente y registrar comentario obligatorio.
16. Al reenviar desde `RCH`: recalcular default, conservar manuales activos, aplicar inactividad y volver a `PND`.
17. Al anular: estado `ANL`, sin modificaciones posteriores.

## 6. Concurrencia en aprobacion

Aunque la pantalla valide que un REQ esta en `PND`, Aprobar y Rechazar deben validar nuevamente en backend/SP.

Si el creador paso el REQ a `EDT` mientras un aprobador lo tenia abierto:

- se rechaza la accion,
- se muestra mensaje indicando que el requerimiento esta siendo editado,
- al confirmar, se redirige al listado de pendientes de aprobacion.

## 7. `reqcomprasfirmantes`

| Columna | Tipo logico | NULL | Descripcion / regla |
|---|---|---|---|
| `reqcomprafirmanteid` | INT PK AI | NO | PK interna |
| `reqcompraid` | INT FK | NO | REQ |
| `firmanteusuarioid` | INT FK | NO | Usuario aprobador |
| `firmanteorden` | INT | NO | Orden secuencial |
| `firmantetipo` | VARCHAR(20) | NO | JEF_CC/JEF_TEC/MANUAL/FUERA_PPTO/REEMPLAZO u otro |
| `firmantedefault` | TINYINT(1) | NO | 1 si viene de regla default y no se puede remover |
| `firmantefuerapptocompra` | TINYINT(1) | NO | 1 si participa como autorizador fuera de presupuesto |
| `firmantemotivoinclusion` | VARCHAR(50) | SI | Motivo de inclusion, por ejemplo `REQ_SIN_SALDO_PPTO` |
| `firmanteestado` | VARCHAR(5) | NO | PND/APR/RCH/INA/NVG |
| `firmantefechahora` | DATETIME | SI | Fecha/hora del evento |
| `firmantecomentario` | TEXT | SI | Comentario de aprobacion/rechazo o inactividad |
| `firmantereemplazodeid` | INT FK | SI | Firmante inactivo al que reemplaza |

Reglas:

- Los default no se remueven.
- No se permiten duplicados.
- Solo usuarios activos con permiso de aprobar REQ.
- Si un default esta inactivo, queda como `Inactivo` y se agrega reemplazante.
- El comentario de rechazo debe tener mas de 10 caracteres.
- Jefe de centro, jefe tecnico y manuales pueden reordenarse entre si.
- Los firmantes fuera de presupuesto se agregan al final, no se remueven y no se reordenan.
- Si un usuario ya existe en la lista y tambien califica como fuera de presupuesto, se mantiene una sola fila y se marca el motivo correspondiente.

### 7.1 Autorizadores fuera de presupuesto

Cuando el snapshot presupuestario detecta falta de saldo:

1. Se setea advertencia en cabecera.
2. Se agregan al final los usuarios activos con `reqautorizadorfuerapptocompra = 1`.
3. Se ordenan por `reqautorizadorfuerapptocompraorden`.
4. Se agregan una sola vez aunque existan multiples deficits.
5. Se les marca `firmantedefault = 1`, `firmantefuerapptocompra = 1` y motivo `REQ_SIN_SALDO_PPTO`.
6. Se aplica inactividad/reemplazo.

`reqautorizadorfuerapptocompraorden` debe ser unico para usuarios con `reqautorizadorfuerapptocompra = 1`; el resto usa orden `0`.

En la grilla de firmantes:

- estos firmantes quedan fijos al final,
- sus botones Subir/Bajar quedan bloqueados,
- el firmante inmediatamente anterior a ellos no puede bajar para quedar despues de un autorizador fuera de presupuesto.

### 7.2 Resolucion del siguiente aprobador

Cada vez que se necesita avanzar al siguiente firmante, el backend/SP debe ejecutar una funcion de resolucion:

1. Buscar el siguiente firmante por orden.
2. Si el usuario no esta vigente:
   - marcar firmante `NVG`,
   - registrar comentario funcional,
   - registrar LOG,
   - continuar con el siguiente.
3. Si el usuario esta vigente pero tiene periodo de inactividad:
   - marcar firmante original `INA`,
   - registrar comentario con motivo de inactividad,
   - insertar reemplazante inmediatamente despues,
   - reordenar la lista,
   - asignar reemplazante como pendiente.
4. Si el usuario esta vigente y sin inactividad:
   - asignarlo a `reqaprobadoridpnd`.

Si no queda ningun firmante habilitado, el REQ queda `APR` solo si ya existe al menos una aprobacion efectiva y no quedan aprobaciones pendientes validas. Si esto ocurre antes de cualquier aprobacion efectiva, el REQ no debe avanzar automaticamente a `APR`.

`reqaprobadoridpnd` es una denormalizacion controlada para consultas rapidas y debe coincidir con el firmante pendiente vigente. La pantalla de pendientes filtra por esta columna contra el usuario login.

## 8. `reqcomprascomentarios`

Tabla funcional separada del LOG tecnico.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `reqcomentarioid` | INT PK AI | NO | PK |
| `reqcompraid` | INT FK | NO | REQ |
| `usuarioid` | INT FK | NO | Usuario que comenta |
| `reqcomentariotipo` | VARCHAR(20) | NO | APR/RCH/ANL/INFO u otro tipo funcional |
| `reqcomentariotxt` | TEXT | NO | Comentario visible |
| `reqcomentariofechahora` | DATETIME | NO | Fecha/hora |

El rechazo siempre genera comentario funcional obligatorio. La aprobacion puede generar comentario opcional.

## 9. `reqcompraspptosnapshot`

Guarda la copia actualizable del calculo presupuestario usado por el REQ.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `reqpptosnapshotid` | INT PK AI | NO | PK |
| `reqcompraid` | INT FK | NO | REQ |
| `subfamiliaid` | INT FK | NO | Subfamilia agrupada |
| `centrocostoid` | INT FK | NO | Centro del REQ |
| `pptocompraid` | INT FK | SI | Presupuesto encontrado, si existe |
| `reqpptomonto` | DECIMAL(15,2) | NO | Monto requerido por grupo |
| `reqpptosaldodisponible` | DECIMAL(15,2) | SI | Saldo disponible calculado en ese momento |
| `reqpptomontootroscurso` | DECIMAL(15,2) | NO | Otros REQ en curso para la misma combinacion |
| `reqpptomontoaprobadospend` | DECIMAL(15,2) | NO | REQ aprobados pendientes de compra |
| `reqpptosaldoproyectado` | DECIMAL(15,2) | SI | Disponible actual - otros curso - aprobados pendientes - este REQ |
| `reqpptoporcentajeuso` | DECIMAL(9,4) | SI | Proporcion de este REQ sobre disponible actual |
| `reqpptodeficit` | DECIMAL(15,2) | NO | Monto de deficit estimado, si aplica |
| `reqpptoadvertencia` | TINYINT(1) | NO | 1 si no hay presupuesto o saldo suficiente |
| `reqpptofuerapptocompra` | TINYINT(1) | NO | 1 si requiere autorizacion fuera de presupuesto |
| `reqpptofechahora` | DATETIME | NO | Fecha/hora del calculo |

Reglas:

- REQ solo informa, no bloquea.
- REQ no genera movimientos de presupuesto.
- Si el REQ se edita, se recalcula y reemplaza/actualiza la copia.
- El boton "Analisis de ppto de compra" debe estar disponible para todo usuario que pueda visualizar el REQ.
- El analisis se muestra agrupado por temporada, subfamilia y centro de costo.

## 10. `reqaprobados`

Tabla operativa de lineas aprobadas listas para compra. Mantiene una fila por linea aprobada; no contiene `preocid`.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `reqaprobadoid` | INT PK AI | NO | PK |
| `reqcompradetid` | INT FK | NO | Linea original del REQ |
| `reqcompraid` | INT FK | NO | REQ |
| `invitemid` | INT FK | NO | Item actual asociado |
| `reqaprobadoitemcod` | VARCHAR(50) | NO | Snapshot codigo |
| `reqaprobadoitemdsc` | VARCHAR(200) | NO | Snapshot descripcion |
| `invunidmedid` | INT FK | NO | Unidad de medida snapshot/relacion |
| `reqaprobadocantidadreq` | DECIMAL(15,4) | NO | Cantidad requerida |
| `reqaprobadocantidadpendiente` | DECIMAL(15,4) | NO | Cantidad pendiente de compra |
| `reqaprobadocantidadcomprada` | DECIMAL(15,4) | NO | Cantidad comprada acumulada |
| `reqaprobadocantidadanulada` | DECIMAL(15,4) | NO | Cantidad anulada acumulada |
| `reqaprobadoprecioneto` | DECIMAL(15,2) | NO | Precio neto de referencia |
| `reqaprobadoestado` | TINYINT | NO | 1=Pendiente, 2=Parcial, 3=Completa, 4=Anulada |
| `reqaprobadofecha` | DATE | NO | Fecha de aprobacion del REQ |

Regla de consistencia:

`reqaprobadocantidadreq = reqaprobadocantidadpendiente + reqaprobadocantidadcomprada + reqaprobadocantidadanulada`

## 11. `reqaprobadoshistorial`

Registra compras y anulaciones de saldo pendiente.

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `reqaprobadohistid` | INT PK AI | NO | PK |
| `reqaprobadoid` | INT FK | NO | Linea aprobada |
| `preocid` | INT FK | SI | PreOC asociada, si es compra |
| `preocdetid` | INT FK | SI | Linea PreOC asociada, si es compra |
| `histtipo` | VARCHAR(20) | NO | COMPRA/ANULACION/AJUSTE u otro |
| `histcantidadpendienteantes` | DECIMAL(15,4) | NO | Cantidad pendiente antes de aplicar el movimiento |
| `histcantidad` | DECIMAL(15,4) | NO | Cantidad afectada |
| `histprecioneto` | DECIMAL(15,2) | SI | Precio al momento, si aplica |
| `histitemcod` | VARCHAR(50) | SI | Snapshot rapido |
| `histitemdsc` | VARCHAR(200) | SI | Snapshot rapido |
| `histusuarioid` | INT FK | NO | Usuario |
| `histfechahora` | DATETIME | NO | Fecha/hora |
| `histobs` | TEXT | SI | Motivo/observacion |

Anulacion:

- solo sobre cantidad pendiente,
- motivo obligatorio,
- visible para el solicitante,
- si ya se compro todo, no permite anular.
- `histcantidadpendienteantes` permite reconstruir rapidamente el saldo posterior del evento para consultas historicas.

## 12. `reqaprobadoscambios`

| Columna | Tipo logico | NULL | Descripcion |
|---|---|---|---|
| `reqcambioid` | INT PK AI | NO | PK |
| `reqaprobadoid` | INT FK | NO | Linea afectada |
| `invitemidoriginal` | INT FK | NO | Item original |
| `invitemidnuevo` | INT FK | NO | Item nuevo |
| `reqcambioobs` | TEXT | NO | Motivo obligatorio |
| `reqcambiofechahora` | DATETIME | NO | Fecha/hora |
| `reqcambiousuarioid` | INT FK | NO | Comprador |

Validaciones:

- no existen transacciones posteriores,
- no duplicar item ya existente en el REQ original,
- no cambiar Material por Servicio ni Servicio por Material.

## 13. Maestros y bases compartidas

### 13.1 `usuarioscentroscosto`

| Columna | Tipo logico | Descripcion |
|---|---|---|
| `usuarioid` | INT FK | Usuario |
| `centrocostoid` | INT FK | Centro asignado |
| `usucendefault` | TINYINT(1) | Centro default del usuario |
| `usucenactivo` | TINYINT(1) | Asociacion activa |
| + auditoria |  | Auditoria estandar |

Debe existir solo un default activo por usuario.

### 13.2 `centroscosto`

Datos ERP base no editables desde pantalla:

- codigo,
- descripcion,
- activo.

Atributos locales editables:

- jefe de centro,
- jefe tecnico.

`DIMCTC` se resuelve internamente desde el codigo del centro. No se expone como dato editable del comprador.

### 13.3 `funcionarios`

| Columna | Tipo logico | Descripcion |
|---|---|---|
| `funcionariorut` | VARCHAR(20) PK | RUT validado |
| `funcionarionombre` | VARCHAR(100) | Nombre |
| `funcionariocargo` | VARCHAR(100) | Cargo |
| `funcionarioemail` | VARCHAR(150) | Correo |
| `funcencos` | INT/VARCHAR | Centro de costo asociado |
| `funcionarioactivo` | TINYINT(1) | Activo |

Funcionario es opcional en REQ.

### 13.4 `aprobadoresperiodoinactividad`

| Columna | Tipo logico | Descripcion |
|---|---|---|
| `aprobadorperiodoid` | INT PK AI | PK |
| `usuarioid` | INT FK | Aprobador ausente |
| `usuarioreemplazoid` | INT FK | Reemplazante |
| `motivoinactividad` | TINYINT/VARCHAR | Ausencia, vacaciones, licencia, permiso, otro |
| `fechadesde` | DATE | Inicio |
| `fechahasta` | DATE | Fin incluido |
| `periodoactivo` | TINYINT(1) | Vigente |
| + auditoria |  | Auditoria estandar |

Reglas:

- No se elimina fisicamente un periodo de inactividad existente.
- Si deja de aplicar, se cambia su estado a inactivo.
- Toda creacion, edicion o inactivacion debe dejar LOG.

### 13.5 `aprobadoresperiodoinactividadlog`

Tabla de auditoria especifica para periodos de inactividad.

| Columna | Tipo logico | Descripcion |
|---|---|---|
| `aprobadorperiodologid` | INT PK AI | PK |
| `aprobadorperiodoid` | INT FK | Periodo afectado |
| `logusuarioid` | INT FK | Usuario que ejecuta |
| `logtipo` | VARCHAR(10) | INS/UPD/INA u otro |
| `logfechahora` | DATETIME | Fecha/hora |
| `logparamjson` | JSON | Parametros/evento |
| `logregbkpjson` | JSON | Registro antes del cambio si aplica |

## 14. Modificaciones a `usuarios`

Permisos funcionales a consolidar:

| Permiso conceptual | Uso |
|---|---|
| permite aprobar REQ | Lista de firmantes REQ y centros de costo |
| permite aprobar PreOC | Lista de firmantes PreOC |
| `reqautorizadorfuerapptocompra` | Usuario autorizador fuera de presupuesto para REQ |
| `reqautorizadorfuerapptocompraorden` | Orden relativo de autorizadores fuera de presupuesto; unico cuando aplica |
| comprador | Crear/editar PreOC si tiene acceso al formulario |
| permite anular PreOC | Anulacion especial de PreOC cuando el estado lo permite |
| editar precios | Editar precios en REQ |
| permite crear item | Crear item local urgente |
| permite editar item | Editar precio cero, uso funcional y activo/inactivo |
| `permitesynctrnerp` | Botones de sincronizacion de transacciones ERP |

## 15. Modificaciones a `invitems`

Campos/conceptos relevantes para REQ:

- tipo Material/Servicio,
- precio referencial,
- comprable,
- uso funcional,
- activo/inactivo,
- `iteminglocal`.

Reglas:

- Si `iteminglocal = 1`, el item fue ingresado localmente para resolver una urgencia.
- Si ERP luego trae el item, ERP manda y actualiza los campos que correspondan.
- La edicion local rapida aplica a cualquier item, pero solo para precio cero, uso funcional y activo/inactivo.
- Debe advertirse que el cambio tambien debe realizarse en ERP.

## 16. Pantallas y experiencia

### 16.0 Creacion y edicion de REQ

La pantalla puede operar como una vista unica con grupos:

1. Cabecera/Datos generales.
2. Detalle de items.
3. Lista de firmantes.
4. Resumen, advertencias y analisis de presupuesto.

Agregar item:

- Boton con signo `+`.
- Modal de busqueda de item.
- Busqueda por codigo/descripcion con coincidencia tipo `LIKE '%texto%'`.
- Se recomienda minimo de caracteres para busquedas amplias.
- Mostrar datos relacionados del item: codigo, descripcion, unidad, precio, uso funcional, subfamilia y datos utiles disponibles.
- El usuario informa cantidad.
- Botones Cancelar y Grabar.
- Al grabar se valida item activo, comprable, tipo compatible y precio mayor a cero.
- Al grabar se agrega a grilla, se calcula linea, se recalculan totales y analisis presupuestario.

Primer item:

- Al agregar el primer item, se bloquean centro de costo y tipo de REQ.
- Se genera lista de firmantes default.
- Se habilita gestion de firmantes manuales.

Eliminar item:

- Recalcula numeros de linea.
- Recalcula totales y analisis presupuestario.
- Si se eliminan todos los items estando en `BRR`, se desbloquean centro y tipo, y se limpian firmantes.
- Si habia firmantes manuales, debe pedir confirmacion antes de limpiar.

Agregar firmante manual:

- Boton con signo `+`.
- Modal de busqueda de usuarios.
- Filtrar usuarios activos con permiso de aprobacion de REQ.
- Al grabar se valida duplicidad.
- Si esta OK, se agrega a la grilla como manual, removible y reordenable.

Reordenar firmantes:

- La grilla debe tener botones Subir y Bajar.
- El primer registro no puede subir.
- El ultimo registro no puede bajar.
- Si hay un solo registro, ambos botones quedan bloqueados.
- Al mover se intercambia orden y se renumera sin huecos.
- Los default jefe/jefe tecnico no se remueven, pero si pueden reordenarse.
- Los fuera de presupuesto quedan fijos al final.

### 16.1 `reqcompra_pend_aprob`

Listado de pendientes de aprobacion.

- El usuario login se obtiene desde sesion/websession, no por URL.
- Acciones con iconos: Ver, Aprobar, Rechazar.
- Si el usuario es creador y aprobador pendiente, se muestran todos los botones aplicables.

### 16.2 `reqcompra_ver`

Pantalla de visualizacion/analisis.

- Si el usuario es creador, muestra Editar cuando el estado lo permite.
- Si el usuario es aprobador pendiente, muestra Aprobar/Rechazar.
- En movil, el detalle se muestra como tarjetas.
- Los botones de accion quedan fijos en la parte superior cuando exista un listado extenso.

### 16.3 Visualizacion de tracking

Debe mostrar:

- advertencia presupuestaria arriba si `reqadvertenciapptocompra = 1`,
- boton "Analisis de ppto de compra" disponible para todos los usuarios que puedan visualizar,
- items con falta de saldo resaltados en amarillo suave,
- fecha y cantidad del ultimo requerimiento del mismo centro-item, tanto en grilla como en tarjetas,
- prioridad alta con marca visual,
- si el item tiene PreOC asociada,
- compra parcial/total,
- item modificado,
- historial funcional,
- dias sin asociar a PreOC.

## 17. LOG tecnico

`reqcompraslog` conserva auditoria tecnica. Tipos vigentes minimos:

| Tipo | Evento |
|---|---|
| `INS` | Envio inicial del REQ |
| `UPD` | Modificacion confirmada |
| `ANL` | Anulacion |
| `APR` | Aprobacion |
| `RCH` | Rechazo |
| `EDT` | Paso a edicion |
| `CMB` | Cambio de item |
| `AJP` | Ajuste/anulacion de cantidad pendiente, si se define este codigo |

Los comentarios funcionales no reemplazan el LOG.

## 18. Emails

Queda preparado el requisito funcional de correos:

- aprobador con REQ pendiente,
- solicitante cuando REQ sea aprobado o rechazado,
- enfasis visual/textual cuando la prioridad sea Alta.

## 19. Fuera de alcance de esta estructura

- Crear tablas o SQL definitivos.
- Implementar pantallas.
- Integracion de recepcion ERP en tracking.
- Monto maximo por requerimiento.
- Descripcion de producto editada libremente fuera de reglas de item local.
