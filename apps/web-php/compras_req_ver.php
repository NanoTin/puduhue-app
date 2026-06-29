<?php
$isPartial = $partial ?? false;
$req = $req ?? [];
$detalle = $detalle ?? [];
$firmantes = $firmantes ?? [];
$comentarios = $comentarios ?? [];
$analisisPpto = $analisisPpto ?? [];
$puedeAprobar = !empty($puedeAprobar);
$puedeRechazar = !empty($puedeRechazar);
$puedeEditar = !empty($puedeEditar);
$puedeAnular = !empty($puedeAnular);
$errorMessage = $errorMessage ?? null;

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}

$fmtDate = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    try {
        return (new DateTime((string)$value))->format('d-m-Y');
    } catch (Exception $e) {
        return (string)$value;
    }
};

$fmtDateTime = static function ($value): string {
    if (empty($value)) {
        return '';
    }
    try {
        return (new DateTime((string)$value))->format('d-m-Y H:i');
    } catch (Exception $e) {
        return (string)$value;
    }
};

$fmtMoney = static function ($value): string {
    return number_format((float)$value, 0, ',', '.');
};

$fmtQuantity = static function ($value): string {
    $raw = trim((string)$value);
    if ($raw === '') {
        return '';
    }

    $normalized = str_replace(',', '.', $raw);
    if (!is_numeric($normalized)) {
        return $raw;
    }

    $numeric = (float)$normalized;
    $decimals = 0;
    if (str_contains($normalized, '.')) {
        $decimalPart = rtrim(substr($normalized, strpos($normalized, '.') + 1), '0');
        $decimals = strlen($decimalPart);
    }

    return number_format($numeric, $decimals, ',', '.');
};

$estadoBadge = static function ($estado): string {
    return match ((string)$estado) {
        'BRR' => 'bg-secondary',
        'PND' => 'bg-warning text-dark',
        'EDT' => 'bg-info text-dark',
        'APR' => 'bg-success',
        'RCH' => 'bg-danger',
        'ANL' => 'bg-dark',
        default => 'bg-light text-dark',
    };
};

$renderReqActions = static function () use ($req, $puedeEditar, $puedeAprobar, $puedeRechazar, $puedeAnular): string {
    ob_start();
    ?>
    <a class="btn btn-outline-secondary btn-sm" href="?route=compras-req/listar">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <?php if ($puedeEditar): ?>
        <?php if (($req['reqcompraestadoid'] ?? '') === 'PND'): ?>
            <form method="POST" action="?route=compras-req/tomar-edicion" class="d-inline">
                <?= CsrfHelper::input('web') ?>
                <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($req['reqcompraid'] ?? '')) ?>">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-pencil-square"></i> Editar</button>
            </form>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm" href="?route=compras-req/editar&id=<?= urlencode((string)($req['reqcompraid'] ?? '')) ?>">
                <i class="bi bi-pencil-square"></i> Editar
            </a>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($puedeAprobar): ?>
        <button class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#reqAprobarModal">
            <i class="bi bi-check2-circle"></i> Aprobar
        </button>
    <?php endif; ?>
    <?php if ($puedeRechazar): ?>
        <button class="btn btn-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#reqRechazarModal">
            <i class="bi bi-x-circle"></i> Rechazar
        </button>
    <?php endif; ?>
    <?php if ($puedeAnular): ?>
        <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#reqAnularModal">
            <i class="bi bi-slash-circle"></i> Anular
        </button>
    <?php endif; ?>
    <?php
    return (string)ob_get_clean();
};
?>

<div class="container-fluid px-4 py-3 pdh-page">
    <div class="pdh-page__header" id="req-view-header">
        <div>
            <div class="pdh-page__eyebrow">Compras / REQ</div>
            <h3 class="mb-1"><?= htmlspecialchars((string)($req['reqcompracod'] ?? 'REQ')) ?></h3>
            <div class="pdh-page__subtitle">
                <?= htmlspecialchars((string)($req['centrocostodsc'] ?? '')) ?>
                <?php if (!empty($req['funcionarionombre'])): ?>
                    <span class="mx-1">|</span><?= htmlspecialchars((string)$req['funcionarionombre']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="pdh-page__actions">
            <?= $renderReqActions() ?>
        </div>
    </div>

    <div class="compras-req-view__floating-bar" id="req-floating-bar" aria-hidden="true">
        <div class="compras-req-view__floating-inner">
            <div class="compras-req-view__floating-copy">
                <div class="compras-req-view__floating-code"><?= htmlspecialchars((string)($req['reqcompracod'] ?? 'REQ')) ?></div>
                <div class="compras-req-view__floating-center"><?= htmlspecialchars((string)($req['centrocostodsc'] ?? '')) ?></div>
            </div>
            <div class="pdh-page__actions compras-req-view__floating-actions">
                <?= $renderReqActions() ?>
            </div>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (($req['reqcompraestadoid'] ?? '') === 'EDT'): ?>
        <div class="alert alert-warning" role="alert">
            Este REQ se encuentra actualmente en edición. Mientras permanezca en ese estado, los aprobadores no pueden aprobar ni rechazar.
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-3">
            <div class="pdh-card p-3 h-100">
                <div class="compras-req-context__label mb-2">Estado</div>
                <div><span class="badge <?= htmlspecialchars($estadoBadge((string)($req['reqcompraestadoid'] ?? ''))) ?>"><?= htmlspecialchars((string)($req['reqcomprasestadodsc'] ?? ($req['reqcompraestadoid'] ?? ''))) ?></span></div>
                <div class="compras-req-table-note mt-3">Fecha: <?= htmlspecialchars($fmtDate($req['reqcomprafecha'] ?? '')) ?></div>
            </div>
        </div>
        <div class="col-12 col-xl-3">
            <div class="pdh-card p-3 h-100">
                <div class="compras-req-context__label mb-2">Prioridad</div>
                <div class="fs-5 fw-semibold"><?= ((int)($req['reqcompraprioridad'] ?? 1) === 2) ? 'Alta' : 'Normal' ?></div>
                <div class="compras-req-table-note mt-3">Aprobador pendiente: <?= htmlspecialchars((string)($req['reqaprobadorpendientenombre'] ?? '')) ?></div>
            </div>
        </div>
        <div class="col-12 col-xl-3">
            <div class="pdh-card p-3 h-100">
                <div class="compras-req-context__label mb-2">Presupuesto</div>
                <?php if (!empty($req['reqfuerapptocompra'])): ?>
                    <div class="compras-req-pill compras-req-pill--danger">Fuera de presupuesto</div>
                <?php elseif (!empty($req['reqadvertenciapptocompra'])): ?>
                    <div class="compras-req-pill compras-req-pill--warning">Con advertencia</div>
                <?php else: ?>
                    <div class="badge bg-light text-dark">Sin advertencias</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-xl-3">
            <div class="pdh-card p-3 h-100">
                <div class="compras-req-context__label mb-2">Total neto</div>
                <div class="fs-4 fw-semibold">$<?= htmlspecialchars($fmtMoney($req['reqcompranettotal'] ?? 0)) ?></div>
                <div class="compras-req-table-note mt-3">Creado por <?= htmlspecialchars((string)($req['creadornombre'] ?? '')) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="pdh-card p-3 mb-3">
                <h5 class="mb-3">General</h5>
                <div class="compras-req-context">
                    <div>
                        <div class="compras-req-context__label">Tipo</div>
                        <div class="compras-req-context__value"><?= ((int)($req['reqcompratipo'] ?? 1) === 1) ? 'Material' : 'Servicio' ?></div>
                    </div>
                    <div>
                        <div class="compras-req-context__label">Centro</div>
                        <div class="compras-req-context__value"><?= htmlspecialchars((string)($req['centrocostodsc'] ?? '')) ?></div>
                    </div>
                    <div>
                        <div class="compras-req-context__label">Funcionario</div>
                        <div class="compras-req-context__value"><?= htmlspecialchars((string)($req['funcionarionombre'] ?? 'Sin funcionario')) ?></div>
                    </div>
                    <div>
                        <div class="compras-req-context__label">Fecha aprobación final</div>
                        <div class="compras-req-context__value"><?= htmlspecialchars($fmtDate($req['reqaprobacionfecha'] ?? '')) ?></div>
                    </div>
                </div>
                <hr>
                <div class="compras-req-context__label mb-2">Observación general</div>
                <div><?= nl2br(htmlspecialchars((string)($req['reqcompraobs'] ?? 'Sin observación'))) ?></div>
            </div>

            <div class="pdh-card p-3 mb-3">
                <h5 class="mb-3">Detalle de items</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Cantidad</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Total</th>
                                <th>Obs.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($detalle)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Sin detalle.</td></tr>
                            <?php else: ?>
                                <?php foreach ($detalle as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string)($row['reqcompradetitemcod'] ?? '')) ?></div>
                                            <div class="compras-req-table-note"><?= htmlspecialchars((string)($row['reqcompradetdsc'] ?? '')) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($fmtQuantity($row['reqcompradetcantidad'] ?? '')) ?></td>
                                        <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqcompradetprecioneto'] ?? 0)) ?></td>
                                        <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqcompradettotalneto'] ?? 0)) ?></td>
                                        <td><?= htmlspecialchars((string)($row['reqcompradetobs'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pdh-card p-3">
                <h5 class="mb-3">Análisis de presupuesto</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Subfamilia</th>
                                <th class="text-end">Saldo disp.</th>
                                <th class="text-end">Otros REQ</th>
                                <th class="text-end">Aprobados</th>
                                <th class="text-end">Monto REQ</th>
                                <th class="text-end">Saldo proyectado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($analisisPpto)): ?>
                                <tr><td colspan="6" class="text-center text-muted">Sin análisis disponible.</td></tr>
                            <?php else: ?>
                                <?php foreach ($analisisPpto as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($row['subfamiliadsc'] ?? $row['subfamiliaid'] ?? '')) ?></td>
                                        <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptosaldodisponible'] ?? 0)) ?></td>
                                        <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptomontootroscurso'] ?? 0)) ?></td>
                                        <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptomontoaprobadospend'] ?? 0)) ?></td>
                                        <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptomonto'] ?? 0)) ?></td>
                                        <td class="text-end">
                                            <?php if (!empty($row['reqpptoadvertencia'])): ?>
                                                <span class="compras-req-pill compras-req-pill--warning">$<?= htmlspecialchars($fmtMoney($row['reqpptosaldoproyectado'] ?? 0)) ?></span>
                                            <?php else: ?>
                                                $<?= htmlspecialchars($fmtMoney($row['reqpptosaldoproyectado'] ?? 0)) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="pdh-card p-3 mb-3">
                <h5 class="mb-3">Firmantes</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Orden</th>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($firmantes)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin firmantes.</td></tr>
                            <?php else: ?>
                                <?php foreach ($firmantes as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($row['firmanteorden'] ?? '')) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars((string)($row['usuarionombre'] ?? '')) ?></div>
                                            <div class="compras-req-table-note"><?= htmlspecialchars((string)($row['usuariorut'] ?? '')) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars((string)($row['firmantetipo'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['firmanteestado'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pdh-card p-3">
                <h5 class="mb-3">Comentarios funcionales</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Comentario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($comentarios)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Sin comentarios registrados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($comentarios as $comentario): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fmtDateTime($comentario['reqcomentariofechahora'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($comentario['usuarionombre'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($comentario['reqcomentariotipo'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($comentario['reqcomentariotxt'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const header = document.getElementById('req-view-header');
    const floatingBar = document.getElementById('req-floating-bar');
    const appHeader = document.querySelector('.app-header');

    if (!header || !floatingBar) {
        return;
    }

    const syncFloatingBar = () => {
        const headerOffset = appHeader ? appHeader.offsetHeight : 0;
        const headerRect = header.getBoundingClientRect();
        const shouldShow = headerRect.top <= headerOffset;

        floatingBar.style.left = `${Math.max(headerRect.left, 0)}px`;
        floatingBar.style.width = `${Math.max(headerRect.width, 0)}px`;

        floatingBar.classList.toggle('is-visible', shouldShow);
        floatingBar.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
    };

    syncFloatingBar();
    window.addEventListener('scroll', syncFloatingBar, { passive: true });
    window.addEventListener('resize', syncFloatingBar);
});
</script>

<div class="modal fade" id="reqAprobarModal" tabindex="-1" aria-labelledby="reqAprobarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?route=compras-req/aprobar">
                <?= CsrfHelper::input('web') ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="reqAprobarModalLabel">Aprobar REQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($req['reqcompraid'] ?? '')) ?>">
                    <label class="form-label">Comentario opcional</label>
                    <textarea class="form-control" name="comentario" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Aprobar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="reqRechazarModal" tabindex="-1" aria-labelledby="reqRechazarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?route=compras-req/rechazar">
                <?= CsrfHelper::input('web') ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="reqRechazarModalLabel">Rechazar REQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($req['reqcompraid'] ?? '')) ?>">
                    <label class="form-label">Comentario obligatorio</label>
                    <textarea class="form-control" name="comentario" rows="4" required minlength="11"></textarea>
                    <div class="form-text">Debe contener más de 10 caracteres.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rechazar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="reqAnularModal" tabindex="-1" aria-labelledby="reqAnularModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?route=compras-req/anular">
                <?= CsrfHelper::input('web') ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="reqAnularModalLabel">Anular REQ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($req['reqcompraid'] ?? '')) ?>">
                    <label class="form-label">Comentario obligatorio</label>
                    <textarea class="form-control" name="comentario" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Anular</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>
