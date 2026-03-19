# Endpoint - SuplAnimal Detalle

## 1. Recurso y ruta

- Recurso: `suplanimal-detalle`
- Endpoint: `POST /v1/suplanimal-detalle/query`

URL publica recomendada:

- `https://api.puduhue.cl/v1/suplanimal-detalle/query`

## 2. Objetivo funcional

Exponer consulta paginada del detalle de suplementacion animal, reutilizando la relacion real entre `suplanimal`, `suplanimaldetalle`, `fundos`, `invcateganimal`, `invitems` e `invunidadesmedidas`.

## 3. Tablas reales auditadas

### 3.1. Encabezado `suplanimal`

Columnas relevantes:

- `suplanimalid` PK
- `suplanimalstatus`
- `empresaid`
- `fundoid`
- `invbodegaid`
- `suplanimalfecha`
- `suplanimalobservacion`
- `sup_erpestablecimientocod`
- `sup_erplotecod`
- `sup_erpinvbodegacod`
- `suplanml_erp_sync`

Indices auditados:

- PK `suplanimalid`
- indice `idx_suplanimal_empresaid`
- indice `idx_suplanimal_fundoid`
- indice `idx_suplanimal_invbodegaid`

### 3.2. Detalle `suplanimaldetalle`

PK compuesta:

- `suplanimalid`
- `suplanimallinea`

Columnas relevantes:

- `invcateganimalid`
- `sup_erpinvcateganimalcod`
- `invitemid`
- `sup_erpinvitemcod`
- `invunidmedid`
- `sup_erpunidmedcod`
- `totalconsumido`
- `totalanimales`
- `dosisporanimal`
- `erpdocumentocod`
- `supdetfechareg`
- `supdetfechaedt`

Indices auditados:

- PK compuesta
- indice `idx_suplanimaldetalle_suplanimalid`
- indice `idx_suplanimaldetalle_invcateganimalid`
- indice `idx_suplanimaldetalle_invitemid`
- indice `idx_suplanimaldetalle_invunidmedid`

### 3.3. Maestros relacionados

`fundos`

- `fundoid`
- `fundonombre`
- `empresaid`

`invcateganimal`

- `invcateganimalid`
- `invcateganimaldsc`
- `erpinvcateganimalcod`

`invitems`

- `invitemid`
- `invitemdsc`
- `invunidmedid`
- `erpinvitemcod`

`invunidadesmedidas`

- `invunidmedid`
- `invunidmeddsc`
- `erpunidmedcod`

## 4. Filtros confirmados

- `param_fecha_desde`
- `param_fecha_hasta`
- `param_fundos_ids`
- `page`
- `page_size`

Ejemplo:

```json
{
  "param_fecha_desde": "2026-03-01",
  "param_fecha_hasta": "2026-03-17",
  "param_fundos_ids": [1, 2, 5],
  "page": 1,
  "page_size": 100
}
```

## 5. Joins reales recomendados

```sql
FROM suplanimaldetalle d
INNER JOIN suplanimal s ON s.suplanimalid = d.suplanimalid
INNER JOIN fundos f ON f.fundoid = s.fundoid
INNER JOIN invcateganimal c ON c.invcateganimalid = d.invcateganimalid
INNER JOIN invitems i ON i.invitemid = d.invitemid
INNER JOIN invunidadesmedidas u ON u.invunidmedid = d.invunidmedid
```

## 6. Campos de salida recomendados

- `suplanimal_id`
- `linea`
- `suplanimal_fecha`
- `suplanimal_status`
- `empresa_id`
- `fundo_id`
- `fundo_nombre`
- `categoria_animal_id`
- `categoria_animal_nombre`
- `item_id`
- `item_nombre`
- `unidad_medida_id`
- `unidad_medida_nombre`
- `total_consumido`
- `total_animales`
- `dosis_por_animal`
- `erp_documento_cod`
- `fecha_registro`
- `fecha_edicion`

## 7. Query base sugerida

Notas:

- filtrar por `DATE(s.suplanimalfecha)`
- paginar con `LIMIT` y `OFFSET`
- contar total con consulta paralela o separada
- ordenar por fecha descendente, fundo ascendente, `suplanimalid` descendente, linea ascendente

## 8. Validaciones especificas

- `param_fecha_desde` y `param_fecha_hasta` validas en `YYYY-MM-DD`
- `param_fecha_desde <= param_fecha_hasta`
- `param_fundos_ids` arreglo de enteros positivos
- `page >= 1`
- `page_size` con maximo recomendado de 500

## 9. Performance observada y recomendada

Situacion actual:

- `suplanimalfecha` no tiene indice dedicado.
- la tabla detalle depende del encabezado para fecha y fundo.

Recomendaciones:

- evaluar indice compuesto `suplanimal(suplanimalfecha, fundoid)`
- usar primero el encabezado para acotar por fecha y luego unir detalle

## 10. Hallazgo importante

El SP `sp_suplanimaldetalle_listar_detalle` actual esta incompleto y solo filtra por `supdetfechareg`; no resuelve los joins ni los filtros reales del recurso externo. No debe reutilizarse como implementacion final del endpoint publico.

## 11. Respuesta estandar

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

## 12. Pendientes por completar

- Confirmar si deben excluirse registros `suplanimalstatus = 'ANL'`.
- Definir si corresponde exponer `suplanimalobservacion`.
- Confirmar si debe incorporarse filtro futuro por `invitemid` o `invcateganimalid`.
- Confirmar si el alcance por token debe limitarse a los fundos asociados en `usuariosfundos`.
