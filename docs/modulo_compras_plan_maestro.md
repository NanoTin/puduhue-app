# Modulo Compras - Plan Maestro

> Documento maestro de estado del modulo Compras.
>
> Este archivo no contiene SQL ejecutable. Centraliza decisiones vigentes, estado de incrementales, pendientes y secuencia recomendada para continuar sin mezclar DDL, SP, backend, frontend ni integracion ERP.

## 1. Fuentes normativas

- `docs/modulo_compras_corte_sql_previo.md`
- `docs/modulo_compras_incremental_07_diseno.md`
- `docs/modulo_compras_brechas_sql.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_preoc_estructura.md`
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

### 2.3 Pendientes de compra

- `reqaprobados` mantiene lineas aprobadas listas para compra.
- `reqaprobados` no apunta a una PreOC unica.
- El vinculo de compra o anulacion vive en `reqaprobadoshistorial`.
- Se permiten compras parciales.
- La consistencia operativa es:
  `reqaprobadocantidadreq = reqaprobadocantidadpendiente + reqaprobadocantidadcomprada + reqaprobadocantidadanulada`.
- La anulacion opera solo sobre cantidad pendiente y requiere motivo obligatorio.
- El cambio de item lo ejecuta el comprador desde pendientes de compra, no dentro de PreOC.

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

### 2.5 Bases compartidas

- `usuarios` debe separar permisos de aprobacion REQ, aprobacion PreOC, comprador, anulacion PreOC, edicion de precios, creacion/edicion de item, sincronizacion ERP y autorizador fuera de presupuesto.
- `usuarioscentroscosto` define centros disponibles para crear REQ y centro default.
- Solo puede existir un centro default activo por usuario; en el primer corte se valida en SP/BE.
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
| 08 | `database/alter_table/08_modulo_compras_req.sql` | Creado. DDL REQ base. Pendiente revalidar tras cambio de PK de estados por codigo. |
| 09 | `database/alter_table/09_modulo_compras_req_pendientes.sql` | Creado. Pendientes de compra (`reqaprobados*`). Pendiente revalidar tras recreacion. |
| 10 | `database/alter_table/10_modulo_compras_preoc.sql` | Creado. DDL PreOC. Pendiente revalidar tras correccion MariaDB en FK finales. |
| 11 | `database/alter_table/11_modulo_compras_presupuesto_sp.sql` | Creado como placeholder/contrato pendiente. Sin SQL ejecutable por ahora. |

### 3.1 Auditoria aplicada

- `reqaprobados*` queda solo en incremental 09.
- Estados REQ corregidos a codigos `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL`.
- Estados REQ y PreOC ajustados para usar codigo como PK.
- PreOC permite compras parciales; no debe existir unicidad por `reqaprobadoid` en `preocdetallereqitems`.
- FK finales de PreOC hacia `reqaprobadoshistorial` fueron ajustadas para MariaDB sin `ADD CONSTRAINT IF NOT EXISTS`.

### 3.2 Estado de validacion manual en BD local

- 07: OK.
- 08: pendiente revalidar tras cambio de PK por codigo.
- 09: pendiente revalidar tras recreacion.
- 10: pendiente revalidar tras correccion MariaDB.
- 11: sin SQL ejecutable por ahora.

## 4. Secuencia recomendada

### 4.1 Base SQL

1. Ejecutar/revalidar incremental 07.
2. Ejecutar/revalidar incremental 08.
3. Ejecutar/revalidar incremental 09.
4. Ejecutar/revalidar incremental 10.
5. Definir contrato tecnico del incremental 11.
6. Implementar incremental 11 solo despues de cerrar parametros, salidas, referencias y efectos presupuestarios.

### 4.2 Backend y SP

1. Actualizar SP de usuarios para nuevas columnas de permisos y logs.
2. Actualizar SP de presupuesto de compra para responsable, administrador y colaborador.
3. Actualizar SP de items para `iteminglocal` y permisos de crear/editar item.
4. Crear contratos/SP de analisis REQ:
   - resolver presupuesto informativo,
   - generar snapshot,
   - detectar deficit,
   - resolver firmantes fuera de presupuesto.
5. Crear contratos/SP de pendientes de compra:
   - crear `reqaprobados` al aprobar REQ,
   - cambiar item con motivo,
   - anular saldo pendiente,
   - consultar pendientes/parciales.
6. Crear contratos/SP de PreOC:
   - crear borrador desde pendientes,
   - resolver presupuesto por linea,
   - calcular totales/impuestos,
   - generar firmantes,
   - resolver siguiente aprobador.
7. Crear contratos/SP presupuestarios del incremental 11:
   - reserva `POC_RESERVA`,
   - confirmacion `POC_CONFIRMACION`,
   - reversa `POC_REVERSA`,
   - liberacion al volver de `PND` a `BRR` sin aprobaciones,
   - recalculo de saldos.
8. Preparar integracion ERP PreOC aprobada:
   - POST ERP,
   - manejo de `SNC`/`ERR`,
   - reintento,
   - error visible.

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

## 5. Reglas transversales de implementacion

### 5.1 Backend/BD

- Usar `src/Config/Database.php`.
- Consumir SP desde Models/Services; no llamar SP desde vistas.
- Los SP mantienen firma estandar:
  `p_in_json`, `p_in_usuarioid`, `p_in_dispositivo`, `p_in_ip`, `p_out_json`.
- Los SP no deben contener `BEGIN`, `COMMIT` ni `ROLLBACK`; la transaccion la controla PHP con `callSpMaint()`.
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

- Definir contrato tecnico del incremental 11.
- Validar sintaxis final de 08, 09 y 10 en MariaDB local.
- Confirmar estrategia de recreacion/migracion si ya existen tablas de 08/09 en BD local.
- Actualizar logs y SP existentes para nuevas columnas de usuarios, presupuesto e items.
- Definir SP/servicios de reserva, confirmacion, reversa y recalculo presupuestario.
- Confirmar campos obligatorios definitivos del POST ERP de PreOC.
- Confirmar conceptos/impuestos activos requeridos por Finnegans.
- Confirmar comportamiento esperado de `NumeroComprobante`.
- Preparar prompts cerrados para Spark cuando corresponda.

## 7. Riesgos y ambiguedades a cerrar

- Naming de autorizador fuera de presupuesto: algunas fuentes antiguas mencionan `reqautorizadorfuerapptocompra`; el nombre tecnico consolidado en los incrementales y estructura vigente es `usuarioreqautorizadorfuerapptocompra`.
- Si 08/09 ya fueron ejecutados antes de las correcciones, puede requerirse recreacion controlada o migracion especifica antes de revalidar.
- Las FK finales de 10 dependen de que 09 exista y de que MariaDB acepte la forma exacta del `ALTER TABLE` usado.
- `preocitemsdimensiones` esta definido por req-item origen; queda pendiente confirmar campos obligatorios finales con Finnegans.
- `imptoid`/conceptos de impuestos aun dependen de confirmacion tecnica de Finnegans.
- Responsable y administrador de `pptocompra` son obligatorios funcionalmente, pero entran nullable en el primer DDL para permitir backfill.

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
| CMP-016 | SQL 08 | Ajustar maestros de estado REQ para usar codigo como PK | Cerrado | Pendiente revalidacion MariaDB tras cambio |
| CMP-017 | SQL 08 | Revalidar incremental 08 en MariaDB local | Pendiente | Revalidar tras cambio de PK por codigo |
| CMP-018 | SQL 09 | Crear incremental 09 de pendientes de compra | Cerrado | `database/alter_table/09_modulo_compras_req_pendientes.sql` |
| CMP-019 | SQL 09 | Mantener `reqaprobados`, historial y cambios de item solo en incremental 09 | Cerrado | Evita duplicidad con 08 |
| CMP-020 | SQL 09 | Revalidar incremental 09 en MariaDB local | Pendiente | Revalidar tras recreacion |
| CMP-021 | SQL 10 | Crear incremental 10 de DDL PreOC | Cerrado | `database/alter_table/10_modulo_compras_preoc.sql` |
| CMP-022 | SQL 10 | Ajustar PreOC para permitir compras parciales sin unicidad por `reqaprobadoid` | Cerrado | Modelo vigente: vinculo por historial |
| CMP-023 | SQL 10 | Ajustar estados PreOC para usar codigo como PK | Cerrado | Maestro `preocestados` por codigo |
| CMP-024 | SQL 10 | Ajustar FK finales PreOC para MariaDB sin `ADD CONSTRAINT IF NOT EXISTS` | Cerrado | Requiere revalidacion local |
| CMP-025 | SQL 10 | Revalidar incremental 10 en MariaDB local | Pendiente | Revalidar tras correccion MariaDB |
| CMP-026 | SQL 11 | Crear placeholder/contrato pendiente de incremental 11 | Cerrado | `database/alter_table/11_modulo_compras_presupuesto_sp.sql` |
| CMP-027 | SQL 11 | Definir contrato tecnico de SP presupuestarios | Pendiente | No inventar SP sin contrato cerrado |
| CMP-028 | SQL 11 | Implementar reserva `POC_RESERVA` | Pendiente | Depende de CMP-027 |
| CMP-029 | SQL 11 | Implementar confirmacion `POC_CONFIRMACION` | Pendiente | Depende de CMP-027 |
| CMP-030 | SQL 11 | Implementar reversa `POC_REVERSA` | Pendiente | Depende de CMP-027 |
| CMP-031 | SQL 11 | Implementar liberacion de reserva al volver `PND` a `BRR` sin aprobaciones | Pendiente | Depende de CMP-027 |
| CMP-032 | SQL 11 | Implementar recalculo operacional de saldos `pptocompra` | Pendiente | Depende de CMP-027 |
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

## 10. Cierre operativo

El siguiente paso recomendado es revalidar SQL localmente en orden 07, 08, 09 y 10. Despues de eso, cerrar el contrato tecnico de 11 antes de avanzar a SP funcionales, backend o pantallas.
