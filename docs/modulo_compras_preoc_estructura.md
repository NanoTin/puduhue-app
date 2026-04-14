# Módulo PreOC (Pre Orden de Compra) — Estructura de Datos v1

> [!NOTE]
> Diseño detallado de tablas para el módulo de Pre Orden de Compra (PreOC).
> En Finnegans: "Orden de Compra". En Puduhue App: "Pre OC" o "POC".
>
> Convenciones aplicadas del proyecto:
> - PK: `<tabla>id` INT AUTO_INCREMENT.
> - Baja lógica: `<tabla>vig` TINYINT(1).
> - Auditoría: 8 columnas estándar (4 creación + 4 edición). Ver README §7.1.
> - LOG: tabla separada `<tabla>log` con `logid`, `logtipo`, `logparamjson`, `logregbkpjson`. Ver README §7.2.

### Decisiones confirmadas

| # | Decisión | Resolución |
|---|----------|------------|
| 1 | ¿Quién crea la Pre OC? | ✅ Las secretarias (compradores). Atributo `comprador = 1` en `usuarios` |
| 2 | Consolidación de REQ | ✅ Una POC puede consolidar múltiples REQs aprobados |
| 3 | Compra parcial | ✅ Se puede comprar parte de un REQ; el saldo queda disponible para otra POC |
| 4 | Sin cotizaciones | ✅ Flujo directo: REQ Aprobado → Pre OC. Sin módulo de cotizaciones |
| 5 | Fecha de la Pre OC | ✅ Fecha de creación automática. No editable por el comprador |
| 6 | Moneda | ✅ Solo CLP (`MonedaCodigo: "PES"`) |
| 7 | Tipo en ERP | ✅ Material = `TransaccionSubtipoCodigo: "OC"` / Servicio = `"OCSS"` |
| 8 | Campo Descripcion ítem ERP | ✅ Se usa para identificar el Centro de Costo de cada línea en pantalla ERP |
| 9 | Cambio de ítem | ✅ El comprador puede cambiar un ítem desde "Pendientes de Compra" (no desde la POC) |
| 10 | Aprobación por monto | ✅ Tabla de reglas por monto neto → agrega aprobadores automáticos a la lista |
| 11 | Orden de firmantes | ✅ Drag & drop o flechas. Último paso antes de grabar. El aprobador puede existir solo una vez |
| 12 | Validación presupuestaria | ✅ Bloqueante. No puede avanzar si no hay saldo |
| 13 | Acceso al presupuesto | ✅ Solo Gerencia de Adm. y Finanzas crea/edita. Compradores solo consultan reporte |
| 14 | Vista por defecto comprador | ✅ Muestra solo sus POC. Puede limpiar filtro para ver las de otros |

---

## 1. Tablas Nuevas — Resumen

| Tabla                           | Tipo           | Descripción                                                          |
|---------------------------------|----------------|----------------------------------------------------------------------|
| `preoc`                         | Transaccional  | Cabecera de la Pre Orden de Compra                                   |
| `preocdetalle`                  | Transaccional  | Detalle (ítems) de la Pre OC                                         |
| `preocfirmantes`                | Transaccional  | Lista de firmantes/aprobadores por POC                               |
| `preoclog`                      | LOG            | Registro de acciones (INS/UPD/ANL/APR/RCH/CSO/ERP)                  |
| `preocestados`                  | Maestro        | Catálogo de estados para Pre OC                                      |
| `preocaprobadoresxmonto`        | Maestro        | Reglas de aprobadores automáticos según monto neto de la POC         |
| `pptocompra`                    | Maestro        | Presupuesto de compra (encabezado: clasificación / sub / presupuesto)|
| `pptocompramovimientos`         | Transaccional  | Kardex de movimientos del presupuesto                                |

### Tablas existentes a modificar / relacionar

| Tabla              | Cambio / Relación                                                      |
|--------------------|------------------------------------------------------------------------|
| `reqaprobados`     | `preocid` FK ya contemplado. Estado se actualiza al vincular a POC     |
| `reqaprobadoshistorial` | `preocid` y `preocdetid` FK → ya contemplados en la estructura REQ |

### Maestros ERP requeridos (definidos en `modulo_compras_maestros_erp.md`)

| Tabla                         | Descripción                                        |
|-------------------------------|----------------------------------------------------|
| `centroscosto`                | Centros de costo (ya en req_estructura.md)         |
| `erpdimensiones`              | Dimensiones ERP (DIMPARFIN, DIMCTC, DIMBU)         |
| `erpdimensionvalores`         | Valores por dimensión (código de partida financiera)|
| `erpcategoriasfiscales`       | Categorías fiscales de proveedores                 |
| `erpcatfiscalconceptos`       | Conceptos / impuestos por categoría fiscal         |
| `proveedores`                 | Maestro de proveedores (espejo ERP)                |
| `condicionespago`             | Condiciones de pago (espejo ERP)                   |
| `erpmonedas`                  | Monedas (espejo ERP; por defecto CLP = `PES`)      |
| `erpworkflows`                | Workflows de compra (ej. `CPRA-INS-SERV`)          |
| `erpprovinciasdestino`        | Provincias / establecimientos destino              |
| `erpcuentas`                  | Plan de cuentas contables (referencia, no se envía en POST) |

---

## 2. `preoc` — Cabecera de la Pre Orden de Compra

| Columna                        | Tipo           | NULL | Default   | Descripción / Notas                                                                   |
|--------------------------------|----------------|------|-----------|---------------------------------------------------------------------------------------|
| `preocid`                      | INT PK AI      | NO   |           | PK interna                                                                            |
| `preocdoc`                     | VARCHAR(20)    | NO   |           | Código visible: `POC-000001`. Autogenerado, UNIQUE                                    |
| `empresaid`                    | INT FK         | NO   |           | FK → `empresas`                                                                       |
| `preoctipo`                    | TINYINT        | NO   |           | 1=Material (`OC`), 2=Servicio (`OCSS`). Determinado por el tipo de los REQ vinculados|
| `preocfecha`                   | DATE           | NO   | CURDATE() | Fecha de creación. **No editable**. Fijada por SP al insertar                        |
| `proveedorid`                  | INT FK         | NO   |           | FK → `proveedores`. RUT en ERP: `Proveedor`                                           |
| `condicionpagoid`              | INT FK         | SÍ   | NULL      | FK → `condicionespago`. Pre-cargado desde ficha del proveedor, editable por comprador |
| `erpworkflowid`                | INT FK         | NO   |           | FK → `erpworkflows`. Workflow de compra (ej. `CPRA-INS-SERV`)                        |
| `erpmonedaid`                  | INT FK         | NO   |           | FK → `erpmonedas`. Por default CLP (`PES`). Fijado automáticamente                   |
| `erpprovinciaid`               | INT FK         | SÍ   | NULL      | FK → `erpprovinciasdestino`. Provincia/establecimiento destino global de la POC       |
| `pptocompraid`                 | INT FK         | SÍ   | NULL      | FK → `pptocompra`. Presupuesto seleccionado por el comprador (activo, con saldo)     |
| `preocobs`                     | TEXT           | SÍ   | NULL      | Observaciones generales de la Pre OC                                                  |
| `preocestadoid`                | INT FK         | NO   |           | FK → `preocestados`                                                                   |
| `preocaprobadorpendienteid`    | INT FK         | SÍ   | NULL      | FK → `usuarios`. Siguiente firmante. NULL cuando BRR/APR/RCH/ANL                    |
| `preocnettotal`                | DECIMAL(15,2)  | NO   | 0.00      | Suma total neto del detalle (recalculado por SP)                                      |
| `preociva`                     | DECIMAL(15,2)  | NO   | 0.00      | IVA calculado (desde conceptos del proveedor). Recalculado por SP                     |
| `preoctotal`                   | DECIMAL(15,2)  | NO   | 0.00      | Total con impuestos. Recalculado por SP                                               |
| `erptransaccionid`             | VARCHAR(50)    | SÍ   | NULL      | ID de la transacción generada en Finnegans (post integración)                        |
| `erpnumerodoc`                 | VARCHAR(50)    | SÍ   | NULL      | Número de documento ERP (`NumeroComprobante`) asignado por Finnegans                 |
| `erpenviofechahora`            | DATETIME       | SÍ   | NULL      | Fecha/hora de envío al ERP                                                            |
| `erprespuestajson`             | JSON           | SÍ   | NULL      | Respuesta completa del ERP al POST (para debugging y trazabilidad)                    |
| `preocvig`                     | TINYINT(1)     | NO   | 1         | 1=vigente, 0=anulado                                                                  |
| + 8 columnas de auditoría      |                |      |           | Estándar del proyecto                                                                 |

**Índices:**
- `PK (preocid)`
- `UNIQUE (preocdoc)`
- `FK → empresas`, `FK → proveedores`, `FK → condicionespago`, `FK → erpworkflows`, `FK → erpmonedas`, `FK → erpprovinciasdestino` (nullable), `FK → pptocompra`, `FK → preocestados`, `FK → usuarios` (nullable)

---

## 3. `preocdetalle` — Detalle (Ítems)

| Columna                        | Tipo           | NULL | Default | Descripción / Notas                                                               |
|--------------------------------|----------------|------|---------|-----------------------------------------------------------------------------------|
| `preocdetid`                   | INT PK AI      | NO   |         | PK interna                                                                        |
| `preocid`                      | INT FK         | NO   |         | FK → `preoc`                                                                      |
| `reqaprobadoid`                | INT FK         | NO   |         | FK → `reqaprobados`. Línea del REQ aprobado origen                                |
| `preocdetlinea`                | INT            | NO   |         | Nro de línea (1, 2, 3...)                                                         |
| `invitemid`                    | INT FK         | NO   |         | FK → `invitems`. Producto (puede diferir del REQ original si el comprador lo cambió)|
| `centrocostoid`                | INT FK         | NO   |         | FK → `centroscosto`. Centro de costo de la línea                                  |
| `erpprovinciaid`               | INT FK         | SÍ   | NULL    | FK → `erpprovinciasdestino`. Puede heredarse de la cabecera o definirse por línea |
| `erpdimparfincod`              | VARCHAR(50)    | SÍ   | NULL    | Código `DIMPARFIN` para esta línea (partida financiera ERP). ❓ Pendiente definir   |
| `preocdetdsc`                  | VARCHAR(200)   | NO   |         | Descripción del producto (de `invitems`)                                          |
| `preocdetdsccc`                | VARCHAR(200)   | NO   |         | Descripción para ERP: se mapea al campo `Descripcion` del item = nombre del CC    |
| `invunidmedid`                 | INT FK         | NO   |         | FK → `invunidadesmedidas`                                                         |
| `preocdetcantidad`             | DECIMAL(15,4)  | NO   |         | Cantidad a comprar (≤ `reqaprobados.reqaprobadocantidadpendiente`)                |
| `preocdetprecioneto`           | DECIMAL(15,2)  | NO   | 0.00    | Precio neto unitario                                                              |
| `preocdetsubtotalneto`         | DECIMAL(15,2)  | NO   | 0.00    | CALC: `cantidad × precioneto`                                                     |
| `preocdetobs`                  | TEXT           | SÍ   | NULL    | Observación por línea (opcional)                                                  |

**Reglas:**
- Al crear cada línea de la POC, se descuenta `reqaprobados.reqaprobadocantidadpendiente`.
- Si la cantidad de la POC = `reqaprobadocantidadpendiente` → estado `Completa`.
- Si la cantidad de la POC < `reqaprobadocantidadpendiente` → estado `Parcial`.
- El campo `preocdetdsccc` se construye con el nombre del CC para enviarlo al ERP como `Descripcion` de cada ítem.
- El campo `erpdimparfincod` es el código de partida financiera para `DIMPARFIN`. ❓ **Pendiente definir cómo se obtiene.**
- El campo `erpprovinciaid` puede heredarse de la cabecera (`preoc.erpprovinciaid`) o definirse por línea si difiere.

---

## 4. `preocfirmantes` — Lista de Aprobadores

| Columna                        | Tipo           | NULL | Default | Descripción / Notas                                                                |
|--------------------------------|----------------|------|---------|------------------------------------------------------------------------------------|
| `preocfirmanteid`              | INT PK AI      | NO   |         | PK interna                                                                         |
| `preocid`                      | INT FK         | NO   |         | FK → `preoc`                                                                       |
| `firmanteusuarioid`            | INT FK         | NO   |         | FK → `usuarios`                                                                    |
| `firmantetipo`                 | TINYINT        | NO   |         | 1=Responsable Ppto, 2=Administrador Ppto, 3=Colaborador Ppto, 4=Por Monto, 5=Manual|
| `firmanteorden`                | INT            | NO   |         | Orden de firma. Editable por el comprador antes de grabar (drag & drop / flechas) |
| `firmanteestado`               | TINYINT        | NO   | 0       | 0=Pendiente, 1=Aprobado, 2=Rechazado, 3=Cambios solicitados                       |
| `firmanteobs`                  | TEXT           | SÍ   | NULL    | Motivo de rechazo o comentario                                                     |
| `firmantefechahora`            | DATETIME       | SÍ   | NULL    | Fecha/hora de firma. NULL si pendiente                                             |

**Constraint**: `UNIQUE (preocid, firmanteusuarioid)` — Un aprobador puede existir **solo una vez** en la lista.

### Lógica de generación de firmantes

Al completar los datos de la Pre OC (antes de grabar), el SP genera automáticamente la lista de firmantes en este orden por defecto:

```
1. Responsable del Presupuesto  (firmantetipo = 1)
2. Administrador del Presupuesto (firmantetipo = 2)
3. Colaborador del Presupuesto  (firmantetipo = 3)
4. Aprobadores por Monto        (firmantetipo = 4) — solo si preocnettotal > umbral
5. Aprobadores Manuales         (firmantetipo = 5) — agregados por el comprador
```

**Deduplicación**: Si el mismo usuario aparece en más de un rol (ej. Responsable y Administrador son la misma persona), se agrega **una sola vez** con el tipo/orden del primer rol donde aparece.

**Edición de orden**: El comprador puede modificar el orden antes de grabar mediante drag & drop o flechas en cada fila. La lista se muestra como **último paso** en el formulario de creación de la Pre OC.

**Al enviar (BRR → PND):**
- `preocaprobadorpendienteid` = firmante con `firmanteorden = 1`.
- Se notifica al primer firmante.

**Al aprobar firmante N:**
- Si hay N+1 → `preocaprobadorpendienteid = firmante[N+1]`.
- Si era el último → `preocaprobadorpendienteid = NULL`, estado → `APR`.
- Se actualiza el presupuesto: movimiento de "En proceso" → "Aprobada".
- Se inicia integración con Finnegans.

**Al rechazar / Cambios solicitados:**
- `preocaprobadorpendienteid = NULL`.
- El presupuesto devuelve el importe reservado (movimiento positivo de devolución).

---

## 5. Flujo de Estados

```
               ┌──────────── CREAR ──────────────────────┐
               ▼                                         │
          ┌─────────┐       Enviar       ┌─────────────┐ │
          │   BRR   │ ─────────────────► │     PND     │ │
          │Borrador │                    │  Pendiente  │ │
          └────┬────┘                    └──┬──┬──┬────┘ │
               │                           │  │  │      │
               │ Anular                    │  │  │      │
               ▼                           │  │  │      │
          ┌─────────┐                      │  │  │      │
          │   ANL   │ ◄────────────────────┘  │  │      │
          │ Anulada │                         │  │      │
          └─────────┘                         │  │      │
                                              │  │      │
          ┌──────────────── Último APR ───────┘  │      │
          ▼                                      │      │
     ┌─────────┐    Integrar ERP   ┌──────────┐  │      │
     │   APR   │ ────────────────► │   ERP    │  │      │
     │Aprobada │                   │Integrada │  │      │
     └─────────┘                   └──────────┘  │      │
                                                 │      │
          ┌──────────────── RCH ────────────────-┘      │
          ▼                                             │
     ┌─────────┐                                        │
     │   RCH   │                                        │
     │Rechazada│                                        │
     └─────────┘                                        │
                                                        │
          ┌──────────────── CSO ───────────────────────-┘
          ▼
     ┌─────────┐
     │   CSO   │  ── Comprador corrige ──► BRR / PND
     │ Cambios │
     └─────────┘
```

### Estados

| ID | Código | Descripción              | Editable | Notas                                                                |
|----|--------|--------------------------|-----------|----------------------------------------------------------------------|
| 1  | `BRR`  | Borrador                 | ✅ Sí    | En redacción. El comprador puede modificar libremente               |
| 2  | `PND`  | Pendiente de aprobación  | ❌ No    | Enviada a firmantes. El presupuesto reserve el importe              |
| 3  | `APR`  | Aprobada                 | ❌ No    | Todos aprobaron. Disponible para integrar a Finnegans               |
| 4  | `RCH`  | Rechazada                | ❌ No    | Definitivo. Presupuesto devuelve importe                            |
| 5  | `CSO`  | Cambios solicitados      | ✅ Sí    | El comprador corrige y reenvía. Presupuesto devuelve hasta reenvío  |
| 6  | `ANL`  | Anulada                  | ❌ No    | Baja lógica definitiva. Presupuesto devuelve importe si fue en PND  |
| 7  | `ERP`  | Integrada ERP            | ❌ No    | OC creada en Finnegans exitosamente                                 |
| 8  | `ERR`  | Error de integración     | ❌ No    | Fallo en POST a Finnegans. Permite reintento manual                 |

---

## 6. `preocaprobadoresxmonto` — Reglas de Aprobación por Monto

> Maestro de reglas para agregar aprobadores automáticos cuando el neto de la POC supera un umbral.

| Columna                        | Tipo           | NULL | Descripción                                                         |
|--------------------------------|----------------|------|---------------------------------------------------------------------|
| `preocaprobmontoid`            | INT PK AI      | NO   | PK interna                                                          |
| `usuarioid`                    | INT FK         | NO   | FK → `usuarios`. Aprobador que se agrega                            |
| `montominimo`                  | DECIMAL(15,2)  | NO   | Monto neto mínimo para activar este aprobador (ej. 1.000.000)      |
| `firmanteorden`                | INT            | NO   | Orden sugerido en la lista de firmantes de la POC                   |
| `preocaprobmontoactivo`        | TINYINT(1)     | NO   | 1=Activo, 0=Inactivo                                                |
| + 8 columnas de auditoría      |                |      | Estándar                                                            |

> [!NOTE]
> Cuando el total neto de la Pre OC supera el `montominimo`, este aprobador se agrega automáticamente a la lista de firmantes con su `firmanteorden` sugerido. El comprador puede ajustar el orden final.

---

## 7. `preoclog` — Log de Auditoría

| Columna              | Tipo           | Descripción                                        |
|----------------------|----------------|----------------------------------------------------|
| `preocid`            | INT FK         | FK → `preoc`                                       |
| `logid`              | INT PK AI      | PK del log                                         |
| `logusuarioid`       | INT            | `p_in_usuarioid`                                   |
| `logdispositivo`     | VARCHAR(100)   | `p_in_dispositivo`                                 |
| `logip`              | VARCHAR(50)    | `p_in_ip`                                          |
| `logfechahora`       | DATETIME       | NOW()                                              |
| `logtipo`            | VARCHAR(3)     | Ver tabla abajo                                    |
| `logparamjson`       | JSON           | `p_in_json`                                        |
| `logregbkpjson`      | JSON           | Registro antes de modificación                     |

| `logtipo` | Significado          | Cuándo se registra                                          |
|-----------|----------------------|-------------------------------------------------------------|
| `INS`     | Inserción            | Al **enviar** la POC (BRR → PND)                           |
| `UPD`     | Actualización        | Al confirmar edición tras CSO                               |
| `ANL`     | Anulación            | Baja lógica (→ ANL)                                         |
| `APR`     | Aprobación           | Un firmante aprueba                                         |
| `RCH`     | Rechazo              | Un firmante rechaza                                         |
| `CSO`     | Cambios solicitados  | Un firmante solicita cambios                                |
| `ERP`     | Integración ERP      | POST a Finnegans exitoso (→ ERP)                            |
| `ERR`     | Error ERP            | POST a Finnegans falló (→ ERR). Incluye respuesta de error |

---

## 8. Módulo de Presupuesto

### 8.1. Estructura jerárquica

```
pptocompraclasif (Clasificación)       → Capex / Opex
    └── pptocomprasubclasif (Sub)      → Salud Animal
            └── pptocompra (Presupuesto) → Insumos Veterinarios — T1-26
```

### 8.2. `pptocompraclasif` — Clasificación (Nivel 1)

| Columna                    | Tipo           | NULL | Descripción                    |
|----------------------------|----------------|------|--------------------------------|
| `pptocompraclasifid`       | INT PK AI      | NO   | PK interna                     |
| `pptocompraclasifcod`      | VARCHAR(20)    | NO   | Código (ej. `CAPEX`, `OPEX`)   |
| `pptocompraclasifdsc`      | VARCHAR(100)   | NO   | Descripción                    |
| `pptocompraclasifactivo`   | TINYINT(1)     | NO   | 1=Activo                       |
| + 8 columnas de auditoría  |                |      | Estándar                       |

### 8.3. `pptocomprasubclasif` — Sub-Clasificación (Nivel 2)

| Columna                       | Tipo           | NULL | Descripción                          |
|-------------------------------|----------------|------|--------------------------------------|
| `pptocomprasubclasifid`       | INT PK AI      | NO   | PK interna                           |
| `pptocompraclasifid`          | INT FK         | NO   | FK → `pptocompraclasif`              |
| `pptocomprasubclasifdsc`      | VARCHAR(100)   | NO   | Descripción (ej. `Salud Animal`)     |
| `pptocomprasubclasifactivo`   | TINYINT(1)     | NO   | 1=Activo                             |
| + 8 columnas de auditoría     |                |      | Estándar                             |

### 8.4. `pptocompra` — Presupuesto (Nivel 3)

| Columna                    | Tipo           | NULL | Default | Descripción / Notas                                                        |
|----------------------------|----------------|------|---------|----------------------------------------------------------------------------|
| `pptocompraid`             | INT PK AI      | NO   |         | PK interna                                                                 |
| `pptocomprasubclasifid`    | INT FK         | NO   |         | FK → `pptocomprasubclasif`                                                 |
| `pptocompranombre`         | VARCHAR(100)   | NO   |         | Nombre descriptivo (ej. `Insumos Veterinarios`)                            |
| `pptocompraperiodo`        | VARCHAR(20)    | NO   |         | Período codificado: `A-2026`, `T1-26`, `S2-26`, `M04-26`                  |
| `pptocompratipoper`        | CHAR(1)        | NO   |         | Tipo de período: `A`=Anual, `T`=Trimestral, `S`=Semestral, `M`=Mensual   |
| `pptocomprafechadesde`     | DATE           | SÍ   | NULL    | Fecha de inicio del período (referencial, para reportes)                   |
| `pptocomprafechahasta`     | DATE           | SÍ   | NULL    | Fecha de fin del período (referencial)                                     |
| `pptoinicial`              | DECIMAL(15,2)  | NO   | 0.00    | Saldo inicial cargado al crear el presupuesto                              |
| `pptoajustes`              | DECIMAL(15,2)  | NO   | 0.00    | Suma de ajustes manuales (calculado desde movimientos)                     |
| `pptoreproyectado`         | DECIMAL(15,2)  | NO   | 0.00    | CALC: `pptoinicial + pptoajustes`                                          |
| `pptoconsumos`             | DECIMAL(15,2)  | NO   | 0.00    | CALC: suma de consumos en estado PND/APR (reservas activas)                |
| `pptosaldo`                | DECIMAL(15,2)  | NO   | 0.00    | CALC: `pptoreproyectado − pptoconsumos`                                    |
| `pptocompraactivo`         | TINYINT(1)     | NO   | 1       | 1=Activo (visible para compradores). 0=Inactivo                            |
| + 8 columnas de auditoría  |                |      |         | Estándar. Solo Gerencia puede crear/editar                                 |

> [!IMPORTANT]
> Los campos calculados (`pptoajustes`, `pptoreproyectado`, `pptoconsumos`, `pptosaldo`) se recalculan por SP a partir de `pptocompramovimientos`. No se editan directamente.

### 8.5. `pptocompramovimientos` — Kardex del Presupuesto

> Cada movimiento que afecta el saldo del presupuesto genera un registro aquí. Funciona como un extracto bancario o kardex.

| Columna                        | Tipo           | NULL | Descripción                                                                    |
|--------------------------------|----------------|------|--------------------------------------------------------------------------------|
| `pptomovid`                    | INT PK AI      | NO   | PK interna                                                                     |
| `pptocompraid`                 | INT FK         | NO   | FK → `pptocompra`                                                              |
| `preocid`                      | INT FK         | SÍ   | FK → `preoc`. NULL si es ajuste manual                                         |
| `pptomovtipo`                  | TINYINT        | NO   | 1=Saldo inicial, 2=Ajuste manual, 3=Consumo POC PND, 4=Consumo POC APR, 5=Devolución |
| `pptomovimporte`               | DECIMAL(15,2)  | NO   | Importe del movimiento. Positivo (+) suma, negativo (−) resta                  |
| `pptomovconcepto`              | VARCHAR(200)   | SÍ   | Descripción del movimiento (ej. "Reserva POC-000023")                         |
| `pptomovfechahora`             | DATETIME       | NO   | Fecha/hora del movimiento                                                      |
| `pptomovusuarioid`             | INT FK         | NO   | FK → `usuarios`. Quién generó el movimiento                                    |
| + 4 columnas auditoría creación|                |      | Solo creación (movimientos no se editan)                                       |

**Tipos de movimiento y su impacto:**

| Tipo | Código | Signo | Cuándo se genera |
|------|--------|-------|-----------------|
| Saldo inicial | 1 | `+` | Al crear el presupuesto |
| Ajuste manual | 2 | `+/-` | Gerencia hace ajuste (opción específica en UI) |
| Consumo POC → PND | 3 | `−` | Al enviar la Pre OC a aprobación. Estado: "En proceso" |
| Consumo POC → APR | 4 | sin cambio neto | Al aprobar la Pre OC. Cambia el estado del movimiento anterior de "En proceso" a "Aprobado" |
| Devolución | 5 | `+` | Al rechazar, anular, o cambios solicitados en POC. Devuelve el importe al presupuesto |

> [!NOTE]
> Los movimientos de tipo 3 (POC en PND) bloquean el saldo disponible desde el momento del envío. Si la POC es rechazada o anulada, se genera automáticamente un movimiento de tipo 5 (devolución).

---

## 9. Integración con Finnegans

### 9.1. Cuándo se integra
- Cuando la Pre OC alcanza el estado `APR` (todos los firmantes aprobaron).
- El SP genera el JSON y hace el POST al endpoint de Finnegans.
- Si el POST es exitoso → estado `ERP`, guarda `erptransaccionid`, `erpnumerodoc`, `erprespuestajson`.
- Si el POST falla → estado `ERR`, guarda error en `erprespuestajson`. El comprador puede reintentar desde la UI.

### 9.2. Mapeo de campos Pre OC → Finnegans

| Campo Puduhue App                | Campo Finnegans JSON                  | Valor / Lógica                                                    |
|----------------------------------|---------------------------------------|-------------------------------------------------------------------|
| `preoctipo = 1` (Material)       | `TransaccionSubtipoCodigo`            | `"OC"`                                                            |
| `preoctipo = 2` (Servicio)       | `TransaccionSubtipoCodigo`            | `"OCSS"`                                                          |
| `preocdoc`                       | `IdentificacionExterna`               | Código legible de la POC                                          |
| `preocfecha`                     | `Fecha`                               | Fecha de creación                                                 |
| `proveedores.proveedorrut`       | `Proveedor`                           | RUT del proveedor (ej. `"82392600-6"`)                            |
| `condicionespago.condicionpagocod`| `CondicionPagoCodigo`               | Código de condición de pago (ej. `"30"`)                          |
| `erpworkflows.erpworkflowcod`    | `WorkflowCodigo`                      | Workflow de compra (ej. `"CPRA-INS-SERV"`)                        |
| `erpmonedas.erpmonedacod`        | `MonedaCodigo`                        | Moneda (siempre `"PES"` por defecto)                              |
| (fijo)                           | `TransaccionTipoCodigo`               | `"OPER"` (fijo para compras)                                      |
| `empresas.erpempresacod`         | `EmpresaCodigo`                       | Código de empresa en Finnegans                                    |
| `preocdoc`                       | `Nombre`                              | Nombre de la OC (ej. `"POC-000023"`)                              |
| `preocobs`                       | `Descripcion`                         | Observación general                                               |
| `preocdetalle[n].invitemid`      | `Items[n].ProductoCodigo`             | Código ERP del producto (`invitems.erpitemcod`)                   |
| `preocdetalle[n].preocdetcantidad`| `Items[n].Cantidad`                  | Cantidad a comprar                                                |
| `preocdetalle[n].preocdetcantidad`| `Items[n].CantidadPendiente`         | Igual a `Cantidad` al crear                                       |
| `preocdetalle[n].preocdetprecioneto`| `Items[n].Precio`                  | Precio unitario neto                                              |
| `preocdetalle[n].preocdetdsccc`  | `Items[n].Descripcion`                | **Nombre del CC** de esa línea (para identificar en ERP)          |
| `preocdetalle[n].erpprovinciaid` | `Items[n].ProvinciaDestino`           | Código provincia destino ERP (ej. `"RDLL"`)                       |
| `preocdetalle[n].erpdimparfincod`| `DimensionDistribucion[DIMPARFIN]`    | Código de partida financiera. ❓ **Pendiente definir origen**     |
| `centroscosto.erpcentrocostocod` | `DimensionDistribucion[DIMCTC]`       | Código de Centro de Costo en ERP                                 |
| `erpcatfiscalconceptos.*`        | `Conceptos[]`                         | Array generado desde la categoría fiscal del proveedor            |
| `preocfecha`                     | `FechaBaseVencimiento`                | Fecha de la OC como base de vencimiento                           |
| `erpprovinciasdestino.erprovinciacod`| `ProvinciaDestinoCodigo`          | Provincia destino global de la cabecera                           |

### 9.3. Campos ERP adicionales en detalle (por línea)

Cada línea de `preocdetalle` requiere los códigos de las **DimensionDistribucion** para el POST al ERP:

| Campo adicional en `preocdetalle` | Descripción |
|-----------------------------------|-------------|
| `dimparfincod` | Código de partida financiera (`DIMPARFIN`) para esta línea |
| `dimparfinimporte` | Importe para `DIMPARFIN` (= subtotal neto de la línea) |
| `erpprovcod` | Código de provincia destino ERP en `ProvinciaDestino` e ítem |

> [!IMPORTANT]
> Para los campos de `DimensionDistribucion`, se deben analizar los ejemplos reales provistos:
> - `docs/inputs/mejoras_mar_abr_26/erp_oc_material_ejemplo.json`
> - `docs/inputs/mejoras_mar_abr_26/erp_oc_servicio_ejemplo.json`
>
> El campo `DIMPARFIN` corresponde a la partida financiera (presupuesto ERP). El código de la partida (`REB000`, `LEC000`, `CRM000`) debe ser determinado por el cliente. ❓ **Pendiente confirmar**: ¿cómo se obtiene el código `DIMPARFIN` desde el presupuesto de Puduhue App?

---

## 10. Diagrama de Relaciones

```
┌─────────────────────────────┐
│    pptocompraclasif         │
│ pptocompraclasifid          │
└──────────┬──────────────────┘
           ▼
┌─────────────────────────────┐
│    pptocomprasubclasif      │
│ pptocomprasubclasifid       │
│ pptocompraclasifid FK       │
└──────────┬──────────────────┘
           ▼
┌─────────────────────────────┐     ┌──────────────────────────┐
│       pptocompra            │────►│   pptocompramovimientos   │
│ pptocompraid                │     │ pptomovtipo (1-5)         │
│ pptoperiodo / tipo          │     │ pptomovimporte (+/-)      │
│ pptoinicial                 │     │ preocid FK (opt)          │
│ pptosaldo (calc)            │     └──────────────────────────┘
└──────────┬──────────────────┘
           │ FK pptocompraid
           ▼
┌──────────────────────────────────────────────────────────┐
│                         preoc                            │
│ preocid       preoctipo (1=OC / 2=OCSS)                  │
│ preocdoc      preocfecha (no editable)                   │
│ proveedorid FK   condicionpagoid FK   pptocompraid FK    │
│ preocnettotal / preociva / preoctotal                    │
│ erptransaccionid   erpnumerodoc   erprespuestajson        │
│ preocestadoid FK                                         │
└────────────┬──────────────┬───────────────────-----------┘
             │              │
             ▼              ▼
┌────────────────┐  ┌───────────────────────┐
│  preocdetalle  │  │    preocfirmantes      │
│ preocdetid     │  │ firmanteusuarioid FK   │
│ reqaprobadoid FK│  │ firmantetipo (1-5)    │
│ invitemid FK   │  │ firmanteorden (reord.) │
│ centrocostoid FK│  │ firmanteestado        │
│ preocdetdsccc  │  └───────────────────────┘
│ (nombre del CC │
│  para el ERP)  │
└────────────────┘
       │
       ▼
┌───────────────────────────────────┐
│          reqaprobados             │
│ reqaprobadoid                     │
│ cantidadoriginal / cantidadpendiente │
│ estado (Pendiente/Parcial/Completa)│
└───────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────┐
│      reqaprobadoshistorial        │
│ preocid FK / preocdetid FK        │
│ histcantidad / histprecioneto     │
└──────────────────────────────────┘
```

---

## 11. Flujo Completo

```
1. CREAR (BRR)
   → Comprador (atributo comprador = 1) inicia la Pre OC
   → Selecciona proveedor, condición de pago
   → Selecciona líneas de `reqaprobados` (estado Pendiente o Parcial)
   → Define cantidades a comprar por cada línea (≤ cantidad pendiente)
   → Ingresa precio por línea
   → El SP genera lista de firmantes (responsable/admin/colaborador ppto + por monto)
   → El comprador puede reordenar firmantes (drag & drop / flechas)
   → Selecciona el presupuesto a aplicar (activo, con saldo disponible ≥ total neto)
   → Visualiza la lista final de firmantes como último paso antes de grabar
   → Graba en estado BRR

2. ENVIAR (BRR → PND)
   → SP valida saldo presupuestario (bloqueante si no hay saldo)
   → SP genera log INS
   → SP genera movimiento de tipo 3 (Consumo en proceso) en `pptocompramovimientos`
   → preocaprobadorpendienteid = firmante[1]
   → Notificación al primer firmante

3. APROBAR (PND)
   → Firmante N firma → log APR
   → Si hay N+1 → preocaprobadorpendienteid = firmante[N+1]
   → Si era el último → estado APR
   → SP actualiza movimiento ppto: tipo 3 → tipo 4 (Aprobado)
   → SP inicia integración con Finnegans (construye JSON, hace POST)
   → Si éxito → estado ERP, guarda erptransaccionid y erpnumerodoc
   → Si falla → estado ERR, guarda error en erprespuestajson

4. RECHAZAR / CAMBIOS SOLICITADOS
   → firmante rechaza → estado RCH
   → SP genera movimiento tipo 5 (devolución) en presupuesto
   → CSO: comprador corrige → reenvía → PND
   → SP genera nuevo movimiento tipo 3 al reenviar

5. ANULAR (cualquier estado editable)
   → estado ANL (baja lógica)
   → Si estaba en PND: SP genera movimiento tipo 5 (devolución)
   → reqaprobados: las cantidades vinculadas se devuelven a `cantidadpendiente`

6. REINTENTO ERP (estado ERR)
   → Comprador o admin presiona "Reintentar envío a ERP"
   → SP vuelve a construir JSON y hace POST
   → Misma lógica de éxito/error
```

---

## 12. Resumen de Atributos Nuevos en `usuarios`

| Atributo           | Descripción                                                       | Quién puede tenerlo |
|--------------------|-------------------------------------------------------------------|---------------------|
| `autorizareq`      | Puede ser firmante/aprobador de REQ                               | Jefes de CC, gerentes |
| `editarprecios`    | Puede editar precios en REQ                                       | Usuarios autorizados |
| `comprador`        | Puede Crear/Editar/Anular Pre OC                                  | Secretarias/compradores |
| `permitecreareditar`| Puede Crear/Editar productos en Maestro de Productos             | Administradores |

---

## 13. Preguntas Pendientes (PreOC)

❓ Aún sin confirmar:

| # | Pregunta |
|---|----------|
| 1 | ¿El Maestro de Proveedores se sincroniza desde Finnegans (espejo) o se mantiene manualmente? |
| 2 | ¿Las Condiciones de Pago son texto libre o se sincronizan desde el ERP? |
| 3 | ¿Cómo se obtiene el código `DIMPARFIN` (partida financiera) para mapear al presupuesto ERP desde el presupuesto de Puduhue App? |
| 4 | ¿Quién son el Responsable, Administrador y Colaborador del Presupuesto? ¿Son columnas en `pptocompra` o roles configurables? |
| 5 | ¿Se puede anular una OC ya enviada a Finnegans desde esta app? |
| 6 | ¿El campo `ProvinciaDestino` en cada ítem del ERP siempre corresponde al fundo/provincia del CC, o es un valor fijo? |
| 7 | ¿La recepción de la OC en ERP se integrará en el historial del REQ en etapa futura? (Ya se contempla en el diseño) |
