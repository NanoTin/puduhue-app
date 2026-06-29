# Modulo Compras - Contrato SP REQ

> Contrato tecnico para implementar el primer corte funcional REQ.
>
> Este documento define nombres, responsabilidades, parametros y salidas esperadas de los Stored Procedures de REQ. No contiene SQL ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE.agent`
- `docs/CODEX_TASK_CONTEXT.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_presupuesto_definitivo.md`
- `docs/ADR/modulo-compras/ADR-001-modelo-presupuesto-compras.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `database/alter_table/07_modulo_compras_bases_compartidas.sql`
- `database/alter_table/08_modulo_compras_req.sql`
- `database/alter_table/09_modulo_compras_req_pendientes.sql`
- `database/alter_table/11_modulo_compras_presupuesto_sp.sql`

## 2. Reglas transversales

- Todos los SP usan firma estandar:
  `p_in_json`, `p_in_usuarioid`, `p_in_dispositivo`, `p_in_ip`, `p_out_json`.
- Los SP de mantenimiento no deben incluir `BEGIN`, `COMMIT` ni `ROLLBACK`; PHP controla transacciones mediante `callSpMaint()`.
- Los SP de consulta se consumen con `callSpQuery()` y no devuelven data en `p_out_json`; la data sale por `SELECT`.
- En SP de consulta, `p_out_json` se usa solo para meta/error: `status` y `message`; no contiene data de negocio.
- En SP de mantenimiento, `p_out_json` puede devolver meta operativa acotada como `status`, `message`, `id`, `estado` o resumen de la accion ejecutada.
- Los campos `auditcreacion*` y `auditedicion*` nunca vienen desde `p_in_json`.
- Los SP de mantenimiento deben insertar LOG tecnico en `reqcompraslog` cuando afecten cabecera, estado, detalle, firmantes o comentarios.
- `sp_compras_req_ppto_snapshot_actualizar` reutiliza el SP existente del incremental 11 y no inserta LOG tecnico propio; cuando se invoque desde `sp_compras_req_crear` o `sp_compras_req_editar`, el LOG tecnico lo registra el SP padre para evitar doble trazabilidad.
- Comentarios funcionales visibles van en `reqcomprascomentarios`; no reemplazan el LOG tecnico.
- Combos, grillas y modales de seleccion no deben generar SP auxiliares por cada interaccion; se resuelven desde Services reutilizables con metodos `*FormSelect` y `*FormGrid`, salvo que la consulta requiera regla transaccional critica.
- REQ no genera movimientos presupuestarios.
- El analisis presupuestario de REQ es informativo y no bloqueante.
- PreOC es el unico flujo que compromete presupuesto.

## 3. Estados y permisos base

Estados REQ vigentes: `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL`.

Permisos/validaciones de usuario:

- Usuario ejecutor debe existir, estar activo y no bloqueado.
- Crear/editar REQ requiere al menos un centro activo asignado en `usuarioscentroscosto`.
- Aprobar/rechazar REQ requiere `usuariopermiteaprobreq = 1`.
- Firmantes manuales y reemplazantes deben ser usuarios activos con `usuariopermiteaprobreq = 1`.
- Autorizadores fuera de presupuesto se resuelven con `usuarioreqautorizadorfuerapptocompra = 1` y `usuarioreqautorizadorfuerapptocompraorden > 0`.

## 4. SP de consulta

| SP | Objetivo | JSON entrada | SELECT esperado | Tablas principales |
|---|---|---|---|---|
| `sp_compras_req_listar_resumen` | Listar REQ para pantalla principal. | `filtroBusqueda`, `filtroEstado`, `filtroFechaDesde`, `filtroFechaHasta`, `filtroCentroCostoId`, `filtroPrioridad`, `filtroSoloVigentes` | Una fila por REQ con cabecera, centro, funcionario, estado, aprobador pendiente, totales, flags de presupuesto y conteo de lineas. | `reqcompras`, `centroscosto`, `funcionarios`, `reqcomprasestados`, `usuarios`, `reqcomprasdetalle` |
| `sp_compras_req_listar_pendientes_aprobacion` | Listar REQ pendientes de aprobacion para el usuario login. | `filtroBusqueda`, `filtroFechaDesde`, `filtroFechaHasta`, `filtroCentroCostoId`, `filtroPrioridad` | REQ en `PND` con `reqaprobadoridpnd = p_in_usuarioid`, datos de cabecera, solicitante, centro, total y advertencias. | `reqcompras`, `reqcomprasfirmantes`, `centroscosto`, `funcionarios` |
| `sp_compras_req_consulta_por_id_resumen` | Consultar cabecera para ver/editar/aprobar. | `reqcompraid` | Cabecera completa, centro, funcionario, estado, aprobador pendiente, flags y totales. | `reqcompras`, `centroscosto`, `funcionarios`, `reqcomprasestados`, `usuarios` |
| `sp_compras_req_consulta_por_id_detalle` | Consultar lineas del REQ. | `reqcompraid` | Lineas ordenadas por `reqcompradetlinea`, snapshots de item, cantidades, precios, ultimo REQ y advertencia de presupuesto. | `reqcomprasdetalle`, `invitems`, `subfamilias`, `invunidadesmedidas` |
| `sp_compras_req_consulta_por_id_firmantes` | Consultar firmantes del REQ. | `reqcompraid` | Firmantes ordenados por `firmanteorden`, usuario, tipo, estado, motivos, reemplazo y comentario. | `reqcomprasfirmantes`, `usuarios` |
| `sp_compras_req_consulta_por_id_comentarios` | Consultar comentarios funcionales visibles. | `reqcompraid` | Comentarios ordenados por fecha/hora con usuario y tipo. | `reqcomprascomentarios`, `usuarios` |
| `sp_compras_req_ppto_analizar` | Mostrar analisis presupuestario informativo actual del REQ sin escribir snapshot. | `reqcompraid` | Grupos por subfamilia/centro con saldo disponible, otros REQ en curso, aprobados pendientes, monto del REQ, saldo proyectado, porcentaje, deficit y advertencia. La temporada solo se muestra si el SELECT la entrega explicitamente. | `reqcompras`, `reqcomprasdetalle`, `pptocompra`, `temporadas`, `reqaprobados`, `reqcompraspptosnapshot` |

Reglas para listados:

- Los filtros vacios o nulos no restringen.
- `filtroBusqueda` aplica con `LIKE '%valor%'` sobre una cadena logica compuesta con campos visibles del listado.
- En `sp_compras_req_listar_resumen`, la cadena de busqueda incluye: `reqcompracod`, `reqcompraobs`, nombre del centro de costo, nombre del usuario creador, nombre del funcionario solicitante si existe y nombre del aprobador pendiente si existe.
- En `sp_compras_req_listar_pendientes_aprobacion`, la cadena de busqueda incluye: `reqcompracod`, `reqcompraobs`, nombre del centro de costo, nombre del usuario creador y nombre del funcionario solicitante si existe. No incluye aprobador pendiente porque corresponde al usuario login.
- `filtroFechaDesde` nulo usa fecha minima operativa.
- `filtroFechaHasta` nulo usa fecha actual.
- No filtrar por usuario pasado por URL; el usuario viene de `p_in_usuarioid` y sesion/controlador.

## 5. Fuentes auxiliares para formularios y modales

No se crean SP especificos para cada combo, grilla o modal de seleccion del formulario REQ. El patron acordado replica y mejora lo usado en otros modulos con metodos `*FormSelect` y `*FormGrid`.

El contrato BE debe centralizar estos catalogos en:

- `src/Services/ComprasCatalogosService.php`

Metodos publicos esperados, con nombres especificos por regla funcional:

| Metodo | Uso | Fuente de datos / regla |
|---|---|---|
| `listarCentrosCostoUsuarioFormSelect` | Combo centro de costo en crear/editar/listar. | `usuarioscentroscosto` activo del usuario login, con datos de `centroscosto`. |
| `listarUsuariosAprobadoresReqFormGrid` | Modal/grilla para firmantes manuales REQ. | Usuarios activos con `usuariopermiteaprobreq = 1`. |
| `listarItemsCompraReqFormGrid` | Modal/grilla de items REQ. | `invitems` activos, comprables, precio mayor a cero y tipo compatible con `reqcompratipo`. |
| `listarFuncionariosFormSelect` | Combo/busqueda opcional de solicitante. | `funcionarios` activos, opcionalmente filtrados por centro. |

Reglas:

- El Service puede usar `Database::select()` con SQL simple o reutilizar SP/listados existentes del maestro cuando ya existan y sirvan al caso.
- La vista no ejecuta SQL ni SP; pide datos al controlador, y el controlador los obtiene desde Services.
- Los metodos publicos deben ser especificos por caso de uso cuando la regla cambia, por ejemplo aprobadores REQ y aprobadores PreOC separados.
- Se permiten helpers privados internos para evitar duplicar SQL comun, por ejemplo listar usuarios por columna de permiso.
- Si un catalogo pasa a requerir validacion transaccional, auditoria funcional o reglas compartidas criticas, se puede promover a SP con contrato explicito.

## 6. SP de mantenimiento REQ

| SP | Objetivo | JSON entrada minimo | Salida `p_out_json` | Tablas afectadas |
|---|---|---|---|---|
| `sp_compras_req_crear` | Crear REQ en `BRR` o enviar directo a `PND`. | `reqcompratipo`, `centrocostoid`, `funcionariorut`, `reqcompraobs`, `reqcompraprioridad`, `accion`, `detalle[]`, `firmantesManual[]` | `status`, `message`, `id`, `reqcompracod`, `estado`, `reqaprobadoridpnd`, `advertenciapptocompra`, `fuerapptocompra` | `reqcompras`, `reqcomprasdetalle`, `reqcomprasfirmantes`, `reqcompraspptosnapshot`, `reqcomprascomentarios`, `reqcompraslog` |
| `sp_compras_req_editar` | Editar REQ permitido y guardarlo como borrador o reenviarlo a aprobacion. | `reqcompraid`, cabecera editable, `accion`, `detalle[]`, `firmantesManual[]`, `comentario` opcional | `status`, `message`, `id`, `estado`, `reqaprobadoridpnd`, `advertenciapptocompra`, `fuerapptocompra` | `reqcompras`, `reqcomprasdetalle`, `reqcomprasfirmantes`, `reqcompraspptosnapshot`, `reqcomprascomentarios`, `reqcompraslog` |
| `sp_compras_req_tomar_edicion` | Pasar de `PND` a `EDT` mediante accion POST explicita antes de mostrar el formulario de edicion. | `reqcompraid` | `status`, `message`, `id`, `estado` | `reqcompras`, `reqcompraslog` |
| `sp_compras_req_cancelar_edicion` | Salir de `EDT` sin guardar cambios funcionales, liberando el REQ. | `reqcompraid`, `motivo` opcional | `status`, `message`, `id`, `estado` | `reqcompras`, `reqcompraslog` |
| `sp_compras_req_liberar_edicion` | Liberar `EDT` por regla controlada de abandono/perdida de conexion. | `reqcompraid`, `motivo` | `status`, `message`, `id`, `estado` | `reqcompras`, `reqcompraslog` |
| `sp_compras_req_aprobar` | Aprobar el firmante pendiente del usuario login y avanzar flujo. | `reqcompraid`, `comentario` opcional | `status`, `message`, `id`, `estado`, `reqaprobadoridpnd`, `aprobadoCompleto` | `reqcompras`, `reqcomprasfirmantes`, `reqcomprascomentarios`, `reqaprobados`, `reqcompraslog` |
| `sp_compras_req_rechazar` | Rechazar REQ con comentario obligatorio. | `reqcompraid`, `comentario` | `status`, `message`, `id`, `estado` | `reqcompras`, `reqcomprasfirmantes`, `reqcomprascomentarios`, `reqcompraslog` |
| `sp_compras_req_anular` | Anular REQ cuando el estado lo permita. | `reqcompraid`, `comentario` | `status`, `message`, `id`, `estado` | `reqcompras`, `reqcomprascomentarios`, `reqcompraslog` |
| `sp_compras_req_ppto_snapshot_actualizar` | Recalcular y persistir snapshot presupuestario informativo. | `reqcompraid` | `status`, `message`, `reqcompraid`, `advertencia`, `fuerapptocompra`, `grupos` | `reqcompraspptosnapshot`, `reqcompras`, `reqcomprasdetalle` |

Nota de nomenclatura:

- REQ es un flujo transaccional, no un CRUD maestro simple. Por eso se usa `sp_compras_req_crear` en vez de `sp_compras_req_insertar`: el SP no solo inserta cabecera, tambien resuelve detalle, firmantes, estado inicial, snapshot presupuestario informativo y LOG del flujo.
- `sp_compras_req_ppto_snapshot_actualizar` mantiene la salida del incremental 11: `status`, `message`, `reqcompraid`, `advertencia`, `fuerapptocompra`, `grupos`.

Regla de `reqcompracod`:

- Formato definitivo: `REQ-00000001`.
- Composicion: prefijo fijo `REQ-` + `LPAD(reqcompraid, 8, '0')`.
- Es global, no editable por usuario, no reciclable y no cambia si el REQ se anula.
- `sp_compras_req_crear` debe insertarlo de forma controlada despues de obtener el `reqcompraid` generado.

## 7. Contrato de `detalle[]`

Cada elemento de `detalle[]` debe contener:

- `invitemid`
- `reqcompradetcantidad`
- `reqcompradetobs` opcional

El SP resuelve y guarda desde maestros:

- `subfamiliaid`
- `reqcompradetitemcod`
- `reqcompradetdsc`
- `invunidmedid`
- `reqcompradetprecioneto`
- `reqcompradettotalneto`
- `reqcompradetultreqfecha`
- `reqcompradetultreqcantidad`

Validaciones:

- Toda accion que persista cabecera/detalle en tablas debe validar datos minimos y al menos una linea valida, tanto al crear como al editar.
- No se permite mezclar Material y Servicio.
- Material/Servicio se resuelve desde `invitemstockeable`: `1` Material, `0` Servicio.
- El item debe estar activo y cumplir `invitemcompra = 1`.
- Items con precio cero no se agregan; mensaje esperado: contactar a Administracion.
- No se duplica `invitemid` dentro del mismo REQ.
- Cantidad debe ser mayor a cero.

## 8. Contrato de `firmantesManual[]`

Cada elemento de `firmantesManual[]` debe contener:

- `usuarioid`
- `firmanteorden` cuando el frontend envie orden manual.

Reglas:

- Solo usuarios activos con `usuariopermiteaprobreq = 1`.
- No se permiten duplicados.
- Firmantes default se regeneran desde jefe de centro y jefe tecnico del centro.
- Firmantes manuales activos se conservan al reenviar desde `RCH`.
- Firmantes fuera de presupuesto se agregan internamente al final si el snapshot detecta deficit.
- Firmantes fuera de presupuesto usan `usuarioreqautorizadorfuerapptocompra` y `usuarioreqautorizadorfuerapptocompraorden`.
- Aplica reemplazo por `aprobadoresperiodoinactividad`.

## 9. Flujo de estados

### 9.1 Crear/guardar

- La accion `guardar_borrador` crea el REQ en `BRR`.
- La accion `enviar_aprobacion` crea el REQ y lo deja en `PND`.
- Toda accion de crear que persista datos en tablas (`guardar_borrador` o `enviar_aprobacion`) requiere al menos:
  - `reqcompratipo`,
  - `centrocostoid`,
  - `reqcompraprioridad`,
  - una linea valida en `detalle[]`.
- `funcionariorut` y `reqcompraobs` son opcionales.
- Crear enviando requiere, ademas, al menos un firmante activo resultante.
- Si no hay firmantes default ni manuales, el documento queda `BRR` y debe devolver advertencia.
- El boton de cancelar/descartar en crear no invoca SP de mantenimiento ni persiste datos; es una accion FE/UX.
- Envio a aprobacion deja estado `PND` y asigna `reqaprobadoridpnd`.

### 9.2 Editar

- Se permite editar en `BRR` y `RCH`.
- Desde `PND`, solo el creador puede tomar edicion si no existe aprobacion efectiva; cambia a `EDT`.
- No se permite editar si existe al menos un firmante `APR` o si el estado es `APR`.
- Cada edicion permitida actualiza `reqcomprafecha` desde sistema/BD.
- Mientras el REQ este en `EDT`, solo el usuario creador que tomo la edicion puede ejecutar `guardar_borrador` o `reenviar_aprobacion`.
- En `EDT`, `sp_compras_req_editar` admite solo estas acciones:
  - `guardar_borrador`: guarda cambios y deja el REQ en `BRR`;
  - `reenviar_aprobacion`: guarda cambios, regenera flujo de firmantes segun contrato y deja el REQ en `PND`.
- Si durante `EDT` aparece al menos una aprobacion efectiva (`firmanteestado = 'APR'`), el SP debe rechazar `guardar_borrador`, `reenviar_aprobacion` y `sp_compras_req_cancelar_edicion`, devolviendo error funcional para evitar reabrir un flujo ya aprobado parcialmente.
- `sp_compras_req_cancelar_edicion` no guarda cambios funcionales y devuelve el REQ a `PND`.
- `sp_compras_req_cancelar_edicion` se invoca desde una ruta dedicada; no forma parte del set de acciones del payload de `sp_compras_req_editar`.
- `sp_compras_req_cancelar_edicion` solo restaura estado documental y `reqaprobadoridpnd` segun la lista vigente; no modifica cabecera, detalle, snapshot ni comentarios funcionales.
- Si el usuario cierra navegador/pestana, vuelve atras, pierde conexion o abandona el flujo, el REQ queda en `EDT` hasta que el creador retome la edicion o se ejecute una liberacion controlada.
- `sp_compras_req_liberar_edicion` no se ejecuta automaticamente por eventos del navegador; queda reservado para accion controlada/manual con motivo obligatorio.

### 9.3 Aprobar

- Solo puede aprobar el usuario igual a `reqaprobadoridpnd`.
- Antes de aprobar se revalida estado `PND`; si el REQ esta en `EDT`, se devuelve error funcional.
- La aprobacion marca el firmante pendiente como `APR`.
- Luego se resuelve el siguiente aprobador vigente.
- Si no quedan aprobadores pendientes validos, el REQ pasa a `APR`, limpia `reqaprobadoridpnd`, setea `reqaprobacionfecha` y crea `reqaprobados`.

### 9.4 Rechazar

- Solo puede rechazar el usuario igual a `reqaprobadoridpnd`.
- Comentario obligatorio de mas de 10 caracteres.
- Pasa a `RCH`, limpia `reqaprobadoridpnd`, marca firmante como `RCH` y registra comentario funcional.

### 9.5 Anular

- Debe requerir comentario obligatorio.
- No debe borrar fisicamente el REQ.
- Pasa a `ANL` cuando el estado lo permita.

## 10. Analisis presupuestario REQ

`sp_compras_req_ppto_analizar` y `sp_compras_req_ppto_snapshot_actualizar` deben resolver presupuesto por fecha REQ dentro de la temporada de compras vigente y agrupar por:

- `subfamiliaid`;
- `centrocostoid`.

La temporada se usa para resolver el presupuesto vigente. No es un campo obligatorio de salida del analisis REQ; si el backend no la entrega explicitamente, FE no debe mostrarla.

Cada grupo debe considerar:

- saldo disponible actual del presupuesto;
- monto de otros REQ vigentes en `PND` o `EDT`, excluyendo el REQ actual;
- monto de REQ aprobados pendientes de compra;
- monto del REQ actual;
- saldo disponible proyectado;
- porcentaje del REQ sobre saldo disponible actual;
- deficit cuando aplique;
- advertencia si no existe presupuesto o si el proyectado queda bajo cero.

El snapshot persistido debe actualizar:

- `reqcompraspptosnapshot`;
- `reqcompras.reqadvertenciapptocompra`;
- `reqcompras.reqfuerapptocompra`;
- `reqcomprasdetalle.reqcompradetadvertenciappto`.

## 11. Creacion de `reqaprobados`

Al aprobar completamente un REQ, `sp_compras_req_aprobar` debe crear una fila en `reqaprobados` por cada linea de `reqcomprasdetalle`.

Mapeo:

- `reqcompradetid` desde detalle.
- `reqcompraid` desde cabecera.
- `invitemid` desde detalle.
- `reqaprobadoitemcod` desde `reqcompradetitemcod`.
- `reqaprobadoitemdsc` desde `reqcompradetdsc`.
- `invunidmedid` desde detalle.
- `reqaprobadocantidadreq` desde `reqcompradetcantidad`.
- `reqaprobadocantidadpendiente` igual a cantidad requerida.
- `reqaprobadocantidadcomprada` igual a `0`.
- `reqaprobadocantidadanulada` igual a `0`.
- `reqaprobadoprecioneto` desde `reqcompradetprecioneto`.
- `reqaprobadoestado` igual a `1`.
- `reqaprobadofecha` desde fecha sistema/BD.

La operacion debe ser idempotente: si la fila por `reqcompradetid` ya existe y el REQ ya esta `APR`, no debe duplicar pendientes.

## 12. Validaciones de implementacion

Cuando se implemente SQL:

- Validar sintaxis SQL antes de entregar.
- No ejecutar SQL contra BD real sin autorizacion explicita.
- Confirmar que ningun SP contiene `BEGIN`, `COMMIT` ni `ROLLBACK`.

Cuando se implemente PHP:

- Ejecutar `php -l` en archivos PHP nuevos o modificados.
- Ejecutar `git diff --check`.

## 13. Cierre documental

- Contrato BE y contrato FE ya existen para el primer corte REQ.
- Las rutas, clases, vistas y variables definitivas se implementan segun esos contratos y patrones existentes del proyecto.
