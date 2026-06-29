# Modulo Compras - Contrato SP REQ Aprobados / Pendientes de Compra

> Contrato tecnico para implementar el puente funcional entre REQ aprobado y PreOC.
>
> Este documento define nombres, responsabilidades, parametros y salidas esperadas de los Stored Procedures de pendientes de compra. No contiene SQL ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE.agent`
- `docs/CODEX_TASK_CONTEXT.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/modulo_compras_presupuesto_definitivo.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `database/alter_table/08_modulo_compras_req.sql`
- `database/alter_table/09_modulo_compras_req_pendientes.sql`
- `database/alter_table/10_modulo_compras_preoc.sql`
- `database/sp/02_sp_compras_req.sql`

## 2. Objetivo

Implementar el corte separado de `REQ aprobados / pendientes de compra`, requerido antes de PreOC.

Este corte:

- lista lineas aprobadas disponibles para compra;
- permite anular cantidad pendiente con motivo obligatorio;
- permite cambio de item por comprador cuando la regla lo permite;
- expone historial operativo por linea;
- prepara la seleccion de pendientes para crear PreOC;
- conserva `reqaprobadoshistorial` como vinculo operativo de compra/anulacion.

## 3. Reglas transversales

- Todos los SP usan firma estandar:
  `p_in_json`, `p_in_usuarioid`, `p_in_dispositivo`, `p_in_ip`, `p_out_json`.
- Los SP de mantenimiento no deben incluir `BEGIN`, `COMMIT` ni `ROLLBACK`; PHP controla transacciones mediante `Database::callSpMaint()`.
- Los SP de consulta se consumen con `Database::callSpQuery()` y devuelven data mediante `SELECT`.
- `p_out_json` en consultas solo contiene meta/error.
- Los campos `auditcreacion*` y `auditedicion*` nunca vienen desde `p_in_json`.
- `reqaprobados` no apunta a una PreOC unica.
- El vinculo con PreOC vive en `reqaprobadoshistorial.preocid` y `reqaprobadoshistorial.preocdetid`.
- `reqaprobadoshistorial.preocdetid` referencia `preocdetallereqitems.preocdetreqitemid`; no renombrar la columna en contrato ni implementacion.
- Se permiten compras parciales.
- La consistencia obligatoria es:
  `reqaprobadocantidadreq = reqaprobadocantidadpendiente + reqaprobadocantidadcomprada + reqaprobadocantidadanulada`.
- La anulacion opera solo sobre cantidad pendiente.
- El cambio de item ocurre en pendientes de compra, no dentro de PreOC.
- No crear DDL nuevo en este contrato.

## 4. Estados operativos de `reqaprobados`

`reqaprobadoestado` usa los codigos numericos existentes del DDL:

| Codigo | Estado | Regla |
|---|---|---|
| `1` | Pendiente | Toda la cantidad aprobada sigue pendiente. |
| `2` | Parcial | Existe compra o anulacion parcial y aun queda saldo pendiente. |
| `3` | Completa | No queda cantidad pendiente por compra; se compro todo el saldo operativo. |
| `4` | Anulada | No queda cantidad pendiente porque fue anulada operativamente. |

Regla de recalculo:

- Si `reqaprobadocantidadpendiente = reqaprobadocantidadreq`, estado `1`.
- Si `reqaprobadocantidadpendiente > 0` y es menor que `reqaprobadocantidadreq`, estado `2`.
- Si `reqaprobadocantidadpendiente = 0` y `reqaprobadocantidadcomprada > 0`, estado `3`.
- Si `reqaprobadocantidadpendiente = 0` y `reqaprobadocantidadcomprada = 0` y `reqaprobadocantidadanulada > 0`, estado `4`.

## 5. SP de consulta

| SP | Objetivo | JSON entrada | SELECT esperado | Tablas principales |
|---|---|---|---|---|
| `sp_compras_req_pendientes_listar_resumen` | Listar lineas aprobadas pendientes/parciales para comprador y seleccion PreOC. | `filtroBusqueda`, `filtroFechaDesde`, `filtroFechaHasta`, `filtroCentroCostoId`, `filtroTipoReq`, `filtroEstado`, `filtroSoloConSaldo` | Una fila por `reqaprobadoid`, con REQ, item snapshot, item actual, centro, cantidades, estado, flags de cambio, presupuesto informativo si aplica. | `reqaprobados`, `reqcompras`, `reqcomprasdetalle`, `centroscosto`, `invitems`, `invunidadesmedidas` |
| `sp_compras_req_pendientes_consulta_por_id_resumen` | Consultar una linea aprobada para ver/anular/cambiar item. | `reqaprobadoid` | Cabecera operativa de la linea, REQ origen, cantidades, estado y datos de item. | `reqaprobados`, `reqcompras`, `reqcomprasdetalle`, `centroscosto`, `invitems` |
| `sp_compras_req_pendientes_consulta_por_id_historial` | Consultar movimientos de compra/anulacion/ajuste asociados a una linea. | `reqaprobadoid` | Historial ordenado por fecha/hora con tipo, cantidad, saldo anterior, PreOC si existe, item snapshot y observacion. | `reqaprobadoshistorial`, `preoc`, `preocdetallereqitems`, `usuarios` |
| `sp_compras_req_pendientes_consulta_por_id_cambios` | Consultar cambios de item de una linea aprobada. | `reqaprobadoid` | Cambios de item ordenados por fecha/hora con item original, item nuevo, usuario y motivo. | `reqaprobadoscambios`, `invitems`, `usuarios` |
| `sp_compras_req_pendientes_preoc_seleccion` | Listar lineas elegibles para crear PreOC. | `filtroBusqueda`, `filtroFechaDesde`, `filtroFechaHasta`, `filtroCentroCostoId`, `filtroTipoReq`, `reqaprobadoids[]` opcional | Lineas con saldo pendiente mayor a cero, datos suficientes para seleccion y validacion de tipo Material/Servicio. | `reqaprobados`, `reqcompras`, `reqcomprasdetalle`, `centroscosto`, `invitems` |

Reglas de filtros:

- Filtros vacios o nulos no restringen.
- `filtroBusqueda` aplica con `LIKE` sobre codigo REQ, descripcion item, codigo item, centro de costo, solicitante/creador si el SELECT lo expone.
- `filtroFechaDesde` nulo usa fecha minima operativa.
- `filtroFechaHasta` nulo usa fecha actual.
- `filtroSoloConSaldo = 1` restringe a `reqaprobadocantidadpendiente > 0`.
- No filtrar comprador desde URL como regla de seguridad; permisos se validan por usuario ejecutor.

## 6. SP de mantenimiento

| SP | Objetivo | JSON entrada minimo | Salida `p_out_json` | Tablas afectadas |
|---|---|---|---|---|
| `sp_compras_req_pendientes_anular_saldo` | Anular cantidad pendiente parcial o total con motivo obligatorio. | `reqaprobadoid`, `cantidad`, `motivo` | `status`, `message`, `id`, `cantidadPendiente`, `estado` | `reqaprobados`, `reqaprobadoshistorial`, `reqcomprasdetalle` |
| `sp_compras_req_pendientes_cambiar_item` | Cambiar item de una linea pendiente cuando no existan transacciones posteriores. | `reqaprobadoid`, `invitemidnuevo`, `motivo` | `status`, `message`, `id`, `invitemid`, `estado` | `reqaprobados`, `reqaprobadoscambios`, `reqaprobadoshistorial`, `reqcomprasdetalle` |

## 7. Anulacion de saldo pendiente

Validaciones:

- Usuario ejecutor debe existir, estar activo y no bloqueado.
- Usuario ejecutor debe ser comprador (`usuariocomprador = 1`).
- `reqaprobadoid` debe existir.
- `cantidad` debe ser mayor a cero.
- `cantidad` no puede superar `reqaprobadocantidadpendiente`.
- `motivo` es obligatorio y debe tener mas de 10 caracteres.
- Si `reqaprobadocantidadpendiente = 0`, no se puede anular.

Efectos:

- Insertar historial con:
  - `histtipo = 'ANULACION'`;
  - `histcantidadpendienteantes`;
  - `histcantidad`;
  - `histusuarioid = p_in_usuarioid`;
  - `histobs = motivo`.
- Actualizar `reqaprobados`:
  - restar `cantidad` a `reqaprobadocantidadpendiente`;
  - sumar `cantidad` a `reqaprobadocantidadanulada`;
  - recalcular `reqaprobadoestado`;
  - actualizar auditoria de edicion.
- Actualizar `reqcomprasdetalle.reqitemcantanulada` si la columna existe en el DDL vigente.

## 8. Cambio de item

Validaciones:

- Usuario ejecutor debe ser comprador (`usuariocomprador = 1`).
- `reqaprobadoid` debe existir y tener cantidad pendiente mayor a cero.
- `motivo` es obligatorio y debe tener mas de 10 caracteres.
- No deben existir transacciones posteriores en `reqaprobadoshistorial` para la linea.
- El item nuevo debe existir, estar activo, ser comprable y tener precio mayor a cero si la regla vigente del maestro lo requiere.
- El item nuevo no puede existir ya en el REQ original.
- El cambio no puede alterar Material/Servicio:
  - Material: `invitemstockeable = 1`.
  - Servicio: `invitemstockeable = 0`.
- El item nuevo debe tener unidad de medida y subfamilia validas para el flujo de compras.

Efectos:

- Insertar en `reqaprobadoscambios`:
  - `reqaprobadoid`;
  - `invitemidoriginal`;
  - `invitemidnuevo`;
  - `reqcambioobs`;
  - `reqcambiousuarioid`.
- Insertar historial con `histtipo = 'AJUSTE'`, cantidad cero o cantidad informativa segun se cierre en implementacion, item anterior/nuevo y motivo.
- Actualizar `reqaprobados` con el nuevo item y snapshots:
  - `invitemid`;
  - `reqaprobadoitemcod`;
  - `reqaprobadoitemdsc`;
  - `invunidmedid`;
  - `reqaprobadoprecioneto`.
- Marcar visualmente el detalle REQ mediante `reqcomprasdetalle.reqcompradetitemmodificado` si la columna existe en el DDL vigente.

## 9. Preparacion para PreOC

La seleccion para PreOC debe entregar solo lineas con:

- `reqaprobadocantidadpendiente > 0`;
- `reqaprobadoestado IN (1, 2)`;
- REQ origen vigente y aprobado;
- tipo compatible entre lineas seleccionadas si el contrato PreOC exige no mezclar Material/Servicio.

La seleccion no crea PreOC ni reserva presupuesto. Solo alimenta el formulario PreOC.

## 10. Fuera de alcance

- Crear PreOC.
- Reservar, confirmar o revertir presupuesto.
- Ejecutar integracion ERP/Finnegans.
- Crear DDL nuevo.
- Cotizaciones.
- Multimoneda.
- Cambiar item dentro de PreOC.

## 11. Validaciones de implementacion

Cuando se implemente SQL:

- Validar sintaxis SQL antes de entregar.
- Confirmar que ningun SP contiene `BEGIN`, `COMMIT` ni `ROLLBACK`.
- No ejecutar SQL contra BD real sin autorizacion explicita.

Cuando se implemente PHP:

- Ejecutar `php -l` en PHP nuevo o modificado.
- Ejecutar `git diff --check`.
