# Endpoint - ProdLeche Detalle

## 1. Recurso y ruta

- Recurso: `prodleche-detalle`
- Endpoint: `POST /v1/prodleche-detalle/query`

URL publica recomendada:

- `https://api.puduhue.cl/v1/prodleche-detalle/query`

## 2. Objetivo funcional

Exponer consulta paginada del detalle de produccion de leche, reutilizando las tablas operativas reales `prodleche`, `prodlechedetalle`, `prodlechetipos` y `fundos`.

## 3. Tablas reales auditadas

### 3.1. Encabezado `prodleche`

Columnas relevantes:

- `prodlecheid` PK
- `prodlechestatus`
- `empresaid`
- `fundoid`
- `prodlechefecha`
- `prodlechehoraini`
- `prodlechehorafin`
- `prodlechehorario`
- `prodlechetotlitros`
- `prodlechetotvacas`
- `prodlecheventatotlitros`
- `prodlecheventatotvacas`
- `prodlecheventalitrosxvaca`
- `prodlecheobservacion`
- `pl_erp_sync`

Indices auditados:

- PK `prodlecheid`
- indice `idx_prodleche_empresaid`
- indice `idx_prodleche_fundoid`

### 3.2. Detalle `prodlechedetalle`

PK compuesta:

- `prodlecheid`
- `prodlechetipoid`

Columnas relevantes:

- `pldetlitros`
- `pldetvacas`
- `pldetlitrosxvaca`
- `prodlechecod`
- `erpdocumentocod`
- `pldetfechareg`
- `pldetfechaedt`

Indices auditados:

- PK compuesta
- unique `uq_prodlechedetalle_prodlechecod`
- indice `idx_prodlechedetalle_prodlecheid`
- FK/indice `idx_prodlechedetalle_prodlechetipoid`

### 3.3. Maestro `prodlechetipos`

Columnas utiles:

- `prodlechetipoid`
- `prodlechetipodsc`
- `invitemid`
- `prodlecheventa`
- `prodlecheorden`
- `prodlecheactivo`

### 3.4. Maestro `fundos`

Columnas utiles:

- `fundoid`
- `fundonombre`
- `empresaid`
- `erpestablecimientocod`
- `erplotecod`
- `reporteorden`
- `fundoactivo`

## 4. Filtros confirmados

- `param_fecha_desde`
- `param_fecha_hasta`
- `param_fundos_ids`
- `param_tipos_leche_ids`
- `page`
- `page_size`

Ejemplo:

```json
{
  "param_fecha_desde": "2026-03-01",
  "param_fecha_hasta": "2026-03-17",
  "param_fundos_ids": [1, 2, 5],
  "param_tipos_leche_ids": [1, 3],
  "page": 1,
  "page_size": 100
}
```

## 5. Joins reales recomendados

```sql
FROM prodlechedetalle d
INNER JOIN prodleche p ON p.prodlecheid = d.prodlecheid
INNER JOIN fundos f ON f.fundoid = p.fundoid
INNER JOIN prodlechetipos t ON t.prodlechetipoid = d.prodlechetipoid
```

## 6. Campos de salida recomendados

Salida JSON propuesta:

- `prodleche_id`
- `prodleche_fecha`
- `prodleche_horario`
- `prodleche_status`
- `empresa_id`
- `fundo_id`
- `fundo_nombre`
- `tipo_leche_id`
- `tipo_leche_nombre`
- `tipo_leche_venta`
- `litros`
- `vacas`
- `litros_por_vaca`
- `codigo_operacion`
- `erp_documento_cod`
- `fecha_registro`
- `fecha_edicion`

## 7. Query base sugerida

Notas:

- filtrar por `DATE(p.prodlechefecha)`
- ordenar por fecha descendente, horario descendente, fundo, tipo
- paginar con `LIMIT :limit OFFSET :offset`
- obtener `COUNT(*)` en query separada con los mismos filtros

## 8. Validaciones especificas

- `param_fecha_desde` y `param_fecha_hasta` deben venir en formato `YYYY-MM-DD`
- `param_fecha_desde <= param_fecha_hasta`
- `param_fundos_ids` debe ser arreglo de enteros positivos
- `param_tipos_leche_ids` debe ser arreglo de enteros positivos
- `page >= 1`
- `page_size >= 1`
- `page_size` con maximo recomendado de 500

## 9. Performance observada y recomendada

Situacion actual:

- `prodlechefecha` no tiene indice dedicado.
- la tabla detalle no tiene indice separado por fecha porque la fecha vive en encabezado.

Recomendaciones:

- evaluar indice compuesto en `prodleche` sobre `prodlechefecha, fundoid`
- si el filtro por fecha y fundo sera intensivo, evaluar `idx_prodleche_fecha_fundo`
- mantener join principal desde `prodleche` hacia `prodlechedetalle` si el rango de fechas reduce primero el set

## 10. Respuesta estandar

```json
{
  "status": 200,
  "message": "Consulta realizada correctamente",
  "data": [],
  "meta": {
    "request_id": "uuid",
    "page": 1,
    "page_size": 100,
    "total_registros": 0,
    "execution_ms": 0
  }
}
```

## 11. Pendientes por completar

- Confirmar si se debe filtrar por `usuariofundos` para consumidores API o si el token tendra alcance total por usuario.
- Definir si deben excluirse registros `prodlechestatus = 'ANL'`.
- Definir si se expone `prodlecheobservacion`.
- Definir si se requiere filtro adicional por `empresaid`.
- Confirmar si el recurso debe devolver solo fechas o datetime completo en `prodlechefecha`.
