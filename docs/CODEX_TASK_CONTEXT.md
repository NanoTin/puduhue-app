# CODEX_TASK_CONTEXT.md

> Documento vivo para coordinar el corte actual con Codex.
> Actualizarlo solo cuando ayude a reducir contexto entre chats o modelos.

## Estado

- Fecha: 2026-06-26
- Rama: feature/modulo-compras-base
- PR:
- Modelo recomendado:
- Estado: SQL 07-11 validado; siguiente corte REQ completo

## Objetivo actual

Preparar el siguiente corte funcional del modulo Compras: implementar REQ completo de punta a punta antes de avanzar a REQ aprobados / pendientes de compra y PreOC.

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

## Decisiones vigentes

- REQ no mueve presupuesto; solo analiza de forma informativa.
- `EDT` solo nace desde `PND` cuando el usuario creador entra a editar un REQ pendiente.
- Para calculo informativo de "otros REQ pendientes/en edicion", considerar REQ vigentes en `PND` o `EDT`, excluyendo el REQ actual.
- Para REQ en `EDT`, la salida normal debe ser explicita: guardar/reenviar o cancelar edicion.
- Si el usuario abandona navegador, vuelve atras o pierde conexion en `EDT`, el backend no libera automaticamente; debe permitir retomar la edicion o ejecutar una liberacion controlada/manual.
- Si el usuario cierra navegador/pestana o abandona el flujo, el REQ queda en `EDT`; al volver a editar, el creador retoma la edicion. La liberacion de `EDT` es accion controlada/manual, no automatica por evento de navegador.
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

## Estado de avance

- [x] Contexto leido
- [x] Alcance confirmado
- [x] Contrato SP REQ documentado
- [x] Contrato BE REQ documentado
- [x] Contrato FE REQ documentado
- [ ] Implementacion
- [ ] Validacion
- [ ] Revision
- [ ] Cierre

## Handoff para proximo chat

Continuar conversacion sobre:
- revisar y aprobar `docs/modulo_compras_req_contrato_sp.md`.
- revisar y aprobar `docs/modulo_compras_req_contrato_be.md`.
- revisar y aprobar `docs/modulo_compras_req_contrato_fe.md`.
- implementar REQ completo en orden: SP, BE, FE y rutas.
