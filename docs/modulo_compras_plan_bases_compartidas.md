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
> - `docs/modulo_compras_plan_tecnico_maestros_erp.md`
> - `docs/ADR/modulo-compras/ADR-001-modelo-presupuesto-compras.md`
> - `docs/ADR/modulo-compras/ADR-002-compromiso-edicion-preoc.md`
> - `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`

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
- Mantener atributos locales como jefe del CC y jefe tecnico.
- Usar `centrocostocod` como codigo operativo para `DIMCTC`.
- No separar centros por empresa desde ERP; si el cliente lo pide mas adelante, debe tratarse como dato local/manual.

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
  - aprobador de requerimientos,
  - aprobador de PreOC,
  - edición de precios,
  - creación/edición de productos,
  - sincronización ERP,
  - anulacion de PreOC,
  - autorizador fuera de presupuesto de compra,
  - orden de autorizador fuera de presupuesto de compra.
- Definir qué permisos se consumen desde login/payload y cuáles se consultan en pantalla.

Prioridad: alta.

### 3.5 Maestro de Ítems / Productos

**Uso:** REQ, pendientes de compra, PreOC, presupuesto y ERP.

Acciones requeridas:

- Sincronizar desde Finnegans como espejo.
- Guardar precio referencial o estándar para validación operativa.
- Diferenciar Material vs Servicio.
- No permitir agregar al REQ items sin precio/costo referencial.
- Clasificar por uso de negocio:
  - `LCH`
  - `CMB`
  - `ALM`
  - `BDG`
- Preparar soporte para familia/subfamilia.
- Mantener descripción proveniente de ERP sin edición libre.
- Permitir creacion local controlada para resolver urgencias, marcando el item como ingresado localmente.
- Permitir edicion local solo para precio cuando es cero, uso funcional y activacion/desactivacion.
- Advertir que cambios locales deben regularizarse igualmente en ERP porque la sincronizacion posterior manda sobre los campos espejo.

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

### 3.8 Aprobadores y periodos de inactividad

**Uso:** REQ, PreOC, reemplazos y continuidad de aprobaciones.

Acciones requeridas:

- Usar la tabla `aprobadoresperiodoinactividad` para registrar ausencias, vacaciones u otros periodos.
- Registrar siempre desde la mirada de usuario aprobador, no de funcionario.
- Definir reemplazante activo para los periodos que correspondan.
- No eliminar registros existentes; solo inactivarlos.
- Mantener tabla LOG para trazabilidad de altas, cambios e inactivaciones.
- Reutilizar la misma regla para resolver el siguiente aprobador en REQ y PreOC.

Prioridad: alta.

### 3.9 Condiciones de Pago

**Uso:** PreOC y mapeo ERP.

Acciones requeridas:

- Sincronizar catálogo desde ERP.
- Pre-cargar desde proveedor.
- Permitir edición antes de grabar la PreOC.

Prioridad: alta.

### 3.10 Categorías fiscales, impuestos y conceptos

**Uso:** PreOC e integración con Finnegans.

Acciones requeridas:

- Mantener categorías fiscales del proveedor.
- Definir conceptos asociados por categoría.
- Construir el array de impuestos/retenciones en la OC.

Prioridad: media-alta.

### 3.11 Monedas

**Uso:** PreOC e integración ERP.

Acciones requeridas:

- Sincronizar catálogo ERP.
- Mantener CLP como moneda operativa.
- Resolver el código ERP `PES`.

Prioridad: media.

### 3.12 Cuentas contables

**Uso:** referencia y trazabilidad de integración.

Acciones requeridas:

- Sincronizar catálogo como espejo de referencia.
- No calcular cuentas manualmente en la app.

Prioridad: media.

### 3.13 Workflow, provincias y dimensiones ERP

**Uso:** PreOC e integración con Finnegans.

Acciones requeridas:

- Mantener workflow como valor fijo de integracion, sin maestro propio por ahora.
- Definir provincias o destinos requeridos por OC.
- Resolver `DIMPARFIN` desde el maestro de items.
- Resolver `DIMCTC` desde el centro de costo operativo.
- Confirmar si las dimensiones de PreOC deben quedar a nivel de item agrupado o de req-item origen.

Prioridad: media-alta.

## 4. Cambios funcionales que dependen de estas bases

### 4.1 REQ

- Validar saldo disponible por temporada, subfamilia y centro de costo.
- Tomar precio desde el maestro de ítems.
- Bloquear el agregado de un ítem sin precio referencial.
- Guardar snapshot del presupuesto usado para validar, incluyendo saldo actual, otros REQ en curso, REQ aprobados pendientes de compra, monto de este REQ, saldo proyectado, porcentaje usado y deficit.
- Marcar `reqadvertenciapptocompra` cuando el analisis proyecte deficit.
- Agregar autorizadores fuera de presupuesto de compra cuando exista deficit.
- No generar movimientos presupuestarios.
- No bloquear el envio del REQ por falta de saldo; la validacion es informativa y visible para solicitante/aprobadores.

### 4.2 Pendientes de compra

- Mostrar saldos pendientes por línea aprobada.
- Permitir cambios de ítems con trazabilidad.
- Reutilizar subfamilia, centro de costo y precio del ítem.
- Permitir anulacion parcial o total del saldo pendiente por comprador, con historial.

### 4.3 PreOC

- Resolver automáticamente el presupuesto aplicable.
- Tomar proveedor y condición de pago desde maestros.
- Validar por temporada, subfamilia, centro de costo y `preocfechaoc`.
- Construir detalle origen desde req-items aprobados pendientes.
- Agrupar items para precios y totales.
- Calcular impuestos por item agrupado, considerando que puede existir mas de un impuesto por item.
- Construir dimensiones ERP desde item/centro; queda pendiente confirmar si se guardan por item agrupado o por req-item origen.
- Mantener resumen de presupuesto por PreOC y presupuesto como apoyo de consulta rapida.
- Registrar reserva al pasar de `BRR` a `PND`, confirmación al aprobar y reversa al rechazar/anular.
- Mantener estado ERP separado del estado principal.

### 4.4 Presupuesto

- Cargar por mes dentro de temporada.
- Usar subfamilia y centro de costo como claves operativas.
- Calcular saldos, consumos y reversas con movimientos.
- Incorporar aprobadores default de PreOC en `pptocompra`: responsable, administrador y colaborador opcional.

## 5. Orden sugerido de implementación

### Fase 1 - Bases comunes de alto impacto

1. Temporadas de presupuesto.
2. Centros de costo.
3. Usuarios y permisos globales.
4. Funcionarios.
5. Aprobadores y periodos de inactividad.
6. Ítems / productos.
7. Familias y subfamilias.

### Fase 2 - Bases ERP para PreOC

1. Proveedores.
2. Condiciones de pago.
3. Categorías fiscales e impuestos.
4. Monedas.
5. Cuentas contables.
6. Workflow fijo, provincias/destinos.
7. Dimensiones ERP.

### Fase 3 - Bases de proceso

1. Validación informativa del REQ.
2. Pendientes de compra.
3. Presupuesto definitivo.
4. PreOC, reserva presupuestaria y sincronizacion ERP.

## 6. Dependencias y alertas

- Si `invitems` no tiene precio, el ítem no puede incorporarse al REQ.
- Si un proveedor no está sincronizado, la PreOC no debe permitir continuar.
- Si la condición de pago no existe, debe pre-cargarse desde el maestro o bloquearse con mensaje controlado.
- Si un REQ queda fuera de presupuesto, se advierte y se agregan autorizadores fuera de presupuesto; no se bloquea el REQ.
- Si una PreOC no tiene saldo suficiente, no puede avanzar a aprobacion porque ahi se genera reserva.
- Si no hay firmantes default al enviar un REQ, el sistema debe advertir y permitir conservarlo como borrador.
- Si un aprobador pendiente no esta vigente o esta inactivo, el resolver interno debe saltar o insertar reemplazante segun corresponda y actualizar la cabecera.
- Si no se resuelven las dimensiones ERP, la integración de PreOC queda bloqueada.
- Si el presupuesto no existe para la combinacion temporada + subfamilia + centro de costo, el REQ debe tratarlo como advertencia/fuera de presupuesto y la PreOC debe bloquear el avance hasta resolver el presupuesto.

## 7. Resultado esperado

Al cerrar este plan, el proyecto queda listo para avanzar a:

- definición final de REQ,
- definición final de PreOC,
- implementación por fases,
- y pruebas de integración con ERP.
