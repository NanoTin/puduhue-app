# CODEX_TASK_CONTEXT.md

> Documento vivo para coordinar el corte actual con Codex.
> Actualizarlo solo cuando ayude a reducir contexto entre chats o modelos.

## Estado

- Fecha: 2026-06-26
- Rama: feature/compras-usuarios-centroscosto
- PR:
- Modelo recomendado:
- Estado: REQ completo implementado en repo; mantenedor `usuarioscentroscosto` implementado en repo; pendiente validacion MariaDB local de SP REQ y `usuarioscentroscosto`

## Objetivo actual

Implementar el mantenedor completo de asignacion Usuario-Centro de Costo (`usuarioscentroscosto`) como corte separado y compatible con REQ ya implementado.

## Alcance permitido

- Documentar estrategia de implementacion vertical por modulo inicial.
- Mantener REQ como primer bloque funcional completo: SP, BE, FE, rutas, aprobaciones, log y analisis presupuestario informativo.
- No ejecutar SQL contra BD real.
- No llamar ERP/Finnegans.

## Fuera de alcance

- Crear DDL no autorizado.
- Implementar PreOC antes de cerrar REQ y pendientes de compra.
- Modificar `.env`, secretos o credenciales.

## Documentos fuente

- `AGENTS.md`
- `PUDUHUE.agent`
- `PUDUHUE_FRONT.agent`, si hay frontend
- `docs/ADR/ADR-INDEX.md`
- `docs/modulo_compras_plan_maestro.md`
- `docs/modulo_compras_req_contrato_sp.md`
- `docs/modulo_compras_req_contrato_be.md`
- `docs/modulo_compras_req_contrato_fe.md`
- `docs/modulo_compras_presupuesto_definitivo.md`
- `docs/modulo_compras_req_estructura.md`
- `docs/modulo_compras_preoc_estructura.md`
- `docs/ADR/modulo-compras/ADR-001-modelo-presupuesto-compras.md`
- `docs/ADR/modulo-compras/ADR-002-compromiso-edicion-preoc.md`
- `docs/ADR/modulo-compras/ADR-003-requerimientos-pendientes-compra.md`
- `database/alter_table/05_modulo_compras_bases.sql`
- `database/alter_table/07_modulo_compras_bases_compartidas.sql`
- `database/alter_table/08_modulo_compras_req.sql`
- `database/alter_table/09_modulo_compras_req_pendientes.sql`
- `database/alter_table/10_modulo_compras_preoc.sql`
- `database/alter_table/11_modulo_compras_presupuesto_sp.sql`
- `database/sp/02_sp_pptocompra.sql`
- `database/sp/02_sp_usuarioscentroscosto.sql`

## Decisiones vigentes

- REQ no mueve presupuesto; solo analiza de forma informativa.
- `EDT` solo nace desde `PND` cuando el usuario creador entra a editar un REQ pendiente.
- Para calculo informativo de "otros REQ pendientes/en edicion", considerar REQ vigentes en `PND` o `EDT`, excluyendo el REQ actual.
- Para REQ en `EDT`, la salida normal queda cerrada asi:
  - `guardar_borrador` guarda cambios y pasa a `BRR`;
  - `reenviar_aprobacion` guarda cambios y vuelve a `PND`;
  - `cancelar_edicion` no guarda cambios y vuelve a `PND`.
- Si el usuario abandona navegador, vuelve atras o pierde conexion en `EDT`, el backend no libera automaticamente; debe permitir retomar la edicion o ejecutar una liberacion controlada/manual.
- Si el usuario cierra navegador/pestana o abandona el flujo, el REQ queda en `EDT`; al volver a editar, el creador retoma la edicion. La liberacion de `EDT` es accion controlada/manual, no automatica por evento de navegador.
- En crear/editar REQ, toda accion que persista datos en tablas debe validar datos minimos y lineas validas; no es una regla amarrada a un estado especifico.
- En crear REQ, `Cancelar` no persiste datos y no crea cabecera vacia en BD.
- En crear/editar REQ, la barra de acciones debe permanecer visible en la parte superior durante scroll vertical, especialmente en pantallas chicas.
- PreOC es el unico flujo que compromete presupuesto.
- Al volver PreOC de `PND` a `BRR` sin aprobaciones, se borran reservas provisionales, sin reversa.
- Movimientos PreOC se registran agrupados por presupuesto afectado y referencian `preocpptoresumen`.
- En movimientos PreOC, `pptocomprareflinea = 'PREOCPPTORESUMEN:<preocpptoresumenid>'`.
- Cada envio de PreOC a aprobacion abre un ciclo presupuestario nuevo en `pptocompregruppomovimiento`, por ejemplo `PREOC:<preocid>:CICLO:<n>`.
- Idempotencia PreOC: mismo tipo, modulo, documento, presupuesto, referencia y ciclo con mismo monto es OK idempotente; con monto distinto es conflicto.
- No crear DDL obligatorio para incremental 11 por ahora.
- SP del incremental 11 sin transacciones internas; PHP controla la transaccion.
- Contrato tecnico de SP incremental 11 cerrado en `docs/modulo_compras_plan_maestro.md` e implementado en `database/alter_table/11_modulo_compras_presupuesto_sp.sql`; validado manualmente en MariaDB local: OK.
- Recalculo de `pptocompra` desde `pptocompratransacciones` como libro oficial; `pptocompramensual` queda como base inicial/mensual.
- PreOC requiere adjuntos: al menos un archivo obligatorio antes de enviar a aprobacion; no bloquea guardar borrador.
- Adjuntos PreOC: no usar `preocadjuntoobligatorio` ni `preocadjuntovig`; la obligatoriedad es regla de envio y el borrado permitido elimina archivo fisico + fila metadata, dejando evidencia en LOG PreOC.
- El tipo de adjunto no debe ser digitado por el usuario; debe resolverse por extension/MIME desde una configuracion reutilizable de tipos permitidos por modulo.
- Guardar extension y MIME cumple objetivos distintos: extension ayuda a nombre/UX/ruta; MIME detectado valida contenido y evita archivos disfrazados cuando `finfo` este disponible.
- La descripcion del adjunto debe ser obligatoria, corta, y usarse como base para generar `preocadjuntonombrearchivo`.
- `preocadjuntonombrearchivo` debe normalizarse a ASCII: sin acentos, sin `ñ`, sin espacios, sin caracteres especiales peligrosos; espacios a `_` y sufijo unico para evitar duplicados.
- Columnas preliminares `preocadjuntos`: `preocadjuntoid`, `preocid`, `preocadjuntolinea`, `preocadjuntotipoid` autocalculado por logica y validado contra maestro, `preocadjuntodescripcion`, `preocadjuntonombreoriginal`, `preocadjuntonombrearchivo`, `preocadjuntoextension`, `preocadjuntomime`, `preocadjuntotamano`, `preocadjuntoruta`, auditoria.
- Maestro transversal propuesto sin autoincremental: `archivoextensionestipos` con PK codigo (`PDF`, `IMG_JPG`, `MSG`, `KMZ`) y `archivoextensionestiposmodulos` con PK compuesta `archivoextensiontipoid + archivomodulocod`.
- Modulos iniciales del maestro de extensiones: `REQ`, `PREOC`, `RET_LECHE`.
- Extensiones iniciales para PreOC: PDF, imagenes, MSG y KMZ.
- Tamano recomendado: 10 MB por archivo y 50 MB total por PreOC.
- La implementacion funcional debe avanzar por flujo vertical: primero REQ completo, despues REQ aprobados / pendientes de compra para PreOC, despues PreOC completo.
- Primer bloque REQ completo incluye SP de listar, crear, editar, consultar por ID, aprobar/rechazar, logs/comentarios cuando corresponda y presupuesto informativo.
- Primer bloque REQ completo incluye BE con Services/Models, control transaccional PHP, permisos y consumo de SP.
- Catalogos de formularios, filtros y modales de Compras se centralizan en `ComprasCatalogosService` con metodos `*FormSelect` / `*FormGrid`; no crear SP auxiliares por cada combo o modal salvo regla transaccional critica.
- Si una asociacion `usuarioscentroscosto` ya existe inactiva para el mismo usuario y centro, el mantenedor la reactiva en vez de crear un duplicado nuevo.
- El mantenedor `usuarioscentroscosto` opera con SP propios de listar, insertar y editar; `editar` resuelve activar, desactivar y marcar default con LOG tecnico.
- Vistas REQ aprobadas: `compras_req_listar.php`, `compras_req_crear.php`, `compras_req_editar.php`, `compras_req_ver.php`, `compras_req_pendientes_aprobacion.php`.
- Post-edicion REQ exitoso redirige a `compras-req/ver&id=X`; filtros en modales de items/aprobadores son client-side en el primer corte.
- Codigo visible REQ cerrado como `REQ-00000001`: prefijo `REQ-` + `LPAD(reqcompraid, 8, '0')`, global, no editable, no reciclable.
- Filtro de listados REQ cerrado como `filtroBusqueda`, aplicado con `LIKE` sobre cadena logica de campos visibles.
- Contrato FE REQ documenta estructura visual, acciones, modales, presupuesto informativo, toasts y validaciones cliente para el primer corte.
- Primer bloque REQ completo incluye FE de listado, crear, editar, ver y listado por aprobar reutilizando la vista de ver para aprobar/rechazar.
- Primer bloque REQ completo incluye actualizacion de rutas para listado, crear, editar, ver y aprobaciones.

## Riesgos y ambiguedades

- Convertir contrato preliminar de adjuntos PreOC a DDL cuando se autorice.
- Mantener alineados los contratos SP/BE/FE de REQ antes de implementar para no inventar parametros/salidas.

## Validaciones esperadas

- `php -l` en PHP modificado o nuevo, si aplica.
- `git diff --check`.

Agregar validaciones especificas del corte cuando corresponda.

## Validaciones ejecutadas en este corte

- `php -l` en:
  - `src/Routes/web.php`
  - `src/Services/ComprasCatalogosService.php`
  - `src/Services/ComprasReqService.php`
  - `src/Controllers/Web/ComprasReqController.php`
  - `apps/web-php/compras_req_listar.php`
  - `apps/web-php/compras_req_crear.php`
  - `apps/web-php/compras_req_editar.php`
  - `apps/web-php/compras_req_ver.php`
  - `apps/web-php/compras_req_pendientes_aprobacion.php`
  - `src/Services/UsuariosCentrosCostoService.php`
  - `src/Controllers/Web/UsuariosCentrosCostoController.php`
  - `apps/web-php/usuarioscentroscosto_listar.php`
  - `apps/web-php/usuarioscentroscosto_crear.php`
- `git diff --check`
- `git status --short`
- `git diff --stat`
- validacion sintactica de `apps/web-php/menu.json` con `php -r`

## Archivos modificados en este corte

- `database/sp/02_sp_compras_req.sql`
- `database/sp/02_sp_usuarioscentroscosto.sql`
- `src/Services/ComprasCatalogosService.php`
- `src/Services/ComprasReqService.php`
- `src/Services/UsuariosCentrosCostoService.php`
- `src/Controllers/Web/ComprasReqController.php`
- `src/Controllers/Web/UsuariosCentrosCostoController.php`
- `src/Routes/web.php`
- `apps/web-php/menu.json`
- `apps/web-php/assets/css/pdh-components.css`
- `apps/web-php/compras_req_listar.php`
- `apps/web-php/compras_req_crear.php`
- `apps/web-php/compras_req_editar.php`
- `apps/web-php/compras_req_ver.php`
- `apps/web-php/compras_req_pendientes_aprobacion.php`
- `apps/web-php/usuarioscentroscosto_listar.php`
- `apps/web-php/usuarioscentroscosto_crear.php`

## Estado de avance

- [x] Contexto leido
- [x] Alcance confirmado
- [x] Contrato SP REQ documentado
- [x] Contrato BE REQ documentado
- [x] Contrato FE REQ documentado
- [x] Implementacion
- [x] Validacion
- [x] Revision
- [x] Cierre

## Handoff para proximo chat

Continuar conversacion sobre:
- validar `database/sp/02_sp_compras_req.sql` en MariaDB local antes de aplicar en BD real.
- validar `database/sp/02_sp_usuarioscentroscosto.sql` en MariaDB local antes de aplicar en BD real.
- probar en navegador el mantenedor `usuarios-centros-costo` con casos de crear, reactivar, desactivar y cambio de default.
- revisar funcionalmente las vistas REQ en navegador local y ajustar UX si aparece algun gap.
- continuar luego con REQ aprobados / pendientes de compra para alimentar PreOC.
