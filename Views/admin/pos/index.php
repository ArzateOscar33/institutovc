<?php include_once 'Views/template/header-admin.php'; ?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-pos-caja-v4951.css?v=4951">
<main class="ivc-pos" id="ivcPos" data-url="<?php echo htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8'); ?>"
  data-csrf="<?php echo htmlspecialchars($data['csrf'],ENT_QUOTES,'UTF-8'); ?>"
  data-abrir="<?php echo $data['permisos']['abrir']?'1':'0'; ?>"
  data-cerrar="<?php echo $data['permisos']['cerrar']?'1':'0'; ?>"
  data-movimientos="<?php echo $data['permisos']['movimientos']?'1':'0'; ?>"
  data-supervisar="<?php echo $data['permisos']['supervisar']?'1':'0'; ?>">
  <div class="ivc-pos-encabezado"><div><span class="ivc-pos-eyebrow">INSTITUTO VISUAL CALIFORNIA • OPERACIÓN PRESENCIAL</span>
    <h1>Punto de Venta <span>· Control de caja</span></h1>
    <p>Administra turnos y arqueos de caja. Registra tus ventas y consulta sus comprobantes e historial.</p></div>
    <div class="ivc-pos-status" id="posEstado"><span class="ivc-pos-pulse"></span> Consultando caja…</div>
  </div>
  <div class="ivc-pos-panel mb-3" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between">
    <div><strong>Operación de mostrador</strong><p class="ivc-pos-ayuda mb-0">Registra productos o consulta comprobantes y devoluciones.</p></div>
    <div style="display:flex;flex-wrap:wrap;gap:.6rem"><a href="<?php echo BASE_URL; ?>pos/venta" class="ivc-pos-btn" style="text-decoration:none">Preparar venta</a>
      <?php if(RolesAcceso::permiso('pos.ventas.consultar')): ?><a href="<?php echo BASE_URL; ?>pos/historial" class="ivc-pos-btn ivc-pos-btn-secundario" style="text-decoration:none">Historial y tickets</a><?php endif; ?></div>
  </div>
  <div class="ivc-pos-grid">
    <section class="ivc-pos-panel ivc-pos-panel-principal">
      <div class="ivc-pos-panel-title"><div><span class="ivc-pos-eyebrow">TURNO ACTUAL</span><h2 id="posCajaTitulo">Caja pendiente</h2></div>
        <span class="ivc-pos-chip" id="posTurnoEstado">Sin iniciar</span></div>
      <div id="posAbrirSeccion" hidden>
        <form id="posAbrirForm" class="ivc-pos-form">
          <label for="posCajaSelect">Seleccionar caja</label><select id="posCajaSelect" class="form-select" name="id_caja" required></select>
          <label for="posFondo">Fondo inicial (MXN)</label><input id="posFondo" name="fondo_inicial" class="form-control" type="number" step="0.01" min="0" max="999999999" value="0.00" required>
          <button class="ivc-pos-btn" type="submit" id="posAbrirBtn">Abrir caja</button>
        </form>
        <p class="ivc-pos-ayuda">Solo puedes abrir una caja a la vez. Cada caja admite un turno abierto.</p>
      </div>
      <div id="posTurnoSeccion" hidden>
        <div class="ivc-pos-indicadores">
          <div><span>Fondo inicial</span><strong id="posFondoActual">—</strong></div>
          <div><span>Entradas de efectivo</span><strong id="posEntradas">—</strong></div>
          <div><span>Salidas de efectivo</span><strong id="posSalidas">—</strong></div>
          <div class="ivc-pos-total"><span>Efectivo esperado</span><strong id="posEsperado">—</strong></div>
        </div>
        <div class="ivc-pos-panel-title mt-4"><h3>Registrar movimiento de efectivo</h3></div>
        <form id="posMovimientoForm" class="ivc-pos-form">
          <label for="posMovimientoTipo">Tipo de movimiento</label>
          <select id="posMovimientoTipo" name="tipo" class="form-select"><option value="INGRESO">Ingreso</option><option value="RETIRO">Retiro</option></select>
          <label for="posMovimientoImporte">Importe (MXN)</label><input id="posMovimientoImporte" name="importe" type="number" class="form-control" step="0.01" min="0.01" required>
          <label for="posMovimientoMotivo">Motivo</label><input id="posMovimientoMotivo" name="motivo" maxlength="220" class="form-control" placeholder="Ej. Depósito de efectivo autorizado" required>
          <button class="ivc-pos-btn ivc-pos-btn-secundario" id="posMovimientoBtn" type="submit">Guardar movimiento</button>
        </form>
      </div>
      <div class="ivc-pos-bloqueo"><i class="bx bx-receipt"></i><div><strong>Ventas e historial disponibles</strong><p>Los reembolsos a tarjeta se realizan físicamente en la terminal y se registran aquí con referencia de aprobación.</p></div></div>
    </section>
    <aside class="ivc-pos-lateral">
      <section class="ivc-pos-panel"><div class="ivc-pos-panel-title"><h3>Arqueo y cierre</h3></div>
        <p class="ivc-pos-ayuda">El cierre compara únicamente efectivo. Los cobros con terminal bancaria se conciliarán por separado.</p>
        <form id="posCerrarForm" class="ivc-pos-form" hidden>
          <label for="posContado">Efectivo contado físicamente (MXN)</label><input id="posContado" name="efectivo_contado" class="form-control" type="number" step="0.01" min="0" required>
          <button type="submit" class="ivc-pos-btn ivc-pos-btn-cerrar" id="posCerrarBtn">Cerrar mi turno</button>
        </form>
        <p id="posCerrarAyuda" class="ivc-pos-ayuda">Abre una caja para habilitar el arqueo.</p>
      </section>
      <section class="ivc-pos-panel"><div class="ivc-pos-panel-title"><h3>Movimientos recientes</h3><span class="ivc-pos-chip" id="posCantidad">0</span></div>
        <div class="ivc-pos-movimientos" id="posMovimientos"><p>Abre una caja para consultar los movimientos.</p></div>
      </section>
    </aside>
  </div>
  <section class="ivc-pos-panel mt-3" id="posSupervision" hidden><div class="ivc-pos-panel-title"><h3>Últimos turnos · Supervisión</h3></div>
     <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Turno</th><th>Caja</th><th>Responsable</th><th>Apertura</th><th>Estado</th><th>Esperado</th><th>Contado</th><th>Diferencia</th></tr></thead><tbody id="posTurnos"></tbody></table></div>
  </section>
</main>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo BASE_URL; ?>assets/js/modulos/pos-caja-v4951.js?v=4951"></script>
</body></html>
