# AGENTS.md - Reglas de trabajo Codex

## Fuente de verdad

- No uses memoria de chats anteriores como fuente principal.
- Reconstruye contexto desde el repositorio.
- Si hay contradicciones entre documentos, reportalas antes de modificar.
- No inventes reglas de negocio, tablas, columnas, stored procedures, endpoints, rutas, modulos ni archivos.

## Seguridad y limites

- No modificar `.env`, secretos, credenciales, tokens ni llaves.
- No ejecutar SQL contra BD real sin autorizacion explicita.
- No ejecutar llamadas reales a ERP/Finnegans desde terminal sin autorizacion explicita.
- No instalar dependencias sin justificar la necesidad y pedir autorizacion.
- No hacer force push.

## Antes de editar

- Revisar rama actual y `git status`.
- Identificar alcance y archivos probablemente involucrados.
- Leer solo los documentos necesarios para la tarea.
- Reportar riesgos, ambiguedades y contradicciones relevantes.

## Implementacion

- Respetar `PUDUHUE.agent` para backend, base de datos e integraciones.
- Respetar `PUDUHUE_FRONT.agent` para vistas y frontend web.
- Mantener cambios pequenos y trazables.
- No refactorizar fuera del alcance.
- No mezclar cambios no relacionados.

## Spark

- Spark no disena arquitectura, reglas de negocio, tablas, columnas, stored procedures, endpoints ni flujos.
- Spark solo ejecuta prompts cerrados preparados previamente por GPT-5.5 Medium o GPT-5.4.
- Todo prompt para Spark debe incluir objetivo exacto, archivos permitidos, archivos prohibidos, fuentes a leer y resultado esperado.
- Si Spark necesita inferir algo no indicado, debe detenerse y reportar bloqueo.

## Validacion

- Ejecutar `php -l` en PHP modificado o nuevo cuando aplique.
- Ejecutar `git diff --check` antes de cerrar cambios.
- Reportar validaciones ejecutadas y no ejecutadas.
