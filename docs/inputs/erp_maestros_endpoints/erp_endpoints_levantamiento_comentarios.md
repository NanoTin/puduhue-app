Crear un Maestro con su FE, BE y tablas para administrar todos los EndPoint
Tabla: ERP_ListadoEndPoints
ID: PK
Descripción: Obligatorio
Grupo: Para agrupar ciertos EndPoint que se deben ejecutar si o si, en especial cuando presionan OnDemand.
Orden Secuencia dentro del Grupo: Dentro del grupo en que orden se debe ejecutar. Dentro del mismo grupo no puede haber 2 ordenes iguales.
EndPoint Hash: Se guarda el dato hasheado. Una opción es .env tener la llave para descifra o buscar otra manera.
EndPoint Tipo: [Base ERP, Custom, Report/Viewer]
Permite OnDemand: Para que en el formulario que lo llame, se habilite o no el botón sincronizar OnDemand.
Permite Sync Auto: Permite sincronización automática
Frecuencia sync: [Diario, Semanal, Mensual]
Dia evento: Si es Diario, no tiene dia. Si es Semanal, debe especificar si es Lunes, Martes, etc. Si es Mensual, que día del mes.
Hora evento: El horario: 00:00, 01:00, etc. —> Opcional o quizás no usar, pues, el Job que crearé inicialmente ya viene con horario establecido.
Formulario Call: Especificar a que formulario corresponda para realizar validaciones.
Fecha ultima sync: Fecha Hora
Estado ultima sync: Estados: Ok, o Error xxxxx
Activo: True / False
<Columnas Auditoria>

Tabla: ERP_ListadoEndPointsLog

Listado de EndPoint en el archivo CSV "erp_listado_endpoints_v20260623.csv" junto con otras columnas a revisar que no necesariamente estan relacionada a la tabla antes propuesta.

Nota 1:
Los siguientes maestros ya existen, por lo que se debe bloquear el botón crear y editar en sus respectivos formularios web, pues, ahora se completan con los EndPoint antes señalados.
* Unidades de Medidas
* Ítems o Productos

Nota 2:
Por ejemplo, no se puede ejecutar el EndPoint de Productos antes de Familia, Sub Familias y Unidades de Medidas, pues, si viene un nuevo producto, con nuevas configuración, esas relaciones deben existir antes de insertar el producto.
Lo mismo debe pasar si al presionar el botón OnDemand en Productos, debe hacer un grupo de EndPoint antes de actualizar o insertar en productos.

Nota 3:
Un producto tiene una Entidad “Dimensiones” donde se configura por lo general “Partida Financiera”. Con ello se construye la Entidad “DimensionDistribucion” en el JSON para integrar una OC.
Se agregará una columna en el Maestro de Ítems que haga referencia a “Partida Financiera” y en ella guardar “ACO000”, por ejemplo, o el valor que tenga el atributo “DimensionDistribucionCodigo”. Se asumirá que solo puede contener una “Partida Financiera” para no crear una tabla “ItemsPartidasFinancieras”.
Ejemplo:
"Dimensiones": [
        {
            "DimensionCodigo": "DIMPARFIN",
            "DimensionDistribucionCodigo": "ACO000"
        }
    ],

Nota 4:
Todos los EndPoint se de les debe adicionar “?ACCESS_TOKEN={Token generado antes}”

Nota 5:
El JSON de un proveedor contiene una entidad llamada “CondicionesPago” que a la vez contiene un listado de condiciones de pago, por lo que un proveedor puede tener varias condiciones de pago. Para no replicar como detalles las condiciones de pago, si tiene más de una, se revisa si tiene una default. De ser así, esa condición de pago código queda. Si no, la primera que se encuentra. Si solo tiene una, mantiene la única.
Ejemplo:
    "CondicionesPago": [
        {
            "CondicionPagoCodigo": "30",
            "Default": false
        }
    ],

Nota 6:
Existen EndPoint “list” que no contienen toda la información, por lo que se debe hacer un proceso adicional para ir consultado código por código e ir actualizando atributos adicionales.
Por ejemplo, “https://api.finneg.com/api/producto/list” solo trae los atributos: código, nombre, descripción, activo (true/false)
Pero se requieren los “códigos” de: unidad de medida, tasa impositiva compra, familia, sub familia, entre otros. Por eso se debe hacer un proceso que recorra los ítems y valla consumiendo el endpoint “https://api.finneg.com/api/producto/{reemplazar por código}” para ir actualizando esos atributos.

Nota 7:
Se debe tomar como base cualquier maestro para crear FE, Controlador, Servicio, SP y Tabla.
Solo recordar que no existe el crear ni editar para estos maestros que provienen del ERP.Nota 8:
Lo que se debe actualizar siempre son lo campos evitables de cualquier tabla a excepción del código: Descripción, etc.

Nota 9:
La referencia en cada tabla es la PK de la tabla principal y no el “código” que proviene desde el ERP. Ese es un dato adicional en cada tabla, único y que no se puede editar.

Nota 10:
El Maestro de ítems contempla cambios que son propios de la lógica del sistema para algunos módulos. Se debe trabajar esto en conjunto con las columnas adicionales. Tener ojo con las Unidades de Medidas que ya existe.

Nota 11:
Se dejan ejemplos de los atributos de todos los endpoints en la carpeta "json_ejemplos_con_datos".