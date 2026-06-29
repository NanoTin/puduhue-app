# Modulo de Compras - Corte SQL Previo a Implementacion

> Documento puente entre definiciones funcionales y desarrollo.
>
> Este documento no contiene SQL ejecutable. Ordena el alcance de los futuros incrementales para evitar mezclar bases compartidas, REQ, pendientes de compra y PreOC en un solo cambio.
>
> Fuentes:
> - `docs/modulo_compras_brechas_sql.md`
> - `docs/modulo_compras_req_estructura.md`
> - `docs/modulo_compras_preoc_estructura.md`
> - `docs/modulo_compras_presupuesto_definitivo.md`
> - `docs/modulo_compras_plan_bases_compartidas.md`
> - `docs/modulo_compras_backlog_bases_compartidas.md`
>
> Diseno detallado del primer incremental:
> - `docs/modulo_compras_incremental_07_diseno.md`

## 1. Objetivo

Definir el orden recomendado para preparar la base de datos antes de programar pantallas, endpoints o stored procedures funcionales de REQ y PreOC.

El corte busca:

- mantener cambios pequenos y auditables,
- separar maestros compartidos de transacciones,
- evitar que PreOC dependa de tablas incompletas de REQ,
- dejar explicitas las decisiones pendientes,
- permitir revisar cada incremental antes de ejecutarlo en una BD real.

## 2. Principios del corte

1. Ningun SQL debe ejecutarse en BD real sin autorizacion explicita.
2. Los incrementales deben ser idempotentes cuando sea razonable (`IF NOT EXISTS`, indices con nombre estable, seeds controladas).
3. Cada tabla transaccional debe tener su LOG o justificar por que no aplica.
4. Los maestros de estado deben ser codificados y semanticos, no dependientes de autoincremental cuando el codigo sea regla de negocio.
5. Las FK a maestros ERP deben permitir evolucionar si el dato viene incompleto desde sincronizacion.
6. Las reglas que impactan presupuesto deben quedar centralizadas para REQ/PreOC y no duplicadas en pantallas.
7. El primer corte debe privilegiar DDL y contratos; no debe mezclar pantallas.

## 3. Incrementales sugeridos

| Incremental sugerido | Alcance | Bloquea |
|---|---|---|
| `07_modulo_compras_bases_compartidas.sql` | Usuarios, usuario-centro, funcionarios, inactividad, items, presupuesto y maestros ERP faltantes | REQ y PreOC |
| `08_modulo_compras_req.sql` | Cabecera, detalle, firmantes, comentarios, snapshot, estados y log de REQ | Creacion y aprobacion REQ |
| `09_modulo_compras_req_pendientes.sql` | `reqaprobados`, historial y cambios de item | PreOC desde pendientes |
| `10_modulo_compras_preoc.sql` | Cabecera PreOC, origen req-items, items agrupados, impuestos, dimensiones, resumen, firmantes, estados y log | Flujo PreOC |
| `11_modulo_compras_presupuesto_sp.sql` | SP/servicios de reserva, confirmacion, reversa y recalculo operacional | Estados PreOC y saldos |

Los nombres son propuestos. Antes de crear archivos reales, deben revisarse contra la secuencia vigente en `database/alter_table`.

El detalle funcional/tecnico del incremental 07 queda desarrollado en `docs/modulo_compras_incremental_07_diseno.md`.

## 4. Incremental 07 - Bases compartidas

### 4.1 Usuarios

Agregar atributos documentados:

- `usuariopermiteaprobreq`,
- `usuariopermiteaprobpreoc`,
- `usuariocomprador`,
- `usuariopermiteanularpreoc`,
- `usuariopermiteeditarprecios`,
- `usuariopermitecrearitem`,
- `usuariopermiteeditaritem`,
- `usuariopermitesynctrnerp`,
- `usuarioreqautorizadorfuerapptocompra`,
- `usuarioreqautorizadorfuerapptocompraorden`.

Reglas esperadas:

- el orden de autorizadores fuera de presupuesto debe ser unico cuando `usuarioreqautorizadorfuerapptocompra = 1`;
- usuarios inactivos no deben servir para nuevas listas de firmantes;
- el filtro comprador de PreOC debe listar usuarios compradores aunque esten inactivos, para busqueda historica.
- En el primer corte, la unicidad condicional del orden se valida en SP/BE, no con indice funcional.

Pendiente tecnico:

- actualizar `usuarioslog` y `sp_usuarios_*`.

### 4.2 Usuarios-centros de costo

Crear `usuarioscentroscosto` y LOG.

Columnas funcionales minimas:

- PK interna,
- `usuarioid`,
- `centrocostoid`,
- `usucendefault`,
- estado activo/inactivo,
- auditoria.

Reglas esperadas:

- solo un centro default activo por usuario;
- se permite asociar, inactivar y reactivar;
- si cambia el default, el resto queda en `FALSE`;
- si el usuario creador de REQ no tiene centros activos, debe bloquear la creacion con mensaje a Administracion.
- En el primer corte, la unicidad condicional del centro default se valida en SP/BE, no con indice funcional.

### 4.3 Funcionarios

Crear maestro propio. No deberia existir actualmente una tabla equivalente.

Columnas funcionales minimas:

- Rut como PK funcional, sin puntos, con guion y digito verificador;
- nombre,
- estado,
- `funcencos`,
- auditoria,
- LOG.

Reglas esperadas:

- funcionario es opcional en REQ;
- no debe confundirse funcionario con usuario aprobador;
- un funcionario no necesariamente tiene usuario de sistema.
- requiere BE, FE y carga masiva por Excel.

### 4.4 Aprobadores periodo inactividad

Crear:

- `aprobadoresperiodoinactividad`,
- `aprobadoresperiodoinactividadlog`.

Columnas funcionales minimas:

- PK interna,
- usuario aprobador,
- usuario reemplazante,
- fecha inicio,
- fecha fin,
- motivo/tipo,
- estado,
- auditoria.

Reglas esperadas:

- no eliminar fisicamente, solo inactivar;
- aplica a REQ y PreOC;
- el resolver de siguiente aprobador debe saltar usuarios no vigentes y agregar reemplazante cuando exista inactividad vigente.

### 4.5 Presupuesto de compra

Extender `pptocompra` con usuarios default para firmantes PreOC:

- `pptocompraresponsableid`,
- `pptocompraadministradorid`,
- `pptocompracolaboradorid`.

Reglas esperadas:

- responsable y administrador obligatorios;
- colaborador opcional;
- todos deben ser usuarios activos con permiso de aprobacion PreOC;
- colaborador puede repetir con responsable/administrador;
- la lista final de firmantes PreOC debe deduplicar usuarios.
- En el primer incremental las columnas se crean `NULL` para permitir carga/backfill de datos existentes.
- BE/FE deben validar obligatoriedad funcional antes de crear/enviar presupuestos que se usen para PreOC.
- Luego de poblar datos se podra evaluar un incremental posterior para endurecer a `NOT NULL`.

Pendiente tecnico:

- actualizar `sp_pptocompra_crear`, `sp_pptocompra_actualizar`, `sp_pptocompra_listar`, `sp_pptocompra_por_id` y logs.

### 4.6 Items

Extender `invitems`:

- agregar `iteminglocal` o nombre equivalente definido.

Reglas esperadas:

- Material/Servicio no requiere columna nueva:
  - `invitemstockeable = 1` equivale a Material,
  - `invitemstockeable = 0` equivale a Servicio.
- Para REQ y PreOC debe cumplirse ademas `invitemcompra = 1`.
- item local resuelve urgencias operativas;
- si ERP sincroniza el item despues, ERP manda sobre campos espejo;
- edicion local permitida solo para precio cuando es cero, uso funcional y activar/desactivar.

Pendiente tecnico:

- actualizar `sp_invitems_*`;
- incorporar validaciones de permisos desde `usuarios`.

### 4.7 Proveedores

Crear maestros locales de proveedores, usando la misma logica de productos:

1. leer `ERP_PROVEEDORES_LIST`;
2. guardar/cotejar proveedor base por codigo;
3. consultar `ERP_PROVEEDORES_DETALLE` por cada proveedor grabado;
4. completar los campos de detalle.

- maestro local de proveedores,
- LOG proveedores,
- relacion proveedor-condicion de pago.

Reglas esperadas:

- proveedores son espejo ERP;
- la pantalla es solo consulta y exportar a Excel;
- PreOC no avanza sin proveedor resoluble;
- proveedor puede tener una o mas condiciones de pago asociadas;
- la condicion de pago de PreOC se precarga desde proveedor cuando aplique, pero puede editarse antes de grabar.

Campos observados en JSON:

- list: `codigo`, `nombre`, `descripcion`, `activo`;
- detalle: `Codigo`, `Nombre`, `Activo`, `RazonSocial`, `Email`, `CategoriaFiscalCodigo`, `IdentificacionTributariaCodigo`, `IdentificacionTributariaNumero`, `CondicionesPago`, `ConceptoProveedorCodigo`, `CuentaProveedorCodigo`, `MonedaID_Pago_Codigo`, `USR_MedioPago`.

### 4.8 Condiciones de pago

Crear maestros locales de condiciones de pago, usando la misma logica de productos/proveedores:

1. leer `ERP_CONDICIONES_PAGO_LIST`;
2. guardar/cotejar condicion base por codigo;
3. consultar `ERP_CONDICIONES_PAGO_DETALLE` por cada condicion grabada;
4. completar los campos de detalle y sus items.

- maestro local de condiciones de pago,
- LOG condiciones de pago,
- detalle/items de condicion de pago.

Reglas esperadas:

- condiciones de pago son espejo ERP;
- la pantalla es solo consulta y exportar a Excel;
- proveedor puede restringir las condiciones disponibles;
- PreOC debe validar condicion seleccionada.

Campos observados en JSON:

- list: `codigo`, `nombre`, `descripcion`, `activo`;
- detalle: `Codigo`, `Activo`, `Nombre`, `Tipo`, `EdicionFija`, `ExigeDocumentosDiferidos`, `PorcentajeInteres`, `CtaProveedores`, `CtaDeudoresPorVentas`, `CtaDisponibilidad`, `ETA/ETD`, `CondicionPagoItems`;
- item: `Fecha`, `Tipo`, `Dias`, `Porcentaje`.

## 5. Incremental 08 - REQ

Crear bloque base de requerimientos:

- `reqcompras`,
- `reqcomprasdetalle`,
- `reqcomprasfirmantes`,
- `reqcomprascomentarios`,
- `reqcompraslog`,
- `reqcomprasestados`,
- `reqcompraestadopreoc`,
- `reqcompraspptosnapshot`.

Reglas a cubrir:

- estados documentales `BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL`;
- estado PreOC separado: sin estado, vinculado parcial, vinculado total;
- `reqfecha` definida por sistema/BD y actualizada en edicion funcional;
- `reqadvertenciapptocompra` por deficit informativo;
- `reqaprobadoridpnd` como denormalizacion controlada;
- `reqaprobacionfecha` para KPI;
- no se edita si existe al menos una aprobacion;
- edicion en `PND` cambia a `EDT`;
- aprobador debe revalidar estado al aprobar/rechazar;
- rechazo exige comentario con mas de 10 caracteres;
- detalle guarda subfamilia, ultimo requerimiento fecha/cantidad, cantidades y anulaciones acumuladas;
- firmantes default/manuales/fuera de presupuesto con bloqueo de removibles/reordenables segun tipo;
- snapshot presupuestario actualizable mientras el REQ sea editable.

Pendiente antes de DDL:

- confirmar nombres fisicos finales de columnas de prioridad y observaciones.
- Material/Servicio se resuelve desde `invitems.invitemstockeable`; no requiere columna nueva.
- `reqcomprasestados` debe usar codigo como PK (`BRR`, `PND`, `EDT`, `APR`, `RCH`, `ANL`).

## 6. Incremental 09 - Pendientes de compra

Crear:

- `reqaprobados`,
- `reqaprobadoshistorial`,
- `reqaprobadoscambios`.

Reglas a cubrir:

- `reqaprobados` mantiene una fila por REQ-item aprobado;
- no contiene `preocid` como relacion unica;
- cantidades requerida, pendiente, comprada y anulada deben cuadrar;
- anulacion solo sobre cantidad pendiente;
- si ya esta comprado todo, no se puede anular;
- historial guarda `histcantidadpendienteantes`;
- cambios de item validan que no existan transacciones posteriores;
- cambio de item no permite Material por Servicio ni viceversa;
- item modificado queda marcado visualmente y con trazabilidad.

Pendiente antes de DDL:

- definir si `reqaprobados` mantiene todos los snapshots o si alguna descripcion se resuelve siempre desde maestro.

## 7. Incremental 10 - PreOC

Crear:

- `preoc`,
- `preocdetallereqitems`,
- `preocitems`,
- `preocimptos`,
- `preocitemsdimensiones`,
- `preocpptoresumen`,
- `preocfirmantes`,
- `preoccomentarios`,
- `preoclog`,
- `preocestados`,
- `preocestadoserp`,
- `preocaprobadoresxmonto`.

Reglas a cubrir:

- estados documentales `BRR`, `PND`, `APR`, `RCH`, `ANL`;
- estado ERP separado: sin estado, `SNC`, `ERR`;
- `preocfecha` interna no editable;
- `preocfechaoc` seleccionada por usuario para presupuesto y ERP;
- `preocobsinterna` y `preocobsoc`;
- prioridad normal/alta;
- aprobador pendiente en cabecera;
- sin edicion cuando ya existe una aprobacion;
- `BRR -> PND` valida saldo y genera reserva;
- `PND -> BRR` antes de aprobaciones libera reserva;
- `APR` confirma reserva;
- `RCH` y `ANL` reversan cuando corresponda;
- sincronizada y anulada localmente mantiene estado ERP `SNC`;
- `ERR` conserva error visible en cabecera;
- detalle origen viene de `reqaprobados`;
- items agrupados concentran precio y totales;
- impuestos pueden ser multiples por item agrupado;
- resumen presupuesto es apoyo rapido, no libro oficial;
- firmantes default vienen de presupuestos usados y se deduplican.

Pendientes antes de DDL:

- confirmar estructura de `preocaprobadoresxmonto`.

Definiciones cerradas:

- proveedor y condicion de pago se crean como maestros espejo ERP con list + detalle.
- `preocitemsdimensiones` cuelga operativamente de `preocdetallereqitems`; puede mantener `preocitemid` nullable como apoyo si luego se requiere consulta por item agrupado.
- estados documentales y ERP usan codigo como PK.

## 8. Incremental 11 - SP y reglas presupuestarias

Crear o ajustar SP/servicios para:

- resolver presupuesto por fecha, subfamilia y centro;
- analizar REQ sin bloquear ni generar movimientos;
- generar snapshot de REQ agrupando por subfamilia y centro;
- reservar PreOC al pasar de `BRR` a `PND`;
- confirmar reserva al aprobar;
- revertir por rechazo/anulacion;
- borrar reserva provisional si vuelve de `PND` a `BRR` antes de aprobaciones;
- recalcular totales de `pptocompra`;
- registrar referencias de origen con modulo, documento y linea.

Reglas a cubrir:

- REQ no mueve presupuesto;
- PreOC mueve presupuesto desde `PND`;
- si no hay presupuesto en REQ, se marca fuera de presupuesto;
- si no hay presupuesto o saldo en PreOC, no avanza;
- todos los movimientos deben quedar en `pptocompratransacciones`.

## 9. Orden minimo antes de programar pantallas

Antes de programar REQ:

1. Incremental 07 completo o al menos usuarios, usuario-centro, inactividad, items y `pptocompra` responsables.
2. Incremental 08 completo.
3. Resolver SP/servicio de analisis presupuestario informativo.

Antes de programar pendientes de compra:

1. REQ aprobado y persistido.
2. Incremental 09 completo.

Antes de programar PreOC:

1. Incremental 07 completo incluyendo proveedores/condiciones.
2. Incremental 09 completo.
3. Incremental 10 cabecera/detalle/firmantes.
4. Incremental 11 para reserva/confirmacion/reversa.

## 10. Decisiones cerradas y pendientes antes de escribir SQL

| Tema | Decision | Estado | Impacto |
|---|---|---|
| Nombres fisicos de permisos en `usuarios` | Usar prefijo `usuario...` y nombres definidos en 4.1 | Cerrado | DDL, SP, FE |
| Material/Servicio en item | Usar `invitemstockeable`: 1 Material, 0 Servicio; complementar con `invitemcompra = 1` | Cerrado | Validacion REQ/PreOC |
| Funcionarios | Crear tabla propia, BE, FE y carga masiva Excel | Cerrado | Base compartida |
| Proveedores | Crear maestros espejo con list + detalle; consulta y exportacion Excel | Cerrado para primer modelo | Bloquea PreOC |
| Condiciones de pago | Crear maestros espejo con list + detalle/items; consulta y exportacion Excel | Cerrado para primer modelo | Bloquea PreOC |
| `preocitemsdimensiones` | Nivel operativo `preocdetallereqitems`; `preocitemid` nullable opcional | Cerrado | Payload ERP |
| Estados maestros | PK codigo | Cerrado | Consistencia SQL/SP |
| Centros de costo `empresaid` | Mantener como local/no usado por ERP por ahora | Cerrado | Integracion ERP |
| `preocaprobadoresxmonto` | Confirmar estructura de reglas por monto | Pendiente | Firmantes PreOC |

## 11. Recomendacion

El primer archivo SQL real deberia ser solo el incremental de bases compartidas. Despues de revisarlo, se recomienda hacer un commit antes de pasar a REQ.

No conviene crear PreOC antes de dejar cerrado `reqaprobados`, porque la PreOC depende directamente de los saldos pendientes aprobados.
