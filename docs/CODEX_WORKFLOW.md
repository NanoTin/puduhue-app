# CODEX_WORKFLOW.md

## 1. Objetivo

Definir como trabajar con Codex en Puduhue App Web reduciendo consumo de tokens, manteniendo trazabilidad y evitando cambios no autorizados.

## 2. Uso recomendado de modelos

### GPT-5.5 Medium

Usar para:

- arquitectura y decisiones criticas;
- auditoria de codigo o documentacion;
- contradicciones entre documentos;
- revision de PR, diff o cambios de alto impacto;
- diseno de prompts cerrados para Spark.

### GPT-5.4

Usar para:

- desarrollo normal con alcance claro;
- documentacion tecnica;
- implementacion backend, frontend o DB siguiendo patrones existentes;
- preparacion de tareas para modelos mas economicos.

### GPT-5.4 Mini

Usar para:

- reentrada liviana;
- resumen de estado;
- changelog;
- clasificacion de pendientes;
- documentacion breve.

### GPT-5.3 Codex Spark

Usar solo para:

- ejecucion acotada;
- cambios mecanicos;
- tareas con archivos permitidos explicitamente;
- validaciones simples.

Spark no disena ni decide arquitectura, reglas de negocio, tablas, columnas, stored procedures, endpoints, flujos ni modulos.

## 3. Tipos de chat

- Reentrada liviana: reconstruye contexto minimo y no modifica archivos.
- Analisis/documentacion: define alcance, decisiones, riesgos y fuentes.
- Ejecucion: implementa un alcance aprobado.
- Revision/auditoria: revisa diff, riesgos, regresiones y pruebas.
- Cierre/documentacion: actualiza changelog, contexto de tarea y handoff.

## 4. Lectura minima por chat

Siempre considerar:

- `AGENTS.md`
- `docs/CODEX_TASK_CONTEXT.md`, si existe y esta actualizado
- `docs/ADR/ADR-INDEX.md`

Segun tarea:

- Backend, DB o integraciones: `PUDUHUE.agent`, `docs/03_backend.md`, `docs/04_db.md`
- Vistas o frontend web: `PUDUHUE_FRONT.agent` y vistas similares existentes
- API externa: `docs/api-externa/README.md`
- Compras: documentos especificos del modulo de compras
- Deploy o cambios de BD: `docs/playbooks/db_changes.md` y `docs/playbooks/deploy.md`
- Testing y ambientes: `docs/06_testing.md`

## 5. GitHub, ramas y commits

- Verificar rama y estado local antes de editar.
- No hacer commit, push ni PR salvo instruccion explicita.
- No cambiar estado de PR draft sin instruccion explicita.
- No hacer force push.
- Si hay divergencia remota, resolver con cuidado y reportar el plan.
- Registrar cambios relevantes en `CHANGELOG.md` cuando corresponda.

## 6. Uso de `docs/CODEX_TASK_CONTEXT.md`

Usar este documento como contexto vivo del corte actual:

- objetivo;
- alcance permitido;
- fuera de alcance;
- documentos fuente;
- decisiones vigentes;
- riesgos;
- validaciones esperadas;
- handoff para el proximo chat.

Este archivo reemplaza prompts gigantes con rama, PR, checklist y estado temporal.

## 7. Cierre de tarea

Toda tarea debe cerrar con:

- archivos modificados;
- validaciones ejecutadas;
- validaciones no ejecutadas y motivo;
- riesgos pendientes;
- documentacion actualizada o pendiente;
- siguiente corte recomendado, si aplica.
