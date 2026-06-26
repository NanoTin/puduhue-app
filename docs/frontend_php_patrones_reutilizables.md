# Patrones reutilizables PHP/Frontend

Guia transversal para nuevas vistas PHP en `apps/web-php/`, basada en lo aplicado en `pptocompra_listar.php` y `pptocompra_detalle.php`.

## Objetivo

- Mantener Bootstrap 5 como base visual.
- Agregar clases propias reutilizables con prefijo `pdh-`.
- Evitar CSS inline nuevo en pantallas PHP.
- Separar lo transversal de lo modulo-especifico.
- Avanzar con pantallas nuevas usando el patron, sin migrar masivamente pantallas antiguas.

## CSS compartido

Archivo base:

`apps/web-php/assets/css/pdh-components.css`

Se carga desde `apps/web-php/head.php`, despues de `layout.css`.

Regla de nombres:

- Clases compartidas: `pdh-*`.
- Clases especificas de presupuesto de compras: `ppto-*`.
- Clases especificas de otro modulo: usar un prefijo propio del modulo solo cuando el estilo no sea reusable.

## Layout estandar de listados

Estructura recomendada:

```php
<div class="container-fluid px-4 py-3 pdh-page">
    <div class="pdh-page__header">
        <div>
            <h3 class="mb-1">Titulo del listado</h3>
            <div class="pdh-page__subtitle">Texto secundario opcional</div>
        </div>
        <div class="pdh-page__actions">
            <a class="btn btn-primary btn-sm" href="?route=modulo/crear">
                <i class="bi bi-plus-circle"></i> Nuevo
            </a>
        </div>
    </div>

    <form class="row g-2 pdh-filter-bar" method="GET" action="?route=modulo/listar">
        <!-- filtros -->
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle pdh-data-table">
            <!-- thead / tbody -->
        </table>
    </div>
</div>
```

Componentes usados:

- `.pdh-page`: contenedor de pagina y variables visuales.
- `.pdh-page__header`: titulo, contexto y acciones principales.
- `.pdh-page__actions`: botones superiores.
- `.pdh-filter-bar`: fila de filtros.
- `.pdh-data-table`: tabla de datos.
- `.pdh-data-table__actions`: celda de acciones por fila.

Acciones por fila:

- Usar botones `btn-sm`.
- Usar Bootstrap Icons.
- Incluir `title`, `aria-label` y `data-bs-toggle="tooltip"` cuando la accion se expresa solo con icono.
- Mantener formularios POST para acciones destructivas o de cambio de estado.

## Layout estandar de detalle

Estructura recomendada:

```php
<div class="container-fluid px-4 py-3 pdh-page">
    <div class="pdh-page__header">
        <div>
            <div class="pdh-page__eyebrow">Modulo / Contexto</div>
            <h3 class="mb-1">Entidad #123</h3>
            <div class="pdh-page__subtitle">Resumen corto</div>
        </div>
        <div class="pdh-page__actions">
            <!-- acciones -->
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl">
            <div class="pdh-card p-3 h-100">
                <div class="pdh-kpi__label mb-3">Indicador</div>
                <div class="pdh-kpi__value">$0</div>
                <div class="pdh-kpi__note mt-3">Nota</div>
            </div>
        </div>
    </div>

    <div class="pdh-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle pdh-trace-table mb-0">
                <!-- trazabilidad -->
            </table>
        </div>
    </div>
</div>
```

Componentes usados:

- `.pdh-card`: panel visual para KPIs, tablas, graficos o bloques de informacion.
- `.pdh-kpi__label`, `.pdh-kpi__value`, `.pdh-kpi__note`: indicadores numericos.
- `.pdh-chart`, `.pdh-chart__axis`, `.pdh-chart__line`, `.pdh-chart__bar`, `.pdh-chart__label`, `.pdh-chart-legend`: graficos SVG simples generados por PHP.
- `.pdh-trace-table`: tablas de eventos, movimientos o trazabilidad.
- `.pdh-status-badge`: badges tipo pildora para estados destacados.

## Que patrones de `ppto-` pasan a componentes reutilizables

Convertidos o candidatos directos a `pdh-`:

- Card base de detalle: `ppto-card` -> `.pdh-card`.
- KPI label/value/note: `ppto-kpi-*` -> `.pdh-kpi__*`.
- Tabla de trazabilidad: `ppto-trace-table` -> `.pdh-trace-table`.
- Graficos SVG simples: `ppto-line-chart`, `ppto-chart-*`, `ppto-legend-*` -> `.pdh-chart*`.
- Badge visual por tono: `ppto-available-badge-*` -> `.pdh-status-badge--*`.

## Que queda modulo-especifico

Mantener como `ppto-*` cuando dependa de reglas visuales o semanticas propias del presupuesto:

- Estados calculados de saldo disponible.
- Iconografia decorativa del disponible.
- Textos de leyenda de rangos de disponibilidad.
- Colores o enfasis asociados a reglas de presupuesto que no apliquen transversalmente.
- Filtros o acciones de negocio como ajustar, traspasar, carga base y movimientos PreOC.

## Criterio para pantallas nuevas de Compras

1. Usar `pdh-page` como contenedor.
2. Usar `pdh-page__header` para titulo, contexto y acciones.
3. Usar `pdh-filter-bar` en listados con filtros.
4. Usar `.table-responsive` siempre que haya tablas.
5. Usar `pdh-card` y `pdh-kpi__*` en detalles con resumen.
6. Crear clases `ppto-*` solo si la necesidad es propia de presupuesto de compras.
7. No migrar pantallas antiguas por arrastre; hacer tandas posteriores con alcance definido.

## Validacion esperada al editar vistas PHP

- Ejecutar `php -l` sobre cada PHP modificado o nuevo.
- Ejecutar `git diff --check`.
- Revisar visualmente cuando el cambio altere layout significativo.
