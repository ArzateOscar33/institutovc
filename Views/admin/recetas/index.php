<?php include_once 'Views/template/header-admin.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-recetas-fase3.css?v=3.0.0">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-recetas-preview-v471.css?v=471">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-recetas-carga-v49541.css?v=49541">
<main class="rx-admin" id="rxAdmin" data-base="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" id="rxCsrfAdmin" value="<?php echo htmlspecialchars($data['csrf'],ENT_QUOTES,'UTF-8'); ?>">
  <div class="rx-admin-top"><div><span class="rx-kicker">INSTITUTO VISUAL CALIFORNIA · ADMINISTRACIÓN</span><h1>Revisión de recetas</h1><p>Consulta las graduaciones registradas, revisa sus documentos y autoriza su uso antes de preparar los lentes.</p></div><div class="rx-acciones-top">
  <?php if (!empty($data['puedeCargar'])): ?>
  <button class="btn btn-primary" id="rxAbrirCarga" type="button"><i class="bx bx-upload"></i> Subir receta de cliente</button>
  <?php endif; ?>
  <button class="btn rx-btn" id="rxReload" type="button">Actualizar listado</button>
  </div></div>
  <div class="rx-note" role="note">Las recetas pendientes deben revisarse antes de utilizarse. <b>Aprobar la receta no inicia la preparación del pedido</b>; verifica también la validación de micas en Pedidos.</div>
  <div class="rx-stats"><div><span>Total</span><strong id="rxTotal">—</strong></div><div><span>Pendientes</span><strong id="rxPending">—</strong></div><div><span>Aprobadas</span><strong id="rxApproved">—</strong></div></div>
  <section class="rx-panel"><div class="rx-filter"><input class="form-control" id="rxSearch" type="search" placeholder="Buscar por cliente, correo o número…" aria-label="Buscar receta"><select class="form-select" id="rxState" aria-label="Filtrar estado"><option value="">Todos los estados</option><option value="PENDIENTE">Pendientes</option><option value="APROBADA">Aprobadas</option><option value="REQUIERE_CORRECCION">Requieren corrección</option><option value="RECHAZADA">Rechazadas</option></select></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Receta</th><th>Cliente</th><th>Modalidad</th><th>Registro</th><th>Revisión</th><th></th></tr></thead><tbody id="rxAdminRows"><tr><td colspan="6">Cargando recetas…</td></tr></tbody></table></div><p id="rxAdminCount" class="text-muted small"></p>
  </section>
  <?php if (!empty($data['puedeCargar'])): ?>
  <div class="modal fade" id="rxCargaModal" tabindex="-1" aria-labelledby="rxCargaTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header"><h2 class="modal-title h5" id="rxCargaTitulo">Subir receta recibida del cliente</h2>
      <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <p class="rx-carga-aviso">Selecciona primero al cliente correcto. Puedes vincular el archivo a unas micas pendientes de una venta ya pagada, sin crear otra compra.</p>
        <form id="rxCargaForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($data['csrf'], ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="id_cliente" id="rxCargaClienteId" value="">
          <div class="mb-3"><label class="form-label" for="rxCargaBuscar">Buscar cliente por nombre, teléfono, correo o ID *</label>
          <div class="input-group"><input class="form-control" id="rxCargaBuscar" type="search" maxlength="90" minlength="2" placeholder="Escribe el nombre o teléfono del cliente">
          <button class="btn btn-outline-primary" type="button" id="rxCargaBuscarBtn">Buscar</button></div>
          <div id="rxCargaResultados" class="rx-carga-resultados" aria-live="polite"></div>
          <div class="rx-carga-seleccion" id="rxCargaSeleccion" hidden></div></div>
          <div class="mb-3"><label class="form-label" for="rxCargaDetalle">Asociar a un artículo pendiente (opcional)</label>
          <select class="form-select" name="id_detalle" id="rxCargaDetalle" disabled><option value="">Solo registrar en historial del cliente</option></select>
          <small class="text-muted">Solo aparecen micas de pedidos pagados, sin receta y pendientes de preparación.</small></div>
          <div class="row g-3 mb-3"><div class="col-sm-6"><label class="form-label" for="rxCargaFecha">Fecha de la receta (opcional)</label>
          <input class="form-control" name="fecha_receta" id="rxCargaFecha" type="date" max="<?php echo date('Y-m-d'); ?>"></div>
          <div class="col-sm-6"><label class="form-label" for="rxCargaArchivo">Receta JPG, PNG o PDF *</label>
          <input class="form-control" name="receta_archivo" id="rxCargaArchivo" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
          <small class="text-muted">Máximo 10 MB. Se almacena fuera del directorio público.</small></div></div>
          <div class="mb-3"><label class="form-label" for="rxCargaObservaciones">Observaciones (opcional)</label>
          <textarea class="form-control" name="observaciones" id="rxCargaObservaciones" maxlength="1000" rows="2" placeholder="Ejemplo: recibida por WhatsApp después de la compra"></textarea></div>
          <div class="rx-carga-aviso">La nueva receta quedará <b>PENDIENTE DE REVISIÓN</b>. No cambia el pago, stock ni autoriza la preparación.</div>
          <div class="mt-3" id="rxCargaMensaje" role="status" aria-live="polite"></div>
        </form></div>
      <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit" form="rxCargaForm" id="rxCargaGuardar">Guardar receta</button></div>
    </div></div>
  </div>
  <?php endif; ?>
  <div id="rxAdminAlert" class="mt-3" role="status" aria-live="polite"></div>
  <div class="modal fade" id="rxAdminModal" tabindex="-1" aria-labelledby="rxModalTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5" id="rxModalTitle">Revisión de receta</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body" id="rxModalInfo"></div><div class="modal-body rx-review-form" id="rxReviewPane" data-puede-revisar="<?php echo empty($data['puedeRevisar']) ? '0' : '1'; ?>" <?php echo empty($data['puedeRevisar']) ? 'hidden' : ''; ?>><label for="rxReviewNotes" class="form-label">Observaciones de la óptica</label><textarea class="form-control" maxlength="1500" rows="3" id="rxReviewNotes" placeholder="Obligatorio si solicitas corrección o rechazas"></textarea><div class="rx-review-actions"><button type="button" class="btn btn-success" data-review="APROBADA">Aprobar receta</button><button type="button" class="btn btn-warning" data-review="REQUIERE_CORRECCION">Solicitar corrección</button><button type="button" class="btn btn-outline-danger" data-review="RECHAZADA">Rechazar</button></div></div></div></div></div>
  </div>
</main>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-receta-preview-v471.js?v=471"></script>
<script defer src="<?php echo BASE_URL; ?>assets/js/modulos/recetas-admin.js?v=49541"></script>
<?php if (!empty($data['puedeCargar'])): ?>
<script defer src="<?php echo BASE_URL; ?>assets/js/modulos/recetas-carga-v49541.js?v=49541"></script>
<?php endif; ?>
</body></html>
