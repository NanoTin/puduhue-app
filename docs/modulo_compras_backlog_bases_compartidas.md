# Modulo de Compras - Backlog Operativo de Bases Compartidas

> Derivado de `docs/modulo_compras_plan_bases_compartidas.md`.
>
> Plan tecnico complementario: `docs/modulo_compras_plan_tecnico_maestros_erp.md`.
>
> Objetivo: convertir el plan previo en una guia de ejecucion accionable, con prioridad, dependencias y responsable sugerido.

## 1. Criterio de uso

- Este backlog se trabaja antes de cerrar definitivamente REQ y PreOC.
- Cada item debe validarse contra el plan de bases compartidas y el presupuesto definitivo.
- La prioridad ayuda a ordenar el trabajo, pero no impone una secuencia rígida si ya existe claridad tecnica.
- El backlog debe servir para distinguir:
  - lo ya identificado,
  - lo que falta confirmar,
  - lo que se puede desarrollar en paralelo,
  - lo que bloquea al resto.

## 2. Leyenda

- **Prioridad A**: bloqueante para REQ / PreOC.
- **Prioridad B**: necesaria para integracion o validacion robusta.
- **Prioridad C**: deseable o de soporte.

## 3. Estado de avance

- `Pendiente`: aun no comenzado o no validado.
- `Parcial`: existe parte de la base, pero falta cerrar la totalidad.
- `Validado`: ya fue confirmado funcionalmente o por documentacion.
- `Bloqueado`: depende de una definicion externa o tecnica.
- `Listo`: ya puede entrar a desarrollo o integracion.

## 4. Estado de cobertura

### 3.1 Ya identificado

| Area | Estado |
|---|---|
| Temporadas de presupuesto | Identificado |
| Centros de costo | Identificado |
| Funcionarios | Identificado |
| Usuarios y permisos globales | Identificado |
| Maestro de ítems / productos | Identificado |
| Familias y subfamilias | Identificado |
| Proveedores | Identificado en gran parte |
| Condiciones de pago | Identificado en gran parte |
| Categorías fiscales e impuestos | Identificado en gran parte |
| Monedas | Identificado en gran parte |
| Cuentas contables | Identificado en gran parte |
| Workflow / provincias / dimensiones ERP | Identificado en gran parte |
| Aprobadores y periodos de inactividad | Identificado |
| Presupuesto definitivo | Identificado |
| REQ validacion informativa | Identificado |
| PreOC reserva / confirmacion / reversa | Identificado |

### 3.2 Por confirmar o completar

| Area | Estado |
|---|---|
| Algunos endpoints restantes de maestros | Pendiente |
| Integracion de proveedores y estructura local final | Pendiente |
| Impuestos multiples por item y soporte exacto de Finnegans | Pendiente |
| Estructura final de reglas `preocaprobadoresxmonto` | Pendiente |
| Algunos detalles del payload ERP | Pendiente |
| Casos borde de traspasos / reversas | Pendiente parcial |

### 3.3 Puede avanzar en paralelo

| Area | Observacion |
|---|---|
| Centros de costo y usuarios | Independientes del presupuesto |
| Funcionarios y permisos | Puede desarrollarse en paralelo con maestros ERP |
| Aprobadores e inactividad | Puede avanzar en paralelo con REQ y PreOC |
| Familia / subfamilia e items | Puede avanzar junto con presupuesto y validaciones |
| Proveedores / condiciones de pago / fiscalidad | Puede ir en paralelo con presupuesto |
| Presupuesto definitivo | Puede construirse mientras se afinan algunos endpoints ERP |
| REQ y pendientes de compra | Puede avanzar con items, subfamilias y centros listos |
| PreOC | Puede avanzar cuando exista la base ERP minima y presupuesto |

### 3.4 Bloquea al resto

| Area | Motivo |
|---|---|
| Temporadas `PPTO_COMPRAS` | Sin esto no hay presupuesto de compras |
| Subfamilia funcional | Sin esto no hay clave de presupuesto ni validacion de REQ |
| Maestro de ítems con precio referencial | Sin esto REQ no puede validar precio |
| Centros de costo | Sin esto no hay asignacion operativa ni aprobacion base |
| Proveedores y condiciones de pago | Sin esto PreOC no puede construirse correctamente |
| Dimensiones ERP obligatorias | Sin esto la integracion ERP queda incompleta |
| Modelo definitivo de presupuesto | Sin esto no se puede cerrar la logica de REQ/PreOC |
| Resolucion de aprobador pendiente | Sin esto no hay listado confiable de aprobaciones REQ/PreOC |

## 5. Backlog por bloque

| ID | Bloque | Tarea | Prioridad | Estado actual | Depende de | Responsable sugerido | Observacion tecnica | Resultado esperado |
|---|---|---|---|---|---|---|---|---|
| BC-01 | Temporadas | Reutilizar tabla `temporadas` para `PPTO_COMPRAS` | A | Validado | Ninguna | BE + DB | Reusar maestro existente y no duplicar temporadas. | Temporadas disponibles para presupuesto de compras. |
| BC-02 | Temporadas | Agregar `temporadatipocodigo` y validacion por rango de fechas | A | Listo para integrar | BC-01 | BE + DB | Implementado en `database/tables/01_table_temporadas.sql`, incremental `database/alter_table/05_modulo_compras_bases.sql` y `src/Services/TemporadasService.php`. Pendiente ejecutar SQL en BD destino. | Temporadas clasificadas por tipo y listas para presupuesto. |
| BC-03 | Centros de Costo | Sincronizacion ERP diaria + on-demand | A | Parcial | API ERP | BE + DB | Ya existe documentacion y parte de la logica. | Catalogo local consistente de centros de costo. |
| BC-04 | Centros de Costo | Atributos locales de jefe de CC y jefe tecnico | A | Parcial | BC-03 | BE + FE + DB | Ambos alimentan firmantes default de REQ. `centrocostocod` se usa como `DIMCTC`; no se separa por empresa ERP. | Aprobacion base disponible para REQ y PreOC. |
| BC-05 | Funcionarios | Maestro compartido con carga inicial por Excel | A | Validado | Ninguna | BE + FE + DB | Reutilizable desde Compras y otros modulos. PK funcional Rut validado; funcionario es opcional al crear REQ. | Solicitantes/reporteria disponibles sin forzar usuario. |
| BC-06 | Funcionarios | Asociacion de funcionario a centro de costo | A | Pendiente | BC-05, BC-03 | BE + DB | Campo funcional `funcencos`; para REQ se usara primero la relacion usuario-centro. | Referencia organizacional trazable. |
| BC-07 | Usuarios | Consolidar permisos globales de compra y sincronizacion | A | Parcial | Ninguna | BE + FE + DB | Incluir comprador, aprobador REQ, aprobador PreOC, editar precios, crear item, editar item, sincronizar transacciones ERP, anular PreOC, autorizador fuera de presupuesto y orden. | Permisos claros para comprador, aprobador, sincronizacion y excepciones. |
| BC-08 | Usuarios | Exponer permisos correctos en login/payload | B | Pendiente | BC-07 | BE | Necesario para UI y middleware. | Permisos consumibles por UI y middleware. |
| BC-09 | Items | Sincronizacion diaria del maestro de productos | A | Parcial | API ERP | BE + DB | Ya existe base y endpoints casi cerrados. | Maestro de items vigente para todo Compras. |
| BC-10 | Items | Precio referencial / estandar para validacion operativa | A | Parcial | BC-09 | BE + DB | Clave para validar REQ sin consumir presupuesto. Si el precio/costo es cero, no se permite agregar al REQ. | REQ puede validar precio y bloquear items sin precio. |
| BC-11 | Items | Material/Servicio, uso `LCH/CMB/ALM/BDG` e ingreso local | A | Validado | BC-09 | BE + DB | Material/Servicio se resuelve con `invitemstockeable`: 1 Material, 0 Servicio. Para REQ/PreOC debe ser `invitemcompra = 1`. Item local se marca con `iteminglocal`; ERP manda cuando lo sincronice. Edicion local solo precio cero, uso funcional y activar/desactivar. | Clasificacion funcional lista para compras y contingencias locales. |
| BC-12 | Items | Soporte de familia y subfamilia en `invitems` | A | Parcial | BC-09, BC-13, BC-14 | BE + DB | Necesario para presupuesto y validacion. | Item clasificado para presupuesto y validacion. |
| BC-13 | Familias | Crear o sincronizar maestro de familia | B | Parcial | API ERP o decision local | BE + DB | Ya aparece en analisis consolidado transversal. | Catalogo base para clasificacion transversal. |
| BC-14 | Subfamilias | Crear o sincronizar maestro de subfamilia | A | Parcial | BC-13 | BE + DB | Clave operativa del presupuesto y validacion. | Clave operativa del presupuesto y validacion. |
| BC-15 | Proveedores | Sincronizacion ERP como maestro espejo | A | Validado funcional | API ERP | BE + DB | Misma logica de productos: list + detalle por codigo. El detalle trae categoria fiscal, identificacion tributaria, condiciones de pago, concepto/cuenta proveedor y medio de pago. | Proveedor seleccionable en PreOC. |
| BC-16 | Proveedores | Consulta y exportacion Excel | B | Validado funcional | BC-15 | FE + BE | Pantalla solo consulta y exportar a Excel; no CRUD libre. | Maestro proveedor usable por operacion y auditoria. |
| BC-17 | Condiciones de pago | Sincronizacion catalogo ERP | A | Validado funcional | API ERP | BE + DB | Misma logica: list + detalle por codigo. El detalle incluye tipo, cuentas e items con dias/porcentaje. | Condicion de pago disponible para PreOC. |
| BC-18 | Condiciones de pago | Pre-carga desde proveedor con edicion manual | A | Validado funcional | BC-15, BC-17 | FE + BE | Debe respetar ficha del proveedor y permitir override antes de grabar PreOC. Requiere relacion proveedor-condicion de pago. | PreOC con valor inicial correcto. |
| BC-19 | Fiscalidad | Categorias fiscales e impuestos por proveedor | B | Parcial | API ERP | BE + DB | Requerido para conceptos de OC. | Conceptos de OC generables desde maestro. |
| BC-20 | Fiscalidad | Construccion de conceptos/retenciones para Finnegans | B | Pendiente | BC-19 | BE | Depende del detalle final de conceptos activos. | JSON de OC consistente con ERP. |
| BC-21 | Monedas | Sincronizacion catalogo monedas | C | Parcial | API ERP | BE + DB | Ya se levantó pero no es bloqueante para el negocio. | Catálogo operativo y trazable. |
| BC-22 | Monedas | Mantener CLP como moneda operativa | A | Validado | BC-21 | BE + FE | Moneda única del negocio. | PreOC limitada a moneda de negocio. |
| BC-23 | Ctas contables | Sincronizar cuentas como referencia | C | Parcial | API ERP | BE + DB | Solo referencia y auditoría. | Referencia contable para auditoria. |
| BC-24 | ERP core | Definir workflow fijo de compra | B | Validado | Mapeo ERP | BE | Workflow es valor fijo de integracion; no requiere maestro propio por ahora. | PreOC con workflow correcto sin CRUD adicional. |
| BC-25 | ERP core | Definir provincias/destinos requeridos | B | Parcial | API ERP | BE + DB | Depende del flujo real de OC y destino final. | Integracion completa con OC. |
| BC-26 | ERP core | Confirmar dimensiones ERP obligatorias | A | Validado funcional | Soporte Finnegans | BE + DB | `DIMCTC` sale del centro de costo y `DIMPARFIN` del item. `preocitemsdimensiones` queda operativamente a nivel req-item origen, con `preocitemid` nullable como apoyo si se requiere consulta agrupada. | Integracion no bloqueada por dimensiones faltantes. |
| BC-27 | Presupuesto | Implementar cabecera `PptoCompra` | A | Validado | BC-01, BC-14 | BE + DB | Ya definido en documento definitivo de presupuesto. Debe sumar responsable, administrador y colaborador opcional para firmantes default de PreOC. | Modelo central de presupuesto operativo. |
| BC-28 | Presupuesto | Implementar `PptoCompraMensual` | A | Validado | BC-27 | BE + DB | Base de carga mensual del presupuesto. | Carga mensual del presupuesto. |
| BC-29 | Presupuesto | Implementar `PptoCompraTransacciones` | A | Validado | BC-27 | BE + DB | Debe preservar toda la historia de movimientos. | Libro de movimientos trazable. |
| BC-30 | Presupuesto | Implementar `PptoCompraTransaccionesTipo` | A | Validado | BC-29 | BE + DB | Tipos codificados, no autoincrementales. | Movimientos codificados por semantica. |
| BC-31 | Presupuesto | Calculo de saldos, consumos y reproyeccion | A | Validado | BC-27 a BC-30 | BE | Formula ya cerrada funcionalmente. | Reglas financieras consolidadas. |
| BC-32 | Presupuesto | Ajustes manuales con motivo obligatorio | A | Validado | BC-29, BC-30 | BE + FE | Ajustes positivos/negativos son transacciones reales. | Operacion financiera controlada. |
| BC-33 | Presupuesto | Traspasos salida/entrada enlazados | B | Validado | BC-29, BC-30 | BE + FE | Implementado como transaccion atomica con salida/entrada y grupo comun. | Cambio de presupuesto trazable. |
| BC-33B | Presupuesto | Carga masiva inicial por Excel | B | Pendiente | BC-27, BC-28, BC-31 | BE + FE | La pantalla pregunta temporada; el Excel debe traer Subfamilia Codigo, Centro Costo Codigo, Anio, Mes, Monto y Observacion Mes opcional. Debe mostrar analisis previo con total cargado, resumen por subfamilia, reporte por centro/subfamilia, detalle completo y modal de confirmacion por temporada. | Presupuestos base cargados masivamente con validacion previa. |
| BC-34 | REQ | Validacion informativa contra saldo disponible | A | Validado | BC-27 a BC-31, BC-09, BC-14 | BE + FE | REQ no genera movimientos ni bloquea por saldo. Si hay deficit, marca advertencia y agrega autorizadores fuera de presupuesto. | REQ advierte sobre deficit sin generar movimientos. |
| BC-35 | REQ | Snapshot del presupuesto validado | A | Pendiente | BC-34 | BE | Debe guardar saldo disponible actual, otros REQ en curso, REQ aprobados pendientes de compra, monto de este REQ, saldo proyectado, porcentaje usado y deficit. | Evidencia historica y analisis visible. |
| BC-36 | REQ | Bloqueo de item sin precio referencial | A | Pendiente | BC-10 | FE + BE | Si no hay precio, el item no entra al REQ. | REQ consistente con maestro de items. |
| BC-37 | PreOC | Resolucion automatica de presupuesto por `preocfechaoc`/subfamilia/CC | A | Validado | BC-27 a BC-31, BC-14 | BE | El usuario no selecciona presupuesto manualmente. La fecha de OC define temporada y envio ERP. | El usuario no selecciona presupuesto manualmente. |
| BC-38 | PreOC | Reserva provisional al pasar de `BRR` a `PND` | A | Validado | BC-29, BC-30, BC-37 | BE | Guardar en borrador no reserva. Si vuelve de `PND` a `BRR` antes de aprobaciones, reversa/libera reserva. | Compromiso presupuestario inicial controlado. |
| BC-39 | PreOC | Confirmacion de reserva al aprobar | A | Validado | BC-38 | BE | La aprobacion convierte reserva en compromiso firme. | Consumo confirmado trazable. |
| BC-40 | PreOC | Reversa positiva al rechazar/anular confirmada | A | Validado | BC-39 | BE | Reversa solo cuando ya hubo confirmacion. | Reversion sin perder historia. |
| BC-41 | PreOC | Eliminacion de linea provisional sin aprobacion | A | Validado | BC-38 | FE + BE | Si sigue en curso y sin aprobaciones, se borra la provisional. | Edicion limpia antes de primera aprobacion. |
| BC-42 | PreOC | Bloqueo de edicion despues de la primera aprobacion | A | Validado | BC-39 | FE + BE | A partir de la primera aprobacion no hay edicion. | Regla de control cumplida. |
| BC-43 | Integracion | Generar JSON y POST a Finnegans | B | Parcial | BC-15, BC-17, BC-19, BC-22, BC-24, BC-25, BC-26 | BE | Parte del mapping esta clara; faltan cierres finos. | OC enviada correctamente al ERP. |
| BC-44 | Integracion | Logs, reintento y errores de sincronizacion | B | Parcial | BC-43 | BE + FE | Debe conservar evidencia de integracion. | Trazabilidad operativa completa. |
| BC-45 | Usuarios-Centros | Crear relacion usuario-centro con default y estado | A | Validado funcional | BC-03, BC-07 | BE + FE + DB | `usuarioscentroscosto` debe permitir asociar, anular por estado y cambiar default. Solo un default activo por usuario. Si un solicitante no tiene centros, crear REQ debe mostrar error y derivar a Administracion. | Centro operativo resoluble para REQ y filtros. |
| BC-46 | Aprobadores | Tabla `aprobadoresperiodoinactividad` y LOG | A | Validado funcional | BC-07 | BE + FE + DB | No se eliminan registros; solo inactivan. Debe registrar reemplazo y motivo. Aplica a REQ y PreOC. | Ausencias trazables y reutilizables. |
| BC-47 | Aprobaciones | Resolver siguiente aprobador pendiente REQ/PreOC | A | Validado funcional | BC-07, BC-46 | BE | Validar usuario vigente, inactividad y reemplazo. Actualiza `reqaprobadoridpnd` o `preocaprobadoridpnd`; si no quedan aprobadores validos, aprueba. | Listados pendientes eficientes y consistentes. |
| BC-48 | REQ | Firmantes default, manuales y fuera de presupuesto | A | Validado funcional | BC-04, BC-07, BC-34 | BE + FE | Jefe centro y jefe tecnico default; manuales activos con permiso REQ; fuera de presupuesto se agregan internamente al final, no removibles ni reordenables. | Lista de firmantes REQ completa y controlada. |
| BC-49 | REQ | Detalle con subfamilia y ultimo requerimiento | B | Validado funcional | BC-09, BC-14, BC-34 | BE + FE + DB | Guardar subfamilia para joins de snapshot y fecha/cantidad ultimo REQ por centro-item como dato informativo. | Visualizacion REQ con contexto historico. |
| BC-50 | REQ | Historial de pendientes aprobados y anulaciones | A | Validado funcional | BC-34 | BE + FE + DB | `reqaprobadoshistorial` debe guardar cantidad pendiente antes del evento. Anulacion solo sobre pendiente, parcial o total. | Trazabilidad de compras y anulaciones. |
| BC-51 | PreOC | Estructura origen y agrupacion de items | A | Validado funcional | BC-34, BC-37 | BE + FE + DB | Separar `preocdetallereqitems` como origen req-item y `preocitems` como item agrupado para precio/totales. | PreOC armable desde multiples requerimientos. |
| BC-52 | PreOC | Impuestos por item agrupado | A | Pendiente confirmacion Finnegans | BC-19, BC-51 | BE + DB | Tabla `preocimptos`; un item puede tener mas de un impuesto. Falta confirmar IDs de impuestos con soporte. | Totales PreOC y payload ERP correctos. |
| BC-53 | PreOC | Resumen presupuestario por PreOC | A | Validado funcional | BC-37, BC-38 | BE + FE + DB | `preocpptoresumen` se relaciona por `preocid` + `pptocompraid`; apoyo/consulta rapida, no libro oficial. | Analisis presupuestario rapido en PreOC. |
| BC-54 | PreOC | Estado ERP separado y anulacion local | A | Validado funcional | BC-43, BC-44 | BE + FE + DB | Estados ERP: sin estado, `SNC`, `ERR`. Si una PreOC sincronizada se anula localmente, estado principal `ANL` y ERP queda `SNC`. Guardar error visible en cabecera cuando aplique. | Auditoria clara de aprobacion y sincronizacion. |

## 6. Orden sugerido de trabajo

### Sprint 1 - Habilitadores criticos

1. Temporadas.
2. Centros de costo.
3. Usuarios y permisos.
4. Funcionarios.
5. Items y subfamilias.

### Sprint 2 - Bases ERP de PreOC

1. Proveedores.
2. Condiciones de pago.
3. Categorias fiscales e impuestos.
4. Monedas.
5. Cuentas contables.
6. Workflows, provincias y dimensiones ERP.

### Sprint 3 - Presupuesto

1. Cabecera.
2. Detalle mensual.
3. Transacciones.
4. Tipos de transaccion.
5. Calculos y reglas.

### Sprint 4 - Flujo operativo

1. REQ validacion informativa.
2. Pendientes de compra.
3. PreOC reserva/confirmacion/reversa.
4. Integracion con Finnegans.

## 7. Bloqueadores

- No avanzar con PreOC si no existen proveedores, condiciones de pago, monedas o dimensiones requeridas.
- No avanzar con REQ si no existe maestro de items con precio referencial.
- No avanzar con presupuesto si no existe temporada con tipo `PPTO_COMPRAS`.
- No permitir compra sin subfamilia y centro de costo resolubles.
- No activar listados de aprobacion sin resolver `reqaprobadoridpnd`/`preocaprobadoridpnd` desde cabecera.
- No cerrar integracion PreOC hasta confirmar impuestos y estructura final de `preocaprobadoresxmonto`.

## 8. Estado sugerido

- `Pendiente`
- `En curso`
- `Bloqueado por dependencia`
- `Listo para integrar`
- `Completado`
