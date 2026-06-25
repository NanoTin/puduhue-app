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
| Presupuesto definitivo | Identificado |
| REQ validacion informativa | Identificado |
| PreOC reserva / confirmacion / reversa | Identificado |

### 3.2 Por confirmar o completar

| Area | Estado |
|---|---|
| Algunos endpoints restantes de maestros | Pendiente |
| Regla exacta de DIMPARFIN | Pendiente |
| Confirmacion final de DIMCTC | Pendiente |
| Algunos detalles del payload ERP | Pendiente |
| Casos borde de traspasos / reversas | Pendiente parcial |

### 3.3 Puede avanzar en paralelo

| Area | Observacion |
|---|---|
| Centros de costo y usuarios | Independientes del presupuesto |
| Funcionarios y permisos | Puede desarrollarse en paralelo con maestros ERP |
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

## 5. Backlog por bloque

| ID | Bloque | Tarea | Prioridad | Estado actual | Depende de | Responsable sugerido | Observacion tecnica | Resultado esperado |
|---|---|---|---|---|---|---|---|---|
| BC-01 | Temporadas | Reutilizar tabla `temporadas` para `PPTO_COMPRAS` | A | Validado | Ninguna | BE + DB | Reusar maestro existente y no duplicar temporadas. | Temporadas disponibles para presupuesto de compras. |
| BC-02 | Temporadas | Agregar `temporadatipocodigo` y validacion por rango de fechas | A | Listo para integrar | BC-01 | BE + DB | Implementado en `database/tables/01_table_temporadas.sql`, incremental `database/alter_table/05_modulo_compras_bases.sql` y `src/Services/TemporadasService.php`. Pendiente ejecutar SQL en BD destino. | Temporadas clasificadas por tipo y listas para presupuesto. |
| BC-03 | Centros de Costo | Sincronizacion ERP diaria + on-demand | A | Parcial | API ERP | BE + DB | Ya existe documentacion y parte de la logica. | Catalogo local consistente de centros de costo. |
| BC-04 | Centros de Costo | Atributo local de jefe de CC y reglas de aprobacion | A | Parcial | BC-03 | BE + FE | Punto de aprobacion base para REQ y PreOC. | Aprobacion base disponible para REQ y PreOC. |
| BC-05 | Funcionarios | Maestro compartido con carga inicial por Excel | A | Validado | Ninguna | BE + FE + DB | Reutilizable desde Compras y otros modulos. | Solicitantes/aprobadores disponibles. |
| BC-06 | Funcionarios | Desactivacion automatica si no vienen en la fuente | A | Pendiente | BC-05 | BE | Mantiene sincronizacion coherente con origen. | Sincronizacion coherente con origen. |
| BC-07 | Usuarios | Consolidar permisos globales de compra y sincronizacion | A | Parcial | Ninguna | BE + FE | Hay permisos levantados, falta consolidacion final. | Permisos claros para comprador, aprobador y sincronizacion. |
| BC-08 | Usuarios | Exponer permisos correctos en login/payload | B | Pendiente | BC-07 | BE | Necesario para UI y middleware. | Permisos consumibles por UI y middleware. |
| BC-09 | Items | Sincronizacion diaria del maestro de productos | A | Parcial | API ERP | BE + DB | Ya existe base y endpoints casi cerrados. | Maestro de items vigente para todo Compras. |
| BC-10 | Items | Precio referencial / estandar para validacion operativa | A | Parcial | BC-09 | BE + DB | Clave para validar REQ sin consumir presupuesto. | REQ puede validar precio y bloquear items sin precio. |
| BC-11 | Items | Campos Material/Servicio y uso `LCH/CMB/ALM/BDG` | A | Validado | BC-09 | BE + DB | Ya levantado funcionalmente en documentacion. | Clasificacion funcional lista para compras. |
| BC-12 | Items | Soporte de familia y subfamilia en `invitems` | A | Parcial | BC-09, BC-13, BC-14 | BE + DB | Necesario para presupuesto y validacion. | Item clasificado para presupuesto y validacion. |
| BC-13 | Familias | Crear o sincronizar maestro de familia | B | Parcial | API ERP o decision local | BE + DB | Ya aparece en analisis consolidado transversal. | Catalogo base para clasificacion transversal. |
| BC-14 | Subfamilias | Crear o sincronizar maestro de subfamilia | A | Parcial | BC-13 | BE + DB | Clave operativa del presupuesto y validacion. | Clave operativa del presupuesto y validacion. |
| BC-15 | Proveedores | Sincronizacion ERP como maestro espejo | A | Parcial | API ERP | BE + DB | Existe endpoint identificado, falta cierre total del modelo. | Proveedor seleccionable en PreOC. |
| BC-16 | Proveedores | Busqueda/autocomplete con formato real del codigo ERP | B | Pendiente | BC-15 | FE + BE | El formato real del codigo puede variar. | Seleccion segura y usable del proveedor. |
| BC-17 | Condiciones de pago | Sincronizacion catalogo ERP | A | Parcial | API ERP | BE + DB | Maestro requerido para PreOC. | Condicion de pago disponible para PreOC. |
| BC-18 | Condiciones de pago | Pre-carga desde proveedor con edicion manual | A | Pendiente | BC-15, BC-17 | FE + BE | Debe respetar ficha del proveedor y permitir override. | PreOC con valor inicial correcto. |
| BC-19 | Fiscalidad | Categorias fiscales e impuestos por proveedor | B | Parcial | API ERP | BE + DB | Requerido para conceptos de OC. | Conceptos de OC generables desde maestro. |
| BC-20 | Fiscalidad | Construccion de conceptos/retenciones para Finnegans | B | Pendiente | BC-19 | BE | Depende del detalle final de conceptos activos. | JSON de OC consistente con ERP. |
| BC-21 | Monedas | Sincronizacion catalogo monedas | C | Parcial | API ERP | BE + DB | Ya se levantó pero no es bloqueante para el negocio. | Catálogo operativo y trazable. |
| BC-22 | Monedas | Mantener CLP como moneda operativa | A | Validado | BC-21 | BE + FE | Moneda única del negocio. | PreOC limitada a moneda de negocio. |
| BC-23 | Ctas contables | Sincronizar cuentas como referencia | C | Parcial | API ERP | BE + DB | Solo referencia y auditoría. | Referencia contable para auditoria. |
| BC-24 | ERP core | Definir workflows de compra | B | Parcial | API ERP | BE + DB | Identificado en ejemplos de OC. | PreOC con workflow correcto. |
| BC-25 | ERP core | Definir provincias/destinos requeridos | B | Parcial | API ERP | BE + DB | Depende del flujo real de OC y destino final. | Integracion completa con OC. |
| BC-26 | ERP core | Confirmar dimensiones ERP obligatorias | A | Pendiente | Soporte Finnegans | BE + DB | Sigue siendo uno de los principales bloqueos. | Integracion no bloqueada por dimensiones faltantes. |
| BC-27 | Presupuesto | Implementar cabecera `PptoCompra` | A | Validado | BC-01, BC-14 | BE + DB | Ya definido en documento definitivo de presupuesto. | Modelo central de presupuesto operativo. |
| BC-28 | Presupuesto | Implementar `PptoCompraMensual` | A | Validado | BC-27 | BE + DB | Base de carga mensual del presupuesto. | Carga mensual del presupuesto. |
| BC-29 | Presupuesto | Implementar `PptoCompraTransacciones` | A | Validado | BC-27 | BE + DB | Debe preservar toda la historia de movimientos. | Libro de movimientos trazable. |
| BC-30 | Presupuesto | Implementar `PptoCompraTransaccionesTipo` | A | Validado | BC-29 | BE + DB | Tipos codificados, no autoincrementales. | Movimientos codificados por semantica. |
| BC-31 | Presupuesto | Calculo de saldos, consumos y reproyeccion | A | Validado | BC-27 a BC-30 | BE | Formula ya cerrada funcionalmente. | Reglas financieras consolidadas. |
| BC-32 | Presupuesto | Ajustes manuales con motivo obligatorio | A | Validado | BC-29, BC-30 | BE + FE | Ajustes positivos/negativos son transacciones reales. | Operacion financiera controlada. |
| BC-33 | Presupuesto | Traspasos salida/entrada enlazados | B | Validado | BC-29, BC-30 | BE + FE | Implementado como transaccion atomica con salida/entrada y grupo comun. | Cambio de presupuesto trazable. |
| BC-33B | Presupuesto | Carga masiva inicial por Excel | B | Pendiente | BC-27, BC-28, BC-31 | BE + FE | La pantalla pregunta temporada; el Excel debe traer Subfamilia Codigo, Centro Costo Codigo, Anio, Mes, Monto y Observacion Mes opcional. Debe mostrar analisis previo con total cargado, resumen por subfamilia, reporte por centro/subfamilia, detalle completo y modal de confirmacion por temporada. | Presupuestos base cargados masivamente con validacion previa. |
| BC-34 | REQ | Validacion informativa contra saldo disponible | A | Validado | BC-27 a BC-31, BC-09, BC-14 | BE + FE | REQ no genera movimientos; solo valida. | REQ bloquea sobreconsumo sin generar movimientos. |
| BC-35 | REQ | Snapshot del presupuesto validado | A | Pendiente | BC-34 | BE | Necesario para evidencia historica. | Evidencia historica de validacion. |
| BC-36 | REQ | Bloqueo de item sin precio referencial | A | Pendiente | BC-10 | FE + BE | Si no hay precio, el item no entra al REQ. | REQ consistente con maestro de items. |
| BC-37 | PreOC | Resolucion automatica de presupuesto por fecha/subfamilia/CC | A | Validado | BC-27 a BC-31, BC-14 | BE | El usuario no selecciona presupuesto manualmente. | El usuario no selecciona presupuesto manualmente. |
| BC-38 | PreOC | Reserva provisional al crear PreOC en curso | A | Validado | BC-29, BC-30, BC-37 | BE | Compromiso presupuestario inicial. | Compromiso presupuestario inicial. |
| BC-39 | PreOC | Confirmacion de reserva al aprobar | A | Validado | BC-38 | BE | La aprobacion convierte reserva en compromiso firme. | Consumo confirmado trazable. |
| BC-40 | PreOC | Reversa positiva al rechazar/anular confirmada | A | Validado | BC-39 | BE | Reversa solo cuando ya hubo confirmacion. | Reversion sin perder historia. |
| BC-41 | PreOC | Eliminacion de linea provisional sin aprobacion | A | Validado | BC-38 | FE + BE | Si sigue en curso y sin aprobaciones, se borra la provisional. | Edicion limpia antes de primera aprobacion. |
| BC-42 | PreOC | Bloqueo de edicion despues de la primera aprobacion | A | Validado | BC-39 | FE + BE | A partir de la primera aprobacion no hay edicion. | Regla de control cumplida. |
| BC-43 | Integracion | Generar JSON y POST a Finnegans | B | Parcial | BC-15, BC-17, BC-19, BC-22, BC-24, BC-25, BC-26 | BE | Parte del mapping esta clara; faltan cierres finos. | OC enviada correctamente al ERP. |
| BC-44 | Integracion | Logs, reintento y errores de sincronizacion | B | Parcial | BC-43 | BE + FE | Debe conservar evidencia de integracion. | Trazabilidad operativa completa. |

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

## 8. Estado sugerido

- `Pendiente`
- `En curso`
- `Bloqueado por dependencia`
- `Listo para integrar`
- `Completado`
