# Modulo Compras - Contrato FE PreOC

> Contrato tecnico Frontend para implementar el corte funcional PreOC.
>
> Este documento define vistas PHP, estructura visual, formularios, acciones, modales y comportamiento UI. No contiene codigo ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE_FRONT.agent`
- `docs/02_ux.md`
- `docs/frontend_php_patrones_reutilizables.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/modulo_compras_preoc_contrato_sp.md`
- `docs/modulo_compras_preoc_contrato_be.md`
- `docs/modulo_compras_req_pendientes_contrato_fe.md`
- `apps/web-php/compras_req_listar.php`
- `apps/web-php/compras_req_crear.php`
- `apps/web-php/compras_req_ver.php`

## 2. Alcance FE

Vistas propuestas:

- `apps/web-php/compras_preoc_listar.php`
- `apps/web-php/compras_preoc_crear.php`
- `apps/web-php/compras_preoc_editar.php`
- `apps/web-php/compras_preoc_ver.php`
- `apps/web-php/compras_preoc_pendientes_aprobacion.php`

Fuera de alcance:

- Pantalla de cotizaciones.
- Multimoneda.
- Ejecucion real ERP/Finnegans.
- Adjuntos reales hasta aprobar DDL.
- Edicion posterior a primera aprobacion.

## 3. Reglas FE transversales

- Usar Bootstrap 5 y Bootstrap Icons.
- Usar layout `head.php`, `menu.php`, `footer.php` salvo render parcial.
- Tomar como base visual REQ v1, especialmente `compras_req_listar.php`, `compras_req_crear.php` y `compras_req_ver.php`.
- Usar clases `pdh-*` existentes y clases modulo-especificas con prefijo `compras-preoc-` si hacen falta.
- No usar CSS inline salvo estilos dinamicos generados por JS.
- Vistas no llaman SP, no instancian Services, no leen `$_SESSION` para reglas complejas.
- Feedback con toasts.
- `confirm()` nativo solo para acciones destructivas simples; rechazo/anulacion requieren comentario en modal.
- Todos los valores impresos deben usar `htmlspecialchars`.

## 4. Estados visuales

| Estado | Texto | Badge sugerido | Acciones principales |
|---|---|---|---|
| `BRR` | Borrador | `bg-secondary` | Ver, Editar, Enviar, Anular |
| `PND` | Pendiente | `bg-warning text-dark` | Ver, aprobar/rechazar si corresponde, volver a borrador si no hay aprobaciones |
| `APR` | Aprobada | `bg-success` | Ver |
| `RCH` | Rechazada | `bg-danger` | Ver, Editar/Rearmar si corresponde |
| `ANL` | Anulada | `bg-dark` | Ver |

Estado ERP:

- Sin estado: badge neutro o vacio.
- `SNC`: sincronizada.
- `ERR`: error visible.

## 5. `compras_preoc_listar.php`

Variables:

- `$preocs`
- `$meta`
- `$filtros`
- `$compradoresOptions`
- `$proveedoresOptions`
- `$partial`
- `$errorMessage` opcional

Estructura:

- Header `Pre Ordenes de Compra`.
- Acciones superiores:
  - `Por aprobar`: visible si usuario login tiene `usuariopermiteaprobpreoc = 1`.
  - `Nueva PreOC`: visible/habilitada si usuario login tiene `usuariocomprador = 1`.
- Filtros GET:
  - busqueda general (`filtroBusqueda`);
  - comprador;
  - estado documental;
  - estado ERP;
  - aprobador pendiente;
  - fecha desde, default UX: fecha actual menos 45 dias;
  - fecha hasta, default UX: fecha actual;
  - proveedor;
  - vigentes/todos si aplica.
- Tabla responsive con columnas minimas:
  - codigo PreOC (`preocdoc`);
  - fecha creacion (`preocfecha`);
  - fecha OC/presupuesto (`preocfechaoc`);
  - comprador;
  - proveedor;
  - estado documental;
  - estado ERP;
  - prioridad;
  - total;
  - aprobador pendiente;
  - acciones.

Reglas filtro comprador:

- Si `usuariocomprador = 1`, inicia con usuario login.
- Si el usuario no es comprador pero tiene acceso, inicia vacio y equivale a `TODOS`.
- El combo lista compradores aunque alguno ya no este vigente, para permitir busqueda historica.

## 6. `compras_preoc_crear.php`

Variables:

- `$formData`
- `$detalle`
- `$itemsAgrupados`
- `$pptoResumen`
- `$firmantes`
- `$proveedoresRows`
- `$condicionesPagoOptions`
- `$aprobadoresRows`
- `$errorMessage`
- `$partial`

Estructura del formulario:

1. Cabecera.
2. Items.
3. Lista de firmantes.
4. Presupuesto de compras.

Campos cabecera:

- `preocfechaoc`;
- proveedor (`erpproveedorid`);
- condicion de pago (`erpcondicionpagoid`);
- prioridad;
- observacion interna (`preocobsinterna`);
- observacion para ERP/OC (`preocobsoc`).

Reglas:

- No incluir `preocid` en crear.
- El comprador no digita `preocdoc`.
- `preocfecha` la define sistema/BD.
- `erpmonedacod` no se edita; vigente `PES`.
- La barra de acciones debe permanecer visible durante scroll vertical, replicando el criterio REQ.

Botones:

- Cancelar.
- Guardar como borrador.
- Enviar a aprobacion.

## 7. Items PreOC

Origen:

- Lineas seleccionadas desde `reqaprobados` pendientes/parciales.
- El item se cambia en pendientes de compra, no en PreOC.

Grilla de lineas origen:

- REQ;
- item;
- centro de costo;
- cantidad pendiente;
- cantidad a comprar;
- presupuesto resuelto;
- subtotal neto;
- observacion.

Grilla de items agrupados:

- item;
- unidad;
- cantidad total;
- precio neto unitario;
- neto total;
- impuestos;
- total;
- accion editar precio.

Reglas UI:

- Cantidad a comprar debe ser mayor a cero y no superar saldo pendiente.
- No permitir enviar si falta precio neto mayor a cero en algun item agrupado.
- El precio se informa una vez por item agrupado.
- Mostrar totales generales de compra y montos por presupuesto.

## 8. Modal de precio

Campos/lectura:

- item;
- unidad;
- cantidad total;
- precio neto;
- ultimo proveedor, ultimo precio y fecha ultima compra si el backend lo entrega;
- costo estandar/precio referencial si no existe historico;
- variacion visual.

Al confirmar:

- actualiza item agrupado;
- actualiza subtotales de lineas relacionadas;
- recalcula presupuesto y totales visibles.

## 9. Firmantes

Grilla:

- orden;
- usuario;
- tipo (`RESPONSABLE`, `ADMIN`, `COLABORADOR`, `MONTO`, `MANUAL`, `REEMPLAZO`);
- default/manual;
- estado;
- acciones.

Reglas:

- Firmantes default desde presupuestos no se remueven.
- Manuales se agregan con boton `+` y modal.
- Modal filtra usuarios activos con `usuariopermiteaprobpreoc = 1`.
- No permitir duplicados.
- Reordenar con botones Subir/Bajar.

## 10. Presupuesto de compras

Mostrar:

- presupuesto resuelto por linea;
- resumen por `preocpptoresumen`;
- monto PreOC por presupuesto;
- saldo antes/despues cuando exista;
- advertencia bloqueante si no existe presupuesto o no hay saldo suficiente.

Regla:

- A diferencia de REQ, esta validacion es bloqueante al enviar a aprobacion.

## 11. `compras_preoc_editar.php`

Mismas secciones que crear.

Reglas:

- Solo editable mientras no exista ninguna aprobacion.
- No editar si estado `APR`, `ANL` o si existe firmante `APR`.
- Si vuelve de `PND` a `BRR` sin aprobaciones, se borra reserva provisional sin reversa.
- Error: preservar `$formData` y mostrar toast.

Botones:

- Guardar como borrador.
- Enviar/Reenviar a aprobacion.
- Volver a borrador si `PND` sin aprobaciones.
- Volver a ver.

## 12. `compras_preoc_ver.php`

Variables:

- `$preoc`
- `$detalle`
- `$itemsAgrupados`
- `$imptos`
- `$pptoResumen`
- `$firmantes`
- `$comentarios`
- `$movimientosPpto`
- banderas `puede*`
- `$errorMessage`
- `$partial`

Estructura:

- Header con `preocdoc`, estado documental, estado ERP, proveedor, fechas y total.
- Acciones superiores:
  - Volver;
  - Editar;
  - Enviar aprobacion;
  - Volver a borrador;
  - Aprobar;
  - Rechazar;
  - Anular.
- Bloques:
  - cabecera;
  - lineas origen;
  - items agrupados;
  - impuestos;
  - presupuesto;
  - firmantes;
  - comentarios;
  - movimientos presupuestarios.

Rechazo y anulacion:

- comentario obligatorio de mas de 10 caracteres.
- usar modal; no usar solo confirmacion simple.

## 13. `compras_preoc_pendientes_aprobacion.php`

Variables:

- `$preocs`
- `$meta`
- `$filtros`
- `$proveedoresOptions`
- `$partial`
- `$errorMessage` opcional

Estructura:

- Header `PreOC pendientes de aprobacion`.
- Filtros:
  - busqueda;
  - fecha desde/hasta;
  - proveedor;
  - prioridad.
- Tabla:
  - codigo;
  - fecha OC;
  - comprador;
  - proveedor;
  - prioridad;
  - total;
  - presupuesto;
  - acciones.

Acciones:

- Ver.
- Aprobar/Rechazar preferentemente desde vista ver.

## 14. Adjuntos

Regla funcional visible:

- Antes de enviar a aprobacion debe existir al menos un adjunto.
- Guardar borrador no exige adjunto.

Bloqueo actual:

- No implementar UI real de adjuntos hasta aprobar DDL de `preocadjuntos` y maestro de extensiones.
- Si el corte exige envio a aprobacion, se debe resolver primero el DDL de adjuntos o declarar bloqueo.

## 15. ERP

Mostrar si existe:

- estado ERP;
- numero ERP;
- fecha/hora sincronizacion;
- error visible.

No implementar:

- POST real a Finnegans;
- reintentos;
- anulacion ERP remota.

## 16. Toasts sugeridos

- PreOC guardada correctamente.
- PreOC enviada a aprobacion.
- PreOC aprobada correctamente.
- PreOC rechazada correctamente.
- PreOC anulada correctamente.
- No hay saldo suficiente para enviar a aprobacion.
- Debe agregar al menos un adjunto antes de enviar.
- Debe informar precio neto para todos los items.

## 17. Validaciones

Cuando se implemente:

- Ejecutar `php -l` en vistas nuevas o modificadas.
- Ejecutar `git diff --check`.
