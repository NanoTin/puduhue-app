# Propuesta de Horas-Hombre (HH) - Mejoras de Dashboard y Nuevos Módulos
**Fecha de Análisis:** 06 de Abril de 2026
**Basado en:** Respuestas de correo/reunión (23/03/2026) y Notas Complementarias de definición técnica.

---

Este documento consolida el levantamiento técnico y los requerimientos funcionales solicitados por el cliente, organizados de manera modular para facilitar su estimación en Horas-Hombre (HH). No incluye desarrollo, solo la especificación estructurada.

## 1. Integración Finnegans y EndPoints
**Descripción**: Requerimientos relacionados con la extracción de datos desde Finnegans y disponibilización a Power BI.
* **EndPoint HACREPORTELECHE**: 
  * Se deben enviar los parámetros tal cual fueron especificados.
  * Única modificación permitida: `FechaHasta`. Si se deja vacío por defecto, el sistema enviará la fecha correspondiente al último día.
* **Tabla PreciosProyectados**: Su origen y manejo continuará dentro de Power BI (fichero Excel).
* **Nota Complementaria**: No se requiere efectuar cruces ni desarrollos adicionales del lado del backend.
* **Estimación HH:** `[   ]` hrs.

## 2. Dashboard - Lógica Transversal y Filtros
**Descripción**: Habilitación de la visualización dinámica Mensual/Diaria en `dashboard.php`, impactando a todos los indicadores presentes.
* **Selector Visual (Diario / Mensual)**: Inserción de componente visual (botón/toggle) en la cabecera.
* **Filtros Vista Diaria**: Al activar "Diario", se despliegan condicionales de filtrado extra `[Año, Mes]` (por defecto, inicializados en `Now()`).
* **Variables de Sesión**: La última selección del usuario en fecha/visión (Día/Mes) se debe almacenar en sesión para que sea persistente en la navegación del usuario logueado.
* **Filtro Temporada**: Aplicación por defecto de un filtro de la temporada vigente de leche (`temporadatipocodigo = 'LECHE' and temporadaactivo = 1`).
* **Presupuesto (PPTO) en Vista Diaria**: Dado que el Ppto de leche cargado en sistema (`01_table_pptoleche.sql`) es mensual, se aplicará una división uniforme para mostrar un valor comparativo cada día en los gráficos de visualización diaria.
* **Filtro de Fundos (Consolidado vs Individual)**: Se hereda la misma lógica existente. Se muestran los fundos acorde al perfil de usuario, con opción por defecto a la vista "TODOS".
* **Estimación HH:** `[   ]` hrs.

## 3. Módulo Suplementación
**Descripción**: Reestructuración de visualización de dosis / dietas proveniente de Finnegans (siempre valorizado en Kg).
* **Parte 1: Reporte Individual (Fuera de Dashboard)**
  * Nuevo reporte diario que detalle por "categoría de ítem".
  * **Nuevo Maestro**: Dado que no existe actualmente un agrupador, se debe desarrollar una tabla maestra de "Categorías de Ítems" y asociarla al maestro de ítems actual.
* **Parte 2: Gráficos dentro de Dashboard**
  * Extracción y grafado directamente desde los datos integrados con Finnegans.
  * **Vista Mensual**: Eje X (Años-Meses de temporada) vs Eje Y (Promedio lineal del mes).
  * **Vista Diaria**: Eje X (Días del mes seleccionado) vs Eje Y (Suma acumulada de `suplanimaldetalle.dosisporanimal`).
  * *Nota*: Para los planes y presupuestos de alimentación, no se realizarán proyecciones cruzadas en esta iteración.
* **Estimación HH:** `[   ]` hrs.

## 4. KPIs Producción (Gráficos Dashboard)
**Descripción**: Modificaciones sobre métricas productivas actuales y sumatoria de nuevos componentes visuales en `dashboard.php`.
* **Tarjetas Summary Activas**: Sin modificaciones lógicas directas. 
* **Gráficos de Línea Actuales**: Incorporación de líneas de visualización de "Presupuesto", sumado al toggle de visualización Diaria/Mensual.
* **Nuevos Gráficos Proyectados**:
  * *G1*: Lt / Vc / Día (Comportamiento Diario o Mensual vs Ppto).
  * *G2*: Gráfico de Composición - Grasa (%), Proteína (%), Sólidos (%) y Kg Sólidos / Vc / Día.
  * *G3*: Gráfico Evolutivo de Precios ($ / Lt).
* **Nota Complementaria Crítica**: Los datos para *G2* y *G3* NO existen calculados en el modelo de BD, lo que activa los siguientes 2 puntos.
* **Estimación HH:** `[   ]` hrs.

## 5. Nuevos Módulos de Carga: Calidad & Precios
**Descripción**: Creación de estructura de datos y pantallas de importación basadas en plantillas Excel estandarizadas, ante la falta de cálculos directos de estos en ERP Finnegans o base local.
* **Módulo Calidad de Leche (Excel)**
  * Importador base que identifique si el registro de ese día/mes se actualiza o se crea.
  * Las métricas mensuales expuestas como un ponderado por litros producidos del campo.
* **Módulo Precio por Litro (Excel)**
  * Importador de precios por planta. Los parámetros son resueltos en Excel.
  * Restringido a visualización según usuarios. Entregará datos al Dashboard.
  * Requerirá relacionar la información proveniente del Excel a la tabla de clientes existente (`01_table_clientes.sql`), para modelar las diferentes Plantas.
* **Estimación HH:** `[   ]` hrs.

## 6. Reporte Producción vs Retiro
**Descripción**: Comparativa de la diferencia entre extracción total reportada y recogida del camión.
* **Nuevo Formulario (Fuera de Dashboard)**
  * Tomar como base programática `@prodlechereporte.php`.
  * Filtros de temporalidad: Temporada actual vigente (leche), opciones diaria y mensual.
  * Columnas Principales: Campo | Ordeña | Retiro | Diferencia.
* **Módulo de Notificación y Alertas**
  * Alerta visual para variaciones mayores a +-100 Litros de leche.
  * Envío automático de Email a responsables para advertir variaciones críticas.
  * *Nota de Setup*: Se requiere configurar el Tenant de Microsoft Graph (utilizando código y experiencia previa / cuenta O365).
* **Estimación HH:** `[   ]` hrs.

---

**Siguientes Pasos Reales:**
1. Solicitar las plantillas de Excel de "Calidad de Leche" y "Precio por Litro" para mapear importadores.
2. Definir valores referenciales de HH por cada ítem listado.
3. Envío al cliente para validación y aprobación de tiempo invertido.
