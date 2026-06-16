# Propuesta de Horas-Hombre (HH) — Componentes Transversales
**Fecha de Análisis:** 16 de Abril de 2026

---

> Este documento agrupa desarrollos base que impactan a más de una propuesta funcional. El objetivo es evitar duplicación de HH entre módulos y dejar explícito que, si un componente transversal se desarrolla en un proyecto, no debe volver a cobrarse íntegramente en otro.

---

## 1. Maestro de Ítems y Clasificación Transversal

**Descripción:** Normalización y extensión del maestro `invitems` como base común para módulos operacionales, reportes y futuras integraciones con ERP.

**Tareas estimadas:**

| Tarea | Detalle | HH Mín | HH Máx |
|---|---|:---:|:---:|
| Definición funcional y modelo objetivo | Levantamiento de atributos transversales, reglas de uso, estrategia local vs ERP/API y criterio de convivencia con `stockable`. | 4 | 6 |
| Ajustes de base de datos | Nuevas columnas, catálogos auxiliares, relaciones y scripts de migración base sobre `invitems`. | 6 | 8 |
| Mantención administrativa | Pantallas o formularios de administración para atributos locales y clasificadores compartidos. | 5 | 8 |
| Clasificación y agrupadores | Implementación de categorías o agrupadores de ítems para reportes, incluyendo asociación al maestro. | 4 | 6 |
| Migración y normalización de datos existentes | Conversión de atributos heredados como `LECHE = SI/NO`, limpieza inicial y homologaciones. | 4 | 6 |
| Sincronización ERP/API | Cron, botón on-demand o integración API para completar/actualizar ítems desde ERP, si se confirma como alcance. | 6 | 10 |
| Pruebas y documentación | Casos de validación, criterios de uso y documentación funcional/técnica mínima. | 3 | 4 |
| **Total Componente 1** |  | **32** | **48** |

**Se referencia desde:**
- `docs/propuesta_hh_modulo_compras.md`
- `docs/propuesta_hh_modulo_combustible.md`
- `docs/propuesta_hh_mejoras_dashboard.md`

**HH estimadas:** `32 – 48` hrs.

---

## 2. Maestro de Funcionarios y Vigencia

**Descripción:** Construcción del maestro transversal de funcionarios para ser reutilizado por módulos operativos y flujos de aprobación.

**Tareas estimadas:**

| Tarea | Detalle | HH Mín | HH Máx |
|---|---|:---:|:---:|
| Modelo y estructura base | Tabla(s), relaciones, estados y campos mínimos del funcionario. | 3 | 5 |
| CRUD y mantención | Pantallas de creación, edición, listado y búsqueda. | 5 | 7 |
| Carga inicial | Importación desde Excel o fuente acordada con validaciones mínimas. | 4 | 6 |
| Vigencia y desactivación | Reglas de activo/inactivo, baja lógica y controles de uso. | 2 | 4 |
| Reemplazos e inactividad | Registro de reemplazante y períodos para vacaciones, licencias u otras ausencias. | 3 | 5 |
| Pruebas y documentación | Validaciones funcionales y guía de operación. | 2 | 3 |
| **Total Componente 2** |  | **19** | **30** |

**Se referencia desde:**
- `docs/propuesta_hh_modulo_compras.md`
- `docs/propuesta_hh_modulo_combustible.md`

**HH estimadas:** `19 – 30` hrs.

---

## 3. Perfiles, Permisos y Payload de Sesión

**Descripción:** Extensión del modelo de usuarios para soportar permisos funcionales reutilizables entre módulos.

**Tareas estimadas:**

| Tarea | Detalle | HH Mín | HH Máx |
|---|---|:---:|:---:|
| Diseño de permisos | Definición de atributos globales de perfil/usuario y su alcance transversal. | 2 | 3 |
| Ajustes de modelo y administración | Persistencia de atributos y exposición en pantallas de usuarios/perfiles. | 3 | 5 |
| Payload de login y sesión | Disponibilizar permisos en sesión/contexto para consumo transversal. | 2 | 3 |
| Aplicación de restricciones | Uso de permisos en botones, acciones y vistas compartidas como sincronización ERP. | 3 | 5 |
| Pruebas y documentación | Validación de perfiles, sesión y visibilidad. | 1 | 2 |
| **Total Componente 3** |  | **11** | **18** |

**Se referencia desde:**
- `docs/propuesta_hh_modulo_compras.md`
- `docs/propuesta_hh_modulo_combustible.md`

**HH estimadas:** `11 – 18` hrs.

---

## 4. Base de Integración ERP / Finnegans

**Descripción:** Servicios, clientes, logging y utilidades comunes para integraciones con Finnegans que puedan ser reutilizadas por más de un módulo.

**Tareas estimadas:**

| Tarea | Detalle | HH Mín | HH Máx |
|---|---|:---:|:---:|
| Cliente común o refactor base | Consolidación de servicio compartido para autenticación, consumo y utilidades comunes. | 5 | 8 |
| Logging y trazabilidad | Registro estándar de payload, respuesta, errores y correlación para debugging. | 4 | 6 |
| Reintentos y operación manual | Herramientas base para reintento manual y manejo operativo de errores. | 3 | 5 |
| Helpers de sincronización | Patrones reutilizables para cron, ejecución on-demand y utilidades de integración. | 4 | 6 |
| Parametrización y configuración | Variables, endpoints, credenciales y comportamiento configurable por ambiente. | 2 | 3 |
| Pruebas y documentación | Casos de integración base y documentación técnica de uso. | 2 | 4 |
| **Total Componente 4** |  | **20** | **32** |

**Se referencia desde:**
- `docs/propuesta_hh_modulo_compras.md`
- `docs/propuesta_hh_modulo_combustible.md`

**HH estimadas:** `20 – 32` hrs.

---

## Resumen de Estimación

| # | Componente | HH Mínimo | HH Máximo |
|---|---|:---:|:---:|
| 1 | Maestro de Ítems y Clasificación Transversal | 32 | 48 |
| 2 | Maestro de Funcionarios y Vigencia | 19 | 30 |
| 3 | Perfiles, Permisos y Payload de Sesión | 11 | 18 |
| 4 | Base de Integración ERP / Finnegans | 20 | 32 |
| | **TOTAL ESTIMADO** | **82** | **128** |

---

## Criterio de Uso

Si alguno de estos componentes se aprueba y desarrolla como proyecto propio, las propuestas funcionales posteriores deben estimar únicamente la adaptación o consumo del componente existente, no su construcción completa nuevamente.
