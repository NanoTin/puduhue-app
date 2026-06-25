# Modulo de Compras - Notas Temporales de Conversacion

> Documento temporal para no perder contexto entre chats.
> Ya existe una version definitiva del modelo de presupuesto en `docs/modulo_compras_presupuesto_definitivo.md`.

## Bloque 1 - Presupuesto

- Reutilizar `database/tables/01_table_temporadas.sql`.
- Agregar `temporadatipocodigo` para `PPTO_COMPRAS`.
- La clave funcional del presupuesto sera:
  - Temporada
  - Sub familia de item
  - Centro de Costo
- La carga del presupuesto se realizara por año-mes dentro del rango de fechas de la temporada seleccionada.
- La cabecera consolidada contendria:
  - Temporada
  - Sub familia item
  - Centro de Costo
  - Presupuestado
  - Ajustes +
  - Ajustes -
  - Reproyectado
  - Consumos
  - Saldo Disponible
- Existira una tabla de transacciones para todos los movimientos del presupuesto.
- Los tipos de transaccion seran codigos funcionales, no autoincrementales.

## Bloque 2 - Requerimientos

- La validacion contra presupuesto se realizara por subfamilia del item.
- La validacion sera contra saldo disponible, no contra el año-mes.
- Se dejara snapshot del presupuesto usado para comparar.
- El precio del item saldra del endpoint diario que sincroniza el maestro de items.
- Si un item es nuevo y no tiene precio, no podra agregarse al requerimiento.
- En ese caso, el item debera crearse primero directamente en el maestro de items de Puduhue App.

## Bloque 3 - Pre OC

- La Pre OC se validara automaticamente contra presupuesto.
- El usuario no seleccionara el presupuesto manualmente.
- La validacion usara:
  - subfamilia,
  - centro de costo,
  - fecha de la Pre OC,
  - temporada correspondiente.

## Bloque 4 - Reserva de presupuesto

- Este bloque fue reemplazado por el modelo definitivo del presupuesto.
- El REQ solo valida y no genera movimiento presupuestario.
- La reserva, confirmacion, eliminacion provisional y reversa se manejan solo en PreOC.
