# Propuesta de Horas-Hombre (HH) — Módulo de Combustible
**Fecha de análisis:** 14 de Abril de 2026
**Basado en:**
- `docs/modulo_combustible_discovery.md`
- requerimiento del cliente
- Excel histórico `Registro carga de petróleo Puduhue(1-1498).xlsx`
- documentación existente de arquitectura e integración Finnegans

---

> Este documento cuantifica la implementación del nuevo Módulo de Combustible dentro de Puduhue App Web, incluyendo maestros, transacción operativa, permisos y sincronización con Finnegans reutilizando el patrón de consumo de productos.

> [!NOTE]
> Los componentes transversales compartidos con otros módulos deben estimarse por separado para evitar duplicación de HH. Ver `docs/propuesta_hh_componentes_transversales.md`.

---

## Componente 1 — Maestros Base del Módulo

**Descripción:** Construcción o reutilización de los maestros necesarios para soportar el módulo.

**Incluye:**
- Maestro Tipo de Vehículo.
- Maestro de Vehículos.
- Uso del Maestro de Funcionarios existente o referencia al proyecto transversal si requiere creación/normalización.
- Asociación de vehículo con tipo de combustible, unidad de medida y responsable.
- Columna `KM_Horas inicial` para arranque operacional.
- Asociación del vehículo con producto combustible y su código ERP.
- Consumo del Maestro de Ítems existente para asociar producto combustible; cualquier ajuste estructural de `invitems` se estima por separado como componente transversal.
- Carga inicial/manual de datos base.

**Estimación HH:** `18 – 26` hrs.

---

## Componente 2 — Transacción Consumo de Combustible

**Descripción:** Desarrollo del CRUD transaccional del módulo, con selección por Fundo/Bodega, buscador de vehículo y cálculo de datos operativos.

**Incluye:**
- Pantalla de listado con filtros.
- Pantalla crear / editar / visualizar / anular.
- Búsqueda de vehículo con carga automática de datos asociados.
- Guardado de snapshot histórico del vehículo.
- Cálculo de valor anterior desde transacción previa o desde `KM_Horas inicial`.
- Cálculo de rendimiento KM/L cuando aplique.
- Estados transaccionales y trazabilidad básica.

**Estimación HH:** `32 – 44` hrs.

---

## Componente 3 — Validaciones de Negocio y Bloqueos

**Descripción:** Reglas para asegurar calidad de dato y control operativo.

**Incluye:**
- Validación de KM/Horas actuales versus anteriores.
- Bloqueo de edición después de 24 horas.
- Bloqueo de edición si el registro ya fue sincronizado.
- Validaciones de litros, fecha, vehículo activo y bodega/fundo válidos.
- Advertencias por diferencias anómalas o rendimientos poco comunes.

**Estimación HH:** `14 – 22` hrs.

---

## Componente 4 — Integración con Finnegans

**Descripción:** Reutilización y adaptación del patrón de alimentación para registrar el consumo de combustible como consumo de producto desde bodega.

**Incluye:**
- Diseño del mapeo local → ERP.
- Construcción del payload o adaptación del payload existente.
- Mapeo preliminar sobre transacción `CONSPROD` / `OPER`.
- Reutilización/extensión incremental de `FinnegansClient.php` o servicio común. La construcción base reutilizable de integración ERP debe estimarse por separado si aún no existe.
- Registro de respuesta ERP y documento generado.
- Manejo de errores y reintento manual.
- Ajustes según validaciones de soporte Finnegans.

**Estimación HH:** `24 – 38` hrs.

> [!WARNING]
> Este componente tiene dependencia externa con la definición final que entregue Finnegans sobre el documento a utilizar y sus campos obligatorios.

---

## Componente 5 — Permisos y Payload de Login

**Descripción:** Perfilamiento del botón sincronizar ERP como mejora transversal de la app.

**Incluye:**
- Aplicación en el módulo de Combustible de permisos o atributos de usuario ya existentes.
- Si el atributo global, payload de sesión o botón transversal aún no existen, deben estimarse por separado como componente transversal.

**Estimación HH:** `4 – 8` hrs.

---

## Componente 6 — Carga Inicial, Normalización y Soporte de Arranque

**Descripción:** Preparación mínima para partir con datos consistentes.

**Incluye:**
- Revisión de equivalencias del Excel histórico.
- Normalización inicial de vehículos/maquinarias duplicadas por nombre.
- Carga base de vehículos, responsables y tipos.
- Ajustes menores de arranque según datos reales del cliente.

**Estimación HH:** `12 – 20` hrs.

> [!NOTE]
> Esta estimación considera apoyo técnico para la carga inicial. Si se solicita una migración histórica completa del Excel a la nueva transacción, debe estimarse aparte.

---

## Componente 7 — Testing Funcional y Documentación Técnica

**Descripción:** Pruebas funcionales del flujo y documentación de apoyo a la salida.

**Incluye:**
- pruebas de creación/edición/anulación;
- pruebas de bloqueo y validaciones;
- pruebas de sincronización exitosa/error;
- documentación de mapeo y consideraciones operativas.

**Estimación HH:** `10 – 16` hrs.

---

## Resumen de Estimación

| # | Componente | HH Mínimo | HH Máximo |
|---|---|:---:|:---:|
| 1 | Maestros Base del Módulo | 18 | 26 |
| 2 | Transacción Consumo de Combustible | 32 | 44 |
| 3 | Validaciones de Negocio y Bloqueos | 14 | 22 |
| 4 | Integración con Finnegans | 24 | 38 |
| 5 | Permisos y Payload de Login | 4 | 8 |
| 6 | Carga Inicial y Normalización | 12 | 20 |
| 7 | Testing Funcional y Documentación Técnica | 10 | 16 |
| | **TOTAL ESTIMADO** | **114** | **174** |

---

## Lectura de la Estimación

💡 El rango depende principalmente de:
- qué tan reutilizable resulte la integración actual de alimentación;
- cuán ordenado venga el catálogo inicial de vehículos y responsables;
- cuántos ajustes pida Finnegans en la etapa de prueba;
- si el JSON completo de Finnegans agrega campos obligatorios no visibles en la muestra mínima;
- cómo se confirme el mapeo de dimensiones `DIMBU` y `DIMCTC`.

> [!NOTE]
> Las HH de los Componentes 1 y 5 fueron recalibradas respecto de una versión anterior del documento para reflejar solo el alcance específico del módulo de Combustible, excluyendo construcción base de componentes transversales.

---

## Recomendación de Ejecución

### Opción recomendada

Desarrollar en este orden:

1. Maestros base.
2. Transacción local completa.
3. Permisos de sincronización.
4. Integración ERP.
5. Carga inicial y ajustes finales.

### Motivo

Esto permite que el cliente empiece a capturar datos correctos aunque la definición final con Finnegans todavía esté en ajuste.

---

## Exclusiones consideradas en esta estimación

No se considera en este rango:
- desarrollo de reportes BI avanzados;
- migración histórica completa de los 1.498 registros a la nueva transacción;
- automatizaciones masivas de importación desde Forms;
- capacitación formal a usuarios;
- gestión de proyecto o QA externo dedicado.

> [!NOTE]
> Tampoco considera desarrollo del concepto `Centro`, ya que quedó fuera de alcance y el flujo quedará basado en `Fundo -> Bodega`.
> Tampoco considera la construcción completa del Maestro de Ítems, Maestro de Funcionarios, permisos transversales ni infraestructura base reutilizable de integración ERP, salvo la adaptación puntual necesaria para este módulo.

---

## Próximos Pasos Sugeridos

1. Validar las preguntas abiertas del documento de discovery.
2. Confirmar con Finnegans el tipo de documento y payload de consumo a reutilizar.
3. Definir si el histórico del Excel se migra o solo se usa como referencia.
4. Aprobar si el desarrollo se aborda en una sola etapa o en Fase 1 local + Fase 2 ERP.
