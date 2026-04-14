(function () {
  let pendingForm = null;
  let modalInstance = null;
  let isSubmitting = false;

  function getModal() {
    const el = document.getElementById('confirmSubmitModal');
    if (!el || !window.bootstrap) return null;
    if (!modalInstance) modalInstance = new bootstrap.Modal(el);
    return { el, instance: modalInstance };
  }

  function setMessage(message) {
    const body = document.getElementById('confirmSubmitModalBody');
    if (body) body.textContent = message || '¿Desea confirmar los datos ingresados?';
  }

  // Intercepta submit solo si el form declara data-confirm="1"
  document.addEventListener('submit', function (evt) {
    const form = evt.target;
    if (!form || form.tagName !== 'FORM') return;

    const wantsConfirm = form.getAttribute('data-confirm') === '1';
    if (!wantsConfirm) return;

    // Si ya está confirmado, dejamos pasar
    if (form.dataset.confirmed === '1') return;

    const modal = getModal();
    if (!modal) return; // si no hay bootstrap modal, no bloqueamos

    evt.preventDefault();
    pendingForm = form;

    // Mensaje opcional por form
    setMessage(form.getAttribute('data-confirm-message'));

    modal.instance.show();
  }, true);

  // Botón Confirmar
  document.addEventListener('click', function (evt) {
    const okBtn = evt.target.closest('#confirmSubmitModalBtnOk');
    if (!okBtn) return;

    if (!pendingForm) return;
    if (isSubmitting) return;

    if (typeof pendingForm.reportValidity === 'function' && !pendingForm.reportValidity()) {
      return;
    }

    // Permite sobreescribir action dinámicamente si se define
    const forcedAction = pendingForm.getAttribute('data-confirm-action');
    if (forcedAction) {
      pendingForm.setAttribute('action', forcedAction);
    }

    isSubmitting = true;
    okBtn.disabled = true;
    pendingForm.dataset.confirmed = '1';

    if (typeof pendingForm.requestSubmit === 'function') {
      pendingForm.requestSubmit();
    } else {
      pendingForm.submit();
    }

    pendingForm = null;
  });

  document.addEventListener('hidden.bs.modal', function (evt) {
    if (evt.target?.id !== 'confirmSubmitModal') return;
    if (isSubmitting) return;

    const okBtn = document.getElementById('confirmSubmitModalBtnOk');
    if (okBtn) okBtn.disabled = false;
    pendingForm = null;
  });

  window.addEventListener('pageshow', function () {
    isSubmitting = false;
    const okBtn = document.getElementById('confirmSubmitModalBtnOk');
    if (okBtn) okBtn.disabled = false;
  });
})();
