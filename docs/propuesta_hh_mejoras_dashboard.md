# Propuesta de Horas-Hombre (HH) — Mejoras de Dashboard y Nuevos Módulos
**Fecha de Análisis:** 17 de Abril de 2026
**Basado en:** Respuestas de correo/reunión (23/03/2026), notas complementarias de definición técnica y definición funcional consolidada al 17/04/2026 para Calidad de Leche y Litros retirados.

---

> Este documento consolida el levantamiento técnico y los requerimientos funcionales solicitados por el cliente, organizados por componentes para facilitar su estimación en Horas-Hombre (HH).
> Incluye el detalle funcional requerido para dimensionar correctamente los módulos de Calidad de Leche, Litros retirados y su impacto en Dashboard.

> [!NOTE]
> Si el Maestro de Ítems y sus clasificadores se transforman en un componente transversal compartido con otros módulos, su construcción base debe estimarse aparte. Ver `docs/propuesta_hh_componentes_transversales.md`.

> [!WARNING]
> En esta versión sí queda suficientemente definido el alcance de:
> - Calidad de Leche
> - Litros retirados diarios desde archivos de planta
>
> El alcance de **Precio por Litro** sigue parcialmente abierto respecto de su **formato exacto de entrada** y cantidad de variantes por cliente/planta.
> Por lo mismo, la HH estimada para ese subcomponente se considera **válida bajo el supuesto de 1 importador base de Excel mensual** y podría recalibrarse si aparecen múltiples formatos especiales.

---

## 1. Integración Finnegans y EndPoints

**Descripción:** Requerimientos relacionados con la extracción de datos desde Finnegans y disponibilización a Power BI.

**Incluye:**
- Ajuste y validación del consumo del EndPoint `HACREPORTELECHE`.
- Envío de parámetros tal cual fueron especificados por negocio.
- Única modificación permitida en la lógica: si `FechaHasta` se deja vacío, el sistema debe completar con el último día correspondiente.
- Mantener `PreciosProyectados` con origen y manejo en Power BI (Excel), sin mover esta lógica al backend.
- Pruebas de salida para asegurar que el endpoint entrega lo requerido para explotación analítica.

**No incluye:**
- Cruces adicionales del lado backend.
- Cálculo local de precios proyectados.

**Estimación HH:** `6 – 10` hrs.

---

## 2. Dashboard — Lógica Transversal y Filtros

**Descripción:** Habilitación de visualización dinámica Mensual/Diaria en `dashboard.php`, impactando a todos los indicadores presentes.

**Incluye:**
- Inserción de selector visual Diario / Mensual en cabecera.
- Despliegue condicional de filtros `[Año, Mes]` al activar vista diaria.
- Inicialización por defecto en fecha actual.
- Persistencia de selección en sesión del usuario logueado.
- Aplicación automática de temporada vigente de leche (`temporadatipocodigo = 'LECHE' and temporadaactivo = 1`).
- Distribución uniforme del PPTO mensual de leche para su comparación diaria en gráficos.
- Mantener lógica actual de fundos por perfil, con opción por defecto a "TODOS".
- Ajustes de consultas y componentes visuales existentes para soportar ambas vistas sin duplicar pantallas.

**Estimación HH:** `18 – 26` hrs.

---

## 3. Módulo Suplementación

**Descripción:** Reestructuración de visualización de dosis / dietas proveniente de Finnegans (siempre valorizado en Kg).

**Incluye:**
- Nuevo reporte diario fuera de Dashboard detallado por categoría de ítem.
- Consumo de datos integrados con Finnegans para gráficos dentro del Dashboard.
- Vista mensual: eje X por Año/Mes de temporada y eje Y con promedio lineal del mes.
- Vista diaria: eje X por días del mes seleccionado y eje Y con suma acumulada de `suplanimaldetalle.dosisporanimal`.
- Ajustes de consultas y visualización para convivencia con selector Diario/Mensual.

**Nota de alcance:**
- Si se requiere una tabla maestra de categorías de ítems o nuevas clasificaciones en `invitems`, su construcción base debe tratarse como componente transversal y no duplicarse aquí.
- No se consideran proyecciones cruzadas de planes o presupuestos de alimentación en esta iteración.

**Estimación HH:** `14 – 22` hrs.

---

## 4. KPIs Producción (Gráficos Dashboard)

**Descripción:** Modificaciones sobre métricas productivas actuales y nuevos componentes visuales en `dashboard.php`.

**Incluye:**
- Mantener tarjetas summary activas, sin rediseño lógico mayor.
- Incorporar línea de Presupuesto en gráficos actuales.
- Adaptar visualización a toggle Diario / Mensual.
- Nuevos gráficos proyectados:
  - `G1`: Lt / Vc / Día (Diario o Mensual vs PPTO).
  - `G2`: Composición de leche: Grasa (%), Proteína (%), Sólidos (%) y Kg Sólidos / Vc / Día.
  - `G3`: Evolución de Precio por Litro ($ / Lt).
- Construcción de consultas y agregaciones necesarias para consumir los nuevos módulos de carga.

**Dependencias críticas:**
- `G2` depende de la construcción del módulo de Calidad de Leche y Litros retirados.
- `G3` depende de la definición final del módulo de Precio por Litro.

**Estimación HH:** `18 – 28` hrs.

---

## 5. Nuevos Módulos de Carga: Calidad de Leche, Litros Retirados y Precio por Litro

**Descripción:** Creación de estructura de datos, reglas de importación e interfaces de carga para información que no existe calculada en ERP Finnegans ni en la base local actual.

### 5.1. Objetivo funcional

Se requiere construir una base confiable para:
- almacenar la calidad diaria de leche por Cliente + Estanque + Fecha;
- almacenar los litros retirados diarios informados por las plantas;
- alimentar reportes comparativos contra lo digitado por usuarios en Módulo de Retiro y Módulo Producción de Leche;
- habilitar futuros gráficos de composición y precio por litro en Dashboard.

### 5.2. Pantallas de importación requeridas

Se requiere un importador para cada opción.

**Datos solicitados por el importador de Calidad/Litros:**
- Cliente
- Estanque
  - el selector debe mostrar solo los estanques del cliente seleccionado
- Selector de tipo de carga
  - Calidad de Leche
  - Litros
  - en Watts deben existir archivos separados
- Archivo a procesar

**Nota de corrección funcional:**
- ya no se solicitarán `Año` ni `Mes` antes de importar;
- el archivo Excel/CSV trae la fecha de cada fila;
- el importador debe recorrer todas las filas válidas del archivo y crear o actualizar según corresponda;
- el mismo archivo puede contener información de múltiples meses, por lo que no se debe asumir un archivo por período.

### 5.3. Regla de persistencia / actualización

La lógica general al procesar el archivo será:
- si no existe la PK `(Cliente, Estanque, Fecha)`, se crea el registro;
- si existe, se actualiza solo si cambió alguno de los valores de la fila;
- para soportar comparación eficiente, se puede construir un identificador/hash basado en las columnas relevantes de negocio;
- la fecha se toma desde cada fila del archivo, no desde filtros previos en pantalla;
- en el caso especial de **Watts**, donde el archivo de litros puede contener múltiples retiros en el mismo día por la hora, se debe consolidar primero por día y recién luego aplicar la lógica de comparación/upsert.

### 5.4. Tabla requerida: Calidad de Leche

La tabla debe incluir, como mínimo, las siguientes columnas:

| Columna | Observación |
|---|---|
| Cliente | FK o referencia al cliente/planta |
| Estanque | FK o referencia al estanque |
| Fecha | Parte de la PK lógica |
| Proteina | Porcentaje visible; internamente valor numérico <= 100 |
| Grasa | Porcentaje visible; internamente valor numérico <= 100 |
| Solidos | Calculado = Proteina + Grasa |
| UFC | Valor informado por planta |
| RCS | Valor informado por planta |
| Urea | Valor informado por planta |
| Crio | Se guarda, aunque por ahora no se use |
| Litros | Litros retirados diarios informados por planta |

**Notas funcionales:**
- `Proteina`, `Grasa` y `Solidos` son porcentajes; deben mostrarse como `%`, pero almacenarse como valores numéricos controlados.
- Ninguno de esos valores puede superar `100`.
- `Crio` se persiste aunque por ahora no participe en cálculos.
- `Litros` se almacena en la misma estructura lógica diaria para permitir cruces posteriores.

### 5.5. Formatos por cliente

#### A. Watts — Calidad de Leche

**Archivo:** `docs/inputs/mejoras_mar_abr_26/Watts - Formato Calidad de Leche.xls`

**Características:**
- archivo por estanque;
- títulos desde celda `A8`;
- se deben mapear los títulos de la fila 8;
- si cambian nombres esperados, se debe informar error de formato;
- si cambia el orden de columnas, el sistema debe adaptarse por mapeo.

**Títulos esperados (referenciales):**
- `Fecha`
- `Prote_`
- `Grasa`
- `UFC`
- `RCS`
- `Crio`
- `Urea`

**Mapeo esperado:**
- Columna A: `Fecha`
- Columna B: `Proteina`
  - la celda viene con formato porcentaje, por lo que internamente podría venir como `0,041` en vez de `4,1`; se debe validar y normalizar
- Columna C: `Grasa`
  - misma consideración de porcentaje que proteína
- Columna D: `UFC`
- Columna E: `RCS`
- Columna F: `Crio`
- Columna G: `Urea`

#### B. Watts — Recolección de Leche / Litros

**Archivo:** `docs/inputs/mejoras_mar_abr_26/Watts - Formato Recolección de Leche.xls`

**Características:**
- archivo por estanque;
- títulos desde celda `A8`;
- se deben mapear los títulos de la fila 8;
- si cambian nombres esperados, se debe informar error de formato;
- si cambia el orden, el sistema debe adaptarse por mapeo;
- es el único formato que contiene **hora de retiro**;
- puede existir más de una fila por día;
- antes de guardar, se deben **sumar y agrupar por día**, ignorando la hora.

**Títulos esperados (referenciales):**
- `Fecha`
- `Total Recolectado`
- `Hora de Retiro`
- `Temperatura`

**Mapeo esperado:**
- Columna A: `Fecha`
- Columna B: `Litros`
- Columna C: `Hora`
- Columna D: `Temperatura`

**Nota especial:**
- Solo a los archivos de Watts se les puede aplicar validación especial basada en comparación por contenido consolidado.

#### C. Nestlé — Resultados por Fecha

**Archivo:** `docs/inputs/mejoras_mar_abr_26/Nestle - Resultados por fecha.xlsx`

**Características:**
- archivo por estanque;
- contiene calidad y litros en un solo archivo;
- títulos desde celda `A2`;
- se deben mapear títulos por nombre;
- si cambian nombres esperados, se debe informar error de formato;
- si cambia el orden, el sistema debe adaptarse por mapeo.

**Títulos esperados (referenciales):**
- `Fecha`
- `Tanque`
- `Vol L`
- `UFC`
- `RCS`
- `SG %`
- `Proteinas %`
- `Urea`
- `Cryo`

**Mapeo esperado:**
- Columna A: `Fecha`
- Columna B: `Tanque`
  - no se usa / no se guarda
- Columna C: `Colecta`
  - no se usa / no se guarda
- Columna D: `Litros diarios`
- Columna E: `UFC`
- Columna F: `RCS`
- Columna G: `Grasa`
- Columna H: `Proteina`
- Columna I: `Urea`
- Columna J: `Cryo`

#### D. Soprole — Formato por Fundo

**Archivo:** `docs/inputs/mejoras_mar_abr_26/Soprole - Formato por Fundo.csv`

**Características:**
- archivo CSV separado por `;`;
- contiene calidad y litros en un solo archivo;
- la fila 1 contiene títulos;
- se deben mapear títulos por nombre;
- si cambian nombres esperados, se debe informar error de formato;
- si cambia el orden, el sistema debe adaptarse por mapeo.

**Títulos esperados:**
- `fecha`
- `litros`
- `grasa_gl`
- `proteina_gl`
- `urea`
- `UFC`
- `RCS`

**Reglas especiales:**
- `Grasa` y `Proteina` se deben dividir por `10` para obtener el valor porcentual correcto.

### 5.6. Reglas de transformación y validación

**Calidad de Leche:**
- calcular `Solidos = Proteina + Grasa`;
- validar que `Proteina`, `Grasa` y `Solidos` queden en rango lógico;
- soportar normalización de porcentajes en formatos que vengan como `0,041` o `4,1`;
- validar encabezados por nombre, no por posición fija.

**Litros retirados:**
- para Watts, consolidar por día sumando todas las líneas del mismo día;
- la hora no se guarda en tabla final diaria;
- `Litros` quedará disponible para cruce con producción y retiro digitado por usuarios.

**Persistencia:**
- aplicar estrategia idempotente de importación;
- registrar si la fila fue creada, actualizada o ignorada por no tener cambios;
- manejar errores de formato diferenciando:
  - encabezado inválido;
  - tipo de archivo incorrecto;
  - celdas obligatorias vacías;
  - valores numéricos fuera de rango.

### 5.7. Relación con Dashboard y reportes

Este módulo habilita:
- gráfico de composición de leche en Dashboard;
- cálculo de sólidos por día/mes;
- futuros cruces entre:
  - Módulo Retiro
  - Módulo Producción de Leche
  - datos informados por planta en Excel
- base de datos confiable para contrastar litros producidos vs litros retirados.

### 5.8. Subcomponente: Precio por Litro

Se requiere una pantalla con **2 formas de registrar** la información de Precio por Litro.

#### A. Carga desde Excel

**Datos solicitados por pantalla:**
- Cliente
- Fundo
  - se obtiene mediante join con relación `Estanque - Cliente`
- Archivo Excel

**Columnas requeridas en el Excel:**
- `Año`
- `Mes`
- `Precio Real`
- `Precio Proyectado`

**Regla de persistencia:**
- si la PK del registro existe, se actualiza;
- si no existe, se crea;
- el importador debe recorrer todas las filas del Excel;
- el Excel puede contener múltiples meses;
- no corresponde pedir `Año` ni `Mes` antes de importar, porque vienen en el archivo.

#### B. Digitador Manual

**Datos solicitados por pantalla:**
- Cliente
- Fundo
- Año
- Mes
- Precio Real
- Precio Proyectado

**Regla funcional:**
- debe permitir crear registros manualmente;
- debe permitir editar un registro existente.

#### C. Modelo funcional esperado

La estructura de Precio por Litro debe quedar asociada, al menos, a:
- Cliente
- Fundo
- Año
- Mes
- Precio Real
- Precio Proyectado

La PK lógica esperada para este módulo es:
- `(Cliente, Fundo, Año, Mes)`

**Uso esperado:**
- alimentar el gráfico evolutivo de Precio por Litro en Dashboard;
- mantener histórico mensual;
- permitir carga masiva por Excel y ajuste puntual por digitador.

### 5.9. Estimación interna del componente

| Subtarea | HH Mín | HH Máx |
|---|:---:|:---:|
| Modelo de datos y tablas base para calidad/litros/precio | 6 | 10 |
| Pantalla de importación y UX de selección Cliente/Estanque/Tipo/Archivo para Calidad-Litros | 6 | 10 |
| Lógica transversal de parser, validación y upsert idempotente para Calidad-Litros | 8 | 12 |
| Importador Watts Calidad | 5 | 8 |
| Importador Watts Litros con consolidación diaria | 5 | 8 |
| Importador Nestlé | 4 | 6 |
| Importador Soprole | 4 | 6 |
| Pantalla Precio por Litro — carga Excel + parser/upsert | 6 | 10 |
| Pantalla Precio por Litro — digitador manual + edición | 5 | 8 |
| Consumo en Dashboard, pruebas y ajustes de consistencia | 5 | 8 |
| **Total Componente 5** | **54** | **86** |

**Estimación HH:** `54 – 86` hrs.

---

## 6. Reporte Producción vs Retiro

**Descripción:** Comparativa de la diferencia entre extracción total reportada y recogida del camión.

**Incluye:**
- Nuevo formulario fuera de Dashboard, tomando como base programática `@prodlechereporte.php`.
- Filtros por temporada vigente de leche.
- Vista diaria y mensual.
- Columnas principales:
  - Campo
  - Ordeña
  - Retiro
  - Diferencia
- Uso de litros informados desde archivos de planta como una de las fuentes oficiales de contraste.
- Alerta visual para variaciones mayores a `+-100` litros.
- Envío automático de email a responsables para advertir variaciones críticas.
- Configuración del tenant de Microsoft Graph reutilizando experiencia/código previo de O365.

**Estimación HH:** `22 – 34` hrs.

---

## Resumen de Estimación

| # | Componente | HH Mínimo | HH Máximo |
|---|---|:---:|:---:|
| 1 | Integración Finnegans y EndPoints | 6 | 10 |
| 2 | Dashboard — Lógica Transversal y Filtros | 18 | 26 |
| 3 | Módulo Suplementación | 14 | 22 |
| 4 | KPIs Producción (Gráficos Dashboard) | 18 | 28 |
| 5 | Carga de Calidad de Leche, Litros Retirados y Precio por Litro | 54 | 86 |
| 6 | Reporte Producción vs Retiro | 22 | 34 |
| | **TOTAL ESTIMADO** | **132** | **206** |

> [!NOTE]
> El rango refleja la variabilidad natural del proyecto: complejidad real de las plantillas por cliente, necesidad de ajustes en validaciones y comportamiento final de las pantallas de Precio por Litro.
> No incluye QA formal separado, capacitación de usuarios, documentación de entrega exhaustiva ni gestión de proyecto.

---

## Próximos Pasos

1. Validar con cliente que el detalle funcional de `Calidad de Leche` y `Litros` por planta quedó correcto.
2. Validar con cliente si el `Fundo` de Precio por Litro quedará efectivamente resuelto desde la relación `Estanque - Cliente` o si requiere una entidad propia.
3. Definir si el desarrollo se ejecuta completo o por fases, recomendando partir por:
   - Componente 5
   - Componente 6
   - luego consumo en Dashboard
4. Una vez aprobada la estimación, iniciar por la base de datos e importadores, ya que son la dependencia crítica del resto de gráficos y reportes.
