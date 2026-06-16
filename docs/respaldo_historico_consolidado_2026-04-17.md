# Respaldo Histórico — Consolidación de Notas Compras, Combustible y Componentes Transversales
**Fecha:** 17 de Abril de 2026
**Propósito:** dejar trazabilidad de las definiciones levantadas, comparación contra documentos previos y notas funcionales relevantes para desarrollo.

---

## 1. Fuente de Consolidación

Este respaldo fue construido considerando:

- `docs/modulo_compras_discovery.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/modulo_combustible_discovery.md`
- `docs/propuesta_hh_modulo_compras.md`
- `docs/propuesta_hh_modulo_combustible.md`
- `docs/propuesta_hh_mejoras_dashboard.md`
- `docs/propuesta_hh_componentes_transversales.md`
- notas funcionales adicionales entregadas el 17-04-2026

---

## 2. Criterios Históricos Relevantes

### 2.1. Maestro de Ítems

**Antes documentado:**
- En Compras se hablaba de mejoras a `invitems` con tipo, módulo de uso y compra/venta/inventario.
- En Discovery se indicaba sincronización automática y on-demand desde ERP.
- En Combustible se reutilizaba `invitems` con código `CMB`.

**Ahora consolidado/mejorado:**
- se mantiene `invitems` como maestro central;
- se agregan columnas de origen, precio estándar, familia, subfamilia, formulario, compra y venta;
- se mantiene `stockable` como criterio ya existente para Material/Servicio;
- el sincronizador ERP actualiza solo campos definidos como sincronizables;
- se debe guardar snapshot de atributos relevantes en transacciones.

**Cambio de nomenclatura importante:**
- ~~`ALM`~~ → `SUP` como código de formulario para Suplementación.

---

### 2.2. Usuarios y Aprobadores

**Antes documentado:**
- `autorizareq`
- `editarprecios`
- `comprador`
- `permitecreareditar`

**Ahora consolidado/mejorado:**
- `Comprador`
- `Aprobador`
- `Monto Aprobación`
- `Firma Imagen`
- `Permite Sincronizar ERP`
- `Permite Editar Precios en REQ`
- `Permite Gestión Ítems`

**Nota histórica:**
- La estructura antigua no se elimina conceptualmente, pero se sugiere reorganizarla hacia una definición más explícita.

---

### 2.3. Inactividad y Reemplazos

**Antes documentado:**
- inactividad de funcionarios/aprobadores en Compras;
- uso de reemplazo automático si el aprobador está ausente;
- motivo: Vacaciones, Licencia, Permiso, Otro.

**Ahora consolidado/mejorado:**
- los aprobadores default no se pueden quitar;
- la única forma de resolver ausencia es mediante registro de inactividad con reemplazo válido;
- el reemplazante debe ser aprobador activo y sin inactividad vigente.

---

### 2.4. PreOC

**Antes documentado:**
- consolidación de múltiples REQ;
- agrupación por ítem;
- aprobación por monto;
- log general;
- integración a ERP.

**Ahora consolidado/mejorado:**
- se explicita snapshot de proveedor e ítem;
- se agrega número de OC ERP;
- fecha de sincronización;
- último error fecha y descripción;
- log específico de sincronización;
- resumen de presupuestos usados por la PreOC;
- detalle multinivel:
  - nivel 1: agrupado por ítem;
  - nivel 2: requerimientos por ítem con presupuesto asociado.

---

### 2.5. Combustible

**Antes documentado:**
- maestro de vehículos;
- responsable desde funcionarios;
- producto combustible desde `invitems`;
- unidad de medida KM/HORAS;
- transacción propia;
- estado Pendiente ERP;
- permiso de sincronización transversal.

**Ahora consolidado/mejorado:**
- se incorpora maestro de Tipo de Vehículo;
- se incorpora maestro de Tipo de Control de Uso;
- vehículo guarda kilómetros/horas iniciales y actuales;
- transacción guarda precio actual del ítem y total valorizado;
- solo se puede editar cuando el registro está Pendiente ERP.

---

## 3. Notas Funcionales Consolidadas para Desarrollo

### 3.1. Notas Generales

- Todas las tablas deben considerar columnas de auditoría estándar.
- Algunas columnas del maestro de usuarios podrían viajar en el payload del login para evitar consultas adicionales.
- Si el costo de consulta es irrelevante, esto puede mantenerse como consulta directa. Queda **a definir**.
- Deben snapshotearse columnas relevantes del Maestro de Ítems en las transacciones nuevas.

---

### 3.2. Notas de Sincronización ERP

- En el sincronizador de Ítems/Productos del ERP no se deben tocar columnas locales como formulario/uso si se definen como propias de la app.
- Sí se deben actualizar desde ERP, al menos:
  - estado;
  - descripción;
  - unidad de medida;
  - familia;
  - subfamilia;
  - compra;
  - venta.
- La PreOC debe registrar:
  - número de OC ERP;
  - fecha de sincronización;
  - último error;
  - log del proceso.

---

### 3.3. Notas de Aprobación

- No se puede quitar aprobadores default ni en REQ ni en PreOC.
- Debe existir una marca o atributo para identificar qué aprobadores son obligatorios/no removibles.
- El aprobador por monto también debe quedar como obligatorio cuando aplique por regla.

---

### 3.4. Notas de Reportería y Snapshot

- Guardar columnas relevantes de Ítems en REQ, Combustible y PreOC.
- Guardar snapshot de proveedor e ítem en PreOC para que el documento conserve la foto del momento.
- Si el maestro cambia y la PreOC está en estado editable, el sistema debe recalcular y actualizar lo que corresponda.

---

## 4. Comparación de Alineación con Documentos Previos

| Tema | Ya existía en MD previos | Aporte nuevo / ajuste |
|---|---|---|
| Maestro de Funcionarios | Sí | Se agregan Teléfono y Activo explícito |
| Maestro de CC | Sí | Se formaliza como transversal y con código único |
| Usuarios y permisos | Sí, parcialmente | Se amplían atributos y se simplifica lectura funcional |
| Familias/Subfamilias | Parcialmente | Se formaliza maestro nuevo sincronizado desde ERP |
| Formulario de Ítems | Parcial | Se formaliza catálogo nuevo y cambio ~~`ALM`~~ → `SUP` |
| Precio actual/snapshot en transacciones | Parcial | Se vuelve requerimiento explícito |
| PreOC con trazabilidad ERP detallada | Parcial | Se agregan campos de error/fecha/OC ERP |
| Pendientes de PreOC con anulación parcial/total | Parcial | Se explicita comportamiento esperado |
| Combustible editable solo en Pendiente ERP | Sí | Se deja como regla obligatoria |

---

## 5. Decisiones o Confirmaciones que Conviene Cerrar

1. Qué columnas del usuario vivirán en payload de login y cuáles se consultarán en DB.
2. Si Familia/Subfamilia se sincronizan completas desde ERP o si se requiere mantención local complementaria.
3. Si el precio estándar de ítem será siempre sincronizado desde ERP o podrá ser sobreescrito localmente.
4. Si la integración ERP de combustible será 1 registro local = 1 documento ERP, o agrupada.
5. Cómo se marcarán técnicamente los aprobadores obligatorios/no removibles.

---

## 6. Relación con el Documento Maestro

El documento operativo principal derivado de este respaldo es:

- `docs/propuesta_hh_consolidada_compras_combustible.md`

Este respaldo debe conservarse como historial de criterios, cambios y notas de consolidación a la fecha 17-04-2026.
