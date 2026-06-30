# Modulo Compras - Contrato FE REQ Aprobados / Pendientes de Compra

> Contrato tecnico Frontend para implementar el puente entre REQ aprobado y PreOC.
>
> Este documento define vistas PHP, estructura visual, formularios, acciones y comportamiento de UI. No contiene codigo ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE_FRONT.agent`
- `docs/02_ux.md`
- `docs/frontend_php_patrones_reutilizables.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_req_pendientes_contrato_sp.md`
- `docs/modulo_compras_req_pendientes_contrato_be.md`
- `docs/modulo_compras_req_contrato_fe.md`
- `apps/web-php/compras_req_listar.php`
- `apps/web-php/compras_req_ver.php`

## 2. Alcance FE

Vistas propuestas:

- `apps/web-php/compras_req_pendientes_listar.php`
- `apps/web-php/compras_req_pendientes_ver.php`
- `apps/web-php/compras_req_pendientes_seleccionar_preoc.php`

Fuera de alcance:

- Formulario completo PreOC.
- Adjuntos PreOC.
- Integracion ERP.
- AJAX nuevo obligatorio.
- Ejecutar SP o Services desde vistas.

## 3. Reglas FE transversales

- Usar Bootstrap 5 y Bootstrap Icons.
- Usar layout `head.php`, `menu.php`, `footer.php` salvo render parcial.
- Tomar como base visual el patron de `compras_req_listar.php` y `compras_req_ver.php`.
- Usar clases `pdh-*` existentes y clases modulo-especificas con prefijo `compras-req-pendientes-` si hacen falta.
- No usar CSS inline salvo estilos dinamicos generados por JS.
- Vistas no llaman SP, no instancian Services, no leen `$_SESSION` para reglas complejas.
- Feedback con toasts.
- `confirm()` nativo solo para acciones destructivas simples; para anular saldo y cambiar item se recomienda modal con motivo.
- Todos los valores impresos deben usar `htmlspecialchars`.

## 4. Estados visuales

| Estado `reqaprobadoestado` | Texto | Badge sugerido | Acciones |
|---|---|---|---|
| `1` | Pendiente | `bg-warning text-dark` | Ver, anular saldo, cambiar item, seleccionar para PreOC |
| `2` | Parcial | `bg-info text-dark` | Ver, anular saldo, seleccionar para PreOC |
| `3` | Completa | `bg-success` | Ver |
| `4` | Anulada | `bg-dark` | Ver |

Indicadores:

- Item modificado: badge o icono visible.
- Compra parcial: mostrar cantidad comprada y pendiente.
- Anulacion parcial/total: mostrar cantidad anulada.

## 5. `compras_req_pendientes_listar.php`

Variables:

- `$pendientes`
- `$meta`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

Estructura:

- Header `Pendientes de compra`.
- Accion principal hacia seleccion para PreOC si el usuario es comprador.
- Filtros GET:
  - busqueda general (`filtroBusqueda`);
  - fecha desde;
  - fecha hasta;
  - centro de costo;
  - tipo REQ;
  - estado operativo;
  - solo con saldo.
- Tabla responsive con columnas minimas:
  - codigo REQ;
  - fecha aprobacion o fecha REQ;
  - centro de costo;
  - item;
  - unidad;
  - cantidad requerida;
  - cantidad pendiente;
  - cantidad comprada;
  - cantidad anulada;
  - estado;
  - acciones.

Acciones por fila:

- Ver: `?route=compras-req-pendientes/ver&id=X`.
- Anular saldo: visible si `$row['puedeAnularSaldo']`.
- Cambiar item: visible si `$row['puedeCambiarItem']`.
- Seleccionar para PreOC: visible si `$row['puedeSeleccionarPreoc']`.

## 6. `compras_req_pendientes_ver.php`

Variables:

- `$pendiente`
- `$historial`
- `$cambios`
- `$itemsCambioRows`
- `$puedeAnularSaldo`
- `$puedeCambiarItem`
- `$partial`
- `$errorMessage` opcional

Estructura:

- Header con codigo REQ, estado pendiente, item y centro.
- Acciones superiores:
  - Volver;
  - Anular saldo;
  - Cambiar item.
- Bloques:
  - resumen de REQ origen;
  - linea aprobada;
  - cantidades operativas;
  - historial;
  - cambios de item.

### 6.1 Modal anular saldo

Campos:

- cantidad a anular;
- motivo obligatorio.

Reglas cliente:

- cantidad mayor a cero;
- cantidad no mayor a cantidad pendiente visible;
- motivo con mas de 10 caracteres.

POST:

- `?route=compras-req-pendientes/anular-saldo`
- hidden `reqaprobadoid`.

### 6.2 Modal cambiar item

Campos:

- item nuevo desde `$itemsCambioRows`;
- motivo obligatorio.

Reglas cliente:

- item nuevo requerido;
- motivo con mas de 10 caracteres;
- no permitir seleccionar el mismo item visible;
- advertir que el cambio se realiza en pendientes de compra y afectara la futura PreOC.

POST:

- `?route=compras-req-pendientes/cambiar-item`
- hidden `reqaprobadoid`.

## 7. `compras_req_pendientes_seleccionar_preoc.php`

Objetivo:

- Permitir al comprador seleccionar lineas pendientes/parciales que alimentaran una nueva PreOC.
- Esta pantalla no crea PreOC por si sola; agrega lineas a un carrito PreOC.

Variables:

- `$pendientes`
- `$seleccionados`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

Estructura:

- Header `Seleccionar pendientes para PreOC`.
- Filtros equivalentes al listado, restringidos a lineas con saldo pendiente.
- Tabla responsive con checkbox por fila para agregar al carrito.
- Boton/indicador de carrito con contador.
- Modal de carrito con lineas agregadas y accion liberar.
- Acciones:
  - Volver;
  - Liberar carrito;
  - Crear PreOC.

Reglas UI:

- Solo se seleccionan lineas con `reqaprobadocantidadpendiente > 0`.
- Debe evitarse mezclar Material y Servicio si el contrato PreOC mantiene una PreOC de tipo unico.
- La cantidad a comprar puede definirse en PreOC; si se define en esta pantalla, debe validarse contra saldo pendiente y enviarse como seleccion preliminar.
- La seleccion debe sobrevivir al filtrado usando carrito persistente.
- Al agregar una linea al carrito, debe dejar de mostrarse en la grilla principal.
- Si el usuario vuelve al flujo con carrito activo, debe poder continuar o liberar.
- Si el item no tiene tasa impositiva de compra configurada, no se puede agregar y se muestra mensaje para contactar a Administracion.

## 8. Mensajes y toasts

Toasts sugeridos:

- Saldo anulado correctamente.
- Item cambiado correctamente.
- No se puede anular una cantidad mayor al saldo pendiente.
- Debe ingresar un motivo.
- Seleccione al menos una linea para crear PreOC.

## 9. Validaciones

Cuando se implemente:

- Ejecutar `php -l` en vistas nuevas o modificadas.
- Ejecutar `git diff --check`.
