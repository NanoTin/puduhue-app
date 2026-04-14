# Mapeo JSON Finnegans OC → Pre OC Puduhue

> [!NOTE]
> Análisis comparativo de los JSON GET (OC existente) y POST (documentación API) de Finnegans
> para diseñar la estructura de la Pre OC alineada al ERP.
>
> **Fuentes analizadas:**
> - **GET OCSS (Servicios)**: `OCSS - 9759` — 1 ítem, proveedor `77962608-3`
> - **GET OC (Insumos/Materiales)**: `OC - 4811` — 8 ítems, proveedor `76474349-0`
> - **POST (documentación API)**: plantilla con tipos de datos y FKs
>
> Leyenda:
> - **MATCH** = campo que ya existe o se puede mapear directamente en el proyecto.
> - **NUEVO** = campo que requiere crear maestro o columna nueva.
> - **FIJO** = valor que será constante o configurado en `.env`.
> - **CALC** = campo calculado.
> - **DEPENDE** = valor que cambia según tipo de OC (material vs servicio).
> - **❓ CONSULTAR** = requiere confirmación con soporte de Finnegans o con el cliente.

---

## 0. Comparativa: OC de Insumos vs OC de Servicios

> [!IMPORTANT]
> Se confirmó que Finnegans maneja **dos subtipos de OC**:
> - **`OC`** → Órdenes de Insumos / Materiales
> - **`OCSS`** → Órdenes de Servicios
>
> A continuación, las diferencias detectadas campo a campo.

### 0.1. Diferencias en Cabecera

| Campo                       | OC Insumos (`OC - 4811`)     | OC Servicios (`OCSS - 9759`)   | ¿Difiere? | Impacto |
|-----------------------------|-------------------------------|----------------------------------|-----------|---------|
| `IdentificacionExterna`     | `"OC - 4811"`                | `"OCSS - 9759"`                 | ✅ Sí     | El prefijo depende del subtipo |
| `TransaccionSubtipoCodigo`  | **`"OC"`**                   | **`"OCSS"`**                    | ✅ Sí     | **Crítico** — determina el tipo de OC en Finnegans |
| `TransaccionTipoCodigo`     | `"OPER"`                     | `"OPER"`                        | ❌ No     | Siempre `"OPER"` |
| `WorkflowCodigo`            | `"CPRA-INS-SERV"`            | `"CPRA-INS-SERV"`               | ❌ No     | Mismo workflow para ambos tipos |
| `CondicionPagoCodigo`       | `"120"`                      | `"30"`                          | ✅ Sí     | Varía por OC, no por tipo. Es dato del proveedor/negociación |
| `Descripcion`               | `""` (vacío)                 | `"SEGUN COTIZACION ADJUNTA"`    | ✅ Sí     | Servicios tiende a tener descripción; insumos no |
| `NumeroComprobante`         | `"014191"`                   | `"014485"`                      | ✅ Sí     | Correlativo diferente. ❓ ¿Lo genera Finnegans? |
| `Conceptos` (cantidad)      | **5** conceptos              | **6** conceptos                 | ✅ Sí     | Insumos no tiene `COMPRA-RET16` |
| Demás campos cabecera       | Idénticos                    | Idénticos                        | ❌ No     | |

### 0.2. Diferencias en Items

| Campo                       | OC Insumos                               | OC Servicios                      | ¿Difiere? | Impacto |
|-----------------------------|------------------------------------------|-----------------------------------|-----------|---------|
| `ProductoCodigo`            | `"FER028"` (repetido en 8 ítems)         | `"SMA005"` (1 ítem)              | ✅ Sí     | Un mismo producto puede repetirse con diferente destino |
| `Cantidad`                  | Rangos: 11,200 – 83,600                  | 1                                 | ✅ Sí     | Insumos en volumen alto |
| `CantidadPendiente`         | **Puede diferir** de `Cantidad`          | Igual a `Cantidad`               | ✅ Sí     | **Descubrimiento**: `CantidadPendiente` baja con recepciones parciales |
| `Precio`                    | 529 – 557 (bajo por unidad)              | 1,716,960 (alto por servicio)    | ✅ Sí     | Escala de precios muy diferente |
| `vinculacionDestino`        | `"RECEP-CPRA - 6953"` (algunos ítems)   | `null`                           | ✅ Sí     | **Descubrimiento**: vincula a documentos de Recepción de Compra |
| `Descripcion`               | Detallada por destino geográfico         | Descripción del servicio          | ✅ Sí     | Ítems de insumos se describen por ubicación |
| `IdentificacionExterna`     | `"OC - 4811 Item-N"` (N no secuencial)  | `"OCSS - 9759 Item-1"`          | ✅ Sí     | El N puede tener gaps (1,2,3,4,5,18,19,20) |

### 0.3. Diferencias en DimensionDistribucion

| Dimensión     | OC Insumos                          | OC Servicios                      | ¿Difiere? | Impacto |
|---------------|--------------------------------------|-----------------------------------|-----------|---------|
| `DIMPARFIN`   | **`"APR000"`** (todos los ítems)    | **`"CRM000"`** (todos los ítems) | ✅ Sí     | **La partida financiera cambia según tipo de OC** |
| `DIMCTC`      | Varía por ítem (ver tabla abajo)    | `"ROTATI-0002"` (1 ítem)        | ✅ Sí     | **Cada ítem puede tener su propio centro de costo** |
| `tipoCalculo` | `"2"` (porcentaje)                  | `"2"` (porcentaje)                | ❌ No     | Siempre porcentaje |
| `porcentaje`  | `100%` en todos                     | `100%` en todos                   | ❌ No     | ❓ ¿Puede dividirse? |

#### Centros de Costo observados en OC - 4811 (Insumos):

| Ítem | Descripción destino                  | `DIMCTC` código         | Patrón              |
|------|--------------------------------------|--------------------------|-----------------------|
| 1    | SAN MIGUEL                          | `LSM-0002`               | Lechería San Miguel   |
| 2    | COIPOMO                             | `LCO-0002`               | Lechería Coipomo      |
| 3    | QUICHITUE                           | `1-1-3-01`               | Código numérico       |
| 4    | COGOMO                              | `Cogomo-Reposición`      | Texto descriptivo     |
| 5    | CARDENAS Y SIGUEL                   | `1-1-2-02`               | Código numérico       |
| 18   | TRONADOR ROTATIVA                   | `ROTATI-0002`            | Rotativa              |
| 19   | TRONADOR AREA SECA                  | `ROTATI-0002`            | Mismo (ítems comparten CTC) |
| 20   | TRONADOR TRADICIONAL                | `TRADICIONAL-0002`       | Tradicional           |

> [!WARNING]
> **Hallazgos clave de la comparativa:**
> 1. `TransaccionSubtipoCodigo` **NO es fijo** → depende del tipo de OC (`"OC"` o `"OCSS"`).
> 2. `DIMPARFIN` (partida financiera) **cambia según el tipo**: `APR000` (insumos) vs `CRM000` (servicios).
> 3. `DIMCTC` (centro de costo) **varía por ítem**, no por OC. Cada línea puede ir a un centro de costo diferente.
> 4. `CantidadPendiente` **no siempre es igual a `Cantidad`** en el GET — indica recepciones parciales.
> 5. `vinculacionDestino` puede contener una referencia a un documento de **Recepción de Compra** (`RECEP-CPRA`).
> 6. El mismo `ProductoCodigo` puede repetirse en múltiples ítems si va a destinos diferentes.
> 7. La numeración de ítems (`Item-N`) puede tener gaps, no es secuencial.
> 8. Los `Conceptos` (retenciones) no son idénticos entre ambos tipos — `COMPRA-RET16` solo aparece en servicios.

---

## 1. Campos de Cabecera de OC — GET vs POST

| # | Campo Finnegans             | Ejemplo GET (real)         | POST (doc)                                  | Obligatorio | Tipo        |
|---|------------------------------|----------------------------|---------------------------------------------|-------------|-------------|
| 1 | `IdentificacionExterna`      | `"OCSS - 9759"`            | `""` (string)                               | Sí          | String      |
| 2 | `Fecha`                      | `"2026-03-10"`             | `"2026-03-20"` (date)                       | Sí          | Date        |
| 3 | `Proveedor`                  | `"77962608-3"`             | `"codigoFKBSOrganizacion"` (FK)             | Sí          | String (RUT)|
| 4 | `CondicionPagoCodigo`        | `"30"`                     | `"codigoFKBSCondicionPago"` (FK)            | Sí          | String      |
| 5 | `TransaccionTipoCodigo`      | `"OPER"`                   | `"codigoFKFAFTransaccionTipo"` (FK)         | Sí          | String      |
| 6 | `TransaccionSubtipoCodigo`   | `"OC"` / `"OCSS"`          | `"codigoFKFAFTransaccionSubtipo"` (FK)      | Sí          | String      |
| 7 | `WorkflowCodigo`             | `"CPRA-INS-SERV"`          | `"codigoFKBSWorkflow"` (FK)                 | ❓          | String      |
| 8 | `Descripcion`                | `"SEGUN COTIZACION..."`    | `""` (string)                               | No          | String      |
| 9 | `NumeroComprobante`          | `"014485"`                 | `""` (string)                               | ❓          | String      |
|10 | `MonedaCodigo`               | `"PES"`                    | `"codigoFKBSMoneda"` (FK)                   | Sí          | String      |
|11 | `EmpresaCodigo`              | `"2"`                      | `"codigoFKFAFEmpresa"` (FK)                 | Sí          | String      |
|12 | `Nombre`                     | `"OCSS - 9759"`            | `""` (string)                               | ❓          | String      |
|13 | `ComprobanteAdicional`       | `""`                       | `""` (string)                               | No          | String      |
|14 | `ProvinciaOrigenCodigo`      | `null`                     | `"codigoFKBSProvincia"` (FK)                | No          | String      |
|15 | `ProvinciaDestinoCodigo`     | `"RDLL"`                   | `"codigoFKBSProvincia"` (FK)                | ❓          | String      |
|16 | `FechaBaseVencimiento`       | `"2026-03-10"`             | `"2026-03-20"` (date)                       | ❓          | Date        |
|17 | `PorcentajeAnticipoFinanciero` | `0.00`                  | `1234.56` (number)                          | No          | Decimal     |
|18 | `ProductoAnticipoFinancieroCodigo` | `null`              | `"codigoFKBSProducto"` (FK)                 | No          | String      |
|19 | `CHL_ModVenta`               | `"1"`                      | `""` (string)                               | ❓          | String      |
|20 | `CHL_ViaTransporte`          | `null`                     | `"codigoFKCHLViaTransporte"` (FK)           | No          | String      |
|21 | `USR_Serv3`                  | `""`                       | `""` (string)                               | No          | String      |
|22 | `AFIPDGI_Cosecha`            | `"0"`                      | `""` (string)                               | No          | String      |
|23 | `USR_SolicitanteID`          | `null`                     | `"codigoFKBSPersona"` (FK)                  | No          | String      |
|24 | `CHL_CantBultos`             | `0`                        | `1` (int)                                   | No          | Int         |
|25 | `CHL_FolioRef`               | `""`                       | `""` (string)                               | No          | String      |
|26 | `CHL_FechaRef`               | `null`                     | `"2026-03-20"` (date)                       | No          | Date        |
|27 | `CHL_TipoIndicadorTraslado`  | `"1"`                      | `""` (string)                               | No          | String      |
|28 | `IdentificacionExternaPadre` | `""`                       | `""` (string)                               | No          | String      |

---

## 2. Campos de Items de OC — GET vs POST

| # | Campo Finnegans             | Ejemplo GET (real)            | POST (doc)                         | Obligatorio | Tipo      |
|---|------------------------------|-------------------------------|------------------------------------|-------------|-----------|
| 1 | `ProductoCodigo`             | `"SMA005"`                    | `"codigoFKBSProducto"` (FK)        | Sí          | String    |
| 2 | `Cantidad`                   | `1.000000`                    | `1234.56` (number)                 | Sí          | Decimal   |
| 3 | `CantidadPendiente`          | `1.000000`                    | `1234.56` (number)                 | ❓          | Decimal   |
| 4 | `Precio`                     | `1716960.000000`              | `1234.56` (number)                 | Sí          | Decimal   |
| 5 | `Descripcion`                | `"ROTATIVA;favor generar..."` | `""` (string)                      | No          | String    |
| 6 | `ProvinciaOrigen`            | `null`                        | `"codigoFKBSProvincia"` (FK)       | No          | String    |
| 7 | `ProvinciaDestino`           | `"RDLL"`                      | `"codigoFKBSProvincia"` (FK)       | ❓          | String    |
| 8 | `FechaProximoPaso`           | `null`                        | `"2026-03-20"` (date)              | No          | Date      |
| 9 | `IdentificacionExterna`      | `"OCSS - 9759 Item-1"`       | `""` (string)                      | ❓          | String    |
|10 | `RegistroID`                 | `null`                        | `"codigoFKnull"` (FK)              | No          | String    |
|11 | `AplicaAnticipoFinanciero`   | `false`                       | `true` (bool)                      | No          | Boolean   |
|12 | `USRSolicitanteID`           | `null`                        | `"codigoFKBSPersona"` (FK)         | No          | String    |
|13 | `USRtransaccioncnid`         | `null`                        | `"codigoFKBSTransaccion"` (FK)     | No          | String    |
|14 | `vinculacionOrigen`          | `null`                        | `"valor"` (string)                 | No          | String    |
|15 | `ImporteExento`              | `0.0000`                      | `-1234.56` (number)                | No          | Decimal   |
|16 | `vinculacionDestino`         | `null`                        | `"valor"` (string)                 | No          | String    |

#### DimensionDistribucion (sub-array dentro de cada Item)

| # | Campo                    | Ejemplo GET                    | POST (doc)                              | Obligatorio |
|---|--------------------------|--------------------------------|-----------------------------------------|-------------|
| 1 | `dimensionCodigo`        | `"DIMPARFIN"`, `"DIMCTC"`      | String, si lo requiere la transacción   | Condicional |
| 2 | `distribucionCodigo`     | `"CRM000"`, `""`               | String, distribución por default         | No          |
| 3 | `tipoCalculo`            | `"2"` (= porcentaje)          | `"0"`, `"1"` (importe), `"2"` (%)       | Sí          |
| 4 | `distribucionItems[].codigo`    | `"CRM000"`, `"ROTATI-0002"` | Código de registro de la dimensión  | No          |
| 5 | `distribucionItems[].porcentaje`| `100.000000`               | Número                               | No          |
| 6 | `distribucionItems[].importe`   | `1716960.0000`             | Número                               | No          |

---

## 3. Conceptos (Impuestos y Retenciones)

| # | Campo                    | Ejemplo GET                    | POST (doc)    | Notas |
|---|--------------------------|--------------------------------|---------------|-------|
| 1 | `ConceptoCodigo`         | `"IVA19COMPRA"`                | FK a concepto | Código del concepto en Finnegans |
| 2 | `ConceptoImporte`        | `326222.0000`                  | Número        | Monto del impuesto/retención |
| 3 | `ConceptoImporteGravado` | `1716960.0000`                 | Número        | Base gravada |

**Conceptos observados en GET:**
| Concepto              | Importe     | Descripción probable              |
|-----------------------|-------------|------------------------------------|
| `IVA19COMPRA`         | 326,222     | IVA 19% sobre compras              |
| `COMPRA-RET12.25`     | 0           | Retención 12.25% (honorarios)      |
| `COMPRA-RET15.25`     | 0           | Retención 15.25%                   |
| `COMPRA-RET13`        | 0           | Retención 13%                      |
| `COMPRA-RET16`        | 0           | Retención 16%                      |
| `COMPRA-RET14`        | 0           | Retención 14%                      |

> [!IMPORTANT]
> Los conceptos de impuestos/retenciones parecen ser fijos del tenant de Finnegans. Se debe consultar cuáles aplican siempre y cuáles son opcionales. Para la Pre OC, probablemente baste con calcular el IVA 19% automáticamente y dejar los demás en 0.

---

## 4. Mapeo Pre OC Puduhue → JSON Finnegans

### 4.1. Cabecera

| Campo Pre OC (Puduhue)          | → Campo Finnegans                   | Estrategia                                           | Estado    |
|----------------------------------|--------------------------------------|------------------------------------------------------|-----------|
| `preoccod`                       | `IdentificacionExterna`              | Generar formato `"POC-XXXXXX"` o similar              | MATCH     |
| `preoccod`                       | `Nombre`                             | Mismo valor que `IdentificacionExterna`               | MATCH     |
| `preocfecha`                     | `Fecha`                              | Fecha de la Pre OC                                    | MATCH     |
| `preocfecha`                     | `FechaBaseVencimiento`               | ❓ ¿Misma fecha o fecha de vencimiento distinta?      | ❓ CONSULTAR |
| `proveedorrut` (nuevo)           | `Proveedor`                          | RUT del proveedor (formato `"XXXXXXXX-X"`)            | NUEVO     |
| `condicionpagocod` (nuevo)       | `CondicionPagoCodigo`                | ❓ Código ERP de condición de pago (ej. `"30"`)       | NUEVO     |
| — (fijo)                         | `TransaccionTipoCodigo`              | FIJO: `"OPER"` (según GET)                            | FIJO      |
| `preoctipo` (nuevo)              | `TransaccionSubtipoCodigo`           | `"OC"` si es material, `"OCSS"` si es servicio        | **DEPENDE** ⚠️ |
| `workflowcod` (nuevo)            | `WorkflowCodigo`                     | ❓ ¿Siempre `"CPRA-INS-SERV"` o depende del tipo?    | ❓ CONSULTAR |
| `preocdsc`                       | `Descripcion`                        | Descripción general de la OC                          | MATCH     |
| `preocnumcomprobante` (nuevo)    | `NumeroComprobante`                  | ❓ ¿Se genera aquí o en Finnegans?                    | ❓ CONSULTAR |
| `monedacod` (nuevo)              | `MonedaCodigo`                       | FIJO: `"PES"` (pesos chilenos). ❓ ¿Siempre CLP?     | FIJO      |
| `empresaiderp` (existente)       | `EmpresaCodigo`                      | Ya existe en tabla `empresas.empresaiderp`            | **MATCH** ✅ |
| — (fijo)                         | `ProvinciaDestinoCodigo`             | ❓ ¿Siempre `"RDLL"` o depende del fundo?             | ❓ CONSULTAR |
| — (null/vacío)                   | `ProvinciaOrigenCodigo`              | `null` en el GET. Probablemente no aplica.            | NULL      |
| —                                | `ComprobanteAdicional`               | Vacío                                                 | VACÍO     |
| —                                | `PorcentajeAnticipoFinanciero`       | `0.00`                                                | FIJO 0    |
| —                                | `ProductoAnticipoFinancieroCodigo`   | `null`                                                | NULL      |
| —                                | `CHL_ModVenta`                       | `"1"` (según GET)                                     | FIJO      |
| —                                | `CHL_ViaTransporte`                  | `null`                                                | NULL      |
| —                                | `USR_Serv3`                          | `""`                                                  | VACÍO     |
| —                                | `AFIPDGI_Cosecha`                    | `"0"`                                                 | FIJO      |
| —                                | `USR_SolicitanteID`                  | `null`. ❓ ¿Mapear al usuario que creó la Pre OC?     | ❓ CONSULTAR |
| —                                | `CHL_CantBultos`                     | `0`                                                   | FIJO 0    |
| —                                | `CHL_FolioRef`                       | `""`                                                  | VACÍO     |
| —                                | `CHL_FechaRef`                       | `null`                                                | NULL      |
| —                                | `CHL_TipoIndicadorTraslado`          | `"1"` (según GET)                                     | FIJO      |
| —                                | `IdentificacionExternaPadre`         | `""`. ❓ ¿Se usa si la OC tiene padre?                | VACÍO     |

### 4.2. Items

| Campo Pre OC Detalle (Puduhue)   | → Campo Finnegans Item             | Estrategia                                         | Estado    |
|-----------------------------------|------------------------------------|-----------------------------------------------------|-----------|
| `invitems.erpinvitemcod`          | `ProductoCodigo`                   | Ya existe en `invitems.erpinvitemcod`                | **MATCH** ✅ |
| `cantidad`                        | `Cantidad`                         | Directo                                              | MATCH     |
| `cantidad`                        | `CantidadPendiente`                | ❓ ¿Igual a `Cantidad` al crear?                     | ❓ CONSULTAR |
| `preciounitario`                  | `Precio`                           | Precio unitario neto (sin IVA)                       | MATCH     |
| `descripcion`                     | `Descripcion`                      | Descripción de la línea                              | MATCH     |
| `preoccod + "-Item-N"`            | `IdentificacionExterna`            | Generar como `"POC-XXXXXX Item-N"`                   | CALC      |
| — (fijo)                          | `ProvinciaDestino`                 | ❓ ¿Siempre `"RDLL"` o por ítem?                     | ❓ CONSULTAR |
| —                                 | `ProvinciaOrigen`                  | `null`                                               | NULL      |
| —                                 | `FechaProximoPaso`                 | `null`                                               | NULL      |
| —                                 | `RegistroID`                       | `null`                                               | NULL      |
| —                                 | `AplicaAnticipoFinanciero`         | `false`                                              | FIJO      |
| —                                 | `USRSolicitanteID`                 | `null`                                               | NULL      |
| —                                 | `USRtransaccioncnid`               | `null`                                               | NULL      |
| —                                 | `vinculacionOrigen`                | `null`                                               | NULL      |
| —                                 | `vinculacionDestino`               | `null` al crear. Finnegans lo llena con `RECEP-CPRA` al recibir | NULL (solo lectura) |
| `importeexento`                   | `ImporteExento`                    | ❓ ¿Cuándo un item es exento? Default `0`            | ❓ CONSULTAR |

### 4.3. DimensionDistribucion (por Item)

| Campo Puduhue                    | → Campo Finnegans                  | Estrategia                                        | Estado    |
|----------------------------------|-------------------------------------|---------------------------------------------------|-----------|
| `dimensionparfincod` (nuevo)     | `dimensionCodigo: "DIMPARFIN"`     | ❓ Dimensión "Partida Financiera"                  | NUEVO     |
| `distparfincod` (nuevo)          | `distribucionItems[].codigo`       | ❓ Ej: `"CRM000"` — ¿De dónde sale?               | ❓ CONSULTAR |
| `dimensionctccod` (nuevo)        | `dimensionCodigo: "DIMCTC"`        | ❓ Dimensión "Centro de Costo"                     | NUEVO     |
| `distctccod` (nuevo)             | `distribucionItems[].codigo`       | ❓ Ej: `"ROTATI-0002"` — código del centro de costo | ❓ CONSULTAR |
| — (fijo)                         | `tipoCalculo`                      | `"2"` (porcentaje). ❓ ¿Siempre?                   | FIJO      |
| — (fijo)                         | `distribucionItems[].porcentaje`   | `100` (100%). ❓ ¿Siempre 100% o puede dividirse?  | ❓ CONSULTAR |

> [!WARNING]
> El bloque `DimensionDistribucion` es el más complejo y el que más dudas genera.
> En el GET se ven 2 dimensiones (`DIMPARFIN` y `DIMCTC`) cada una con 100%.
> Se debe consultar a Finnegans:
> 1. ¿Son siempre estas 2 dimensiones o depende de la configuración?
> 2. ¿Los códigos (`CRM000`, `ROTATI-0002`) vienen de un maestro consultable vía API?
> 3. ¿Se pueden consultar las dimensiones requeridas con `/Dimension/list`?
> 4. ¿El porcentaje puede ser distinto de 100% (ej. 60/40 entre dos centros de costo)?

### 4.4. Conceptos

| Campo Puduhue                     | → Campo Finnegans                 | Estrategia                                       | Estado    |
|-----------------------------------|------------------------------------|-------------------------------------------------|-----------|
| — (calculado)                     | `ConceptoCodigo: "IVA19COMPRA"`   | FIJO. Calcular: `subtotalNeto * 0.19`            | CALC      |
| — (calculado)                     | `ConceptoImporteGravado`          | Suma de `Precio * Cantidad` de todos los ítems   | CALC      |
| — (fijo 0)                        | Retenciones (`COMPRA-RET*`)       | Enviar en `0.00`. ❓ ¿Son obligatorios en el POST? | ❓ CONSULTAR |

---

## 5. Análisis de Maestros

### 5.1. Maestros Existentes que Aplican

| Maestro actual       | Tabla              | Tiene código ERP  | Campos relevantes                  | Acción                          |
|----------------------|--------------------|-------------------|------------------------------------|---------------------------------|
| Empresas             | `empresas`         | ✅ `empresaiderp` | `empresaid`, `empresaiderp`        | ✅ Listo, se mapea a `EmpresaCodigo` |
| Ítems/Productos      | `invitems`         | ✅ `erpinvitemcod` | `invitemid`, `erpinvitemcod`       | ✅ Listo, se mapea a `ProductoCodigo` |
| Unidades de Medida   | `invunidadesmedidas`| ❌ No tiene       | `invunidmedid`, `invunidmeddsc`    | 🟡 Evaluar agregar `erpunidmedcod` |

### 5.2. Maestros Nuevos Necesarios

| Maestro Nuevo          | Razón                                    | Campos mínimos propuestos                            | FK Finnegans                   |
|------------------------|------------------------------------------|-------------------------------------------------------|--------------------------------|
| **Proveedores**        | `Proveedor` en OC (código libre)         | `proveedorid`, `erpprovedorcod`, `proveedornombre`, `proveedoractivo` | `Proveedor` = `erpprovedorcod`. **Sincronización por cron, sin CRUD** |
| **Condiciones de Pago**| `CondicionPagoCodigo` en OC              | `condpagoid`, `condpagodsc`, `erpcondpagocod`         | `CondicionPagoCodigo`          |
| **Monedas**            | `MonedaCodigo` en OC                     | `monedaid`, `monedadsc`, `erpmonedacod`               | `MonedaCodigo`                 |
| **Centros de Costo**   | `DIMCTC` distribucionItems               | `centrocostoid`, `centrocostodsc`, `erpcentrocostocod` | `distribucionItems[].codigo`  |
| **Partidas Financieras**| `DIMPARFIN` distribucionItems           | `partfinid`, `partfindsc`, `erppartfincod`             | `distribucionItems[].codigo`  |

> [!TIP]
> **Patrón recomendado para todos los maestros con integración ERP:**
> ```
> <maestro>id         INT AUTO_INCREMENT   (PK interna)
> <maestro>cod        VARCHAR(50)          (código visible / legible)
> <maestro>dsc        VARCHAR(100)         (descripción)
> erp<maestro>cod     VARCHAR(50)          (código en Finnegans)
> <maestro>activo     TINYINT(1)           (vigencia)
> + columnas de auditoría estándar
> ```

### 5.3. Mejoras a Maestros Existentes

| Maestro             | Mejora propuesta                                   | Impacto |
|---------------------|----------------------------------------------------|---------|
| `invitems`          | ❓ Agregar campo `invitemprecio` (precio referencial) | Permitiría pre-cargar precio en la Pre OC |
| `invitems`          | ❓ Agregar campo `invitemtipo` (Material/Servicio)    | Para diferenciar ítems en el módulo de compras |
| `invitems`          | ❓ Agregar campo `invitemcomprable` (TINYINT)         | Flag para filtrar ítems aplicables a compras |
| `invunidadesmedidas`| Agregar `erpunidmedcod` (código ERP)                  | Para futuras integraciones que necesiten la UM |
| `clientes`          | ❓ ¿Los proveedores son un tipo de "cliente" o tabla separada? | Definición de arquitectura |

---

## 6. Valores Fijos y Configurables

### 6.1. Valores que serían fijos (`.env` o config)

| Campo Finnegans                  | Valor observado                    | Configurable en |
|----------------------------------|------------------------------------|-----------------|
| `TransaccionTipoCodigo`          | `"OPER"` (ambos tipos)            | `.env` o tabla config |
| `TransaccionSubtipoCodigo`       | `"OC"` (material) / `"OCSS"` (servicio) | **Depende del tipo de Pre OC** |
| `WorkflowCodigo`                 | `"CPRA-INS-SERV"`    | `.env` o tabla config |
| `MonedaCodigo`                   | `"PES"`              | `.env` o maestro |
| `ProvinciaDestinoCodigo`         | `"RDLL"`             | `.env` o por fundo |
| `CHL_ModVenta`                   | `"1"`                | `.env` |
| `CHL_TipoIndicadorTraslado`     | `"1"`                | `.env` |
| `AFIPDGI_Cosecha`                | `"0"`                | `.env` |
| `PorcentajeAnticipoFinanciero`   | `0.00`               | Fijo |

❓ **Consultar a Finnegans**: ¿Cambian estos valores según el tipo de OC (materiales vs servicios)?

### 6.2. Valores calculados

| Campo Finnegans                     | Cálculo                                     |
|--------------------------------------|----------------------------------------------|
| `IdentificacionExterna`              | `preoccod` (ej. `"POC-000123"`)             |
| `Nombre`                             | Igual a `IdentificacionExterna`              |
| Item `IdentificacionExterna`         | `preoccod + " Item-" + N`                    |
| Item `CantidadPendiente`             | ❓ Igual a `Cantidad` al crear               |
| `Conceptos[IVA19COMPRA].Importe`     | `Sum(Precio * Cantidad) * 0.19`              |
| `Conceptos[IVA19COMPRA].Gravado`     | `Sum(Precio * Cantidad)`                     |

---

## 7. Estructura de Tabla Pre OC Revisada (alineada a Finnegans)

### 7.1. `preoc` (cabecera)

| Columna                         | Tipo           | Mapeo Finnegans                    | Notas                        |
|---------------------------------|----------------|-------------------------------------|------------------------------|
| `preocid`                       | INT PK         | —                                   | PK interna                   |
| `preoccod`                      | VARCHAR(30)    | `IdentificacionExterna`, `Nombre`   | Autogenerado                 |
| `preoctipo`                     | TINYINT        | `TransaccionSubtipoCodigo`          | 1=Material(`OC`), 2=Servicio(`OCSS`) |
| `empresaid`                     | INT FK         | — (interna)                         | FK a `empresas`              |
| `erp_empresacod`                | VARCHAR(10)    | `EmpresaCodigo`                     | De `empresas.empresaiderp`   |
| `fundoid`                       | INT FK         | —                                   | FK interna                   |
| `proveedorid`                   | INT FK         | —                                   | FK a `proveedores` (NUEVO)   |
| `erp_proveedorcod`              | VARCHAR(20)    | `Proveedor`                         | RUT del proveedor            |
| `condpagoid`                    | INT FK         | —                                   | FK a `condicionespago` (NUEVO) |
| `erp_condpagocod`               | VARCHAR(10)    | `CondicionPagoCodigo`               | Código ERP                   |
| `preocfecha`                    | DATE           | `Fecha`                             |                              |
| `preocfechavenc`                | DATE           | `FechaBaseVencimiento`              | ✅ Igual a `preocfecha`      |
| `preocdsc`                      | TEXT           | `Descripcion`                       |                              |
| `monedaid`                      | INT FK         | —                                   | FK a `monedas` (si multi-moneda. ❓ #26) |
| `erp_monedacod`                 | VARCHAR(5)     | `MonedaCodigo`                      | Default `"PES"`              |
| `erp_trntipocod`                | VARCHAR(10)    | `TransaccionTipoCodigo`             | Default `"OPER"`             |
| `erp_trnsubtipocod`             | VARCHAR(10)    | `TransaccionSubtipoCodigo`          | `"OC"` o `"OCSS"` según `preoctipo` |
| `erp_workflowcod`               | VARCHAR(20)    | `WorkflowCodigo`                    | Default `"CPRA-INS-SERV"`   |
| `erp_provinciadestcod`          | VARCHAR(10)    | `ProvinciaDestinoCodigo`            | Default `"RDLL"` ❓          |
| `preocestado`                   | TINYINT        | —                                   | Estado del flujo interno     |
| `preocvig`                      | TINYINT(1)     | —                                   | Vigente                      |
| `erp_integrado`                 | TINYINT(1)     | —                                   | 1 si se envió exitosamente   |
| `erp_fechaintegracion`          | DATETIME       | —                                   | Fecha/hora de envío          |
| `erp_respuesta`                 | TEXT           | —                                   | JSON de respuesta Finnegans  |
| + columnas de auditoría         |                |                                     |                              |

### 7.2. `preocdetalle` (ítems)

| Columna                         | Tipo           | Mapeo Finnegans                    | Notas                        |
|---------------------------------|----------------|-------------------------------------|------------------------------|
| `preocdetid`                    | INT PK         | —                                   | PK interna                   |
| `preocid`                       | INT FK         | —                                   | FK a cabecera                |
| `invitemid`                     | INT FK         | —                                   | FK a `invitems`              |
| `erp_productocod`               | VARCHAR(20)    | `ProductoCodigo`                    | De `invitems.erpinvitemcod`  |
| `preocdetdsc`                   | TEXT           | `Descripcion`                       |                              |
| `preocdetcantidad`              | DECIMAL(15,6)  | `Cantidad`                          |                              |
| `preocdetprecio`                | DECIMAL(15,6)  | `Precio`                            | Precio unitario neto         |
| `preocdetsubtotal`              | DECIMAL(15,4)  | —                                   | CALC: cantidad × precio      |
| `preocdetimporteexento`         | DECIMAL(15,4)  | `ImporteExento`                     | Default 0                    |
| `erp_provinciadestcod`          | VARCHAR(10)    | `ProvinciaDestino`                  | Hereda de cabecera ❓        |
| `dim_parfin_cod`                | VARCHAR(20)    | `DIMPARFIN → codigo`                | Código partida financiera    |
| `dim_ctc_cod`                   | VARCHAR(20)    | `DIMCTC → codigo`                   | Código centro de costo       |
| + columnas de auditoría         |                |                                     |                              |

---

## 8. Preguntas Consolidadas para Soporte Finnegans

### Sobre la Transacción
1. ✅ **RESPONDIDO**: `TransaccionSubtipoCodigo` cambia: `"OC"` para insumos, `"OCSS"` para servicios. `TransaccionTipoCodigo` siempre es `"OPER"`.
2. ✅ **RESPONDIDO**: `WorkflowCodigo` es `"CPRA-INS-SERV"` para ambos tipos.
3. ✅ **RESPONDIDO**: `NumeroComprobante` es interno de Finnegans. Enviar como `""` (vacío).
4. ✅ **RESPONDIDO**: `FechaBaseVencimiento` = misma `Fecha`.

### Sobre Items
5. ¿`CantidadPendiente` debe ser igual a `Cantidad` en la creación?
6. ¿`ProvinciaDestino` en cada ítem puede diferir de la cabecera, o siempre es la misma?
7. ¿`ImporteExento` es 0 por defecto?

### Sobre DimensionDistribucion
8. ¿Qué dimensiones son obligatorias? ¿Siempre `DIMPARFIN` y `DIMCTC`?
9. ✅ **RESPONDIDO**: Existe el maestro de "Cuenta Dimensión" en Finnegans con 175+ registros. Contiene 3 tipos de dimensiones: `"Centros de Costo"`, `"Partida Financiera"` y `"Bienes de Uso"`. Sin embargo, este maestro vincula **cuentas contables** a dimensiones, no los códigos de distribución (`APR000`, `CRM000`, `ROTATI-0002`). Los códigos de distribución parecen venir de un maestro diferente. Ver sección 9.
10. ✅ **RESPONDIDO**: Sí, la distribución se puede dividir entre varios centros de costo (ej. 60/40). Una POC puede agrupar varios CC o diferentes requerimientos.
11. ¿`tipoCalculo` siempre es `"2"` (porcentaje)?

### Sobre Conceptos (Impuestos)
12. ¿Los 6 conceptos del GET son obligatorios en el POST, o solo se envía `IVA19COMPRA`?
13. ¿Las retenciones (`COMPRA-RET*`) se pueden omitir si son 0?
14. ¿Existen otros conceptos posibles?

### Sobre Proveedores
15. ✅ **RESPONDIDO**: Sí existe endpoint API de proveedores. **Diseño**: tabla local sincronizada por tarea programada (cron). No habrá CRUD de proveedores — el mantenimiento se hace en Finnegans y aquí solo se reflejan.
16. ✅ **RESPONDIDO**: El código del proveedor **NO es siempre el RUT**. Es un campo libre. Ejemplos del endpoint: `"ORGNODEFINIDA"`, `"76523064-0"` (RUT con guión), `"77090370K"` (RUT sin guión). Hay inconsistencia de formato en el ERP.

### Sobre vinculacionDestino
17. ¿`vinculacionDestino` es solo lectura (llenado por Finnegans al hacer recepción), o se puede enviar en el POST?
18. ¿Los códigos `RECEP-CPRA - XXXX` se generan automáticamente en Finnegans?

### Sobre Partidas Financieras
19. ✅ **PARCIAL**: Los códigos `APR000` (insumos) y `CRM000` (servicios) **no aparecen** en el maestro de Cuenta Dimensión. Probablemente vienen de otro maestro (registros de la dimensión). Por confirmar.
20. ✅ **RESPONDIDO**: No se encontró endpoint específico para partidas financieras.
21. ✅ **RESPONDIDO**: El maestro de Cuenta Dimensión (punto 9) contiene ~80+ registros de partida financiera, pero con códigos contables (110101, 110102, etc.), no los códigos de distribución usados en las OCs (APR000, CRM000).

### General
22. ✅ **RESPONDIDO**: Finnegans tiene un ambiente de pruebas con empresa de prueba. Se puede usar para validar POST.
23. ✅ **RESPONDIDO**: La respuesta del POST debería devolver el mismo formato que las integraciones de suplementación y producción de leche.
24. ✅ **RESPONDIDO**: `NumeroComprobante` es interno de Finnegans (no se envía, queda en `""`).
25. ¿Los `Conceptos` (retenciones) son obligatorios en el POST, o se pueden enviar solo `IVA19COMPRA` y omitir los `COMPRA-RET*` con importe 0?

---

## 9. Análisis del Maestro Cuenta Dimensión (Finnegans)

> [!NOTE]
> Datos obtenidos del endpoint de Finnegans. Este maestro vincula **cuentas contables** a las **dimensiones** que requieren distribución.

### 9.1. Dimensiones Encontradas

| Dimensión              | Código API   | Cantidad de cuentas | Ejemplo                                          |
|------------------------|--------------|--------------------:|--------------------------------------------------|
| **Centros de Costo**   | `DIMCTC`     | ~100+               | `COSECHAC`, `LABC`, `120201 VACAS`              |
| **Partida Financiera** | `DIMPARFIN`  | ~80+                | `110101 CAJA`, `210201 PROVEEDORES`             |
| **Bienes de Uso**      | ❓ (nuevo)   | ~8                  | `110401 MATERIALES`, `410504 COMBUSTIBLES`      |

> [!WARNING]
> **Descubrimiento: 3 dimensiones, no 2.**
>
> El maestro revela una tercera dimensión: **"Bienes de Uso"**. Cuentas como `410201 MANTENCIÓN SALA ORDEÑA`, `410504 COMBUSTIBLES Y LUBRICANTES`, `410605 MANTENCION MOTOS`, `410606 MANTENCION TRACTORES`, `410607 MANTENCION VEHICULOS`, `410608 MANTENCION OTROS`, `410609 MANTENCION OTRAS MAQUINARIAS` aparecen en esta dimensión.
>
> ❓ ¿Las OCs que usan estos productos necesitarán también la dimensión "Bienes de Uso"?

### 9.2. Observaciones sobre el Maestro

1. **Códigos duplicados entre dimensiones**: Un mismo código (ej. `210201`, `110501`, `120105`) puede aparecer tanto en "Partida Financiera" como en "Centros de Costo".
2. **Los códigos de distribución de OC NO están aquí**: Los códigos `APR000`, `CRM000`, `LSM-0002`, `ROTATI-0002`, etc. usados en las OCs reales **no aparecen** en este maestro. Esto indica que son registros de otro nivel (registros de la dimensión, no cuentas contables).
3. **`DISTRIBUCION` generalmente es `null`**: Solo 3 registros tienen distribución explícita: `210504 → "GASTOS FINANCIEROS"`, `420601 → "GASTOS GENERALES"`, `410511 → "GASTOS GENERALES DE PRODUCCION"`, `420610 → "OTROS GTOS ADM"`.

### 9.3. Pregunta Clave Pendiente para Finnegans

> [!CAUTION]
> **¿De dónde salen los códigos `APR000`, `CRM000`, `LSM-0002`, `ROTATI-0002`, etc.?**
>
> Estos son los códigos usados realmente en `distribucionItems[].codigo` dentro de las OCs, pero no están en el maestro de Cuenta Dimensión. Probablemente existe un endpoint tipo:
> - `/Dimension/list` → listar dimensiones
> - `/Dimension/{dimensionCodigo}/registros` → listar registros de cada dimensión
>
> **Esto es BLOQUEANTE** para la implementación de la Pre OC, porque sin conocer los registros válidos de cada dimensión, no se puede construir el selector en el formulario.

---

## 10. Proveedores — Diseño Actualizado

### 10.1. Estrategia: Sincronización desde Finnegans

```
Finnegans (maestro)  →  Cron/Tarea Programada  →  Tabla local `proveedores`
      ↑                                                    ↓
  CRUD en ERP                              Solo lectura en Puduhue App
```

- **No se construye CRUD** de proveedores en esta app.
- Se usa una **tarea programada** (cron) que consume el endpoint de proveedores de Finnegans.
- Si se crea o desactiva un proveedor en el ERP, se refleja automáticamente.

### 10.2. Tabla `proveedores` (revisada)

| Columna                   | Tipo           | Fuente                               | Notas                          |
|---------------------------|----------------|---------------------------------------|--------------------------------|
| `proveedorid`             | INT PK         | — (interna)                           | Autoincremental local          |
| `erpprovedorcod`          | VARCHAR(50)    | `codigo` del endpoint                 | ⚠️ Puede ser RUT, texto, o código libre |
| `proveedornombre`         | VARCHAR(200)   | `nombre` del endpoint                 |                                |
| `proveedordsc`            | VARCHAR(200)   | `descripcion` del endpoint            | Puede ser vacío               |
| `proveedoractivo`         | TINYINT(1)     | `activo` del endpoint                 | `true`/`false`                |
| `sincfechahora`           | DATETIME       | —                                     | Fecha de última sincronización |
| + columnas de auditoría   |                |                                       |                                |

> [!WARNING]
> **Problema de formato en `codigo` del proveedor.**
>
> El campo `codigo` en el endpoint de Finnegans es libre. Ejemplos reales:
> | `codigo`           | `nombre`                              | Formato            |
> |--------------------|---------------------------------------|--------------------|
> | `ORGNODEFINIDA`    | Organización no definida              | Texto libre        |
> | `76523064-0`       | AGRIC. LOS COIGUES SPA               | RUT con guión      |
> | `77090370K`        | AGRIC. Y GANAD. SAN VICENTE...        | RUT sin guión      |
>
> **Impacto**: No se puede validar formato. El campo `Proveedor` del JSON de OC debe enviarse exactamente como está en `erpprovedorcod`. La selección en el formulario debe ser por búsqueda (autocomplete) sobre `proveedornombre`.

---

## 11. Preguntas Nuevas (pendientes de confirmación con el cliente)

### Sobre Monedas y Financiero
26. ❓ ¿Compran en otras monedas o solo pesos chilenos (`PES`)? → Si solo es CLP, `MonedaCodigo` es fijo y no se necesita maestro de monedas.

### Sobre Bodegas y Logística
27. ❓ ¿Existe un concepto de "Bodega Central" que recepciona y luego traslada al destino? ¿O la bodega de recepción es la misma que el destino final?
28. ❓ ¿Cómo es el flujo en terreno para que el producto llegue a destino? (proveedor → recepción → destino final).

> [!NOTE]
> Los JSON de OC de Finnegans **no contienen campos de bodega**. Esto sugiere que la gestión de bodegas es posterior a la OC (recepción de compra → stock). Sin embargo, si los requerimientos especifican dónde se necesita el material, este dato se refleja en la `Descripcion` del ítem y en el `DIMCTC` (centro de costo).

### Sobre Autorizaciones y Flujo de Rechazo
29. ❓ Al rechazar un requerimiento o Pre OC: ¿vuelve al estado inicial (re-crear) o se implementa un estado intermedio tipo **"Solicitar Cambios"**?

💡 **Propuesta**: Implementar estado `CAMBIOS_SOLICITADOS` (ej. código 6):
```
ENVIADO → EN_REVISION → APROBADO
                      → RECHAZADO (definitivo, con motivo)
                      → CAMBIOS_SOLICITADOS (editable por solicitante)
                            ↓
                         REENVIADO → EN_REVISION (no reinicia autorizaciones previas)
```
Ventajas:
- El solicitante puede editar sin recrear.
- Se conserva el historial de autorizaciones previas.
- Los autorizadores anteriores reciben notificación pero no deben re-aprobar.

### Sobre Tipos Mixtos
30. ❓ ¿Se puede agregar un servicio a una OC de material (y viceversa)?

💡 **Consideración**: Si se mezclan tipos, `TransaccionSubtipoCodigo` no puede ser ni `"OC"` ni `"OCSS"`. Opciones:
- **Opción A**: Prohibir mezcla — una Pre OC es 100% material o 100% servicio.
- **Opción B**: Permitir mezcla — definir regla para decidir el subtipo (ej: por el tipo del primer ítem, o por mayoría).
- **Opción C**: ❓ Consultar si Finnegans acepta ítems de servicio en una OC tipo `"OC"` o viceversa.

> [!IMPORTANT]
> La respuesta a la pregunta 30 impacta directamente el diseño de `preoctipo` y la lógica de integración.

