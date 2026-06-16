# Reunión Mejoras Web — 23 de Marzo 2026

> [!NOTE]
> Minuta de la reunión con el cliente del 23-03-2026. Los requerimientos aquí levantados se sumarán a las mejoras identificadas en [`code_audit_proposals.md`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/code_audit_proposals.md) para una propuesta unificada de HH.

**Estado**: 🟡 Pendiente de aclaración con el cliente
**Próximo paso**: Enviar email con ideas ordenadas + preguntas → documentar acuerdos y definiciones

---

## Índice

| # | Módulo | Estado |
|---|--------|--------|
| 1 | Reporte Producción Leche — Recuadro No Planta/Venta | ✅ Claro |
| 2 | Nuevo EndPoint propio (proxy Finnegans) | ✅ Claro |
| 3 | Dashboard — Gráficos y Tarjetas | ⚠️ Dudas pendientes |
| 4 | Calidad de Leche | ⚠️ Dudas pendientes |
| 5 | Precio por Litro | ⚠️ Dudas pendientes |

---

## 1. Reporte Producción Leche — Recuadro "No Planta / No Venta"

**Archivo**: [`prodlechereporte.php`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/apps/web-php/prodlechereporte.php)
**Estado**: ✅ Requerimiento claro

### Descripción

Adicionar un **recuadro al final del reporte** que muestre datos de vacas y litros cuya producción **NO** va a Planta ni a Venta. Actualmente el reporte consolida `prodlecheventatotlitros` y `prodlecheventatotvacas` (datos de venta/planta). Se requiere un bloque adicional con los datos excluidos.

### Datos del recuadro

| Campo | Definición |
|-------|-----------|
| **Vacas Último Día** | Cantidad de vacas no planta/venta del **último día ingresado** del mes seleccionado. |
| **Litros Último Día** | Litros no planta/venta del **último día ingresado** del mes seleccionado. |
| **Vacas Promedio Mes** | Suma de vacas no planta/venta de cada día, dividido por la cantidad de días con datos → **promedio del mes**. |
| **Litros Acumulado Mes** | Suma de litros no planta/venta de cada día → **acumulado del mes**. |

### Notas técnicas

- La tabla `prodlechedetalle` tiene relación con `prodlechetipos` que contiene el campo `prodlecheventa` (boolean). Los registros donde `prodlecheventa = 0` (o equivalente) son los que NO van a Planta/Venta.
- Se debe reutilizar la lógica existente de datos diarios (`$dailyData`) pero filtrando por tipo de leche no venta.
- "Último día ingresado" no necesariamente es el último del calendario, sino el último con datos registrados.

### Preguntas para el cliente

> No se identifican dudas para este punto. Requerimiento suficientemente claro.

---

## 2. Nuevo EndPoint Propio (Proxy Finnegans → HACREPORTELECHE)

**Archivos de referencia — Patrón API externa existente**:
- [`api-php/index.php`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/apps/api-php/index.php) — Entry point
- [`ProdlecheDetalleController.php`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/src/Controllers/Api/V1/ProdlecheDetalleController.php) — Controller de referencia
- [`ProdlecheDetalleApiService.php`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/src/Services/Api/ProdlecheDetalleApiService.php) — Service de referencia

**Documentación de arquitectura API**:
- [`docs/api-externa/README.md`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/api-externa/README.md) — Visión general y reglas de trabajo
- [`docs/api-externa/api-externa-estandar.md`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/api-externa/api-externa-estandar.md) — Contrato, versionado, respuesta JSON estándar
- [`docs/api-externa/api-externa-seguridad.md`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/api-externa/api-externa-seguridad.md) — Autenticación Bearer, logging, controles

**Estado**: ✅ Requerimiento claro — código M de Power BI ya disponible

### Descripción

Crear un **EndPoint propio de la App** que internamente consuma el endpoint de Finnegans `HACREPORTELECHE`, replicando la lógica que actualmente se realiza en **Power BI** con **código M** y una **función de token**. El objetivo es:

1. **Reutilizar** la autenticación con Finnegans que ya existe en la App (obtención de token OAuth).
2. Consumir el endpoint `https://api.finneg.com/api/reports/HACREPORTELECHE` con los parámetros necesarios.
3. Devolver la **misma estructura de columnas** para que el usuario no necesite cambiar su tabla en Power BI.
4. Seguir el patrón de la API externa propia: `POST /v1/{recurso}/query` con `Authorization: Bearer`, respuesta JSON estándar (`status`, `message`, `data`, `meta`).

### Código M de Power BI (referencia obtenida)

#### Función de Token (OAuth Client Credentials)

```
() =>
let
    Origen2 = Table.FromColumns({Lines.FromBinary(Web.Contents(
        "https://api.finneg.com/api/oauth/",
        [RelativePath="token?grant_type=client_credentials
            &client_id=XXXX
            &client_secret=XXXX"]
    ))}),
    token  = Table.ToList(Origen2),
    token2 = Lines.ToText(token as list),
    token3 = Text.Middle(token2, 0, 36)
in
    token3
```

> **Nota**: Esta lógica de token ya se resuelve en la App mediante el flujo existente (`erptokenactivo` / `FinnegansClient`). No es necesario reimplementarla; el nuevo endpoint debe reutilizar el token OAuth ya gestionado.

#### Query principal — HACREPORTELECHE

```
let
    URL1 = "https://api.finneg.com/api/reports/HACREPORTELECHE?ACCESS_TOKEN=",
    URL2 = "&PARAMWEBREPORT_fechaDesde=2025-07-01
            &PARAMWEBREPORT_fechaHasta=
            &PARAMWEBREPORT_producto=
            &PARAMWEBREPORT_Pestablecimiento=
            &PARAMWEBREPORT_deposito=
            &PARAMWEBREPORT_organizacion=
            &PARAMWEBREPORT_numeroPartida=
            &PARAMWEBREPORT_circuitocontable=
            &PARAMWEBREPORT_Empresa=2
            &PARAMWEBREPORT_TipoPrecio=0
            &PARAMWEBREPORT_Moneda=",
    token2   = TokenAutorizacion,
    URLfinal = Text.Combine({URL1, token2(), URL2}),
    Origen2  = Json.Document(Web.Contents(URLfinal)),
    ...
```

### Columnas del endpoint HACREPORTELECHE

El nuevo EndPoint debe devolver **exactamente estas columnas** para mantener compatibilidad con la tabla del usuario:

| Columna | Descripción/Uso |
|---------|----------------|
| `LUGARID` | — *(removida en el reporte final de Power BI)* |
| `ESTABLECIMIENTO` | Nombre del establecimiento/fundo |
| `DEPOSITOID` | — *(removida en el reporte final)* |
| `LOTE` | — *(removida en el reporte final)* |
| `PRODUCTOID` | — *(removida en el reporte final)* |
| `CATEGORIA` | Categoría del producto |
| `FECHA` | Fecha del registro (tipo `date`) |
| `DOCUMENTO` | — *(removida en el reporte final)* |
| `CABEZAS` | Cantidad de cabezas/vacas (`Int64`) |
| `PRODUCTO` | Nombre del producto |
| `DEPOSITOIDORIGEN` | — *(removida en el reporte final)* |
| `DEPOSITO` | Nombre del depósito |
| `LITROS` | Litros producidos (`number`) |
| `GRASA` | % Grasa |
| `UFC` | Unidades formadoras de colonia |
| `ACIDEZ` | Nivel de acidez |
| `PROTEINAS` | % Proteínas |
| `TEMPERATURA` | Temperatura |
| `CELSOMATICAS` | Células somáticas |
| `KGGRASA` | Kg de grasa |
| `KGPROTEINA` | Kg de proteína |
| `EVENTOHACIENDAID` | — *(removida en el reporte final)* |
| `EVENTO` | — *(removida en el reporte final)* |
| `TRANSACCIONID` | ID de transacción |
| `PRECIO` | Precio por litro |
| `IMPORTE` | Importe total |
| `@@CLASEVO` | Clasificación del evento |
| `COORDENADAS` | — *(removida en el reporte final)* |

> [!IMPORTANT]
> El usuario además agrega una columna calculada `MesAño` (formato `MM/yyyy`) y luego hace un `LEFT JOIN` contra una tabla `PreciosProyectado` por (`ESTABLECIMIENTO`, `MesAño`) para obtener `PrecioProyectado`. **Confirmado**: este join se resuelve en Power BI, no en el EndPoint.

### Parámetros del endpoint Finnegans

| Parámetro | Valor por defecto | Comentario |
|-----------|-------------------|------------|
| `fechaDesde` | `2025-07-01` | Fecha inicio de temporada |
| `fechaHasta` | *(vacío = hasta hoy)* | Fecha fin |
| `producto` | *(vacío = todos)* | Filtro por producto |
| `Pestablecimiento` | *(vacío = todos)* | Filtro por establecimiento |
| `deposito` | *(vacío = todos)* | Filtro por depósito |
| `organizacion` | *(vacío)* | — |
| `numeroPartida` | *(vacío)* | — |
| `circuitocontable` | *(vacío)* | — |
| `Empresa` | `2` | ID de empresa en Finnegans |
| `TipoPrecio` | `0` | — |
| `Moneda` | *(vacío)* | — |

### Patrón de arquitectura a seguir

Según la [documentación de la API externa](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/api-externa/README.md):

```
apps/api-php/index.php                              → Entry point (ya existente)
src/Routes/api.php                                   → Agregar nueva ruta
src/Controllers/Api/V1/HacReporteLecheController.php → [NUEVO] Controller
src/Services/Api/HacReporteLecheApiService.php       → [NUEVO] Service (consume Finnegans)
```

- **Autenticación saliente** (hacia Finnegans): Reutilizar el flujo OAuth existente (`erptokenactivo` / `FinnegansClient.php`).
- **Autenticación entrante** (del consumidor): `Authorization: Bearer` con token de `usuariosapitokens`, según el estándar definido en [`api-externa-seguridad.md`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/api-externa/api-externa-seguridad.md).
- **Respuesta**: JSON estándar con `status`, `message`, `data[]`, `meta{}`.

### Definiciones confirmadas por el cliente

| Pregunta | Respuesta |
|----------|-----------|
| ¿Quién consume el EndPoint? | **Solo Power BI** |
| ¿Parámetros configurables o fijos? | **Configurables** — el consumidor envía los filtros en el request body |
| ¿JOIN con PreciosProyectado? | **Se resuelve en Power BI**, no en el EndPoint |

### Ejemplo: Cómo Power BI consume la API existente (prodleche-detalle)

El cliente proporcionó el código M que usa actualmente para consumir el endpoint `prodleche-detalle/query`. Este mismo patrón de consumo será el que use para el nuevo endpoint HACREPORTELECHE:

```
let
    Url = "https://api.puduhue.cl/v1/prodleche-detalle/query",
    Token = "pudu_XXXX...",
    PageSize = 500,

    GetPage = (Page as number) as record =>
        let
            RequestBody = [ page = Page, page_size = PageSize ],
            Response = Web.Contents(Url, [
                Headers = [
                    #"Content-Type" = "application/json",
                    Accept = "application/json",
                    Authorization = "Bearer " & Token
                ],
                Content = Json.FromValue(RequestBody)
            ]),
            JsonResponse = Json.Document(Response),
            Data = try JsonResponse[data] otherwise {},
            Meta = try JsonResponse[meta] otherwise null,
            Total = try Meta[total_registros] otherwise 0
        in
            [ Page = Page, Data = Data, Total = Total ],

    FirstPage = GetPage(1),
    TotalPages = Number.RoundUp(FirstPage[Total] / PageSize),
    Pages = List.Generate(
        () => [CurrentPage = 1, Result = FirstPage],
        each [CurrentPage] <= TotalPages,
        each [ CurrentPage = [CurrentPage] + 1,
               Result = GetPage([CurrentPage] + 1) ],
        each [Result]
    ),
    AllDataLists = List.Transform(Pages, each [Data]),
    Combined = List.Combine(AllDataLists),
    Tabla = Table.FromRecords(Combined)
in
    Tabla
```

> **Observación**: El código M usa paginación (`page`, `page_size`, `total_registros`) con el patrón `List.Generate` para recorrer todas las páginas. El nuevo EndPoint HACREPORTELECHE debe soportar la misma paginación estándar para mantener consistencia.

### Preguntas pendientes para el cliente

1. **¿Todos los 11 parámetros de Finnegans** (`fechaDesde`, `fechaHasta`, `producto`, `Pestablecimiento`, `deposito`, `organizacion`, `numeroPartida`, `circuitocontable`, `Empresa`, `TipoPrecio`, `Moneda`) deben ser configurables, o hay algunos que siempre usan valor fijo (ej: `Empresa=2`)?
2. **¿La tabla `PreciosProyectado`** de Power BI de dónde se alimenta? ¿Es un Excel/tabla manual que mantiene el cliente? Esto es relevante para entender si a futuro se podría incorporar a la App.

### Preguntas técnicas internas

- Evaluar si `FinnegansClient.php` existente ya cubre la llamada a `api/reports/HACREPORTELECHE` o si requiere extensión para soportar endpoints de reportes.
- Definir si la información obtenida de Finnegans se cachea en BD local (para evitar llamadas repetidas) o se consulta en tiempo real en cada request.
- Considerar paginación: Finnegans devuelve todo el dataset de una vez; nuestro EndPoint debe paginar la respuesta localmente para mantener el mismo contrato (`page`, `page_size`, `total_registros`) que ya usan los otros endpoints.

---

## 3. Dashboard — Gráficos y Tarjetas

**Archivo**: [`dashboard.php`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/apps/web-php/dashboard.php)
**Estado**: ⚠️ Múltiples dudas pendientes

### Descripción general

Adicionar gráficos o tarjetas al Dashboard existente. Actualmente el Dashboard cuenta con:
- 4 tarjetas de resumen (cards)
- 1 gráfico de líneas: Producción mensual (Litros)
- 1 gráfico de líneas: Vacas mensual (promedio)

**Algunos** de los nuevos datos requieren ser **comparados contra Presupuesto de Leche Mensual** (datos mensuales: Vacas / Litros / Litros x Vaca).

### 3.1. Litros / Vaca / Día

**Duda principal**: El Dashboard filtra por **temporada**. ¿Se requiere el dato **diario** o el **promedio mensual** para comparar contra presupuesto mensual de Litros x Vaca?

> Según lo entendido en la reunión: sería un **gráfico de líneas** comparando lo ingresado (real) vs. presupuesto.

**Preguntas para el cliente**:
1. ¿El eje X del gráfico es por **día** (dentro de un mes) o por **mes** (dentro de una temporada)?
2. Si es diario: ¿Se compara cada día contra el presupuesto mensual dividido por días del mes (presupuesto prorrateado)?
3. Si es mensual: ¿Se muestra el promedio real del mes vs. presupuesto del mes?
4. ¿Se requiere filtro por fundo individual o se muestra consolidado?

### 3.2. Suplementación

**Duda principal**: ¿Qué información de suplementación se requiere mostrar?

**Preguntas para el cliente**:
1. ¿Se requiere la **dosis/dieta diaria** o un **consolidado mensual**?
2. Las dosis generalmente se miden en **Kg**, pero hay alimentos que se consumen por **unidades** (ej: "SILO PARVA PASTO COMPRADO") y se dividen por la cantidad de vacas. Para estos casos: **¿las unidades se deben transformar a Kg con alguna fórmula de conversión?**
3. **Dato sospechoso detectado**: Se encontró un consumo de **16.600 unidades de Silo** el 24-03-2026 en Casa Anita. ¿Están ingresando correctamente como unidades, o esos 16.600 corresponden a **Kilos ya transformados** que están siendo ingresados en el campo de unidades por error?
4. ¿Este dato se visualiza como **tarjeta**, **gráfico de barras** (comparativo por alimento) o **tabla**?
5. ¿Se necesita comparar contra algún presupuesto o plan de alimentación?

### 3.3. KPI Producción

**Duda principal**: Este punto quedó **muy ambiguo** en la reunión.

**Preguntas para el cliente**:
1. **¿Qué se quiere medir exactamente?** Ejemplos posibles:
   - % cumplimiento vs. presupuesto
   - Litros/vaca promedio mensual
   - Tendencia de producción (crecimiento/decrecimiento)
   - Eficiencia de conversión (alimento vs. producción)
2. **¿Qué tipo de visualización?** ¿Tarjeta con valor + porcentaje? ¿Gráfico de gauge? ¿Gráfico de línea con meta?
3. ¿Se busca un **KPI único consolidado** o **múltiples KPIs** por fundo?

### 3.4. Diferencia Litros Producidos vs. Litros Retirados

**Descripción**: Comparar los litros producidos (módulo de Producción de Leche) contra los litros retirados (módulo de Retiro de Leche).

**Preguntas para el cliente**:
1. **¿Periodicidad?** ¿Se requiere la comparación **mensual** o **diaria**?
2. ¿Aplica el **filtro por temporada** del Dashboard?
3. **¿Tipo de visualización?** ¿Gráfico de barras agrupadas (producido vs. retirado)? ¿Gráfico de líneas superpuestas? ¿Tarjeta con la diferencia neta?
4. ¿Se debe alertar cuando la diferencia supera un umbral? (ej: si se retira más de lo producido)

### Pregunta general del Dashboard

5. Para los elementos que aún no tienen tipo de visualización definido: **¿Preferencia general?** ¿Gráficos de barras, líneas o tarjetas? Se pueden proponer alternativas según el dato, pero se necesita una orientación del cliente.

---

## 4. Calidad de Leche (Nuevo Módulo)

**Estado**: ⚠️ Múltiples dudas pendientes

### Descripción

El ERP Finnegans tiene atributos diarios de calidad: **Grasa, Proteína y Sólidos**, que **no fueron incluidos** en el desarrollo actual. El cliente desea incorporar esta información.

### Lo comentado en la reunión

- El cliente quiere ingresar los datos el **día 1** o el **último día del mes**.
- Se mencionó que tienen una **app en Python que hace Web Scraping** a la página de **Colun** para obtener datos de calidad.
- La información de calidad proviene de las **plantas procesadoras**.

### Preguntas para el cliente

1. **¿El dato de calidad es un consolidado mensual o diario?** Si se ingresa solo el día 1 o el último del mes, ¿corresponde al promedio del mes?
2. **¿Cómo obtienen la información de las plantas procesadoras?**
   - ¿Llega por **email** (PDF, Excel)? → Se podría automatizar con **Power Automate** para parsear y guardar en base de datos.
   - ¿Se obtiene de un portal web? → Se podría reutilizar/mejorar la app de Web Scraping existente.
   - ¿Existe una API de las plantas?
3. **Colun — Web Scraping**: La app en Python actualmente hace scraping a Colún. ¿Se desea integrar esta lógica directamente en la App Web? ¿O se mantiene separada y solo se importan los resultados?
4. **¿Y el resto de plantas?** (no solo Colún). ¿Cuántas plantas procesadoras son? ¿Todas entregan la información en el mismo formato?
5. **¿Se requiere un módulo completo de Calidad de Leche?** Esto implicaría:
   - Tabla en BD para almacenar datos de calidad (Grasa %, Proteína %, Sólidos %, Fecha, Fundo, Planta).
   - Pantalla de ingreso manual (o importación automática).
   - Visualización en Dashboard o reporte dedicado.
   - Historial y tendencias.
6. **¿Existen rangos o estándares de calidad** contra los cuales comparar? (ej: Grasa mínima > 3.2%, Proteína > 3.0%).

---

## 5. Precio por Litro

**Estado**: ⚠️ Dudas pendientes

### Descripción

Se discutió la incorporación del Precio por Litro de leche en el sistema.

### Preguntas para el cliente

1. **¿Es un dato que deben ver todos los usuarios en la página de Dashboard?** ¿O requiere restricción por rol/perfil?
2. **¿Se requiere el historial del precio?** ¿O solo el precio vigente del mes?
3. **¿De dónde se obtiene el precio?**
   - ¿Lo define el cliente manualmente?
   - ¿Viene de las plantas procesadoras?
   - ¿Es un dato del ERP Finnegans?
   - ¿Varía por planta?
4. **¿Qué tipo de visualización representa mejor este dato?**
   - Si es solo el precio actual → **Tarjeta**.
   - Si se requiere historial → **Gráfico de líneas** (evolución del precio en el tiempo).
   - Si varía por planta → **Tabla comparativa** o **gráfico de barras**.
5. **¿El precio es el mismo para todos los fundos** o varía según la planta de destino?
6. **¿Tiene relación con los datos de calidad?** (Algunas plantas pagan distinto según grasa/proteína).

---

## Resumen de Preguntas Pendientes

| # | Tema | Pregunta clave | Estado |
|---|------|---------------|--------|
| ~~2~~ | ~~EndPoint Finnegans~~ | ~~¿Solo Power BI o también otros consumidores?~~ | ✅ Solo PBI |
| ~~2~~ | ~~EndPoint Finnegans~~ | ~~¿Parámetros configurables o fijos?~~ | ✅ Configurables |
| ~~2~~ | ~~EndPoint Finnegans~~ | ~~¿Join PreciosProyectado en EndPoint o PBI?~~ | ✅ En PBI |
| 2 | EndPoint Finnegans | ¿Todos los parámetros configurables o algunos fijos (ej: Empresa=2)? | ⚠️ Pendiente |
| 2 | EndPoint Finnegans | ¿Tabla PreciosProyectado de dónde se alimenta? | ⚠️ Pendiente |
| 3.1 | Lts/Vaca/Día | ¿Dato diario o promedio mensual? | ⚠️ Pendiente |
| 3.2 | Suplementación | ¿Qué dato se muestra? ¿Conversión de unidades a Kg? | ⚠️ Pendiente |
| 3.2 | Suplementación | Los 16.600 uds de Silo en Casa Anita ¿son Kg mal ingresados? | ⚠️ Pendiente |
| 3.3 | KPI Producción | ¿Qué se quiere medir? | ⚠️ Pendiente |
| 3.4 | Lts Producidos vs Retirados | ¿Mensual o diario? | ⚠️ Pendiente |
| 4 | Calidad de Leche | ¿Consolidado mensual o diario? ¿Fuente de datos? | ⚠️ Pendiente |
| 4 | Calidad de Leche | ¿Integrar scraping de Colun? ¿Resto de plantas? | ⚠️ Pendiente |
| 5 | Precio por Litro | ¿Quién lo ve? ¿Historial? ¿Fuente? | ⚠️ Pendiente |
| — | Dashboard general | Preferencia de visualización: barras, líneas o tarjetas | ⚠️ Pendiente |

---

## Notas Adicionales

### Relación con Auditoría de Código

Los requerimientos de esta reunión se complementan con las propuestas del documento [`code_audit_proposals.md`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/code_audit_proposals.md). En particular:

- **Propuesta 6.1** (Unificar código ERP duplicado): El nuevo EndPoint de Finnegans (punto 2) se beneficia directamente de esta mejora. Se recomienda implementar primero la unificación de `FinnegansClient.php` antes de construir el proxy.
- **Propuestas de Dashboard**: Las mejoras de dashboard (punto 3) podrían incluir las mejoras de UX propuestas en la auditoría (responsive, CSS, etc.).
- **Arquitectura API Externa**: El punto 2 debe seguir estrictamente la documentación en [`docs/api-externa/`](file:///d:/OneDrive/DevApps/Proyecto%20Puduhue%20App%20Web/docs/api-externa/) (estándar, seguridad, endpoints).

### Flujo de trabajo

```
[✅ Reunión 23-03-2026]
     ↓
[📧 Email al cliente con preguntas]   ← próximo paso
     ↓
[📝 Documentar acuerdos y definiciones]
     ↓
[📊 Cuantificar HH (mejoras reunión + auditoría)]
     ↓
[📋 Propuesta formal al cliente]
```
