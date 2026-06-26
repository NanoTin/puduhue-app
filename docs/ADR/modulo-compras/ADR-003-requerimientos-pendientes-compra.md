# ADR-003 - Requerimientos de compra y pendientes de compra

## Contexto

El modulo de Compras necesita cerrar la definicion funcional de Requerimientos de Compra antes de programar. Los documentos previos contenian avances correctos, pero tambien arrastraban decisiones ya superadas:

- estado `CSO` para cambios solicitados,
- estado `VNC` dentro del estado principal del REQ,
- validacion presupuestaria del REQ como bloqueante,
- inactividad asociada a funcionarios,
- empresa asociada a centros de costo desde ERP,
- permisos de usuarios demasiado genericos,
- relacion directa de `reqaprobados` con una PreOC unica.

Este ADR consolida las decisiones vigentes para REQ, pendientes de compra y bases compartidas que afectan el flujo.

## Decision

### Estados del REQ

El REQ usara solo estos estados documentales:

- `BRR`: Borrador.
- `PND`: Pendiente de aprobacion.
- `EDT`: En edicion.
- `APR`: Aprobado.
- `RCH`: Rechazado.
- `ANL`: Anulado.

No se usara `CSO`. La solicitud de cambios se trata como rechazo (`RCH`) con comentario obligatorio.

No se usara `VNC` como estado principal del REQ. La vinculacion con PreOC se representara en una columna separada de estado PreOC:

- sin estado: no aplica o no tiene vinculo,
- `VNC_Parcial`: vinculacion parcial,
- `VNC_Total`: vinculacion total.

### Rechazo y reenvio

El rechazo (`RCH`) es corregible. El comentario de rechazo es obligatorio y debe tener mas de 10 caracteres.

Al reenviar un REQ rechazado:

- se recalculan los firmantes default,
- se conservan firmantes manuales previos que sigan activos,
- se vuelve a aplicar reemplazo por inactividad,
- se reinicia la lista de aprobacion.

### Edicion y concurrencia

Un REQ no se puede editar cuando ya existe al menos una aprobacion o cuando ya esta aprobado.

Cuando un REQ esta en `PND` y el creador entra a editar, el estado pasa a `EDT`. Si un aprobador tenia la pantalla abierta antes del cambio, el backend/SP debe validar nuevamente el estado al presionar Aprobar o Rechazar. Si el REQ esta en `EDT`, la accion se rechaza con mensaje y el usuario debe volver al listado de pendientes de aprobacion.

La fecha funcional del requerimiento (`reqfecha` o equivalente) no la elige el usuario. Se toma desde sistema/BD. Cada edicion permitida, incluso en `BRR`, actualiza esa fecha funcional para no distorsionar KPI de tiempos. Esto es independiente de las columnas de auditoria.

### Firmantes de REQ

La lista default de firmantes incluye, cuando existan:

- jefe del centro de costo,
- jefe tecnico del centro de costo.

Los firmantes default no son removibles. Si alguno esta en periodo de inactividad, se registra como inactivo y se agrega su reemplazante como firmante activo.

Para enviar un REQ debe existir al menos un autorizador. Si no existe jefe de centro, jefe tecnico ni firmante manual, el sistema debe advertir que el documento quedara como borrador si el usuario decide continuar.

Los firmantes manuales solo pueden ser usuarios activos con permiso de aprobacion de REQ. No se permite agregar un firmante duplicado.

### Autorizador fuera de presupuesto

Cuando el analisis presupuestario del REQ detecta falta de saldo en una o mas combinaciones de temporada, subfamilia y centro de costo, el sistema debe agregar autorizadores fuera de presupuesto.

Estos autorizadores se resuelven desde usuarios activos con:

- `usuarioreqautorizadorfuerapptocompra = 1`,
- `usuarioreqautorizadorfuerapptocompraorden > 0`.

El orden debe ser unico entre usuarios con `usuarioreqautorizadorfuerapptocompra = 1`. Para el resto de usuarios, el orden queda en `0`.

Reglas:

- Se agregan una sola vez, aunque existan varios presupuestos sin saldo.
- Si un usuario ya estaba en la lista por otro motivo, se mantiene una sola fila y se marca tambien su motivo fuera de presupuesto.
- Si no estaba en la lista, se agrega internamente al final, ordenado por `usuarioreqautorizadorfuerapptocompraorden`.
- Es firmante default, no removible.
- No puede reordenarse por el usuario.
- Si existe un firmante fuera de presupuesto al final de la grilla, sus botones subir/bajar quedan bloqueados y el firmante inmediatamente anterior no puede bajar para quedar despues de el.
- Aplica reemplazo por periodo de inactividad.
- Si el REQ se edita y ya no existe falta de saldo, se recalcula y se elimina este firmante mientras no haya aprobaciones.
- El autorizador debe ver claramente que fue incluido porque una o mas subfamilias no tenian saldo suficiente.

### Inactividad de aprobadores

La inactividad se modela desde usuarios, no desde funcionarios, porque un funcionario no necesariamente tiene usuario de sistema.

La tabla/pantalla se llamara conceptualmente `aprobadoresperiodoinactividad` y aplica a REQ y PreOC. Debe registrar:

- usuario aprobador,
- usuario reemplazante,
- motivo,
- rango de fechas,
- estado/activo,
- auditoria.

Los registros no se eliminan fisicamente. Si dejan de aplicar, se inactivan.

Debe existir `aprobadoresperiodoinactividadlog` o tabla equivalente para trazabilidad adicional de creacion, edicion e inactivacion.

### Comentarios funcionales

REQ y PreOC tendran tablas separadas de comentarios funcionales. Estas tablas no reemplazan el LOG tecnico.

Se usaran para conservar comentarios de:

- aprobacion,
- rechazo,
- anulacion,
- otros eventos funcionales que deban ser visibles para el usuario.

El comentario es obligatorio al rechazar y debe tener mas de 10 caracteres. Al aprobar es opcional.

### Presupuesto en REQ

La validacion presupuestaria del REQ es informativa y no bloqueante. El REQ no genera movimientos presupuestarios.

El sistema debe permitir crear/enviar el requerimiento aunque no exista saldo suficiente. Los aprobadores decidiran aprobar o rechazar con esa informacion visible.

El calculo debe agrupar primero los items por:

- subfamilia,
- centro de costo.

La cabecera del REQ debe guardar un indicador de advertencia, por ejemplo `reqadvertenciapptocompra`, cuando exista al menos un grupo o item sin saldo suficiente.

Tambien debe guardarse una copia actualizable del calculo presupuestario usado como referencia. Si el REQ se edita, esa copia se recalcula y actualiza.

El detalle del REQ debe guardar `subfamiliaid` para facilitar el cruce con el snapshot presupuestario y reconocer los items que originan cada grupo de presupuesto.

Cada item del detalle debe guardar informacion informativa del ultimo requerimiento del mismo centro de costo e item:

- fecha ultimo requerimiento: maxima fecha encontrada para el mismo centro e item,
- cantidad ultimo requerimiento: cantidad solicitada en esa fecha.

Estos datos se muestran en la grilla y en las tarjetas de visualizacion. Son informativos y no cambian reglas de aprobacion.

El boton o accion "Analisis de ppto de compra" debe estar disponible para todo usuario que pueda visualizar el REQ, no solo para aprobadores. Debe permitir revisar el detalle por temporada, subfamilia y centro de costo.

El analisis debe mostrar, como minimo:

- saldo disponible actual del presupuesto,
- monto de otros requerimientos en curso,
- monto de requerimientos aprobados pendientes de compra,
- monto de este requerimiento,
- saldo disponible proyectado,
- porcentaje o proporcion que este requerimiento representa sobre el saldo disponible actual,
- deficit o advertencia cuando aplique.

### Centros de costo por usuario

Para crear REQ se usaran los centros asignados al usuario en `usuarioscentroscosto`.

La asociacion debe contemplar:

- `usuarioid`,
- `centrocostoid`,
- `usucendefault`,
- estado/activo,
- auditoria.

Solo puede existir un centro default activo por usuario. Si el usuario cambia el default, los demas centros del mismo usuario quedan como no default.

Si el usuario no tiene centros asignados, al crear REQ se debe mostrar error: "No tiene centro(s) asignado(s). Informar a Administracion."

El usuario puede cambiar el centro de costo del REQ por otro centro que tenga asignado.

### Centros de costo y ERP

No se contempla separar centros de costo por empresa desde el GET del ERP. El ERP maneja una sola base de centros para todas las empresas. Si en el futuro el cliente solicita esa separacion, sera un dato local/manual.

`DIMCTC` no requiere una columna adicional en el maestro de centros si equivale al codigo del centro (`centrocostocod`). Se usara internamente para PreOC e integracion ERP. El comprador no lo edita.

### Funcionarios

El maestro de funcionarios usa RUT como PK funcional, con formato preestablecido y validado. No requiere PK autoincremental como identificador funcional.

Debe contemplar centro de costo del funcionario en una columna como `funcencos`.

En el REQ, funcionario es opcional. Si se informa, queda como dato del solicitante. El centro operativo del REQ se resuelve desde los centros asignados al usuario.

### Usuarios y permisos

Se separan los permisos de compra:

- permite aprobar REQ,
- permite aprobar PreOC,
- autorizador fuera de presupuesto de compra para REQ,
- orden como autorizador fuera de presupuesto de compra para REQ,
- comprador,
- permite anular PreOC,
- editar precios,
- permite crear item,
- permite editar item,
- permite sincronizacion de transacciones ERP (`permitesynctrnerp` o nombre equivalente).

El permiso de crear item permite resolver casos urgentes antes de una sincronizacion ERP. El permiso de editar item permite editar solo:

- precio cuando es cero,
- uso funcional,
- activar/desactivar.

Debe advertirse al usuario que el cambio debe realizarse tambien en ERP, porque una sincronizacion posterior puede volver a modificar el dato local.

### Items

Un item con precio cero no puede agregarse a un REQ. El sistema debe indicar que se contacte a Administracion para resolver.

Los items ingresados localmente deben marcarse con `iteminglocal` o equivalente. Si luego la integracion ERP trae el item, ERP manda y actualiza los campos que correspondan.

No se permite cambiar un item de Material a Servicio ni de Servicio a Material.

### Pendientes de compra

`reqaprobados` mantiene una fila operativa por linea aprobada. No debe apuntar a una unica PreOC. La relacion con PreOC vive en `reqaprobadoshistorial`.

La tabla debe conservar snapshots operativos:

- REQ,
- item id,
- item codigo,
- item descripcion,
- unidad de medida,
- cantidad requerida,
- cantidad pendiente de compra,
- cantidad comprada,
- cantidad anulada.

La regla de consistencia es:

`cantidad requerida = cantidad pendiente + cantidad comprada + cantidad anulada`

Las pantallas pueden mostrar datos actuales del maestro cuando corresponda, pero los snapshots preservan la evidencia operativa.

El historial de pendientes debe guardar tambien la cantidad pendiente que tenia la linea antes del movimiento. Esto permite reconstruir rapidamente el saldo nuevo de una compra o anulacion sin depender solo del estado actual de `reqaprobados`.

### Cambio de item en pendientes

El comprador puede cambiar un item desde pendientes de compra, no desde PreOC.

El cambio debe validar:

- que no existan transacciones posteriores,
- que el item nuevo no exista ya en el REQ original,
- que no cambie Material por Servicio ni Servicio por Material.

Debe registrarse historial del cambio y marcarse visualmente en el detalle del requerimiento.

### Anulacion de cantidades pendientes

El comprador puede anular cantidad pendiente parcial o total desde pendientes de compra, con motivo obligatorio.

Solo se puede anular cantidad pendiente:

- si no hay compras parciales, puede anular el total,
- si hay compra parcial, solo puede anular el saldo pendiente,
- si ya se compro todo, no puede anular.

La anulacion debe registrarse historicamente y mostrarse al solicitante. El detalle del REQ puede mantener una cantidad acumulada anulada, por ejemplo `reqitemcantanulada`.

### Pantallas REQ

Debe existir una pantalla de pendientes de aprobacion, por ejemplo `reqcompra_pend_aprob`.

El usuario no puede venir por URL; debe resolverse desde sesion/websession u otro mecanismo no editable por el usuario.

El listado muestra acciones con iconos:

- Ver,
- Aprobar,
- Rechazar.

Si el usuario login cumple condicion de creador y aprobador pendiente, se muestran todos los botones aplicables.

La pantalla de visualizacion, por ejemplo `reqcompra_ver`, tendra doble proposito:

- si el usuario es creador, muestra Editar cuando el estado lo permita,
- si el usuario es aprobador pendiente, muestra Aprobar/Rechazar.

En movil, el detalle de items debe mostrarse como tarjetas y los botones de accion deben quedar fijos en la parte superior cuando exista un listado extenso.

### Creacion y edicion del REQ

La creacion del REQ puede resolverse como una pantalla unica con grupos funcionales:

1. Cabecera/Datos generales.
2. Detalle de items.
3. Lista de firmantes.
4. Resumen y advertencias.

Para agregar items:

- se usa un boton con signo `+`,
- se abre un modal de busqueda/seleccion,
- la busqueda debe permitir coincidencia por palabras clave, por ejemplo `LIKE '%texto%'`,
- se recomienda exigir un minimo de caracteres para busquedas amplias,
- el modal muestra datos relacionados del item antes de grabar,
- el usuario informa cantidad,
- el modal tiene botones Cancelar y Grabar.

Al grabar un item:

- se validan tipo, vigencia, comprable y precio mayor a cero,
- se calcula la linea,
- se agrega a la grilla,
- se recalculan numeros de linea, totales y analisis presupuestario.

Al agregar el primer item:

- se bloquea centro de costo y tipo de REQ,
- se genera la lista de firmantes default,
- se habilita gestion de firmantes manuales.

Al eliminar lineas:

- se recalculan numeros de linea,
- se recalculan totales y analisis presupuestario.

Si se eliminan todos los items estando en `BRR`:

- se desbloquean centro de costo y tipo de REQ,
- se limpian firmantes,
- si existian firmantes manuales, debe solicitarse confirmacion antes de limpiar.

Para agregar firmantes manuales:

- se usa un boton con signo `+`,
- se abre un modal de busqueda de usuarios,
- se filtran usuarios activos con permiso de aprobacion de REQ,
- al grabar se valida que no exista ya en la lista,
- se agrega como firmante manual removible y reordenable.

Para reordenar firmantes:

- la grilla debe tener botones Subir y Bajar,
- el primer firmante no puede subir,
- el ultimo firmante no puede bajar,
- si existe un solo firmante, ambos botones quedan bloqueados,
- al mover, se intercambia orden y se renumera sin huecos,
- los firmantes default de jefe/jefe tecnico no se pueden remover, pero si pueden reordenarse con manuales,
- los firmantes fuera de presupuesto quedan fijos al final y no pueden reordenarse.

### Tracking y KPI

La visualizacion del REQ debe mostrar de forma rapida:

- si un item tiene al menos una PreOC asociada,
- si la compra esta parcial o total,
- si el item fue editado,
- historial de seguimiento,
- dias sin asociar a PreOC.

Se separan tres capas:

- LOG tecnico/auditoria,
- comentarios funcionales,
- tracking/KPI de pasos del proceso.

### Resolucion del siguiente aprobador

REQ y PreOC deben usar una funcion/proceso interno para resolver el siguiente aprobador pendiente cada vez que:

- se envia a aprobacion,
- un firmante aprueba,
- se recalcula una lista por edicion permitida,
- se inserta un reemplazante por inactividad.

La funcion debe recorrer la lista en orden y validar:

1. Si el usuario no esta vigente:
   - marca al firmante como `No Vigente`,
   - registra comentario funcional,
   - registra LOG,
   - continua con el siguiente.
2. Si el usuario esta vigente pero tiene periodo de inactividad:
   - marca al firmante original como `Inactivo`,
   - registra comentario con motivo de inactividad,
   - inserta el reemplazante inmediatamente despues,
   - reordena la lista,
   - deja al reemplazante como siguiente aprobador.
3. Si el usuario esta vigente y no tiene inactividad:
   - lo deja como aprobador pendiente.

Si no existe ningun firmante habilitado despues de recorrer la lista, el documento queda aprobado solo si ya existe al menos una aprobacion efectiva y no quedan aprobaciones pendientes validas. Si esto ocurre antes de cualquier aprobacion efectiva, el documento no debe avanzar automaticamente a `APR`.

La columna de cabecera `reqaprobadoridpnd` es una denormalizacion controlada para consultas rapidas. Debe coincidir con el firmante pendiente vigente de la tabla de firmantes y se usa para listar pendientes de aprobacion sin resolverlo desde la URL ni desde parametros editables por el usuario.

### Emails y prioridad

REQ y PreOC deben contemplar prioridad:

- Normal,
- Alta.

La prioridad solo tiene efecto visual y enfasis en correo; no altera el flujo.

El envio de correos queda como requisito funcional preparado para:

- aprobadores con documentos pendientes,
- solicitantes cuando el documento sea aprobado o rechazado.

## Consecuencia

El REQ queda separado en tres dimensiones:

- estado documental del requerimiento,
- estado de vinculacion con PreOC,
- tracking de compra por linea.

El presupuesto queda como advertencia preventiva para REQ y como compromiso real en PreOC, consistente con ADR-001 y ADR-002.

La trazabilidad funcional mejora sin sobrecargar el LOG tecnico.

La estructura queda lista para actualizar documentos de datos, pantallas y futuros cortes de implementacion sin mezclar definiciones antiguas con decisiones vigentes.
