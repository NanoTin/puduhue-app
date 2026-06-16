# Módulo de Combustible — Levantamiento y Definiciones

> [!NOTE]
> Documento de trabajo para ordenar el nuevo requerimiento del cliente.
> Los ítems marcados con ❓ requieren confirmación con el cliente.
> Los ítems marcados con 💡 son propuestas/sugerencias del equipo de desarrollo.
> Los ítems marcados con ✅ son definiciones preliminares suficientemente claras para estimar.

**Fecha de análisis:** 14 de Abril de 2026
**Fuentes consideradas:**
- Requerimiento entregado por el cliente
- Excel histórico `docs/inputs/mejoras_mar_abr_26/Registro carga de petróleo Puduhue(1-1498).xlsx`
- Documentación existente en `docs/` sobre compras, alimentación e integración con Finnegans

---

## 1. Objetivo del Módulo

Registrar el consumo de combustible de vehículos y maquinarias de la empresa, con trazabilidad operativa y posterior integración con Finnegans como consumo de productos desde bodega.

La intención no es solo reemplazar el Microsoft Forms actual, sino dejar el proceso controlado, validado, auditable y consistente con la arquitectura ya existente en Puduhue App Web.

---

## 2. Proceso Actual Levantado

### 2.1. Fuente actual

✅ Hoy la información se registra mediante un **Microsoft Forms**.

✅ La evidencia histórica disponible está en el Excel:
- `docs/inputs/mejoras_mar_abr_26/Registro carga de petróleo Puduhue(1-1498).xlsx`

### 2.2. Estructura observada en el Excel

El archivo contiene una tabla `A1:O1499`, es decir:
- **15 columnas**
- **1.498 registros de datos**

### 2.3. Campos actuales del formulario

| Campo actual | Observación |
|---|---|
| ID | Identificador del registro en Forms/Excel |
| Hora de inicio | Metadato del Forms |
| Hora de finalización | Metadato del Forms |
| Correo electrónico | Metadato del Forms |
| Nombre | Metadato del Forms |
| Nombre operario | Dato de negocio |
| Fecha | Dato de negocio |
| Maquinaria | Dato de negocio |
| Unidad de registro | Kilómetros u Horas |
| Cantidad de Kilómetros/Horómetro | Dato numérico |
| Digitador estanque | Dato numérico, significado aún no completamente claro |
| Carga (Litros) | Dato numérico |
| Trabajo | Texto libre |
| Centro | Lugar/bomba/estación |
| Carga de petróleo | Entrada / Salida / Sin información |

### 2.3.1. Campos descartados para el desarrollo

✅ Con las aclaraciones posteriores del cliente:

- `Digitador estanque` queda descartado.
- `Centro` existe como entidad aparte, pero no se usará en este módulo.
- `Carga de petróleo` queda fuera de alcance.

### 2.4. Problemas detectados del proceso actual

💡 A partir del Excel y la descripción entregada, hoy existen varios problemas de calidad de datos:

- No hay maestro formal de vehículos/maquinarias.
- El campo `Maquinaria` se digita libremente, por lo que aparecen variantes del mismo activo (`Jd 6230`, `JD 6230`, `John Deere 6230`, etc.).
- El tipo de unidad (`Kilómetros`/`Horas`) depende del operador y puede prestarse a error.
- No hay validación robusta del valor actual versus el valor anterior.
- No existe una relación formal entre centro operativo, fundo y bodega ERP.
- No queda claro el uso real del campo `Carga de petróleo`.
- La integración con ERP hoy no está formalizada dentro de la app.
- La información operativa y la contable/logística están desacopladas.

### 2.5. Conclusión del levantamiento actual

✅ El proceso actual funciona como captura básica, pero no como sistema transaccional controlado.

💡 El principal salto de valor no es “hacer el mismo Forms dentro de la app”, sino:
- normalizar maestros,
- validar la transacción,
- controlar permisos,
- y dejar lista la integración con Finnegans reutilizando el concepto ya existente de consumo de productos.

---

## 3. Relación con la Arquitectura Actual

### 3.1. Reutilización de patrones existentes

✅ Este módulo debe integrarse al proyecto actual `Puduhue App Web`.

✅ Conviene reutilizar patrones ya existentes:
- selección de **Fundos por usuario** (`usuariosfundos`)
- selección de **Bodegas asociadas a Fundo**
- estados transaccionales tipo `PND / CN / ANL`
- botones de sincronización ERP
- tabla `sincronizacionerplog`
- servicios de integración a Finnegans

### 3.2. Relación con Alimentación

✅ El cliente indicó que la integración debe **reutilizar y unificar lo de alimentación**, porque el concepto final es el mismo: **consumo de productos desde bodega**.

💡 Esta es una decisión muy buena y debe respetarse desde el diseño:
- no conviene construir una integración “especial” para combustible;
- conviene modelar combustible como una variante del patrón de consumo;
- el producto consumido debiera salir de `invitems`, filtrado por módulo de uso `CMB`.

### 3.3. Relación con mejoras ya documentadas

✅ En `docs/modulo_compras_discovery.md` ya quedó propuesta la evolución del maestro de productos para clasificar por módulo de uso:

| Código | Módulo |
|---|---|
| `LCH` | Producción de Leche |
| `CMB` | Combustible |
| `ALM` | Alimentación |
| `BDG` | Bodega |

💡 Esto calza perfectamente con el nuevo módulo y conviene reutilizarlo para evitar otro atributo paralelo solo para combustible.

---

## 4. Solución Propuesta

## 4.1. Enfoque general

💡 Propuesta: construir el módulo de combustible como una **transacción propia**, con **maestros específicos** y con una **capa de integración ERP reutilizable** basada en el concepto de consumo de productos.

Flujo propuesto:

```text
Maestro de Vehículos + Maestro de Funcionarios + Maestro de Centros/Bodegas
        ↓
Registro de Consumo de Combustible
        ↓
Validaciones de negocio
        ↓
Estado Pendiente ERP
        ↓
Sincronización manual perfilada
        ↓
Consumo registrado en Finnegans
```

## 4.2. Maestros propuestos

### A. Maestro Tipo de Vehículo

✅ Requerido.

Ejemplos:
- Camioneta
- Tractor
- Camión
- Retroexcavadora
- Generador
- Bomba

### B. Maestro de Funcionarios

✅ Requerido como fuente para responsable/operario.

💡 Si el **Maestro de Funcionarios** del módulo de compras se desarrolla antes, este módulo debe **reutilizarlo** en lugar de crear otro.

### C. Maestro de Vehículos

✅ Requerido y será el maestro central del módulo.

Campos base levantados:

| Campo | Uso |
|---|---|
| `vehiculoid` | PK |
| `vehiculocodigo` | Patente o código identificador visible |
| `vehiculodesc` | Marca/modelo/descripción |
| `invitemid_combustible` | FK al producto combustible en `invitems` |
| `vehiculotipocombustible` | Bencina / Petróleo |
| `vehiculounidmedtipo` | KM / HORAS |
| `funcionarioid_responsable` | Responsable actual |
| `tipovehiculoid` | FK al tipo de vehículo |
| `vehiculoemailalerta` | Opcional |
| `vehiculoactivo` | Vigencia |

💡 Mejora recomendada:
- agregar también código ERP o identificador externo si Finnegans lo exige más adelante;
- guardar un flag `permitehorometro` no sería necesario si ya existe `vehiculounidmedtipo`;
- el responsable debe **copiarse** en la transacción para mantener histórico, aunque luego cambie en el maestro.

### D. Centro de Carga / Estación de Combustible

✅ `Centro` es otra entidad, pero no se usará acá.

✅ En combustible se reutiliza la lógica ya aplicada en alimentación:
- `Fundo`
- `Bodega`

💡 Por lo tanto, este módulo no requiere maestro ni selector de `Centro`.

---

## 5. Transacción Propuesta: Consumo de Combustible

## 5.1. Definición

Una transacción representa una carga de combustible realizada a un vehículo o maquinaria en una fecha determinada, desde un centro/bodega determinado, dejando trazabilidad del medidor operativo del activo.

## 5.2. Campos funcionales mínimos

| Campo | Origen |
|---|---|
| Fecha | Ingresado por usuario |
| Fundo | Seleccionado según usuario logueado |
| Bodega | Seleccionada según Fundo |
| Vehículo | Seleccionado desde maestro |
| Responsable | Traído desde maestro de vehículo y guardado en histórico |
| Tipo de unidad | Traído desde maestro de vehículo |
| Valor anterior KM/Horas | Calculado desde última transacción válida |
| Valor actual KM/Horas | Ingresado por usuario |
| Litros cargados | Ingresado por usuario |
| Rendimiento KM/L | Calculado cuando aplique |
| Trabajo | Texto libre |
| Estado | Pendiente / Confirmado ERP / Error / Anulado |

## 5.3. Propuesta de estructura técnica

💡 La transacción puede modelarse como una sola cabecera, porque cada registro actual corresponde a una sola carga y a un solo vehículo.

Propuesta preliminar de columnas:

| Campo técnico sugerido | Comentario |
|---|---|
| `combustibleid` | PK |
| `empresaid` | Empresa |
| `fundoid` | Fundo |
| `invbodegaid` | Bodega origen |
| `centrocargaid` | Si finalmente `Centro` es maestro propio |
| `combustiblefecha` | Fecha transacción |
| `vehiculoid` | FK vehículo |
| `vehiculocodigo` | Snapshot histórico |
| `vehiculodesc` | Snapshot histórico |
| `vehiculotipocombustible` | Snapshot histórico |
| `vehiculounidmedtipo` | Snapshot histórico |
| `funcionarioid_responsable` | Snapshot histórico |
| `vehiculokmhorasinicial` | Valor base del maestro para iniciar histórico |
| `combvehcantactual` | KM/Horas actual |
| `combvehcantanterior` | KM/Horas anterior |
| `comblitros` | Litros cargados |
| `combvehkmxlt` | Rendimiento calculado si unidad = KM |
| `combtrabajo` | Motivo/uso |
| `combstatus` | Estado |
| `erpdocumentocod` | Documento ERP si aplica |
| `comberpsync` | Fecha/hora sync |
| columnas de auditoría | estándar del proyecto |

### 5.4. Datos que deben copiarse desde el maestro

✅ Se comparte la idea planteada por el cliente: al grabar la transacción se debe guardar una “foto” de los principales datos del vehículo.

💡 Esto es correcto porque:
- evita inconsistencias históricas,
- permite reportes fieles aunque cambie el maestro,
- y reduce joins para análisis operativos.

### 5.4.1. Valor inicial de KM/Horas

✅ Cuando un vehículo aún no tenga transacciones previas, el valor `anterior` debe salir desde el **Maestro de Vehículos**.

💡 Se debe agregar una columna específica, por ejemplo:
- `vehiculokmhorasinicial`

Esto debe pedirse al cliente para la carga masiva inicial del maestro.

Regla sugerida:

```text
Si existe transacción previa válida:
    anterior = última transacción del vehículo
Si no existe transacción previa:
    anterior = km_horas_inicial del maestro
```

## 5.5. Comportamiento en pantalla

✅ Flujo sugerido de UI:

1. Usuario selecciona `Fundo`.
2. Sistema filtra `Bodegas` del fundo.
3. Usuario busca `Vehículo`.
4. Sistema muestra:
   - Responsable
   - Descripción
   - Tipo de unidad
   - Tipo de combustible
   - Producto ERP asociado
   - Valor anterior KM/Horas
5. Usuario digita:
   - Fecha
   - Valor actual KM/Horas
   - Litros
   - Trabajo
6. Sistema calcula rendimiento cuando corresponda.

💡 Recomendación UX:
- el selector de vehículo debe ser con buscador, no un combo plano;
- al elegir vehículo debe verse claramente si usa `KM` o `HORAS`;
- si la unidad es `HORAS`, el campo de rendimiento debería cambiar de etiqueta o simplemente ocultarse si no aplica.

---

## 6. Reglas de Negocio y Validaciones

## 6.1. Validaciones confirmadas

✅ Si la unidad es `KM`, el valor actual no puede ser menor que el anterior.

💡 Por consistencia operativa, esta misma validación debe aplicar también cuando la unidad sea `HORAS`.

✅ No se puede editar:
- si ya fue sincronizado con ERP;
- o si ya pasaron más de 24 horas desde su creación.

## 6.2. Validaciones adicionales recomendadas

💡 Sugerencias importantes:

- Si la unidad es `HORAS`, el valor actual tampoco debiera ser menor que el anterior.
- `Litros` debe ser mayor que `0`.
- `Valor actual KM/Horas` debe ser mayor que `0`.
- El vehículo debe estar activo.
- La bodega debe pertenecer al fundo seleccionado.
- El producto combustible asociado al vehículo debe existir y estar activo.
- No permitir transacciones futuras.
- Si la diferencia entre actual y anterior es anómala, advertir antes de grabar.

## 6.3. Validaciones de calidad de dato recomendadas

💡 Para reducir errores reales de operación:

- advertencia si la diferencia KM/Horas es `0`;
- advertencia si los litros cargados exceden un umbral esperable por tipo de vehículo;
- advertencia si el rendimiento cae fuera de un rango histórico del mismo vehículo;
- advertencia si el usuario intenta cargar dos veces el mismo vehículo, mismo día y mismo valor de medidor.

Estas validaciones pueden ser:
- **bloqueantes** para errores claros;
- **advertencias** para casos “raros pero posibles”.

---

## 7. Integración con Finnegans

## 7.1. Principio de integración

✅ Debe reutilizarse el concepto de **consumo de productos** ya usado en alimentación.

💡 Propuesta técnica:
- no diseñar un endpoint/JSON nuevo si el patrón actual ya resuelve consumo de inventario;
- encapsular la lógica común en un servicio compartido o en `FinnegansClient.php`;
- dejar combustible como un nuevo origen funcional de la misma integración.

## 7.2. Dato clave para integrar

Para cada carga se necesita, como mínimo:
- producto consumido (`invitemid_combustible`)
- fundo/establecimiento ERP
- bodega ERP
- fecha
- cantidad consumida (`litros`)
- glosa/observación
- identificador externo del documento

✅ Se confirma además que el producto a consumir se define desde el **Maestro de Vehículos**, según su **Tipo de Combustible**.

💡 La asociación debe permitir obtener el **código ERP** del producto, no solo el ID local.

Campos sugeridos en el maestro:

| Campo sugerido | Uso |
|---|---|
| `vehiculotipocombustible` | Bencina / Petróleo |
| `invitemid_combustible` | FK local a `invitems` |
| `erpproductocodigo` | Código real usado en Finnegans |

## 7.3. Estructura preliminar del JSON

✅ El ejemplo entregado por el cliente permite inferir que la integración irá por una transacción tipo:

- `TransaccionSubtipoCodigo = "CONSPROD"`
- `TransaccionTipo = "OPER"`

Estructura base esperable:

```json
{
  "IdentificacionExterna": "CONSPROD - {id}",
  "TransaccionSubtipoCodigo": "CONSPROD",
  "TransaccionTipo": "OPER",
  "Fecha": "YYYY-MM-DD",
  "Descripcion": "",
  "EmpresaCodigo": "2",
  "OperacionItems": [
    {
      "ProductoCodigo": "COM008",
      "Cantidad": 77.95,
      "Precio": 0,
      "Descripcion": "",
      "DepositoOrigenCodigo": "BC2-1-2-01",
      "IdentificacionExterna": "CONSPROD - {id} Item-1",
      "DimensionDistribucion": [
        {
          "dimensionCodigo": "DIMBU",
          "tipoCalculo": "2",
          "distribucionItems": [
            { "codigo": "OTROS", "porcentaje": 100, "importe": 0 }
          ]
        },
        {
          "dimensionCodigo": "DIMCTC",
          "tipoCalculo": "2",
          "distribucionItems": [
            { "codigo": "codigo_centro_costo", "porcentaje": 100, "importe": 0 }
          ]
        }
      ]
    }
  ]
}
```

💡 Esto deja bastante encaminadas estas definiciones:

- `Cantidad` = litros cargados
- `ProductoCodigo` = producto ERP asociado al vehículo
- `DepositoOrigenCodigo` = bodega ERP del fundo seleccionado
- `Fecha` = fecha de la carga
- `IdentificacionExterna` = identificador propio de la app
- `DIMCTC` = dimensión a mapear con soporte Finnegans
- `DIMBU` = en el ejemplo usa `OTROS`, pero debe confirmarse si será fijo

## 7.4. Nivel de documento ERP a generar

❓ El ejemplo compartido muestra un documento con múltiples `OperacionItems`.

💡 Para combustible quedan dos alternativas:

1. 1 carga local = 1 documento ERP con 1 ítem.
2. N cargas locales = 1 documento ERP consolidado con N ítems.

💡 Recomendación inicial:
- partir con **1 carga local = 1 documento ERP**
- porque simplifica trazabilidad, reintentos y bloqueo de edición.

## 7.5. Riesgos de integración

❓ Hay puntos que deben confirmarse antes del desarrollo final:

- si cada carga genera un documento ERP individual o si puede agruparse;
- si `DIMBU = OTROS` será fijo para este módulo;
- de dónde sale exactamente el código `DIMCTC`;
- si el ERP requiere `WorkflowCodigo` u otros campos hoy nulos en el ejemplo;
- qué diferencia existe entre el JSON mínimo documentado y el JSON completo real.

## 7.6. Recomendación de implementación

💡 Separar el trabajo en dos capas:

1. **Transacción local robusta**
   - captura
   - validación
   - trazabilidad
   - reporting

2. **Sincronización ERP**
   - manual
   - perfilada
   - con log de respuesta
   - reintento en caso de error

Esto reduce riesgo y permite avanzar aunque Finnegans demore confirmaciones.

---

## 8. Permisos y Perfilamiento

## 8.1. Regla solicitada

✅ El botón de sincronizar con ERP debe estar perfilado.

✅ La necesidad aplica de forma transversal a la app, no solo a combustible.

## 8.2. Propuesta

💡 Agregar un nuevo atributo booleano en `usuarios`, por ejemplo:
- `puedesincronizarerp`

Y exponerlo también en:
- sesión web / contexto de usuario
- payload de login
- vistas que muestran botón `Sincronizar ERP`

## 8.3. Alcance de esta mejora

💡 Esta mejora debería aplicarse al menos en:
- Producción de Leche
- Alimentación
- Combustible
- cualquier futura transacción con botón manual de sincronización

Esto evita soluciones parciales distintas por módulo.

---

## 9. Mejoras Propuestas al Requerimiento Original

## 9.1. Mejoras funcionales

- Fijar `Unidad de Medida` en el maestro de vehículos, no en la transacción.
- Asociar cada vehículo a su producto combustible en `invitems`.
- Guardar también el código ERP del producto combustible.
- Agregar `KM_Horas inicial` al maestro de vehículos.
- Mantener histórico del responsable del vehículo en cada transacción.
- Calcular el valor anterior automáticamente a partir de la última carga válida.
- Incorporar observaciones y alertas por rendimientos anómalos.

## 9.2. Mejoras de calidad de datos

- Normalizar vehículos existentes del Excel antes de la carga inicial.
- Normalizar nombres de funcionarios/operarios.
- Pedir al cliente el valor inicial de `KM/Horas` para la carga masiva.
- Excluir `Digitador estanque` y `Carga de petróleo` del MVP.

## 9.3. Mejoras técnicas

- Reutilizar `funcionarios` si se implementa por compras.
- Reutilizar `invitems` con módulo `CMB`.
- Reutilizar `sincronizacionerplog`.
- Unificar el permiso de sincronización a nivel transversal.
- Diseñar la integración como extensión del patrón de alimentación, no como integración nueva aislada.

---

## 10. Propuesta de Alcance por Fases

💡 Recomendación para disminuir riesgo:

### Fase 1 — Base Operacional
- Maestro Tipo Vehículo
- Maestro Vehículos
- Reutilización/creación de Funcionarios
- Transacción de Consumo de Combustible
- Listado / Crear / Editar / Visualizar / Anular
- Validaciones de negocio

### Fase 2 — Integración ERP
- Asociación vehículo → producto combustible ERP
- Sincronización manual con Finnegans
- Log de sincronización
- Reintento y bloqueo por permisos

### Fase 3 — Calidad y control
- Alertas de rendimiento inusual
- Migración/carga inicial desde Excel histórico
- Reportes operativos por vehículo, centro, fundo y responsable

---

## 11. Preguntas Abiertas para el Cliente

1. ¿Para vehículos en `HORAS`, esperan algún cálculo equivalente a rendimiento o solo guardar horas y litros?
2. ¿Se permite anular una transacción ya sincronizada en ERP, o solo bloquearla localmente?
3. ¿La restricción de edición “más de 24 horas” aplica desde la creación exacta del registro o hasta el cierre del día siguiente?
4. ¿Existe un archivo maestro inicial de vehículos con código, descripción, tipo, responsable, tipo combustible, unidad y `KM/Horas inicial`?
5. ¿De dónde saldrá exactamente el código de `DIMCTC`: desde Fundo, desde Bodega, desde otra tabla local o desde una tabla espejo ERP?
6. ¿La dimensión `DIMBU` usará siempre el valor `OTROS` para combustible?
7. ¿Cada carga debe generar un documento ERP individual o se consolidarán varias cargas en un solo POST?
8. ¿`WorkflowCodigo` seguirá nulo para este caso o luego será obligatorio?
9. ¿El permiso nuevo de sincronización debe vivir en `usuarios`, en `perfiles`, o en ambos niveles?
10. ¿El JSON completo de Finnegans agrega campos realmente obligatorios o solo opcionales?

---

## 12. Cierre

✅ El requerimiento ya permite estructurar una propuesta seria de solución y una estimación de HH.

💡 La recomendación principal es tratar este módulo como una combinación de:
- **maestro de activos operativos**,
- **transacción de consumo**,
- e **integración ERP reutilizable**.

Eso deja a combustible alineado con el rumbo que ya se documentó para compras y con la lógica existente de alimentación/Finnegans.
