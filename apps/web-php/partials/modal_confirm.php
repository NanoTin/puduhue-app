<?php
// Modal confirm reusable (Bootstrap 5)
// Requiere que Bootstrap JS esté cargado (bundle)
?>
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="confirmSubmitModalBody">
        ¿Desea confirmar los datos ingresados?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="confirmSubmitModalBtnOk">Confirmar</button>
      </div>
    </div>
  </div>
</div>
