# Propuesta de Horas-Hombre (HH) — Módulo de Compras
**Fecha de Análisis:** 14 de Abril de 2026
**Basado en:** Respuestas del cliente (Discovery Módulo de Compras), ejemplos JSON de OC Finnegans, y levantamiento técnico consolidado en:
- `docs/modulo_compras_discovery.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/modulo_compras_maestros_erp.md`

---

> Este documento consolida el levantamiento técnico del Módulo de Compras, organizado por componentes para facilitar la estimación. El módulo se integra a Puduhue App Web y cubre el ciclo completo desde la Solicitud Interna hasta la generación de la Orden de Compra en Finnegans.

---

## Componente 1 — Maestros Base y Sincronización ERP

**Descripción:** Creación de todos los **maestros espejo** requeridos por el módulo (datos sincronizados desde Finnegans). Incluye las tablas, SPs de sincronización, tareas cron y botones On-Demand en las pantallas de administración.

**Maestros a implementar:**

| Maestro | Tabla(s) | Estrategia sync |
|---------|---------|----------------|
| Centros de Costo | `centroscosto` | Cron diario + On-Demand |
| Dimensiones ERP | `erpdimensiones`, `erpdimensionvalores` | Cron diario + On-Demand |
| Proveedores | `proveedores` | Cron diario + On-Demand |
| Condiciones de Pago | `condicionespago` | Cron diario + On-Demand |
| Categorías Fiscales / Impuestos | `erpcategoriasfiscales`, `erpcatfiscalconceptos` | Cron diario + On-Demand |
| Monedas | `erpmonedas` | Cron diario + On-Demand |
| Cuentas Contables | `erpcuentas` | Cron diario (referencia) |
| Workflows de Compra | `erpworkflows` | Cron diario |
| Provincias / Establecimientos | `erpprovinciasdestino` | Cron diario + On-Demand |

**Incluye también:**
- Mejoras al Maestro de Productos (`invitems`): nuevos atributos de tipo, módulo de uso, compra/venta/inventario.
- Migración del atributo `LECHE = SI/NO` al nuevo código de módulo (`LCH`, `ALM`, etc.).
- Maestro de Funcionarios (`funcionarios`): carga inicial desde Excel + lógica de desactivación.

**Estimación HH:** `40 – 55` hrs.

---

## Componente 2 — Maestro de Centros de Costo y Usuarios

**Descripción:** Pantallas de administración para los nuevos maestros con atributos locales editables (no provenientes del ERP).

**Pantallas:**
- Maestro de Centros de Costo: configurar jefe de CC, asociar usuarios.
- Maestro de Funcionarios: CRUD local, carga masiva Excel, desactivación automática.
- Gestión de Inactividad de Aprobadores: registrar reemplazante + período (vacaciones, licencia, permiso).
- Maestro de Perfiles de Usuarios: nuevos atributos `autorizareq`, `editarprecios`, `comprador`, `permitecreareditar`.

**Estimación HH:** `25 – 35` hrs.

---

## Componente 3 — Módulo de Presupuesto de Compra

**Descripción:** Módulo para la gestión del presupuesto que controla el gasto en Pre OC. Incluye estructura jerárquica de 3 niveles, kardex de movimientos y control de saldo disponible.

**Incluye:**
- Maestro de Clasificación / Sub-clasificación / Presupuesto (CRUD, solo Gerencia de Adm. y Finanzas).
- Registrar saldo inicial y ajustes manuales con kardex de movimientos (tipo extracto bancario).
- Reporte de ejecución presupuestaria (consulta para compradores): presupuesto vs ejecutado vs saldo.
- Validación bloqueante al momento de enviar una Pre OC a aprobación (sin saldo → no puede avanzar).
- Movimientos automáticos al aprobar, rechazar o anular una Pre OC (reserva / devolución).

**Estimación HH:** `30 – 45` hrs.

---

## Componente 4 — Módulo de Requerimiento de Compra (REQ)

**Descripción:** Módulo transaccional principal. Permite crear, enviar, aprobar y gestionar solicitudes internas de compra de materiales o servicios.

**Incluye:**
- Pantalla de creación/edición de REQ: tipo (Material/Servicio), solicitante (Maestro de Funcionarios), CC, ítems, observación.
- Gestión dinámica de firmantes: jefe de CC por defecto, firmantes adicionales, drag & drop de orden, lógica de inactividad/reemplazo.
- Flujo completo de estados: BRR → PND → EDT → APR / RCH / CSO / ANL / VNC.
- Pantalla de firma/aprobación para usuarios con `autorizareq = 1`.
- Edición en estado PND (→ EDT) con bloqueo de firmas durante edición.
- Historial de seguimiento del REQ (tipo tracking): hitos fecha/hora de cada cambio de estado.
- Log de auditoría completo.
- Listado de REQ con filtros por estado, fecha, solicitante, tipo.

**Estimación HH:** `55 – 75` hrs.

---

## Componente 5 — Módulo de Pendientes de Compra (Intermediario REQ → PreOC)

**Descripción:** Pantalla que permite al comprador gestionar los requerimientos aprobados antes de armar la Pre OC. Incluye cambio de ítems con trazabilidad y métricas.

**Incluye:**
- Pantalla de líneas pendientes de compra (`reqaprobados`): agrupadas por REQ aprobado, con saldo pendiente por comprar.
- Capacidad del comprador de cambiar un ítem (con validación de unicidad en el REQ y registro del cambio en `reqaprobadoscambios`).
- Historial de cambios por REQ (visible para el solicitante original).
- Métricas: frecuencia de cambios de ítems, tiempo desde creación hasta aprobación del REQ, tiempo desde aprobación hasta generación de Pre OC.

**Estimación HH:** `20 – 30` hrs.

---

## Componente 6 — Módulo de Pre Orden de Compra (PreOC)

**Descripción:** Módulo transaccional que consolida requerimientos aprobados y genera la OC en Finnegans. Es el núcleo del módulo de compras.

**Incluye:**
- Pantalla de creación de Pre OC: selección de proveedor (con carga automática de condición de pago e impuestos), líneas desde pendientes de compra, cantidades parciales.
- Validación de saldo presupuestario antes de enviar.
- Generación automática de lista de firmantes (Responsable/Administrador/Colaborador del Presupuesto + reglas por monto + manuales), con drag & drop de orden y deduplicación.
- Flujo completo de estados: BRR → PND → APR / RCH / CSO / ANL → ERP / ERR.
- Pantalla de firma/aprobación para aprobadores de Pre OC.
- Pantalla de consulta de Pre OC (vista por comprador, con opción de ver otras).
- Log de auditoría completo.

**Estimación HH:** `65 – 85` hrs.

---

## Componente 7 — Integración con Finnegans (Creación de OC)

**Descripción:** Integración técnica con el ERP Finnegans para enviar la Pre OC aprobada como una Orden de Compra real. Es el componente de mayor complejidad técnica y dependencia externa.

**Incluye:**
- SP de construcción del JSON de la OC: mapeo de todos los campos de la Pre OC al formato esperado por Finnegans (ítems, dimensiones DIMPARFIN/DIMCTC, conceptos/impuestos calculados desde la categoría fiscal del proveedor, workflow, moneda, provincia destino).
- Lógica diferenciada para OC Material (`TransaccionSubtipoCodigo: "OC"`) y OC Servicio (`"OCSS"`).
- POST al endpoint de Finnegans con manejo de respuesta: éxito → estado ERP + guardar número de documento; error → estado ERR + guardar respuesta para debugging.
- Pantalla de reintento manual en caso de error de integración.
- **Coordinación activa con soporte técnico de Finnegans** para:
  - Confirmar estructura definitiva de campos obligatorios del POST.
  - Definir cómo se obtiene el código `DIMPARFIN` por línea.
  - Validar si `DIMCTC` es el mismo catálogo que Centros de Costo o tabla independiente.
  - Confirmar comportamiento de `WorkflowCodigo` y `ProvinciaDestino`.

> [!WARNING]
> Este componente tiene una **dependencia crítica** con el soporte de Finnegans.
> El tiempo de respuesta e iteraciones con el equipo del ERP puede afectar el plazo de entrega.
> Se estima un período de pruebas y ajustes de al menos 2–3 rondas de validación.

**Estimación HH:** `35 – 55` hrs. *(Incluye pruebas de integración, ajustes por respuesta de Finnegans y documentación del mapeo final.)*

---

## Resumen de Estimación

| # | Componente | HH Mínimo | HH Máximo |
|---|------------|:---------:|:---------:|
| 1 | Maestros Base y Sincronización ERP | 40 | 55 |
| 2 | Maestro de CC, Funcionarios y Perfiles | 25 | 35 |
| 3 | Módulo de Presupuesto de Compra | 30 | 45 |
| 4 | Módulo REQ (Requerimiento de Compra) | 55 | 75 |
| 5 | Pendientes de Compra (REQ → PreOC) | 20 | 30 |
| 6 | Módulo de Pre Orden de Compra (PreOC) | 65 | 85 |
| 7 | Integración con Finnegans | 35 | 55 |
| | **TOTAL ESTIMADO** | **270** | **380** |

> [!NOTE]
> El rango refleja la variabilidad natural de un proyecto de esta envergadura: complejidad de las reglas de negocio confirmadas, tiempo de respuesta de Finnegans, y ajustes durante el desarrollo iterativo.
> No incluye: Testing QA formal, capacitación de usuarios, documentación técnica de entrega, ni gestión de proyecto.

---

## Próximos Pasos

1. Aprobar estimación y definir prioridad de componentes (¿se desarrolla todo junto o por fases?).
2. Confirmar con soporte Finnegans los 7 puntos pendientes de la integración (ver `modulo_compras_maestros_erp.md` §9).
3. Definir responsable de presupuesto en el lado del cliente (¿quién es el Responsable/Administrador/Colaborador del presupuesto en `pptocompra`?).
4. Iniciar desarrollo comenzando por Componente 1 (maestros y sincronización ERP) como base.

---

---

# Email para el Cliente

---

**Para:** Alexis
**Asunto:** Propuesta Módulo de Compras — Puduhue App

---

Hola Alexis, junto con saludar,

Luego de las consultas que realizamos y las respuestas que nos compartiste, hemos concluido el levantamiento del **Módulo de Compras** para Puduhue App. Quiero presentarte el alcance y la estimación de horas para que podamos avanzar con tu aprobación.

---

## ¿Qué problema resuelve este módulo?

Hoy, el proceso de compras de la empresa opera con **dos vacíos de control importantes**:

**1. Sin control sobre quién autoriza una Orden de Compra en el ERP**

Las Órdenes de Compra se ingresan directamente en Finnegans sin un flujo formal de autorización interno. No existe un mecanismo que obligue a que una compra pase por la aprobación de las personas correctas antes de llegar al ERP. Esto expone a la empresa a compras no autorizadas, sin visibilidad del jefe del área solicitante ni de la gerencia.

**2. Sin control presupuestario previo al gasto**

No hay un sistema que valide si existe saldo disponible antes de comprometer una compra. El gasto ocurre, y el control se hace —en el mejor caso— después del hecho. Esto dificulta la planificación y el cumplimiento de los presupuestos por área.

---

## ¿Qué propone el nuevo Módulo de Compras?

El módulo crea un **ciclo completo y controlado** desde que nace la necesidad de compra hasta que se genera la OC en Finnegans:

```
Requerimiento Interno  →  Aprobación por firmantes  →  Pre Orden de Compra  →  Aprobación + Control de Presupuesto  →  OC en Finnegans (automática)
```

Los puntos clave del diseño son:

- **Requerimientos separados** por tipo (Materiales / Servicios), con solicitante asignado y Centro de Costo.
- **Flujo de aprobación configurable** por Centro de Costo: el jefe del área es el aprobador por defecto, con posibilidad de agregar firmantes adicionales y reordenarlos. Si un aprobador está de vacaciones o con licencia, su reemplazo queda asignado automáticamente.
- **Pre Orden de Compra** generada por el equipo de compras (secretarias), que puede consolidar múltiples requerimientos y realizar compras parciales.
- **Control presupuestario bloqueante**: si no hay saldo disponible en el presupuesto asignado, el sistema no permite avanzar con la Pre OC. El presupuesto se gestiona en una estructura jerárquica de 3 niveles, con kardex de movimientos (similar a una cuenta corriente).
- **Integración automática con Finnegans**: una vez que la Pre OC es aprobada por todos los firmantes, el sistema genera y envía automáticamente la Orden de Compra al ERP, incluyendo todos los datos de impuestos, dimensiones contables y datos del proveedor.
- **Trazabilidad completa**: desde que el solicitante crea el requerimiento hasta que la OC queda registrada en Finnegans, cada paso queda registrado con fecha, hora y usuario responsable.

---

## Estimación de Horas-Hombre

El módulo tiene **7 componentes** de desarrollo, que se desglosan a continuación:

| Componente | HH Estimadas |
|------------|:------------:|
| Sincronización de maestros ERP (proveedores, CC, impuestos, etc.) | 40 – 55 hrs |
| Maestro de Funcionarios, Centros de Costo y perfiles de usuario | 25 – 35 hrs |
| Módulo de Presupuesto de Compra (gestión y control) | 30 – 45 hrs |
| Módulo de Requerimiento Interno (REQ) | 55 – 75 hrs |
| Gestión de Pendientes de Compra (REQ → Pre OC) | 20 – 30 hrs |
| Módulo de Pre Orden de Compra (PreOC) | 65 – 85 hrs |
| **Integración con Finnegans** (creación automática de OC) | 35 – 55 hrs |
| **TOTAL** | **270 – 380 hrs** |

---

## Una nota importante sobre la integración con Finnegans

La integración con el ERP es el componente más complejo del proyecto y tiene una **dependencia directa con el soporte técnico de Finnegans**. Para que el sistema pueda generar una OC correctamente en su plataforma, debemos coordinar con ellos la validación de varios puntos técnicos: campos obligatorios del API, codificación de dimensiones contables (partidas financieras), comportamiento de impuestos por tipo de proveedor, entre otros.

Este proceso de coordinación e iteración con Finnegans puede afectar los plazos de entrega de este componente en particular, por lo que recomendamos iniciar ese contacto en paralelo con el desarrollo de los módulos anteriores.

---

## Una reflexión sobre el costo de las alternativas

Quiero compartirte dos puntos que creo vale la pena tener en cuenta al evaluar esta propuesta.

**¿Qué costaría implementar esto directamente en Finnegans?**

Un módulo de esta naturaleza —flujo de aprobaciones, control presupuestario, reglas de negocio personalizadas— implementado directamente dentro del ERP por el equipo de Finnegans o un partner certificado supone un desarrollo a medida sobre su plataforma, que generalmente implica costos significativamente más altos, menor flexibilidad para ajustar reglas de negocio propias, y tiempos de respuesta más lentos ante cambios futuros. Todo en un entorno que la empresa no controla directamente. La solución que proponemos vive dentro de Puduhue App —tu plataforma— y se conecta con Finnegans solo para lo que el ERP debe hacer: registrar la Orden de Compra final.

**El costo del control manual actual**

Hoy, el proceso de compras se sostiene en parte con Excel, correos, coordinaciones manuales y revisiones posteriores al gasto. Ese trabajo tiene un costo real: horas de personas capacitadas destinadas a tareas que un sistema puede hacer en segundos, con el riesgo adicional de errores, información desactualizada y ausencia de trazabilidad. Cada hora que un jefe de área, una secretaria o un miembro de finanzas dedica a hacer seguimiento de una compra o cuadrar un presupuesto en una planilla, es una hora que no se está dedicando a la gestión del negocio. De más está decir que ese costo —difuso pero real— se acumula mes a mes.

La inversión en este módulo reemplaza ese esfuerzo por un proceso estructurado, auditable y conectado al ERP. El retorno no es solo en eficiencia: es en control real del gasto y en reducir el riesgo operacional.

---

Me pongo a disposición para revisar esta propuesta en detalle cuando lo estimes conveniente, o si necesitas ajustar el alcance por fases.

Quedo atento,
**[Firma]**
