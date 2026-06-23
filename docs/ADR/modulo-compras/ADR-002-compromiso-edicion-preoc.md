# ADR-002 - Compromiso y edicion de PreOC

## Contexto

La PreOC es el punto donde el presupuesto de compras se compromete de manera real. El negocio definio que la PreOC:

- se crea en estado `En Curso`,
- puede editarse solo mientras no tenga aprobaciones,
- genera reserva provisional al crearse,
- confirma la reserva al aprobarse,
- revierte lo confirmado si se rechaza o anula.

Tambien se definio que una linea provisional eliminada antes de la primera aprobacion no debe generar reversa, sino eliminarse junto con su transaccion provisional asociada.

## Decision

- La PreOC es la unica transaccion del flujo de compras que compromete presupuesto.
- El REQ no genera movimientos presupuestarios.
- Al crear una PreOC:
  - se valida saldo disponible,
  - se crea una reserva provisional negativa.
- Mientras la PreOC siga en curso y sin aprobaciones:
  - puede editarse,
  - puede eliminarse una linea provisional,
  - si una linea provisional se quita, se borra su transaccion provisional asociada.
- Cuando existe al menos una aprobacion:
  - la PreOC deja de ser editable.
- Si una PreOC ya confirmada se rechaza o anula:
  - se genera reversa positiva,
  - no se borra la historia del movimiento.
- La validacion de presupuesto para la PreOC se hace automaticamente por:
  - sub familia del item,
  - centro de costo,
  - fecha de la PreOC,
  - temporada correspondiente.

## Consecuencia

- La PreOC se convierte en el unico punto de compromiso contable del proceso de compras.
- El presupuesto refleja correctamente lo que esta en curso, lo que ya quedo confirmado y lo que fue revertido.
- La edicion previa a aprobacion queda limpia y sin falsos reversos.
- La aprobacion inicial marca la frontera entre borrado provisional y reversa historica.
