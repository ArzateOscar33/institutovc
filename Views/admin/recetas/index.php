<?php include_once 'Views/template/header-admin.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-recetas-fase3.css?v=3.0.0">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-recetas-preview-v471.css?v=471">
<main class="rx-admin" id="rxAdmin" data-base="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" id="rxCsrfAdmin" value="<?php echo htmlspecialchars($data['csrf'],ENT_QUOTES,'UTF-8'); ?>">
  <div class="rx-admin-top"><div><span class="rx-kicker">INSTITUTO VISUAL CALIFORNIA · ADMINISTRACIÓN</span><h1>Revisión de recetas</h1><p>Consulta graduaciones externas y autoriza su uso antes de fabricar las micas.</p></div><button class="btn rx-btn" id="rxReload" type="button">Actualizar listado</button></div>
  <div class="rx-note" role="note">Todas las recetas del cliente se reciben como <b>PENDIENTES</b>. Aprobar una receta <b>no inicia producción ni modifica un pedido o PayPal</b>.</div>
  <div class="rx-stats"><div><span>Total</span><strong id="rxTotal">—</strong></div><div><span>Pendientes</span><strong id="rxPending">—</strong></div><div><span>Aprobadas</span><strong id="rxApproved">—</strong></div></div>
  <section class="rx-panel"><div class="rx-filter"><input class="form-control" id="rxSearch" type="search" placeholder="Buscar por cliente, correo o número…" aria-label="Buscar receta"><select class="form-select" id="rxState" aria-label="Filtrar estado"><option value="">Todos los estados</option><option value="PENDIENTE">Pendientes</option><option value="APROBADA">Aprobadas</option><option value="REQUIERE_CORRECCION">Requieren corrección</option><option value="RECHAZADA">Rechazadas</option></select></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Receta</th><th>Cliente</th><th>Modalidad</th><th>Registro</th><th>Revisión</th><th></th></tr></thead><tbody id="rxAdminRows"><tr><td colspan="6">Cargando recetas…</td></tr></tbody></table></div><p id="rxAdminCount" class="text-muted small"></p>
  </section>
  <div id="rxAdminAlert" class="mt-3" role="status" aria-live="polite"></div>
  <div class="modal fade" id="rxAdminModal" tabindex="-1" aria-labelledby="rxModalTitle" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5" id="rxModalTitle">Revisión de receta</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body" id="rxModalInfo"></div><div class="modal-body rx-review-form" id="rxReviewPane"><label for="rxReviewNotes" class="form-label">Observaciones de la óptica</label><textarea class="form-control" maxlength="1500" rows="3" id="rxReviewNotes" placeholder="Obligatorio si solicitas corrección o rechazas"></textarea><div class="rx-review-actions"><button type="button" class="btn btn-success" data-review="APROBADA">Aprobar receta</button><button type="button" class="btn btn-warning" data-review="REQUIERE_CORRECCION">Solicitar corrección</button><button type="button" class="btn btn-outline-danger" data-review="RECHAZADA">Rechazar</button></div></div></div></div></div>
  </div>
</main>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-receta-preview-v471.js?v=471"></script>
<script defer src="<?php echo BASE_URL; ?>assets/js/modulos/recetas-admin.js?v=471"></script>
</body></html>
