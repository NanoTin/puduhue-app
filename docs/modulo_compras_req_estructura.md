# Módulo REQ (Requerimiento de Compra) — Estructura de Datos v3

> [!NOTE]
> Diseño detallado de tablas para el módulo de Requerimiento de Compra (REQ).
> En Finnegans: "Pedido de Compra". En Puduhue App: "Requerimiento".
>
> Convenciones aplicadas del proyecto:
> - PK: `<tabla>id` INT AUTO_INCREMENT.
> - Baja lógica: `<tabla>vig` TINYINT(1).
> - Auditoría: 8 columnas estándar (4 creación + 4 edición). Ver README §7.1.
> - LOG: tabla separada `<tabla>log` con `logid`, `logtipo`, `logparamjson`, `logregbkpjson`. Ver README §7.2.

### Decisiones confirmadas

| # | Decisión | Resolución |
|---|----------|------------|
| 1 | Estado Borrador | ✅ Confirmado. REQ inicia en BRR |
| 2 | Bodegas/Fundos | ✅ Fundos fuera del flujo de compras. CC es independiente (incluye dptos como Adquisiciones, IT, Contabilidad) |
| 3 | Precio editable | ✅ Sí, pero solo usuarios con atributo `editarprecios = 1` |
| 4 | Historial precios | ✅ No se crea tabla separada. Se obtiene de las POC históricas |
| 5 | Edición en Pendiente | ✅ Sí, con nuevo estado `EDT` (En Edición) + control de concurrencia |
| 6 | REQ → POC | ✅ Múltiples REQs → 1 POC. Compra parcial posible. Nueva tabla intermedia `reqaprobados` |
| 7 | Vinculación parcial | ✅ Respondido en #6 — por cantidades parciales, no por líneas |
| 8 | DELETE de líneas | ✅ Físico. Sin log al crear; con log al editar (al confirmar modificación) |
| 9 | `empresaid` | ✅ Se incluye, inferido del CC. CC tiene `empresaid` FK |
| 10 | Última solicitud | ✅ JOIN dinámico, sin columnas duplicadas |
| 11 | Tipos REQ | ✅ Separados: Material (EsCompra+EsStockeable) vs Servicio (solo EsCompra). Nunca mixtos |
| 12 | Aprobación parcial | ✅ No. La autorización es general (todo el REQ) |
| 13 | Borrador | ✅ Confirmado. Existe estado BRR |
| 14 | Solicitante asignado | ✅ Campo separado al creador. FK → `funcionarios`. El CC se carga del funcionario, editable |
| 15 | Aprobador por defecto | ✅ Jefe del CC (Maestro de Centros). Creador puede agregar más firmantes |
| 16 | REQ rechazado | ✅ Puede corregirse. Solo ANL bloquea toda modificación |
| 17 | Inactividad aprobador | ✅ Nueva transacción: aprobador → reemplazo + fechas. Estado especial "Omitido por inactividad" |
| 18 | Moneda | ✅ Solo CLP |
| 19 | Módulo en proyecto actual | ✅ Se integra a Puduhue App Web |

---

## 1. Tablas Nuevas — Resumen

| Tabla                           | Tipo           | Descripción                                                  |
|---------------------------------|----------------|--------------------------------------------------------------|
| `reqcompras`                    | Transaccional  | Cabecera del Requerimiento                                   |
| `reqcomprasdetalle`             | Transaccional  | Detalle (ítems) del Requerimiento                            |
| `reqcomprasfirmantes`           | Transaccional  | Lista de firmantes/aprobadores por REQ                       |
| `reqcompraslog`                 | LOG            | Registro de acciones (INS/UPD/ANL/APR/RCH/CSO/EDT/CMB)      |
| `reqcomprasestados`             | Maestro        | Catálogo de estados para REQ                                 |
| `reqaprobados`                  | Transaccional  | Líneas aprobadas listas para compra (link REQ → POC)         |
| `reqaprobadoshistorial`         | Transaccional  | Historial de movimientos de cada línea aprobada              |
| `reqaprobadoscambios`           | Transaccional  | Historial de cambios de ítem realizados por el comprador     |
| `pocestados`                    | Maestro        | Catálogo de estados para Pre OC (flujo independiente)        |
| `centroscosto`                  | Maestro        | Centros de costo (sync Finnegans + editable localmente)      |
| `usuarioscentroscosto`          | Asociación     | Centros de costo accesibles por usuario                      |
| `funcionarios`                  | Maestro        | Funcionarios de la empresa (solicitantes / aprobadores)      |
| `funcionariosinactividad`       | Transaccional  | Períodos de inactividad de aprobadores + reemplazo           |

### Tablas existentes a modificar

| Tabla              | Cambio                                                                                                    |
|--------------------|-----------------------------------------------------------------------------------------------------------|
| `invitems`         | Agregar `invitemtipo`, `invitemprecioref`, `invitemcomprable`, `invitemmodulo`, `invitemcompra`, `invitemventa`, `inviteminventario`, `invitemvig` |
| `usuarios`         | Agregar `autorizareq`, `editarprecios`, `comprador`, `permitecreareditar`                                 |

---

## 2. `reqcompras` — Cabecera del Requerimiento

| Columna                        | Tipo           | NULL | Default    | Descripción / Notas                                                          |
|--------------------------------|----------------|------|------------|------------------------------------------------------------------------------|
| `reqcompraid`                  | INT PK AI      | NO   |            | PK interna                                                                   |
| `reqcompracod`                 | VARCHAR(20)    | NO   |            | Código visible: `REQ-000001`. Autogenerado, UNIQUE                           |
| `empresaid`                    | INT FK         | NO   |            | FK → `empresas`. Inferido del CC del solicitante                             |
| `reqcompratipo`                | TINYINT        | NO   |            | 1=Material, 2=Servicio. **Nunca mixto**                                      |
| `reqcomprafecha`               | DATE           | NO   | CURDATE()  | Fecha de creación. No editable. Fijada por SP                                |
| `centrocostoid`                | INT FK         | NO   |            | FK → `centroscosto`. Se carga del CC del solicitante, editable               |
| `funcionarioid`                | INT FK         | NO   |            | FK → `funcionarios`. Solicitante asignado (≠ usuario creador)                |
| `reqcompraobs`                 | TEXT           | SÍ   | NULL       | Observación genérica del requerimiento (cabecera)                            |
| `reqcompraestadoid`            | INT FK         | NO   |            | FK → `reqcomprasestados`. Estado actual del flujo                            |
| `reqcompraaprobadorpendienteid`| INT FK         | SÍ   | NULL       | FK → `usuarios`. Siguiente firmante. NULL cuando BRR/APR/RCH/ANL/EDT        |
| `reqcompranettotal`            | DECIMAL(15,2)  | NO   | 0.00       | Suma total neto del detalle (recalculado por SP)                             |
| `reqcompravig`                 | TINYINT(1)     | NO   | 1          | 1=vigente, 0=anulado                                                         |
| + 8 columnas de auditoría      |                |      |            | Estándar del proyecto                                                        |

**Índices:**
- `PK (reqcompraid)`
- `UNIQUE (reqcompracod)`
- `FK → empresas`, `FK → centroscosto`, `FK → funcionarios`, `FK → reqcomprasestados`, `FK → usuarios` (nullable)

---

## 3. `reqcomprasdetalle` — Detalle (Ítems)

| Columna                        | Tipo           | NULL | Default    | Descripción / Notas                                  |
|--------------------------------|----------------|------|------------|-------------------------------------------------------|
| `reqcompradetid`               | INT PK AI      | NO   |            | PK interna                                            |
| `reqcompraid`                  | INT FK         | NO   |            | FK → `reqcompras`                                     |
| `reqcompradetlinea`            | INT            | NO   |            | Nro de línea (1, 2, 3... renumerable al eliminar)     |
| `invitemid`                    | INT FK         | NO   |            | FK → `invitems`                                       |
| `reqcompradetdsc`              | VARCHAR(200)   | NO   |            | Descripción (copiada de `invitems.invitemdsc`)        |
| `invunidmedid`                 | INT FK         | NO   |            | FK → `invunidadesmedidas`                             |
| `reqcompradetcantidad`         | DECIMAL(15,4)  | NO   |            | Cantidad solicitada                                   |
| `reqcompradetprecioneto`       | DECIMAL(15,2)  | NO   | 0.00       | Precio neto unitario (del maestro, editable con permiso) |
| `reqcompradettotalneto`        | DECIMAL(15,2)  | NO   | 0.00       | CALC: `cantidad × precioneto`                         |
| `invbodegaid`                  | INT FK         | SÍ   | NULL       | Bodega central (opcional, por línea)                  |
| `reqcompradetobs`              | TEXT           | SÍ   | NULL       | Observación por línea (opcional)                      |

**Reglas de filtrado en front:**
- Mostrar solo ítems donde `invitemcomprable = 1` AND `invitemactivo = 1`
- Si `reqcompratipo = 1` (Material) → solo `invitemtipo = 1`
- Si `reqcompratipo = 2` (Servicio) → solo `invitemtipo = 2`

**Eliminación de líneas:**
- **Al crear (BRR)**: DELETE físico, sin log. Renumerar líneas.
- **Al editar (EDT)**: DELETE físico + log `UPD` en `reqcompraslog` al confirmar modificación. Renumerar líneas. Recalcular `reqcompranettotal`.

### Datos de la Última Solicitud (JOIN dinámico)

Se obtienen en el SP de consulta, no se almacenan:
```sql
-- Para cada línea, obtener la última solicitud del mismo ítem:
LEFT JOIN (
    SELECT rd2.invitemid, MAX(rc2.reqcompraid) AS last_reqid
    FROM reqcomprasdetalle rd2
    JOIN reqcompras rc2 ON rd2.reqcompraid = rc2.reqcompraid
    WHERE rc2.reqcompraid < @current_reqid AND rc2.reqcompravig = 1
    GROUP BY rd2.invitemid
) AS last_req ON rd.invitemid = last_req.invitemid
-- → anterior_fecha, anterior_cantidad, anterior_precio, anterior_solicitante
```

---

## 4. `reqcomprasfirmantes` — Lista de Aprobadores

| Columna                        | Tipo           | NULL | Default    | Descripción / Notas                                                      |
|--------------------------------|----------------|------|------------|--------------------------------------------------------------------------|
| `reqcomprafirmanteid`          | INT PK AI      | NO   |            | PK interna                                                               |
| `reqcompraid`                  | INT FK         | NO   |            | FK → `reqcompras`                                                       |
| `firmanteusuarioid`            | INT FK         | NO   |            | FK → `usuarios` (con `autorizareq = 1`)                                 |
| `firmanteorden`                | INT            | NO   |            | Orden de firma (1, 2, N). Reordenable por creador                       |
| `firmanteestado`               | TINYINT        | NO   | 0          | 0=Pendiente, 1=Aprobado, 2=Rechazado, 3=Cambios solicitados, 4=Omitido por inactividad |
| `firmanteomitido`              | TINYINT(1)     | NO   | 0          | 1 = Omitido por inactividad (reemplazado por otro firmante)             |
| `firmantereemplazodeid`        | INT FK         | SÍ   | NULL       | FK → `reqcomprasfirmantes`. ID del firmante que fue reemplazado (si aplica) |
| `firmanteobs`                  | TEXT           | SÍ   | NULL       | Motivo de rechazo o comentario                                          |
| `firmantefechahora`            | DATETIME       | SÍ   | NULL       | Fecha/hora de firma. NULL si pendiente                                  |

**Constraint**: `UNIQUE (reqcompraid, firmanteusuarioid)`

### Lógica de firmantes

**Al crear REQ en BRR:**
1. Agregar automáticamente como firmante 1 al **jefe del CC** (`centroscosto.centrocostojefeusuarioid`).
2. El SP verifica si ese aprobador tiene una **transacción de inactividad** activa en ese período:
   - Si sí: agrega al aprobador con estado `Omitido por inactividad` + `firmanteomitido = 1`, y agrega al **reemplazante** como firmante pendiente.
   - Si no: agrega normalmente.
3. El creador puede agregar más firmantes (usuarios con `autorizareq = 1`).
4. Se pueden reordenar (`firmanteorden`).

**Al enviar (BRR → PND):**
- `reqcompraaprobadorpendienteid` = firmante con `firmanteorden = 1` y `firmanteomitido = 0`.
- Se notifica al primer firmante activo.

**Al aprobar firmante N:**
- Si hay firmante N+1 → `aprobadorpendienteid = firmante[N+1]`.
- Si era el último → `aprobadorpendienteid = NULL`, estado → `APR`.
- Cada línea aprobada se copia a `reqaprobados`.

**Al rechazar:**
- Estado → `RCH` (puede corregirse). Solo `ANL` bloquea toda modificación futura.
- `CSO` (cambios solicitados): editable, el creador corrige y reenvía → `PND`.
- `aprobadorpendienteid = NULL`.

---

## 5. Flujo de Estados

```
                 ┌─────────────────── CREAR ──────────────────────┐
                 ▼                                                │
            ┌─────────┐       Enviar        ┌─────────────┐      │
            │   BRR   │ ──────────────────► │     PND     │      │
            │Borrador │                     │  Pendiente  │      │
            └────┬────┘                     └──┬──┬──┬────┘      │
                 │                             │  │  │           │
                 │  Anular                     │  │  │           │
                 ▼                             │  │  │           │
            ┌─────────┐                        │  │  │           │
            │   ANL   │  ◄─────────────────────┘  │  │           │
            │ Anulada │                           │  │           │
            └─────────┘                           │  │           │
                                                  │  │           │
            ┌─────────┐     Editar (creador)      │  │           │
            │   EDT   │  ◄────────────────────────┘  │           │
            │En Edición│ ── Confirmar edición ───┐   │           │
            └─────────┘                          │   │           │
                 ▲                               ▼   │           │
                 │                          ┌────────┤           │
                 │                          │  PND   │           │
                 │                          └──┬─────┤           │
                 │                             │     │           │
                 │         Último firmante OK   │     │           │
                 │                             ▼     │           │
                 │                        ┌────────┐ │           │
                 │                        │  APR   │ │           │
                 │                        │Aprobada│ │           │
                 │                        └───┬────┘ │           │
                 │                            │      │           │
                 │                            ▼      │           │
                 │                        ┌────────┐ │           │
                 │                        │  VNC   │ │           │
                 │                        │Vinculada│ │           │
                 │                        └────────┘ │           │
                 │                                   │           │
                 │                                   ▼           │
                 │                             ┌──────────┐      │
                 │                             │   RCH    │      │
                 │                             │Rechazada │      │
                 │                             └──────────┘      │
                 │                                               │
                 │                              ┌──────────┐     │
                 └──────────────────────────────│   CSO    │     │
                      Editar tras cambios       │ Cambios  │     │
                      solicitados               │Solicitados│    │
                                                └──────────┘     │
```

### Control de Concurrencia (Estado EDT)

> [!IMPORTANT]
> Cuando el creador presiona "Editar" estando el REQ en `PND`:
> 1. El SP cambia el estado a `EDT` (En Edición) inmediatamente.
> 2. Si un aprobador intenta firmar un REQ en estado `EDT`, el SP rechaza la operación y retorna:
>    `"El requerimiento está siendo editado por el solicitante. Intente más tarde."`
> 3. Al confirmar la edición, el estado vuelve a `PND` y se actualiza `aprobadorpendienteid`.
> 4. Las firmas previas se mantienen (no se reinician las autorizaciones).
>
> **En front**: Al cargar la pantalla de aprobación, validar que el estado sea `PND`. Si es `EDT`, mostrar alerta y deshabilitar botón de firma.

---

## 6. `reqcomprasestados` — Maestro de Estados

| ID | Código | Descripción                 | Editable | Notas                                                       |
|----|--------|-----------------------------|-----------|-------------------------------------------------------------|
| 1  | `BRR`  | Borrador                    | ✅ Sí    | Creado, no enviado                                          |
| 2  | `PND`  | Pendiente de aprobación     | ❌ No    | Enviado a firmantes                                         |
| 3  | `EDT`  | En edición                  | ✅ Sí    | Creador editando después de enviar                          |
| 4  | `APR`  | Aprobada                    | ❌ No    | Todos los firmantes aprobaron                               |
| 5  | `RCH`  | Rechazada                   | ✅ Sí    | Puede corregirse y reenviarse (≠ ANL)                       |
| 6  | `CSO`  | Cambios solicitados         | ✅ Sí    | Firmante pide modificaciones                                |
| 7  | `ANL`  | Anulada                     | ❌ No    | Baja lógica definitiva. No puede modificarse                |
| 8  | `VNC`  | Vinculada a POC             | ❌ No    | Ya se generó Pre OC (total o parcial)                       |

**Estructura de la tabla:**

| Columna                  | Tipo           | Descripción              |
|--------------------------|----------------|--------------------------|
| `reqcompraestadoid`      | INT PK AI      | PK                       |
| `reqcompraestadocod`     | VARCHAR(5)     | Código corto             |
| `reqcompraestadodsc`     | VARCHAR(50)    | Descripción              |
| `reqcompraestadoeditable`| TINYINT(1)     | ¿El REQ es editable en este estado? |
| `reqcompraestadoactivo`  | TINYINT(1)     | Vigente                  |

---

## 7. `reqaprobados` — Líneas Aprobadas Listas para Compra

> [!NOTE]
> Tabla intermedia que separa la lógica del REQ de la lógica de la POC.
> Cada registro = 1 línea de un REQ aprobado, lista para ser comprada.
> Permite:
> - Consolidar múltiples REQs en 1 POC.
> - Compra parcial (parte de la cantidad ahora, el resto después).
> - El solicitante puede consultar el estado de su pedido.

| Columna                        | Tipo           | NULL | Default | Descripción                                           |
|--------------------------------|----------------|------|---------|-------------------------------------------------------|
| `reqaprobadoid`                | INT PK AI      | NO   |         | PK interna                                            |
| `reqcompradetid`               | INT FK         | NO   |         | FK → `reqcomprasdetalle`. Línea original del REQ      |
| `reqcompraid`                  | INT FK         | NO   |         | FK → `reqcompras` (desnormalizado para consultas rápidas) |
| `invitemid`                    | INT FK         | NO   |         | FK → `invitems` (desnormalizado)                      |
| `reqaprobadodsc`               | VARCHAR(200)   | NO   |         | Descripción (copiada del detalle REQ)                 |
| `reqaprobadocantidadoriginal`  | DECIMAL(15,4)  | NO   |         | Cantidad original del REQ                             |
| `reqaprobadocantidadpendiente` | DECIMAL(15,4)  | NO   |         | Cantidad aún sin vincular a POC. Inicia = original    |
| `reqaprobadoprecioneto`        | DECIMAL(15,2)  | NO   |         | Precio neto unitario (del REQ)                        |
| `reqaprobadoestado`            | TINYINT        | NO   | 1       | 1=Pendiente, 2=Parcial, 3=Completa, 4=Cancelada      |
| `reqaprobadofecha`             | DATE           | NO   |         | Fecha de aprobación                                   |
| + 4 columnas auditoría creación |               |      |         | Solo creación (se genera automáticamente)             |

**Índices:**
- `PK (reqaprobadoid)`
- `FK → reqcomprasdetalle`, `FK → reqcompras`, `FK → invitems`
- `IDX (reqaprobadoestado)` — para filtrar pendientes

### Flujo de `reqaprobados`

```
REQ Aprobada (APR)
    │
    ▼ SP copia cada línea del detalle
┌────────────────────────────┐
│       reqaprobados         │
│ estado = 1 (Pendiente)     │
│ cantidadpendiente = total  │
└──────────┬─────────────────┘
           │
           │ Al crear POC, se toman N líneas de aquí
           ▼
    ┌──────────────────────────────┐
    │ Si cantidadpendiente > 0    │ → estado = 2 (Parcial)
    │ Si cantidadpendiente = 0    │ → estado = 3 (Completa)
    └──────────────────────────────┘
```

---

## 8. `reqaprobadoshistorial` — Historial de Movimientos

> Cada vez que una línea aprobada se vincula (total o parcialmente) a una POC, se registra aquí.

| Columna                        | Tipo           | NULL | Descripción                                           |
|--------------------------------|----------------|------|-------------------------------------------------------|
| `reqaprobadohistid`            | INT PK AI      | NO   | PK interna                                            |
| `reqaprobadoid`                | INT FK         | NO   | FK → `reqaprobados`                                   |
| `preocid`                      | INT FK         | SÍ   | FK → `preoc` (Pre OC donde se vinculó)                |
| `preocdetid`                   | INT FK         | SÍ   | FK → `preocdetalle` (línea específica de la POC)      |
| `histcantidad`                 | DECIMAL(15,4)  | NO   | Cantidad vinculada en este movimiento                 |
| `histprecioneto`               | DECIMAL(15,2)  | NO   | Precio neto al momento de vincular                    |
| `histfechahora`                | DATETIME       | NO   | Fecha/hora del movimiento                             |
| `histusuarioid`                | INT FK         | NO   | FK → `usuarios`. Quién vinculó                        |
| `histobs`                      | TEXT           | SÍ   | Observación                                           |

### Ejemplo de compra parcial

```
REQ-000001, Línea 1: 100 kg de FER028
    │
    ├─► reqaprobados: cantidadoriginal=100, cantidadpendiente=100, estado=Pendiente
    │
    ├─► POC-000010 (compra 60 kg)
    │   └─ historial: cantidad=60, preocid=10
    │   └─ reqaprobados: cantidadpendiente=40, estado=Parcial
    │
    └─► POC-000015 (compra 40 kg)
        └─ historial: cantidad=40, preocid=15
        └─ reqaprobados: cantidadpendiente=0, estado=Completa
```

Esta estructura permite al solicitante ver:
- ✅ "60 kg comprados en POC-000010 el 15/03"
- ✅ "40 kg comprados en POC-000015 el 22/03"
- ✅ "0 kg pendientes — Completa"

---

## 9. `reqcompraslog` — Log de Auditoría

| Columna              | Tipo           | Descripción                                           |
|----------------------|----------------|-------------------------------------------------------|
| `reqcompraid`        | INT FK         | FK → `reqcompras`                                     |
| `logid`              | INT PK AI      | PK del log                                            |
| `logusuarioid`       | INT            | `p_in_usuarioid`                                      |
| `logdispositivo`     | VARCHAR(100)   | `p_in_dispositivo`                                    |
| `logip`              | VARCHAR(50)    | `p_in_ip`                                             |
| `logfechahora`       | DATETIME       | NOW()                                                 |
| `logtipo`            | VARCHAR(3)     | Ver tabla abajo                                       |
| `logparamjson`       | JSON           | `p_in_json`                                           |
| `logregbkpjson`      | JSON           | Registro antes de modificación                        |

| `logtipo` | Significado             | Cuándo se registra                                             |
|-----------|-------------------------|----------------------------------------------------------------|
| `INS`     | Inserción               | Al **enviar** el REQ (BRR→PND), no al crear borrador          |
| `UPD`     | Actualización           | Al confirmar edición (EDT→PND)                                 |
| `ANL`     | Anulación               | Baja lógica (→ANL)                                             |
| `APR`     | Aprobación              | Un firmante aprueba                                            |
| `RCH`     | Rechazo                 | Un firmante rechaza                                            |
| `CSO`     | Cambios solicitados     | Un firmante solicita cambios                                   |
| `EDT`     | En edición              | Creador inicia edición (PND→EDT)                               |
| `CMB`     | Cambio de ítem          | Comprador cambia un ítem en pantalla de pendientes de compra   |

**Regla**: Al crear/editar en estado BRR (borrador), NO se genera log. El log inicia al enviar (`INS`).

---

## 10. Maestros de Soporte

### 10.1. `centroscosto` — Centros de Costo

| Columna                    | Tipo           | NULL | Descripción                                                                     |
|----------------------------|----------------|------|---------------------------------------------------------------------------------|
| `centrocostoid`            | INT PK AI      | NO   | PK interna                                                                      |
| `empresaid`                | INT FK         | SÍ   | FK → `empresas`. Nullable hasta definir regla de asignación, porque el endpoint ERP no trae empresa |
| `centrocostocod`           | VARCHAR(50)    | NO   | Código visible                                                                  |
| `centrocostodsc`           | VARCHAR(100)   | NO   | Descripción (sync desde Finnegans)                                              |
| `erpcentrocostocod`        | VARCHAR(50)    | NO   | Código en Finnegans (`DIMCTC`)                                                  |
| `centrocostojefeusuarioid` | INT FK         | SÍ   | FK → `usuarios`. Jefe del centro (firmante 1 por defecto). **Editable localmente** |
| `centrocostoactivo`        | TINYINT(1)     | NO   | Vigente (sync desde Finnegans)                                                  |
| `sincfechahora`            | DATETIME       | SÍ   | Fecha de última sincronización desde ERP                                        |
| + 8 columnas de auditoría  |                |      | Estándar                                                                        |

> [!NOTE]
> **Estrategia híbrida**: Los datos base (`centrocostocod`, `centrocostodsc`, `erpcentrocostocod`, `centrocostoactivo`) se sincronizan desde Finnegans por cron. En pantalla **no hay eventos CRUD** — solo botón Exportar y botón Sincronizar On-Demand. Los atributos locales (`centrocostojefeusuarioid`) son editables localmente.

### 10.2. `usuarioscentroscosto` — Asociación Usuarios ↔ CC

> Patrón `usuariosfundos`: PK compuesta, solo auditoría de creación.

| Columna                    | Tipo           | Descripción                                  |
|----------------------------|----------------|-----------------------------------------------|
| `usuarioid`                | INT FK         | FK → `usuarios`                               |
| `centrocostoid`            | INT FK         | FK → `centroscosto`                           |
| + 4 columnas auditoría creación |           | Estándar                                      |
| `PK (usuarioid, centrocostoid)` |           |                                               |

### 10.3. `funcionarios` — Maestro de Funcionarios (nuevo)

> Carga inicial desde Excel. Sincronización: si un funcionario no viene en el Excel, se desactiva.

| Columna                    | Tipo           | NULL | Descripción                                  |
|----------------------------|----------------|------|-----------------------------------------------|
| `funcionarioid`            | INT PK AI      | NO   | PK interna                                    |
| `funcionariorut`           | VARCHAR(20)    | NO   | RUT del funcionario. UNIQUE                   |
| `funcionarionombre`        | VARCHAR(100)   | NO   | Nombre completo                               |
| `funcionariocargo`         | VARCHAR(100)   | SÍ   | Cargo                                         |
| `centrocostoid`            | INT FK         | SÍ   | FK → `centroscosto`. CC al que pertenece      |
| `funcionarioemail`         | VARCHAR(150)   | SÍ   | Correo electrónico                            |
| `funcionarioactivo`        | TINYINT(1)     | NO   | 1=Activo, 0=Inactivo                          |
| + 8 columnas de auditoría  |                |      | Estándar                                      |

### 10.4. `funcionariosinactividad` — Períodos de Inactividad (nuevo)

> Solo aplica a aprobadores (usuarios con `autorizareq = 1`). Permite registrar un período de ausencia y definir reemplazante.

| Columna                        | Tipo           | NULL | Descripción                                                 |
|--------------------------------|----------------|------|-------------------------------------------------------------|
| `funcionarioinactiviadid`      | INT PK AI      | NO   | PK interna                                                  |
| `usuarioid`                    | INT FK         | NO   | FK → `usuarios`. Aprobador que se ausenta                   |
| `usuarioreemplazoid`           | INT FK         | NO   | FK → `usuarios`. Aprobador reemplazante                     |
| `motivoinactividad`            | TINYINT        | NO   | 1=Vacaciones, 2=Licencia, 3=Permiso, 4=Otro                 |
| `fechadesde`                   | DATE           | NO   | Inicio del período de inactividad                           |
| `fechahasta`                   | DATE           | NO   | Fin del período (incluido)                                  |
| `inactividadactivo`            | TINYINT(1)     | NO   | 1=Vigente, 0=Cancelado                                      |
| + 8 columnas de auditoría      |                |      | Estándar                                                    |

> [!NOTE]
> Al generar la lista de firmantes de un REQ, el SP verifica si el aprobador tiene inactividad activa cuyo rango incluye la fecha actual. Si sí, agrega al aprobador con `firmanteomitido = 1` y al reemplazante como firmante activo pendiente.

### 10.5. `reqaprobadoscambios` — Historial de Cambios de Ítem (nuevo)

> Permite al comprador cambiar un ítem del requerimiento en la pantalla de "Pendientes de Compra", dejando trazabilidad del cambio.

| Columna                        | Tipo           | NULL | Descripción                                                     |
|--------------------------------|----------------|------|-----------------------------------------------------------------|
| `reqcambioid`                  | INT PK AI      | NO   | PK interna                                                      |
| `reqaprobadoid`                | INT FK         | NO   | FK → `reqaprobados`. Línea afectada                             |
| `invitemidoriginal`            | INT FK         | NO   | FK → `invitems`. Ítem original del REQ                          |
| `invitemidnuevo`               | INT FK         | NO   | FK → `invitems`. Ítem nuevo asignado por el comprador           |
| `reqcambioobs`                 | TEXT           | SÍ   | Motivo del cambio                                               |
| `reqcambiofechahora`           | DATETIME       | NO   | Fecha/hora del cambio                                           |
| `reqcambiousuarioid`           | INT FK         | NO   | FK → `usuarios`. Comprador que realizó el cambio               |

> [!IMPORTANT]
> Validación: El ítem nuevo NO puede ser un ítem que ya exista en el requerimiento original.
> Esta tabla alimenta las métricas de errores de solicitantes.

---

## 11. Modificaciones a Tablas Existentes

### 11.1. `invitems` — Nuevas columnas

```sql
ALTER TABLE invitems
    ADD COLUMN invitemtipo TINYINT NOT NULL DEFAULT 0
        COMMENT '0=Sin clasificar, 1=Material (EsCompra+EsStockeable), 2=Servicio (solo EsCompra)',
    ADD COLUMN invitemprecioref DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'Precio neto referencial para REQ',
    ADD COLUMN invitemcomprable TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Aplica para compras',
    ADD COLUMN invitemmodulo VARCHAR(10) NOT NULL DEFAULT ''
        COMMENT 'Código del módulo de uso: LCH/CMB/ALM/BDG',
    ADD COLUMN invitemcompra TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Se usa en operaciones de Compra',
    ADD COLUMN invitemventa TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Se usa en operaciones de Venta',
    ADD COLUMN inviteminventario TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Aplica a Inventario';
```

> [!NOTE]
> El campo `invitemmodulo` reemplaza la lógica anterior de `LECHE = SI/NO`.
> - Producto con `LECHE = SI` → migrar a `invitemmodulo = 'LCH'`
> - Producto con `LECHE = NO` → migrar a `invitemmodulo = 'ALM'`

### 11.2. `usuarios` — Nuevas columnas

```sql
ALTER TABLE usuarios
    ADD COLUMN autorizareq TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Puede ser firmante/aprobador de REQ',
    ADD COLUMN editarprecios TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Puede editar precios en REQ',
    ADD COLUMN comprador TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Puede Crear/Editar/Anular Pre OC (aunque tenga el módulo en su perfil)',
    ADD COLUMN permitecreareditar TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1=Puede Crear/Editar productos en el Maestro de Productos';
```

---

## 12. Diagrama de Relaciones

```
┌──────────────┐        ┌─────────────────────┐       ┌────────────────────────────┐
│   empresas   │◄───────│    centroscosto      │◄──────│       funcionarios         │
│ empresaid    │        │ centrocostoid        │       │ funcionarioid              │
└──────────────┘        │ empresaid FK         │       │ funcionariorut / nombre    │
                        │ centrocostojefe FK ──┼──┐    │ centrocostoid FK           │
                        └────────┬────────────┘  │    │ funcionarioactivo          │
                                 │               │    └────────────────────────────┘
                      ┌──────────┴───┐           │
                      │ usuarioscc   │    ┌──────┴──────────────────────┐
                      │ usuarioid FK │    │          usuarios            │
                      │ centrocosto FK    │ autorizareq   comprador      │
                      └──────────────┘   │ editarprecios permitecreareditar │
                                         └──────┬───────────────┬────────┘
                                                │               │
              ┌─────────────────────────────────┘               │
              │                                                  │
              ▼                                                  ▼
┌─────────────────────────┐                   ┌──────────────────────────────┐
│       reqcompras        │◄── reqcompraslog   │  funcionariosinactividad     │
│ reqcompraid             │                    │ usuarioid FK (aprobador)     │
│ reqcompracod            │  ┌──────────────────────────────────────────────┐ │
│ empresaid FK            │  │            reqcomprasfirmantes               │ │
│ centrocostoid FK        │──►│ firmanteusuarioid FK                        │ │
│ funcionarioid FK ───────┼──►│ firmanteorden (reordenable)                 │ │
│ reqcompraestadoid FK ───┼──┐│ firmanteestado (0-4)                        │ │
│ aprobadorpendiente FK   │  ││ firmanteomitido (inactividad)               │ │
│ reqcompranettotal       │  │└──────────────────────────────────────────────┘ │
└──────────┬──────────────┘  │                                                 │
           │                 ▼                                                 │
           │      ┌────────────────────┐                                       │
           │      │  reqcomprasestados │                                       │
           │      │ BRR/PND/EDT/APR   │         Cuando aprobador tiene         │
           │      │ RCH/CSO/ANL/VNC   │◄────────── inactividad activa ─────────┘
           │      └────────────────────┘         → firmanteomitido = 1
           │                                     → agrega reemplazante
           ▼
┌─────────────────────┐              ┌──────────────────────────────┐
│  reqcomprasdetalle  │── (APR) ────►│         reqaprobados         │
│ invitemid FK        │              │ reqcompradetid FK            │
│ invunidmedid FK     │              │ cantidadoriginal             │
│ cantidad            │              │ cantidadpendiente            │
│ precioneto          │              │ estado (Pend/Parc/Comp)      │
│ totalneto           │              └──────────┬──────────┬────────┘
└─────────────────────┘                         │          │
           │                                    ▼          ▼
           ▼                     ┌─────────────────┐  ┌───────────────────────┐
┌──────────────────┐             │reqaprobadoshist.│  │  reqaprobadoscambios  │
│    invitems      │             │ preocid FK       │  │ reqaprobadoid FK      │
│ invitemtipo      │             │ histcantidad     │  │ invitemidoriginal FK  │
│ invitemprecioref │             │ histprecioneto   │  │ invitemidnuevo FK     │
│ invitemcomprable │             └─────────────────┘  │ comprador FK          │
│ invitemmodulo    │                                   │ (métricas errores)    │
│ invitemcompra    │                                   └───────────────────────┘
│ invitemventa     │
│ inviteminventario│
└──────────────────┘
```

---

## 13. Resumen del Flujo Completo

```
1. CREAR (BRR)
   → Usuario selecciona Solicitante (de Maestro de Funcionarios)
   → El CC se carga del CC del funcionario seleccionado (editable)
   → Selecciona tipo (Material / Servicio). Nunca mixtos.
   → Agrega ítems filtrados por tipo y módulo de uso
   → El SP agrega como firmante 1 al jefe del CC
       · Si el jefe tiene inactividad activa → agrega su reemplazo como activo
         y al jefe con firmanteomitido = 1
   → Creador puede agregar más firmantes (autorizareq = 1)
   → Reordena firmantes si necesario

2. ENVIAR (BRR → PND)
   → Se genera log INS
   → aprobadorpendiente = primer firmante con firmanteomitido = 0
   → Notificación al primer firmante activo

3. APROBAR (PND)
   → Firmante N firma → log APR
   → Si hay N+1 (no omitido) → aprobadorpendiente = firmante[N+1]
   → Si era el último → aprobadorpendiente = NULL, estado → APR
   → Se copian líneas a reqaprobados (cantidadpendiente = total)

4. EDITAR (PND → EDT → PND)
   → Creador presiona "Editar" → estado EDT
   → Aprobadores: si intentan firmar, SP retorna "El REQ está siendo editado"
   → Creador modifica → presiona "Confirmar" → estado PND + log UPD
   → Firmas previas se mantienen

5. RECHAZAR
   → Estado → RCH. Puede corregirse y reenviarse
   → Solo ANL bloquea toda modificación futura

6. CAMBIOS SOLICITADOS (CSO)
   → Firmante pide cambios → estado CSO
   → Creador edita → reenvía → PND

7. CAMBIO DE ÍTEM (por comprador, en Pendientes de Compra)
   → Comprador cambia ítem en reqaprobados
   → SP valida que el ítem nuevo no exista ya en el REQ original
   → Se registra en reqaprobadoscambios → log CMB en reqcompraslog
   → El REQ original muestra el cambio con historial
   → Alimenta métricas de errores de solicitantes

8. VINCULAR A POC (APR → VNC)
   → Desde reqaprobados (pendientes), el comprador crea la Pre OC
   → Compra parcial: cantidadpendiente se reduce
   → Se registra en reqaprobadoshistorial
   → Cuando cantidadpendiente = 0 → estado Completa
   → Cuando todas las líneas = Completa → REQ estado VNC

9. ANULAR (cualquier estado editable)
   → Estado → ANL (baja lógica definitiva)
   → No puede volver a modificarse
```

---

## 13. Resumen del Flujo Completo

```
1. CREAR (BRR)
   → Usuario selecciona CC (de sus CC)
   → Selecciona tipo (Material/Servicio)
   → Agrega ítems (filtrados por tipo)
   → Configura firmantes (jefe CC + otros con autorizareq=1)
   → Reordena firmantes si necesario

2. ENVIAR (BRR → PND)
   → Se genera log INS
   → aprobadorpendiente = firmante[1]
   → Notificación al primer firmante

3. APROBAR (PND)
   → Firmante N firma → log APR
   → Si hay N+1 → aprobadorpendiente = firmante[N+1]
   → Si era último → estado APR
   → Se copian líneas a reqaprobados (cantidadpendiente = total)

4. EDITAR (PND → EDT → PND)
   → Creador presiona "Editar" → estado EDT
   → Aprobadores ven mensaje "En edición"
   → Creador modifica → presiona "Confirmar" → estado PND + log UPD
   → Firmas previas se mantienen

5. RECHAZAR / CAMBIOS SOLICITADOS
   → Firmante rechaza → estado RCH (definitivo) o CSO (corregible)
   → CSO: creador edita → reenvía → PND

6. VINCULAR A POC (APR → VNC)
   → Desde reqaprobados, al crear POC se toman líneas
   → Compra parcial: cantidadpendiente se reduce
   → Se registra en reqaprobadoshistorial
   → Cuando cantidadpendiente = 0 → estado Completa
   → Cuando todas las líneas = Completa → REQ estado VNC
```
