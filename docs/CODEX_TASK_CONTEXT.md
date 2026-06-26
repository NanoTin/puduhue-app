# CODEX_TASK_CONTEXT.md

> Documento vivo para coordinar el corte actual con Codex.
> Actualizarlo solo cuando ayude a reducir contexto entre chats o modelos.

## Estado

- Fecha: 2026-06-26
- Rama: feature/modulo-compras-base
- PR:
- Modelo recomendado:
- Estado: Implementacion SQL 11 realizada; validacion DB pendiente

## Objetivo actual

Implementar el incremental `11_modulo_compras_presupuesto_sp.sql` para SP/servicios presupuestarios del modulo Compras, segun contrato tecnico cerrado.

## Alcance permitido

- Implementar SP presupuestarios del contrato 11.
- Documentar estado actualizado del contrato 11.
- No ejecutar SQL contra BD real.
- No llamar ERP/Finnegans.

## Fuera de alcance

- Crear DDL no autorizado.
- Modificar `.env`, secretos o credenciales.

## Documentos fuente

- `AGENTS.md`
- `PUDUHUE.agent`
- `PUDUHUE_FRONT.agent`, si hay frontend
- `docs/ADR/ADR-INDEX.md`
- `docs/modulo_compras_plan_maestro.md`
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
- Si el usuario abandona navegador, vuelve atras o pierde conexion en `EDT`, el backend debe permitir retomar o liberar la edicion con regla controlada.
- PreOC es el unico flujo que compromete presupuesto.
- Al volver PreOC de `PND` a `BRR` sin aprobaciones, se borran reservas provisionales, sin reversa.
- Movimientos PreOC se registran agrupados por presupuesto afectado y referencian `preocpptoresumen`.
- En movimientos PreOC, `pptocomprareflinea = 'PREOCPPTORESUMEN:<preocpptoresumenid>'`.
- Cada envio de PreOC a aprobacion abre un ciclo presupuestario nuevo en `pptocompregruppomovimiento`, por ejemplo `PREOC:<preocid>:CICLO:<n>`.
- Idempotencia PreOC: mismo tipo, modulo, documento, presupuesto, referencia y ciclo con mismo monto es OK idempotente; con monto distinto es conflicto.
- No crear DDL obligatorio para incremental 11 por ahora.
- SP del incremental 11 sin transacciones internas; PHP controla la transaccion.
- Contrato tecnico de SP incremental 11 cerrado en `docs/modulo_compras_plan_maestro.md` e implementado en `database/alter_table/11_modulo_compras_presupuesto_sp.sql`.
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

## Riesgos y ambiguedades

- Convertir contrato preliminar de adjuntos PreOC a DDL cuando se autorice.
- Validar sintaxis/ejecucion del incremental 11 en MariaDB local antes de integrar backend.

## Validaciones esperadas

- `php -l` en PHP modificado o nuevo, si aplica.
- `git diff --check`.

Agregar validaciones especificas del corte cuando corresponda.

## Estado de avance

- [x] Contexto leido
- [x] Alcance confirmado
- [x] Implementacion
- [ ] Validacion
- [ ] Revision
- [ ] Cierre

## Handoff para proximo chat

Continuar conversacion sobre:
- validar el incremental 11 en MariaDB local cuando se autorice.
- integrar backend REQ/PreOC contra los SP presupuestarios.
