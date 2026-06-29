# Modulo Compras - Contrato BE PreOC

> Contrato tecnico Backend para implementar el corte funcional PreOC.
>
> Este documento define clases, responsabilidades, rutas web, metodos y variables Controller -> View. No contiene codigo ejecutable.

## 1. Fuentes

- `AGENTS.md`
- `PUDUHUE.agent`
- `PUDUHUE_FRONT.agent`
- `docs/03_backend.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/modulo_compras_preoc_contrato_sp.md`
- `docs/modulo_compras_req_pendientes_contrato_be.md`
- `src/Services/ComprasReqService.php`
- `src/Services/ComprasCatalogosService.php`
- `src/Controllers/Web/ComprasReqController.php`

## 2. Alcance BE

El contrato BE PreOC cubre:

- `ComprasPreocService`;
- `ComprasPreocController`;
- extension acotada de `ComprasCatalogosService`;
- rutas web para listar, crear, editar, ver, enviar, aprobar, rechazar, anular y volver a borrador;
- consumo de SP definidos en `docs/modulo_compras_preoc_contrato_sp.md`;
- integracion con SP presupuestarios del incremental 11.

Fuera de alcance:

- API externa.
- POST real a ERP/Finnegans.
- Adjuntos hasta que DDL sea aprobado.
- Cotizaciones.
- Multimoneda.
- Ejecutar SQL desde vistas.

## 3. Archivos BE propuestos

| Archivo | Responsabilidad |
|---|---|
| `src/Services/ComprasPreocService.php` | Coordina operaciones PreOC contra SP de consulta y mantenimiento. |
| `src/Controllers/Web/ComprasPreocController.php` | Orquesta rutas web, contexto de usuario, llamadas a Services y carga de vistas. |
| `src/Services/ComprasCatalogosService.php` | Centraliza catalogos de Compras reutilizables para PreOC. |

## 4. `ComprasPreocService`

Metodos publicos:

| Metodo | SP / fuente | Uso |
|---|---|---|
| `listarPreoc(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_listar_resumen` | Listado principal. |
| `listarPendientesAprobacion(array $filtros, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_listar_pendientes_aprobacion` | PreOC por aprobar del usuario login. |
| `consultarPreocResumen(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_resumen` | Cabecera. |
| `consultarPreocDetalle(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_detalle` | Lineas origen. |
| `consultarPreocItems(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_items` | Items agrupados. |
| `consultarPreocImptos(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_imptos` | Impuestos. |
| `consultarPreocPpto(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_ppto` | Resumen presupuesto. |
| `consultarPreocFirmantes(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_firmantes` | Firmantes. |
| `consultarPreocComentarios(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_comentarios` | Comentarios. |
| `consultarPreocMovimientosPpto(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_consulta_por_id_movimientos_ppto` | Movimientos presupuestarios. |
| `crearPreoc(array $data, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_crear` | Crear borrador o enviar. |
| `editarPreoc(int $preocid, array $data, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_editar` | Guardar/reenviar cambios. |
| `enviarAprobacion(int $preocid, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_enviar_aprobacion` | Enviar `BRR -> PND`. |
| `volverBorrador(int $preocid, ?string $motivo, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_volver_borrador` | Volver `PND -> BRR` sin aprobaciones. |
| `aprobarPreoc(int $preocid, ?string $comentario, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_aprobar` | Aprobar firmante pendiente. |
| `rechazarPreoc(int $preocid, string $comentario, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_rechazar` | Rechazar con comentario obligatorio. |
| `anularPreoc(int $preocid, string $comentario, int $usuarioId, ?string $disp, ?string $ip): array` | `sp_compras_preoc_anular` | Anular cuando corresponda. |

Auxiliares internos recomendados:

- `normalizarFiltrosListado`
- `normalizarCabeceraInput`
- `normalizarDetalleInput`
- `normalizarFirmantesInput`
- `consultarPreocCompleta`

## 5. Extension de `ComprasCatalogosService`

Metodos publicos propuestos:

| Metodo | Uso | Regla |
|---|---|---|
| `listarCompradoresPreocFormSelect(?int $activo = null): array` | Filtro comprador. | Usuarios con `usuariocomprador = 1`, independiente de estado vigente para filtros historicos. |
| `listarUsuariosAprobadoresPreocFormGrid(?string $filtroBusqueda = null, ?array $excluirUsuarioIds = null): array` | Firmantes manuales PreOC. | Usuarios activos/no bloqueados con `usuariopermiteaprobpreoc = 1`. |
| `listarProveedoresPreocFormGrid(?string $filtroBusqueda = null): array` | Busqueda proveedor. | Maestro espejo ERP de proveedores, sin llamar ERP. |
| `listarCondicionesPagoProveedorPreocFormSelect(int $erpproveedorid): array` | Condicion pago por proveedor. | Condiciones ya sincronizadas localmente. |

Reglas:

- No crear SP auxiliares por cada combo o modal salvo regla transaccional critica.
- Mantener metodos publicos especificos por caso de uso.
- No ejecutar llamadas ERP desde catalogos.

## 6. `ComprasPreocController`

Rutas aprobadas:

| Ruta | Verbo | Metodo Controller | Vista / salida |
|---|---|---|---|
| `compras-preoc/listar` | GET | `listar(bool $partial = false): void` | `compras_preoc_listar.php` |
| `compras-preoc/crear` | GET | `crearForm(bool $partial = false): void` | `compras_preoc_crear.php` |
| `compras-preoc/crear` | POST | `crearPost(bool $partial = false): void` | redirect/ver o vuelve a crear |
| `compras-preoc/editar` | GET | `editarForm(bool $partial = false): void` | `compras_preoc_editar.php` |
| `compras-preoc/editar` | POST | `editarPost(bool $partial = false): void` | redirect/ver o vuelve a editar |
| `compras-preoc/ver` | GET | `ver(bool $partial = false): void` | `compras_preoc_ver.php` |
| `compras-preoc/pendientes-aprobacion` | GET | `pendientesAprobacion(bool $partial = false): void` | `compras_preoc_pendientes_aprobacion.php` |
| `compras-preoc/enviar-aprobacion` | POST | `enviarAprobacionPost(bool $partial = false): void` | redirect/ver |
| `compras-preoc/volver-borrador` | POST | `volverBorradorPost(bool $partial = false): void` | redirect/ver |
| `compras-preoc/aprobar` | POST | `aprobarPost(bool $partial = false): void` | redirect/pendientes o ver |
| `compras-preoc/rechazar` | POST | `rechazarPost(bool $partial = false): void` | redirect/pendientes o ver |
| `compras-preoc/anular` | POST | `anularPost(bool $partial = false): void` | redirect/listar o ver |

Reglas:

- Toda accion protegida llama `AuthMiddleware::requireAuth()`.
- Obtener contexto con `AuthMiddleware::getUserContext()`.
- El usuario login no viene por URL.
- Validaciones profundas viven en SP.
- Usar toasts y redirects.

## 7. Variables Controller -> View

### 7.1 Listado

Vista: `compras_preoc_listar.php`

Variables:

- `$preocs`
- `$meta`
- `$filtros`
- `$compradoresOptions`
- `$proveedoresOptions`
- `$partial`
- `$errorMessage` opcional

### 7.2 Crear / editar

Vistas:

- `compras_preoc_crear.php`
- `compras_preoc_editar.php`

Variables:

- `$preoc` o `$formData`
- `$detalle`
- `$itemsAgrupados`
- `$imptos`
- `$pptoResumen`
- `$firmantes`
- `$comentarios`
- `$proveedoresRows`
- `$condicionesPagoOptions`
- `$aprobadoresRows`
- `$errorMessage`
- `$partial`

### 7.3 Ver / aprobar / rechazar

Vista: `compras_preoc_ver.php`

Variables:

- `$preoc`
- `$detalle`
- `$itemsAgrupados`
- `$imptos`
- `$pptoResumen`
- `$firmantes`
- `$comentarios`
- `$movimientosPpto`
- `$puedeEditar`
- `$puedeEnviarAprobacion`
- `$puedeVolverBorrador`
- `$puedeAprobar`
- `$puedeRechazar`
- `$puedeAnular`
- `$errorMessage`
- `$partial`

### 7.4 Pendientes de aprobacion

Vista: `compras_preoc_pendientes_aprobacion.php`

Variables:

- `$preocs`
- `$meta`
- `$filtros`
- `$proveedoresOptions`
- `$partial`
- `$errorMessage` opcional

## 8. Permisos BE visibles

Banderas UX:

- `puedeEditar`: comprador creador, estado editable y sin aprobaciones.
- `puedeEnviarAprobacion`: comprador creador, estado `BRR`, detalle valido, precios completos y adjuntos disponibles cuando exista DDL.
- `puedeVolverBorrador`: estado `PND` sin aprobaciones.
- `puedeAprobar`: usuario login igual a `preocaprobadoridpnd`, estado `PND`.
- `puedeRechazar`: usuario login igual a `preocaprobadoridpnd`, estado `PND`.
- `puedeAnular`: estado permitido y permiso correspondiente; no salta la regla de firmantes aprobados.

La autorizacion final vive en SP.

## 9. Adjuntos

El BE debe contemplar la regla funcional de adjunto obligatorio al enviar a aprobacion, pero la implementacion queda bloqueada hasta aprobar DDL de adjuntos.

Si no existe DDL aprobado:

- no implementar carga real de adjuntos;
- no permitir cerrar `BRR -> PND` si el alcance exige adjunto obligatorio;
- reportar bloqueo antes de implementar envio.

## 10. Validaciones

Cuando se implemente:

- Ejecutar `php -l` en PHP nuevo o modificado.
- Ejecutar `git diff --check`.
- No ejecutar SQL contra BD real sin autorizacion explicita.
- No llamar ERP/Finnegans desde terminal.
