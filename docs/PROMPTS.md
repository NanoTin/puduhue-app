# PROMPTS.md

Biblioteca breve de prompts reutilizables para trabajar con Codex en Puduhue App Web.

## 1. Reentrada liviana

Usar al cambiar de modelo, abrir un chat nuevo o retomar el proyecto despues de varios dias.

```text
Estoy retomando Puduhue App Web en una conversacion nueva.

No dependas de chats anteriores. Reconstruye solo el estado minimo desde el repositorio.

Lee en este orden:
1. AGENTS.md
2. docs/CODEX_TASK_CONTEXT.md, si existe
3. docs/ADR/ADR-INDEX.md
4. CHANGELOG.md

Despues identifica, sin leer todavia, que documentos adicionales podrian ser necesarios segun el estado del proyecto y los pendientes.

Responde solo con:
1. fase o estado actual del proyecto;
2. resumen corto del objetivo y alcance vigente;
3. decisiones vigentes mas importantes y ADR relacionados;
4. pendientes inmediatos;
5. preguntas abiertas o supuestos riesgosos;
6. documentos que recomendarias leer despues, agrupados por motivo;
7. si el proyecto parece listo o no para recibir una tarea concreta.

No implementes todavia.
No instales dependencias.
No cambies archivos.
No leas documentos grandes adicionales salvo que sean imprescindibles para responder este diagnostico.
```

## 2. Analisis o documentacion

```text
Actua como analista tecnico del repo Puduhue App Web. No modifiques archivos.

No uses memoria de chats anteriores como fuente principal. Usa el repositorio.

Lee primero:
- AGENTS.md
- docs/CODEX_TASK_CONTEXT.md, si existe
- docs/ADR/ADR-INDEX.md
- documentos especificos que correspondan a la tarea

Objetivo:
[DESCRIBIR OBJETIVO]

Entrega:
1. documentos fuente usados;
2. alcance propuesto;
3. archivos probablemente involucrados;
4. contradicciones o pendientes;
5. riesgos;
6. plan de trabajo.

No inventes reglas, tablas, columnas, endpoints, stored procedures ni modulos.
```

## 3. Ejecucion de codigo

```text
Actua como implementador del repo Puduhue App Web.

Antes de editar:
1. Lee AGENTS.md.
2. Lee docs/CODEX_TASK_CONTEXT.md, si existe.
3. Lee PUDUHUE.agent y, si hay vistas, PUDUHUE_FRONT.agent.
4. Lee solo los archivos relacionados con esta tarea.
5. Confirma rama y git status.

Objetivo aprobado:
[OBJETIVO]

Archivos permitidos:
[LISTA]

Archivos prohibidos:
[LISTA]

Restricciones:
- no refactor fuera del alcance;
- no cambios fuera de alcance;
- no inventar reglas, tablas, columnas, endpoints, stored procedures ni rutas;
- no tocar .env ni secretos;
- no ejecutar SQL ni llamadas ERP reales sin autorizacion.

Implementa, valida con comandos razonables y reporta:
- archivos modificados;
- pruebas realizadas;
- riesgos pendientes.
```

## 4. Spark bajo riesgo

```text
Modo Spark acotado.

No disenes ni decidas. No inventes tablas, columnas, stored procedures, endpoints, rutas, reglas de negocio, flujos ni archivos.

Objetivo exacto:
[OBJETIVO]

Puedes modificar unicamente:
[ARCHIVOS PERMITIDOS]

No puedes modificar:
[ARCHIVOS PROHIBIDOS]

Lee antes:
[ARCHIVOS FUENTE]

Resultado esperado:
[RESULTADO]

Validacion:
[COMANDOS]

Si falta informacion, aparece una contradiccion o necesitas tocar un archivo no listado, detente y reporta bloqueo.
```

## 5. Revision o auditoria

```text
Actua como revisor senior. No modifiques archivos.

Revisa el diff actual contra el objetivo:
[OBJETIVO]

Prioriza:
1. bugs;
2. regresiones;
3. seguridad;
4. inconsistencias con AGENTS.md, PUDUHUE.agent o PUDUHUE_FRONT.agent;
5. cambios fuera de alcance;
6. pruebas faltantes;
7. documentacion pendiente.

Entrega hallazgos con archivo y linea cuando sea posible. Si no hay hallazgos, dilo claramente.
No propongas refactors fuera del alcance.
```

## 6. Cierre de tarea

```text
Actua como encargado de cierre. No cambies codigo funcional.

Con base en el diff y la tarea:
[OBJETIVO]

Actualiza solo documentacion aprobada:
- CHANGELOG.md, si corresponde;
- docs/CODEX_TASK_CONTEXT.md;
- ADR, si hubo decision relevante.

Entrega resumen final:
1. que cambio;
2. que se valido;
3. que queda pendiente;
4. riesgos residuales;
5. siguiente corte recomendado.

No inventes estado de pruebas no ejecutadas.
```
