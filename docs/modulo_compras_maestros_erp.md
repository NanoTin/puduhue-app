# Módulo de Compras — Maestros Espejo ERP v1

> [!NOTE]
> Este documento centraliza todos los **maestros espejo** del ERP Finnegans que requiere
> el módulo de Compras (REQ + PreOC) en Puduhue App.
>
> **Estrategia general de sincronización:**
> - Datos provenientes del ERP → Solo lectura en Puduhue App. Sin CRUD.
> - Pantalla: solo botón **Exportar** y botón **Sincronizar On-Demand**.
> - Adicionalmente, tarea **cron diaria** que actualiza automáticamente.
> - Atributos locales (que no existen en el ERP) → Editables localmente en Puduhue App.

---

## Estado de Cobertura

| # | Maestro                          | Estado en documentación | Archivo |
|---|----------------------------------|-------------------------|---------|
| 1 | Centros de Costo                 | ✅ Documentado          | `modulo_compras_req_estructura.md` §10.1 |
| 2 | Dimensiones (DIMPARFIN / DIMCTC) | ⚠️ Mencionado, sin tabla | Este documento (nuevo) |
| 3 | Impuestos / Conceptos por Proveedor | ❌ Faltaba           | Este documento (nuevo) |
| 4 | Proveedores                      | ⚠️ Solo FK, sin tabla   | Este documento (nuevo) |
| 5 | Condiciones de Pago              | ⚠️ Solo FK, sin tabla   | Este documento (nuevo) |
| 6 | Monedas                          | ⚠️ Solo mencionado      | Este documento (nuevo) |
| 7 | Cuentas Contables                | ❌ Faltaba              | Este documento (nuevo) |

---

## 1. Centros de Costo (`centroscosto`)

✅ **Ya documentado en `modulo_compras_req_estructura.md` §10.1.**

Referencia ERP: `DIMCTC` (dimensión de Centro de Costo en `DimensionDistribucion`).

> [!IMPORTANT]
> ❓ **PENDIENTE confirmar con soporte ERP**: La dimensión `DIMCTC` en Finnegans ¿es
> exactamente el mismo catálogo que los Centros de Costo del punto anterior, o es una
> entidad independiente dentro del motor de dimensiones del ERP?
> Si son distintas → se debe crear una tabla `erpdimctc` como espejo separado.
> Si son iguales → el campo `erpcentrocostocod` de `centroscosto` es suficiente.

---

## 2. Dimensiones ERP (`erpdimensiones` + `erpdimensionvalores`)

> [!WARNING]
> ❓ **PENDIENTE confirmar con soporte ERP**: El ERP maneja un motor de dimensiones
> genérico. En los ejemplos de OC se observan al menos 3 dimensiones:
> - `DIMPARFIN` — Partida Financiera (presupuesto ERP)
> - `DIMCTC` — Centro de Costo
> - `DIMBU` — (aparece en algunos ítems de servicios, función desconocida)
>
> Se debe confirmar si `DIMCTC` coincide con el catálogo de Centros de Costo,
> si `DIMBU` aplica para Puduhue, y cuáles otras dimensiones son obligatorias al crear una OC.

### 2.1. `erpdimensiones` — Catálogo de Dimensiones

| Columna                  | Tipo           | NULL | Descripción                                                    |
|--------------------------|----------------|------|----------------------------------------------------------------|
| `erpdimensionid`         | INT PK AI      | NO   | PK interna                                                     |
| `erpdimensioncod`        | VARCHAR(20)    | NO   | Código en ERP (ej. `DIMPARFIN`, `DIMCTC`, `DIMBU`). UNIQUE    |
| `erpdimensiondsc`        | VARCHAR(100)   | NO   | Descripción legible                                            |
| `erpdimensionobligatorio`| TINYINT(1)     | NO   | 1=La dimensión es obligatoria al crear OC en Finnegans         |
| `erpdimensionactivo`     | TINYINT(1)     | NO   | 1=Activo (sync desde ERP)                                      |
| `sincfechahora`          | DATETIME       | SÍ   | Última sincronización                                          |

### 2.2. `erpdimensionvalores` — Valores por Dimensión

> Cada dimensión tiene un catálogo de códigos válidos (ej. DIMPARFIN tiene `REB000`, `LEC000`, `CRM000`).

| Columna                      | Tipo           | NULL | Descripción                                                    |
|------------------------------|----------------|------|----------------------------------------------------------------|
| `erpdimvaloreid`             | INT PK AI      | NO   | PK interna                                                     |
| `erpdimensionid`             | INT FK         | NO   | FK → `erpdimensiones`                                          |
| `erpdimvalorcod`             | VARCHAR(50)    | NO   | Código del valor (ej. `REB000`, `LEC000`, `LBV-0002`)         |
| `erpdimvalordsc`             | VARCHAR(100)   | SÍ   | Descripción (si la provee el ERP)                              |
| `erpdimvalorActivo`          | TINYINT(1)     | NO   | 1=Activo                                                       |
| `sincfechahora`              | DATETIME       | SÍ   | Última sincronización                                          |

**Uso en la Pre OC:**

Al crear una línea de Pre OC, para construir el JSON del POST a Finnegans se necesita:
- El valor de `DIMPARFIN` para esa línea (partida financiera del presupuesto ERP).
- El valor de `DIMCTC` para esa línea (código del CC en ERP = `centroscosto.erpcentrocostocod`).

> [!IMPORTANT]
> ❓ **PENDIENTE**: ¿Cómo se determina el valor de `DIMPARFIN` para cada línea de la Pre OC?
> En los ejemplos se observan códigos como `REB000`, `LEC000`, `CRM000`.
> ¿Estos vienen del presupuesto seleccionado en Puduhue App, o del producto/CC?
> **Esto debe aclararse con soporte del ERP antes de implementar la integración.**

---

## 3. Proveedores (`proveedores`)

> Espejo del catálogo de organizaciones/proveedores del ERP.
> El campo `Proveedor` del JSON de OC corresponde al RUT del proveedor.

### 3.1. `proveedores` — Maestro de Proveedores

| Columna                    | Tipo           | NULL | Descripción                                                                        |
|----------------------------|----------------|------|------------------------------------------------------------------------------------|
| `proveedorid`              | INT PK AI      | NO   | PK interna                                                                         |
| `proveedorrut`             | VARCHAR(20)    | NO   | RUT (ej. `82392600-6`). Campo `Proveedor` en JSON ERP. UNIQUE                     |
| `proveedornombre`          | VARCHAR(200)   | NO   | Razón social (ej. `COOPRINSEM`)                                                    |
| `proveedorcategoriaFiscalCod`| VARCHAR(20)  | SÍ   | Código de categoría fiscal en ERP (`CategoriaFiscalID`, ej. `1cat`). Ver §3.2     |
| `condicionpagoid`          | INT FK         | SÍ   | FK → `condicionespago`. Condición de pago por defecto (de la ficha del proveedor)  |
| `proveedorActivo`          | TINYINT(1)     | NO   | 1=Activo (sync desde ERP)                                                          |
| `sincfechahora`            | DATETIME       | SÍ   | Última sincronización                                                              |
| + 8 columnas de auditoría  |                |      | Estándar                                                                           |

> [!NOTE]
> Al seleccionar un proveedor en la Pre OC:
> - La **condición de pago** se pre-carga desde `proveedores.condicionpagoid` (editable por el comprador).
> - La **categoría fiscal** determina qué conceptos/impuestos se incluyen en el `Conceptos[]` del JSON del ERP.

### 3.2. `erpimputaciones` — Impuestos / Conceptos del Proveedor

> En la ficha contable del proveedor en Finnegans existe un atributo llamado **"Concepto"**
> que actúa como una clase (categoría fiscal) que agrupa los impuestos (retenciones + IVA)
> que se aplican al momento de crear una OC con ese proveedor.
>
> Al construir el JSON de la OC, el array `Conceptos[]` se construye en base a esta categoría,
> calculando los importes sobre los valores **netos** de cada ítem.

#### `erpcategoriasfiscales` — Categorías Fiscales

| Columna                     | Tipo           | NULL | Descripción                                                   |
|-----------------------------|----------------|------|---------------------------------------------------------------|
| `erpcatfiscalid`            | INT PK AI      | NO   | PK interna                                                    |
| `erpcatfiscalcod`           | VARCHAR(20)    | NO   | Código ERP (ej. `1cat`). UNIQUE                               |
| `erpcatfiscaldsc`           | VARCHAR(100)   | NO   | Descripción                                                   |
| `erpcatfiscalactivo`        | TINYINT(1)     | NO   | 1=Activo                                                      |
| `sincfechahora`             | DATETIME       | SÍ   | Última sincronización                                         |

#### `erpcatfiscalconceptos` — Conceptos por Categoría Fiscal

> Tabla de relación: cada categoría fiscal tiene un conjunto de conceptos (impuestos).

| Columna                     | Tipo           | NULL | Descripción                                                              |
|-----------------------------|----------------|------|--------------------------------------------------------------------------|
| `erpcatfiscalconceptoid`    | INT PK AI      | NO   | PK interna                                                               |
| `erpcatfiscalid`            | INT FK         | NO   | FK → `erpcategoriasfiscales`                                             |
| `erpconceptocod`            | VARCHAR(50)    | NO   | Código del concepto en ERP (ej. `IVA19COMPRA`, `COMPRA-RET12.25`)       |
| `erpconceptodsc`            | VARCHAR(100)   | SÍ   | Descripción del concepto                                                 |
| `erpconceptoporcentaje`     | DECIMAL(5,2)   | NO   | Porcentaje de la tasa (ej. `19.00`, `12.25`)                             |
| `erpconceptoactivo`         | TINYINT(1)     | NO   | 1=Activo                                                                 |
| `sincfechahora`             | DATETIME       | SÍ   | Última sincronización                                                    |

**Conceptos observados en los ejemplos de OC:**

| `ConceptoCodigo`     | Descripción estimada           | Porcentaje | Notas |
|----------------------|-------------------------------|------------|-------|
| `IVA19COMPRA`        | IVA 19% Compra                | 19.00%     | Se calcula sobre importe gravado neto |
| `COMPRA-RET12.25`    | Retención 12.25%              | 12.25%     | Retención de impuesto; importe = 0 si no aplica |
| `COMPRA-RET15.25`    | Retención 15.25%              | 15.25%     | Ídem |
| `COMPRA-RET13`       | Retención 13.75%              | 13.75%     | Ídem |
| `COMPRA-RET14`       | Retención 14.50%              | 14.50%     | Ídem |
| `COMPRA-RET16`       | Retención 16.75%              | 16.75%     | Solo en algunos proveedores |

> [!NOTE]
> En los ejemplos reales, todos los conceptos de retención tienen importe `0.0000` para los proveedores
> del ejemplo. El concepto `IVA19COMPRA` es el único con importe positivo en los OC de materiales.
> Los servicios pueden tener IVA en 0 dependiendo de la categoría fiscal del proveedor.

**Cálculo al construir el JSON de la OC:**
```
Para cada ConceptoCodigo en erpcatfiscalconceptos (de la categoría fiscal del proveedor):
    ConceptoImporteGravado = suma de (preocdetsubtotalneto) de todos los ítems gravados
    ConceptoImporte        = ConceptoImporteGravado × (porcentaje / 100)
    → Si el concepto no aplica para alguna línea, el importe va en 0.0000
```

---

## 4. Condiciones de Pago (`condicionespago`)

> Espejo del catálogo de condiciones de pago del ERP.
> Campo ERP: `CondicionPagoCodigo` en el POST de la OC.
> En la ficha del proveedor existe una condición de pago por defecto, usada como valor inicial en la Pre OC (editable por el comprador).

| Columna                    | Tipo           | NULL | Descripción                                               |
|----------------------------|----------------|------|-----------------------------------------------------------|
| `condicionpagoid`          | INT PK AI      | NO   | PK interna                                               |
| `condicionpagocod`         | VARCHAR(20)    | NO   | Código ERP (ej. `"30"`, `"Contado"`). UNIQUE             |
| `condicionpagodsc`         | VARCHAR(100)   | NO   | Descripción legible (ej. `"30 días"`, `"Contado"`)       |
| `condicionpagoActivo`      | TINYINT(1)     | NO   | 1=Activo (sync desde ERP)                                 |
| `sincfechahora`            | DATETIME       | SÍ   | Última sincronización                                     |

**Uso en Pre OC:**
- Al seleccionar el proveedor → se pre-carga `proveedores.condicionpagoid`.
- El comprador puede cambiarla antes de grabar la Pre OC.
- Al construir el JSON del ERP → `CondicionPagoCodigo = condicionespago.condicionpagocod`.

---

## 5. Monedas (`erpmonedas`)

> Espejo del catálogo de monedas del ERP.
> Campo ERP: `MonedaCodigo` en el POST de la OC.
> **Por decisión confirmada: solo se utiliza CLP (`PES`)**. Igual se sincroniza el catálogo
> para disponer del código correcto al armar el JSON.

| Columna              | Tipo           | NULL | Descripción                                          |
|----------------------|----------------|------|------------------------------------------------------|
| `erpmonedaid`        | INT PK AI      | NO   | PK interna                                           |
| `erpmonedacod`       | VARCHAR(10)    | NO   | Código ERP (ej. `PES`, `DOL`). UNIQUE                |
| `erpmonedadsc`       | VARCHAR(50)    | NO   | Descripción (ej. `Pesos Chilenos`, `Dólar`)          |
| `erpmonedadefault`   | TINYINT(1)     | NO   | 1=Moneda por defecto para Pre OC (solo una activa)   |
| `erpmonedaActivo`    | TINYINT(1)     | NO   | 1=Activo (sync desde ERP)                            |
| `sincfechahora`      | DATETIME       | SÍ   | Última sincronización                                |

> [!NOTE]
> En los ejemplos de OC se observan cotizaciones en `OperacionCotizaciones`:
> `PES` (cotización 1.0) y `DOL` (cotización variable, ej. 700.0).
> Aunque la Pre OC siempre se crea en CLP, el ERP registra la cotización del dólar del día.
> ❓ **PENDIENTE**: ¿El sistema debe enviar la cotización del dólar en el JSON de la OC?
> Si es así, ¿de dónde se obtiene (API externa, manual, ERP)?

---

## 6. Cuentas Contables (`erpcuentas`)

> Espejo del plan de cuentas contables del ERP.
> En los ejemplos de OC, cada ítem (`OperacionItems[n].CuentaID`) y cada concepto
> (`OperacionConceptos[n].CuentaID`) tiene asociada una cuenta contable.
> Estos valores los asigna automáticamente Finnegans según la configuración del ERP;
> **Puduhue App no los debe calcular manualmente**.
> Se sincroniza el catálogo como referencia y para auditoría.

| Columna                  | Tipo           | NULL | Descripción                                              |
|--------------------------|----------------|------|----------------------------------------------------------|
| `erpcuentaid`            | INT PK AI      | NO   | PK interna                                               |
| `erpcuentacod`           | VARCHAR(20)    | NO   | Código de cuenta (ej. `210208`, `110301`). UNIQUE        |
| `erpcuentadsc`           | VARCHAR(150)   | NO   | Descripción de la cuenta                                 |
| `erpcuentatipo`          | VARCHAR(20)    | SÍ   | Tipo de cuenta (Activo, Pasivo, Resultado, etc.)         |
| `erpcuentaActivo`        | TINYINT(1)     | NO   | 1=Activa (sync desde ERP)                                |
| `sincfechahora`          | DATETIME       | SÍ   | Última sincronización                                    |

> [!NOTE]
> **Cuentas observadas en los ejemplos:**
> - `210208` — Cuenta de ítems de compra (materiales)
> - `420301` — Cuenta de ítems de compra (servicios)
> - `110301` — Cuenta de IVA crédito fiscal
> - `210503` — Cuenta de retenciones
> - `210201` — Cuenta de condición de pago (cuentas por pagar)
>
> Finnegans asigna estas cuentas automáticamente al crear la OC. No se envían en el POST mínimo.
> ❓ **PENDIENTE confirmar con soporte ERP**: ¿Es necesario enviar `CuentaID` en cada ítem/concepto
> del POST, o Finnegans las resuelve por configuración interna?

---

## 7. Tablas de Soporte Adicional ERP

### 7.1. `erpworkflows` — Workflows de Compra

> El `WorkflowCodigo` es obligatorio en el JSON del POST de la OC.
> En los ejemplos se observa siempre `"CPRA-INS-SERV"`.

| Columna              | Tipo           | NULL | Descripción                                             |
|----------------------|----------------|------|----------------------------------------------------------|
| `erpworkflowid`      | INT PK AI      | NO   | PK interna                                              |
| `erpworkflowcod`     | VARCHAR(50)    | NO   | Código ERP (ej. `CPRA-INS-SERV`). UNIQUE               |
| `erpworkflowdsc`     | VARCHAR(150)   | NO   | Descripción                                             |
| `erpworkflowtipo`    | VARCHAR(10)    | SÍ   | Tipo: `OC` (Material) o `OCSS` (Servicio)               |
| `erpworkflowActivo`  | TINYINT(1)     | NO   | 1=Activo (sync desde ERP)                               |
| `sincfechahora`      | DATETIME       | SÍ   | Última sincronización                                   |

> [!NOTE]
> El `WorkflowCodigo` podría ser fijo (`"CPRA-INS-SERV"`) para todos los tipos de OC de Puduhue.
> ❓ **PENDIENTE confirmar**: ¿Hay un workflow distinto para OC de materiales vs servicios?

### 7.2. `erpprovinciasdestino` — Provincias / Establecimientos

> El campo `ProvinciaDestino` en cada ítem de la OC representa el establecimiento/fundo destino.
> En los ejemplos se observa `"RDLL"` (posiblemente "Rincón de la Laja" o similar).

| Columna                    | Tipo           | NULL | Descripción                                             |
|----------------------------|----------------|------|----------------------------------------------------------|
| `erpprovinciad`            | INT PK AI      | NO   | PK interna                                              |
| `erprovinciacod`           | VARCHAR(20)    | NO   | Código ERP (ej. `RDLL`). UNIQUE                         |
| `erpprovinciadsc`          | VARCHAR(100)   | NO   | Descripción                                             |
| `erprovinciaActivo`        | TINYINT(1)     | NO   | 1=Activo                                                |
| `sincfechahora`            | DATETIME       | SÍ   | Última sincronización                                   |

> [!NOTE]
> ❓ **PENDIENTE confirmar**: ¿El `ProvinciaDestino` de cada ítem es siempre el mismo para toda
> la Pre OC, o puede variar por línea según el CC de destino?
> En los ejemplos de test (`erp_oc_material_test_minimas_columnas.json`) se observa `ProvinciaDestino: null`,
> lo que sugiere que podría ser opcional.

---

## 8. Resumen — Tablas Nuevas a Crear

| Tabla                         | Tipo   | Usada en     | Descripción                                    |
|-------------------------------|--------|--------------|------------------------------------------------|
| `erpdimensiones`              | Espejo | REQ + PreOC  | Dimensiones del ERP (DIMPARFIN, DIMCTC, DIMBU)|
| `erpdimensionvalores`         | Espejo | PreOC        | Valores por dimensión                          |
| `erpcategoriasfiscales`       | Espejo | PreOC        | Categorías fiscales de proveedores             |
| `erpcatfiscalconceptos`       | Espejo | PreOC        | Conceptos/impuestos por categoría fiscal       |
| `proveedores`                 | Espejo | PreOC        | Maestro de proveedores                         |
| `condicionespago`             | Espejo | PreOC        | Condiciones de pago                            |
| `erpmonedas`                  | Espejo | PreOC        | Monedas (referencia para código CLP)           |
| `erpcuentas`                  | Espejo | Referencia   | Plan de cuentas contables                      |
| `erpworkflows`                | Espejo | PreOC        | Workflows de compra                            |
| `erpprovinciasdestino`        | Espejo | PreOC        | Establecimientos / provincias destino          |

---

## 9. Pendientes Confirmados con Soporte ERP

| # | Pregunta | Impacto |
|---|----------|---------|
| 1 | ¿`DIMCTC` es el mismo catálogo que los Centros de Costo, o una entidad independiente? | Definir si `centroscosto` cubre esto o si se crea `erpdimctc` separado |
| 2 | ¿Cuál es la función de la dimensión `DIMBU` en las OC de servicios? ¿Es obligatoria para Puduhue? | Determinar si se incluye en el POST y cómo se mapea |
| 3 | ¿Cómo se determina el valor de `DIMPARFIN` para cada línea de la Pre OC? | Bloquea la integración con el ERP |
| 4 | ¿El workflow `CPRA-INS-SERV` es fijo para todos los tipos de OC de Puduhue? | Determinar si hay un workflow diferente para OC vs OCSS |
| 5 | ¿El `ProvinciaDestino` es obligatorio en cada ítem del JSON del POST? ¿Varía por CC? | Definir si se agrega como campo en `preocdetalle` o se usa un valor global de la Pre OC |
| 6 | ¿Es necesario enviar `CuentaID` en ítems y conceptos del POST, o Finnegans las resuelve internamente? | Simplifica el POST si no es necesario |
| 7 | ¿Se debe enviar `OperacionCotizaciones` (cotización del dólar) en el POST? ¿De dónde se obtiene? | Define si se necesita integración con tipo de cambio |

---

## 10. Mapeo Completo de Campos ERP → Tablas Puduhue App

> Referencia rápida para el desarrollo: qué campo del JSON de la OC se obtiene de qué tabla.

| Campo JSON (OC POST)                           | Tabla Puduhue App                          | Campo               |
|------------------------------------------------|--------------------------------------------|---------------------|
| `Proveedor`                                    | `proveedores`                              | `proveedorrut`      |
| `CondicionPagoCodigo`                          | `condicionespago`                          | `condicionpagocod`  |
| `MonedaCodigo`                                 | `erpmonedas`                               | `erpmonedacod`      |
| `WorkflowCodigo`                               | `erpworkflows`                             | `erpworkflowcod`    |
| `EmpresaCodigo`                                | `empresas`                                 | `erpempresacod`     |
| `TransaccionSubtipoCodigo`                     | Lógica: `preoctipo`                        | `"OC"` o `"OCSS"`  |
| `TransaccionTipoCodigo`                        | Fijo                                       | `"OPER"`            |
| `Items[n].ProductoCodigo`                      | `invitems`                                 | `erpitemcod`        |
| `Items[n].Descripcion`                         | `centroscosto`                             | `centrocostodsc`    |
| `Items[n].ProvinciaDestino`                    | `erpprovinciasdestino`                     | `erprovinciacod`    |
| `Items[n].DimensionDistribucion[DIMCTC].codigo`| `centroscosto`                             | `erpcentrocostocod` |
| `Items[n].DimensionDistribucion[DIMPARFIN].codigo`| `erpdimensionvalores`                   | `erpdimvalorcod`    |
| `Conceptos[n].ConceptoCodigo`                  | `erpcatfiscalconceptos`                    | `erpconceptocod`    |
| `Conceptos[n].ConceptoImporte`                 | Calculado (neto × porcentaje del concepto) | —                   |
| `Conceptos[n].ConceptoImporteGravado`          | Calculado (suma netos gravados de ítems)   | —                   |
| `FechaBaseVencimiento`                         | `preoc`                                    | `preocfecha`        |
| `ProvinciaDestinoCodigo`                       | `erpprovinciasdestino`                     | `erprovinciacod`    |
