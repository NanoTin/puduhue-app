/*
Incremental 11 - SP y reglas presupuestarias para Compras.

Este archivo queda sin DDL de bases compartidas por diseno:
- el incremental 07 crea/ajusta usuarios, items, pptocompra, usuarios-centros,
  funcionarios, periodos de inactividad, proveedores y condiciones de pago;
- el incremental 08 crea REQ base;
- el incremental 09 crea pendientes de compra (`reqaprobados*`);
- el incremental 10 crea PreOC.

Alcance pendiente de implementar cuando se cierre contrato de parametros/salidas:
- resolver presupuesto por fecha, subfamilia y centro;
- analizar REQ sin bloquear ni generar movimientos;
- generar snapshot REQ agrupado por subfamilia y centro;
- reservar PreOC al pasar de BRR a PND con movimiento POC_RESERVA negativo;
- confirmar reserva al aprobar con movimiento POC_CONFIRMACION;
- revertir por rechazo/anulacion con movimiento POC_REVERSA positivo;
- liberar reserva si vuelve de PND a BRR antes de aprobaciones;
- recalcular totales de pptocompra desde pptocompratransacciones;
- registrar referencias de origen con modulo, documento y linea.

No inventar SP, tablas, columnas ni reglas adicionales en este incremental sin
un contrato funcional/tecnico cerrado.
*/
