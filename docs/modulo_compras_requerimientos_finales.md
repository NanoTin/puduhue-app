# Modulo de Compras - Definicion Final de Requerimientos v1.0

> Fuente consolidada: `docs/modulo_compras_discovery.md`, `docs/modulo_compras_req_estructura.md`, `docs/modulo_compras_preoc_estructura.md`, `docs/modulo_compras_maestros_erp.md`, `docs/modulo_compras_mapeo_finnegans.md`, `docs/propuesta_hh_modulo_compras.md` y `docs/propuesta_hh_consolidada_compras_combustible.md`.
>
> ADR especificos del modulo: `docs/ADR/modulo-compras/ADR-INDEX.md`.
>
> Plan previo de bases compartidas: `docs/modulo_compras_plan_bases_compartidas.md`.
>
> Este documento fija el alcance funcional general del modulo. Para Requerimientos de Compra y Pendientes de Compra, la fuente vigente de detalle es `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md` y `docs/modulo_compras_req_estructura.md`.

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
- La validacion presupuestaria del REQ es informativa y no bloqueante; la PreOC si valida y compromete presupuesto.
- La fecha de la Pre OC es la fecha de creacion y no la puede editar el comprador.
- El maestro de productos se sincroniza desde Finnegans. Se permite creacion/edicion local acotada para resolver urgencias, con advertencia de que ERP sigue siendo la fuente que manda al sincronizar.
- El maestro de centros de costo tambien se sincroniza desde Finnegans y se usa como base de aprobacion.

## 3. Roles y responsabilidades

- Creador de requerimiento: inicia y corrige el REQ segun estado permitido.
- Solicitante asignado: funcionario opcional asociado al requerimiento; es distinto del usuario creador si aplica.
- Aprobador de REQ: usuario habilitado para firmar requerimientos.
- Autorizador fuera de presupuesto REQ: usuario habilitado para aprobar requerimientos con advertencia de falta de saldo presupuestario.
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
- La creacion o edicion acotada de items solo esta disponible para usuarios con permiso explicito.
- Los items creados localmente deben marcarse como `iteminglocal` o equivalente; si ERP luego los sincroniza, ERP manda y actualiza los campos que correspondan.
- La edicion local rapida se limita a precio cuando es cero, uso funcional y activo/inactivo.
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
- Cada centro de costo puede tener jefe de centro y jefe tecnico configurados localmente como aprobadores default del REQ.
- `DIMCTC` se resuelve internamente desde el codigo del centro de costo; el comprador no lo edita.
- No se contempla separar centros de costo por empresa desde el GET del ERP.

### 4.4 Inactividad de aprobadores

- Debe existir una transaccion de inactividad para aprobadores basada en usuarios, no en funcionarios.
- La inactividad debe soportar:
  - vacaciones,
  - licencia,
  - permiso,
  - otro.
- Cada transaccion debe registrar aprobador usuario, reemplazo usuario y rango de fechas.
- Si un aprobador esta inactivo en el rango de la solicitud, se omite como firmante activo y su reemplazo queda como firmante pendiente.
- La trazabilidad debe conservar ambos registros.

### 4.5 Requerimiento de compra

- El requerimiento es la solicitud interna formal.
- Debe existir cabecera, detalle, firmantes, log y estados.
- Debe soportar tipo Material o Servicio, nunca ambos en el mismo documento.
- Debe soportar observacion general en cabecera.
- Debe permitir solicitante asignado opcional y centro de costo editable dentro de los centros asignados al usuario.
- Debe iniciar en estado `BRR`.
- La aprobacion es general para todo el documento, no por linea.
- Los aprobadores default son el jefe del centro de costo y el jefe tecnico del centro de costo, cuando existan.
- El creador puede agregar aprobadores manuales adicionales.
- Si el REQ no cuenta con saldo suficiente en uno o mas presupuestos, se agregan autorizadores fuera de presupuesto al final de la lista, no removibles y segun orden configurado en usuarios.
- Debe existir soporte para estados:
  - `BRR` Borrador,
  - `PND` Pendiente,
  - `EDT` En edicion,
  - `APR` Aprobado,
  - `RCH` Rechazado,
  - `ANL` Anulado.
- La vinculacion con PreOC se maneja en una columna separada de estado PreOC:
  - sin estado,
  - `LNK_Parcial`,
  - `LNK_Total`.
- `RCH` puede corregirse y reenviarse.
- Al reenviar desde `RCH`, se recalculan firmantes default, se conservan manuales activos y se reaplica inactividad.
- `ANL` bloquea cualquier modificacion posterior.
- La edicion desde `PND` debe pasar por `EDT` para evitar firmas concurrentes.
- No se puede editar un REQ cuando ya existe al menos una aprobacion o cuando ya esta aprobado.
- La fecha funcional del REQ la define sistema/BD y se actualiza en cada edicion permitida.
- El rechazo requiere comentario obligatorio de mas de 10 caracteres.
- El sistema debe resolver dinamicamente el siguiente aprobador, validando usuario vigente e inactividad antes de actualizar el aprobador pendiente de cabecera.
- Cada evento relevante debe quedar en log de auditoria.
- Debe existir tabla de comentarios funcionales separada del log tecnico.

### 4.6 Pendientes de compra

- Debe existir una tabla intermedia con lineas aprobadas listas para comprar.
- Cada linea debe conservar:
  - cantidad original,
  - cantidad pendiente,
  - cantidad comprada,
  - cantidad anulada,
  - precio neto,
  - estado de avance.
- La relacion con PreOC vive en historial, no como `preocid` unico en la fila de `reqaprobados`.
- La suma de cantidad pendiente, comprada y anulada debe coincidir con la cantidad requerida.
- Debe permitirse compra parcial.
- El saldo pendiente debe quedar disponible para futuras Pre OC.
- El comprador puede anular cantidad pendiente parcial o total con motivo obligatorio.
- Solo se puede anular saldo pendiente; si ya se compro todo, no se puede anular.
- El comprador puede cambiar un item en la pantalla de pendientes de compra.
- El cambio debe quedar trazado con:
  - item original,
  - item nuevo,
  - usuario,
  - fecha/hora,
  - motivo.
- El item nuevo no puede duplicar un item ya existente en el mismo requerimiento original.
- El item nuevo no puede cambiar Material por Servicio ni Servicio por Material.
- El cambio de item no se permite si la linea tiene transacciones posteriores.
- El solicitante debe poder ver el historial de cambios.
- Deben existir metricas de cambio de item y de tiempos de espera del requerimiento.
- La visualizacion del REQ debe incluir un boton o accion de analisis de presupuesto de compra disponible para todo usuario que pueda ver el documento.

### 4.7 Pre OC

- La Pre OC la crea el comprador.
- El acceso a crear/editar/anular Pre OC depende de un permiso explicito de usuario.
- La Pre OC puede consolidar multiples requerimientos aprobados.
- La Pre OC debe tener:
  - proveedor,
  - condicion de pago,
- workflow de compra fijo,
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
- La PreOC debe tener estado ERP separado del estado documental:
  - sin estado,
  - `SNC` sincronizada,
  - `ERR` error de sincronizacion.
- Una PreOC sincronizada puede anularse localmente con permiso especial y comentario; el estado ERP se mantiene para auditoria.

### 4.8 Presupuesto de compra

- El modelo definitivo de presupuesto vive en [docs/modulo_compras_presupuesto_definitivo.md](./modulo_compras_presupuesto_definitivo.md).
- A nivel funcional, el presupuesto se organiza por temporada, sub familia de item y centro de costo.
- La carga es mensual dentro del rango de la temporada.
- REQ solo valida.
- En REQ la validacion es informativa, no bloqueante y no genera movimientos.
- El calculo de REQ debe agrupar por subfamilia y centro de costo, guardar advertencia y conservar snapshot actualizable.
- El analisis debe mostrar disponible actual, otros REQ en curso, REQ aprobados pendientes de compra, monto del REQ, saldo proyectado y deficit cuando exista.
- Si existe deficit, se agregan autorizadores fuera de presupuesto configurados en usuarios.
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
6. En REQ, el presupuesto advierte sin bloquear ni mover presupuesto; en PreOC, valida y compromete.
7. El jefe del centro de costo y el jefe tecnico son aprobadores default del REQ, cuando existan.
8. El comprador puede reordenar firmantes de Pre OC.
9. Los maestros provenientes de ERP no se editan libremente en su dato base; la edicion local de items queda acotada a urgencias definidas.
10. El historial y la auditoria son parte del alcance y no un agregado posterior.
11. El rechazo de REQ y PreOC requiere comentario obligatorio de mas de 10 caracteres.
12. La prioridad Normal/Alta es visual y se debe destacar tambien en correos.

## 6. Pendientes tecnicos para cierre con ERP

Estos puntos no cambian el alcance funcional, pero deben cerrarse antes de construir la integracion final:

- diseno fisico final de dimensiones por item PreOC para `DIMPARFIN` y `DIMCTC`,
- confirmacion de los campos obligatorios definitivos del POST,
- catalogo exacto de conceptos/impuestos que Finnegans espera enviar,
- comportamiento de `NumeroComprobante` en la creacion de OC,
- disponibilidad de endpoints de sincronizacion para proveedores, condiciones de pago, monedas e impuestos.

## 7. Fuera de alcance

- modulo de cotizaciones,
- multi-moneda,
- sistema separado fuera de Puduhue App Web,
- aprobacion parcial por linea del requerimiento,
- edicion manual de descripcion de producto sincronizado desde ERP.
- recepcion ERP dentro del tracking del REQ.
- monto maximo por requerimiento.

## 8. Resultado esperado

Al finalizar este modulo, el negocio debe poder:

1. levantar requerimientos internos con control,
2. aprobarlos con trazabilidad,
3. convertirlos en Pre OC con control de presupuesto,
4. enviar la OC final a Finnegans,
5. consultar el historial completo del proceso.
