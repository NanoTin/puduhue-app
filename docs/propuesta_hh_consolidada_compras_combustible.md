# Propuesta de Horas-Hombre (HH) — Consolidado Compras, Combustible y Componentes Transversales
**Fecha de Análisis:** 17 de Abril de 2026
**Objetivo:** Consolidar en un solo documento el alcance funcional, técnico y de estimación de los módulos de Compras y Combustible, separando claramente lo transversal de lo específico de cada módulo.

---

## 1. Propósito del Documento

Este documento reemplaza la necesidad de revisar múltiples archivos para entender el alcance completo del trabajo. Su objetivo es:

- dejar en una sola vista los componentes transversales y los componentes propios de cada módulo;
- consolidar HH evitando doble cobro entre Compras y Combustible;
- unificar columnas, reglas, integraciones y notas relevantes para desarrollo;
- servir como base de validación funcional antes de pasar a diseño técnico y construcción.

> [!NOTE]
> Este consolidado **no elimina** el contenido de los documentos previos. Los complementa y los resume en una estructura única para facilitar revisión, búsqueda y ejecución.

---

## 2. Criterio General de Separación

Se trabajará con tres capas:

1. **Componentes transversales**: se desarrollan una sola vez y luego son reutilizados por Compras, Combustible y otros módulos futuros.
2. **Componentes específicos de Compras**: aplican solo al flujo REQ → Pendientes → PreOC → ERP.
3. **Componentes específicos de Combustible**: aplican solo al flujo operativo de carga/consumo de combustible y su envío a ERP.

Si un componente transversal se implementa primero, los módulos posteriores deben considerar solo su uso o adaptación y no su construcción completa nuevamente.

---

## 3. Componentes Transversales

### 3.1. Maestro de Funcionarios

**Carácter:** Transversal
**Uso:** Compras, Combustible, aprobaciones, responsables, reportería.

**Columnas base consolidadas:**

| Columna | Tipo / Observación |
|---|---|
| `funcionarioid` | PK AI |
| `funcionariorut` | RUT |
| `funcionarionombre` | Nombre completo |
| `funcionarioactivo` | Si / No |
| `funcionariocargo` | Cargo |
| `centrocostoid` | FK → Maestro de Centros de Costo |
| `funcionariotelefono` | Teléfono |
| `funcionarioemail` | Email |
| auditoría | 8 columnas estándar del proyecto |

**Notas de diseño:**
- Ya estaba contemplado en Compras y reutilizable desde Combustible.
- Si se carga desde Excel, los no presentes pueden desactivarse automáticamente.
- Debe poder ser usado tanto como solicitante/responsable como también como base para reglas de reemplazo de aprobadores.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Modelo de datos y relaciones | 3 | 5 |
| CRUD y listados | 5 | 7 |
| Importación/carga inicial | 4 | 6 |
| Activo/inactivo y reglas de vigencia | 2 | 4 |
| Validaciones y documentación | 2 | 3 |
| **Total** | **16** | **25** |

---

### 3.2. Maestro de Centros de Costo

**Carácter:** Transversal
**Uso:** Compras, aprobaciones, integraciones ERP, eventualmente otros módulos.

**Columnas base consolidadas:**

| Columna | Tipo / Observación |
|---|---|
| `centrocostoid` | PK AI |
| `centrocostodesc` | Descripción |
| `centrocostocod` | Código único alfanumérico, útil para asociar con ERP |
| `centrocostoactivo` | Si / No |
| auditoría | 8 columnas estándar |

**Notas de diseño:**
- En Compras el jefe del CC es parte de la lógica de aprobación.
- El documento técnico previo ya contemplaba sincronización ERP de centros.
- Conviene dejar el código local preparado para asociación con ERP, aunque el ID interno siga siendo AI.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Modelo de datos | 2 | 3 |
| CRUD y mantención | 4 | 6 |
| Asociación con configuraciones de aprobación | 2 | 4 |
| Validaciones y documentación | 1 | 2 |
| **Total** | **9** | **15** |

---

### 3.3. Maestro de Usuarios y Permisos Globales

**Carácter:** Transversal
**Uso:** Compras, Combustible, login/payload, aprobaciones, sincronizaciones.

**Columnas nuevas consolidadas:**

| Columna | Tipo / Observación |
|---|---|
| `usuariocomprador` | Si / No |
| `usuarioaprobador` | Si / No. Unifica aprobadores REQ y PreOC |
| `usuarioaprobmontonetopreoc` | Monto neto para aprobador automático de PreOC |
| `usuariofirmaimagen` | Firma gráfica para formato de PreOC |
| `usuariopermitesincronizarerp` | Si / No |
| `usuariopermiteeditarpreciosreq` | Si / No |
| `usuariopermitegestionitems` | Si / No |

**Notas de diseño:**
- Se reemplaza la visión fragmentada previa (`autorizareq`, `editarprecios`, `permitecreareditar`) por una estructura más explícita.
- Conviene evaluar qué atributos se devuelven en el payload de login y cuáles se consultan desde DB.
- El botón de sincronización ERP no debe mostrarse si el usuario no tiene permiso.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Diseño y ajuste del modelo de usuarios | 3 | 5 |
| Mantención administrativa en UI | 4 | 6 |
| Exposición en payload/login/sesión | 2 | 4 |
| Aplicación de permisos en vistas/acciones | 4 | 6 |
| Validaciones, matrices y documentación | 2 | 3 |
| **Total** | **15** | **24** |

---

### 3.4. Maestro de Familia

**Carácter:** Transversal
**Uso:** Maestro de Ítems, Dashboard/Suplementación, Compras, Combustible.

**Columnas base:**

| Columna | Tipo / Observación |
|---|---|
| `familiaid` | PK AI |
| `familiadesc` | Descripción |
| `familiacod` | Código único alfanumérico asociado a ERP |
| `familiaactivo` | Si / No |
| auditoría | 8 columnas estándar |

**Notas de diseño:**
- Este maestro nace como necesidad clara del reporte de suplementación.
- Debe sincronizarse desde ERP.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Modelo de datos | 2 | 3 |
| CRUD/listado | 2 | 4 |
| Validaciones/documentación | 1 | 2 |
| **Total** | **5** | **9** |

---

### 3.5. Maestro de Subfamilia

**Carácter:** Transversal
**Uso:** Maestro de Ítems, Dashboard/Suplementación, Compras, Combustible.

**Columnas base:**

| Columna | Tipo / Observación |
|---|---|
| `subfamiliaid` | PK AI |
| `familiaid` | FK → `familia` |
| `subfamiliadesc` | Descripción |
| `subfamiliacod` | Código único alfanumérico asociado a ERP |
| `subfamiliaactivo` | Si / No |
| auditoría | 8 columnas estándar |

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Modelo y relaciones | 2 | 3 |
| CRUD/listado | 2 | 4 |
| Validaciones/documentación | 1 | 2 |
| **Total** | **5** | **9** |

---

### 3.6. Maestro de Ítems Formularios (`invitemsformularios`)

**Carácter:** Transversal
**Uso:** Maestro de Ítems, filtrado por módulos/formularios.

**Columnas base:**

| Columna | Tipo / Observación |
|---|---|
| `invitemformcod` | PK natural. Sin AI |
| `invitemformdesc` | Descripción |

**Valores iniciales propuestos:**

| Código | Descripción |
|---|---|
| `LCH` | Leche |
| `CMB` | Combustible |
| `SUP` | Suplementación |
| `BDG` | Bodega / Inventario general |

**Nota histórica:**
- Se reemplaza la idea anterior de módulo de uso como catálogo difuso.
- Cambio explícito: ~~`ALM`~~ → `SUP` para reflejar mejor Suplementación.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Definición del catálogo | 1 | 2 |
| Modelo y semillas iniciales | 1 | 2 |
| Consumo en formularios y filtros | 2 | 3 |
| Documentación | 1 | 1 |
| **Total** | **5** | **8** |

---

### 3.7. Maestro de Ítems / Productos

**Carácter:** Transversal
**Uso:** Compras, Combustible, Suplementación, reportería, ERP.

**Columnas nuevas consolidadas:**

| Columna | Tipo / Observación |
|---|---|
| `invitemorigen` | Interno / Externo |
| `invitemprecioestandar` | Precio estándar editable/local o proveniente de ERP |
| `familiaid` | FK |
| `subfamiliaid` | FK |
| `invitemformcod` | FK → `invitemsformularios` |
| `invitemcompra` | Si / No |
| `invitemventa` | Si / No |

**Otras columnas que se deben respetar/sincronizar:**
- Estado/Activo
- Descripción
- Unidad de medida
- Familia
- Subfamilia
- Compra
- Venta
- `stockable` ya existente para distinguir Material / Servicio

**Notas clave:**
- El sincronizador ERP no debe tocar campos locales como formulario/uso si eso se define localmente.
- Debe guardar snapshot de atributos relevantes en transacciones futuras.
- Precio estándar debe usarse para valorizar REQ y Combustible al momento de grabar.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Definición funcional y reglas de sincronización | 4 | 6 |
| Ajustes DB y migraciones | 6 | 8 |
| UI mantenimiento y búsqueda | 5 | 8 |
| Snapshot y reglas de uso en módulos | 4 | 6 |
| Validaciones/documentación | 3 | 4 |
| **Total** | **22** | **32** |

---

### 3.8. Integraciones GET ERP de Maestros

**Carácter:** Transversal
**Uso:** Compras y Combustible, dependiendo del endpoint.

**Objetivo:** separar el esfuerzo de consumo `GET` desde ERP para tener trazabilidad por endpoint y una base reutilizable de estimación.

| Endpoint GET | Uso principal | Compras | Combustible | HH Mín | HH Máx |
|---|---|---|---|:---:|:---:|
| Obtener Centros de Costo | Maestro de CC | Sí | No directo | 4 | 6 |
| Obtener Familias | Clasificación de ítems | Sí | Sí | 3 | 5 |
| Obtener Subfamilias | Clasificación de ítems | Sí | Sí | 3 | 5 |
| Obtener Ítems/Productos | Maestro `invitems` | Sí | Sí | 6 | 10 |
| Obtener Proveedores | Maestro de Proveedores | Sí | No | 2 | 3 |
| Obtener Monedas | Maestro de Monedas | Sí | No | 1 | 2 |
| Obtener Impuestos | Maestro de Impuestos | Sí | No | 2 | 3 |
| Obtener Clases de Impuestos | Maestro de Clase de Impuestos | Sí | No | 1 | 2 |
| Obtener Condiciones de Pago | Maestro de Condiciones de Pago | Sí | No | 2 | 3 |
| Obtener Cuentas Contables | Maestro de Cuentas Contables | Sí | No | 2 | 2 |
| **Total Integraciones GET ERP** |  |  |  | **26** | **41** |

**Promedio HH por endpoint:**
- HH mínima promedio: `2.6` hrs por endpoint
- HH máxima promedio: `4.1` hrs por endpoint
- HH promedio simple de referencia: `3.35` hrs por endpoint

> [!NOTE]
> Este promedio sirve como base rápida de estimación. No reemplaza el análisis puntual, porque endpoints con mayor transformación o validación, como Ítems, naturalmente pesan más que endpoints simples como Monedas.

---

### 3.9. Maestros ERP de Soporte para Compras

**Carácter:** Transversal para compras y futuras integraciones ERP.

**Maestros incluidos:**
- Impuestos
- Clases de Impuestos
- Condiciones de Pago
- Cuentas Contables
- Proveedores
- Monedas

**Definiciones consolidadas:**

| Maestro | Columnas clave |
|---|---|
| Impuestos | ID AI, Descripción, Código, Activo |
| Clase de Impuestos | ID AI, Descripción, Código, Activo |
| Condiciones de Pago | ID AI, Descripción, Código, Activo |
| Cuentas Contables | ID AI, Descripción, Código, Activo |
| Proveedores | debe incluir FK a Clase de Impuestos |
| Monedas | espejo ERP |

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Modelado de maestros | 5 | 8 |
| Pantallas/listados y filtros | 5 | 8 |
| Relaciones cruzadas proveedor/impuestos/condición | 3 | 5 |
| Validaciones/documentación | 2 | 3 |
| **Total** | **15** | **24** |

---

### 3.10. Base Común de Integración ERP

**Carácter:** Transversal
**Uso:** Compras, Combustible y cualquier módulo con sincronización manual/automática.

**Incluye:**
- cliente común ERP;
- configuración por ambiente;
- logging de sincronización;
- reintento manual;
- control de error/último error/fecha;
- helpers de cron y sincronización on-demand.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Cliente común / refactor base | 5 | 8 |
| Logging y trazabilidad | 4 | 6 |
| Reintentos y operación manual | 3 | 5 |
| Configuración por ambiente | 2 | 3 |
| Helpers reutilizables | 3 | 5 |
| Pruebas/documentación | 2 | 4 |
| **Total** | **19** | **31** |

---

### 3.11. Transacción de Inactividad de Aprobadores con Reemplazo

**Carácter:** Transversal
**Uso:** Compras hoy, potencial reutilización futura.

**Maestro auxiliar requerido:**
- Tipos de Inactividad para Aprobadores: Licencia, Vacaciones, Permiso, Otros.

**Reglas consolidadas:**
- el reemplazante debe ser aprobador activo;
- el reemplazante no debe tener inactividad vigente;
- esta lógica se usa para construir listas automáticas de firmantes;
- los aprobadores default no se pueden quitar manualmente.

**Tareas y HH:**

| Tarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Maestro de tipos de inactividad | 2 | 3 |
| Transacción de inactividad y reemplazo | 4 | 6 |
| Validaciones de elegibilidad del reemplazante | 2 | 4 |
| Integración con generación de firmantes | 3 | 5 |
| Validaciones/documentación | 1 | 2 |
| **Total** | **12** | **20** |

---

## 4. Componentes Específicos de Compras

### 4.1. Presupuesto de Compra

**Maestros:**
- Clasificación de Presupuesto
- Sub-clasificación de Presupuesto
- Códigos de Presupuesto

**Transacción:**
- Cabecera + Detalle/Kardex + Log
- saldo inicial al crear
- ajustes manuales
- movimientos por reserva, devolución y aprobación desde PreOC

**HH:** `30 – 45`

---

### 4.2. Requerimientos de Compra (REQ)

**Transacciones requeridas:**
- Cabecera
- Detalle
- Lista de Aprobación
- Log General
- Log Lista de Aprobación

**Reglas clave consolidadas:**
- no mezclar Material y Servicio;
- solicitante desde Maestro de Funcionarios;
- snapshot de precio actual del ítem y total por línea;
- aprobadores default no se pueden quitar;
- si un aprobador está ausente, debe resolverse vía reemplazo/inactividad;
- al editar en estado permitido, se debe dejar traza de cambios.

**HH:** `55 – 75`

---

### 4.3. Requerimientos Pendientes de PreOC

**Transacción consolidada:**
- Cabecera de saldo autorizado y pendiente;
- Detalle de eventos al asociar a PreOC o anular cantidades;
- control de comprado, compra parcial, disponible y anulado.

**Notas clave:**
- debe permitir anulación parcial o total de saldo pendiente;
- el comprador puede decidir anular totalmente un requerimiento pendiente cuando ya no se comprará.

**HH:** `20 – 30`

---

### 4.4. Pre Orden de Compra (PreOC)

**Transacciones requeridas:**
- Cabecera
- Detalle agrupado por ítem
- Subnivel por requerimientos asociados a cada ítem
- Resumen de presupuestos utilizados
- Lista de aprobación
- Log general
- Log lista de aprobación
- Log de sincronización ERP

**Columnas/atributos clave adicionales:**
- número de OC ERP;
- fecha de sincronización ERP;
- último error fecha;
- último error descripción;
- snapshot de datos de proveedor e ítem;
- recalcular snapshots al editar cuando corresponda.

**Reglas clave consolidadas:**
- niveles de visualización:
  - Nivel 1: agrupado por ítem
  - Nivel 2: requerimientos por ítem, con presupuesto asociado
- se debe mostrar resumen para el aprobador:
  - porcentaje de la PreOC por presupuesto;
  - saldo disponible;
  - participación monetaria por presupuesto.
- aprobadores por monto se agregan automáticamente si corresponde y no se pueden quitar si son obligatorios por regla.

**HH:** `65 – 85`

---

### 4.5. Integración ERP Compras

**Integraciones consolidadas:**
- GET Centros
- GET Proveedores
- GET Monedas
- GET Impuestos
- GET Clases de Impuestos
- GET Condiciones de Pago
- GET Cuentas Contables
- POST Enviar PreOC

**Notas clave:**
- la PreOC debe quedar con trazabilidad completa de sincronización;
- si falla el POST, debe quedar último error y log de integración;
- el botón/manual de sincronización depende del permiso global.

**HH:** `35 – 55`

---

## 5. Componentes Específicos de Combustible

### 5.1. Maestro de Tipos de Vehículos

**Columnas:**
- ID AI
- Descripción
- Activo

**HH:** `4 – 6`

---

### 5.2. Maestro de Tipo de Control de Uso

**Columnas:**
- ID AI
- Descripción
- Activo

**Ejemplos:**
- Kilómetros
- Horómetro

**HH:** `4 – 6`

---

### 5.3. Maestro de Vehículos

**Columnas consolidadas:**

| Columna | Observación |
|---|---|
| `vehiculoid` | PK AI |
| `vehiculocod` | Código único alfanumérico |
| `vehiculodesc` | Descripción |
| `tipovehiculoid` | FK |
| `tipocontrolusoid` | FK |
| `funcionarioid_responsable` | FK a funcionarios |
| `vehiculokmhorasinicial` | valor base |
| `vehiculokmhorasactual` | se actualiza al grabar |
| `vehiculoultimocombustible` | último registro o snapshot útil |
| `invitemid` | solo ítems con formulario `CMB` |
| `vehiculoactivo` | vigencia |
| auditoría | estándar |

**HH:** `18 – 26`

---

### 5.4. Transacción Carga de Combustible

**Estructura consolidada:**
- Registro Individual
- Log

**Campos principales:**
- Estado
- Fundo
- Bodega
- Fecha
- Vehículo
- snapshot del vehículo
- litros utilizados/cargados
- KM/Horas anterior
- KM/Horas actual
- diferencia
- KM/Horas por litro
- precio actual del ítem
- total valorizado

**Reglas clave:**
- solo editable en estado Pendiente ERP;
- snapshot de columnas relevantes del vehículo e ítem;
- usa precio actual del Maestro de Ítems al momento de grabar;
- permite valorización total de la transacción.

**HH:** `32 – 44`

---

### 5.5. Validaciones y Bloqueos de Combustible

**Incluye:**
- consistencia de KM/Horas;
- bloqueo luego de ventana de tiempo definida;
- bloqueo si ya fue sincronizado;
- duplicidad operativa;
- vehículo activo;
- producto combustible activo;
- reglas de advertencia para rendimiento anómalo.

**HH:** `14 – 22`

---

### 5.6. Integración ERP Combustible

**Incluye:**
- POST de consumo de ítems desde módulos asociados a inventario;
- reutilización de base común de integración;
- log de sincronización;
- reintento manual;
- control de error.

**HH:** `24 – 38`

---

### 5.7. Carga Inicial y Normalización

**Incluye:**
- homologación del Excel histórico;
- normalización de nombres de vehículos y responsables;
- carga base;
- ajustes menores de arranque.

**HH:** `12 – 20`

---

### 5.8. Testing Funcional y Documentación

**HH:** `10 – 16`

---

## 6. Reglas Funcionales Transversales Recordatorias

- Todas las tablas nuevas deben incluir columnas de auditoría estándar.
- El snapshot de datos relevantes del Maestro de Ítems debe guardarse en las transacciones nuevas cuando aporte a reportería y trazabilidad.
- En Combustible y REQ se debe guardar precio actual del ítem al momento del registro.
- En PreOC se deben snapshotear atributos relevantes del proveedor y del ítem.
- Los aprobadores default no se pueden quitar.
- Si un aprobador no está disponible, la salida correcta es registrar ausencia con reemplazo o período de inactividad.
- Si el usuario no puede sincronizar ERP, no se debe mostrar el botón correspondiente.
- El sincronizador de ítems ERP no debe sobrescribir columnas locales que se definan como de gestión propia.

---

## 7. Tabla de Conceptos, Uso y HH

| Concepto | Transversal | Compras | Combustible | HH Mín | HH Máx |
|---|---|---|---|:---:|:---:|
| Maestro de Funcionarios | Sí | Sí | Sí | 16 | 25 |
| Maestro de Centros de Costo | Sí | Sí | No directo | 9 | 15 |
| Maestro de Usuarios y Permisos | Sí | Sí | Sí | 15 | 24 |
| Maestro de Familia | Sí | Sí | Sí | 5 | 9 |
| Maestro de Subfamilia | Sí | Sí | Sí | 5 | 9 |
| Maestro de Ítems Formularios | Sí | Sí | Sí | 5 | 8 |
| Maestro de Ítems / Productos | Sí | Sí | Sí | 22 | 32 |
| Integraciones GET ERP de Maestros | Sí | Sí | Sí parcial | 26 | 41 |
| Maestros ERP de soporte | Sí | Sí | No directo | 15 | 24 |
| Base común de Integración ERP | Sí | Sí | Sí | 19 | 31 |
| Inactividad de Aprobadores con Reemplazo | Sí | Sí | No directo | 12 | 20 |
| Presupuesto de Compra | No | Sí | No | 30 | 45 |
| REQ | No | Sí | No | 55 | 75 |
| Pendientes de PreOC | No | Sí | No | 20 | 30 |
| PreOC | No | Sí | No | 65 | 85 |
| Integración ERP Compras | No | Sí | No | 35 | 55 |
| Maestro Tipos de Vehículos | No | No | Sí | 4 | 6 |
| Maestro Tipo Control de Uso | No | No | Sí | 4 | 6 |
| Maestro de Vehículos | No | No | Sí | 18 | 26 |
| Transacción Carga Combustible | No | No | Sí | 32 | 44 |
| Validaciones de Combustible | No | No | Sí | 14 | 22 |
| Integración ERP Combustible | No | No | Sí | 24 | 38 |
| Carga Inicial Combustible | No | No | Sí | 12 | 20 |
| Testing y Documentación Combustible | No | No | Sí | 10 | 16 |

---

## 8. Resumen de HH por Bloque

### 8.1. Solo Componentes Transversales

**Total estimado:** `149 – 238` hrs

**Promedio referencial de Integraciones GET ERP:** `2.6 – 4.1 hrs` por endpoint
**Promedio simple de referencia:** `3.35 hrs` por endpoint

### 8.2. Solo Componentes Específicos de Compras

**Total estimado:** `205 – 290` hrs

### 8.3. Solo Componentes Específicos de Combustible

**Total estimado:** `118 – 178` hrs

### 8.4. Escenario Consolidado Total

Si se ejecuta todo el alcance levantado en este documento:

**Total general estimado:** `472 – 706` hrs

> [!NOTE]
> Este total consolidado incluye componentes transversales + Compras + Combustible. Si algún componente transversal ya existe o se resuelve de otra forma, el total debe recalcularse.

---

## 9. Próximos Pasos Recomendados

1. Validar este documento como versión maestra de alcance.
2. Confirmar qué componentes transversales se desarrollarán antes que los módulos.
3. Definir decisiones abiertas de sincronización ERP, especialmente sobre ítems, familias/subfamilias y payload/login.
4. Pasar luego a un diseño técnico definitivo de tablas y endpoints usando este consolidado como fuente principal.
