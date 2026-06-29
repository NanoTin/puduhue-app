# ADR-001 - Modelo de presupuesto de compras

## Contexto

El modulo de Compras necesita un presupuesto operativo distinto al enfoque jerarquico anterior. El negocio definio que el presupuesto de compras se reutiliza desde temporadas, se carga por mes, se cruza por sub familia de item y centro de costo, y debe conservar todos sus movimientos en forma trazable.

Ademas:

- REQ solo valida disponibilidad y no genera movimiento presupuestario.
- PreOC si genera movimientos reales.
- Los consumos y reservas se guardan en negativo.
- Las reversas se guardan en positivo.

## Decision

- Reutilizar la tabla de temporadas como base de identificacion temporal del presupuesto.
- Agregar `temporadatipocodigo` para identificar temporadas de `PPTO_COMPRAS`.
- Modelar el presupuesto de compras con estas entidades:
  - `PptoCompra` como cabecera resumen,
  - `PptoCompraMensual` como carga base mensual,
  - `PptoCompraTransacciones` como libro de movimientos,
  - `PptoCompraTransaccionesTipo` como catalogo funcional de tipos.
- Definir la llave funcional del presupuesto por:
  - Temporada,
  - Sub familia de item,
  - Centro de costo.
- Cargar el monto presupuestario por fila mensual dentro del rango de fechas de la temporada.
- Calcular la cabecera con:
  - `Presupuestado`
  - `Ajustes +`
  - `Ajustes -`
  - `Reproyectado`
  - `Consumos En Curso`
  - `Consumos Confirmados`
  - `Saldo Disponible`
- Usar la formula:
  - `Reproyectado = Presupuestado + AjustesPositivos + AjustesNegativos`
  - `SaldoDisponible = Reproyectado + ConsumosEnCurso + ConsumosConfirmados`
- Hacer que la validacion de REQ sea solo informativa, sin crear transacciones.
- Hacer que la validacion de PreOC use el saldo disponible del presupuesto y la clave funcional `Temporada + SubFamilia + CentroCosto`.
- Permitir edicion de presupuesto solo si no existen movimientos.
- Registrar ajustes como transacciones con motivo obligatorio.
- Registrar traspasos como salida/entrada enlazadas por un grupo de movimiento.
- Permitir eliminar una linea provisional de PreOC solo mientras la PreOC esta en curso y sin aprobaciones; en ese caso se borra la transaccion provisional asociada y no se genera reversa.
- Prohibir la edicion de una PreOC una vez exista al menos una aprobacion.
- Si una PreOC confirmada se rechaza o anula, registrar reversa positiva sin borrar historia.

## Consecuencia

- El presupuesto queda trazable como un libro de movimientos.
- La operacion diaria es simple para usuarios, pero auditable para el negocio.
- Se evita mezclar validacion preventiva de REQ con el compromiso real de PreOC.
- Las ediciones previas a aprobacion no contaminan el historial con reversas artificiales.

