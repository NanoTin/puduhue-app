# Modulo de Compras - Definicion Final de Requerimientos v1.0

> Fuente consolidada: `docs/modulo_compras_discovery.md`, `docs/modulo_compras_req_estructura.md`, `docs/modulo_compras_preoc_estructura.md`, `docs/modulo_compras_maestros_erp.md`, `docs/modulo_compras_mapeo_finnegans.md`, `docs/propuesta_hh_modulo_compras.md` y `docs/propuesta_hh_consolidada_compras_combustible.md`.
>
> ADR especificos del modulo: `docs/ADR/modulo-compras/ADR-INDEX.md`.
>
> Plan previo de bases compartidas: `docs/modulo_compras_plan_bases_compartidas.md`.
>
> Este documento fija el alcance funcional que se considera cerrado para iniciar diseno y desarrollo. Los puntos aun dependientes de soporte ERP quedan marcados como pendientes tecnicos, no como dudas funcionales del negocio.

## 1. Objetivo

Implementar en Puduhue App Web un flujo completo de compras que cubra:

1. solicitud interna de materiales o servicios,
2. autorizacion del requerimiento,
3. consolidacion en Pre OC,
4. control presupuestario,
5. aprobacion interna,
6. generacion de la OC real en Finnegans.

## 2. Alcance confirmado

- El modulo se integra al proyecto actual, no a un sistema separado.
- El flujo cubre materiales y servicios.
- No existe modulo de cotizaciones.
- Un requerimiento no mezcla materiales y servicios.
- Una Pre OC puede consolidar multiples requerimientos aprobados.
- Una compra puede ser parcial, dejando saldo para futuras Pre OC.
- La moneda operativa es solo CLP.
- La validacion presupuestaria es bloqueante.
- La fecha de la Pre OC es la fecha de creacion y no la puede editar el comprador.
- El maestro de productos se sincroniza desde Finnegans y no se mantiene como CRUD libre.
- El maestro de centros de costo tambien se sincroniza desde Finnegans y se usa como base de aprobacion.

## 3. Roles y responsabilidades

- Creador de requerimiento: inicia y corrige el REQ segun estado permitido.
- Solicitante asignado: funcionario asociado al requerimiento; es distinto del usuario creador si aplica.
- Aprobador de REQ: usuario habilitado para firmar requerimientos.
- Comprador: arma la Pre OC y administra los requerimientos pendientes de compra.
- Finanzas/Gerencia: administra presupuestos y ajustes.
- Administrador tecnico: sincroniza maestros espejos desde ERP.

## 4. Requerimientos funcionales

### 4.1 Maestro de productos

- Debe sincronizarse automaticamente cada dia desde Finnegans.
- Debe permitir sincronizacion bajo demanda desde la interfaz.
- Solo se conservan productos vigentes en ERP.
- Debe distinguir Material vs Servicio:
  - Material: `Es Compra` + `Es Stockeable`.
  - Servicio: solo `Es Compra`.
- Debe incorporar una clasificacion de uso del producto:
  - `LCH` = Produccion de Leche
  - `CMB` = Combustible
  - `ALM` = Alimentacion
  - `BDG` = Bodega
- La descripcion proviene del ERP y no se edita localmente.
- La creacion o edicion de items solo esta disponible para usuarios con permiso explicito.
- Debe existir un precio referencial/local para pre-carga y valorizacion operativa.
- La clasificacion historica `LECHE = SI/NO` se migra al nuevo codigo de uso.

### 4.2 Maestro de funcionarios

- Debe existir un maestro de funcionarios con:
  - RUT,
  - nombre completo,
  - cargo,
  - activo/inactivo,
  - correo,
  - centro de costo.
- La carga inicial proviene de Excel.
- Si un funcionario ya no aparece en el archivo origen, se desactiva.
- El funcionario asignado como solicitante no depende del usuario creador.

### 4.3 Centros de costo

- Debe existir un maestro de centros de costo sincronizado desde ERP.
- La pantalla no debe exponer CRUD sobre datos venidos desde ERP.
- Debe existir sincronizacion cron diaria y opcion bajo demanda.
- Cada centro de costo puede tener un jefe configurado localmente para la aprobacion por defecto.

### 4.4 Inactividad de aprobadores

- Debe existir una transaccion de inactividad para aprobadores.
- La inactividad debe soportar:
  - vacaciones,
  - licencia,
  - permiso,
  - otro.
- Cada transaccion debe registrar aprobador, reemplazo y rango de fechas.
- Si un aprobador esta inactivo en el rango de la solicitud, se omite como firmante activo y su reemplazo queda como firmante pendiente.
- La trazabilidad debe conservar ambos registros.

### 4.5 Requerimiento de compra

- El requerimiento es la solicitud interna formal.
- Debe existir cabecera, detalle, firmantes, log y estados.
- Debe soportar tipo Material o Servicio, nunca ambos en el mismo documento.
- Debe soportar observacion general en cabecera.
- Debe incluir solicitante asignado y centro de costo editable.
- Debe iniciar en estado `BRR`.
- La aprobacion es general para todo el documento, no por linea.
- El aprobador por defecto es el jefe del centro de costo.
- El creador puede agregar aprobadores manuales adicionales.
- Debe existir soporte para estados:
  - `BRR` Borrador,
  - `PND` Pendiente,
  - `EDT` En edicion,
  - `APR` Aprobado,
  - `RCH` Rechazado,
  - `CSO` Cambios solicitados,
  - `ANL` Anulado,
  - `VNC` Vinculado a Pre OC.
- `RCH` puede corregirse y reenviarse.
- `ANL` bloquea cualquier modificacion posterior.
- La edicion desde `PND` debe pasar por `EDT` para evitar firmas concurrentes.
- Cada evento relevante debe quedar en log de auditoria.

### 4.6 Pendientes de compra

- Debe existir una tabla intermedia con lineas aprobadas listas para comprar.
- Cada linea debe conservar:
  - cantidad original,
  - cantidad pendiente,
  - precio neto,
  - estado de avance.
- Debe permitirse compra parcial.
- El saldo pendiente debe quedar disponible para futuras Pre OC.
- El comprador puede cambiar un item en la pantalla de pendientes de compra.
- El cambio debe quedar trazado con:
  - item original,
  - item nuevo,
  - usuario,
  - fecha/hora,
  - motivo.
- El item nuevo no puede duplicar un item ya existente en el mismo requerimiento original.
- El solicitante debe poder ver el historial de cambios.
- Deben existir metricas de cambio de item y de tiempos de espera del requerimiento.

### 4.7 Pre OC

- La Pre OC la crea el comprador.
- El acceso a crear/editar/anular Pre OC depende de un permiso explicito de usuario.
- La Pre OC puede consolidar multiples requerimientos aprobados.
- La Pre OC debe tener:
  - proveedor,
  - condicion de pago,
  - workflow de compra,
  - moneda,
  - provincia destino,
  - presupuesto asociado,
  - observacion general,
  - detalle por linea,
  - firmantes,
  - log.
- Debe existir vista por defecto de las Pre OC del comprador logueado, con filtro abierto para consultar otras.
- La lista de firmantes se genera al completar los datos de la Pre OC.
- La lista debe incluir, en este orden base:
  - responsable del presupuesto,
  - administrador del presupuesto,
  - colaborador del presupuesto,
  - aprobadores por monto,
  - aprobadores manuales.
- La lista debe permitir reordenamiento.
- Un aprobador no puede repetirse dentro de la misma lista.
- La firma final es secuencial y habilita el paso a Finnegans cuando se aprueba por completo.
- La fecha de la Pre OC es fija, correspondiente a la fecha de creacion.

### 4.8 Presupuesto de compra

- El modelo definitivo de presupuesto vive en [docs/modulo_compras_presupuesto_definitivo.md](./modulo_compras_presupuesto_definitivo.md).
- A nivel funcional, el presupuesto se organiza por temporada, sub familia de item y centro de costo.
- La carga es mensual dentro del rango de la temporada.
- REQ solo valida.
- PreOC reserva, confirma o revierte.
- La administracion del presupuesto sigue siendo exclusiva de Finanzas/Gerencia.

### 4.9 Integracion con Finnegans

- La integracion ocurre solo cuando la Pre OC queda completamente aprobada.
- La OC real se genera en Finnegans a partir de la Pre OC.
- Deben distinguirse:
  - `OC` para materiales,
  - `OCSS` para servicios.
- Debe guardarse la respuesta del ERP para trazabilidad y reintento.
- Si el POST es exitoso, la Pre OC pasa a estado de integracion exitosa.
- Si el POST falla, la Pre OC queda marcada con error de integracion y puede reintentarse manualmente.
- La integracion debe conservar trazabilidad tecnica del envio.

### 4.10 Reportes requeridos

- Requerimientos por estado, fecha, solicitante y tipo.
- Pre OC por estado, fecha, comprador y proveedor.
- Ejecucion presupuestaria.
- Historial de aprobaciones y rechazos.
- Metricas de items cambiados.
- Metricas de tiempos de espera.
- Exportacion a Excel de todos los listados relevantes.

## 5. Reglas de negocio cerradas

1. No se mezcla material con servicio en el mismo requerimiento.
2. No hay cotizaciones.
3. La compra parcial esta permitida.
4. La fecha de Pre OC no es editable.
5. La moneda es solo CLP.
6. El presupuesto bloquea el avance si no hay saldo.
7. El jefe del centro de costo es el aprobador base del REQ.
8. El comprador puede reordenar firmantes de Pre OC.
9. Los maestros provenientes de ERP no se editan manualmente en su dato base.
10. El historial y la auditoria son parte del alcance y no un agregado posterior.

## 6. Pendientes tecnicos para cierre con ERP

Estos puntos no cambian el alcance funcional, pero deben cerrarse antes de construir la integracion final:

- origen exacto del codigo `DIMPARFIN`,
- confirmacion de si `DIMCTC` usa el mismo catalogo local de centros de costo o un catalogo separado en ERP,
- confirmacion de los campos obligatorios definitivos del POST,
- catalogo exacto de conceptos/impuestos que Finnegans espera enviar,
- comportamiento de `NumeroComprobante` en la creacion de OC,
- disponibilidad de endpoints de sincronizacion para proveedores, condiciones de pago, monedas, impuestos y workflows.

## 7. Fuera de alcance

- modulo de cotizaciones,
- multi-moneda,
- sistema separado fuera de Puduhue App Web,
- aprobacion parcial por linea del requerimiento,
- edicion manual de descripcion de producto sincronizado desde ERP.

## 8. Resultado esperado

Al finalizar este modulo, el negocio debe poder:

1. levantar requerimientos internos con control,
2. aprobarlos con trazabilidad,
3. convertirlos en Pre OC con control de presupuesto,
4. enviar la OC final a Finnegans,
5. consultar el historial completo del proceso.
