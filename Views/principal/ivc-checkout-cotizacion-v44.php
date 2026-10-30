<!-- IVC F4.6: cotización PHP, pedido con inventario y PayPal SOLO SANDBOX. -->
<section class="ivc44-root" id="ivcCheckoutQuote44" hidden aria-labelledby="ivc44Title" data-base="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="ivc44-progress" aria-label="Etapas del pedido">
    <span class="ivc44-current"><b>01</b> Revisar carrito</span>
    <span><b>02</b> Revisar total</span>
    <span><b>03</b> Pago seguro</span>
  </div>
  <header class="ivc44-heading">
    <span class="ivc44-eyebrow">TU COMPRA · REVISA LOS DETALLES</span>
    <h2 id="ivc44Title">Revisa los detalles de tu compra</h2>
    <p>Confirma tus armazones, micas y graduaciones. Consulta el importe total antes de continuar con tu pago.</p>
  </header>
  <div class="ivc44-grid">
    <div class="ivc44-panel">
      <div class="ivc44-panel-head"><h3>Artículos del carrito</h3><span id="ivc44Count">—</span></div>
      <p class="ivc44-hint">La configuración de cada armazón se respeta por separado. Puedes modificarla desde el carrito antes de cotizar.</p>
      <div class="ivc44-table-wrap"><table class="ivc44-table"><thead><tr><th>Artículo</th><th>Graduación</th><th>Cantidad</th></tr></thead><tbody id="ivc44Preview"><tr><td colspan="3">Preparando carrito…</td></tr></tbody></table></div>
      <a class="ivc44-back" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>principal/shop">← Regresar a la colección</a>
    </div>
    <aside class="ivc44-panel ivc44-summary" aria-label="Cotización del pedido">
      <span class="ivc44-eyebrow">RESUMEN DE COMPRA</span>
      <h3>Total de tu pedido</h3>
      <div id="ivc44BeforeQuote" class="ivc44-before">Selecciona <strong>«Revisar total»</strong> para consultar el precio de todos los productos de tu carrito.</div>
      <div id="ivc44Quote" hidden>
        <div class="ivc44-quote-id">Referencia <strong id="ivc44QuoteId">—</strong><span id="ivc44QuoteState">ABIERTA</span></div>
        <div class="ivc44-quote-items" id="ivc44QuoteItems"></div>
        <dl class="ivc44-totals">
          <div><dt>Armazones</dt><dd id="ivc44Frames">—</dd></div>
          <div><dt>Pares de micas</dt><dd id="ivc44Lenses">—</dd></div>
          <div class="ivc44-grand"><dt>Total verificado</dt><dd id="ivc44Total">—</dd></div>
        </dl>
        <p class="ivc44-validity">El total se mantiene vigente durante <strong>15 minutos</strong>. Al iniciar el pago, apartaremos temporalmente tus productos durante <strong>10 minutos</strong>, según disponibilidad.</p>
      </div>
      <p class="ivc44-status" id="ivc44Status" role="status" aria-live="polite"></p>
      <button id="ivc44Generate" type="button" class="ivc44-primary">Revisar total →</button>
      <!-- IVC_F45_SANDBOX_PANEL -->
      <div class="ivc45-sandbox" id="ivc45Sandbox" hidden role="region" aria-label="Pago seguro con PayPal">
        <span class="ivc45-eyebrow">PAGO PROTEGIDO</span>
        <h4>Elige cómo pagar</h4>
        <p>Puedes continuar con PayPal o, si está disponible, pagar con tarjeta de débito o crédito. Revisa los datos antes de confirmar.</p>
        <div id="ivc45Buttons"></div>
        <p id="ivc45Status" role="status" aria-live="polite"></p>
      </div>
      <div id="ivc46Recovery" class="ivc46-recovery" role="region" aria-label="Compras pendientes de confirmación"></div>
      <p class="ivc44-review">Las recetas externas requieren revisión por la óptica. Si solicitaste valoración, se deberá agendar antes de fabricar las micas.</p>
    </aside>
  </div>
</section>
