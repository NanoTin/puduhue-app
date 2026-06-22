# Plan de Mejoras — Auditoria de Codigo

> Fuente: `docs/code_audit_proposals.md`.
> Alcance validado: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2, 2.3, 4.1, 4.2, 5.1, 5.2, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6 y 7.1.

## Criterio de trabajo

Cada punto se ejecuta con tres agentes:

- **Agente revisor**: verifica si el hallazgo todavia aplica, detecta correcciones no marcadas y deja el alcance exacto.
- **Agente implementador**: aplica la mejora o deja el cambio listo por fases cuando el riesgo sea alto.
- **Agente validador**: prueba, revisa regresiones y confirma criterios de aceptacion.

Estados usados:

- **Vigente**: el problema sigue presente.
- **Parcial**: existe infraestructura o avance, pero falta adopcion completa.
- **Probablemente resuelto**: el codigo actual parece cubrir el hallazgo; requiere validacion final.
- **Por decidir**: requiere decision funcional antes de modificar.

## Resumen de aplicabilidad actual

| Punto | Estado | Observacion |
|---|---|---|
| 1.1 | Vigente | No se encontro helper ni token CSRF en formularios/controladores. |
| 1.2 | Vigente | `apps/web-php/login.php` sigue procesando POST, reCAPTCHA, sesion y rate limit. |
| 1.3 | Parcial | `head.php` usa `AuthMiddleware::getUserContext()`, pero `menu.php`, `footer.php`, `index.php` y router aun leen/escriben `$_SESSION`. |
| 1.4 | Vigente | `login.php` conserva `$_POST` en la vista para repoblar usuario/checkbox. |
| 2.1 | Parcial | Existe `assets/js/toast.js`, pero quedan `alert()` en `prodleche_*`, `suplanimal_*` y `usuarios_crear.php`. |
| 2.2 | Parcial | Existe `partials/modal_confirm.php` y `assets/js/confirm-modal.js`, pero muchos listados siguen usando `confirm()`. |
| 2.3 | Parcial | `footer.php` muestra `$_SESSION['toast']` y varios controllers lo usan; falta documentar y homogeneizar. |
| 4.1 | Probablemente resuelto | `head.php` tiene boton mobile, backdrop, CSS responsive y clases `sidebar-open/sidebar-collapsed`. |
| 4.2 | Vigente | Formularios transaccionales usan tablas inline y requieren validacion mobile por formulario. |
| 5.1 | Vigente | Persisten estilos inline en vistas y CSS embebido extenso en `head.php`. |
| 5.2 | Vigente | Listados usan mayormente `.container mt-4`; formularios complejos usan variantes `container-fluid px-3 py-3`. |
| 6.1 | Vigente | `ProdlecheService` y `SuplanimalService` duplican token ERP, cURL y manejo Finnegans. No existe `FinnegansClient.php`. |
| 6.2 | Vigente | `usuariosempresas_editar.php` y `usuariosfundos_editar.php` siguen casi vacios. |
| 6.3 | Vigente | Siguen `apps/web-php/prodleche_crear_bak_20251215.php` y `apps/web-php/dashboard copy.php`. |
| 6.4 | Vigente | `src/Core/*` sigue presente; busqueda activa apunta a `src/Config/*`, salvo referencias documentales y archivos legacy mismos. |
| 6.5 | Vigente | `AuthController.php` solo implementa logout. |
| 6.6 | Probablemente resuelto | No se encontro carpeta `lib/` en el arbol actual. |
| 7.1 | Vigente | Persisten archivos temporales y artefactos de cliente en la raiz del proyecto. |

## Dependencias recomendadas

1. Ejecutar primero **1.2 + 1.4 + 6.5**, porque concentran la migracion del login.
2. Ejecutar **1.1** despues de estabilizar login o en paralelo si se implementa como middleware/helper transversal.
3. Ejecutar **2.1 + 2.3** juntos, y luego **2.2**.
4. Ejecutar **6.2, 6.3, 6.4, 6.6 y 7.1** como sprint de limpieza de bajo riesgo, con validacion de rutas.
5. Ejecutar **4.2, 5.1 y 5.2** por fases para evitar cambios visuales masivos.
6. Ejecutar **6.1** aislado, porque toca integracion ERP y requiere mocks o ambiente controlado.

---

## 1.1. CSRF en formularios POST

**Estado**: Vigente.

**Agente revisor**

- Inventariar formularios `method="POST"` y endpoints POST del router: `crearPost`, `editarPost`, `anularPost`, `eliminarPost`, `syncPost`, `cargaMasivaPost`, `cambiarEmpresaPost`, `generarTokenApiPost` y `export_excel.php`.
- Separar POST mutativos de POST no mutativos. Los mutativos deben exigir CSRF; exportacion puede evaluarse aparte.
- Confirmar que no exista helper equivalente antes de crear uno nuevo.

**Agente implementador**

- Crear `src/Helpers/CsrfHelper.php` con `generate(string $context = 'default')`, `validate(?string $token, string $context = 'default')` y `input(string $context = 'default')`.
- Agregar token en formularios POST de vistas.
- Validar token al inicio de cada metodo POST mutativo, idealmente con un helper compartido en controllers para evitar duplicacion.
- Para acciones AJAX, aceptar token por campo `_csrf` o header `X-CSRF-Token`.
- Al fallar, responder 403 o redirect con toast `danger` segun el tipo de request.

**Agente validador**

- Intentar POST sin `_csrf`: debe fallar con 403 o mensaje controlado.
- Intentar POST con token invalido o de otro contexto: debe fallar.
- Intentar flujo normal crear/editar/anular/sync con token valido: debe funcionar.
- Verificar que el token rote al regenerar sesion de login.

**Criterios de aceptacion**

- No quedan formularios mutativos sin token CSRF.
- No quedan metodos POST mutativos sin validacion CSRF.
- Los errores CSRF no exponen stack trace ni datos sensibles.

## 1.2. Login fuera de la vista

**Estado**: Vigente.

**Agente revisor**

- Levantar comportamiento actual de `apps/web-php/login.php`: cookies seguras, reCAPTCHA, rate limit, normalizacion de RUT, validacion de usuario, bloqueo, defaults, `remember_user`, regeneracion de sesion y logs.
- Identificar dependencias actuales: `Env`, `Database`, `Logger`, `UsuariosService`, `AuthService`.
- Confirmar rutas esperadas: `login.php` como entrada publica y `?route=auth/logout` para logout.

**Agente implementador**

- Mover funciones `normalizeUsernameInput()` y `checkAndRegisterRateLimit()` a `AuthService` o `AuthController`, segun responsabilidad.
- Implementar `AuthController::loginForm()` y `AuthController::loginPost()` o un metodo equivalente invocado desde `login.php`.
- Dejar `login.php` como vista/controlador delgado: obtiene un view model y renderiza formulario/mensajes.
- Mantener compatibilidad con action actual vacio o definir action explicito seguro.
- Preservar logs y mensajes genericos para no filtrar motivo exacto de fallo.

**Agente validador**

- Probar login correcto, password incorrecta, usuario inexistente, usuario inactivo/bloqueado y falta de defaults.
- Probar reCAPTCHA habilitado y deshabilitado con `.env.local`.
- Probar `remember_user`: cookie creada y eliminada segun checkbox.
- Probar que una sesion activa redirige a dashboard.

**Criterios de aceptacion**

- `login.php` no contiene reglas de autenticacion ni consultas directas.
- La logica testeable queda en `AuthController`/`AuthService`.
- El comportamiento visible del login no cambia salvo correcciones intencionales.

## 1.3. Acceso directo a `$_SESSION` en vistas

**Estado**: Parcial.

**Agente revisor**

- Revisar usos actuales de `$_SESSION` en `menu.php`, `footer.php`, `index.php`, `src/Routes/web.php` y vistas.
- Clasificar usos permitidos de infraestructura contra usos de presentacion. La sesion puede vivir en middleware/router/controller, no en vistas.
- Confirmar que `AuthMiddleware::getUserContext()` ya cubre datos de usuario requeridos.

**Agente implementador**

- Pasar `perfilId` al menu desde `AuthMiddleware::getUserContext()` o desde el front controller antes de requerir `menu.php`.
- Extraer lectura/limpieza de toast a un helper tipo `FlashMessageHelper::pullToast()` o a variables preparadas antes de incluir `footer.php`.
- Mantener `index.php` y router como capa de orquestacion, pero evitar que vistas lean `$_SESSION`.
- Documentar excepciones si quedan usos de infraestructura inevitables.

**Agente validador**

- Navegar menu con usuarios de distintos perfiles.
- Generar toasts desde controllers y confirmar que se muestran una sola vez.
- Verificar logout/login y cambio de empresa.

**Criterios de aceptacion**

- Vistas compartidas no leen ni escriben `$_SESSION` directamente.
- El contexto de usuario llega por variables explicitas.
- Los mensajes flash siguen funcionando.

## 1.4. `$_POST` directo en `login.php`

**Estado**: Vigente.

**Agente revisor**

- Identificar todos los valores de formulario que se repueblan desde `$_POST`: usuario y checkbox `remember_user`.
- Validar que el valor recordado por cookie no tenga prioridad incorrecta sobre el POST fallido.

**Agente implementador**

- Resolverlo dentro de 1.2 con un view model: `usernameValue`, `rememberUserChecked`, `toastMessage`, `toastType`, config reCAPTCHA.
- Escapar solo al renderizar, no durante la preparacion del view model.

**Agente validador**

- Fallar login y confirmar que el usuario ingresado se conserva.
- Confirmar que password nunca se repuebla.
- Confirmar que no quedan lecturas `$_POST` en la vista.

**Criterios de aceptacion**

- `login.php` no accede a `$_POST`.
- El formulario conserva el comportamiento actual de repoblado seguro.

## 2.1. Migrar `alert()` a toasts

**Estado**: Parcial.

**Agente revisor**

- Confirmar instancias actuales de `alert()` en `prodleche_crear.php`, `prodleche_editar.php`, `suplanimal_crear.php`, `suplanimal_editar.php` y `usuarios_crear.php`.
- Revisar si cada alerta es validacion bloqueante, warning informativo o error.
- Confirmar que `footer.php` carga `assets/js/toast.js` en las paginas afectadas.

**Agente implementador**

- Reemplazar `alert(message)` por `ToastManager.show(message, type)` usando `danger` o `warning` segun corresponda.
- Eliminar funciones locales `showToast()` que caen a `alert()` y reutilizar `ToastManager`.
- Mantener bloqueo de submit donde corresponda; el toast reemplaza el feedback, no la validacion.

**Agente validador**

- Ejecutar validaciones client-side de produccion leche, suplementacion animal y usuarios.
- Confirmar que el toast aparece, no bloquea el navegador y el formulario no se envia cuando hay error.
- Confirmar que no queda `alert(` en `apps/web-php`.

**Criterios de aceptacion**

- Cero `alert()` nativos en vistas de la app.
- Feedback visual consistente con Bootstrap 5.

## 2.2. Migrar `confirm()` a modal reusable

**Estado**: Parcial.

**Agente revisor**

- Inventariar `confirm()` restantes en listados y cargas Excel.
- Revisar `assets/js/confirm-modal.js`: hoy intercepta solo formularios con `data-confirm="1"`.
- Confirmar si sincronizaciones ERP deben compartir modal con anulaciones o usar mensaje/titulo propio.

**Agente implementador**

- Agregar `data-confirm="1"` y `data-confirm-message="..."` a formularios destructivos o sensibles.
- Incluir `partials/modal_confirm.php` y `assets/js/confirm-modal.js` en layouts o en los listados afectados.
- Reemplazar confirmaciones de carga Excel por el mismo mecanismo o por boton que dispare modal.
- Remover `onclick="return confirm(...)"`.

**Agente validador**

- Probar cancelar y confirmar en anulaciones, eliminaciones, sync ERP y cargas masivas.
- Confirmar que no se duplica el submit al aceptar.
- Confirmar que no queda `confirm(` salvo usos justificados como `beforeunload`.

**Criterios de aceptacion**

- Las acciones destructivas usan modal Bootstrap.
- El modal conserva mensaje especifico por accion.

## 2.3. Uso consistente de toasts

**Estado**: Parcial.

**Agente revisor**

- Revisar controllers que ya setean `$_SESSION['toast']`.
- Detectar controllers o flujos que todavia muestran errores inline, `alert()` o redirects sin feedback.
- Revisar el mapeo de tipos: `success`, `danger`, `warning`, `info`.

**Agente implementador**

- Crear o documentar helper `FlashMessageHelper::toast($type, $message)`.
- Migrar controllers a ese helper para evitar arrays escritos a mano.
- Documentar en `docs/playbooks/new_crud_module.md` el patron de toast.

**Agente validador**

- Probar crear/editar/anular en modulos representativos.
- Confirmar que el toast se muestra una vez y desaparece despues de refresh.
- Confirmar fallback si `ToastManager` no carga.

**Criterios de aceptacion**

- Patron documentado.
- Controllers usan una API consistente para mensajes flash.

## 4.1. Sidebar responsive mobile

**Estado**: Probablemente resuelto.

**Agente revisor**

- Validar visualmente `head.php` y `menu.php` en viewport menor a 768px.
- Confirmar comportamiento de `mobileMenuBtn`, `sidebarBackdrop`, cierre por link y scroll del sidebar.
- Revisar que el contenido principal no quede desplazado ni cubierto.

**Agente implementador**

- Si falla la validacion, ajustar solo CSS/JS existente en `head.php`/layout.
- Extraer CSS/JS a assets dedicados si se aborda junto con 5.1.

**Agente validador**

- Probar 375px, 768px y desktop.
- Probar abrir/cerrar, navegar a una ruta, usar submenu y hacer logout.
- Confirmar que no hay overlap de header, sidebar y contenido.

**Criterios de aceptacion**

- En mobile el menu se abre/cierra con boton y backdrop.
- En desktop se mantiene colapsable sin romper flyouts.

## 4.2. Formularios transaccionales responsive

**Estado**: Vigente.

**Agente revisor**

- Revisar `prodleche_crear.php`, `suplanimal_crear.php`, `retiroleche_crear.php` y sus ediciones equivalentes.
- Detectar tablas detalle que no funcionan bien bajo 768px.
- Priorizar por uso real: produccion leche, suplementacion animal, retiro leche.

**Agente implementador**

- Ajustar grids con clases Bootstrap `col-12 col-md-6 col-lg-4` donde falten.
- Para detalles editables, evaluar `table-responsive` como paso minimo y card-stack mobile como mejora superior.
- Mantener IDs y nombres de inputs para no romper controllers.
- Ejecutar por formulario, no como cambio masivo.

**Agente validador**

- Probar crear/editar con datos reales en mobile y desktop.
- Confirmar que se pueden agregar/eliminar lineas detalle.
- Confirmar que validaciones client-side siguen operando.

**Criterios de aceptacion**

- Formularios principales son usables en 375px sin scroll horizontal global.
- Las tablas detalle no ocultan acciones ni inputs criticos.

## 5.1. CSS inline y CSS embebido

**Estado**: Vigente.

**Agente revisor**

- Inventariar `style="..."` y bloques `<style>` actuales.
- Separar estilos estructurales de estilos puntuales de reporte.
- Definir convencion `pdh-*` y archivos destino: `assets/css/puduhue.css`, `assets/css/layout.css` o equivalentes.

**Agente implementador**

- Extraer primero estilos de layout global desde `head.php`.
- Migrar estilos inline repetidos: anchos de columna, alineacion numerica, logo, gaps y campos ocultos.
- Dejar temporalmente estilos muy especificos de reportes si requieren revision visual propia.
- Cargar CSS nuevo desde `head.php`.

**Agente validador**

- Comparar pantallas antes/despues en dashboard, listados, formularios simples, formularios complejos y reporte leche.
- Confirmar que no aumenta el layout shift.
- Ejecutar `rg 'style=\"|<style' apps/web-php` y revisar remanentes justificados.

**Criterios de aceptacion**

- Estilos globales viven en assets CSS.
- Inline styles restantes quedan justificados o planificados por excepcion.

## 5.2. Estandarizar contenedores

**Estado**: Vigente.

**Agente revisor**

- Inventariar contenedores actuales por tipo: listados, formularios simples, formularios complejos, dashboard y reportes.
- Confirmar estandar propuesto: listados `container-fluid px-4`, formularios `container mt-3`.
- Decidir excepciones: formularios transaccionales y reportes pueden requerir `container-fluid`.

**Agente implementador**

- Aplicar cambio por grupo de vistas, empezando por listados.
- Evitar mezclar con refactor funcional.
- Ajustar espaciados en CSS compartido si aparecen diferencias visuales.

**Agente validador**

- Revisar listados en desktop y mobile.
- Confirmar que filtros, botones exportar y tablas quedan alineados.
- Verificar formularios simples y transaccionales.

**Criterios de aceptacion**

- Contenedores siguen una regla documentada.
- No hay pantallas con ancho inconsistente sin excepcion declarada.

## 6.1. Centralizar cliente Finnegans

**Estado**: Vigente.

**Agente revisor**

- Comparar metodos duplicados en `ProdlecheService.php` y `SuplanimalService.php`: token activo, renovacion, guardado, POST y parseo de respuesta.
- Confirmar contratos de payload/respuesta para produccion leche y suplementacion.
- Verificar que no existe `src/api-external/FinnegansClient.php`; el plan debe crearlo.

**Agente implementador**

- Crear `src/api-external/FinnegansClient.php` o `src/ApiExternal/FinnegansClient.php` segun convencion final.
- Centralizar:
  - lectura de token activo;
  - renovacion contra `ERP_AUTH_URL`;
  - persistencia via `sp_erptokenactivo_insertar`;
  - POST JSON con timeout;
  - errores cURL/HTTP;
  - reintento si el token expira.
- Hacer que `ProdlecheService` y `SuplanimalService` construyan payload y deleguen envio.

**Agente validador**

- Probar sync exitoso en ambiente controlado.
- Probar token expirado y renovacion.
- Probar error HTTP y error de conexion.
- Confirmar que logs y estados `PND/SYNC/ERR` se mantienen.

**Criterios de aceptacion**

- No queda logica cURL/token duplicada entre ambos servicios.
- La integracion conserva payloads actuales.

## 6.2. Archivos de edicion vacios

**Estado**: Vigente.

**Agente revisor**

- Confirmar si `usuariosempresas` y `usuariosfundos` son relaciones N:M solo crear/eliminar.
- Revisar router/menu para saber si existen rutas de editar visibles o permitidas.
- Consultar reglas de negocio si se necesita cambiar defaults o atributos de la relacion.

**Agente implementador**

- Si no aplica editar: eliminar archivos vacios y quitar rutas/menu que apunten a editar.
- Si aplica editar: implementar vistas y metodos `editarForm`/`editarPost`.
- Documentar decision en ADR o playbook si afecta generador CRUD.

**Agente validador**

- Navegar listados y acciones disponibles.
- Confirmar que no hay links 404 a editar.
- Ejecutar busqueda de referencias a los archivos eliminados o implementados.

**Criterios de aceptacion**

- No quedan archivos placeholder sin funcionalidad.
- El menu/router refleja la decision funcional.

## 6.3. Archivos backup en `apps/web-php`

**Estado**: Vigente.

**Agente revisor**

- Confirmar diferencias relevantes entre `prodleche_crear_bak_20251215.php` y `prodleche_crear.php`.
- Confirmar si `dashboard copy.php` contiene informacion no migrada.

**Agente implementador**

- Si no hay informacion unica necesaria, eliminar ambos archivos.
- Si hay contenido util, moverlo a documentacion antes de eliminar.
- No tocar archivos activos.

**Agente validador**

- Ejecutar `rg 'prodleche_crear_bak_20251215|dashboard copy'`.
- Verificar que el router y menu no referencian backups.

**Criterios de aceptacion**

- No quedan backups PHP servibles dentro de `apps/web-php`.

## 6.4. Retirar `src/Core/*` legacy

**Estado**: Vigente.

**Agente revisor**

- Confirmar que runtime usa `src/Config/Database.php` y `src/Config/Env.php`.
- Excluir referencias documentales de la decision tecnica.
- Revisar composer/autoload y requires manuales.

**Agente implementador**

- Opcion recomendada: mover a `src/_deprecated/Core/` solo si se quiere conservar temporalmente.
- Opcion final: eliminar archivos legacy y actualizar docs que aun los mencionan como presentes.
- Si se elimina, registrar en changelog/ADR.

**Agente validador**

- Ejecutar busqueda de `src/Core`, `Core/Database`, `DBConfig`.
- Probar login, listado y endpoint API simple.
- Confirmar que no hay fatal error por clases duplicadas.

**Criterios de aceptacion**

- Runtime no contiene capa DB duplicada activa.
- Documentacion refleja el estado real.

## 6.5. Completar `AuthController`

**Estado**: Vigente.

**Agente revisor**

- Revisar responsabilidades actuales de `AuthController` y `AuthService`.
- Alinear con 1.2 para evitar duplicar logica.

**Agente implementador**

- Implementar `loginPost()` y, si corresponde, `loginForm()` en `AuthController`.
- Mantener `logout()` existente.
- Concentrar redirecciones y mensajes de login en controller.

**Agente validador**

- Ejecutar la matriz de login de 1.2.
- Confirmar que logout sigue funcionando por `?route=auth/logout`.

**Criterios de aceptacion**

- `AuthController` deja de ser un placeholder.
- La autenticacion web queda encapsulada en controller/service.

## 6.6. Carpeta `lib/` vacia

**Estado**: Probablemente resuelto.

**Agente revisor**

- Confirmar con `find lib -maxdepth 2 -print` si la carpeta existe en el ambiente de ejecucion.
- Revisar si `.gitignore` o deploy crea `lib/`.

**Agente implementador**

- Si existe y esta vacia, eliminarla.
- Si no existe, marcar el punto como resuelto en la auditoria.

**Agente validador**

- Confirmar que `rg 'lib/'` no muestra referencias funcionales.

**Criterios de aceptacion**

- El hallazgo queda cerrado: carpeta eliminada o inexistente documentada.

## 7.1. Ordenar archivos sueltos de raiz

**Estado**: Vigente.

**Agente revisor**

- Clasificar archivos raiz:
  - temporales: `tmp_eval.php`, `tmp_output.html`;
  - capturas/documentos cliente: `*.png`, `*.jpeg`, `ProdLeche_Datos_Historicos_cargar_2024_S2.xlsx`;
  - script auxiliar: `files_create.bat`.
- Confirmar si algun archivo esta referenciado por docs o codigo.

**Agente implementador**

- Crear `docs/client/` para capturas, imagenes de referencia y Excel.
- Mover `files_create.bat` a `scripts/` o `docs/playbooks/` segun uso.
- Eliminar temporales si no tienen valor documental.
- Actualizar referencias si existieran.

**Agente validador**

- Ejecutar `find . -maxdepth 1 -type f` para confirmar raiz limpia.
- Ejecutar `rg` por nombres antiguos.
- Confirmar que docs/client no contiene secretos ni datos sensibles que deban ignorarse.

**Criterios de aceptacion**

- La raiz queda reservada para archivos del proyecto.
- Artefactos de cliente quedan documentados y ubicados.
- Temporales desaparecen.

---

## Plan de ejecucion por sprints

### Sprint 1 — Seguridad login y CSRF

- 1.2, 1.4 y 6.5 como una unidad.
- 1.1 inmediatamente despues.
- Resultado esperado: login separado por capas y POST mutativos protegidos.

### Sprint 2 — Feedback UX

- 2.1 y 2.3 juntos.
- 2.2 despues, usando el modal ya existente.
- Resultado esperado: no quedan `alert()` ni `confirm()` nativos en flujos normales.

### Sprint 3 — Limpieza de arquitectura y raiz

- 6.2, 6.3, 6.4, 6.6 y 7.1.
- Resultado esperado: sin placeholders, backups servibles, legacy duplicado ni temporales en raiz.

### Sprint 4 — Responsive y estilos

- 4.1 solo validacion/cierre.
- 4.2 por formulario.
- 5.1 y 5.2 por grupos visuales.
- Resultado esperado: layout responsive consistente y CSS extraido gradualmente.

### Sprint 5 — Integracion ERP

- 6.1 aislado.
- Resultado esperado: cliente Finnegans centralizado y servicios transaccionales mas simples.

## Checklist transversal de validacion

- `php -l` sobre archivos PHP modificados.
- Busquedas de cierre:
  - `rg 'alert\\s*\\(' apps/web-php`
  - `rg 'confirm\\s*\\(' apps/web-php`
  - `rg 'style="' apps/web-php`
  - `rg '\\$_SESSION' apps/web-php`
  - `rg 'src/Core|Core/Database|DBConfig' .`
- Pruebas manuales minimas:
  - login/logout;
  - dashboard;
  - un listado maestro;
  - crear/editar/anular maestro;
  - produccion leche crear/editar/sync;
  - suplementacion crear/editar/sync;
  - cambio de empresa;
  - mobile 375px y desktop.

## Notas para actualizar la auditoria original

Cuando un punto sea implementado y validado, actualizar `docs/code_audit_proposals.md` con estado `✅ Resuelto` o moverlo a una seccion de resoluciones. Evitar borrar el historial del hallazgo sin dejar fecha, archivos tocados y criterio de cierre.
