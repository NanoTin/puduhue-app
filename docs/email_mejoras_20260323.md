# Email — Aclaraciones Reunión Mejoras Web (23-03-2026)

> **Para**: Alexis
> **Asunto**: Aclaraciones sobre mejoras web discutidas en reunión del 23/03

---

Hola Alexis,

Espero que estés bien. Junto con saludar, te escribo para dar seguimiento a la reunión del pasado 23 de marzo donde conversamos sobre las nuevas mejoras para la web. Logré ordenar los temas y quedaron algunas dudas que necesito aclarar contigo para poder avanzar con la propuesta de horas-hombre.

A continuación, te resumo los puntos conversados y las preguntas agrupadas por tema:

---

## 1. Reporte de Producción de Leche — Recuadro No Planta/Venta ✅

Este punto quedó claro. Se adicionará un recuadro al final del reporte con los datos de vacas y litros que **no** van a Planta/Venta:

- Vacas Último Día
- Litros Último Día
- Vacas Promedio Mes
- Litros Acumulado Mes

**No requiere respuesta para este punto.**

---

## 2. EndPoint Propio (Proxy Finnegans — HACREPORTELECHE)

Ya tengo el código M de Power BI y confirmamos que será consumido solo por Power BI, con parámetros configurables y el JOIN de PreciosProyectado se mantiene en Power BI.

Solo me quedaron dos consultas:

**2.1.** De los parámetros del reporte HACREPORTELECHE (`fechaDesde`, `fechaHasta`, `producto`, `Pestablecimiento`, `deposito`, `organizacion`, `numeroPartida`, `circuitocontable`, `Empresa`, `TipoPrecio`, `Moneda`), **¿todos deben ser configurables o hay algunos que siempre usan un valor fijo?** Por ejemplo, ¿`Empresa` siempre es `2`?

**2.2.** La tabla `PreciosProyectado` que usan en Power BI para el JOIN, **¿de dónde se alimenta?** ¿Es un Excel o tabla manual? Pregunto porque a futuro podría incorporarse directamente en la App.

---

## 3. Dashboard — Gráficos y Tarjetas

Este es el punto donde quedaron más dudas. Actualmente el Dashboard tiene tarjetas de resumen y gráficos de producción y vacas a nivel mensual. Se conversó agregarle más visualizaciones:

### 3.1. Litros / Vaca / Día

Entendí que se busca un gráfico de líneas comparando real vs. presupuesto, pero necesito aclarar:

**3.1.a.** ¿El eje X del gráfico es por **día** (dentro de un mes) o por **mes** (dentro de una temporada)?

**3.1.b.** Si es diario: ¿se compara cada día contra el presupuesto mensual dividido por los días del mes?

**3.1.c.** Si es mensual: ¿se muestra el promedio real del mes vs. el presupuesto del mes?

**3.1.d.** ¿Se muestra consolidado de todos los fundos o se requiere filtrar por fundo?

### 3.2. Suplementación

**3.2.a.** ¿Se requiere ver la **dosis/dieta diaria** o un **consolidado mensual**?

**3.2.b.** Las dosis se miden en Kg generalmente, pero hay alimentos como "SILO PARVA PASTO COMPRADO" que se registran en **unidades**. Para estos casos, **¿las unidades se deben transformar a Kg con alguna fórmula?**

**3.2.c.** Revisando los datos, encontré un consumo de **16.600 unidades de Silo** el 24-03-2026 en Casa Anita. ¿Están ingresando correctamente como unidades o esos 16.600 corresponden a **Kilos que están ingresando en el campo equivocado**?

**3.2.d.** ¿Cómo se visualiza este dato? ¿Tarjeta, gráfico de barras por alimento, o tabla?

**3.2.e.** ¿Se necesita comparar contra algún presupuesto o plan de alimentación?

### 3.3. KPI Producción

Este punto quedó muy abierto en la reunión:

**3.3.a.** ¿Qué se quiere medir exactamente? Algunos ejemplos para orientar:
- % de cumplimiento vs. presupuesto
- Litros/vaca promedio mensual
- Tendencia de producción (crecimiento o decrecimiento)
- Eficiencia de conversión (alimento vs. producción)

**3.3.b.** ¿Qué tipo de visualización? ¿Una tarjeta con valor y porcentaje? ¿Un gráfico de línea con meta?

**3.3.c.** ¿Se busca un KPI único consolidado o múltiples KPIs por fundo?

### 3.4. Diferencia Litros Producidos vs. Litros Retirados

**3.4.a.** ¿Se requiere la comparación **mensual** o **diaria**?

**3.4.b.** ¿Aplica el filtro por temporada del Dashboard?

**3.4.c.** ¿Qué tipo de gráfico? ¿Barras agrupadas (producido vs. retirado)? ¿Líneas superpuestas? ¿Tarjeta con la diferencia?

**3.4.d.** ¿Se debería alertar cuando la diferencia supera un umbral (ej: si se retira más de lo producido)?

### Pregunta general del Dashboard

**3.5.** Para los elementos nuevos que aún no tienen visualización definida, ¿tienen alguna preferencia general? ¿Gráficos de barras, líneas o tarjetas? Puedo proponer alternativas según el tipo de dato, pero me ayudaría saber hacia dónde se inclinan.

---

## 4. Calidad de Leche

Se conversó sobre incorporar datos de Grasa, Proteína y Sólidos, que existen como atributos en Finnegans pero no fueron incluidos en el desarrollo.

**4.1.** ¿El dato de calidad es un **consolidado mensual** o es **diario**? Si se ingresa solo el día 1 o el último del mes, ¿corresponde al promedio del mes?

**4.2.** ¿Cómo obtienen hoy la información de las plantas procesadoras?
- ¿Llega por email (PDF, Excel)?
- ¿Se obtiene de algún portal web?
- ¿Existe alguna API de las plantas?

**4.3.** Mencionaron que tienen una **app en Python que hace web scraping** a la página de Colún. ¿Se desea integrar esa lógica en la App Web o mantenerla separada y solo importar los resultados?

**4.4.** ¿Y el **resto de las plantas** (no solo Colún)? ¿Cuántas son y todas entregan la información en el mismo formato?

**4.5.** ¿Se requiere un módulo completo de Calidad de Leche? Esto implicaría una tabla para almacenar datos, pantalla de ingreso (o importación automática), visualización en Dashboard y/o reporte dedicado, e historial con tendencias.

**4.6.** ¿Existen rangos o estándares de calidad contra los cuales comparar? (ej: Grasa mínima > 3.2%, Proteína > 3.0%).

---

## 5. Precio por Litro

**5.1.** ¿Es un dato que deben ver **todos los usuarios** en el Dashboard, o requiere restricción por rol?

**5.2.** ¿Se requiere el **historial** del precio o solo el precio vigente del mes?

**5.3.** ¿De dónde se obtiene el precio? ¿Lo definen ustedes manualmente? ¿Viene de las plantas? ¿Del ERP Finnegans? ¿Varía por planta?

**5.4.** ¿El precio es el mismo para todos los fundos o varía según la planta de destino?

**5.5.** ¿Tiene relación con los datos de calidad? (Algunas plantas pagan diferente según grasa/proteína).

---

## Resumen

El **punto 1** y el **punto 2** ya están bastante claros y se pueden avanzar. Los puntos **3, 4 y 5** necesitan definición para poder dimensionar correctamente las horas de desarrollo.

Si te resulta más fácil, podemos agendar una llamada corta para resolver las dudas en vez de responderlas por escrito. Lo que sea más práctico para ti.

Quedo atento, saludos.
