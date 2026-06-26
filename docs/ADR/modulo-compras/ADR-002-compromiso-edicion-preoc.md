# ADR-002 - Compromiso y edicion de PreOC

## Contexto

La PreOC es el punto donde el presupuesto de compras se compromete de manera real. El negocio definio que la PreOC:

- se crea como borrador y pasa a `PND` cuando se envia a aprobacion,
- puede editarse solo mientras no tenga aprobaciones,
- genera reserva provisional al pasar de `BRR` a `PND`,
- confirma la reserva al aprobarse,
- revierte lo confirmado si se rechaza o anula.

Tambien se definio que una linea provisional eliminada antes de la primera aprobacion no debe generar reversa, sino eliminarse junto con su transaccion provisional asociada.

En la definicion posterior se separo el estado documental de la PreOC del estado de sincronizacion ERP, y se definio que los firmantes default de PreOC se obtienen desde los presupuestos de compra que componen sus lineas.

## Decision

- La PreOC es la unica transaccion del flujo de compras que compromete presupuesto.
- El REQ no genera movimientos presupuestarios.
- La PreOC usara estados documentales:
  - `BRR`: Borrador. No descuenta presupuesto.
  - `PND`: Pendiente o en curso. Valida saldo y genera reserva.
  - `APR`: Aprobada. Confirma presupuesto en reserva.
  - `RCH`: Rechazada. Revierte presupuestos asociados.
  - `ANL`: Anulada. Revierte presupuestos asociados cuando corresponde.
- El estado ERP se maneja en columna separada:
  - sin estado: no aplica cuando no esta aprobada,
  - `SNC`: sincronizada,
  - `ERR`: error de sincronizacion.
- Si falla la sincronizacion ERP, la PreOC documental queda `APR` y el estado ERP queda `ERR`.
- La PreOC debe guardar el error ERP en columna visible de consulta, sin obligar a buscarlo en el LOG. Esa columna se limpia cuando sincroniza correctamente.
- Si sincroniza correctamente, debe guardar numero ERP y fecha/hora de sincronizacion.
- Al enviar una PreOC de `BRR` a `PND`:
  - se valida saldo disponible,
  - se crea una reserva provisional negativa.
- La reserva presupuestaria se genera al pasar `BRR -> PND`, no al solo guardar borrador.
- Mientras la PreOC siga en curso y sin aprobaciones:
  - puede editarse,
  - puede eliminarse una linea provisional,
  - si una linea provisional se quita, se borra su transaccion provisional asociada.
- Una PreOC puede volver de `PND -> BRR` solo si ningun firmante ha aprobado; en ese caso se libera/revierte la reserva.
- Una PreOC rechazada (`RCH`) puede volver a `BRR` para corregirse; el rechazo ya realizo la reversa, por lo que el paso posterior a borrador es solo cambio documental para rearmar.
- Cuando existe al menos una aprobacion:
  - la PreOC deja de ser editable.
- Una PreOC no puede anularse directamente cuando tiene al menos un firmante aprobado; debe solicitarse rechazo para liberar el flujo.
- Si una PreOC ya confirmada se rechaza o anula:
  - se genera reversa positiva,
  - no se borra la historia del movimiento.
- Una PreOC sincronizada con ERP puede anularse localmente con permiso especial y comentario obligatorio. En ese caso el estado documental pasa a `ANL`, pero el estado ERP se mantiene `SNC` para auditoria rapida de sincronizadas y anuladas.
- La validacion de presupuesto para la PreOC se hace automaticamente por:
  - sub familia del item,
  - centro de costo,
  - fecha de la PreOC,
  - temporada correspondiente.
- La resolucion de presupuesto por linea usa item/subfamilia + centro de costo + fecha PreOC para encontrar la temporada y el presupuesto de compra.
- `pptocompra` debe contemplar firmantes default:
  - `pptocompraresponsableid`: obligatorio, usuario activo con permiso de aprobacion PreOC,
  - `pptocompraadministradorid`: obligatorio, usuario activo con permiso de aprobacion PreOC,
  - `pptocompracolaboradorid`: opcional, usuario activo con permiso de aprobacion PreOC.
- Al crear la lista de firmantes de PreOC, se toman los presupuestos que componen la PreOC y se agregan responsable, administrador y colaborador en ese orden.
- Si un aprobador se repite, queda una sola vez. Estos firmantes default no se pueden quitar.
- Aplica reemplazo por periodo de inactividad de aprobadores.
- El comprador puede agregar aprobadores manuales sin duplicar y puede editar el orden final.
- Para agregar aprobadores manuales se usara un patron equivalente al de items: boton `+`, modal de busqueda, validacion de usuario activo con permiso de aprobacion PreOC y validacion de duplicidad.
- Para reordenar firmantes se usaran botones Subir/Bajar. El primer registro no puede subir, el ultimo no puede bajar y si hay un solo registro ambos quedan bloqueados.
- La PreOC debe resolver el siguiente aprobador con la misma logica de REQ: validar vigencia, marcar `No Vigente` si corresponde, aplicar inactividad/reemplazo, insertar reemplazante y mantener `preocaprobadoridpnd` como denormalizacion controlada para consultas rapidas.
- La PreOC debe contemplar prioridad `Normal`/`Alta`, solo con efecto visual y enfasis en correo.
- La PreOC debe contemplar comentarios funcionales separados del LOG. El rechazo y la anulacion requieren comentario de mas de 10 caracteres.
- `DIMPARFIN` viene del maestro de items y `DIMCTC` del codigo del centro de costo. Para integracion ERP deben guardarse dimensiones por item PreOC, con estructura equivalente a `DimensionDistribucion`, incluyendo dimension, codigo de distribucion si aplica, tipo de calculo, codigo, porcentaje e importe.
- Las dimensiones se completan desde maestros y no son editables por comprador. Deben ser visibles, por ejemplo mediante accion "ver dimensiones" por linea.
- El workflow de compra es un valor fijo, no un maestro.

## Consecuencia

- La PreOC se convierte en el unico punto de compromiso contable del proceso de compras.
- El presupuesto refleja correctamente lo que esta en curso, lo que ya quedo confirmado y lo que fue revertido.
- La edicion previa a aprobacion queda limpia y sin falsos reversos.
- La aprobacion inicial marca la frontera entre borrado provisional y reversa historica.
- La separacion entre estado documental y estado ERP permite auditar PreOC aprobadas con error de sincronizacion, sincronizadas y sincronizadas anuladas.
- Los firmantes default de PreOC quedan trazables desde el presupuesto que origina cada linea.
