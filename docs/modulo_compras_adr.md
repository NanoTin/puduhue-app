# ADR - Modulo de Compras

## ADR-001 - Integracion del modulo en Puduhue App Web

### Contexto

El modulo de Compras ya existe levantado sobre la arquitectura actual del proyecto, con FE, BE y base de datos funcionales. Rehacerlo como sistema aparte implicaria duplicar auth, menu, maestros y acceso a datos.

### Decision

- El modulo de Compras se implementa dentro de Puduhue App Web.
- Se reutiliza la arquitectura existente, la base de datos y los patrones de autenticacion y navegacion del proyecto.

### Consecuencia

Se evita duplicar infraestructura y el modulo queda alineado con el resto del producto desde el inicio.

## ADR-002 - Separacion estricta entre Requerimiento y Pre OC

### Contexto

El levantamiento funcional distingue dos transacciones con responsabilidades distintas: el Requerimiento como solicitud interna y la Pre OC como documento de consolidacion y aprobacion previa al ERP.

### Decision

- El Requerimiento y la Pre OC se modelan como flujos separados.
- El Requerimiento no mezcla materiales y servicios.
- La Pre OC puede consolidar multiples requerimientos aprobados.
- La compra parcial queda permitida a traves de una tabla intermedia de saldos pendientes.

### Consecuencia

Se conserva trazabilidad por etapa y se habilita reutilizacion parcial del saldo aprobado sin romper la historia del requerimiento original.

## ADR-003 - Maestros espejo de ERP como fuente de verdad operativa

### Contexto

El modulo depende de maestros que provienen de Finnegans: productos, centros de costo, proveedores, condiciones de pago, monedas, impuestos y workflows. Estos datos no deben quedar sujetos a edicion local libre.

### Decision

- Los maestros de origen ERP se sincronizan en Puduhue App Web como espejos.
- La operacion diaria usa sincronizacion automatica y, donde aplique, sincronizacion bajo demanda.
- Los CRUD locales solo aplican a atributos propios del negocio que no pertenecen al ERP.

### Consecuencia

Se evita divergencia entre ambos sistemas y se mantiene un catalogo local consistente para operar compras sin perder la referencia oficial del ERP.

## ADR-004 - Control presupuestario bloqueante

### Contexto

El negocio definio que una Pre OC no puede avanzar si no existe saldo disponible. Ademas, el presupuesto debe comportarse como un kardex con reservas y devoluciones.

### Decision

- La validacion presupuestaria es bloqueante.
- El presupuesto se administra como una estructura jerarquica con movimientos de saldo inicial, ajustes, reserva, confirmacion y devolucion.
- Las Pre OC en aprobacion reservan presupuesto y los rechazos o anulaciones liberan el saldo.

### Consecuencia

La compra queda controlada antes de comprometer gasto real y el reporte de saldo reflejara el estado operativo del pipeline de compras.

## ADR-005 - Aprobacion secuencial con reemplazo por inactividad

### Contexto

Los requerimientos y las Pre OC necesitan listas de firmantes trazables, con reglas por defecto, aprobadores manuales y reemplazo cuando exista inactividad de un aprobador.

### Decision

- La aprobacion es secuencial y trazable.
- El jefe del centro de costo es el aprobador base del Requerimiento.
- La Pre OC construye su lista de firmantes por roles de presupuesto, aprobacion por monto y aprobadores manuales.
- Si un aprobador esta inactivo dentro del rango definido, se omite su firma activa y se activa el reemplazo.

### Consecuencia

Se evita bloquear el flujo por ausencias operativas y se conserva una bitacora clara de quien debia firmar y quien lo reemplazo.

## ADR-006 - Integracion con Finnegans despues de aprobacion total

### Contexto

La OC real debe generarse en Finnegans solo cuando la Pre OC termina su aprobacion interna. La integracion necesita distinguir entre materiales y servicios y conservar respuesta del ERP para trazabilidad.

### Decision

- La integracion con Finnegans se ejecuta al alcanzar aprobacion total de la Pre OC.
- Se usan subtipos distintos en el ERP para material y servicio.
- La respuesta del ERP se persiste para auditoria, reintento y soporte.
- Un fallo de integracion no invalida el documento interno; lo deja en estado de error operativo recuperable.

### Consecuencia

Se desacopla la aprobacion interna del exito tecnico del ERP y se gana capacidad de reintento sin perder control de auditoria.

## ADR-007 - Trazabilidad como requisito base del modulo

### Contexto

El negocio pidio seguimiento completo del requerimiento, historial de cambios de items, logs de aprobacion y reportes de tiempos. Sin trazabilidad, el modulo pierde valor operativo.

### Decision

- Toda accion relevante genera historial y/o log.
- Los cambios de item deben conservar item original, item nuevo, usuario, fecha y motivo.
- Los tiempos de aprobacion y de generacion de Pre OC deben quedar disponibles para reporte.

### Consecuencia

El modulo habilita auditoria operativa real y metricas de mejora, no solo el registro transaccional minimo.
