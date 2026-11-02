<?php include_once 'Views/template/header-admin.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-pedidos-admin-v47.css?v=471">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-recetas-preview-v471.css?v=471">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-citas-valoracion-v48.css?v=48">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-validacion-unificada-v481.css?v=481">
<div id="ivc-pedidos-v47" data-solo-lectura="<?php echo RolesAcceso::rol() === 'VENDEDOR' ? '1' : '0'; ?>" data-csrf="<?php echo htmlspecialchars($data['csrf'], ENT_QUOTES, 'UTF-8'); ?>">
  <div class="ivc-pedidos-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <div class="ivc-pedidos-eyebrow">INSTITUTO VISUAL CALIFORNIA · OPERACIONES</div>
      <h2 class="mb-1">Administración de pedidos</h2>
      <p class="mb-0">Control de pagos, micas, recetas y preparación de pedidos.</p>
    </div>
    <button id="ivcPedRefresh" type="button" class="btn btn-light"><i class="bx bx-refresh"></i> Actualizar</button>
  </div>
  <div class="row g-3 mb-4" id="ivcPedResumen">
    <div class="col-6 col-lg-3"><div class="ivc-pedidos-kpi"><span>Pedidos consultados</span><strong id="ivcPedTotal">—</strong></div></div>
    <div class="col-6 col-lg-3"><div class="ivc-pedidos-kpi"><span>Por preparar</span><strong id="ivcPedPendientes">—</strong></div></div>
    <div class="col-6 col-lg-3"><div class="ivc-pedidos-kpi"><span>Por validar</span><strong id="ivcPedBloqueados">—</strong></div></div>
    <div class="col-6 col-lg-3"><div class="ivc-pedidos-kpi"><span>Finalizados</span><strong id="ivcPedFinalizados">—</strong></div></div>
  </div>
  <div class="card ivc-pedidos-panel mb-4"><div class="card-body">
    <div class="d-flex flex-wrap align-items-end gap-3 mb-3">
      <div class="flex-grow-1"><label class="form-label" for="ivcPedBusqueda">Buscar por pedido, cliente o referencia de pago</label>
        <input id="ivcPedBusqueda" type="search" maxlength="100" class="form-control" placeholder="Ej. número de pedido, nombre o correo"></div>
      <div><label class="form-label" for="ivcPedFiltro">Etapa comercial</label>
        <select id="ivcPedFiltro" class="form-select"><option value="">Todas</option><option value="1">Pendientes</option><option value="2">En preparación</option><option value="3">Finalizados</option></select></div>
      <button type="button" id="ivcPedBuscar" class="btn btn-primary">Buscar</button>
    </div>
    <div id="ivcPedAviso" class="alert alert-info d-none" role="status"></div>
    <div class="table-responsive"><table class="table table-hover align-middle ivc-pedidos-tabla">
      <thead><tr><th>Pedido</th><th>Cliente</th><th>Fecha</th><th>Importe</th><th>Pago</th><th>Etapa</th><th>Revisión óptica</th><th></th></tr></thead>
      <tbody id="ivcPedFilas"><tr><td colspan="8" class="text-center p-4">Cargando pedidos…</td></tr></tbody>
    </table></div>
    <div class="small text-muted">Se muestran hasta 300 resultados por consulta. Usa los filtros para localizar otros pedidos.</div>
  </div></div>
</div>
<div class="modal fade" id="ivcPedModal" tabindex="-1" aria-labelledby="ivcPedTitulo" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header ivc-pedidos-modal-head"><div><div class="small opacity-75">EXPEDIENTE DEL PEDIDO</div><h5 class="modal-title" id="ivcPedTitulo">Detalle del pedido</h5></div>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><div id="ivcPedDetalle" aria-live="polite">Consultando…</div></div>
    <div class="modal-footer justify-content-between"><div class="small text-muted">Consulta el expediente y el estado comercial del pedido.</div>
      <div><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
      <button id="ivcPedAvanzar" class="btn btn-primary" type="button" hidden>Avanzar pedido</button></div>
    </div>
  </div></div>
</div>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-receta-preview-v471.js?v=471"></script>
<script src="<?php echo BASE_URL; ?>assets/js/modulos/pedidos.js?v=494"></script>
</body></html>
