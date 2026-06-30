# Modulo Compras - Contrato BE REQ Aprobados / Pendientes de Compra

> Contrato tecnico Backend para implementar el puente entre REQ aprobado y PreOC.
>
> Este documento define clases, responsabilidades, rutas web, metodos y variables Controller -> View. No contiene codigo ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE.agent`
- `PUDUHUE_FRONT.agent`
- `docs/03_backend.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_req_pendientes_contrato_sp.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `src/Services/ComprasReqService.php`
- `src/Services/ComprasCatalogosService.php`
- `src/Controllers/Web/ComprasReqController.php`

## 2. Alcance BE

El contrato BE de pendientes cubre:

- servicio de pendientes de compra;
- controlador web de pendientes;
- rutas web para listar, ver, anular saldo, cambiar item y seleccionar para PreOC;
- seleccion de pendientes para carrito PreOC sin crear PreOC directamente;
- consumo de SP definidos en `docs/modulo_compras_req_pendientes_contrato_sp.md`;
- uso y extension acotada de `ComprasCatalogosService` para catalogos de items comprador cuando sea necesario.

Fuera de alcance:

- Crear PreOC.
- Pantallas PreOC completas.
- SP presupuestarios.
- Integracion ERP.
- Adjuntos PreOC.
- Ejecutar SQL desde vistas.

## 3. Archivos BE propuestos

| Archivo | Responsabilidad |
|---|---|
| `src/Services/ComprasReqPendientesService.php` | Coordina operaciones de pendientes contra SP de consulta y mantenimiento. |
| `src/Controllers/Web/ComprasReqPendientesController.php` | Orquesta rutas web, contexto de usuario, llamadas a Services y carga de vistas. |
| `src/Services/ComprasCatalogosService.php` | Se puede extender con metodos especificos para items de reemplazo en pendientes. |

Reglas:

- Cargar dependencias con `require_once`, siguiendo patron existente.
- Usar `Database::callSpQuery()` para SP de consulta.
- Usar `Database::callSpMaint()` para SP de mantenimiento.
- No llamar SP desde vistas.
- No leer usuario desde URL para decisiones de seguridad; usar `AuthMiddleware::getUserContext()`.

## 4. `ComprasReqPendientesService`

Responsabilidad: normalizar filtros/payloads y consumir SP de pendientes.

Metodos publicos:

| Metodo | SP / fuente | Uso |
|---|---|---|
| `listarPendientes(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_pendientes_listar_resumen` | Listado principal de pendientes de compra. |
| `consultarPendienteResumen(int $reqaprobadoid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_pendientes_consulta_por_id_resumen` | Cabecera operativa de una linea. |
| `consultarPendienteHistorial(int $reqaprobadoid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_pendientes_consulta_por_id_historial` | Historial de movimientos. |
| `consultarPendienteCambios(int $reqaprobadoid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_pendientes_consulta_por_id_cambios` | Cambios de item. |
| `listarSeleccionPreoc(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_pendientes_preoc_seleccion` | Seleccion de lineas para PreOC. |
| `anularSaldo(int $reqaprobadoid, float $cantidad, string $motivo, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_pendientes_anular_saldo` | Anular cantidad pendiente. |
| `cambiarItem(int $reqaprobadoid, int $invitemidnuevo, string $motivo, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_req_pendientes_cambiar_item` | Cambiar item desde pendientes. |

Metodos auxiliares internos recomendados:

- `normalizarFiltrosListado`
- `normalizarCantidad`
- `normalizarMotivo`
- `consultarPendienteCompleto`

## 5. Extension de `ComprasCatalogosService`

Metodos publicos propuestos solo si el FE los necesita:

| Metodo | Uso | Regla |
|---|---|---|
| `listarItemsCompraPendientesFormGrid(int $reqcompratipo, ?string $filtroBusqueda = null): array` | Modal de cambio de item. | Items activos, comprables, precio mayor a cero y tipo compatible. |

Reglas:

- No duplicar reglas si ya existe helper privado reutilizable para items REQ.
- Mantener metodo publico especifico porque la regla de pendientes puede divergir de REQ.
- No crear SP auxiliar para el modal salvo que una regla transaccional critica lo requiera.

## 6. `ComprasReqPendientesController`

Responsabilidad: manejar rutas web, contexto de usuario, GET/POST, toasts, redirects y carga de vistas.

Rutas aprobadas:

| Ruta | Verbo | Metodo Controller | Vista / salida |
|---|---|---|---|
| `compras-req-pendientes/listar` | GET | `listar(bool $partial = false): void` | `compras_req_pendientes_listar.php` |
| `compras-req-pendientes/ver` | GET | `ver(bool $partial = false): void` | `compras_req_pendientes_ver.php` |
| `compras-req-pendientes/anular-saldo` | POST | `anularSaldoPost(bool $partial = false): void` | redirect/ver o listar |
| `compras-req-pendientes/cambiar-item` | POST | `cambiarItemPost(bool $partial = false): void` | redirect/ver |
| `compras-req-pendientes/seleccionar-preoc` | GET | `seleccionarPreoc(bool $partial = false): void` | `compras_req_pendientes_seleccionar_preoc.php` |

Reglas:

- Toda accion protegida llama `AuthMiddleware::requireAuth()`.
- Obtener contexto con `AuthMiddleware::getUserContext()`.
- Calcular banderas UX sin saltarse validaciones SP.
- Redirigir con `header('Location: ...')` y `exit`.
- Usar toasts para exito/error.
- Validaciones profundas viven en SP.

## 7. Variables Controller -> View

### 7.1 Listado principal

Vista: `compras_req_pendientes_listar.php`

Variables:

- `$pendientes`
- `$meta`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

### 7.2 Ver pendiente

Vista: `compras_req_pendientes_ver.php`

Variables:

- `$pendiente`
- `$historial`
- `$cambios`
- `$itemsCambioRows`
- `$puedeAnularSaldo`
- `$puedeCambiarItem`
- `$partial`
- `$errorMessage` opcional

### 7.3 Seleccion para PreOC

Vista: `compras_req_pendientes_seleccionar_preoc.php`

Variables:

- `$pendientes`
- `$seleccionados`
- `$filtros`
- `$centrosOptions`
- `$partial`
- `$errorMessage` opcional

## 8. Permisos BE visibles

Banderas recomendadas:

- `puedeVer`: usuario con acceso al modulo.
- `puedeAnularSaldo`: usuario comprador y linea con cantidad pendiente mayor a cero.
- `puedeCambiarItem`: usuario comprador, linea con cantidad pendiente mayor a cero y sin historial posterior.
- `puedeSeleccionarPreoc`: usuario comprador y linea con cantidad pendiente mayor a cero.

La autorizacion final vive en SP.

## 9. Redirecciones

- Anulacion exitosa: `?route=compras-req-pendientes/ver&id=X`.
- Cambio de item exitoso: `?route=compras-req-pendientes/ver&id=X`.
- Seleccion para PreOC: agrega lineas al carrito PreOC mediante rutas `compras-preoc/carrito-*`; la creacion de borrador se realiza desde el carrito.

## 10. Validaciones

Cuando se implemente:

- Ejecutar `php -l` en PHP nuevo o modificado.
- Ejecutar `git diff --check`.
- No ejecutar SQL contra BD real sin autorizacion explicita.
- No llamar ERP/Finnegans desde terminal.
