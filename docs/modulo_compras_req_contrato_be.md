# Modulo Compras - Contrato BE REQ

> Contrato tecnico Backend para implementar el primer corte funcional REQ.
>
> Este documento define clases, responsabilidades, rutas web, metodos y variables Controller -> View. No contiene codigo ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE.agent`
- `PUDUHUE_FRONT.agent`
- `docs/03_backend.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_req_contrato_sp.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `src/Controllers/Web/EmpresasController.php`
- `src/Services/EmpresasService.php`
- `src/Controllers/Web/ProdlecheController.php`
- `src/Services/ProdlecheService.php`
- `src/Services/ProdlechetiposService.php`

## 2. Alcance BE del primer corte REQ

El primer contrato BE REQ cubre:

- `ComprasReqService`
- `ComprasCatalogosService`
- `ComprasReqController`
- rutas web para listar, crear, editar, ver, aprobar, rechazar, anular y gestionar edicion;
- carga de datos para vistas de listado, creacion, edicion, visualizacion y pendientes de aprobacion;
- consumo de SP definidos en `docs/modulo_compras_req_contrato_sp.md`;
- uso de catalogos reutilizables con metodos `*FormSelect` y `*FormGrid`.

Fuera de alcance:

- API externa.
- Endpoints JSON publicos separados.
- PreOC.
- Pendientes de compra como pantalla independiente.
- Sincronizacion ERP.
- Ejecucion directa de SQL desde vistas.

## 3. Archivos BE propuestos

| Archivo | Responsabilidad |
|---|---|
| `src/Services/ComprasReqService.php` | Coordina operaciones REQ contra SP de consulta y mantenimiento. |
| `src/Services/ComprasCatalogosService.php` | Centraliza catalogos de Compras para combos, filtros, grillas y modales. |
| `src/Controllers/Web/ComprasReqController.php` | Orquesta rutas web, contexto de usuario, llamadas a Services y carga de vistas. |

Reglas:

- Cargar dependencias con `require_once`, siguiendo patron existente.
- Usar `Database::callSpQuery()` para SP de consulta.
- Usar `Database::callSpMaint()` para SP de mantenimiento.
- Usar `Database::select()` solo en catalogos simples de `ComprasCatalogosService`.
- No llamar SP desde vistas.
- No leer usuario desde URL para decisiones de seguridad; usar `AuthMiddleware::getUserContext()`.

## 4. `ComprasReqService`

Responsabilidad: armar payloads para los SP REQ, normalizar filtros simples y devolver resultados al Controller.

Metodos publicos:

| Metodo | SP / fuente | Uso |
|---|---|---|
| `listarReq(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_listar_resumen` | Listado principal. |
| `listarPendientesAprobacion(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_listar_pendientes_aprobacion` | Listado de REQ por aprobar del usuario login. |
| `consultarReqResumen(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_consulta_por_id_resumen` | Cabecera para ver/editar/aprobar. |
| `consultarReqDetalle(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_consulta_por_id_detalle` | Detalle de lineas. |
| `consultarReqFirmantes(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_consulta_por_id_firmantes` | Lista de firmantes. |
| `consultarReqComentarios(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_consulta_por_id_comentarios` | Comentarios funcionales. |
| `consultarReqAnalisisPpto(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_ppto_analizar` | Analisis presupuestario informativo. |
| `crearReq(array $data, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_crear` | Crear borrador o enviar a aprobacion. |
| `editarReq(int $reqcompraid, array $data, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_editar` | Guardar/reenviar cambios. |
| `tomarEdicion(int $reqcompraid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_tomar_edicion` | Pasar `PND -> EDT` mediante `POST compras-req/tomar-edicion`. |
| `cancelarEdicion(int $reqcompraid, ?string $motivo, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_cancelar_edicion` | Salir normalmente de `EDT` sin guardar. |
| `liberarEdicion(int $reqcompraid, string $motivo, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_liberar_edicion` | Liberacion controlada/manual. |
| `aprobarReq(int $reqcompraid, ?string $comentario, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_aprobar` | Aprobar firmante pendiente. |
| `rechazarReq(int $reqcompraid, string $comentario, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_rechazar` | Rechazar con comentario obligatorio. |
| `anularReq(int $reqcompraid, string $comentario, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_anular` | Anular cuando estado lo permita. |

Metodos auxiliares internos recomendados:

- `normalizarFiltrosListado`
- `normalizarDetalleInput`
- `normalizarFirmantesInput`
- `consultarReqCompleto`

`consultarReqCompleto` puede agrupar llamadas a resumen, detalle, firmantes, comentarios y analisis presupuestario para evitar duplicacion en el Controller.

Filtros de listado:

- El Controller recibe `filtroBusqueda` desde GET y el Service lo envia al SP con el mismo nombre.
- El filtro aprobado para REQ es `filtroBusqueda`.
- La composicion de campos buscables vive en los SP de listado, no en el Controller.

## 5. `ComprasCatalogosService`

Responsabilidad: centralizar fuentes auxiliares de Compras para formularios, filtros, grillas y modales reutilizables.

Metodos publicos:

| Metodo | Uso | Regla |
|---|---|---|
| `listarCentrosCostoUsuarioFormSelect(int $usuarioId, ?int $activo = 1): array` | Combo centro de costo en crear/editar/listar. | Solo centros activos asociados al usuario. |
| `listarUsuariosAprobadoresReqFormGrid(?string $filtroBusqueda = null, ?array $excluirUsuarioIds = null): array` | Modal firmantes manuales REQ. | Usuarios activos con `usuariopermiteaprobreq = 1`. |
| `listarItemsCompraReqFormGrid(int $reqcompratipo, ?string $filtroBusqueda = null): array` | Modal items REQ. | Items activos, `invitemcompra = 1`, precio mayor a cero y tipo compatible. |
| `listarFuncionariosFormSelect(?string $filtroBusqueda = null, ?int $centrocostoid = null): array` | Combo/busqueda solicitante opcional. | Funcionarios activos, opcionalmente filtrados por centro. |

Reglas:

- No crear SP auxiliares por cada combo o modal.
- Usar SQL simple con `Database::select()` o reutilizar SP/listados existentes si calzan con el caso.
- Mantener metodos publicos explicitos por regla funcional.
- Usar helpers privados para evitar duplicar SQL comun.
- Si un catalogo pasa a tener regla transaccional critica o auditoria propia, promoverlo a SP con contrato previo.
- Catalogos PreOC y compradores quedan fuera de este corte; no implementar metodos futuros hasta cerrar el contrato PreOC/pendientes.

## 5.1 Payload normalizado FE -> BE -> SP

El Controller debe normalizar los POST de crear/editar antes de llamar al Service. Payload minimo acordado:

- `reqcompratipo`: `1` Material o `2` Servicio.
- `centrocostoid`: centro seleccionado.
- `funcionariorut`: RUT opcional o `null`.
- `reqcompraobs`: observacion opcional.
- `reqcompraprioridad`: `1` Normal o `2` Alta.
- `accion`: `guardar_borrador`, `enviar_aprobacion` o `reenviar_aprobacion`. Cancelar edicion no viaja como `accion`; usa ruta dedicada.
- `detalle[]`: cada fila con `invitemid`, `reqcompradetcantidad` y `reqcompradetobs` opcional.
- `firmantesManual[]`: cada fila con `usuarioid` y `firmanteorden`.
- `comentario`: opcional, salvo rechazo/anulacion segun contrato SP.

Reglas:

- El FE puede enviar inputs como `detalle[0][invitemid]`; el BE los normaliza a arrays limpios.
- El BE no acepta campos calculados de item, precio, subfamilia, unidad ni totales desde el POST; esos valores se resuelven en SP desde maestros.
- `POST /editar` no confia en el hidden `reqcompraid`; el SP vuelve a validar estado, creador/autorizacion y aprobador pendiente.
- `crearPost` solo debe invocar `sp_compras_req_crear` para acciones que persisten: `guardar_borrador` o `enviar_aprobacion`.
- La accion de cancelar/descartar en crear no persiste datos y vuelve al listado; no crea cabecera vacia en BD.
- Toda accion que persista datos en tablas debe validar datos minimos y al menos una linea valida. En crear, aplica a `guardar_borrador` y `enviar_aprobacion`:
  - `reqcompratipo`,
  - `centrocostoid`,
  - `reqcompraprioridad`,
  - una linea valida en `detalle[]`.

## 6. `ComprasReqController`

Responsabilidad: manejar rutas web, contexto de usuario, GET/POST, toasts, redirects y carga de vistas.

Metodos publicos:

| Ruta | Verbo | Metodo Controller | Vista / salida |
|---|---|---|---|
| `compras-req/listar` | GET | `listar(bool $partial = false): void` | `compras_req_listar.php` |
| `compras-req/crear` | GET | `crearForm(bool $partial = false): void` | `compras_req_crear.php` |
| `compras-req/crear` | POST | `crearPost(bool $partial = false): void` | redirect/listar o vuelve a crear |
| `compras-req/editar` | GET | `editarForm(bool $partial = false): void` | `compras_req_editar.php` |
| `compras-req/editar` | POST | `editarPost(bool $partial = false): void` | redirect a `compras-req/ver&id=X` o vuelve a editar |
| `compras-req/ver` | GET | `ver(bool $partial = false): void` | `compras_req_ver.php` |
| `compras-req/pendientes-aprobacion` | GET | `pendientesAprobacion(bool $partial = false): void` | `compras_req_pendientes_aprobacion.php` |
| `compras-req/aprobar` | POST | `aprobarPost(bool $partial = false): void` | redirect/pendientes o ver |
| `compras-req/rechazar` | POST | `rechazarPost(bool $partial = false): void` | redirect/pendientes o ver |
| `compras-req/anular` | POST | `anularPost(bool $partial = false): void` | redirect/listar |
| `compras-req/tomar-edicion` | POST | `tomarEdicionPost(bool $partial = false): void` | redirect/editar |
| `compras-req/cancelar-edicion` | POST | `cancelarEdicionPost(bool $partial = false): void` | redirect/ver o listar |

Reglas:

- Toda accion protegida llama `AuthMiddleware::requireAuth()`.
- Obtener contexto con `AuthMiddleware::getUserContext()`.
- Remover `_token`, `action`, `route` antes de enviar payload a Service.
- Usar toasts para exito/error.
- Redirigir con `header('Location: ...')` y `exit`.
- Validaciones de negocio profundas viven en SP; el Controller solo valida presencia minima para UX.

## 7. Rutas Web

Agregar mapeo de modulo `compras-req` hacia `ComprasReqController` en `src/Routes/web.php`.

Rutas aprobadas:

- `compras-req/listar`
- `compras-req/crear`
- `compras-req/editar`
- `compras-req/ver`
- `compras-req/pendientes-aprobacion`
- `compras-req/aprobar`
- `compras-req/rechazar`
- `compras-req/anular`
- `compras-req/tomar-edicion`
- `compras-req/cancelar-edicion`

No agregar rutas PreOC en este corte.

## 8. Variables Controller -> View

Nombres de vistas aprobados para el primer corte:

- `compras_req_listar.php`
- `compras_req_crear.php`
- `compras_req_editar.php`
- `compras_req_ver.php`
- `compras_req_pendientes_aprobacion.php`

### 8.1 Listado principal

Vista: `compras_req_listar.php`

Variables:

- `$reqs`
- `$meta`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

### 8.2 Crear

Vista: `compras_req_crear.php`

Variables:

- `$formData`
- `$centrosOptions`
- `$funcionariosOptions`
- `$itemsRows`
- `$aprobadoresRows`
- `$errorMessage`
- `$partial`

### 8.3 Editar

Vista: `compras_req_editar.php`

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

### 8.4 Ver / aprobar / rechazar

Vista: `compras_req_ver.php`

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

La vista de ver se reutiliza para aprobar/rechazar desde pendientes de aprobacion.

### 8.5 Pendientes de aprobacion

Vista: `compras_req_pendientes_aprobacion.php`

Variables:

- `$reqs`
- `$meta`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

## 9. Flujo `EDT`

Regla aprobada:

- `GET compras-req/editar&id=X` solo muestra el formulario si el usuario tiene permiso; nunca toma edicion ni cambia estado.
- Si el creador quiere editar un REQ en `PND`, debe ejecutar primero `POST compras-req/tomar-edicion`; si el SP cambia el documento a `EDT`, el BE redirige a `GET compras-req/editar&id=X`.
- Si el REQ ya esta en `EDT` y el mismo creador vuelve a editar, el BE permite retomar la edicion.
- Cambiar `id` en la URL o el hidden `reqcompraid` no concede permisos: Controller y SP validan que el usuario pueda ver/editar ese REQ.
- Si el usuario cierra navegador/pestana, vuelve atras, pierde conexion o abandona el flujo, el REQ queda en `EDT`.
- Mientras el REQ esta en `EDT`, solo el creador que tomo la edicion puede operar sobre ese formulario.
- La salida normal de `EDT` es:
  - `guardar_borrador`: guardar cambios y pasar a `BRR`;
  - `reenviar_aprobacion`: guardar cambios y volver a `PND`;
  - ruta dedicada `cancelar-edicion`: no guardar cambios y volver a `PND`.
- Si el backend detecta una aprobacion efectiva mientras el REQ estaba en `EDT`, debe bloquear `guardar_borrador`, `reenviar_aprobacion` y `cancelar-edicion`, mostrar mensaje funcional y obligar a recargar desde vista/listado.
- `liberarEdicion` queda reservado para accion controlada/manual, no para eventos automaticos del navegador.
- Mientras el REQ esta en `EDT`, aprobadores no pueden aprobar ni rechazar; el SP debe rechazar la accion y el Controller debe mostrar mensaje funcional y volver al listado de pendientes o a ver.

## 10. Manejo de modales y grillas

Primer corte:

- Cargar combos y grillas auxiliares server-side al renderizar la vista, siguiendo el patron usado en Produccion Leche.
- Buscar/filtrar dentro de modales de items y aprobadores en client-side durante el primer corte.
- Reutilizar `ComprasCatalogosService`.
- No crear endpoints AJAX ni rutas parciales adicionales salvo necesidad concreta durante implementacion.
- Si la grilla de items queda pesada, se podra definir una ruta parcial posterior con contrato especifico.

Redirecciones post-edicion:

- Guardar borrador: `compras-req/ver&id=X`.
- Guardar y enviar o reenviar: `compras-req/ver&id=X`.
- Cancelar cambios desde `EDT`: `compras-req/ver&id=X` con el REQ nuevamente en `PND`.
- Error: volver a `compras_req_editar.php` con datos, `errorMessage` y toast.

## 11. Permisos BE visibles

El BE debe calcular banderas para vistas, sin saltarse validaciones SP:

- `puedeVer`: `true` para filas visibles al usuario.
- `puedeEditar`: estado `BRR` o `RCH`; o `PND` si puede ejecutar `tomar-edicion`; o `EDT` si es retomable por el mismo creador.
- `puedeRetomarEdicion`: estado `EDT` y usuario creador autorizado.
- `puedeAnular`: estado permitido por contrato SP y usuario creador/perfil autorizado segun validacion SP.
- `puedeAprobar`: usuario login coincide con `reqaprobadoridpnd` y estado `PND`.
- `puedeRechazar`: usuario login coincide con `reqaprobadoridpnd` y estado `PND`.

Estas banderas son para UX; la autorizacion final vive en SP.

## 12. Validaciones

Cuando se implemente:

- Ejecutar `php -l` en PHP nuevo o modificado.
- Ejecutar `git diff --check`.
- No ejecutar SQL contra BD real sin autorizacion explicita.
- No llamar ERP/Finnegans desde terminal.

## 13. Cierre documental

- Contrato FE ya existe para el primer corte REQ.
- Layout, modales y mensajes/toasts se implementan segun contrato FE y patrones existentes del proyecto.
