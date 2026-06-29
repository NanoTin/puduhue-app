# Modulo Compras - Plan Maestro

> Documento maestro de estado del modulo Compras.
>
> Este archivo no contiene SQL ejecutable. Centraliza decisiones vigentes, estado de incrementales, pendientes y secuencia recomendada para continuar sin mezclar DDL, SP, backend, frontend ni integracion ERP.

## 1. Fuentes normativas

- `docs/modulo_compras_corte_sql_previo.md`
- `docs/modulo_compras_incremental_07_diseno.md`
- `docs/modulo_compras_brechas_sql.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_req_contrato_sp.md`
- `docs/modulo_compras_req_contrato_be.md`
- `docs/modulo_compras_req_contrato_fe.md`
- `docs/modulo_compras_req_pendientes_contrato_sp.md`
- `docs/modulo_compras_req_pendientes_contrato_be.md`
- `docs/modulo_compras_req_pendientes_contrato_fe.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/modulo_compras_preoc_contrato_sp.md`
- `docs/modulo_compras_preoc_contrato_be.md`
- `docs/modulo_compras_preoc_contrato_fe.md`
- `docs/modulo_compras_presupuesto_definitivo.md`
- `docs/ADR/modulo-compras/ADR-001-modelo-presupuesto-compras.md`
- `docs/ADR/modulo-compras/ADR-002-compromiso-edicion-preoc.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `docs/02_ux.md`
- `docs/03_backend.md`
- `PUDUHUE.agent`
- `PUDUHUE_FRONT.agent`

## 2. Decisiones vigentes

### 2.1 Presupuesto

- REQ solo analiza presupuesto de forma informativa.
- REQ no bloquea por saldo y no genera movimientos presupuestarios.
- PreOC es el unico punto del flujo que compromete presupuesto.
- La validacion de PreOC es bloqueante.
- La resolucion presupuestaria se hace por fecha PreOC, subfamilia de item y centro de costo.
- `pptocompratransacciones` es el libro oficial de movimientos.
- Reservas y consumos se guardan en negativo.
- Reversas se guardan en positivo.
- La reserva PreOC se genera al pasar de `BRR` a `PND`, no al guardar borrador.
- Una linea provisional de PreOC solo puede eliminarse sin reversa si la PreOC sigue en curso y no tiene aprobaciones.
- Si una PreOC vuelve de `PND` a `BRR` antes de aprobaciones, se borran las reservas provisionales asociadas; no se genera reversa.
- Las transacciones presupuestarias de PreOC se registran agrupadas por presupuesto afectado, usando `preocpptoresumen` como referencia operativa.
- Para movimientos PreOC en `pptocompratransacciones`: `pptocompramoduloorigen = 'PREOC'`, `pptocompranrodocumentoorigen = preocid` y `pptocomprareflinea = 'PREOCPPTORESUMEN:<preocpptoresumenid>'`.
- Cada envio de PreOC a aprobacion debe abrir un ciclo presupuestario nuevo identificado en `pptocompregruppomovimiento`, por ejemplo `PREOC:<preocid>:CICLO:<n>`.
- La idempotencia de movimientos PreOC se valida por tipo de transaccion, modulo origen, documento origen, presupuesto, referencia de resumen y ciclo; si existe movimiento con el mismo monto se considera OK idempotente, si existe con monto distinto es conflicto.
- El recalculo de `pptocompra` debe tomar el libro `pptocompratransacciones` como fuente oficial; `pptocompramensual` queda como fuente de carga inicial/base mensual mientras no se defina otra estructura.
- Los SP del incremental 11 no deben contener transacciones internas; `SELECT ... FOR UPDATE` se usa bajo la transaccion abierta por PHP.
- Cuando existe al menos una aprobacion, la PreOC deja de ser editable.

### 2.2 REQ

- Estados documentales vigentes: `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL`.
- No se usa `CSO`; cambios solicitados se tratan como `RCH` con comentario obligatorio.
- No se usa `VNC` como estado principal; el vinculo con PreOC vive en `reqcompraestadopreoc`.
- REQ rechazado es corregible y reenviable.
- Un REQ no se puede editar si ya tiene al menos una aprobacion o si esta aprobado.
- La fecha funcional REQ la define sistema/BD y se actualiza en cada edicion permitida.
- Firmantes default REQ: jefe de centro y jefe tecnico del centro.
- Los firmantes fuera de presupuesto se agregan al final, no se remueven ni reordenan.
- La inactividad aplica sobre usuarios aprobadores, no sobre funcionarios.
- El centro operativo del REQ sale de `usuarioscentroscosto`.
- Funcionario es opcional.
- Items con precio cero no se pueden agregar al REQ.
- Al aprobar completamente se crean lineas en `reqaprobados`.
- `EDT` solo nace desde `PND` cuando el usuario creador entra a editar un REQ pendiente.
- Para analisis presupuestario informativo, "otros REQ pendientes/en edicion" corresponde a REQ vigentes en estado `PND` o `EDT`, excluyendo el REQ actual.
- Para REQ en `EDT`, la salida normal debe ser explicita: guardar/reenviar o cancelar edicion.
- Si el usuario abandona navegador, vuelve atras o pierde conexion mientras esta en `EDT`, el REQ queda en `EDT`; el creador puede retomar la edicion al volver. La liberacion de `EDT` es accion controlada/manual, no automatica por evento del navegador.

### 2.3 Pendientes de compra

- `reqaprobados` mantiene lineas aprobadas listas para compra.
- `reqaprobados` no apunta a una PreOC unica.
- El vinculo de compra o anulacion vive en `reqaprobadoshistorial`.
- Se permiten compras parciales.
- La consistencia operativa es:
  `reqaprobadocantidadreq = reqaprobadocantidadpendiente + reqaprobadocantidadcomprada + reqaprobadocantidadanulada`.
- La anulacion opera solo sobre cantidad pendiente y requiere motivo obligatorio.
- El cambio de item lo ejecuta el comprador desde pendientes de compra, no dentro de PreOC.
- Contratos SP/BE/FE de pendientes de compra quedan documentados como prerequisito funcional de PreOC:
  - `docs/modulo_compras_req_pendientes_contrato_sp.md`;
  - `docs/modulo_compras_req_pendientes_contrato_be.md`;
  - `docs/modulo_compras_req_pendientes_contrato_fe.md`.

### 2.4 PreOC

- Estados documentales vigentes: `BRR`, `PND`, `APR`, `RCH`, `ANL`.
- Estados ERP separados: sin estado, `SNC`, `ERR`.
- Si falla ERP, PreOC queda documentalmente `APR` y ERP `ERR`.
- Si sincroniza correctamente, guarda numero ERP, fecha/hora y respuesta tecnica.
- Si una PreOC sincronizada se anula localmente, queda documentalmente `ANL` y ERP `SNC`.
- Creador de PreOC debe ser usuario comprador con `usuariocomprador = 1`.
- Origen de lineas: `reqaprobados` pendientes o parciales.
- No se guarda un unico `pptocompraid` en cabecera PreOC.
- Una PreOC puede afectar varios presupuestos por sus lineas.
- Firmantes default PreOC salen de los presupuestos afectados: responsable, administrador y colaborador.
- Aprobadores por monto y manuales se agregan despues de defaults.
- El workflow de compra es fijo, no maestro.
- Moneda operativa vigente: CLP / `PES`.
- Tipo ERP: Material `OC`, Servicio `OCSS`.
- PreOC debe tener adjuntos/archivos; se requiere al menos un adjunto obligatorio antes de enviar a aprobacion.
- La obligatoriedad de adjuntos PreOC se valida al pasar `BRR` a `PND`; no bloquea guardar borrador.
- Adjuntos PreOC debe soportar inicialmente PDF, imagenes, MSG y KMZ.
- El tipo de adjunto no debe ser digitado por el usuario; debe resolverse por extension/MIME desde una configuracion reutilizable de tipos permitidos por modulo.
- Adjuntos PreOC se almacenan como archivo fisico y metadata en BD; no se guardan como BLOB.
- Si se elimina un adjunto permitido por estado, se elimina fisicamente el archivo y la fila de metadata; el LOG de PreOC debe registrar que se elimino el archivo.
- La descripcion del adjunto debe ser obligatoria, corta, y sirve como base para generar el nombre fisico del archivo.
- El nombre fisico de adjuntos debe normalizarse a ASCII: sin acentos, sin `ñ`, sin espacios, sin caracteres especiales peligrosos; los espacios se reemplazan por `_` y se agrega sufijo unico para evitar duplicados.
- El nombre/descripcion original se conserva como metadata visible.
- Contratos SP/BE/FE PreOC quedan documentados para el corte funcional local:
  - `docs/modulo_compras_preoc_contrato_sp.md`;
  - `docs/modulo_compras_preoc_contrato_be.md`;
  - `docs/modulo_compras_preoc_contrato_fe.md`.
- Codigo visible PreOC cerrado como `POC-00000001`: prefijo `POC-` + `LPAD(preocid, 8, '0')`, global, no editable, no reciclable.
- Integracion ERP real y definicion final de impuestos/conceptos Finnegans quedan fuera del corte funcional local.

#### 2.4.1 Contrato preliminar adjuntos PreOC

Tabla propuesta: `preocadjuntos`.

Columnas acordadas:

- `preocadjuntoid`: PK autoincremental.
- `preocid`: FK a cabecera PreOC.
- `preocadjuntolinea`: orden visual/secuencial dentro de la PreOC.
- `preocadjuntotipoid`: FK/codigo autocalculado por la logica desde el archivo seleccionado; debe existir en el maestro de extensiones/tipos permitidos y no lo informa el usuario.
- `preocadjuntodescripcion`: descripcion obligatoria, corta, ingresada por usuario.
- `preocadjuntonombreoriginal`: nombre original recibido desde el navegador.
- `preocadjuntonombrearchivo`: nombre fisico generado desde descripcion normalizada ASCII + sufijo unico + extension.
- `preocadjuntoextension`: extension normalizada en minusculas.
- `preocadjuntomime`: MIME detectado cuando `finfo` este disponible.
- `preocadjuntotamano`: tamano en bytes.
- `preocadjuntoruta`: ruta relativa dentro del almacenamiento de uploads.
- `auditcreacion*`: auditoria estandar.

Columnas descartadas:

- `preocadjuntoobligatorio`: no aplica; la obligatoriedad es regla al enviar `BRR -> PND`.
- `preocadjuntovig`: no aplica; el borrado permitido elimina archivo fisico y fila de metadata, dejando evidencia en LOG PreOC.

Reglas propuestas:

- Minimo 1 adjunto al enviar `BRR -> PND`.
- No exigir adjunto para guardar borrador.
- Tamaño maximo recomendado: 10 MB por archivo y 50 MB total por PreOC.
- Tipos/extensiones/MIME permitidos deben salir de un maestro reutilizable por modulo.
- Extensiones iniciales para PreOC: PDF, imagenes, MSG y KMZ.

#### 2.4.2 Contrato preliminar maestro de extensiones

Tablas propuestas sin autoincremental:

`archivoextensionestipos`

- `archivoextensiontipoid`: codigo unico, por ejemplo `PDF`, `IMG_JPG`, `MSG`, `KMZ`.
- `archivoextension`: extension normalizada en minusculas, sin punto.
- `archivoextensionmime`: MIME permitido principal.
- `archivoextensiondsc`: descripcion visible/tecnica.
- `archivoextensionactivo`: estado del tipo.
- `auditcreacion*` y `auditedicion*`: auditoria estandar.

`archivoextensionestiposmodulos`

- `archivoextensiontipoid`: FK/codigo del tipo de extension.
- `archivomodulocod`: codigo de modulo, inicialmente `REQ`, `PREOC`, `RET_LECHE`.
- `archivoextensionmoduloactivo`: estado de permiso por modulo.
- `auditcreacion*` y `auditedicion*`: auditoria estandar.

Reglas:

- La PK de `archivoextensionestipos` es `archivoextensiontipoid`.
- La PK de `archivoextensionestiposmodulos` es compuesta: `archivoextensiontipoid + archivomodulocod`.
- Un modulo solo permite adjuntos cuyo tipo este activo en ambas tablas.
- Para PreOC, `preocadjuntotipoid` se autocalcula validando extension/MIME contra este maestro.
- `RET_LECHE` debe quedar preparado para imagenes, reemplazando en futuro la validacion local fija de `retiroleche`.

### 2.5 Bases compartidas

- `usuarios` debe separar permisos de aprobacion REQ, aprobacion PreOC, comprador, anulacion PreOC, edicion de precios, creacion/edicion de item, sincronizacion ERP y autorizador fuera de presupuesto.
- `usuarioscentroscosto` define centros disponibles para crear REQ y centro default.
- Solo puede existir un centro default activo por usuario; en el primer corte se valida en SP/BE.
- Si una asociacion usuario-centro existe inactiva, el mantenedor debe reactivarla en vez de crear una fila duplicada.
- `funcionarios` usa RUT como PK funcional y no equivale a usuario del sistema.
- `aprobadoresperiodoinactividad` aplica a REQ y PreOC.
- `invitemstockeable = 1` equivale a Material y `0` a Servicio.
- Para REQ/PreOC el item debe cumplir `invitemcompra = 1`.
- `iteminglocal` marca items ingresados localmente.
- Proveedores y condiciones de pago son maestros espejo ERP, solo consulta/exportacion, sincronizados por list + detalle.

## 3. Estado actual de SQL

| Incremental | Archivo | Estado |
|---|---|---|
| 07 | `database/alter_table/07_modulo_compras_bases_compartidas.sql` | Creado. Bases compartidas. Validado manualmente en BD local: OK. |
| 08 | `database/alter_table/08_modulo_compras_req.sql` | Creado. DDL REQ base. Validado manualmente en BD local: OK. |
| 09 | `database/alter_table/09_modulo_compras_req_pendientes.sql` | Creado. Pendientes de compra (`reqaprobados*`). Validado manualmente en BD local: OK. |
| 10 | `database/alter_table/10_modulo_compras_preoc.sql` | Creado. DDL PreOC. Validado manualmente en BD local: OK. |
| 11 | `database/alter_table/11_modulo_compras_presupuesto_sp.sql` | SP presupuestarios implementados segun contrato tecnico cerrado. Validado manualmente en BD local: OK. |

### 3.1 Auditoria aplicada

- `reqaprobados*` queda solo en incremental 09.
- Estados REQ corregidos a codigos `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL`.
- Estados REQ y PreOC ajustados para usar codigo como PK.
- PreOC permite compras parciales; no debe existir unicidad por `reqaprobadoid` en `preocdetallereqitems`.
- FK finales de PreOC hacia `reqaprobadoshistorial` fueron ajustadas para MariaDB sin `ADD CONSTRAINT IF NOT EXISTS`.

### 3.2 Estado de validacion manual en BD local

- 07: OK.
- 08: OK.
- 09: OK.
- 10: OK.
- 11: OK.

## 4. Secuencia recomendada

### 4.1 Base SQL

1. Ejecutar/revalidar incremental 07.
2. Ejecutar/revalidar incremental 08.
3. Ejecutar/revalidar incremental 09.
4. Ejecutar/revalidar incremental 10.
5. Validar incremental 11 segun contrato tecnico cerrado en la seccion 4.2.1.

### 4.2 Backend y SP

1. Actualizar SP de usuarios para nuevas columnas de permisos y logs.
2. Actualizar SP de presupuesto de compra para responsable, administrador y colaborador.
3. Actualizar SP de items para `iteminglocal` y permisos de crear/editar item.
4. Crear contratos/SP de analisis REQ:
   - resolver presupuesto informativo,
   - generar snapshot,
   - detectar deficit,
   - resolver firmantes fuera de presupuesto.
5. Implementar segun contratos SP/BE/FE de pendientes de compra:
   - crear `reqaprobados` al aprobar REQ,
   - cambiar item con motivo,
   - anular saldo pendiente,
   - consultar pendientes/parciales.
6. Implementar segun contratos SP/BE/FE de PreOC:
   - crear borrador desde pendientes,
   - resolver presupuesto por linea,
   - calcular totales/impuestos,
   - generar firmantes,
   - resolver siguiente aprobador.
7. Crear contratos/SP presupuestarios del incremental 11:
   - reserva `POC_RESERVA`,
   - confirmacion `POC_CONFIRMACION`,
   - reversa `POC_REVERSA`,
   - borrado de reserva provisional al volver de `PND` a `BRR` sin aprobaciones,
   - recalculo de saldos.
8. Preparar integracion ERP PreOC aprobada:
   - POST ERP,
   - manejo de `SNC`/`ERR`,
   - reintento,
   - error visible.

### 4.2.1 Contrato tecnico SP incremental 11

Todos los SP deben usar la firma estandar del proyecto:

`p_in_json`, `p_in_usuarioid`, `p_in_dispositivo`, `p_in_ip`, `p_out_json`.

Los SP de mantenimiento no deben contener `BEGIN`, `COMMIT` ni `ROLLBACK`; la transaccion la controla PHP con `callSpMaint()`.

| SP | Objetivo | Parametros JSON | Salida | Tablas afectadas |
|---|---|---|---|---|
| `sp_compras_ppto_resolver` | Resolver presupuesto por fecha, subfamilia y centro. | `fecha`, `subfamiliaid`, `centrocostoid` | `SELECT` con `temporadaid`, `pptocompraid`, saldo y firmantes; `p_out_json` solo meta/error. | Solo lectura: `temporadas`, `pptocompra`, `usuarios` si resuelve firmantes. |
| `sp_compras_req_ppto_analizar` | Analizar presupuesto informativo de REQ sin bloquear ni mover presupuesto. | `reqcompraid` | `SELECT` por grupo presupuestario; `p_out_json` solo meta/error. | Solo lectura: `reqcompras`, `reqcomprasdetalle`, `reqaprobados`, `pptocompra`. |
| `sp_compras_req_ppto_snapshot_actualizar` | Recalcular y guardar snapshot REQ agrupado por subfamilia y centro. | `reqcompraid` | JSON con `status`, `advertencia`, `fuerapptocompra`, `grupos`. | Escribe `reqcompraspptosnapshot`; actualiza flags en `reqcompras` y `reqcomprasdetalle`. |
| `sp_compras_preoc_ppto_reservar` | Validar saldo bloqueante y crear `POC_RESERVA` negativa al pasar `BRR -> PND`. | `preocid` | JSON con reservas, saldos antes/despues y error si falta saldo. | Inserta `pptocompratransacciones`; actualiza `preocpptoresumen`; recalcula `pptocompra`. |
| `sp_compras_preoc_ppto_confirmar` | Confirmar reserva al aprobar PreOC. | `preocid` | JSON con confirmaciones. | Inserta `POC_CONFIRMACION`; recalcula `pptocompra`; actualiza `preocpptoresumen`. |
| `sp_compras_preoc_ppto_revertir` | Revertir por rechazo/anulacion cuando corresponda. | `preocid`, `motivo`, `evento` (`RCH`/`ANL`) | JSON con reversas. | Inserta `POC_REVERSA` positiva; recalcula `pptocompra`; actualiza `preocpptoresumen`. |
| `sp_compras_preoc_ppto_borrar_reserva_provisional` | Borrar reservas provisionales si vuelve de `PND` a `BRR` sin aprobaciones. | `preocid`, `motivo` opcional | JSON con reservas borradas. | Borra movimientos `POC_RESERVA` provisionales del ciclo vigente; actualiza `preocpptoresumen`; recalcula `pptocompra`; registra LOG PreOC desde el flujo que invoca. |
| `sp_compras_ppto_recalcular_totales` | Recalcular cabecera `pptocompra` desde el libro oficial de movimientos. | `pptocompraid` o `null` para varios | JSON con presupuestos recalculados. | Wrapper con firma estandar; actualiza `pptocompra`; suma impactos netos desde `pptocompratransacciones` y usa `pptocompramensual` como base inicial/mensual. |

Reglas transversales:

- REQ no genera movimientos presupuestarios.
- PreOC es el unico flujo que compromete presupuesto.
- Las reservas/consumos se registran negativos; las reversas se registran positivas.
- Los movimientos PreOC se registran agrupados por `preocpptoresumenid`.
- `pptocomprareflinea = 'PREOCPPTORESUMEN:<preocpptoresumenid>'`.
- Cada envio a aprobacion abre un ciclo presupuestario nuevo en `pptocompregruppomovimiento`, por ejemplo `PREOC:<preocid>:CICLO:<n>`.
- La idempotencia valida tipo, modulo, documento, presupuesto, referencia y ciclo.
- La validacion de saldo PreOC usa `SELECT ... FOR UPDATE` dentro de la transaccion abierta por PHP.

### 4.3 Frontend

1. Maestros/base compartida:
   - usuarios permisos compras,
   - usuarios-centros,
   - funcionarios,
   - inactividad aprobadores,
   - proveedores y condiciones de pago en consulta/exportacion.
2. REQ:
   - crear/editar/ver,
   - pendientes de aprobacion,
   - firmantes,
   - analisis de ppto de compra,
   - tracking.
3. Pendientes de compra:
   - listado de `reqaprobados`,
   - cambios de item,
   - anulacion de saldo,
   - seleccion para PreOC.
4. PreOC:
   - listado,
   - seleccion de requerimientos aprobados,
   - crear/editar/ver,
   - precios por item agrupado,
   - firmantes,
   - aprobacion/rechazo/anulacion.
5. Sincronizacion ERP/tracking:
   - estados ERP,
   - reintentos,
   - errores visibles,
   - numero y fecha/hora ERP.

### 4.4 Estrategia de implementacion por modulo inicial

La implementacion funcional debe avanzar por flujo completo de negocio, no por capas aisladas. El orden acordado es:

1. REQ completo.
2. REQ aprobados / pendientes de compra para PreOC.
3. PreOC completo.
4. Sincronizacion ERP PreOC aprobada.

#### 4.4.1 Primer bloque: REQ completo

Objetivo: dejar operativo el ciclo REQ de punta a punta, incluyendo tablas ya creadas, SP, backend, frontend, rutas y procesos internos.

Alcance REQ:

- Tablas: la base del incremental 08 queda cerrada para este corte; solo se esperan mejoras posteriores si lo requiere el cliente.
- SP:
  - listar REQ;
  - crear REQ, incluyendo snapshot/analisis presupuestario informativo;
  - editar REQ, incluyendo recalculo de snapshot/analisis presupuestario;
  - consultar REQ por ID;
  - aprobar REQ;
  - rechazar REQ;
  - registrar comentarios/log funcional cuando corresponda;
  - consultas relacionadas a presupuesto si el flujo o pantalla lo requiere.
- Backend:
  - Models/Services que consumen SP mediante `callSpMaint()` o mecanismo estandar vigente;
  - control transaccional en PHP para cambios de estado, detalle, firmantes, snapshot y log;
  - control de permisos por usuario para crear, editar, aprobar, rechazar y visualizar;
  - no llamar SP desde vistas.
- Frontend:
  - pantalla principal de listado REQ;
  - pantalla crear REQ;
  - pantalla editar REQ;
  - pantalla ver REQ;
  - pantalla/listado de REQ por aprobar, reutilizando la vista de ver para aprobar/rechazar;
  - visualizacion de advertencias presupuestarias sin bloquear el flujo normal de REQ.
- Rutas:
  - actualizar rutas necesarias para listado, crear, editar, ver y aprobaciones.

Reglas REQ a preservar:

- REQ no mueve presupuesto.
- El analisis presupuestario de REQ es informativo.
- `EDT` solo nace desde `PND`.
- La salida normal de `EDT` debe ser guardar/reenviar o cancelar edicion.
- Si hay abandono de navegador o perdida de conexion en `EDT`, el backend no libera automaticamente; debe permitir retomar la edicion o ejecutar una liberacion controlada/manual.

#### 4.4.2 Segundo bloque: REQ aprobados / pendientes de compra

Objetivo: implementar el puente entre REQ aprobado y seleccion para PreOC.

Alcance:

- Crear `reqaprobados` al aprobar REQ.
- Consultar pendientes por comprador/filtros necesarios.
- Permitir cambios de item con motivo si corresponde al contrato vigente.
- Permitir anulacion de saldo pendiente cuando corresponda.
- Mantener historial de movimientos de pendiente.
- Preparar seleccion de pendientes para crear PreOC.

#### 4.4.3 Tercer bloque: PreOC completo

Objetivo: implementar PreOC despues de tener REQ y pendientes operativos.

Alcance:

- Crear/editar/ver PreOC desde pendientes aprobados.
- Resolver totales, precios, impuestos y agrupaciones.
- Resolver firmantes.
- Exigir adjuntos al enviar a aprobacion, cuando exista DDL/servicio de adjuntos.
- Integrar SP presupuestarios del incremental 11:
  - reservar al pasar `BRR -> PND`;
  - confirmar al aprobar;
  - revertir por rechazo/anulacion;
  - borrar reserva provisional si vuelve `PND -> BRR` sin aprobaciones.
- Preparar posterior sincronizacion ERP.

## 5. Reglas transversales de implementacion

### 5.1 Backend/BD

- Usar `src/Config/Database.php`.
- Consumir SP desde Models/Services; no llamar SP desde vistas.
- Los SP mantienen firma estandar:
  `p_in_json`, `p_in_usuarioid`, `p_in_dispositivo`, `p_in_ip`, `p_out_json`.
- Los SP no deben contener `BEGIN`, `COMMIT` ni `ROLLBACK`; la transaccion la controla PHP con `callSpMaint()`.
- Catalogos de formularios, filtros y modales de Compras se centralizan en `ComprasCatalogosService` con metodos `*FormSelect` / `*FormGrid`; no crear SP auxiliares por cada combo o modal salvo regla transaccional critica.
- Vistas REQ aprobadas para el primer corte: `compras_req_listar.php`, `compras_req_crear.php`, `compras_req_editar.php`, `compras_req_ver.php`, `compras_req_pendientes_aprobacion.php`.
- Post-edicion REQ exitoso redirige a `compras-req/ver&id=X`; filtros en modales de items/aprobadores son client-side en el primer corte.
- Codigo visible REQ cerrado como `REQ-00000001`: prefijo `REQ-` + `LPAD(reqcompraid, 8, '0')`, global, no editable, no reciclable.
- Filtro de listados REQ cerrado como `filtroBusqueda`, aplicado con `LIKE` sobre cadena logica de campos visibles.
- Contrato FE REQ cerrado para estructura visual, acciones, modales, presupuesto informativo, toasts y validaciones cliente del primer corte.
- Mantener auditoria con `auditcreacion*` y `auditedicion*`.
- Mantener LOG tecnico separado de comentarios funcionales.
- No ejecutar SQL contra BD real sin autorizacion explicita.
- No ejecutar llamadas reales a ERP/Finnegans desde terminal sin autorizacion explicita.

### 5.2 Frontend

- Mantener Bootstrap 5 y Bootstrap Icons.
- Usar clases personalizadas con prefijo `pdh-` y nomenclatura BEM.
- No usar CSS inline salvo estilos dinamicos generados por JS.
- Usar toasts como mecanismo de feedback.
- Evitar `alert()` para operaciones CRUD.
- `confirm()` nativo solo se permite para acciones destructivas.
- Tablas con `.table-responsive`.
- Formularios con grid responsivo Bootstrap.
- Vistas no deben implementar autenticacion ni ejecutar SP directamente.
- El usuario login se obtiene desde sesion/controlador, no por URL.

## 6. Pendientes criticos

- Implementar primer bloque funcional REQ completo: SP, BE, FE, rutas, aprobaciones, log y analisis presupuestario informativo.
- Definir contrato tecnico/DDL de adjuntos PreOC y maestro reutilizable de tipos/extensiones/MIME permitidos.
- Confirmar estrategia de recreacion/migracion si ya existen tablas de 08/09 en BD local.
- Actualizar logs y SP existentes para nuevas columnas de usuarios, presupuesto e items.
- Implementar segundo bloque REQ aprobados / pendientes de compra para PreOC.
- Implementar tercer bloque PreOC completo e integrar SP/servicios de reserva, confirmacion, reversa y recalculo presupuestario.
- Confirmar campos obligatorios definitivos del POST ERP de PreOC.
- Confirmar conceptos/impuestos activos requeridos por Finnegans.
- Confirmar comportamiento esperado de `NumeroComprobante`.
- Preparar prompts cerrados para Spark cuando corresponda.

## 7. Riesgos y ambiguedades a cerrar

- Naming de autorizador fuera de presupuesto resuelto: el nombre tecnico consolidado en los incrementales, ADR-003 corregido y estructura vigente es `usuarioreqautorizadorfuerapptocompra`.
- Si 08/09 ya fueron ejecutados antes de las correcciones, puede requerirse recreacion controlada o migracion especifica antes de revalidar.
- Las FK finales de 10 dependen de que 09 exista y de que MariaDB acepte la forma exacta del `ALTER TABLE` usado.
- `preocitemsdimensiones` esta definido por req-item origen; queda pendiente confirmar campos obligatorios finales con Finnegans.
- `imptoid`/conceptos de impuestos aun dependen de confirmacion tecnica de Finnegans.
- Responsable y administrador de `pptocompra` son obligatorios funcionalmente, pero entran nullable en el primer DDL para permitir backfill.
- Falta llevar a DDL/seed el maestro de extensiones/MIME permitidos para adjuntos por modulo.

## 8. Criterio para usar Spark

Spark no define arquitectura, reglas de negocio, tablas, columnas, stored procedures, endpoints ni flujos.

Solo debe usarse con prompts cerrados que indiquen:

- objetivo exacto,
- archivos permitidos,
- archivos prohibidos,
- fuentes a leer,
- resultado esperado,
- condicion de bloqueo si necesita inferir algo no indicado.

## 9. Checklist operativo e historico

Este checklist es la bitacora accionable del plan maestro. No mover filas cuando se cierren; actualizar `Estado`, completar `Evidencia / nota` y, si aplica, agregar fecha, archivo, commit o referencia de validacion.

Estados sugeridos:

- `Cerrado`: definido, creado o corregido segun alcance indicado.
- `Validado`: ejecutado/revalidado manualmente en BD local o por validacion tecnica indicada.
- `Pendiente`: falta ejecutar, definir o implementar.
- `Bloqueado`: requiere decision externa, contrato tecnico, confirmacion cliente/Finnegans o autorizacion.

| ID | Bloque | Item | Estado | Evidencia / nota |
|---|---|---|---|---|
| CMP-001 | Planificacion | Crear corte SQL previo para separar bases compartidas, REQ, pendientes, PreOC y SP presupuestarios | Cerrado | `docs/modulo_compras_corte_sql_previo.md` |
| CMP-002 | Planificacion | Definir diseno detallado del incremental 07 | Cerrado | `docs/modulo_compras_incremental_07_diseno.md` |
| CMP-003 | Planificacion | Documentar brechas SQL contra definiciones funcionales | Cerrado | `docs/modulo_compras_brechas_sql.md` |
| CMP-004 | Planificacion | Consolidar estructura vigente de REQ | Cerrado | `docs/modulo_compras_req_estructura.md` |
| CMP-005 | Planificacion | Consolidar estructura vigente de PreOC | Cerrado | `docs/modulo_compras_preoc_estructura.md` |
| CMP-006 | Planificacion | Consolidar modelo definitivo de presupuesto | Cerrado | `docs/modulo_compras_presupuesto_definitivo.md` y ADR-001 |
| CMP-007 | Planificacion | Crear plan maestro del modulo Compras | Cerrado | `docs/modulo_compras_plan_maestro.md` |
| CMP-008 | ADR | Cerrar decision de modelo de presupuesto de compras | Cerrado | `docs/ADR/modulo-compras/ADR-001-modelo-presupuesto-compras.md` |
| CMP-009 | ADR | Cerrar decision de compromiso y edicion PreOC | Cerrado | `docs/ADR/modulo-compras/ADR-002-compromiso-edicion-preoc.md` |
| CMP-010 | ADR | Cerrar decision de REQ y pendientes de compra | Cerrado | `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md` |
| CMP-011 | SQL 07 | Crear incremental 07 de bases compartidas | Cerrado | `database/alter_table/07_modulo_compras_bases_compartidas.sql` |
| CMP-012 | SQL 07 | Validar incremental 07 en MariaDB local | Validado | Reportado como OK en estado actual |
| CMP-013 | SQL 08 | Crear incremental 08 de DDL REQ base | Cerrado | `database/alter_table/08_modulo_compras_req.sql` |
| CMP-014 | SQL 08 | Mover `reqaprobados*` fuera del incremental 08 | Cerrado | Pendientes de compra quedan en incremental 09 |
| CMP-015 | SQL 08 | Corregir estados REQ a `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL` | Cerrado | Seeds y FK usan estado documental vigente |
| CMP-016 | SQL 08 | Ajustar maestros de estado REQ para usar codigo como PK | Cerrado | Validado posteriormente en MariaDB local |
| CMP-017 | SQL 08 | Revalidar incremental 08 en MariaDB local | Validado | Reportado como OK en validacion 07-11 |
| CMP-018 | SQL 09 | Crear incremental 09 de pendientes de compra | Cerrado | `database/alter_table/09_modulo_compras_req_pendientes.sql` |
| CMP-019 | SQL 09 | Mantener `reqaprobados`, historial y cambios de item solo en incremental 09 | Cerrado | Evita duplicidad con 08 |
| CMP-020 | SQL 09 | Revalidar incremental 09 en MariaDB local | Validado | Reportado como OK en validacion 07-11 |
| CMP-021 | SQL 10 | Crear incremental 10 de DDL PreOC | Cerrado | `database/alter_table/10_modulo_compras_preoc.sql` |
| CMP-022 | SQL 10 | Ajustar PreOC para permitir compras parciales sin unicidad por `reqaprobadoid` | Cerrado | Modelo vigente: vinculo por historial |
| CMP-023 | SQL 10 | Ajustar estados PreOC para usar codigo como PK | Cerrado | Maestro `preocestados` por codigo |
| CMP-024 | SQL 10 | Ajustar FK finales PreOC para MariaDB sin `ADD CONSTRAINT IF NOT EXISTS` | Cerrado | Validado posteriormente en MariaDB local |
| CMP-025 | SQL 10 | Revalidar incremental 10 en MariaDB local | Validado | Reportado como OK en validacion 07-11 |
| CMP-026 | SQL 11 | Crear incremental 11 | Validado | `database/alter_table/11_modulo_compras_presupuesto_sp.sql`; contrato tecnico cerrado, implementado y validado manualmente en BD local |
| CMP-027 | SQL 11 | Definir contrato tecnico de SP presupuestarios | Cerrado | Contrato tecnico cerrado en seccion 4.2.1 y sincronizado con `database/alter_table/11_modulo_compras_presupuesto_sp.sql` |
| CMP-028 | SQL 11 | Implementar reserva `POC_RESERVA` | Validado | Implementado en `sp_compras_preoc_ppto_reservar`; validado manualmente en BD local |
| CMP-029 | SQL 11 | Implementar confirmacion `POC_CONFIRMACION` | Validado | Implementado en `sp_compras_preoc_ppto_confirmar`; validado manualmente en BD local |
| CMP-030 | SQL 11 | Implementar reversa `POC_REVERSA` | Validado | Implementado en `sp_compras_preoc_ppto_revertir`; validado manualmente en BD local |
| CMP-031 | SQL 11 | Implementar borrado de reserva provisional al volver `PND` a `BRR` sin aprobaciones | Validado | Implementado en `sp_compras_preoc_ppto_borrar_reserva_provisional`; decision vigente: borrar reservas, sin reversa; validado manualmente en BD local |
| CMP-032 | SQL 11 | Implementar recalculo operacional de saldos `pptocompra` | Validado | Implementado en `sp_compras_ppto_recalcular_totales`; criterio alineado con `sp_pptocompra_recalcular_totales`; validado manualmente en BD local |
| CMP-033 | Migracion | Confirmar estrategia si tablas 08/09 ya existen en BD local | Pendiente | Puede requerir recreacion controlada o migracion |
| CMP-034 | Backend/SP | Actualizar SP de usuarios por permisos de Compras | Pendiente | Incluye logs y columnas nuevas |
| CMP-035 | Backend/SP | Actualizar SP de presupuesto de compra por firmantes default PreOC | Pendiente | Responsable, administrador y colaborador |
| CMP-036 | Backend/SP | Actualizar SP de items por `iteminglocal` y permisos | Pendiente | Crear/editar item, precio cero, uso funcional, activo |
| CMP-037 | Backend/SP | Crear contratos/SP de analisis REQ | Pendiente | Presupuesto informativo, snapshot, deficit, firmantes fuera de presupuesto |
| CMP-038 | Backend/SP | Crear contratos/SP de pendientes de compra | Pendiente | Crear aprobados, cambio item, anulacion saldo, consultas |
| CMP-039 | Backend/SP | Crear contratos/SP de flujo PreOC | Pendiente | Borrador, resolver presupuesto, totales, firmantes, aprobaciones |
| CMP-040 | Frontend | Implementar maestros/base compartida del modulo | Pendiente | Usuarios permisos, usuarios-centros, funcionarios, inactividad, proveedores, condiciones |
| CMP-041 | Frontend | Implementar flujo REQ | Pendiente | Crear/editar/ver, aprobaciones, firmantes, analisis presupuesto, tracking |
| CMP-042 | Frontend | Implementar pendientes de compra | Pendiente | Listado, cambio item, anulacion saldo, seleccion PreOC |
| CMP-043 | Frontend | Implementar flujo PreOC | Pendiente | Listado, crear/editar/ver, precios, firmantes, aprobacion/rechazo/anulacion |
| CMP-044 | ERP | Confirmar contrato POST ERP PreOC | Bloqueado | Requiere confirmar campos obligatorios, impuestos/conceptos y `NumeroComprobante` |
| CMP-045 | ERP | Implementar sincronizacion ERP/tracking PreOC | Pendiente | Depende de CMP-044 y flujo PreOC |
| CMP-046 | Spark | Preparar prompts cerrados para tareas delegables | Pendiente | Solo despues de cerrar objetivo, archivos permitidos/prohibidos y fuentes |
| CMP-047 | SQL/PreOC | Definir DDL y contrato de adjuntos PreOC obligatorios | Pendiente | Contrato preliminar documentado; falta llevar a DDL y seed del maestro extension/MIME por modulo |
| CMP-048 | UX/REQ | Definir recuperacion de REQ en estado `EDT` por abandono de navegador | Cerrado | `EDT` solo nace desde `PND`; contar `PND` y `EDT` en analisis informativo; salida normal con guardar/reenviar o cancelar; si abandona, queda `EDT` hasta que el creador retome o exista liberacion controlada/manual |
| CMP-049 | Planificacion | Ordenar implementacion por flujo vertical REQ -> aprobados pendientes -> PreOC | Cerrado | Estrategia documentada en seccion 4.4 |
| CMP-050 | REQ | Implementar SP funcionales de REQ completo | Implementado | `database/sp/02_sp_compras_req.sql`; pendiente validacion MariaDB local antes de ejecutar en BD real |
| CMP-051 | REQ | Implementar BE de REQ completo | Implementado | `ComprasReqService`, `ComprasCatalogosService`, `ComprasReqController`; pendiente prueba funcional integrada |
| CMP-052 | REQ | Implementar FE de REQ completo | Implementado | Vistas REQ creadas segun contrato; pendiente revision visual/navegacion en navegador local |
| CMP-053 | REQ | Actualizar rutas REQ | Implementado | `src/Routes/web.php`, `apps/web-php/menu.json` y estilos auxiliares de REQ |

## 10. Cierre operativo

El siguiente paso recomendado es implementar el primer bloque funcional REQ completo: SP, BE, FE, rutas, aprobaciones, log y analisis presupuestario informativo. Despues, avanzar a REQ aprobados / pendientes de compra y finalmente PreOC.
