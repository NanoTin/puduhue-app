# Changelog

Todas las modificaciones relevantes deben registrarse aquí.

## [Unreleased]
### Added
- Se agrego la columna `estanqueclientecod` a `retirolechedetalle` en el script de creacion de tabla y en el script de `ALTER TABLE`.
- Se agrego la visualizacion de `estanqueclientecod` en el listado de `retiroleche`.
- Se agrego `estanqueclientecod` a las consultas, respaldos JSON y resultados de los SP de `retirolechedetalle`.
- Se agrego `estanqueclientecod` al flujo web de `retiroleche` para crear, editar, listar y exportar.
- Se agrego una restriccion unica para `fundosestanquesclientes.estanqueclientecod`.
- Se agrego el script `database/alter_table/04_api_externa_tokens.sql` con las tablas `usuariosapitokens` y `apirequestlog`.
- Se agrego la accion web para generar tokens API desde el listado de usuarios.
- Se agrego un modal en `usuarios_listar.php` para generar, visualizar una vez y copiar el token API.
- Se agrego el front controller publico `apps/api-php/index.php`.
- Se agrego `.htaccess` en `apps/api-php` para hosting compartido.
- Se agregaron helper, middleware y router para la API externa versionada bajo `/api/v1/`.
- Se agrego logging central de requests API en `apirequestlog`.
- Se agrego el endpoint `POST /api/v1/prodleche-detalle/query`.
- Se agrego la base funcional del endpoint `POST /api/v1/suplanimal-detalle/query`.

### Changed
- La pantalla `fundosestanquesclientes_editar.php` ahora muestra `estanqueclientecod` solo en modo lectura y solo permite activar o desactivar la asociacion.
- El SP `sp_fundosestanquesclientes_editar` ya no modifica `estanqueclientecod`; solo actualiza el estado activo y auditoria.
- El listado `retiroleche_listar.php` muestra `estanqueclientecod` como texto plano, sin formato numerico.
- El servicio `FundosestanquesclientesService` ahora incluye `estanqueclientecod` en las opciones para formularios de seleccion.
- El router web ahora acepta la accion `usuarios/generar-token-api`.
- `UsuariosService` ahora genera tokens API seguros, calcula hash HMAC y persiste en `usuariosapitokens`.
- La API externa ahora responde JSON estandar con `status`, `message`, `data` y `meta`.
- El router de API ahora soporta tanto `/v1/*` como `/api/v1/*`.
- El front controller de `apps/api-php` ya no depende de `vendor/autoload.php`, evitando el bloqueo por platform check de Composer en el subdominio API.
- La captura del header `Authorization` en la API se reforzo para entornos shared hosting con Apache/cPanel.

### Fixed
- Se valido en los SP de `fundosestanquesclientes` que no se pueda insertar un `estanqueclientecod` duplicado.
- Se mantuvo consistente el `colspan` del listado de `retiroleche` tras agregar la nueva columna.

### Removed
- Se elimino la edicion de `estanqueclientecod` desde la pantalla de edicion de asociaciones estanque-cliente.

### Security
- Sin cambios de seguridad.

## [0.1.0] - YYYY-MM-DD
### Added
- Inicialización del proyecto (estructura base, docs y playbook)
