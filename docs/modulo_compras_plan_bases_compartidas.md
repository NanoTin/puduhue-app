# Modulo de Compras - Plan de Bases Compartidas Previas

> Este documento ordena las integraciones, maestros y cambios base que deben estar disponibles antes de cerrar y desarrollar REQ y PreOC.
>
> Fuentes de referencia:
> - `docs/modulo_compras_discovery.md`
> - `docs/modulo_compras_req_estructura.md`
> - `docs/modulo_compras_preoc_estructura.md`
> - `docs/modulo_compras_maestros_erp.md`
> - `docs/modulo_compras_mapeo_finnegans.md`
> - `docs/propuesta_hh_modulo_compras.md`
> - `docs/propuesta_hh_consolidada_compras_combustible.md`
> - `docs/modulo_compras_presupuesto_definitivo.md`
> - `docs/modulo_compras_backlog_bases_compartidas.md`

## 1. Objetivo

Definir primero las bases compartidas del modulo de Compras para que luego puedan reutilizarse tanto en:

- Requerimientos,
- Pendientes de Compra,
- PreOC,
- Presupuesto,
- Integraciones ERP,
- Reporteria y auditoria.

## 2. Principio de trabajo

1. Las bases compartidas se implementan una sola vez.
2. Lo que venga desde ERP se sincroniza como espejo, salvo atributos locales autorizados.
3. Lo que se use en REQ tambien debe servir para PreOC cuando aplique.
4. La PreOC no debe depender de cargas manuales de datos base que ya vienen de maestros.
5. El usuario no debe elegir datos que se puedan resolver automaticamente por contexto.

## 3. Bases compartidas prioritarias

### 3.1 Temporadas de presupuesto

**Uso:** Presupuesto de compras, validacion de REQ y PreOC.

Acciones requeridas:

- Reutilizar `01_table_temporadas.sql`.
- Agregar `temporadatipocodigo = PPTO_COMPRAS`.
- Identificar temporadas vigentes por rango de fechas.
- Permitir que la temporada sea la llave temporal del presupuesto.

Prioridad: alta.

### 3.2 Maestro de Centros de Costo

**Uso:** REQ, PreOC, aprobaciones, presupuesto.

Acciones requeridas:

- Sincronizacion desde ERP.
- Sin CRUD manual sobre los campos espejo.
- Boton de sincronizacion bajo demanda.
- Cron diario de actualización.
- Mantener atributos locales como jefe del CC.

Prioridad: alta.

### 3.3 Maestro de Funcionarios

**Uso:** solicitantes, aprobadores, reemplazos, reporteria.

Acciones requeridas:

- Mantener el maestro como base compartida transversal.
- Permitir carga inicial por Excel.
- Desactivar los funcionarios que ya no existan en el origen.
- Asociar funcionario a centro de costo.

Prioridad: alta.

### 3.4 Maestro de Usuarios y permisos globales

**Uso:** compra, aprobación, sincronización y seguridad de acciones.

Acciones requeridas:

- Consolidar permisos para:
  - comprador,
  - aprobador,
  - edición de precios,
  - creación/edición de productos,
  - sincronización ERP.
- Definir qué permisos se consumen desde login/payload y cuáles se consultan en pantalla.

Prioridad: alta.

### 3.5 Maestro de Ítems / Productos

**Uso:** REQ, pendientes de compra, PreOC, presupuesto y ERP.

Acciones requeridas:

- Sincronizar desde Finnegans como espejo.
- Guardar precio referencial o estándar para validación operativa.
- Diferenciar Material vs Servicio.
- Clasificar por uso de negocio:
  - `LCH`
  - `CMB`
  - `ALM`
  - `BDG`
- Preparar soporte para familia/subfamilia.
- Mantener descripción proveniente de ERP sin edición libre.

Prioridad: alta.

### 3.6 Familias y Subfamilias

**Uso:** presupuesto de compras, validación de REQ, selección de ítems.

Acciones requeridas:

- Definir maestro de familia si aún no existe de forma transversal.
- Definir maestro de subfamilia si aún no existe de forma transversal.
- Vincular `invitems` con familia y subfamilia.
- Usar subfamilia como clave funcional para el presupuesto.

Prioridad: alta.

### 3.7 Proveedores

**Uso:** PreOC y mapeo ERP.

Acciones requeridas:

- Sincronizar desde ERP como maestro espejo.
- No permitir CRUD libre sobre datos base del ERP.
- Mantener la selección por búsqueda/autocomplete por nombre/código.
- Conservar la FK a condición de pago y categoría fiscal.

Prioridad: alta.

### 3.8 Condiciones de Pago

**Uso:** PreOC y mapeo ERP.

Acciones requeridas:

- Sincronizar catálogo desde ERP.
- Pre-cargar desde proveedor.
- Permitir edición antes de grabar la PreOC.

Prioridad: alta.

### 3.9 Categorías fiscales, impuestos y conceptos

**Uso:** PreOC e integración con Finnegans.

Acciones requeridas:

- Mantener categorías fiscales del proveedor.
- Definir conceptos asociados por categoría.
- Construir el array de impuestos/retenciones en la OC.

Prioridad: media-alta.

### 3.10 Monedas

**Uso:** PreOC e integración ERP.

Acciones requeridas:

- Sincronizar catálogo ERP.
- Mantener CLP como moneda operativa.
- Resolver el código ERP `PES`.

Prioridad: media.

### 3.11 Cuentas contables

**Uso:** referencia y trazabilidad de integración.

Acciones requeridas:

- Sincronizar catálogo como espejo de referencia.
- No calcular cuentas manualmente en la app.

Prioridad: media.

### 3.12 Workflow, provincias y dimensiones ERP

**Uso:** PreOC e integración con Finnegans.

Acciones requeridas:

- Definir workflows de compra.
- Definir provincias o destinos requeridos por OC.
- Confirmar dimensiones ERP requeridas para `DIMPARFIN`, `DIMCTC` y otras que aplique el ERP.

Prioridad: media-alta.

## 4. Cambios funcionales que dependen de estas bases

### 4.1 REQ

- Validar saldo disponible por subfamilia.
- Tomar precio desde el maestro de ítems.
- Bloquear el agregado de un ítem sin precio referencial.
- Guardar snapshot del presupuesto usado para validar.
- No generar movimientos presupuestarios.

### 4.2 Pendientes de compra

- Mostrar saldos pendientes por línea aprobada.
- Permitir cambios de ítems con trazabilidad.
- Reutilizar subfamilia, centro de costo y precio del ítem.

### 4.3 PreOC

- Resolver automáticamente el presupuesto aplicable.
- Tomar proveedor y condición de pago desde maestros.
- Construir impuestos/retenciones desde categoría fiscal.
- Validar por temporada, subfamilia, centro de costo y fecha.
- Registrar reserva, confirmación o reversa según estado.

### 4.4 Presupuesto

- Cargar por mes dentro de temporada.
- Usar subfamilia y centro de costo como claves operativas.
- Calcular saldos, consumos y reversas con movimientos.

## 5. Orden sugerido de implementación

### Fase 1 - Bases comunes de alto impacto

1. Temporadas de presupuesto.
2. Centros de costo.
3. Usuarios y permisos globales.
4. Funcionarios.
5. Ítems / productos.
6. Familias y subfamilias.

### Fase 2 - Bases ERP para PreOC

1. Proveedores.
2. Condiciones de pago.
3. Categorías fiscales e impuestos.
4. Monedas.
5. Cuentas contables.
6. Workflows y provincias.
7. Dimensiones ERP.

### Fase 3 - Bases de proceso

1. Validación informativa del REQ.
2. Pendientes de compra.
3. Presupuesto definitivo.
4. PreOC y su compromiso presupuestario.

## 6. Dependencias y alertas

- Si `invitems` no tiene precio, el ítem no puede incorporarse al REQ.
- Si un proveedor no está sincronizado, la PreOC no debe permitir continuar.
- Si la condición de pago no existe, debe pre-cargarse desde el maestro o bloquearse con mensaje controlado.
- Si no se resuelven las dimensiones ERP, la integración de PreOC queda bloqueada.
- Si el presupuesto no existe para la combinación temporada + subfamilia + centro de costo, el REQ y la PreOC deben rechazar la operación según corresponda.

## 7. Resultado esperado

Al cerrar este plan, el proyecto queda listo para avanzar a:

- definición final de REQ,
- definición final de PreOC,
- implementación por fases,
- y pruebas de integración con ERP.
