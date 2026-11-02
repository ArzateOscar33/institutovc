<!-- Resumen y pago en pasos separados; el importe siempre se verifica en PHP. -->
<section class="ivc44-root" id="ivcCheckoutQuote44" hidden aria-labelledby="ivc44Title" data-base="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>">
  <nav class="ivc44-progress" aria-label="Progreso del pago">
    <span class="ivc44-done"><b>✓</b> Carrito</span>
    <span id="ivc44ProgressSummary" class="ivc44-current" aria-current="step"><b>1</b> Resumen</span>
    <span id="ivc44ProgressPayment"><b>2</b> Pago</span>
  </nav>
  <section id="ivc44PanelSummary" class="ivc44-wizard-panel" aria-labelledby="ivc44Title">
    <header class="ivc44-heading ivc44-compact-heading">
      <span class="ivc44-eyebrow">PASO 1 DE 2</span>
      <h2 id="ivc44Title" tabindex="-1">Revisa tu compra</h2>
      <p>Estos son tus productos y el total que pagarás. Después podrás elegir cómo pagar.</p>
    </header>
    <div class="ivc44-grid">
      <div class="ivc44-panel">
        <div class="ivc44-panel-head"><h3>Artículos del carrito</h3><span id="ivc44Count">—</span></div>
        <div class="ivc44-table-wrap"><table class="ivc44-table"><thead><tr><th>Artículo</th><th>Graduación</th><th>Cantidad</th></tr></thead><tbody id="ivc44Preview"><tr><td colspan="3">Preparando carrito…</td></tr></tbody></table></div>
        <a class="ivc44-back" href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>principal/shop">← Regresar a la colección</a>
      </div>
      <aside class="ivc44-panel ivc44-summary" aria-label="Resumen de tu compra">
        <span class="ivc44-eyebrow">TOTAL DE COMPRA</span>
        <h3>Tu pedido</h3>
        <div id="ivc44BeforeQuote" class="ivc44-before is-loading" role="status" aria-live="polite">Calculando el total automáticamente…</div>
        <div id="ivc44Quote" hidden>
          <div class="ivc44-quote-id"><span>Referencia</span><strong id="ivc44QuoteId">—</strong><span id="ivc44QuoteState">Preparado</span></div>
          <div class="ivc44-quote-items" id="ivc44QuoteItems"></div>
          <dl class="ivc44-totals">
            <div><dt>Armazones</dt><dd id="ivc44Frames">—</dd></div>
            <div><dt>Pares de micas</dt><dd id="ivc44Lenses">—</dd></div>
            <div class="ivc44-grand"><dt>Total a pagar</dt><dd id="ivc44Total">—</dd></div>
          </dl>
          <p class="ivc44-validity">El precio se mantiene vigente durante 15 minutos. Apartaremos temporalmente los productos al iniciar el pago, según disponibilidad.</p>
        </div>
        <p class="ivc44-status" id="ivc44Status" role="status" aria-live="polite"></p>
      </aside>
    </div>
    <div class="ivc44-navigation">
      <span class="ivc44-nav-help">Tus micas y recetas se revisarán antes de preparar los lentes.</span>
      <button type="button" id="ivc44ToPayment" class="ivc44-primary" disabled>Continuar al pago →</button>
    </div>
  </section>
  <section id="ivc44PanelPayment" class="ivc44-wizard-panel" aria-labelledby="ivc44PaymentTitle" hidden>
    <header class="ivc44-heading ivc44-compact-heading">
      <span class="ivc44-eyebrow">PASO 2 DE 2</span>
      <h2 id="ivc44PaymentTitle" tabindex="-1">Elige cómo pagar</h2>
      <p>Tu importe está confirmado. Selecciona el medio de pago y sigue las instrucciones de PayPal.</p>
    </header>
    <div class="ivc44-payment-card">
      <div class="ivc44-pay-amount"><span>Total de tu compra</span><strong id="ivc44PaymentTotal">—</strong></div>
      <!-- PayPal permanece en modo de pruebas mientras el servidor use Sandbox. -->
      <div class="ivc45-sandbox" id="ivc45Sandbox" hidden role="region" aria-label="Pago seguro con PayPal">
        <span class="ivc45-eyebrow">PAGO PROTEGIDO</span>
        <h4>Paga con confianza</h4>
        <p>Puedes usar PayPal o, si está disponible, una tarjeta de débito o crédito.</p>
        <div id="ivc45Buttons"></div>
        <p id="ivc45Status" role="status" aria-live="polite"></p>
      </div>
      <p class="ivc44-review">La receta deberá ser aprobada por la óptica antes de fabricar tus lentes.</p>
    </div>
    <div class="ivc44-navigation ivc44-payment-nav">
      <button type="button" id="ivc44BackSummary" class="ivc44-secondary">← Volver al resumen</button>
      <span class="ivc44-nav-help">Regresar no genera ningún cargo.</span>
    </div>
  </section>
</section>
