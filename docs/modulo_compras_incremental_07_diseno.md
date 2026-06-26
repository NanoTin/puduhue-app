# Modulo de Compras - Diseno Incremental 07 Bases Compartidas

> Documento de diseno previo al SQL.
>
> No contiene SQL ejecutable. Define tablas, columnas, indices, seeds y reglas esperadas para el futuro archivo `07_modulo_compras_bases_compartidas.sql`.
>
> Fuentes:
> - `docs/modulo_compras_corte_sql_previo.md`
> - `docs/modulo_compras_brechas_sql.md`
> - `docs/modulo_compras_req_estructura.md`
> - `docs/modulo_compras_preoc_estructura.md`
> - `database/tables/01_table_usuarios.sql`
> - `database/tables/01_table_invitems.sql`
> - `database/tables/01_table_pptocompra.sql`
> - `database/tables/01_table_centroscosto.sql`
> - `database/tables/01_table_pptocompralog.sql`
> - `database/tables/01_table_centroscostolog.sql`

## 1. Objetivo del incremental

Preparar las bases compartidas necesarias antes de crear las tablas transaccionales de REQ, pendientes de compra y PreOC.

Este incremental debe cubrir:

- permisos de usuarios para compras;
- relacion usuario-centro de costo;
- maestro de funcionarios;
- periodos de inactividad de aprobadores;
- firmantes default de PreOC en presupuesto de compra;
- marca local de items;
- proveedores;
- condiciones de pago;
- relacion proveedor-condicion de pago.

## 2. Principios tecnicos

1. Debe ser idempotente cuando sea posible.
2. Debe usar nombres de indices y constraints estables.
3. No debe crear tablas REQ ni PreOC.
4. No debe ejecutar llamadas ERP.
5. No debe incluir pantallas, endpoints PHP ni SP funcionales complejos.
6. Las tablas espejo ERP no tienen CRUD libre; solo consulta/exportacion.
7. Los logs siguen el patron `logid`, `logusuarioid`, `logdispositivo`, `logip`, `logfechahora`, `logtipo`, `logparamjson`, `logregbkpjson`.
8. Las columnas de auditoria siguen el patron actual del proyecto.

## 3. Cambios en `usuarios`

### 3.1 Columnas nuevas

| Columna | Tipo logico | Default | Uso |
|---|---:|---:|---|
| `usuariopermiteaprobreq` | TINYINT(1) | 0 | Usuario puede aprobar REQ y formar listas de firmantes REQ. |
| `usuariopermiteaprobpreoc` | TINYINT(1) | 0 | Usuario puede aprobar PreOC y formar listas de firmantes PreOC. |
| `usuariocomprador` | TINYINT(1) | 0 | Usuario puede crear/gestionar PreOC si tiene acceso al formulario. |
| `usuariopermiteanularpreoc` | TINYINT(1) | 0 | Usuario puede anular PreOC con permiso especial cuando el estado lo permite. |
| `usuariopermiteeditarprecios` | TINYINT(1) | 0 | Usuario puede editar precios donde el flujo lo permita. |
| `usuariopermitecrearitem` | TINYINT(1) | 0 | Usuario puede crear item local urgente. |
| `usuariopermiteeditaritem` | TINYINT(1) | 0 | Usuario puede editar precio cero, uso funcional y activo/inactivo. |
| `usuariopermitesynctrnerp` | TINYINT(1) | 0 | Usuario puede ejecutar sincronizacion de transacciones ERP. |
| `usuarioreqautorizadorfuerapptocompra` | TINYINT(1) | 0 | Usuario autorizador fuera de presupuesto para REQ. |
| `usuarioreqautorizadorfuerapptocompraorden` | INT | 0 | Orden relativo de autorizadores fuera de presupuesto. |

### 3.2 Indices y reglas

| Elemento | Propuesta | Motivo |
|---|---|---|
| `idx_usuarios_aprobreq` | `usuariopermiteaprobreq`, `usuarioactivo` | Listas de firmantes REQ. |
| `idx_usuarios_aprobpreoc` | `usuariopermiteaprobpreoc`, `usuarioactivo` | Listas de firmantes PreOC. |
| `idx_usuarios_comprador` | `usuariocomprador`, `usuarioactivo` | Filtro comprador en PreOC. |
| `idx_usuarios_reqautfuera` | `usuarioreqautorizadorfuerapptocompra`, `usuarioreqautorizadorfuerapptocompraorden`, `usuarioactivo` | Autorizadores fuera de presupuesto. |

Regla funcional:

- `usuarioreqautorizadorfuerapptocompraorden` debe ser unico entre usuarios con `usuarioreqautorizadorfuerapptocompra = 1`.

Pendiente tecnico:

- MariaDB puede requerir una columna generada o indice funcional para expresar unicidad condicional. Si no se implementa a nivel DDL en el primer corte, debe validarse en SP/BE.

### 3.3 SP afectados despues del DDL

No se modifican en este documento, pero deben quedar en backlog:

- `sp_usuarios_insertar`;
- `sp_usuarios_editar`;
- `sp_usuarios_listar`;
- `sp_usuarios_anular`;
- `usuarioslog` debe incluir las nuevas columnas en backup JSON.

## 4. Nueva tabla `usuarioscentroscosto`

### 4.1 Proposito

Definir los centros de costo que un usuario puede usar al crear REQ y permitir un centro default.

### 4.2 Columnas

| Columna | Tipo logico | NULL | Uso |
|---|---:|---|---|
| `usucenid` | INT PK AI | NO | PK interna. |
| `usuarioid` | INT FK | NO | Usuario asociado. |
| `centrocostoid` | INT FK | NO | Centro de costo asociado. |
| `usucendefault` | TINYINT(1) | NO | 1 si es default del usuario. |
| `usucenactivo` | TINYINT(1) | NO | Vigencia de la asociacion. |
| auditoria | columnas estandar | NO/SI | Patron proyecto. |

### 4.3 Indices y reglas

| Elemento | Propuesta | Motivo |
|---|---|---|
| `uq_usuarioscentroscosto_usuario_centro` | `usuarioid`, `centrocostoid` | Evitar duplicados. |
| `idx_usuarioscentroscosto_usuario` | `usuarioid`, `usucenactivo` | Resolver centros de un usuario. |
| `idx_usuarioscentroscosto_centro` | `centrocostoid`, `usucenactivo` | Consulta inversa. |
| default unico | `usuarioid` cuando `usucendefault = 1` y activo | Solo un default activo por usuario. |

Reglas:

- Si se marca un centro como default, los demas centros del mismo usuario quedan `usucendefault = 0`.
- Si el usuario no tiene centros activos, crear REQ debe bloquear con mensaje: "No tiene centro(s) asignado(s). Informar a Administracion."
- No se elimina la asociacion; se inactiva.

### 4.4 Log

Tabla `usuarioscentroscostolog`.

Columnas:

- `usucenid`;
- `logid`;
- `logusuarioid`;
- `logdispositivo`;
- `logip`;
- `logfechahora`;
- `logtipo`;
- `logparamjson`;
- `logregbkpjson`.

## 5. Nueva tabla `funcionarios`

### 5.1 Proposito

Maestro propio de funcionarios/solicitantes para REQ, reporteria y carga masiva.

### 5.2 Columnas

| Columna | Tipo logico | NULL | Uso |
|---|---:|---|---|
| `funcionariorut` | VARCHAR(12) PK | NO | RUT sin puntos, con guion y digito verificador. |
| `funcionarionombre` | VARCHAR(150) | NO | Nombre visible. |
| `funcionarioemail` | VARCHAR(120) | SI | Contacto si aplica. |
| `funcionariocelular` | VARCHAR(20) | SI | Contacto si aplica. |
| `funcencos` | INT FK | SI | Centro de costo asociado. |
| `funcionarioactivo` | TINYINT(1) | NO | Vigencia. |
| auditoria | columnas estandar | NO/SI | Patron proyecto. |

### 5.3 Indices y reglas

| Elemento | Propuesta | Motivo |
|---|---|---|
| PK | `funcionariorut` | El Rut es la PK funcional definida. |
| `idx_funcionarios_encos` | `funcencos`, `funcionarioactivo` | Consulta por centro. |
| `idx_funcionarios_nombre` | `funcionarionombre` | Busqueda FE. |

Reglas:

- Funcionario es opcional al crear REQ.
- Funcionario no equivale a usuario de sistema.
- Carga masiva Excel debe validar RUT sin puntos, con guion y digito verificador, duplicados y centro existente.
- No se elimina; se inactiva.

### 5.4 Log

Tabla `funcionarioslog`.

Por usar Rut como PK, el log referencia `funcionariorut` en vez de ID numerico.

## 6. Nueva tabla `aprobadoresperiodoinactividad`

### 6.1 Proposito

Registrar ausencias/inactividad de usuarios aprobadores y su reemplazante para REQ y PreOC.

### 6.2 Columnas

| Columna | Tipo logico | NULL | Uso |
|---|---:|---|---|
| `aprobadorperiodoid` | INT PK AI | NO | PK interna. |
| `aprobadorusuarioid` | INT FK | NO | Usuario aprobador ausente/inactivo. |
| `aprobadorreemplazousuarioid` | INT FK | NO | Usuario reemplazante. |
| `aprobadorperiodotipocod` | VARCHAR(30) | NO | AUSENCIA/VACACIONES/OTRO u otro codigo. |
| `aprobadorperiodomotivo` | VARCHAR(250) | SI | Motivo visible. |
| `aprobadorperiodofechainicio` | DATE | NO | Inicio vigencia. |
| `aprobadorperiodofechafin` | DATE | NO | Fin vigencia. |
| `aprobadorperiodoactivo` | TINYINT(1) | NO | Vigencia del registro. |
| auditoria | columnas estandar | NO/SI | Patron proyecto. |

### 6.3 Indices y reglas

| Elemento | Propuesta | Motivo |
|---|---|---|
| `idx_aprobadoresperiodo_usuario` | `aprobadorusuarioid`, `aprobadorperiodoactivo`, fechas | Resolver inactividad vigente. |
| `idx_aprobadoresperiodo_reemplazo` | `aprobadorreemplazousuarioid`, `aprobadorperiodoactivo` | Trazabilidad por reemplazante. |

Reglas:

- No se elimina; solo se inactiva.
- Fecha inicio debe ser menor o igual a fecha fin.
- Aprobador y reemplazante no deben ser el mismo usuario.
- Reemplazante debe estar activo.
- El resolver de siguiente aprobador usa esta tabla para insertar o saltar reemplazante segun regla del flujo.

### 6.4 Log

Tabla `aprobadoresperiodoinactividadlog`.

## 7. Cambios en `pptocompra`

### 7.1 Columnas nuevas

| Columna | Tipo logico | NULL | Uso |
|---|---:|---|---|
| `pptocompraresponsableid` | INT FK usuarios | SI | Primer firmante default de PreOC desde presupuesto; obligatorio funcional. |
| `pptocompraadministradorid` | INT FK usuarios | SI | Segundo firmante default de PreOC desde presupuesto; obligatorio funcional. |
| `pptocompracolaboradorid` | INT FK usuarios | SI | Tercer firmante default opcional. |

### 7.2 Indices y reglas

| Elemento | Propuesta | Motivo |
|---|---|---|
| `idx_pptocompra_responsable` | `pptocompraresponsableid` | Consulta/aprobaciones. |
| `idx_pptocompra_administrador` | `pptocompraadministradorid` | Consulta/aprobaciones. |
| `idx_pptocompra_colaborador` | `pptocompracolaboradorid` | Consulta/aprobaciones. |

Reglas:

- Responsable y administrador son obligatorios.
- Los tres usuarios deben tener `usuariopermiteaprobpreoc = 1`.
- Responsable, administrador y colaborador pueden repetirse; la lista de firmantes PreOC deduplica.
- Las columnas deben entrar en `pptocompralog` via backup JSON de SP.

Decision operativa:

- En el primer incremental las columnas se crean nullable para permitir backfill manual de registros existentes.
- BE/FE deben validar obligatoriedad funcional antes de crear/enviar presupuestos utilizables por PreOC.
- Despues de poblar datos se podra evaluar un incremental posterior para cambiar responsable/administrador a `NOT NULL`.

## 8. Cambio en `invitems`

### 8.1 Columna nueva

| Columna | Tipo logico | Default | Uso |
|---|---:|---:|---|
| `iteminglocal` | TINYINT(1) | 0 | Item ingresado localmente para resolver urgencia. |

### 8.2 Reglas

- `invitemstockeable = 1` equivale a Material.
- `invitemstockeable = 0` equivale a Servicio.
- `invitemcompra = 1` habilita uso en REQ/PreOC.
- Si ERP luego trae el item local, ERP manda y actualiza campos espejo.
- Edicion local rapida solo permite precio cero, uso funcional y activo/inactivo.

### 8.3 SP afectados despues del DDL

- `sp_invitems_insertar`;
- `sp_invitems_editar`;
- `sp_invitems_listar`;
- `sp_invitems_anular`;
- `invitemslog` debe incluir `iteminglocal` en backups.

## 9. Nuevas tablas de proveedores

### 9.1 `erpproveedores`

Tabla espejo ERP. Sin CRUD libre.

Columnas propuestas:

| Columna | Tipo logico | NULL | Fuente |
|---|---:|---|---|
| `erpproveedorid` | INT PK AI | NO | Interno. |
| `erpproveedorcod` | VARCHAR(50) UNIQUE | NO | `codigo` / `Codigo`. |
| `erpproveedornombre` | VARCHAR(150) | NO | `nombre` / `Nombre`. |
| `erpproveedordescripcion` | VARCHAR(255) | SI | `descripcion` / `Descripcion`. |
| `erpproveedorrazonsocial` | VARCHAR(150) | SI | `RazonSocial`. |
| `erpproveedoremail` | VARCHAR(150) | SI | `Email`. |
| `erpcategoriafiscalcod` | VARCHAR(50) | SI | `CategoriaFiscalCodigo`. |
| `erpidenttributariacod` | VARCHAR(50) | SI | `IdentificacionTributariaCodigo`. |
| `erpidenttributarianro` | VARCHAR(50) | SI | `IdentificacionTributariaNumero`. |
| `erpproveedorescliente` | TINYINT(1) | NO | `EsCliente`. |
| `erpproveedorescontratista` | TINYINT(1) | NO | `EsContratista`. |
| `erpproveedorrestriccioncondpagos` | TINYINT(1) | NO | `RestriccionCondPagos`. |
| `erpconceptoproveedorcod` | VARCHAR(50) | SI | `ConceptoProveedorCodigo`. |
| `erpcuentaproveedorcod` | VARCHAR(50) | SI | `CuentaProveedorCodigo`. |
| `erpmonedapagocod` | VARCHAR(50) | SI | `MonedaID_Pago_Codigo`. |
| `erpproveedormediopago` | VARCHAR(50) | SI | `USR_MedioPago`. |
| `erpproveedoractivo` | TINYINT(1) | NO | `activo` / `Activo`. |
| `sincfechahora` | DATETIME | SI | Ultima sincronizacion. |
| auditoria | columnas estandar | NO/SI | Patron proyecto. |

Indices:

- `uq_erpproveedores_cod`;
- `idx_erpproveedores_activo`;
- `idx_erpproveedores_nombre`;
- `idx_erpproveedores_categoriafiscal`;

### 9.2 `erpproveedoreslog`

Log espejo del maestro.

### 9.3 `erpproveedorescondicionespago`

Relacion proveedor-condicion de pago desde `CondicionesPago`.

Columnas propuestas:

| Columna | Tipo logico | NULL | Uso |
|---|---:|---|---|
| `erpprovcondpagoid` | INT PK AI | NO | PK. |
| `erpproveedorid` | INT FK | NO | Proveedor. |
| `erpcondicionpagoid` | INT FK | NO | Condicion de pago. |
| `erpprovcondpagodefault` | TINYINT(1) | NO | Marca `Default`. |
| `erpprovcondpagoactivo` | TINYINT(1) | NO | Vigencia. |
| auditoria | columnas estandar | NO/SI | Patron proyecto. |

Reglas:

- Un proveedor no puede duplicar la misma condicion de pago.
- Si ERP informa una condicion que no existe localmente, se debe registrar error de sincronizacion o dejarla pendiente segun estrategia del servicio.

## 10. Nuevas tablas de condiciones de pago

### 10.1 `erpcondicionespago`

Tabla espejo ERP. Sin CRUD libre.

Columnas propuestas:

| Columna | Tipo logico | NULL | Fuente |
|---|---:|---|---|
| `erpcondicionpagoid` | INT PK AI | NO | Interno. |
| `erpcondicionpagocod` | VARCHAR(50) UNIQUE | NO | `codigo` / `Codigo`. |
| `erpcondicionpagonombre` | VARCHAR(150) | NO | `nombre` / `Nombre`. |
| `erpcondicionpagodescripcion` | VARCHAR(255) | SI | `descripcion`. |
| `erpcondicionpagotipo` | INT | SI | `Tipo`. |
| `erpcondicionpagoedicionfija` | TINYINT(1) | NO | `EdicionFija`. |
| `erpcondicionpagoexigedocsdiferidos` | TINYINT(1) | NO | `ExigeDocumentosDiferidos`. |
| `erpcondicionpagoporcentajeinteres` | DECIMAL(9,4) | NO | `PorcentajeInteres`. |
| `erpcondicionpagoctaproveedores` | VARCHAR(50) | SI | `CtaProveedores`. |
| `erpcondicionpagoctadeudoresventas` | VARCHAR(50) | SI | `CtaDeudoresPorVentas`. |
| `erpcondicionpagoctadisponibilidad` | VARCHAR(50) | SI | `CtaDisponibilidad`. |
| `erpcondicionpagoetaetd` | INT | SI | `ETA/ETD`. |
| `erpcondicionpagoactivo` | TINYINT(1) | NO | `activo` / `Activo`. |
| `sincfechahora` | DATETIME | SI | Ultima sincronizacion. |
| auditoria | columnas estandar | NO/SI | Patron proyecto. |

Indices:

- `uq_erpcondicionespago_cod`;
- `idx_erpcondicionespago_activo`;
- `idx_erpcondicionespago_nombre`.

### 10.2 `erpcondicionespagodetalle`

Detalle desde `CondicionPagoItems`.

Columnas propuestas:

| Columna | Tipo logico | NULL | Fuente |
|---|---:|---|---|
| `erpcondicionpagodetid` | INT PK AI | NO | PK. |
| `erpcondicionpagoid` | INT FK | NO | Cabecera. |
| `erpcondicionpagodetlinea` | INT | NO | Orden local. |
| `erpcondicionpagodetfecha` | DATE | SI | `Fecha`. |
| `erpcondicionpagodettipo` | INT | SI | `Tipo`. |
| `erpcondicionpagodetdias` | INT | NO | `Dias`. |
| `erpcondicionpagodetporcentaje` | DECIMAL(9,4) | NO | `Porcentaje`. |
| auditoria | columnas estandar | NO/SI | Patron proyecto. |

Reglas:

- La suma de porcentajes por condicion deberia ser 100 cuando ERP lo informe completo.
- No editar manualmente.

### 10.3 `erpcondicionespagolog`

Log espejo del maestro.

## 11. Dependencias y orden dentro del incremental

Orden recomendado:

1. Alter `usuarios`.
2. Alter `invitems`.
3. Alter `pptocompra`.
4. Crear `usuarioscentroscosto` y log.
5. Crear `funcionarios` y log.
6. Crear `aprobadoresperiodoinactividad` y log.
7. Crear `erpcondicionespago`, detalle y log.
8. Crear `erpproveedores`, log y relacion proveedor-condicion.

Motivo:

- proveedores depende de condiciones de pago;
- PreOC depende de proveedores y condiciones;
- `pptocompra` depende de nuevos permisos de aprobacion PreOC en usuarios a nivel funcional;
- REQ depende de usuarios-centros, funcionarios, inactividad e items.

## 12. Validaciones antes de escribir SQL

Checklist:

- Confirmar nombres finales de tablas `erpproveedores*` y `erpcondicionespago*`.
- Confirmar si los logs de tablas espejo guardan request completo, solo payload normalizado o ambos.
- Confirmar estrategia si proveedor referencia condicion de pago no sincronizada.
- Confirmar si `usuarioscentroscosto` default unico se hara con columna generada o validacion SP/BE.

## 13. Fuera de alcance del incremental 07

- Tablas `reqcompras*`.
- Tablas `reqaprobados*`.
- Tablas `preoc*`.
- SP de aprobacion REQ/PreOC.
- SP de reserva/confirmacion/reversa presupuestaria.
- Pantallas FE.
- Ejecucion real de sincronizaciones ERP.

## 14. Resultado esperado

Al cerrar este incremental, el proyecto deberia tener listas las bases compartidas para pasar al DDL de REQ:

- usuarios con permisos de compra;
- usuarios relacionados a centros;
- funcionarios disponibles;
- aprobadores con periodos de inactividad;
- presupuestos con responsables PreOC;
- items con marca local;
- proveedores y condiciones de pago sincronizables como espejo.
