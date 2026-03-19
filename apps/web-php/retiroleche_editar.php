<?php
// Variables: $retiro, $errorMessage
$isPartial = $partial ?? false;
$fundosOptions = $fundosOptions ?? [];
$fundosestanquesclientesOptions = $fundosestanquesclientesOptions ?? [];
$formData = $retiro ?? [];

$fechaValue = $formData['retirolechefecha'] ?? '';
if (!empty($fechaValue)) {
    $fechaValue = substr((string)$fechaValue, 0, 10);
}
$horaIniValue = $formData['retirolechehoraini'] ?? '';
if (!empty($horaIniValue)) {
    $horaIniValue = substr((string)$horaIniValue, 0, 5);
}
$horaFinValue = $formData['retirolechehorafin'] ?? '';
if (!empty($horaFinValue)) {
    $horaFinValue = substr((string)$horaFinValue, 0, 5);
}

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<link rel="stylesheet" href="assets/css/frm_ins_upd.css">

<div class="container-fluid px-3 py-3">
    <div class="form-shell">
        <h3 class="mb-3">Editar Retiro de Leche</h3>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form id="retirolecheForm" method="POST" action="?route=retiroleche/editar" enctype="multipart/form-data"
            autocomplete="off"
            data-confirm="1"
            data-confirm-message="¿Desea confirmar los datos ingresados?">
            <input type="hidden" name="retirolecheid" value="<?= htmlspecialchars($formData['retirolecheid'] ?? '') ?>">
            <input type="hidden" name="fundoid" value="<?= htmlspecialchars($formData['fundoid'] ?? '') ?>">
            <input type="hidden" name="fundoestanqueid" value="<?= htmlspecialchars($formData['fundoestanqueid'] ?? '') ?>">
            <input type="hidden" name="clienteid" value="<?= htmlspecialchars($formData['clienteid'] ?? '') ?>">
            <input type="hidden" name="estanqueclientecod" value="<?= htmlspecialchars($formData['estanqueclientecod'] ?? '') ?>">
            <input type="hidden" name="retirolechefecha" value="<?= htmlspecialchars($fechaValue) ?>">
            <input type="hidden" name="retirolechefoto_actual" value="<?= htmlspecialchars($formData['retirolechefoto'] ?? '') ?>">

            <section class="mb-4">
                <!-- Leyenda de (*) Obligatorios-->
                <h6>(*) Obligatorios</h6>
                <h5 class="section-title">Datos Generales</h5>
                <div class="row g-3 align-items-end general-grid">
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="fundoid_disabled" class="form-label">Fundo (*)</label>
                        <select name="fundoid_disabled" id="fundoid_disabled" class="form-select" disabled>
                            <option value="">Seleccione un fundo</option>
                            <?php foreach ($fundosOptions as $fundo): ?>
                                <?php
                                $fundoId = $fundo['fundoid'] ?? '';
                                $fundoSelected = (string)($formData['fundoid'] ?? '') === (string)$fundoId ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($fundoId) ?>" <?= $fundoSelected ?>>
                                    <?= htmlspecialchars($fundo['fundonombre'] ?? ($fundoId ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="retirolechefecha_disabled" class="form-label">Fecha (*)</label>
                        <input
                            type="date"
                            id="retirolechefecha_disabled"
                            class="form-control"
                            value="<?= htmlspecialchars($fechaValue) ?>"
                            disabled
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2 d-none">
                        <label for="retirolechehoraini" class="form-label">Hora Inicio</label>
                        <input
                            type="time"
                            name="retirolechehoraini"
                            id="retirolechehoraini"
                            class="form-control"
                            value="<?= htmlspecialchars($horaIniValue) ?>"
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="retirolechehorafin" class="form-label">Hora Retiro/Termino (*)</label>
                        <input
                            type="time"
                            name="retirolechehorafin"
                            id="retirolechehorafin"
                            class="form-control"
                            value="<?= htmlspecialchars($horaFinValue) ?>"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="fundoestanqueid_disabled" class="form-label">Estanque (*)</label>
                        <select name="fundoestanqueid_disabled" id="fundoestanqueid_disabled" class="form-select" disabled>
                            <option value="">Seleccione un estanque</option>
                            <?php foreach ($fundosestanquesclientesOptions as $option): ?>
                                <?php
                                $optionId = $option['fundoestanqueid'] ?? '';
                                $optionSelected = (string)($formData['fundoestanqueid'] ?? '') === (string)$optionId ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($optionId) ?>" <?= $optionSelected ?>>
                                    <?= htmlspecialchars($option['fundoestanqueclientedsc'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="retirolechetemperatura" class="form-label">Temperatura</label>
                        <input
                            type="number"
                            name="retirolechetemperatura"
                            id="retirolechetemperatura"
                            class="form-control"
                            min="0"
                            step="0.1"
                            value="<?= htmlspecialchars($formData['retirolechetemperatura'] ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label class="form-label">Codigo Cliente</label>
                        <input
                            type="number"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['estanqueclientecod'] ?? '') ?>"
                            disabled
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="retirolechelitros" class="form-label">Litros (*)</label>
                        <input
                            type="number"
                            name="retirolechelitros"
                            id="retirolechelitros"
                            class="form-control"
                            min="0"
                            step="1"
                            value="<?= htmlspecialchars($formData['retirolechelitros'] ?? '') ?>"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-4">
                        <label for="retirolechefoto" class="form-label">Imagen (*)</label>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input type="file" name="retirolechefoto" id="retirolechefoto" class="d-none" accept="image/*">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectFoto">
                                <i class="bi bi-image"></i> Cambiar
                            </button>
                            <span class="text-muted small" id="retirolechefotoName"><?= htmlspecialchars($formData['retirolechefoto'] ?? 'Sin archivo') ?></span>
                            <?php if (!empty($formData['retirolechefoto'])): ?>
                                <a class="btn btn-outline-primary btn-sm" target="_blank"
                                   href="uploads/retiroleche/img/<?= htmlspecialchars($formData['retirolecheid'] ?? '') ?>/<?= htmlspecialchars($formData['retirolechefoto']) ?>">
                                    <i class="bi bi-box-arrow-up-right"></i> Ver
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="retirolecheobservacion" class="form-label">Observacion</label>
                        <textarea
                            name="retirolecheobservacion"
                            id="retirolecheobservacion"
                            class="form-control"
                            rows="2"
                            maxlength="100"
                        ><?= htmlspecialchars($formData['retirolecheobservacion'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>

            <div class="d-flex gap-2 justify-content-end">
                <a href="?route=retiroleche/listar" class="btn btn-outline-secondary">Volver</a>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const fileInput = document.getElementById('retirolechefoto');
        const fileName = document.getElementById('retirolechefotoName');
        const fileBtn = document.getElementById('btnSelectFoto');

        if (fileBtn && fileInput) {
            fileBtn.addEventListener('click', () => fileInput.click());
        }
        if (fileInput && fileName) {
            fileInput.addEventListener('change', () => {
                const name = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : fileName.textContent;
                fileName.textContent = name;
            });
        }
    })();
</script>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
