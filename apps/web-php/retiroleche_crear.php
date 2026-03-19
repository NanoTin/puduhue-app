<?php
$isPartial = $partial ?? false;
$fundosOptions = $fundosOptions ?? [];
$fundosestanquesclientesOptions = $fundosestanquesclientesOptions ?? [];
$formData = $formData ?? [];
$defaultFecha = date('Y-m-d');

if (!$isPartial) {
    require 'head.php';
    require 'menu.php';
}
?>

<link rel="stylesheet" href="assets/css/frm_ins_upd.css">

<div class="container-fluid px-3 py-3">
    <div class="form-shell">
        <h3 class="mb-3">Crear Retiro de Leche</h3>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form id="retirolecheForm" method="POST" action="?route=retiroleche/crear" enctype="multipart/form-data"
            autocomplete="off"
            data-confirm="1"
            data-confirm-message="¿Desea confirmar los datos ingresados?">
            <section class="mb-4">
                <!-- Leyenda de (*) Obligatorios-->
                <h6>(*) Obligatorios</h6>
                <h5 class="section-title">Datos Generales</h5>
                <div class="row g-3 align-items-end general-grid">
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="fundoid" class="form-label">Fundo (*)</label>
                        <select name="fundoid" id="fundoid" class="form-select" required>
                            <option value="">Seleccione un fundo</option>
                            <?php foreach ($fundosOptions as $fundo): ?>
                                <?php
                                $fundoId = $fundo['fundoid'] ?? '';
                                $fundoSelected = (string)($formData['fundoid'] ?? $fundoIdWS) === (string)$fundoId ? 'selected' : '';
                                ?>
                                <option value="<?= htmlspecialchars($fundoId) ?>" <?= $fundoSelected ?>>
                                    <?= htmlspecialchars($fundo['fundonombre'] ?? ($fundoId ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="retirolechefecha" class="form-label">Fecha (*)</label>
                        <input
                            type="date"
                            name="retirolechefecha"
                            id="retirolechefecha"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['retirolechefecha'] ?? $defaultFecha) ?>"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2 d-none">
                        <label for="retirolechehoraini" class="form-label">Hora Inicio</label>
                        <input
                            type="time"
                            name="retirolechehoraini"
                            id="retirolechehoraini"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['retirolechehoraini'] ?? '') ?>"
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="retirolechehorafin" class="form-label">Hora Retiro/Termino (*)</label>
                        <input
                            type="time"
                            name="retirolechehorafin"
                            id="retirolechehorafin"
                            class="form-control"
                            value="<?= htmlspecialchars($formData['retirolechehorafin'] ?? '') ?>"
                            required
                        >
                    </div>
                    <div class="col-12 col-lg-6 col-xl-2">
                        <label for="fundoestanqueid" class="form-label">Estanque (*)</label>
                        <select name="fundoestanqueid" id="fundoestanqueid" class="form-select" required>
                            <option value="">Seleccione un estanque</option>
                            <?php foreach ($fundosestanquesclientesOptions as $option): ?>
                                <?php
                                $optionId = $option['fundoestanqueid'] ?? '';
                                $optionCliente = $option['clienteid'] ?? '';
                                $optionSelected = (string)($formData['fundoestanqueid'] ?? '') === (string)$optionId ? 'selected' : '';
                                $optionFundo = $option['fundoid'] ?? '';
                                ?>
                                <option
                                    value="<?= htmlspecialchars($optionId) ?>"
                                    data-clienteid="<?= htmlspecialchars($optionCliente) ?>"
                                    data-estanqueclientecod="<?= htmlspecialchars($option['estanqueclientecod'] ?? '') ?>"
                                    data-fundoid="<?= htmlspecialchars($optionFundo) ?>"
                                    <?= $optionSelected ?>
                                >
                                    <?= htmlspecialchars($option['fundoestanqueclientedsc'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="clienteid" id="clienteid" value="<?= htmlspecialchars($formData['clienteid'] ?? '') ?>">
                        <input type="hidden" name="estanqueclientecod" id="estanqueclientecod" value="<?= htmlspecialchars($formData['estanqueclientecod'] ?? '') ?>">
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
                        <div class="d-flex align-items-center gap-2">
                            <input type="file" name="retirolechefoto" id="retirolechefoto" class="d-none" accept="image/*" required>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSelectFoto">
                                <i class="bi bi-image"></i> Seleccionar
                            </button>
                            <span class="text-muted small" id="retirolechefotoName">Sin archivo</span>
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
        const fundoSelect = document.getElementById('fundoid');
        const estanqueSelect = document.getElementById('fundoestanqueid');
        const clienteInput = document.getElementById('clienteid');
        const estanqueClienteCodInput = document.getElementById('estanqueclientecod');
        const fileInput = document.getElementById('retirolechefoto');
        const fileName = document.getElementById('retirolechefotoName');
        const fileBtn = document.getElementById('btnSelectFoto');

        function refreshEstanques() {
            const fundoId = fundoSelect?.value || '';
            const current = estanqueSelect?.value || '';
            let hasValue = false;
            Array.from(estanqueSelect.options).forEach((opt) => {
                if (!opt.value) return;
                const match = opt.dataset.fundoid === fundoId;
                opt.hidden = !match;
                if (match && current === opt.value) {
                    hasValue = true;
                }
            });
            if (!hasValue) {
                estanqueSelect.value = '';
            }
            syncCliente();
        }

        function syncCliente() {
            if (!estanqueSelect || !clienteInput || !estanqueClienteCodInput) return;
            const selected = estanqueSelect.options[estanqueSelect.selectedIndex];
            clienteInput.value = selected ? (selected.dataset.clienteid || '') : '';
            estanqueClienteCodInput.value = selected ? (selected.dataset.estanqueclientecod || '') : '';
        }

        if (fundoSelect) {
            fundoSelect.addEventListener('change', refreshEstanques);
        }

        if (estanqueSelect) {
            estanqueSelect.addEventListener('change', syncCliente);
            refreshEstanques();
        }

        if (fileBtn && fileInput) {
            fileBtn.addEventListener('click', () => fileInput.click());
        }
        if (fileInput && fileName) {
            fileInput.addEventListener('change', () => {
                const name = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : 'Sin archivo';
                fileName.textContent = name;
            });
        }
    })();
</script>

<?php require __DIR__ . '/partials/modal_confirm.php'; ?>
<script src="assets/js/confirm-modal.js"></script>

<?php if (!$isPartial) { require 'footer.php'; } ?>
