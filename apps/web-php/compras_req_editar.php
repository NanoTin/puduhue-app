<?php
$isPartial = $partial ?? false;
$req = $req ?? [];
$detalle = $detalle ?? [];
$firmantes = $firmantes ?? [];
$comentarios = $comentarios ?? [];
$analisisPpto = $analisisPpto ?? [];
$formData = $formData ?? [];
$centrosOptions = $centrosOptions ?? [];
$funcionariosOptions = $funcionariosOptions ?? [];
$itemsRows = $itemsRows ?? [];
$aprobadoresRows = $aprobadoresRows ?? [];
$errorMessage = $errorMessage ?? null;
$serverDate = date('Y-m-d');
$hasCentrosAsignados = !empty($centrosOptions);

$itemsIndex = [];
foreach ($itemsRows as $itemRow) {
    $itemId = (string)($itemRow['invitemid'] ?? '');
    if ($itemId !== '') {
        $itemsIndex[$itemId] = $itemRow;
    }
}

$aprobadoresIndex = [];
foreach ($aprobadoresRows as $aprobadorRow) {
    $aprobadorId = (string)($aprobadorRow['usuarioid'] ?? '');
    if ($aprobadorId !== '') {
        $aprobadoresIndex[$aprobadorId] = $aprobadorRow;
    }
}

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

$fmtMoney = static function ($value): string {
    return number_format((float)$value, 0, ',', '.');
};
?>

<div class="container-fluid px-4 py-3 pdh-page">
    <div class="pdh-page__header">
        <div>
            <div class="pdh-page__eyebrow">Compras / REQ</div>
            <h3 class="mb-1">Editar <?= htmlspecialchars((string)($req['reqcompracod'] ?? '')) ?></h3>
            <div class="pdh-page__subtitle">Solo el creador puede continuar este formulario en el estado actual.</div>
        </div>
        <div class="pdh-page__actions">
            <a class="btn btn-outline-secondary btn-sm" href="?route=compras-req/ver&id=<?= urlencode((string)($req['reqcompraid'] ?? '')) ?>">
                <i class="bi bi-arrow-left"></i> Volver a ver
            </a>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (!$hasCentrosAsignados): ?>
        <div class="alert alert-warning" role="alert">
            No tiene centros de costo asignados en `usuarioscentroscosto`. Solicite a Administración la asignación antes de continuar con la edición.
        </div>
    <?php endif; ?>

    <div class="pdh-card p-3 mb-3">
        <div class="compras-req-context">
            <div>
                <div class="compras-req-context__label">Código</div>
                <div class="compras-req-context__value"><?= htmlspecialchars((string)($req['reqcompracod'] ?? '')) ?></div>
            </div>
            <div>
                <div class="compras-req-context__label">Estado</div>
                <div class="compras-req-context__value"><?= htmlspecialchars((string)($req['reqcomprasestadodsc'] ?? ($req['reqcompraestadoid'] ?? ''))) ?></div>
            </div>
            <div>
                <div class="compras-req-context__label">Fecha funcional</div>
                <div class="compras-req-context__value"><?= htmlspecialchars($fmtDate($req['reqcomprafecha'] ?? '')) ?></div>
            </div>
            <div>
                <div class="compras-req-context__label">Total actual</div>
                <div class="compras-req-context__value">$<?= htmlspecialchars($fmtMoney($req['reqcompranettotal'] ?? 0)) ?></div>
            </div>
        </div>
    </div>

    <form
        id="req-edit-form"
        method="POST"
        action="?route=compras-req/editar"
        data-server-date="<?= htmlspecialchars($serverDate) ?>"
        data-ppto-validate-url="?route=compras-req/validar-item-ppto"
        data-has-centers="<?= $hasCentrosAsignados ? '1' : '0' ?>"
    >
        <?= CsrfHelper::input('web') ?>
        <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($formData['reqcompraid'] ?? ($req['reqcompraid'] ?? ''))) ?>">
        <input type="hidden" name="accion" id="req-accion" value="<?= htmlspecialchars((string)($formData['accion'] ?? 'guardar_borrador')) ?>">
        <input type="hidden" name="reqcompratipo" id="reqcompratipo-hidden" value="<?= htmlspecialchars((string)($formData['reqcompratipo'] ?? '')) ?>">
        <input type="hidden" name="centrocostoid" id="req-centrocostoid-hidden" value="<?= htmlspecialchars((string)($formData['centrocostoid'] ?? '')) ?>">

        <div class="compras-req-form__actions d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="small text-muted">Los cambios se validan en backend y el análisis presupuestario sigue siendo informativo.</div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-req-confirm="volver-ver">
                    <i class="bi bi-arrow-left"></i> Volver a ver
                </button>
                <?php if (($req['reqcompraestadoid'] ?? '') === 'EDT'): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-req-confirm="cancel-edit">
                        <i class="bi bi-x-circle"></i> Cancelar cambios
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-primary btn-sm" data-req-submit="guardar_borrador">
                    <i class="bi bi-save"></i> Guardar como borrador
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-req-submit="reenviar_aprobacion">
                    <i class="bi bi-send"></i> Guardar y reenviar
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-4">
                <div class="pdh-card p-3 h-100">
                    <h5 class="mb-3">Cabecera</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tipo REQ</label>
                            <select class="form-select" id="reqcompratipo">
                                <option value="">Seleccione</option>
                                <option value="1" <?= (($formData['reqcompratipo'] ?? '') === '1') ? 'selected' : '' ?>>Material</option>
                                <option value="2" <?= (($formData['reqcompratipo'] ?? '') === '2') ? 'selected' : '' ?>>Servicio</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Centro de costo</label>
                            <select class="form-select" id="req-centrocostoid">
                                <option value="">Seleccione</option>
                                <?php foreach ($centrosOptions as $centro): ?>
                                    <option value="<?= htmlspecialchars((string)($centro['centrocostoid'] ?? '')) ?>" <?= ((string)($formData['centrocostoid'] ?? '') === (string)($centro['centrocostoid'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)($centro['centrocostodsc'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-none" id="req-centros-defaults-data">
                            <?php foreach ($centrosOptions as $centro): ?>
                                <div
                                    data-centro-id="<?= htmlspecialchars((string)($centro['centrocostoid'] ?? '')) ?>"
                                    data-jefe-id="<?= htmlspecialchars((string)($centro['centrocostojefeusuarioid'] ?? '')) ?>"
                                    data-jefe-nombre="<?= htmlspecialchars((string)($centro['centrocostojefeusuarionombre'] ?? '')) ?>"
                                    data-jefe-rut="<?= htmlspecialchars((string)($centro['centrocostojefeusuariorut'] ?? '')) ?>"
                                    data-jefe-valido="<?= (!empty($centro['centrocostojefeusuarioid']) && !empty($centro['centrocostojefeusuarioactivo']) && empty($centro['centrocostojefeusuariobloqueado']) && !empty($centro['centrocostojefeusuariopermiteaprobreq'])) ? '1' : '0' ?>"
                                    data-tecnico-id="<?= htmlspecialchars((string)($centro['centrocostojefetecnicoid'] ?? '')) ?>"
                                    data-tecnico-nombre="<?= htmlspecialchars((string)($centro['centrocostojefetecniconombre'] ?? '')) ?>"
                                    data-tecnico-rut="<?= htmlspecialchars((string)($centro['centrocostojefetecnicorut'] ?? '')) ?>"
                                    data-tecnico-valido="<?= (!empty($centro['centrocostojefetecnicoid']) && !empty($centro['centrocostojefetecnicoactivo']) && empty($centro['centrocostojefetecnicobloqueado']) && !empty($centro['centrocostojefetecnicopermiteaprobreq'])) ? '1' : '0' ?>"
                                ></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Funcionario solicitante</label>
                            <select class="form-select" name="funcionariorut">
                                <option value="">Sin funcionario</option>
                                <?php foreach ($funcionariosOptions as $funcionario): ?>
                                    <option value="<?= htmlspecialchars((string)($funcionario['funcionariorut'] ?? '')) ?>" <?= ((string)($formData['funcionariorut'] ?? '') === (string)($funcionario['funcionariorut'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)($funcionario['funcionarionombre'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select" name="reqcompraprioridad">
                                <option value="">Seleccione</option>
                                <option value="1" <?= (($formData['reqcompraprioridad'] ?? '') === '1') ? 'selected' : '' ?>>Normal</option>
                                <option value="2" <?= (($formData['reqcompraprioridad'] ?? '') === '2') ? 'selected' : '' ?>>Alta</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observación general</label>
                            <textarea class="form-control" name="reqcompraobs" rows="4"><?= htmlspecialchars((string)($formData['reqcompraobs'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="pdh-card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">Detalle de items</h5>
                            <div class="compras-req-table-note">El detalle editable reemplaza la versión vigente al guardar.</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="req-open-items-modal">
                            <i class="bi bi-plus-circle"></i> Agregar item
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="req-detalle-table">
                            <thead>
                                <tr>
                                    <th class="req-detail-table__col-delete"></th>
                                    <th>Ítem</th>
                                    <th class="text-end req-detail-table__col-qty">Cantidad</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Total</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($formData['detalle'] ?? []) as $index => $row): ?>
                                    <?php
                                    $itemInfo = $itemsIndex[(string)($row['invitemid'] ?? '')] ?? [];
                                    $itemName = (string)($itemInfo['invitemdsc'] ?? $detalle[$index]['reqcompradetdsc'] ?? $row['invitemid'] ?? '');
                                    $itemCode = (string)($itemInfo['erpinvitemcod'] ?? $detalle[$index]['reqcompradetitemcod'] ?? $row['invitemid'] ?? '');
                                    $unitName = (string)($itemInfo['invunidmeddsc'] ?? $detalle[$index]['invunidmeddsc'] ?? '');
                                    $subfamilyName = (string)($itemInfo['subfamiliadsc'] ?? $detalle[$index]['subfamiliadsc'] ?? '');
                                    $subfamilyId = (string)($itemInfo['subfamiliaid'] ?? $detalle[$index]['subfamiliaid'] ?? '');
                                    $price = (float)($itemInfo['invitemcostoestandar'] ?? $detalle[$index]['reqcompradetprecioneto'] ?? 0);
                                    ?>
                                    <tr
                                        data-item-id="<?= htmlspecialchars((string)($row['invitemid'] ?? '')) ?>"
                                        data-item-price="<?= htmlspecialchars((string)$price) ?>"
                                        data-subfamily-id="<?= htmlspecialchars($subfamilyId) ?>"
                                        data-subfamily-name="<?= htmlspecialchars($subfamilyName) ?>"
                                    >
                                        <td class="text-end">
                                            <button class="btn btn-outline-danger btn-sm req-remove-row" type="button" title="Eliminar item" aria-label="Eliminar item"><i class="bi bi-trash"></i></button>
                                        </td>
                                        <td>
                                            <input type="hidden" name="detalle[<?= $index ?>][invitemid]" value="<?= htmlspecialchars((string)($row['invitemid'] ?? '')) ?>">
                                            <div class="compras-req-item-cell__title"><?= htmlspecialchars($itemName) ?></div>
                                            <div class="compras-req-item-cell__meta"><?= htmlspecialchars($itemCode) ?></div>
                                            <div class="compras-req-item-cell__meta"><?= htmlspecialchars(trim($unitName === '' ? '' : 'UM: ' . $unitName)) ?></div>
                                        </td>
                                        <td><input class="form-control req-detalle-cantidad text-end" type="number" min="0.0001" step="0.0001" name="detalle[<?= $index ?>][reqcompradetcantidad]" value="<?= htmlspecialchars((string)($row['reqcompradetcantidad'] ?? '')) ?>"></td>
                                        <td class="text-end"><span class="req-detalle-price"><?= htmlspecialchars($fmtMoney($price)) ?></span></td>
                                        <td class="text-end"><span class="req-detalle-total">0</span></td>
                                        <td><input class="form-control" type="text" name="detalle[<?= $index ?>][reqcompradetobs]" value="<?= htmlspecialchars((string)($row['reqcompradetobs'] ?? '')) ?>"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="req-detalle-empty" class="compras-req-empty <?= !empty($formData['detalle']) ? 'd-none' : '' ?>">No hay items cargados.</div>
                </div>

                <div class="pdh-card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">Lista de firmantes</h5>
                            <div class="compras-req-table-note">Jefe de centro y jefe técnico se muestran como preview default. Los adicionales se envían como firmantes manuales.</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="req-open-firmantes-modal">
                            <i class="bi bi-person-plus"></i> Agregar firmante
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="req-firmantes-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Orden</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($formData['firmantesManual'] ?? []) as $index => $row): ?>
                                    <?php
                                    $aprobadorInfo = $aprobadoresIndex[(string)($row['usuarioid'] ?? '')] ?? [];
                                    $userName = (string)($aprobadorInfo['usuarionombre'] ?? $row['usuarioid'] ?? '');
                                    $userRut = (string)($aprobadorInfo['usuariorut'] ?? '');
                                    $userEmail = (string)($aprobadorInfo['usuarioemail'] ?? '');
                                    ?>
                                    <tr data-usuario-id="<?= htmlspecialchars((string)($row['usuarioid'] ?? '')) ?>" data-firmante-kind="manual">
                                        <td>
                                            <input type="hidden" name="firmantesManual[<?= $index ?>][usuarioid]" value="<?= htmlspecialchars((string)($row['usuarioid'] ?? '')) ?>">
                                            <div class="fw-semibold"><?= htmlspecialchars($userName) ?></div>
                                            <div class="compras-req-table-note"><?= htmlspecialchars(trim($userRut . ($userEmail !== '' ? ' | ' . $userEmail : ''))) ?></div>
                                        </td>
                                        <td>
                                            <input class="req-firmante-orden" type="hidden" name="firmantesManual[<?= $index ?>][firmanteorden]" value="<?= htmlspecialchars((string)($row['firmanteorden'] ?? (($index + 3) * 10))) ?>">
                                            <div class="req-firmante-order-controls">
                                                <button class="btn btn-outline-secondary btn-sm req-firmante-move" type="button" data-direction="up" title="Subir">
                                                    <i class="bi bi-arrow-up"></i>
                                                </button>
                                                <span class="req-firmante-order-badge"></span>
                                                <button class="btn btn-outline-secondary btn-sm req-firmante-move" type="button" data-direction="down" title="Bajar">
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-end"><button class="btn btn-outline-danger btn-sm req-remove-firmante" type="button"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div id="req-firmantes-empty" class="compras-req-empty <?= !empty($formData['firmantesManual']) ? 'd-none' : '' ?>">No hay firmantes adicionales. Si el centro tiene jefe de centro o jefe técnico válidos, se mostrarán aquí como preview default.</div>
                </div>

                <div class="pdh-card p-3 mb-3">
                    <h5 class="mb-2">Análisis presupuestario referencial</h5>
                    <div class="compras-req-table-note mb-3">Disponible por subfamilia según los ítems hoy seleccionados. El cálculo formal se mantiene en el análisis informativo persistido del REQ.</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="req-live-ppto-table">
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
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="req-live-ppto-empty" class="compras-req-empty">El análisis referencial se actualizará con los ítems del detalle.</div>
                </div>

                <div class="pdh-card p-3 mb-3">
                    <h5 class="mb-3">Análisis presupuestario informativo</h5>
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
                                    <th class="text-end">Déficit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($analisisPpto)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">Sin análisis disponible.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($analisisPpto as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)($row['subfamiliadsc'] ?? $row['subfamiliaid'] ?? '')) ?></td>
                                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptosaldodisponible'] ?? 0)) ?></td>
                                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptomontootroscurso'] ?? 0)) ?></td>
                                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptomontoaprobadospend'] ?? 0)) ?></td>
                                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptomonto'] ?? 0)) ?></td>
                                            <td class="text-end">$<?= htmlspecialchars($fmtMoney($row['reqpptosaldoproyectado'] ?? 0)) ?></td>
                                            <td class="text-end">
                                                <?php if (!empty($row['reqpptoadvertencia'])): ?>
                                                    <span class="compras-req-pill compras-req-pill--warning">$<?= htmlspecialchars($fmtMoney($row['reqpptodeficit'] ?? 0)) ?></span>
                                                <?php else: ?>
                                                    $<?= htmlspecialchars($fmtMoney($row['reqpptodeficit'] ?? 0)) ?>
                                                <?php endif; ?>
                                            </td>
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
                                            <td><?= htmlspecialchars($fmtDate($comentario['reqcomentariofechahora'] ?? '')) ?></td>
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
    </form>

    <form id="req-cancel-edit-form" method="POST" action="?route=compras-req/cancelar-edicion">
        <?= CsrfHelper::input('web') ?>
        <input type="hidden" name="reqcompraid" value="<?= htmlspecialchars((string)($req['reqcompraid'] ?? '')) ?>">
        <input type="hidden" name="motivo" value="Cancelación de cambios desde formulario de edición.">
    </form>
</div>

<div class="modal fade" id="reqItemsModal" tabindex="-1" aria-labelledby="reqItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reqItemsModalLabel">Seleccionar item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input class="form-control mb-3" type="text" id="req-items-filter" placeholder="Filtrar por código, descripción o subfamilia">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="req-items-table">
                        <thead>
                            <tr>
                                <th class="text-center req-items-table__col-action">+</th>
                                <th>Descripción</th>
                                <th>Código</th>
                                <th class="d-none d-lg-table-cell">Unidad</th>
                                <th class="d-none d-lg-table-cell">Subfamilia</th>
                                <th class="text-end d-none d-lg-table-cell">Neto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itemsRows as $item): ?>
                                <tr
                                    data-item-id="<?= htmlspecialchars((string)($item['invitemid'] ?? '')) ?>"
                                    data-item-code="<?= htmlspecialchars((string)($item['erpinvitemcod'] ?? '')) ?>"
                                    data-item-desc="<?= htmlspecialchars((string)($item['invitemdsc'] ?? '')) ?>"
                                    data-item-unit="<?= htmlspecialchars((string)($item['invunidmeddsc'] ?? '')) ?>"
                                    data-item-subfamily="<?= htmlspecialchars((string)($item['subfamiliadsc'] ?? '')) ?>"
                                    data-item-subfamily-id="<?= htmlspecialchars((string)($item['subfamiliaid'] ?? '')) ?>"
                                    data-item-price="<?= htmlspecialchars((string)($item['invitemcostoestandar'] ?? '0')) ?>"
                                    data-item-type="<?= !empty($item['invitemstockeable']) ? '1' : '2' ?>"
                                >
                                    <td class="text-center"><button type="button" class="btn btn-outline-primary btn-sm req-add-item"><i class="bi bi-plus-circle"></i></button></td>
                                    <td>
                                        <div class="compras-req-item-cell__title"><?= htmlspecialchars((string)($item['invitemdsc'] ?? '')) ?></div>
                                        <div class="compras-req-item-cell__meta d-lg-none"><?= htmlspecialchars((string)($item['invunidmeddsc'] ?? '')) ?></div>
                                        <div class="compras-req-item-cell__meta d-lg-none"><?= htmlspecialchars((string)($item['subfamiliadsc'] ?? '')) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars((string)($item['erpinvitemcod'] ?? '')) ?></td>
                                    <td class="d-none d-lg-table-cell"><?= htmlspecialchars((string)($item['invunidmeddsc'] ?? '')) ?></td>
                                    <td class="d-none d-lg-table-cell"><?= htmlspecialchars((string)($item['subfamiliadsc'] ?? '')) ?></td>
                                    <td class="text-end d-none d-lg-table-cell"><?= htmlspecialchars(number_format((float)($item['invitemcostoestandar'] ?? 0), 0, ',', '.')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reqAprobadoresModal" tabindex="-1" aria-labelledby="reqAprobadoresModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reqAprobadoresModalLabel">Agregar firmante manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input class="form-control mb-3" type="text" id="req-aprobadores-filter" placeholder="Filtrar por RUT, nombre o correo">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="req-aprobadores-table">
                        <thead>
                            <tr>
                                <th>RUT</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aprobadoresRows as $aprobador): ?>
                                <tr
                                    data-usuario-id="<?= htmlspecialchars((string)($aprobador['usuarioid'] ?? '')) ?>"
                                    data-usuario-rut="<?= htmlspecialchars((string)($aprobador['usuariorut'] ?? '')) ?>"
                                    data-usuario-nombre="<?= htmlspecialchars((string)($aprobador['usuarionombre'] ?? '')) ?>"
                                    data-usuario-email="<?= htmlspecialchars((string)($aprobador['usuarioemail'] ?? '')) ?>"
                                >
                                    <td><?= htmlspecialchars((string)($aprobador['usuariorut'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($aprobador['usuarionombre'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($aprobador['usuarioemail'] ?? '')) ?></td>
                                    <td class="text-end"><button type="button" class="btn btn-outline-primary btn-sm req-add-firmante"><i class="bi bi-person-plus"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reqConfirmModal" tabindex="-1" aria-labelledby="reqConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reqConfirmModalLabel">Confirmar acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="req-confirm-message"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="button" class="btn btn-primary" id="req-confirm-accept">Continuar</button>
            </div>
        </div>
    </div>
</div>

<?php if (!$isPartial) { require 'footer.php'; } ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('req-edit-form');
        const cancelEditForm = document.getElementById('req-cancel-edit-form');
        const accionInput = document.getElementById('req-accion');
        const typeSelect = document.getElementById('reqcompratipo');
        const typeHidden = document.getElementById('reqcompratipo-hidden');
        const centerSelect = document.getElementById('req-centrocostoid');
        const centerHidden = document.getElementById('req-centrocostoid-hidden');
        const addItemTrigger = document.getElementById('req-open-items-modal');
        const addFirmanteTrigger = document.getElementById('req-open-firmantes-modal');
        const detalleTableBody = document.querySelector('#req-detalle-table tbody');
        const firmantesTableBody = document.querySelector('#req-firmantes-table tbody');
        const detalleEmpty = document.getElementById('req-detalle-empty');
        const firmantesEmpty = document.getElementById('req-firmantes-empty');
        const livePptoBody = document.querySelector('#req-live-ppto-table tbody');
        const livePptoEmpty = document.getElementById('req-live-ppto-empty');
        const itemsFilterInput = document.getElementById('req-items-filter');
        const aprobadoresFilterInput = document.getElementById('req-aprobadores-filter');
        const centrosDefaultsContainer = document.getElementById('req-centros-defaults-data');
        const confirmModalEl = document.getElementById('reqConfirmModal');
        const confirmMessageEl = document.getElementById('req-confirm-message');
        const confirmAcceptBtn = document.getElementById('req-confirm-accept');
        const confirmModal = window.bootstrap ? new bootstrap.Modal(confirmModalEl) : null;
        const itemsModalEl = document.getElementById('reqItemsModal');
        const aprobadoresModalEl = document.getElementById('reqAprobadoresModal');
        const itemsModal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(itemsModalEl) : null;
        const aprobadoresModal = window.bootstrap ? bootstrap.Modal.getOrCreateInstance(aprobadoresModalEl) : null;
        const pptoValidateUrl = form.dataset.pptoValidateUrl || '';
        const serverDate = form.dataset.serverDate || '';
        const hasCenters = form.dataset.hasCenters === '1';
        let confirmAction = null;
        const budgetCache = new Map();

        const showToast = (message, type = 'info') => {
            if (window.ToastManager && typeof window.ToastManager.show === 'function') {
                window.ToastManager.show(message, type);
                return;
            }
            console.log(type + ': ' + message);
        };

        const formatMoney = (value) => new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(Number(value || 0));
        const getDetalleRows = () => Array.from(detalleTableBody.querySelectorAll('tr[data-item-id]'));
        const getAllFirmanteRows = () => Array.from(firmantesTableBody.querySelectorAll('tr[data-usuario-id]'));
        const getManualFirmanteRows = () => Array.from(firmantesTableBody.querySelectorAll('tr[data-firmante-kind="manual"]'));

        const updateEmptyStates = () => {
            detalleEmpty.classList.toggle('d-none', getDetalleRows().length > 0);
            firmantesEmpty.classList.toggle('d-none', getAllFirmanteRows().length > 0);
        };

        const syncHeaderLockState = () => {
            const shouldLock = getDetalleRows().length > 0;
            typeSelect.disabled = shouldLock;
            centerSelect.disabled = shouldLock;
        };

        const syncHeaderHiddenValues = () => {
            typeHidden.value = typeSelect.value;
            centerHidden.value = centerSelect.value;
        };

        const refreshDetalleIndexes = () => {
            getDetalleRows().forEach((row, index) => {
                row.querySelectorAll('input').forEach((input) => {
                    if (input.name.includes('[invitemid]')) input.name = `detalle[${index}][invitemid]`;
                    if (input.name.includes('[reqcompradetcantidad]')) input.name = `detalle[${index}][reqcompradetcantidad]`;
                    if (input.name.includes('[reqcompradetobs]')) input.name = `detalle[${index}][reqcompradetobs]`;
                });
            });
        };

        const getCentroDefaults = () => {
            const centroId = centerSelect.value;
            if (!centroId) {
                return [];
            }

            const safeCentroId = window.CSS && typeof window.CSS.escape === 'function'
                ? window.CSS.escape(centroId)
                : centroId.replace(/"/g, '\\"');
            const centroData = centrosDefaultsContainer?.querySelector(`[data-centro-id="${safeCentroId}"]`);
            if (!centroData) {
                return [];
            }

            const defaults = [];
            const pushRole = (role) => {
                if (centroData.dataset[`${role}Valido`] !== '1') {
                    return;
                }

                const id = centroData.dataset[`${role}Id`] || '';
                if (!id || defaults.some((row) => row.id === id)) {
                    return;
                }

                defaults.push({
                    id,
                    label: role === 'jefe' ? 'Jefe de centro' : 'Jefe técnico',
                    name: centroData.dataset[`${role}Nombre`] || '',
                    rut: centroData.dataset[`${role}Rut`] || '',
                });
            };

            pushRole('jefe');
            pushRole('tecnico');

            return defaults;
        };

        const refreshFirmanteIndexes = () => {
            const defaultRows = Array.from(firmantesTableBody.querySelectorAll('tr[data-firmante-kind="default"]'));
            const manualRows = getManualFirmanteRows();

            defaultRows.forEach((row, index) => {
                const badge = row.querySelector('.req-firmante-order-badge');
                if (badge) {
                    badge.textContent = String(index + 1);
                }
            });

            manualRows.forEach((row, index) => {
                const usuarioInput = row.querySelector('input[name*="[usuarioid]"]');
                const ordenInput = row.querySelector('input[name*="[firmanteorden]"]');
                const badge = row.querySelector('.req-firmante-order-badge');
                const upButton = row.querySelector('.req-firmante-move[data-direction="up"]');
                const downButton = row.querySelector('.req-firmante-move[data-direction="down"]');

                if (usuarioInput) {
                    usuarioInput.name = `firmantesManual[${index}][usuarioid]`;
                }
                if (ordenInput) {
                    ordenInput.name = `firmantesManual[${index}][firmanteorden]`;
                    ordenInput.value = String((index + 3) * 10);
                }
                if (badge) {
                    badge.textContent = String(defaultRows.length + index + 1);
                }
                if (upButton) {
                    upButton.disabled = index === 0;
                }
                if (downButton) {
                    downButton.disabled = index === manualRows.length - 1;
                }
            });
        };

        const renderDefaultFirmantes = () => {
            firmantesTableBody.querySelectorAll('tr[data-firmante-kind="default"]').forEach((row) => row.remove());

            const defaults = getCentroDefaults();
            defaults.reverse().forEach((signer) => {
                const tr = document.createElement('tr');
                tr.dataset.usuarioId = signer.id;
                tr.dataset.firmanteKind = 'default';
                tr.innerHTML = `
                    <td>
                        <div class="fw-semibold">${signer.name || signer.label}</div>
                        <div class="compras-req-table-note">${signer.rut || ''}${signer.rut ? ' | ' : ''}${signer.label}</div>
                    </td>
                    <td><span class="req-firmante-order-badge"></span></td>
                    <td class="text-end"><span class="compras-req-pill">${signer.label}</span></td>
                `;
                firmantesTableBody.prepend(tr);
            });

            const defaultIds = new Set(defaults.map((row) => row.id));
            getManualFirmanteRows().forEach((row) => {
                if (defaultIds.has(row.dataset.usuarioId || '')) {
                    row.remove();
                }
            });

            refreshFirmanteIndexes();
            updateEmptyStates();
        };

        const getExistingFirmanteIds = () => {
            return new Set(getAllFirmanteRows().map((row) => row.dataset.usuarioId || '').filter(Boolean));
        };

        const applyAprobadoresFilter = () => {
            const term = (aprobadoresFilterInput?.value || '').toLowerCase();
            const existingIds = getExistingFirmanteIds();
            document.querySelectorAll('#req-aprobadores-table tbody tr').forEach((row) => {
                const alreadySelected = existingIds.has(row.dataset.usuarioId || '');
                const matchesSearch = row.innerText.toLowerCase().includes(term);
                row.classList.toggle('d-none', alreadySelected || !matchesSearch);
            });
        };

        const refreshRowTotals = () => {
            getDetalleRows().forEach((row) => {
                const price = Number(row.dataset.itemPrice || 0);
                const qtyInput = row.querySelector('.req-detalle-cantidad');
                const qty = Number((qtyInput?.value || '0').replace(',', '.'));
                const total = price * qty;
                const totalEl = row.querySelector('.req-detalle-total');
                if (totalEl) {
                    totalEl.textContent = formatMoney(total);
                }
            });
        };

        const renderBudgetPreview = () => {
            const grouped = new Map();
            getDetalleRows().forEach((row) => {
                const key = row.dataset.subfamilyId || '';
                if (!key) {
                    return;
                }

                const qty = Number((row.querySelector('.req-detalle-cantidad')?.value || '0').replace(',', '.'));
                const price = Number(row.dataset.itemPrice || 0);
                const requested = qty * price;
                const current = grouped.get(key) || {
                    subfamilyName: row.dataset.subfamilyName || key,
                    saldoDisponible: Number(row.dataset.pptoSaldo || 0),
                    otrosReq: Number(row.dataset.pptoOtrosReq || 0),
                    aprobados: Number(row.dataset.pptoAprobados || 0),
                    requested: 0,
                };
                current.requested += requested;
                grouped.set(key, current);
            });

            livePptoBody.innerHTML = '';
            if (!grouped.size) {
                livePptoEmpty.classList.remove('d-none');
                return;
            }

            livePptoEmpty.classList.add('d-none');
            grouped.forEach((entry) => {
                const projected = entry.saldoDisponible - entry.otrosReq - entry.aprobados - entry.requested;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${entry.subfamilyName}</td>
                    <td class="text-end">$${formatMoney(entry.saldoDisponible)}</td>
                    <td class="text-end">$${formatMoney(entry.otrosReq)}</td>
                    <td class="text-end">$${formatMoney(entry.aprobados)}</td>
                    <td class="text-end">$${formatMoney(entry.requested)}</td>
                    <td class="text-end ${projected < 0 ? 'text-danger fw-semibold' : ''}">$${formatMoney(projected)}</td>
                `;
                livePptoBody.appendChild(tr);
            });
        };

        const ensureCentroSelected = (message) => {
            if (!hasCenters || !centerSelect.value) {
                showToast(message, 'warning');
                return false;
            }
            return true;
        };

        const ensureTipoSelected = () => {
            if (!typeSelect.value) {
                showToast('Seleccione primero el tipo de REQ.', 'warning');
                return false;
            }
            return true;
        };

        const applyItemFilters = () => {
            const term = (itemsFilterInput?.value || '').toLowerCase();
            const currentType = typeSelect.value;
            document.querySelectorAll('#req-items-table tbody tr').forEach((row) => {
                const matchesSearch = row.innerText.toLowerCase().includes(term);
                const matchesType = currentType === '' || row.dataset.itemType === currentType;
                row.classList.toggle('d-none', !(matchesSearch && matchesType));
            });
        };

        const validateBudgetForItem = async (itemId) => {
            const cacheKey = [itemId, centerSelect.value, typeSelect.value, serverDate].join(':');
            if (budgetCache.has(cacheKey)) {
                return budgetCache.get(cacheKey);
            }

            const params = new URLSearchParams({
                invitemid: itemId,
                centrocostoid: centerSelect.value,
                reqcompratipo: typeSelect.value,
                fecha: serverDate
            });

            const response = await fetch(`${pptoValidateUrl}&${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const payload = await response.json();
            if (!response.ok || Number(payload.status || 500) !== 200) {
                throw new Error(payload.message || 'No fue posible validar presupuesto para el ítem.');
            }

            budgetCache.set(cacheKey, payload);
            return payload;
        };

        const appendDetalleRow = (item, pptoInfo) => {
            if (Number(item.price) <= 0) {
                showToast('El ítem no puede agregarse porque tiene precio cero.', 'warning');
                return;
            }
            if (detalleTableBody.querySelector(`tr[data-item-id="${item.id}"]`)) {
                showToast('El ítem ya fue agregado al detalle.', 'warning');
                return;
            }

            const index = getDetalleRows().length;
            const tr = document.createElement('tr');
            tr.dataset.itemId = item.id;
            tr.dataset.itemPrice = item.price;
            tr.dataset.subfamilyId = item.subfamilyId || '';
            tr.dataset.subfamilyName = item.subfamily || '';
            tr.dataset.pptoSaldo = String(pptoInfo?.presupuesto?.saldoDisponible || 0);
            tr.dataset.pptoOtrosReq = String(pptoInfo?.presupuesto?.otrosReq || 0);
            tr.dataset.pptoAprobados = String(pptoInfo?.presupuesto?.aprobados || 0);
            tr.innerHTML = `
                <td class="text-end">
                    <button class="btn btn-outline-danger btn-sm req-remove-row" type="button" title="Eliminar item" aria-label="Eliminar item"><i class="bi bi-trash"></i></button>
                </td>
                <td>
                    <input type="hidden" name="detalle[${index}][invitemid]" value="${item.id}">
                    <div class="compras-req-item-cell__title">${item.desc}</div>
                    <div class="compras-req-item-cell__meta">${item.code}</div>
                    <div class="compras-req-item-cell__meta">${item.unit ? `UM: ${item.unit}` : ''}</div>
                </td>
                <td><input class="form-control req-detalle-cantidad text-end" type="number" min="0.0001" step="0.0001" name="detalle[${index}][reqcompradetcantidad]" value="1"></td>
                <td class="text-end"><span class="req-detalle-price">${formatMoney(item.price)}</span></td>
                <td class="text-end"><span class="req-detalle-total">${formatMoney(item.price)}</span></td>
                <td><input class="form-control" type="text" name="detalle[${index}][reqcompradetobs]" value=""></td>
            `;
            detalleTableBody.appendChild(tr);
            refreshDetalleIndexes();
            refreshRowTotals();
            renderBudgetPreview();
            updateEmptyStates();
            syncHeaderLockState();
            itemsModal?.hide();

            if (pptoInfo?.presupuesto?.advertenciaSaldo) {
                showToast('El ítem tiene presupuesto vigente, pero sin saldo disponible al momento del análisis. Continuará bajo advertencia presupuestaria.', 'warning');
            }
        };

        const appendFirmanteRow = (user) => {
            if (firmantesTableBody.querySelector(`tr[data-usuario-id="${user.id}"]`)) {
                showToast('El firmante ya fue agregado.', 'warning');
                return;
            }

            const tr = document.createElement('tr');
            tr.dataset.usuarioId = user.id;
            tr.dataset.firmanteKind = 'manual';
            tr.innerHTML = `
                <td>
                    <input type="hidden" name="firmantesManual[0][usuarioid]" value="${user.id}">
                    <div class="fw-semibold">${user.name}</div>
                    <div class="compras-req-table-note">${user.rut} | ${user.email}</div>
                </td>
                <td>
                    <input class="req-firmante-orden" type="hidden" name="firmantesManual[0][firmanteorden]" value="30">
                    <div class="req-firmante-order-controls">
                        <button class="btn btn-outline-secondary btn-sm req-firmante-move" type="button" data-direction="up" title="Subir">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <span class="req-firmante-order-badge"></span>
                        <button class="btn btn-outline-secondary btn-sm req-firmante-move" type="button" data-direction="down" title="Bajar">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                    </div>
                </td>
                <td class="text-end"><button class="btn btn-outline-danger btn-sm req-remove-firmante" type="button"><i class="bi bi-trash"></i></button></td>
            `;
            firmantesTableBody.appendChild(tr);
            refreshFirmanteIndexes();
            updateEmptyStates();
            applyAprobadoresFilter();
            aprobadoresModal?.hide();
        };

        const hydrateExistingBudgetData = async () => {
            for (const row of getDetalleRows()) {
                if (
                    row.dataset.subfamilyId === ''
                    || (
                        typeof row.dataset.pptoSaldo !== 'undefined'
                        && typeof row.dataset.pptoOtrosReq !== 'undefined'
                        && typeof row.dataset.pptoAprobados !== 'undefined'
                    )
                ) {
                    continue;
                }
                try {
                    const payload = await validateBudgetForItem(row.dataset.itemId);
                    row.dataset.pptoSaldo = String(payload?.presupuesto?.saldoDisponible || 0);
                    row.dataset.pptoOtrosReq = String(payload?.presupuesto?.otrosReq || 0);
                    row.dataset.pptoAprobados = String(payload?.presupuesto?.aprobados || 0);
                } catch (error) {
                    row.dataset.pptoSaldo = '0';
                    row.dataset.pptoOtrosReq = '0';
                    row.dataset.pptoAprobados = '0';
                }
            }
            renderBudgetPreview();
        };

        addItemTrigger?.addEventListener('click', function (event) {
            event.preventDefault();
            if (!ensureCentroSelected('Debe seleccionar un centro de costo antes de agregar ítems.')) {
                return;
            }
            if (!ensureTipoSelected()) {
                return;
            }
            applyItemFilters();
            itemsModal?.show();
        });

        addFirmanteTrigger?.addEventListener('click', function (event) {
            event.preventDefault();
            if (!ensureCentroSelected('Debe seleccionar un centro de costo antes de agregar firmantes.')) {
                return;
            }
            applyAprobadoresFilter();
            aprobadoresModal?.show();
        });

        detalleTableBody.addEventListener('click', function (event) {
            const btn = event.target.closest('.req-remove-row');
            if (!btn) return;
            btn.closest('tr')?.remove();
            refreshDetalleIndexes();
            refreshRowTotals();
            renderBudgetPreview();
            updateEmptyStates();
            syncHeaderLockState();
            applyItemFilters();
        });

        detalleTableBody.addEventListener('input', function (event) {
            if (event.target.classList.contains('req-detalle-cantidad')) {
                refreshRowTotals();
                renderBudgetPreview();
            }
        });

        firmantesTableBody.addEventListener('click', function (event) {
            const btn = event.target.closest('.req-remove-firmante');
            if (btn) {
                btn.closest('tr')?.remove();
                refreshFirmanteIndexes();
                updateEmptyStates();
                applyAprobadoresFilter();
                return;
            }

            const moveBtn = event.target.closest('.req-firmante-move');
            if (!moveBtn) return;

            const row = moveBtn.closest('tr[data-firmante-kind="manual"]');
            if (!row) return;

            const manualRows = getManualFirmanteRows();
            const index = manualRows.indexOf(row);
            if (index === -1) return;

            if (moveBtn.dataset.direction === 'up' && index > 0) {
                manualRows[index - 1].before(row);
            }
            if (moveBtn.dataset.direction === 'down' && index < manualRows.length - 1) {
                manualRows[index + 1].after(row);
            }

            refreshFirmanteIndexes();
        });

        document.querySelectorAll('.req-add-item').forEach((button) => {
            button.addEventListener('click', async function () {
                if (!ensureCentroSelected('Debe seleccionar un centro de costo antes de agregar ítems.')) {
                    return;
                }
                if (!ensureTipoSelected()) {
                    return;
                }
                const row = button.closest('tr');
                try {
                    button.disabled = true;
                    const payload = await validateBudgetForItem(row.dataset.itemId);
                    appendDetalleRow({
                        id: row.dataset.itemId,
                        code: row.dataset.itemCode,
                        desc: row.dataset.itemDesc,
                        unit: row.dataset.itemUnit,
                        subfamily: row.dataset.itemSubfamily,
                        subfamilyId: row.dataset.itemSubfamilyId,
                        price: row.dataset.itemPrice,
                    }, payload);
                } catch (error) {
                    showToast(error.message, 'danger');
                } finally {
                    button.disabled = false;
                }
            });
        });

        document.querySelectorAll('.req-add-firmante').forEach((button) => {
            button.addEventListener('click', function () {
                if (!ensureCentroSelected('Debe seleccionar un centro de costo antes de agregar firmantes.')) {
                    return;
                }
                const row = button.closest('tr');
                appendFirmanteRow({
                    id: row.dataset.usuarioId,
                    rut: row.dataset.usuarioRut,
                    name: row.dataset.usuarioNombre,
                    email: row.dataset.usuarioEmail
                });
            });
        });

        itemsFilterInput?.addEventListener('input', applyItemFilters);
        aprobadoresFilterInput?.addEventListener('input', applyAprobadoresFilter);
        typeSelect?.addEventListener('change', function () {
            syncHeaderHiddenValues();
            applyItemFilters();
        });
        centerSelect?.addEventListener('change', function () {
            syncHeaderHiddenValues();
            renderDefaultFirmantes();
            applyAprobadoresFilter();
        });

        document.querySelectorAll('[data-req-submit]').forEach((button) => {
            button.addEventListener('click', function () {
                const action = button.getAttribute('data-req-submit');
                accionInput.value = action;
                syncHeaderHiddenValues();
                confirmMessageEl.textContent = action === 'guardar_borrador'
                    ? 'Los cambios quedarán guardados y el REQ pasará a borrador. ¿Desea continuar?'
                    : 'Los cambios se guardarán y el REQ se reenviará a aprobación. ¿Desea continuar?';
                confirmAction = () => {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                        return;
                    }
                    form.submit();
                };
                confirmModal?.show();
            });
        });

        document.querySelector('[data-req-confirm="volver-ver"]')?.addEventListener('click', function () {
            window.location.href = '?route=compras-req/ver&id=<?= urlencode((string)($req['reqcompraid'] ?? '')) ?>';
        });

        document.querySelector('[data-req-confirm="cancel-edit"]')?.addEventListener('click', function () {
            confirmMessageEl.textContent = 'Esta acción NO guardará los cambios realizados y volverá a dejar el requerimiento Pendiente de Aprobación (En Curso). ¿Desea continuar?';
            confirmAction = () => {
                if (typeof cancelEditForm.requestSubmit === 'function') {
                    cancelEditForm.requestSubmit();
                    return;
                }
                cancelEditForm.submit();
            };
            confirmModal?.show();
        });

        confirmAcceptBtn?.addEventListener('click', function () {
            if (typeof confirmAction === 'function') {
                confirmAction();
            }
        });

        updateEmptyStates();
        refreshDetalleIndexes();
        refreshRowTotals();
        syncHeaderHiddenValues();
        syncHeaderLockState();
        hydrateExistingBudgetData();
        renderDefaultFirmantes();
        applyItemFilters();
        applyAprobadoresFilter();
    });
</script>
