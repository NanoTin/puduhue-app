# Modulo de Compras - Modelo Definitivo de Presupuesto

> Documento definitivo del modelo funcional y logico del presupuesto de compras.
>
> Alcance vigente:
> - REQ solo valida, no genera movimiento presupuestario.
> - PreOC genera y administra los movimientos reales de presupuesto.
> - La reserva provisional solo se borra si la PreOC sigue en curso y aun no tiene aprobaciones.

## 1. Objetivo

Definir el modelo de presupuesto de compras basado en:

- temporada,
- sub familia de item,
- centro de costo,
- detalle mensual por año y mes,
- control de reservas, ajustes, traspasos, consumos y reversas.

## 2. Reglas base

1. El presupuesto se reutiliza desde la tabla de temporadas.
2. `temporadatipocodigo` debe permitir identificar temporadas de `PPTO_COMPRAS`.
3. La validacion de REQ es informativa, no genera transacciones.
4. La PreOC si genera transacciones presupuestarias.
5. La PreOC solo puede editarse mientras este `En Curso` y no tenga ninguna aprobacion.
6. Si una linea provisional se elimina antes de aprobarse, se borra su transaccion provisional asociada.
7. Si una linea ya fue confirmada, no se borra: se revierte.
8. Los consumos y reservas se guardan en negativo.
9. Las reversas se guardan en positivo.
10. Se deben guardar todos los movimientos confirmados del presupuesto.

## 3. Estructura logica de tablas

### 3.1 `PptoCompra`

Cabecera resumen del presupuesto por:

- Temporada
- Sub familia de item
- Centro de costo

Columnas logicas:

| Columna | Descripcion |
|---|---|
| `Temporada` | FK o codigo de temporada base. |
| `SubFamiliaItem` | FK a subfamilia del item. |
| `CentroCosto` | FK a centro de costo. |
| `Presupuestado` | Suma de montos cargados en `PptoCompraMensual`. |
| `AjustesPositivos` | Suma de ajustes positivos confirmados. |
| `AjustesNegativos` | Suma de ajustes negativos confirmados. |
| `Reproyectado` | `Presupuestado + AjustesPositivos + AjustesNegativos`. |
| `ConsumosEnCurso` | Suma negativa de reservas provisionales y consumos no confirmados. |
| `ConsumosConfirmados` | Suma negativa de consumos confirmados. |
| `SaldoDisponible` | `Reproyectado + ConsumosEnCurso + ConsumosConfirmados`. |

### 3.2 `PptoCompraMensual`

Detalle inicial del presupuesto por mes dentro de la temporada.

Columnas logicas:

| Columna | Descripcion |
|---|---|
| `Temporada` | FK o codigo de temporada base. |
| `SubFamiliaItem` | FK a subfamilia del item. |
| `CentroCosto` | FK a centro de costo. |
| `Anio` | Ano calendario. |
| `Mes` | Mes calendario. |
| `AnioMes` | Llave de periodo, formato `YYYY-MM`. |
| `MontoPpto` | Monto cargado para ese mes. |
| `Observacion` | Opcional. |

Regla:
- una fila por cada mes dentro del rango de fechas de la temporada.

### 3.3 `PptoCompraTransacciones`

Libro de movimientos del presupuesto.

Columnas logicas:

| Columna | Descripcion |
|---|---|
| `PptoCompra` | FK a la cabecera afectada. |
| `Anio` | Ano del impacto o referencia del periodo. |
| `Mes` | Mes del impacto o referencia del periodo. |
| `AnioMes` | Llave de cruce con el detalle mensual. |
| `TipoTransaccion` | FK a `PptoCompraTransaccionesTipo`. |
| `Monto` | Valor con signo segun la naturaleza del movimiento. |
| `MontoEnCurso` | Impacto negativo o positivo sobre consumos en curso. |
| `MontoConfirmado` | Impacto negativo o positivo sobre consumos confirmados. |
| `Motivo` | Texto obligatorio en ajustes, traspasos y reversas. |
| `ReferenciaOrigen` | Documento o evento que origina el movimiento. |
| `ReferenciaLinea` | Detalle puntual si aplica. |
| `GrupoMovimiento` | Agrupador para traspasos y pares logicos. |
| `Usuario` | Quien ejecuta el movimiento. |
| `FechaHora` | Momento del movimiento. |

### 3.4 `PptoCompraTransaccionesTipo`

Catalogo de tipos funcionales, no autoincrementales.

| Codigo | Uso |
|---|---|
| `PPTO_CARGA` | Carga inicial mensual del presupuesto. |
| `PPTO_AJUSTE_POS` | Ajuste positivo manual. |
| `PPTO_AJUSTE_NEG` | Ajuste negativo manual. |
| `PPTO_TRASPASO_SALIDA` | Salida de monto hacia otro presupuesto. |
| `PPTO_TRASPASO_ENTRADA` | Entrada de monto desde otro presupuesto. |
| `POC_RESERVA` | Reserva provisional al crear una PreOC. |
| `POC_CONFIRMACION` | Confirmacion al aprobar una PreOC. |
| `POC_REVERSA` | Reversa al rechazar o anular una PreOC. |

## 4. Formula de calculo

### 4.1 Presupuestado

`Presupuestado = SUM(PptoCompraMensual.MontoPpto)`

### 4.2 Reproyectado

`Reproyectado = Presupuestado + AjustesPositivos + AjustesNegativos`

### 4.3 Saldo disponible del presupuesto

`SaldoDisponible = Reproyectado + ConsumosEnCurso + ConsumosConfirmados`

Nota:
- `ConsumosEnCurso` se guarda negativo.
- `ConsumosConfirmados` se guarda negativo.
- Las reversas se guardan positivas.

### 4.4 Validacion de REQ

REQ solo valida, no descuenta.

`DisponibleREQ = SaldoDisponible - RequerimientosEnCurso - RequerimientosAprobadosSinPreOC`

Donde:
- `RequerimientosEnCurso` son REQ abiertos que aun no pasan a PreOC.
- `RequerimientosAprobadosSinPreOC` son REQ aprobados pendientes de PreOC.

### 4.5 Validacion de PreOC

La PreOC valida contra el saldo disponible operativo del presupuesto segun:

- sub familia del item,
- centro de costo,
- fecha de la PreOC,
- temporada correspondiente.

## 5. Comportamiento por proceso

### 5.1 Creacion de presupuesto

- La creacion representa el monto base.
- No se puede editar si ya existen movimientos.
- Si no existen movimientos, puede corregirse la carga inicial.

### 5.2 Ajustes

- Requieren motivo obligatorio.
- Pueden ser positivos o negativos.
- No modifican la carga original.
- Siempre generan transaccion propia.

### 5.3 Traspasos

- Son un tipo funcional distinto de transaccion.
- Se registran como salida en un presupuesto y entrada en otro.
- Deben quedar enlazados por un mismo `GrupoMovimiento`.

### 5.4 PreOC en curso

- Al crearse, genera reserva provisional.
- Si la PreOC esta en curso y no tiene aprobaciones, una linea provisional puede eliminarse.
- Si se elimina una linea provisional, se borra la transaccion provisional asociada.
- No se genera reversa en ese caso.

### 5.5 PreOC aprobada

- Cuando existe al menos una aprobacion, ya no se permite editar la PreOC.
- Si se rechaza o anula una PreOC ya confirmada, se genera reversa positiva.
- La reversa nunca borra historia.

## 6. Interpretacion de estados y efectos

### REQ

| Estado | Efecto |
|---|---|
| `BRR` / armado | Solo visual e informativo. |
| `EN_CURSO` | Solo valida disponibilidad. |
| `APR` | No mueve presupuesto. |
| `RCH` / `ANL` | No mueve presupuesto. |

### PreOC

| Estado | Efecto |
|---|---|
| `BRR` / creada | Reserva provisional. |
| `EN_CURSO` sin aprobaciones | Editable; se puede borrar la reserva provisional de una linea. |
| `EN_CURSO` con aprobaciones | No editable. |
| `APR` | Confirmacion de reserva. |
| `RCH` / `ANL` | Reversa positiva del compromiso confirmado. |

## 7. Reglas de trazabilidad

- Toda transaccion confirmada debe quedar persistida.
- Toda reserva confirmada debe poder reconstruirse desde su transaccion.
- Las transacciones de traspaso deben poder cruzarse entre si.
- Los borrados de transacciones provisionales por edicion en curso deben quedar registrados en la auditoria de PreOC, aunque no se mantengan como movimiento presupuestario final.

## 8. Fuera de alcance

- Requerimientos que descuenten presupuesto.
- Edicion de PreOC despues de la primera aprobacion.
- Lineas confirmadas que se borren en vez de revertirse.
