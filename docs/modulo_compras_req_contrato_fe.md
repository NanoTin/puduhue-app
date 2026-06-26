# Modulo Compras - Contrato FE REQ

> Contrato tecnico Frontend para implementar el primer corte funcional REQ.
>
> Este documento define vistas PHP, estructura visual, formularios, acciones, modales y comportamiento de UI. No contiene codigo ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE_FRONT.agent`
- `docs/02_ux.md`
- `docs/frontend_php_patrones_reutilizables.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_req_contrato_sp.md`
- `docs/modulo_compras_req_contrato_be.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `apps/web-php/empresas_listar.php`
- vistas existentes de `prodleche` como referencia de formularios transaccionales.

## 2. Alcance FE del primer corte REQ

Vistas aprobadas:

- `apps/web-php/compras_req_listar.php`
- `apps/web-php/compras_req_crear.php`
- `apps/web-php/compras_req_editar.php`
- `apps/web-php/compras_req_ver.php`
- `apps/web-php/compras_req_pendientes_aprobacion.php`

Fuera de alcance:

- Pantallas PreOC.
- Pantalla independiente de pendientes de compra.
- AJAX nuevo para buscar items/aprobadores.
- Integracion ERP.
- Adjuntos PreOC.

## 3. Reglas FE transversales

- Usar Bootstrap 5 y Bootstrap Icons.
- Usar layout `head.php`, `menu.php`, `footer.php` salvo render parcial.
- Usar `pdh-page`, `pdh-page__header`, `pdh-page__actions`, `pdh-filter-bar`, `pdh-card`, `pdh-data-table` y `.table-responsive` segun `docs/frontend_php_patrones_reutilizables.md`.
- Crear clases modulo-especificas solo si son necesarias, con prefijo `req-` o `compras-req-`.
- No usar CSS inline salvo estilos dinamicos generados por JS.
- Vistas no llaman SP, no instancian Services, no leen `$_SESSION` para reglas complejas.
- Vistas muestran variables entregadas por `ComprasReqController`.
- Feedback con toasts; evitar `alert()`.
- `confirm()` nativo solo para acciones destructivas o cambio irreversible, por ejemplo anular.
- Botones de accion con Bootstrap Icons, `title` y `aria-label` cuando sean solo icono.
- Todos los valores impresos deben usar `htmlspecialchars`.

## 4. Estados visuales REQ

| Estado | Texto | Badge sugerido | Acciones principales |
|---|---|---|---|
| `BRR` | Borrador | `bg-secondary` | Ver, Editar, Anular |
| `PND` | Pendiente de aprobacion | `bg-warning text-dark` | Ver; aprobar/rechazar solo si corresponde |
| `EDT` | En edicion | `bg-info text-dark` | Ver; retomar edicion si es creador |
| `APR` | Aprobado | `bg-success` | Ver |
| `RCH` | Rechazado | `bg-danger` | Ver, Editar/Reenviar |
| `ANL` | Anulado | `bg-dark` | Ver |

Indicadores adicionales:

- Prioridad alta: badge o icono visual no bloqueante.
- Advertencia presupuesto: badge `bg-warning text-dark`.
- Fuera de presupuesto: badge `bg-danger`.
- Vinculacion PreOC: mostrar solo si `reqcompraestadopreocid` viene informado.

## 5. `compras_req_listar.php`

Variables:

- `$reqs`
- `$meta`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

Estructura:

- Header `Requerimientos de compra`.
- Accion principal `Nuevo REQ` hacia `?route=compras-req/crear`.
- Accion secundaria hacia `?route=compras-req/pendientes-aprobacion`.
- Filtros GET:
  - busqueda general (`filtroBusqueda`), placeholder: `Codigo, observacion, solicitante o aprobador`;
  - estado;
  - fecha desde;
  - fecha hasta;
  - centro de costo;
  - prioridad;
  - vigentes/todos si aplica.
- Tabla responsive con columnas minimas:
  - codigo REQ;
  - fecha;
  - centro de costo;
  - solicitante/funcionario si existe;
  - estado;
  - prioridad;
  - total neto;
  - advertencia presupuesto;
  - aprobador pendiente;
  - acciones.

Acciones por fila:

- Ver: `?route=compras-req/ver&id=X`.
- Editar: visible si la fila trae `puedeEditar`.
- Retomar edicion: visible si la fila trae `puedeRetomarEdicion`.
- Anular: POST a `?route=compras-req/anular` cuando aplique.

## 6. `compras_req_crear.php`

Variables:

- `$formData`
- `$centrosOptions`
- `$funcionariosOptions`
- `$itemsRows`
- `$aprobadoresRows`
- `$errorMessage`
- `$partial`

Formulario:

- `method="POST"` hacia `?route=compras-req/crear`.
- No incluir `reqcompraid`.
- Usar inputs normalizables por BE: `detalle[n][invitemid]`, `detalle[n][reqcompradetcantidad]`, `detalle[n][reqcompradetobs]`, `firmantesManual[n][usuarioid]`, `firmantesManual[n][firmanteorden]` y `accion`.
- Campos cabecera:
  - tipo REQ: Material / Servicio;
  - centro de costo;
  - funcionario opcional;
  - prioridad Normal / Alta;
  - observacion general.
- Secciones:
  - detalle de items;
  - firmantes manuales;
  - resumen presupuestario informativo si existe tras error o recalculo posterior.
- La barra de acciones del formulario debe permanecer visible en la parte superior durante el scroll vertical, especialmente en pantallas chicas. Puede resolverse con una barra sticky usando clases del modulo, sin CSS inline.

Botones:

- Cancelar.
- Guardar como borrador.
- Enviar a aprobacion.

Reglas UX de botones:

- `Cancelar` no persiste nada en BD.
- Al presionar `Cancelar`, abrir modal:
  `Esta accion borrara los datos ingresados. Desea continuar?`
- Si acepta, vuelve a `?route=compras-req/listar`.
- Si cancela el modal, permanece en el formulario sin refrescar ni perder datos.
- `Guardar como borrador` debe abrir modal de confirmacion antes del POST.
- `Enviar a aprobacion` debe abrir modal de confirmacion antes del POST.

Comportamiento:

- El tipo REQ filtra o valida items compatibles.
- No permitir agregar item con precio cero en cliente si el dato viene en `$itemsRows`; el SP valida igualmente.
- No permitir cantidades menores o iguales a cero en cliente; el SP valida igualmente.
- Mantener datos ingresados si hay error y el Controller devuelve `$formData`.
- Tanto `Guardar como borrador` como `Enviar a aprobacion` requieren al menos:
  - tipo REQ,
  - centro de costo,
  - prioridad,
  - al menos un item valido en el detalle.

## 7. `compras_req_editar.php`

Variables:

- `$req`
- `$detalle`
- `$firmantes`
- `$comentarios`
- `$analisisPpto`
- `$formData`
- `$centrosOptions`
- `$funcionariosOptions`
- `$itemsRows`
- `$aprobadoresRows`
- `$errorMessage`
- `$partial`

Formulario:

- `method="POST"` hacia `?route=compras-req/editar`.
- Incluir hidden `reqcompraid`.
- Misma estructura que crear.
- Mostrar codigo, estado actual y fecha funcional en bloque de contexto.
- Cambiar `reqcompraid` en la URL o en el hidden no debe habilitar edicion; el Controller y el SP validan permisos y estado antes de mostrar o guardar.
- La barra de acciones del formulario debe permanecer visible en la parte superior durante el scroll vertical, especialmente en pantallas chicas. Puede resolverse con una barra sticky usando clases del modulo, sin CSS inline.

Botones:

- Guardar como borrador.
- Guardar y reenviar a aprobacion.
- Cancelar cambios si estado `EDT`.
- Volver a ver.

Reglas UX de botones:

- `Guardar como borrador` en `EDT` guarda cambios y deja el REQ en `BRR`.
- `Guardar y reenviar a aprobacion` en `EDT` guarda cambios y reenvia a aprobacion, dejando el REQ en `PND`.
- `Cancelar cambios` en `EDT` no guarda cambios y abre modal:
  `Esta accion NO guardara los cambios realizados y volvera a dejar el requerimiento Pendiente de Aprobacion (En Curso). Desea continuar?`
- Si acepta el modal de `Cancelar cambios`, el FE hace POST a `?route=compras-req/cancelar-edicion`.
- Si cancela el modal, permanece en la pantalla de edicion sin refrescar ni perder los cambios en memoria.

Redirecciones esperadas:

- Exito: `?route=compras-req/ver&id=X`.
- Error: misma vista con `errorMessage`, toast y datos preservados.

Regla `EDT`:

- Si el usuario abandona navegador/pestana, el FE no intenta liberar automaticamente.
- La accion normal para salir sin guardar es el boton Cancelar cambios.
- Si el REQ esta `PND`, el FE no debe navegar directo a editar con efecto de estado; debe usar `POST ?route=compras-req/tomar-edicion` y luego seguir la redireccion del BE.

## 8. `compras_req_ver.php`

Variables:

- `$req`
- `$detalle`
- `$firmantes`
- `$comentarios`
- `$analisisPpto`
- `$puedeAprobar`
- `$puedeRechazar`
- `$puedeEditar`
- `$puedeAnular`
- `$errorMessage`
- `$partial`

Estructura:

- Header con codigo REQ, estado, centro, fecha, prioridad y total.
- Acciones superiores:
  - Volver;
  - Editar si `$puedeEditar`;
  - Aprobar si `$puedeAprobar`;
  - Rechazar si `$puedeRechazar`;
  - Anular si `$puedeAnular`.
- Bloques:
  - cabecera;
  - detalle de items;
  - firmantes;
  - analisis de presupuesto;
  - comentarios funcionales.

Modo aprobar/rechazar:

- La misma vista se usa desde pendientes de aprobacion.
- Aprobar: comentario opcional, POST a `?route=compras-req/aprobar`.
- Rechazar: comentario obligatorio, minimo mas de 10 caracteres, POST a `?route=compras-req/rechazar`.
- Si el REQ esta en `EDT`, mostrar mensaje funcional de que esta siendo editado y no mostrar botones aprobar/rechazar.

## 9. `compras_req_pendientes_aprobacion.php`

Variables:

- `$reqs`
- `$meta`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

Estructura:

- Header `REQ pendientes de aprobacion`.
- Filtros GET:
  - busqueda general (`filtroBusqueda`), placeholder: `Codigo, observacion o solicitante`;
  - fecha desde/hasta;
  - centro de costo;
  - prioridad.
- Tabla responsive con columnas:
  - codigo;
  - fecha;
  - centro;
  - solicitante;
  - prioridad;
  - total;
  - advertencia presupuesto;
  - acciones.

Acciones por fila:

- Ver: `?route=compras-req/ver&id=X`.
- Aprobar: preferentemente desde la vista ver; si se muestra en tabla, debe POSTear con confirmacion ligera y seguir validacion SP.
- Rechazar: preferentemente desde la vista ver para exigir comentario.

## 10. Modal de items

Fuente: `$itemsRows` desde `ComprasCatalogosService::listarItemsCompraReqFormGrid`.

Primer corte:

- Datos cargados server-side al renderizar.
- Filtro client-side por codigo/descripcion/subfamilia.
- Seleccion agrega fila al detalle del formulario.
- Columnas minimas:
  - codigo item;
  - descripcion;
  - unidad;
  - subfamilia;
  - precio neto;
  - tipo Material/Servicio.

Reglas UI:

- No permitir seleccionar item duplicado.
- Item precio cero debe verse no seleccionable o mostrar accion bloqueada.
- Mostrar mensaje breve si el tipo del item no coincide con tipo REQ.
- Cantidad y observacion se editan en la grilla de detalle, no dentro del modal.

## 11. Modal de aprobadores

Fuente: `$aprobadoresRows` desde `ComprasCatalogosService::listarUsuariosAprobadoresReqFormGrid`.

Primer corte:

- Datos cargados server-side al renderizar.
- Filtro client-side por nombre/usuario/correo.
- Seleccion agrega firmante manual a la grilla.

Columnas minimas:

- nombre;
- usuario/login si existe;
- correo;
- estado activo.

Reglas UI:

- No permitir duplicados.
- Firmantes default y fuera de presupuesto se muestran como no removibles.
- Firmantes manuales pueden removerse antes de enviar.
- Si se implementa reordenamiento, usar botones Subir/Bajar con Bootstrap Icons.
- Firmantes fuera de presupuesto siempre quedan al final y no se reordenan.

## 12. Analisis presupuestario informativo

Visible en crear/editar/ver cuando exista data disponible.

Mostrar:

- temporada, solo si el backend la entrega;
- subfamilia;
- centro de costo;
- saldo disponible actual;
- otros REQ en curso;
- aprobados pendientes de compra;
- monto de este REQ;
- saldo proyectado;
- porcentaje de uso;
- deficit/advertencia.

Reglas UI:

- Debe ser claramente informativo.
- No bloquear envio por saldo insuficiente desde FE.
- Si hay advertencia, mostrar badge y texto de apoyo.
- Si corresponde fuera de presupuesto, mostrar que se agregaran/aplicaran autorizadores fuera de presupuesto.

## 13. Comentarios y toasts

Toasts sugeridos:

- REQ guardado correctamente.
- REQ enviado a aprobacion.
- REQ aprobado correctamente.
- REQ rechazado correctamente.
- REQ anulado correctamente.
- El REQ esta siendo editado y no puede aprobarse/rechazarse.
- Error funcional devuelto por SP/Service.

Comentarios:

- Aprobacion: opcional.
- Rechazo: obligatorio, mas de 10 caracteres.
- Anulacion: obligatorio.
- Mostrar historial en `compras_req_ver.php`.

## 14. Validaciones cliente

Las validaciones cliente son ergonomia; la regla final queda en SP.

Validar:

- centro de costo requerido;
- tipo REQ requerido;
- al menos una linea para enviar;
- cantidad mayor a cero;
- no duplicar item;
- no duplicar firmante manual;
- comentario de rechazo mayor a 10 caracteres;
- comentario de anulacion obligatorio.

No validar en cliente como fuente definitiva:

- permisos;
- estado actual;
- concurrencia;
- aprobador pendiente;
- saldo presupuestario como bloqueo.

## 15. Validaciones de implementacion

Cuando se implemente:

- Ejecutar `php -l` en vistas PHP nuevas o modificadas.
- Ejecutar `git diff --check`.
- Revisar visualmente listado, crear, editar, ver y pendientes de aprobacion.
- Verificar responsive con tablas dentro de `.table-responsive`.

## 16. Cierre antes de implementar FE

- Usar `pdh-components.css` y componentes existentes siempre que cubran el caso; agregar CSS modulo-especifico solo si falta una pieza concreta.
- Usar los helpers comunes disponibles de toasts/confirmaciones.
- Las acciones de aprobar/rechazar quedan en `ver`; cualquier atajo futuro desde tabla de pendientes requiere contrato adicional.
