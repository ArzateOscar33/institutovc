<?php
/** IVC POS 4.9.5.5: historial limitado al vendedor, devoluciones con autorización adicional. */
$h=static fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
include_once 'Views/template/header-admin.php';
?>
<link rel="stylesheet" href="<?php echo $h(BASE_URL); ?>assets/css/ivc-pos-postventa-v4955.css?v=4955">
<main class="ivc-pv-postventa" id="pvPostventa"
 data-base="<?php echo $h(BASE_URL); ?>"
 data-csrf="<?php echo $h($data['csrf']); ?>"
 data-devolver="<?php echo !empty($data['puede_devolver'])?'1':'0'; ?>"
 data-todas="<?php echo !empty($data['puede_ver_todas'])?'1':'0'; ?>">
 <header class="pvp-head">
  <div><span class="pvp-eyebrow">INSTITUTO VISUAL CALIFORNIA · POS</span><h1>Historial de ventas y devoluciones</h1>
   <p>Consulta las ventas registradas, imprime tickets y realiza devoluciones autorizadas sin alterar el importe histórico de los pedidos.</p></div>
  <nav class="pvp-actions"><a href="<?php echo $h(BASE_URL); ?>pos/venta">Nueva venta</a><a href="<?php echo $h(BASE_URL); ?>pos">Control de caja</a></nav>
 </header>
 <section class="pvp-card" aria-label="Filtros de venta">
  <form id="pvpFiltros" class="pvp-filtros">
   <label>Desde<input type="date" id="pvpDesde" name="desde" required></label>
   <label>Hasta<input type="date" id="pvpHasta" name="hasta" required></label>
   <label class="pvp-busqueda">Buscar por venta, pedido o cliente<input id="pvpQ" type="search" maxlength="70" placeholder="Número de venta o cliente"></label>
   <button type="submit" class="pvp-btn">Consultar</button>
  </form>
  <p class="pvp-info"><?php echo empty($data['puede_ver_todas'])?'Solo puedes consultar tus propias ventas.':'Puedes consultar las ventas de todos los vendedores.'; ?>
  <?php if(empty($data['puede_devolver'])): ?> El permiso de devoluciones está reservado al personal autorizado.<?php endif; ?></p>
 </section>
 <section class="pvp-card"><div class="pvp-section-title"><h2>Ventas registradas</h2><span id="pvpEstado" role="status" aria-live="polite">Consultando…</span></div>
  <div class="pvp-scroll"><table class="pvp-table"><thead><tr><th>Venta / Pedido</th><th>Fecha</th><th>Cliente</th><th>Vendedor / Caja</th><th>Total</th><th>Devuelto</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="pvpVentas"></tbody></table></div>
  <div class="pvp-paginacion"><button id="pvpAnterior" type="button" disabled>Anterior</button><span id="pvpPagina">Página 1</span><button id="pvpSiguiente" type="button" disabled>Siguiente</button></div>
 </section>
 <section id="pvpDetalleSeccion" class="pvp-card" hidden aria-label="Detalle de venta">
  <div class="pvp-section-title"><h2 id="pvpDetalleTitulo">Detalle de venta</h2><button id="pvpCerrarDetalle" type="button" class="pvp-btn pvp-secondary">Cerrar detalle</button></div>
  <div id="pvpDetalle"></div>
  <?php if(!empty($data['puede_devolver'])): ?>
  <div id="pvpDevolucion" class="pvp-devolucion" hidden>
   <h3>Registrar devolución</h3>
   <p class="pvp-aviso">Verifica físicamente la mercancía. Reingresar al inventario es opcional y requiere comprobar su estado. Las micas nunca regresan como stock de armazones. El reembolso por tarjeta debe efectuarse previamente en la terminal física.</p>
   <form id="pvpDevolverForm">
    <div class="pvp-scroll"><table class="pvp-table"><thead><tr><th>Producto</th><th>Disponibles para devolución</th><th>Cantidad</th><th>Reingresar armazón al stock</th></tr></thead><tbody id="pvpDevolverLineas"></tbody></table></div>
    <div class="pvp-total">Importe exacto de la devolución: <strong id="pvpTotalDev">$0.00</strong></div>
    <label>Motivo documentado (mínimo 8 caracteres)<textarea id="pvpMotivo" maxlength="255" minlength="8" rows="2" required placeholder="Ej. El cliente devuelve el armazón sin uso y en condiciones de venta."></textarea></label>
    <p class="pvp-info" id="pvpMetodoDisponible"></p>
    <div class="pvp-split"><label>Reembolso en efectivo (MXN)<input id="pvpEfectivo" type="number" min="0" step="0.01" value="0.00" required></label>
      <label>Reembolso a tarjeta (MXN)<input id="pvpTarjeta" type="number" min="0" step="0.01" value="0.00" required></label></div>
    <div id="pvpTarjetaCampos" hidden>
     <p class="pvp-alerta">Primero realiza el reembolso con la terminal de la óptica. Guarda el comprobante y captura su referencia aprobada. Si la terminal confirma pero el sistema falla, concilia antes de volver a realizar el reembolso.</p>
     <div class="pvp-split"><label>Proveedor original<input id="pvpProveedor" type="text" maxlength="80" readonly></label>
     <label>Referencia del reembolso<input id="pvpReferencia" type="text" maxlength="100" placeholder="Folio de reembolso aprobado"></label></div>
     <label class="pvp-check"><input id="pvpTarjetaConfirmada" type="checkbox"> Confirmo que el reembolso ya fue aprobado por la terminal física.</label>
    </div>
    <div class="pvp-formacciones"><button id="pvpProponer" type="button" class="pvp-btn pvp-secondary">Proponer distribución por método original</button>
     <button id="pvpConfirmar" type="submit" class="pvp-btn pvp-danger" disabled>Confirmar devolución</button></div>
   </form>
  </div>
  <?php endif; ?>
 </section>
</main>
<?php include_once 'Views/template/footer-admin.php'; ?>
<script src="<?php echo $h(BASE_URL); ?>assets/js/modulos/pos-postventa-v4955.js?v=4955"></script>
