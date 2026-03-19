# Puduhue App – Sistema Multi-Empresa (PHP + MariaDB)

Sistema web multi-empresa para la gestión de:

- Producción de Leche
- Retiro de Leche
- Suplementación Animal

con integración a un ERP externo (**Finnegans**), autenticación basada en usuarios y perfiles, y uso intensivo de Stored Procedures en MariaDB.

El desarrollo es 100% PHP, pensado para ejecutarse en un **hosting compartido con cPanel**, sin SSH, utilizando solo extensiones PHP disponibles en el servidor.
- No se usa Composer en producción; las clases clave (`Env` en `src/Config/Env.php`, `Database` en `src/Config/Database.php`, `Logger` en `src/Helpers/Logger.php`) son globales y se cargan vía `require_once` donde haga falta (controladores/servicios).
- Clases base activas: `Env` (`src/Config/Env.php`), `Database` (`src/Config/Database.php`) y `Logger` (`src/Helpers/Logger.php`, namespace `App\\Helpers`) se cargan con `require_once` en controladores y servicios; el autoload no es confiable en hosting compartido.

---

## 1. Tecnologías y restricciones

- **Lenguaje:** PHP 8.4.x
- **Base de datos:** MariaDB 10.11.15 (compatible MySQL)
- **Servidor:** Hosting compartido con cPanel, administración vía phpMyAdmin
- **Extensiones PHP disponibles:**
  - `mysqli`
  - `curl`
  - `mbstring`
- **Acceso servidor:** Sin SSH (los scripts SQL se ejecutan manualmente vía phpMyAdmin)

---

## 2. Módulos funcionales

### 2.1. Transacciones

1. **Producción de Leche**
   - Registra la producción por tipo de leche, litros, vacas y cálculo de litros/vaca.
   - Solo fundos de tipo = 1.
   - Integra con Finnegans (Producción de Leche).

2. **Retiro de Leche**
   - Registra los retiros de leche realizados por el camión de la planta procesadora.
   - Sube imagen del comprobante (voucher) por estanque.
   - Solo fundos de tipo = 1.
   - Las imágenes se almacenan en `uploads/retiroleche/img/`.

3. **Suplementación Animal**
   - Registra el consumo de productos de bodega por categoría de animal y lote.
   - Calcula dosis por animal.
   - Integra con Finnegans (Suplementación).

### 2.2. Configuración y Maestros

- **Administración**
  - Empresas
  - Usuarios
  - Usuarios Empresas
  - Usuarios Fundos
  - Menús
  - Perfiles
  - Cambio de contraseña

- **Maestros**
  - Fundos
  - Fundos Estanques
  - Tipos de Leche
  - Inventario Bodegas
  - Inventario Ítems (Productos)
  - Inventario Categorías Ganado
  - Clientes

**Regla de negocio importante:**

- Las pantallas de **Maestros / Administración** (Empresas, Usuarios, Fundos, Menús, Perfiles, etc.) **no se filtran por usuario logueado**, empresa o fundo.
- Las asociaciones usuario–empresa/usuario–fundo se aplican solo en **módulos de transacciones** (Producción de Leche, Retiro de Leche, Suplementación, etc.).

---

## 3. Integración con Finnegans

### 3.1. Endpoints

- **Producción de Leche**  
  `https://api.finneg.com/api/PDHIntegracionProduccionLeche?ACCESS_TOKEN=?`

- **Suplementación Animal**  
  `https://api.finneg.com/api/PDHIntegracionSuplementacion?ACCESS_TOKEN=?`

Los datos se envían en formato **JSON en el cuerpo (BODY)** de la petición.

### 3.2. Token

- Endpoint para token:  
  `https://api.finneg.com/api/oauth/token?grant_type=?&client_id=?&client_secret=?`

- Parámetros (`grant_type`, `client_id`, `client_secret`) se obtienen desde el archivo `.env` --> (grant_type,client_id,client_secret,erp_api_url,erp_auth_url).

- Respuesta: **texto plano de 36 caracteres** (ejemplo: GUID).

- Vigencia del token: **5 minutos**.

**Si el token expira o no es válido:**

```json
{
  "status": 400,
  "error": "invalid token"
}
```
- Este error no se muestra al usuario.
- Flujo ante token inválido:
    1. Solicitar un nuevo token.
    2. Actualizar tabla erptokenactivo.
    3. Reintentar la integración.

### 3.3. Tabla erptokenactivo
Tabla que almacena solo el último token vigente para Finnegans.

Flujo general:

Antes de integrar:

Consultar erptokenactivo.

Si está vacío:

Obtener nuevo token y guardarlo.

Si existe:

Usar el token en el endpoint.

Si Finnegans devuelve status = 400:

Volver a pedir token, actualizar tabla y reintentar.

Ejemplo de respuesta correcta de integración:

```json
{
  "documento": "PRODLECH - 10414",
  "id": "PL-000151",
  "message": "created",
  "status": 200
}
```
En caso de error de negocio (sí se muestra al usuario):

```json
{
  "error": "Internal Server Error: ...La identificacion externa PL-000151 ya existe para otra transaccion",
  "status": 500
}
```
### 3.4. Logs de integración (APILog)
Carpeta: storage/APILog/

Archivos (ejemplos):

trn_prodleche_{timestamp}.log

trn_suplementacion_{timestamp}.log

Contenido:

Token activo y/o nuevo token (se puede ofuscar parcialmente).

JSON enviado al ERP.

JSON recibido del ERP.

Mensajes de error.

No se almacenan logs de integración en tablas de BD.

En caso de error en la integración:

Mostrar el error al usuario (cuando aplique).

Hacer ROLLBACK de la transacción interna (tablas propias).

Generar un log en storage/LOGS/.

### 3.5. DTOs para integración con Finnegans
Para mejorar la mantenibilidad se utilizan DTOs (Data Transfer Objects) específicos por endpoint.

Ubicación: src/api-external/DTOs/

Ejemplos:

ProduccionLecheRequestDTO

ProduccionLecheResponseDTO

SuplementacionRequestDTO

SuplementacionResponseDTO

Cada DTO:

Representa exactamente la estructura JSON definida para ese endpoint.

Expone métodos como toArray() / fromArray() para mapear hacia/desde estructuras usadas en json_encode().

### 3.6. Producción de Leche - Regla principal de integración

La integración se ejecuta como un ciclo FOR por “Tipo Leche”, tomando los registros desde prodlechedetalle (detalle).

Solo se integran los detalles donde los litros > 0 (campo prodlechetotlitros).

Por cada Tipo de Leche válido, se genera 1 JSON y se envía 1 request al EndPoint.

En otras palabras:
1 cabecera (prodleche) + N detalles (prodlechedetalle) ⇒ N envíos (uno por tipo de leche con litros > 0).

- Datos base para armar el JSON (cabecera)

Antes del FOR, se obtienen/definen los datos “comunes” (cabecera) desde prodleche + maestros relacionados:

EmpresaID: viene de empresas.empresaiderp (JOIN prodleche ↔ empresas)

Fecha: viene de prodleche.prodlechefecha (la selecciona el usuario en pantalla)

EstablecimientoCodigo: prodleche.pl_erpestablecimientocod

LoteCodigo: prodleche.pl_erplotecod

HaciendaCategoriaCodigo: prodleche.pl_erpleche_invcateganimalcod

IdentificacionExterna: se genera internamente (formato a definir)

Descripcion: se arma concatenando: “Fundo Nombre” + “Tipo de leche” + “Registrado por: Usuario”

Los demás campos fijos/por defecto:

CampanaCodigo = null

NumeroDocumento = ""

Tropa = ""

TransaccionTipo = "OPER"

TransaccionSubtipoCodigo = "PRODLECH"

OperacionCotizaciones = []

- Ciclo FOR (por cada detalle / tipo de leche)

Para cada registro de prodlechedetalle asociado a la cabecera (prodleche) que cumpla:

prodlechedetalle.prodlechetotlitros > 0

Se arma el JSON reutilizando la cabecera, y cargando estos datos propios del detalle:

Cabezas: prodlechedetalle.pldetvacas

Dentro de MovimientoHaciendaProduccionLeche[0]:

ProductoID: prodlechedetalle.prodlechetipoid

DepositoIDOrigen: prodleche.pl_erpleche_invbodegacod

Litros: prodlechedetalle.prodlechetotlitros

El resto queda en cero/null según JSON.

- JSON para EndPoint

```json
{
    "EmpresaID": "se obtiene desde el maestro de empresas, columna 'empresaiderp'. JOIN entre 'prodleche' y 'empresas'. Ej.: 'PRUEBA39'",
    "Fecha": "seleccionado por el usuario en pantalla. Columna 'prodlechefecha'. Ej.: '2025-11-28'",
    "EstablecimientoCodigo": "Se debe obtener de la tabla 'prodleche', columna 'pl_erpestablecimientocod'. Ej.:'LRD'",
    "LoteCodigo": "Se debe obtener de la tabla 'prodleche', columna 'pl_erplotecod'. Ej.:'LRD'",
    "HaciendaCategoriaCodigo": "Se debe obtener de la tabla 'prodleche', columna 'pl_erpleche_invcateganimalcod'. Ej.:'VACA EN LECHE'",
    "CampanaCodigo": null,
    "NumeroDocumento": "",
    "IdentificacionExterna": "Código generad internamente. Tabla/Columna prodlechedetalle.prodlechecod --> Ej.: 'P1834040433'",
    "Tropa": "",
    "Cabezas": "Tabla/Columna prodlechedetalle.pldetvacas --> Ej.: 1170",
    "TransaccionTipo": "OPER",
    "TransaccionSubtipoCodigo": "PRODLECH",
    "Descripcion": "Se debe concatenar 'Fundo Nombre' + 'Tipo de leche' + Registrado por: 'Usuario'",
    "OperacionCotizaciones": [],
    "MovimientoHaciendaProduccionLeche": [
        {
            "ProductoID": "Tabla/Columna prodlechedetalle.prodlechetipoid --> Ej.:'VLE001'",
            "DepositoIDOrigen": "Tabla/Columna prodleche.pl_erpleche_invbodegacod --> Ej.:'BI2-2-2-01'",
            "OrganizacionIDStock": "",
            "Litros": "Tabla/Columna prodlechedetalle.prodlechetotlitros --> Ej.:23400",
            "Grasa": 0.000000,
            "UFC": 0.000000,
            "Acidez": 0.000000,
            "Proteinas": 0.000000,
            "Temperatura": 0.000000,
            "CelSomaticas": 0.000000,
            "PartidaID": null
        }
    ]
}
```
- Resultado esperado del proceso

Si una cabecera tiene, por ejemplo, 3 tipos de leche y solo 2 tienen litros > 0:

Se envían 2 JSON (uno por cada tipo válido).

Cada envío queda identificado por IdentificacionExterna (único por transacción enviada).

### 3.7. Suplementación Animal - Regla principal de integración


- 3.7.1 Integración ERP: regla del FOR por agrupación
- 3.7.1.1 Qué se agrupa y por qué

Debe existir un ciclo FOR que recorra el detalle agrupado por:

Categoría Animal

Total Animales

Para cada grupo se debe enviar 1 JSON al ERP, donde:

Lo agrupado (cabecera del grupo) va en Items[] (usualmente 1 ítem por JSON).

El detalle de productos del grupo va en MovimientoHaciendaSuplementacionInsumo[] (uno por producto de ese grupo).

Importante: el “Total Animales” forma parte del agrupamiento, por lo tanto si la misma categoría aparece con distinto total de animales, se generan JSON separados.

- 3.7.1.2 Datos base del JSON (comunes a todo el envío)

Antes del FOR, preparar datos comunes desde suplanimal + maestros relacionados:

EmpresaID: desde empresas.empresaiderp (JOIN con maestro)

Fecha y FechaComprobante: desde suplanimalfecha

IdentificacionExterna: generado internamente (único por envío)

TransaccionTipo = "OPER"

TransaccionSubtipoCodigo = "SUPXCAB"

ResumirInsumos = false

NumeroComprobante = ""

TransaccionID = 0

Descripcion:

Concatenar Fundo Nombre + (contexto del movimiento) + "Registrado por: Usuario"

(en este módulo, el texto “Tipo de leche” no aplica; pero tú lo dejas como regla conceptual: se reemplaza por “Suplementación” o por la categoría del grupo, por ejemplo).

- 3.7.1.3 Por cada grupo (Categoría + TotalAnimales): construir Items[]

Para cada grupo:

Lote: desde suplanimal.sup_erplotecod

CodigoCategoriahacienda: desde suplanimaldetalle.sup_erpinvcateganimalcod (del grupo)

Cab: suplanimaldetalle.totalanimales (del grupo)

Kilos: cálculo totalanimales * 500

CantidadCertificada: suma del grupo SUM(totalconsumido)

EventoHaciendaID = "SUPLEMENTACION"

Los demás campos null según JSON.

- 3.7.1.4 Por cada fila del grupo: construir MovimientoHaciendaSuplementacionInsumo[]

Por cada detalle dentro del grupo:

ProductoID: suplanimaldetalle.sup_erpinvitemcod

Dosis: suplanimaldetalle.dosisporanimal

CantidadStock1: suplanimaldetalle.totalconsumido

DepositoIDOrigen: suplanimal.sup_erpinvbodegacod

Resto campos fijos/null según JSON.

- 3.7.1.5 Resultado esperado

Si para una misma cabecera tienes:

Categoría A con TotalAnimales = 120 y 5 productos → 1 JSON con:

Items[0] con Cab=120, CantidadCertificada=sum(consumo 5 productos)

MovimientoHaciendaSuplementacionInsumo con 5 objetos

Categoría A con TotalAnimales = 150 (aunque sea el mismo día y mismo fundo) → otro JSON separado.

- 3.7.2. JSON para EndPoint
```json
{
    "EmpresaID": "se obtiene desde el maestro de empresas, columna 'empresaiderp'. JOIN entre 'prodleche' y 'empresas'. Ej.: 'PRUEBA39'",
    "Fecha": "seleccionado por el usuario en pantalla. Columna 'suplanimalfecha'. Ej.: '2025-11-28'",
    "Descripcion":"Se debe concatenar 'Fundo Nombre' + 'Tipo de leche' + Registrado por: 'Usuario'",
    "NumeroComprobante": "",
    "ResumirInsumos": false,
    "TransaccionTipo": "OPER",
    "TransaccionSubtipoCodigo": "SUPXCAB",
    "IdentificacionExterna": "Código a generar internamente. A definir. Ej.: 'AppSupl-0000000151'",
    "TransaccionID": 0,
    "FechaComprobante":  "seleccionado por el usuario en pantalla. Columna 'suplanimalfecha'. Ej.: '2025-11-28'",
    "Items": [
        {
            "Lote": "Se debe obtener de la tabla 'suplanimal', columna 'sup_erplotecod'. Ej.:'LRD'",
            "CodigoCategoriahacienda": "Se debe obtener de la tabla 'suplanimaldetalle', columna 'sup_erpinvcateganimalcod'. Ej.:'VC_PREPARTO'",
            "Cab":"Tabla 'suplanimaldetalle' columna 'totalanimales'. Ej.:120.000000",
            "Kilos": "Calculo de 'totalanimales' x 500. Ej.: 60000",
            "OrganizacionID": null,
            "Tropa": null,
            "EventoHaciendaClasificacionID": null,
            "CantidadCertificada": "Suma del grupo detalle columna 'totalconsumido'. Ej.:4232.000000",
            "EventoHaciendaID": "SUPLEMENTACION"
        }
    ],
    "MovimientoHaciendaSuplementacionInsumo": [
        {
            "ProductoID":"Código del Producto en el ERP. Tabla 'suplanimaldetalle' columna 'sup_erpinvitemcod'.  Ej.:'APR101'",
            "Dosis": "Dosis por animal. Tabla 'suplanimaldetalle' columna 'dosisporanimal'. Ej.: 16.560000",
            "CantidadStock1":  "Consumido. Tabla 'suplanimaldetalle' columna 'totalconsumido'. Ej.: 2600.000000",
            "CantidadStock2": 0.000000,
            "DepositoIDOrigen": "Tabla 'suplanimal' columna 'sup_erpinvbodegacod'. Ej.: 'BI3-1-2-02'",
            "OrganizacionIDStock": null,
            "PartidaID": null,
            "Tipo": 0,
            "TransaccionID": 0
        },
        {
            "ProductoID": "Código del Producto en el ERP. Tabla 'suplanimaldetalle' columna 'sup_erpinvitemcod'.  Ej.:'CON005'",
            "Dosis": "Dosis por animal. Tabla 'suplanimaldetalle' columna 'dosisporanimal'. Ej.: 3.100000",
            "CantidadStock1": "Consumido. Tabla 'suplanimaldetalle' columna 'totalconsumido'. Ej.: 372.000000",
            "CantidadStock2": 0.000000,
            "DepositoIDOrigen": "Tabla 'suplanimal' columna 'sup_erpinvbodegacod'. Ej.: 'BI3-1-2-02'",
            "OrganizacionIDStock": null,
            "PartidaID": null,
            "Tipo": 0,
            "TransaccionID": 0
        },
        {
            "ProductoID": "Código del Producto en el ERP. Tabla 'suplanimaldetalle' columna 'sup_erpinvitemcod'.  Ej.:'SMP001'",
            "Dosis": "Dosis por animal. Tabla 'suplanimaldetalle' columna 'dosisporanimal'. Ej.: 0.200000",
            "CantidadStock1": "Consumido. Tabla 'suplanimaldetalle' columna 'totalconsumido'. Ej.: 24.000000",
            "CantidadStock2": 0.000000,
            "DepositoIDOrigen": "Tabla 'suplanimal' columna 'sup_erpinvbodegacod'. Ej.: 'BI3-1-2-02'",
            "OrganizacionIDStock": null,
            "PartidaID": null,
            "Tipo": 0,
            "TransaccionID": 0
        },
        {
            "ProductoID": "Código del Producto en el ERP. Tabla 'suplanimaldetalle' columna 'sup_erpinvitemcod'.  Ej.:'SMP002'",
            "Dosis": "Dosis por animal. Tabla 'suplanimaldetalle' columna 'dosisporanimal'. Ej.: 0.300000",
            "CantidadStock1": "Consumido. Tabla 'suplanimaldetalle' columna 'totalconsumido'. Ej.: 36.000000",
            "CantidadStock2": 0.000000,
            "DepositoIDOrigen": "Tabla 'suplanimal' columna 'sup_erpinvbodegacod'. Ej.: 'BI3-1-2-02'",
            "OrganizacionIDStock": null,
            "PartidaID": null,
            "Tipo": 0,
            "TransaccionID": 0
        },
        {
            "ProductoID": "Código del Producto en el ERP. Tabla 'suplanimaldetalle' columna 'sup_erpinvitemcod'.  Ej.:'ACO029'",
            "Dosis": "Dosis por animal. Tabla 'suplanimaldetalle' columna 'dosisporanimal'. Ej.: 10.000000",
            "CantidadStock1": "Consumido. Tabla 'suplanimaldetalle' columna 'totalconsumido'. Ej.: 1200.000000",
            "CantidadStock2": 0.000000,
            "DepositoIDOrigen": "Tabla 'suplanimal' columna 'sup_erpinvbodegacod'. Ej.: 'BI3-1-2-02'",
            "OrganizacionIDStock": null,
            "PartidaID": null,
            "Tipo": 0,
            "TransaccionID": 0
        }
    ]
}
```

## 4. Arquitectura general
La arquitectura se organiza en capas:

Frontend Web Interno (apps/web-php)

Controladores Web (src/Controllers/Web)

API externa (apps/api-php + src/Controllers/Api)

Servicios (src/Services)

Modelos (src/Models)

Núcleo (src/Core)

Auth & Middleware (src/Auth, src/Middleware)

Integraciones externas (src/api-external)

Helpers (src/Helpers)

Ruteo Web (src/Routes/web.php)

### 4.1. Frontend Web Interno (PHP)
Ubicación: apps/web-php/

Uso:

Archivos PHP que generan HTML.

No consumen JSON ni hacen llamadas HTTP internas a la API.

Incluyen head.php, menu.php y footer.php.

Ejemplos de vistas:

login.php

UI: Las vistas frontend deben mostrar retroalimentación de operaciones mediante toasts de éxito/error (Success/Failure Toasts) en lugar de alertas intrusivas.
Success/Failure Toasts:
- Usa toasts no intrusivos para mostrar éxito/error en operaciones del frontend interno (evita alerts bloqueantes). Reutiliza el helper JS existente si aplica.

empresas_listar.php, empresas_crear.php, empresas_editar.php

prodleche.php

retiroleche.php

suplanimal.php

dashboard.php, etc.

Flujo interno (Frontend Web):

apps/web-php/*.php (vista)
↓ incluye head.php y menu.php
src/Controllers/Web/*Controller.php
↓
src/Services/*Service.php
↓
src/Models/*Model.php
↓
Stored Procedures (PDO vía Database::callSpMaint() / callSpQuery())
↓
Resultados como array PHP (no JSON para frontend interno).

### 4.2. API Externa (REST)
Ubicación:

Entrada: apps/api-php/index.php

Controladores: src/Controllers/Api/

Uso:

Expuesta a clientes externos (Power BI, futuras apps móviles, integraciones).

Devuelve JSON estándar.

Protegida con JWT y/o API Keys.

Flujo (API Externa):

Cliente externo / App móvil / Power BI
↓ (HTTP Request JSON)
src/Controllers/Api/*ApiController.php
↓
src/Services/*Service.php
↓
src/Models/*Model.php
↓
Stored Procedures (PDO)
↓
Respuesta JSON estándar (con Response::json()).

### 4.3. Capa Reutilizable
Compartida entre Frontend interno, API externa y futuras aplicaciones:

src/Config/Database.php

src/Models/*

src/Services/*

Stored Procedures en MariaDB

DTOs de integración (src/api-external/DTOs)

### 4.4. Front Controller Web + Router (index.php + src/Routes/web.php)
El frontend interno utiliza un Front Controller y un Router dedicados:

Entrada principal Web:
apps/web-php/index.php

Carga Env.php, Database.php, SessionManager, AuthMiddleware, Logger y src/Routes/web.php via `require_once` (sin autoload confiable en hosting).

Inicia sesión PHP, aplica validación básica (redirige a login.php si no hay usuario autenticado) y delega el flujo en `handleWebRequest()`.

Router Web:
src/Routes/web.php

Normaliza `?route=<modulo>/<accion>` (`dashboard` por defecto), arma `allowedMenuRoutes` desde `menu.json`, permite siempre las acciones `crear` / `editar` / `anular` aunque no estén en el menú (formularios lanzados desde otras vistas) y hace `require_once` del controlador si la clase no está cargada. Fallback 404 cuando no hay coincidencia.

Rutas típicas (ejemplo):

php
Copiar código
$router->add('GET',  'empresas/listar', [EmpresasController::class, 'listar']);
$router->add('GET',  'empresas/crear',  [EmpresasController::class, 'crearForm']);
$router->add('POST', 'empresas/crear',  [EmpresasController::class, 'crearPost']);
$router->add('GET',  'empresas/editar', [EmpresasController::class, 'editarForm']);   // usa ?id=...
$router->add('POST', 'empresas/editar', [EmpresasController::class, 'editarPost']);
$router->add('POST', 'empresas/anular', [EmpresasController::class, 'anularPost']);
El parámetro route en la URL define módulo/acción:

?route=empresas/listar

?route=empresas/crear

?route=empresas/editar&id=123

etc.

El router:

Verifica sesión vía AuthMiddleware::requireAuth() para rutas protegidas.

Permite rutas públicas específicas (ej. login/mostrar, login/procesar).

Implementa fallback 404 si la ruta no existe.

Soporta modo “SPA-parcial”:

Si existe $_GET['partial'] === '1' o X-Requested-With: XMLHttpRequest,

el controlador devuelve solo el fragmento HTML central,

sin incluir head.php y menu.php.

Esto permitirá en el futuro navegación AJAX sin recargar todo el layout.

## 5. Formato estándar de respuestas API
Solo se aplica a Controladores API (src/Controllers/Api/*).

Formato:

json
Copiar código
{
  "status": 200,
  "message": "OK",
  "totReg": 0,
  "data": []
}
status: código lógico (200, 400, 401, 403, 500, etc.).

message: descripción breve (“OK”, “Parámetros inválidos”, etc.).

totReg: cantidad de registros en data (se calcula en PHP con count()).

data: arreglo de registros o estructura específica.

Notas:

Los Stored Procedures de mantenimiento:

No devuelven datos en JSON.

Solo devuelven JSON de validación en p_out_json (códigos y mensajes).

Los Stored Procedures de consulta:

Devuelven filas mediante SELECT.

PDO obtiene las filas.

El backend:

arma data a partir de esas filas,

calcula totReg,

construye la respuesta estándar para la API.

## 6. Stored Procedures (SP) – Convenciones
### 6.1. Parámetros comunes
Todos los SP (mantenimiento y consulta) comparten la firma:

p_in_json

p_in_usuarioid

p_in_dispositivo

p_in_ip

p_out_json

### 6.2. Reglas generales
Validar que p_in_json no esté vacío.

Validar que el usuario exista, esté vigente y no bloqueado.

SP de mantenimiento:

Validan duplicados al insertar.

Validan existencia al actualizar/anular/eliminar.

Retornan JSON en p_out_json con:

códigos de respuesta,

mensajes descriptivos,

(opcional) ID generado.

SP de consulta:

Devuelven filas mediante SELECT.

No retornan datos en p_out_json (solo meta/error si se requiere).

### 6.3. Nomenclatura
Crear: sp_<modulo>_insertar

Actualizar: sp_<modulo>_editar

Anular (baja lógica): sp_<modulo>_anular

Eliminar (DELETE físico, solo cuando negocio lo permita): sp_<modulo>_eliminar

Listar encabezados/resumen: sp_<modulo>_listar_resumen

Listar detalle: sp_<modulo>_listar_detalle

Consultar por ID encabezado: sp_<modulo>_consulta_por_id_resumen

Consultar por ID detalle: sp_<modulo>_consulta_por_id_detalle

### 6.4. Manejo de transacciones
Los BEGIN / COMMIT / ROLLBACK NO van dentro de los Stored Procedures.

Las transacciones se manejan desde PHP, en src/Config/Database.php:

Abre la transacción antes de llamar al SP de mantenimiento (callSpMaint()).

Hace COMMIT si el SP devuelve status = 200.

Hace ROLLBACK si el SP devuelve error o lanza excepción.

Los SP asumen que se ejecutan dentro de una transacción externa.

### 6.5. SP de anulación (sp_<modulo>_anular)
El JSON de entrada (p_in_json) solo contiene la PK (o PK compuesta).

Dentro del SP:

Se valida que el registro exista.

No se hace DELETE físico.

Se actualiza la columna de vigencia/activo (...vig, ...activo) a FALSE o 0.

Se actualizan campos de auditoría de edición (auditedicion*) usando:

p_in_usuarioid

p_in_dispositivo

p_in_ip

NOW()

### 6.6. Registro en tablas LOG desde SP de mantenimiento
Para tablas con LOG asociado (Generic Log Table en Tables.csv):

SP que deben insertar en tabla LOG:

sp_<modulo>_insertar

sp_<modulo>_editar

sp_<modulo>_anular

sp_<modulo>_eliminar (cuando corresponde)

La tabla LOG (<tabla>log) registra:

Tipo de operación: 'INSERT', 'EDIT', 'ANULAR', 'ELIMINAR'.

Usuario, dispositivo, IP, fecha/hora.

logparamjson: copia del p_in_json recibido.

logregbkpjson:

En insertar: siempre NULL (no hay estado previo).

En editar: JSON con el estado previo del registro.

En anular/eliminar: se puede guardar estado previo o un JSON simplificado (el generador deja TODO si aplica).

Las tablas LOG solo reciben INSERT.

### 6.7. Filtros mínimos en sp_<modulo>_listar_*
SP de listado (_listar_resumen, _listar_detalle) deben soportar al menos un filtro opcional en p_in_json.

Filtros típicos (texto):

filtroRazonSocial (Empresas)

filtroUsuarioNombre (Usuarios)

filtroInvItemDsc (Ítems de Inventario)

Si el filtro viene vacío/NULL, no se restringe por ese campo.

Se usa LIKE '%valor%' para columnas de texto.

### 6.8. Reglas para sp_<modulo>_listar_* basadas en Columns.csv
En Columns.csv se definen:

spListar_Select_Column:

"TRUE" → incluir columna en SELECT.

spListar_Filter_Column:

"TRUE" → definir filtro opcional filtro<NombreColumna> en p_in_json.

Para VARCHAR/TEXT → LIKE CONCAT('%', valor, '%').

spListar_Select_JOIN_Column_Name:

Si tiene valor (ej. empresas.razonsocial), el SP:

agrega el JOIN correspondiente por la FK,

incluye la columna derivada en el SELECT con alias claro.

Fechas en tablas de transacciones

SP de listado para encabezados de transacciones deben soportar:

filtroFechaDesde

filtroFechaHasta

Implementar WHERE fecha BETWEEN desde AND hasta usando:

desde = '1900-01-01' si viene NULL.

hasta = CURRENT_DATE/NOW() si viene NULL.

Columnas de auditoría en listados

Si "Audit Columns" = TRUE en Tables.csv, los SP de listado incluyen columnas de auditoría en el SELECT, salvo que Columns.csv indique lo contrario.

### 6.9. PK autoincremental y logregbkpjson
En sp_<modulo>_insertar:

La PK numérica (<tabla>id INT AUTO_INCREMENT) no va en el JSON.

El ID lo genera la BD (AUTO_INCREMENT).

El SP puede usar LAST_INSERT_ID() y opcionalmente incluirlo en p_out_json.

Al insertar en la tabla LOG:

logregbkpjson = NULL.

En sp_<modulo>_editar:

Antes del UPDATE, leer registro actual por PK.

Convertirlo a JSON.

Insertar en LOG con logregbkpjson = <JSON previo>.

Luego ejecutar el UPDATE en tabla principal.

## 7. Auditoría y logs
### 7.1. Columnas de auditoría
Tablas principales (cuando Audit Columns = TRUE en Tables.csv):

auditcreacionusuarioid

auditcreaciondispositivo

auditcreacionip

auditcreacionfechahora

auditedicionusuarioid

auditediciondispositivo

auditedicionip

auditedicionfechahora

Reglas:

Campos de auditoría no vienen en p_in_json.

SP insertar:

rellena solo auditcreacion* con:

p_in_usuarioid, p_in_dispositivo, p_in_ip, NOW().

SP editar / anular / eliminar:

rellenan solo auditedicion* con los mismos parámetros.

Tablas usuariosempresas y usuariosfundos:

Solo columnas de creación.

Al DELETE físico, se copia el registro a:

usuariosempresashist

usuariosfundoshist

### 7.2. Tablas LOG (ejemplo empresaslog)
Columnas típicas:

empresaid (PK principal)

logid

logusuarioid

logdispositivo

logip

logfechahora

logtipo

logparamjson

logregbkpjson

Regla general: nunca se hace DELETE/UPDATE en tablas LOG.

### 7.3. Carpetas de logs
storage/LOGS/: logs internos de la aplicación (errores, excepciones, etc.).

storage/APILog/: logs de integración con ERP (sin token completo).

### 7.4. Tablas HIST (históricas)
Tablas especiales que terminan en HIST, usadas para respaldo histórico en DELETE físico:

usuariosempresas → usuariosempresashist

usuariosfundos → usuariosfundoshist

Regla:

SP sp_usuariosempresas_eliminar y análogos:

Copian el registro actual a tabla HIST:

Campos originales con prefijo ori_.

Campos de auditoría histórica (hist_auditusuarioid, etc.).

Ejecutan DELETE en tabla principal.

El resto de las tablas se manejan con sp_<modulo>_anular (baja lógica).

## 8. Seguridad, Login y JWT
Login por RUT (Excluir usuario ROOT) y contraseña.

Validación de DV y formato RUT (Excluir usuario ROOT.): XXXXXXXX-V (sin puntos).

Contraseña:

Mínimo 5 caracteres.

1 mayúscula.

1 número.

1 carácter especial.

Siempre almacenada encriptada.

Bloqueo:

Usuario se bloquea tras 3 intentos fallidos.

Recuperar contraseña:

Link por correo con token tkn en la URL.

### 8.1. Usuario ROOT y Administrador
**Usuario ROOT:**

usuarioesroot = 1.

Ve todas las empresas y fundos.

No se valida formato de RUT.

No se puede eliminar ni desactivar (trigger).

No se puede bloquear.

**Perfil ROOT:**

Tiene todos los menús.

No aparece en pantallas de mantenimiento de perfiles.

Nuevos menús se añaden automáticamente con activo = 1.

**Perfil Administrador:**

Igual que ROOT pero sin mantener menús.

Visible en pantalla, pero no editable ni desactivable.

Nuevos menús se añaden con activo = 1.

### 8.2. JWT y sesión
JWT:

Configurado vía .env (algoritmo HS256, expiración, etc.).

Solo para endpoints de API externa.

Sesión Web:

Session PHP + AuthMiddleware para proteger rutas internas.

Expira a las 3 horas; luego se vuelve al login.

## 9. Estructura de carpetas y archivos
Estructura base propuesta:

/home/miempresa/public_html/webapp.miempresa.cl/
│
├── .env
├── cfg.php
├── composer.json            # para uso local; vendor/ se sube desplegado
├── vendor/                  # (Opcional, si se usa Composer local)
│
├── apps/
│   ├── web-php/             # FRONTEND INTERNO (PHP + HTML)
│   │   ├── index.php        # Front Controller web
│   │   ├── login.php
│   │   ├── head.php
│   │   ├── menu.php
│   │   ├── footer.php
│   │   ├── dashboard.php
│   │   ├── prodleche.php
│   │   ├── retiroleche.php
│   │   ├── suplanimal.php
│   │   ├── cambio_password.php
│   │   ├── export_excel.php
│   │   ├── assets/
│   │   │   ├── css/
│   │   │   ├── js/
│   │   │   └── img/
│   │   └── uploads/
│   │       └── retiroleche/
│   │           └── img/
│   │
│   └── api-php/             # ENTRYPOINT API EXTERNA (REST)
│       ├── index.php        # /api → Controllers/Api/*
│       └── .htaccess
│
├── src/
│   ├── Config/
│   │   ├── Database.php
│   │   ├── Env.php
│   │   ├── AppConfig.php
│   │   └── JWTConfig.php
│   │
│   ├── Core/
│   │   ├── BaseModel.php
│   │   ├── BaseController.php
│   │   ├── Response.php
│   │   └── Router.php        # Router genérico (web + API)
│   │
│   ├── Auth/
│   │   ├── AuthService.php
│   │   ├── SessionManager.php
│   │   ├── JwtService.php
│   │   └── PasswordResetService.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── JwtMiddleware.php
│   │   ├── ApiKeyMiddleware.php
│   │   └── RecaptchaMiddleware.php
│   │
│   ├── Controllers/
│   │   ├── Web/
│   │   │   ├── ProduccionLecheController.php
│   │   │   ├── SuplementacionAnimalController.php
│   │   │   ├── RetiroLecheController.php
│   │   │   ├── UsuariosController.php
│   │   │   ├── EmpresasController.php
│   │   │   ├── FundosController.php
│   │   │   ├── MenusController.php
│   │   │   └── PerfilesController.php
│   │   └── Api/
│   │       ├── ProduccionLecheApiController.php
│   │       ├── SuplementacionAnimalApiController.php
│   │       ├── UsuariosApiController.php
│   │       └── ...
│   │
│   ├── Models/
│   │   ├── UsuarioModel.php
│   │   ├── EmpresaModel.php
│   │   ├── FundoModel.php
│   │   ├── ProdLecheModel.php
│   │   ├── ProdLecheDetalleModel.php
│   │   ├── SupAnimalModel.php
│   │   ├── SupAnimalDetalleModel.php
│   │   ├── MenusModel.php
│   │   ├── PerfilesModel.php
│   │   └── ErpTokenModel.php
│   │
│   ├── Services/
│   │   ├── ProduccionLecheService.php
│   │   ├── SuplementacionAnimalService.php
│   │   ├── RetiroLecheService.php
│   │   ├── EmpresasService.php
│   │   ├── UsuariosService.php
│   │   ├── MenusService.php
│   │   ├── PerfilesService.php
│   │   ├── ApiKeyService.php
│   │   └── RecaptchaService.php
│   │
│   ├── Routes/
│   │   ├── web.php           # Router Web (frontend interno)
│   │   └── api.php           # (futuro) Router API externa
│   │
│   ├── Helpers/
│   │   ├── RutHelper.php
│   │   ├── Validator.php
│   │   ├── Logger.php
│   │   ├── Mailer.php
│   │   └── Utils.php
│   │
│   └── api-external/
│       ├── FinnegansClient.php
│       └── DTOs/
│           ├── ProduccionLecheRequestDTO.php
│           ├── ProduccionLecheResponseDTO.php
│           ├── SuplementacionRequestDTO.php
│           └── SuplementacionResponseDTO.php
│
├── database/
│   ├── Tables.csv
│   ├── Columns.csv
│   ├── Audit Columns.csv
│   ├── Generic Log Table.csv
│   ├── tables/
│   │   ├── 01_table_empresas.sql
│   │   ├── 02_table_usuarios.sql
│   │   └── ...
│   ├── sp/
│   │   ├── 01_sp_empresas.sql
│   │   ├── 02_sp_usuarios.sql
│   │   └── ...
│   ├── generate_tables_from_csv.php
│   └── generate_sp_from_csv.php
│
├── storage/
│   ├── LOGS/
│   ├── APILog/
│   └── temp/
│
└── lib/
    └── db.php                 # Solo si hay código legacy; ideal migrar a Config/Database.php

## 10. .env (resumen de secciones)
Secciones del .env:

Database

JWT

Integración Finnegans

reCAPTCHA (Protegido por reCAPTCHA Enterprise)

Email (SMTP)

Parámetros generales de aplicación (APP_ENV, DEBUG, TIMEZONE)

Seguridad (intentos de login, rate limiting)

Uploads (tamaño máximo, extensiones permitidas)

Cache

Logs

API Keys (empresa, usuario)

## 11. Primeros pasos
Crear la base de datos MariaDB.

Generar tablas y SP:

Usar Tables.csv, Columns.csv, Audit Columns.csv, Generic Log Table.csv.

Ejecutar scripts generados de database/tables/*.sql y database/sp/*.sql en phpMyAdmin.

Copiar .env.example → .env y completar:

Credenciales de BD.

JWT.

Credenciales Finnegans.

reCAPTCHA_
### URL: 
```<script src="https://www.google.com/recaptcha/enterprise.js?render=6LeL7_ErAAAAAK1m-ON3Kj7Kai3qMtOlDJ0DC-Vs"></script>```
### Ejemplo:
```
              try {
                // Obtener token de reCAPTCHA Enterprise
                const recaptchaToken = await grecaptcha.enterprise.execute(
                    '6LeL7_ErAAAAAK1m-ON3Kj7Kai3qMtOlDJ0DC-Vs', 
                    {action: 'LOGIN'}
                );

                if (!recaptchaToken) {
                    throw new Error('Error al obtener token de reCAPTCHA');
                }

                document.getElementById('recaptcha_token').value = recaptchaToken;

                // Enviar solicitud de login
                const response = await api.login(usuariocod, password, recaptchaToken);

                if (response && response.success) {
                    showAlert('¡Login exitoso! Redirigiendo al sistema...', 'success');
                    btnLogin.innerHTML = '<i class="fas fa-check"></i> Acceso concedido';
                    
                    // Redireccionar después de 1.5 segundos
                    setTimeout(() => {
                        window.location.href = '/index.php';
                    }, 1500);
                } else {
                    const errorMessage = response?.message || 'Credenciales incorrectas';
                    showAlert(errorMessage, 'error');
                    btnLogin.disabled = false;
                    btnLogin.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
                    
                    // Limpiar contraseña en caso de error
                    passwordInput.value = '';
                    passwordInput.focus();
                }
              } catch (error) {
                console.error('Error en login:', error);
                showAlert('Error de conexión. Por favor intente nuevamente', 'error');
                btnLogin.disabled = false;
                btnLogin.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
            }
```


SMTP.

Crear estructura de carpetas (manual o con script .bat).

Subir el proyecto al hosting en /home/miempresa/public_html/webapp.miempresa.cl/.

Configurar subdominio webapp.miempresa.cl apuntando a apps/web-php/.

Configurar .htaccess para:

Desactivar listado de directorios.

Usar index.php como DirectoryIndex.

Redirigir a login cuando no exista sesión.

Probar:

login.php (autenticación).

?route=empresas/listar como primer CRUD de referencia.

## 12. Generación de Frontend CRUD con Codex
El proyecto incluye un flujo para generar vistas PHP de CRUD usando un Prompt Universal en Codex.

Codex puede generar automáticamente:

<modulo>_listar.php

<modulo>_crear.php

<modulo>_editar.php

Respetando:

Columnas reales de la tabla (database/tables/*.sql).

Convenciones de SP (sp_<modulo>_*).

Reglas de auditoría y LOG/HIST.

Filtros (filtroXxx) y joins según Columns.csv.

Estructura de layout (head.php, menu.php, footer.php).

Rutas (?route=<modulo>/listar|crear|editar).

Botón de exportación a Excel (helper genérico).

Uso típico:

Abrir un nuevo chat en Codex.

Pegar el Prompt Universal definido para Puduhue (ver PUDUHUE.agent y PUDUHUE_FRONT.agent).

Pedir, por ejemplo:

Generate CRUD (listar/crear/editar) for module Empresas

Codex generará los 3 archivos en /apps/web-php/ listos para integrarse con:

src/Controllers/Web/EmpresasController.php

src/Services/EmpresasService.php

sp_empresas_* en la base de datos.
