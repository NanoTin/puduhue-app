# Módulo de Compras — Levantamiento y Definiciones

> [!NOTE]
> Documento de trabajo para el levantamiento del módulo de compras.
> Los ítems marcados con ❓ requieren confirmación con el cliente.
> Los ítems marcados con 💡 son propuestas/sugerencias del equipo de desarrollo.
> Los ítems marcados con ✅ son definiciones confirmadas.

---

## 1. Visión General

### 1.1. Objetivo del Módulo
Gestionar el ciclo completo de compras de la empresa, desde la solicitud de materiales o servicios hasta la generación de una Orden de Compra real en el ERP Finnegans.

### 1.2. Flujo General Propuesto

```
Requerimiento (Material o Servicio)
        ↓
   Autorización del Requerimiento
        ↓ (aprobado)
Pre Orden de Compra (Pre OC)
   + Presupuesto de Compra
        ↓
   Autorización de la Pre OC
        ↓ (100% aprobada)
Integración → Finnegans (OC real)
```

### 1.3. Decisión de Integración

> [!NOTE]
> ✅ **Este módulo se integra al proyecto actual (Puduhue App Web).**
> Reutiliza toda la arquitectura, BD, auth, menú y patrones existentes.

---

## 2. Módulo de Requerimientos

### 2.1. Definición
Un **Requerimiento** es una solicitud formal de compra de materiales o servicios que un usuario interno genera para abastecer una necesidad operativa.

### 2.2. Tipos de Requerimiento
- **Material**: productos físicos (insumos, repuestos, herramientas, etc.) — `invitemtipo = 1`. En ERP: tiene [Es Compra] y [Es Stockeable].
- **Servicio**: contratación de servicios (mantenimiento, transporte, asesoría, etc.) — `invitemtipo = 2`. En ERP: tiene solo [Es Compra].

✅ **Un requerimiento NO puede mezclar materiales y servicios. Deben ser separados.**

### 2.3. Maestro de Productos (espejo de Finnegans)

✅ Definiciones confirmadas sobre el Maestro de Productos:

| Definición | Detalle |
|---|---|
| Sincronización automática | Tarea diaria (cron) via API/EndPoint de Finnegans |
| Sincronización bajo demanda | Botón On-Demand en la pantalla del Maestro de Productos |
| Vigencia | Solo los productos vigentes en Finnegans |
| Tipo producto | `invitemtipo`: 1=Material (EsCompra+EsStockeable), 2=Servicio (solo EsCompra) |
| Módulo de uso | Nuevo atributo de clasificación: LCH / CMB / ALM / BDG |
| Descripción | Se usa la descripción del ERP, sin poder editar en Puduhue App |
| Crear/Modificar ítems | Solo usuarios con `permitecreareditar = 1` en el maestro de Usuarios |
| Atributos adicionales | Compra, Venta, Inventario (para segregar aún más por finalidad) |

**Códigos de Módulo de Uso (nueva clasificación):**

| Código | Módulo |
|--------|--------|
| `LCH`  | Módulo de Producción de Leche |
| `CMB`  | Módulo de Combustible |
| `ALM`  | Módulo de Alimentación |
| `BDG`  | Módulo de Bodega (Consumos y Traslados) |

> [!NOTE]
> El atributo actual `LECHE = SI/NO` de `invitems` se reemplazará por este código de módulo. Los productos `LECHE = SI` migran a `LCH`; los `LECHE = NO` migran a `ALM`.

### 2.4. Cabecera del Requerimiento — Campos Confirmados

| Campo | Decisión |
|---|---|
| Tipo (Material/Servicio) | ✅ Separados, nunca mixtos |
| Observación en cabecera | ✅ Se añade campo genérico de observación |
| Solicitante asignado | ✅ Campo separado al usuario creador. Se selecciona de Maestro de Funcionarios |
| Centro de costo | ✅ Se carga por defecto del CC del solicitante, editable |
| Estado Borrador | ✅ Confirmado, el REQ inicia en BRR |

### 2.5. Flujo de Autorización del Requerimiento

✅ **Modelo de autorización confirmado:**

- El aprobador por defecto es el **jefe del Centro de Costo** configurado en el Maestro de Centros.
- El **usuario creador** puede agregar más aprobadores manualmente.
- La aprobación es **general** (todo el REQ: no hay aprobación parcial por líneas).

**Regla de reemplazo por inactividad:**
- Si un aprobador tiene una **transacción de inactividad** activa (vacaciones, licencia, permiso, otro), al generar la lista de firmantes:
  - El aprobador inactivo queda con estado `"Omitido por inactividad"`.
  - Su reemplazo queda como el firmante pendiente de autorización.
  - Ambos aparecen en la lista (trazabilidad).

### 2.6. Estados del Requerimiento

| Estado | Código | Editable | Descripción |
|---|---|---|---|
| Borrador | `BRR` | ✅ | Creado, no enviado |
| Pendiente | `PND` | ❌ | Enviado a firmantes |
| En Edición | `EDT` | ✅ | Creador editando tras envío |
| Aprobado | `APR` | ❌ | Todos aprobaron |
| Rechazado | `RCH` | ✅ | Puede corregirse y reenviarse |
| Cambios Solicitados | `CSO` | ✅ | Firmante pide modificaciones |
| Anulado | `ANL` | ❌ | No puede volver a modificarse |
| Vinculado a POC | `VNC` | ❌ | Ya generó Pre OC |
| Omitido por inactividad | *(atributo del firmante)* | — | Solo aplica a la fila del firmante |

> [!IMPORTANT]
> ✅ Un REQ en estado `RCH` **puede** ser corregido y reenviado.
> ✅ Un REQ en estado `ANL` **NO puede** volver a modificarse.

### 2.7. Maestro de Funcionarios (nuevo)

✅ Se debe crear un **Maestro de Funcionarios** con los siguientes campos (mínimos para comenzar):

| Campo | Descripción |
|---|---|
| `rut` | RUT del funcionario |
| `nombre` | Nombre completo |
| `cargo` | Cargo |
| `activo` | 1=Activo, 0=Inactivo |
| `email` | Correo electrónico |
| `centrocostoid` | FK → `centroscosto` |

- Carga inicial: desde Excel con funcionarios activos.
- Si un funcionario no aparece en el Excel, se desactiva automáticamente.

### 2.8. Transacción de Inactividad de Funcionarios (nueva)

✅ Se debe crear una **transacción de inactividad** para aprobadores:

| Campo | Descripción |
|---|---|
| `funcionarioid` | FK → Maestro de Funcionarios (aprobador que se ausenta) |
| `funcionarioreemplazoid` | FK → Maestro de Funcionarios (reemplazante) |
| `motivoinactividad` | Vacaciones / Licencia / Permiso / Otro |
| `fechadesde` | Fecha de inicio de la inactividad |
| `fechahasta` | Fecha de fin (incluida) |

> [!NOTE]
> Solo aplica a aprobadores (usuarios con `autorizareq = 1`).

---

## 3. Pre Orden de Compra (Pre OC)

### 3.1. Definición
Una **Pre OC** es el documento que consolida uno o más requerimientos aprobados y los prepara para convertirse en una Orden de Compra real en Finnegans.

### 3.2. ¿Quién crea la Pre OC?
✅ **Las secretarias (compradores)**. Se debe agregar atributo `comprador` en el Maestro de Usuarios.

| Atributo | Descripción |
|---|---|
| `comprador` | 1=Puede Crear/Editar/Anular en módulo de Pre OC. 0=No puede (aunque tenga el módulo en su perfil) |

> [!NOTE]
> Por defecto, la pantalla de consulta muestra solo las Pre OC del comprador logueado. Puede limpiar el filtro para ver las de otros compradores.

### 3.3. Consolidación de Requerimientos

✅ **Una Pre OC puede consolidar múltiples requerimientos aprobados.**

- Se usa la tabla intermedia `reqaprobados` que contiene las líneas aprobadas con saldo pendiente.
- Un requerimiento puede comprarse **parcialmente** (queda saldo para la siguiente Pre OC).
- Cada línea de la Pre OC en Finnegans lleva en el campo `Descripcion` el nombre del **Centro de Costo** (para identificar a qué CC corresponde cada ítem en la pantalla del ERP).

### 3.4. Validación de Cambio de Ítem

✅ El comprador **puede cambiar un ítem** del requerimiento pendiente de compra (en pantalla de requerimientos pendientes, no dentro de la Pre OC). Reglas:

- El cambio se realiza en la pantalla de "Requerimientos pendientes de compra".
- Debe quedar **trazabilidad** del cambio.
- Se generan **métricas** de cuántas veces se cambian ítems (para medir errores de los solicitantes).
- No puede cambiarse por un ítem que **ya exista en el mismo requerimiento original**.
- En el requerimiento original también se muestra el cambio, con opción de ver el **historial de cambios**.
- Se deben medir métricas de **tiempo de espera**:
  - Tiempo desde creación hasta autorización del REQ.
  - Tiempo desde autorización hasta generación de la Pre OC.

### 3.5. Flujo de seguimiento del solicitante

✅ El usuario creador del REQ debe tener **historial completo** (tipo tracking de transporte):

| Hito | Estado |
|---|---|
| REQ creado | Fecha/hora |
| REQ autorizado | Fecha/hora |
| Ítem cambiado | Fecha/hora + motivo |
| Pre OC generada | Fecha/hora |
| Recepción en ERP | 💡 Punto de mejora futura (leer desde ERP) — diseño debe soportarlo |

### 3.6. Fecha de la Pre OC

✅ Se toma la **fecha de creación** (solo fecha). No editable por el comprador.

### 3.7. Flujo de Autorización de la Pre OC

✅ Lista de firmantes generada automáticamente al completar datos de la Pre OC, con este orden sugerido:

```
1. Responsable del Presupuesto
2. Administrador del Presupuesto
3. Colaborador del Presupuesto
4. Aprobador(es) por Monto (regla: monto neto > X)
5. Aprobadores Manuales (agregados por el comprador)
```

**Reglas:**
- El comprador puede **editar el orden** antes de grabar (drag & drop o flechas por fila).
- Un aprobador puede existir **solo una vez** en la lista (deduplicación automática).
- La lista se muestra como **último paso** antes de grabar la Pre OC.
- La aprobación **bloquea** si no hay saldo presupuestario.

**Tabla de reglas de aprobación por monto:**

| Campo | Descripción |
|---|---|
| `usuarioid` | FK → `usuarios` (aprobador) |
| `montominimo` | Monto neto a partir del cual se agrega (ej. "mayor que 1.000.000") |
| `orden` | Orden sugerido en la lista de firmantes |

### 3.8. Sin módulo de Cotizaciones

✅ **No se incluye módulo de cotizaciones**. El flujo es directo: Requerimiento Aprobado → Pre OC.

---

## 4. Presupuesto de Compra

### 4.1. Cómo se define

✅ Puede ser de cualquier temporalidad. El usuario define la nomenclatura:

| Prefijo | Periodicidad | Ejemplo |
|---|---|---|
| `A` | Anual | `A-2026` |
| `T` | Trimestral | `T1-26` |
| `S` | Semestral | `S2-26` |
| `M` | Mensual | `M04-26` |

- Se agregan **fecha de inicio y fecha de fin** como dato adicional para reportes.
- El creador de la Pre OC elige el presupuesto a aplicar (de los que están activos y con saldo).

### 4.2. Estructura jerárquica

✅ 3 niveles:

```
Clasificación (ej. Capex / Opex)
    └── Sub-clasificación (ej. Salud Animal)
            └── Presupuesto (ej. Insumos Veterinarios — T1-26)
```

El presupuesto (nivel 3) contiene:
- **Saldo inicial**: monto cargado al inicio del período.
- **Ajustes manuales**: movimientos positivos o negativos realizados por administración.
- **Re-proyectado**: saldo inicial + ajustes manuales (calculado).
- **Consumos por Pre OC**: reservas y confirmaciones según estado de la Pre OC.
- **Saldo disponible**: re-proyectado − consumos por Pre OC en estado activo.

**Kardex del presupuesto** (detalle de movimientos, tipo cuenta corriente):

| Tipo movimiento | Signo | Estado |
|---|---|---|
| Saldo inicial | + | — |
| Ajuste manual | +/− | Requiere opción específica |
| Consumo por Pre OC en aprobación | − | "En proceso" |
| Pre OC aprobada | Sin cambio (confirma reserva) | "Aprobada" |
| Pre OC rechazada/anulada | + (devolución) | "Devuelto" |

> [!IMPORTANT]
> ✅ No puede avanzar si no hay saldo. El sistema bloquea la aprobación.

### 4.3. Control de acceso al Presupuesto

✅ Solo la **Gerencia de Administración y Finanzas** puede crear/editar presupuestos.
✅ Los **compradores** tienen acceso solo a un **reporte de consulta** de presupuestos (sin CRUD).

---

## 5. Maestros Nuevos Confirmados

| Maestro | Estado | Notas |
|---|---|---|
| Maestro de Productos (espejo ERP) | ✅ Ya existe (`invitems`). Requiere mejoras | Tipo, módulo de uso, atributos adicionales |
| Maestro de Centros de Costo | ✅ Nuevo. Sin CRUD desde ERP (solo sync) | Botón exportar + sync bajo demanda |
| Maestro de Funcionarios | ✅ Nuevo | Carga inicial Excel. Rut, Nombre, Cargo, Email, CC |
| Maestro de Clasificación de Presupuesto | ✅ Nuevo | 3 niveles: Clasificación / Sub-clasificación / Presupuesto |
| Proveedores (espejo ERP) | 💡 Pendiente confirmar | ¿Se sincroniza o es FK directa al ERP? |
| Condiciones de Pago (espejo ERP) | 💡 Pendiente | ¿Sync o texto libre? |

---

## 6. Integración con Finnegans (OC)

### 6.1. Tipos de OC en ERP

✅ Confirmado:
- `TransaccionSubtipoCodigo: "OC"` → Materiales (incluye `EsStockeable`)
- `TransaccionSubtipoCodigo: "OCSS"` → Servicios (sin inventario)

### 6.2. Campo `Descripcion` en ítems

✅ El campo `Descripcion` de cada ítem de la OC en Finnegans se usará para **identificar el Centro de Costo** al que pertenece esa línea. Esto es necesario porque en la pantalla del ERP solo se muestra esa columna y sirve para diferenciar entre líneas de distintos centros.

### 6.3. Formato JSON del POST a Finnegans

Ver ejemplos en:
- `docs/inputs/mejoras_mar_abr_26/erp_oc_material_ejemplo.json` — OC Material real
- `docs/inputs/mejoras_mar_abr_26/erp_oc_servicio_ejemplo.json` — OC Servicio real
- `docs/inputs/mejoras_mar_abr_26/erp_oc_post_ejemplo.json` — Estructura mínima POST
- `docs/inputs/mejoras_mar_abr_26/erp_oc_material_ejemplo_todas_las_columnas.json` — OC Material con todas las columnas
- `docs/inputs/mejoras_mar_abr_26/erp_oc_servicio_ejemplo_todas_las_columnas.json` — OC Servicio con todas las columnas
- `docs/inputs/mejoras_mar_abr_26/erp_oc_post_ejemplo_todas_las_columnas.json` — POST con todas las columnas posibles

---

## 7. Monedas, Bodegas y Logística

| Tema | Decisión |
|---|---|
| Moneda | ✅ Solo CLP (`MonedaCodigo: "PES"`) |
| Bodega de recepción | ✅ Es el destino final (no hay traslados internos en el flujo) |
| Flujo logístico | ✅ Proveedor → Recepción (= Destino final) |

---

## 8. Roles Confirmados

| Atributo en Usuarios | Descripción |
|---|---|
| `autorizareq` | Puede ser firmante/aprobador de REQ |
| `editarprecios` | Puede editar precios en REQ |
| `comprador` | Puede Crear/Editar/Anular Pre OC |
| `permitecreareditar` | Puede Crear/Editar productos en el Maestro |
| *(por perfil/módulo)* | Acceso al módulo general |

---

## 9. Reportes Requeridos

✅ Confirmados todos:

- [ ] Listado de requerimientos por estado, fecha, solicitante.
- [ ] Listado de Pre OC por estado, fecha, comprador, proveedor.
- [ ] Ejecución presupuestaria (presupuesto vs ejecutado vs saldo) — solo consulta para compradores.
- [ ] Historial de aprobaciones/rechazos.
- [ ] Métricas de ítems cambiados por el comprador (frecuencia de errores del solicitante).
- [ ] Métricas de tiempo de espera (REQ creado → autorizado, autorizado → Pre OC generada).
- [ ] Exportación a Excel de todos los listados.

---

## 10. Preguntas Pendientes

❓ Aún sin confirmar:

1. ¿El Maestro de Proveedores se sincroniza desde Finnegans (espejo) o se mantiene solo en App?
2. ¿Las Condiciones de Pago son texto libre en la Pre OC o se sincronizan desde el ERP?
3. ¿Existe un monto máximo por requerimiento?
4. ¿Se puede anular una OC ya enviada a Finnegans desde esta app?
5. ¿La recepción desde ERP (fecha de entrega) se incorporará en el historial del REQ en etapa futura? (Se diseña para soportarlo)
