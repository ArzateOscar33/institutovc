/* IVC V11 · flujo de compra visual. NO registra recetas ni modifica precios de PayPal. */
(() => {
  'use strict';
  const root = document.getElementById('ivcCheckoutFlow');
  if (!root || root.dataset.initialized === '1') return;
  root.dataset.initialized = '1';
  const $ = id => document.getElementById(id);
  const panes = [null, $('ivcFlowStep1'), $('ivcFlowStep2'), $('ivcFlowStep3')];
  const indicators = [...root.querySelectorAll('[data-flow-indicator]')];
  const payBox = $('ivcPayReal');
  const quoteBox = $('ivcQuoteLock');
  const paymentInfo = { total: null, moneda: null };
  let choice = null;  // null | 'rx' | 'plain'
  let step = 1;
  let paypalRendered = false;

  // El JS heredado ejecuta botonPaypal() al recibir el carrito. NO renderizar
  // el botón allí: PayPal sólo se presenta en el PASO 3, si se omite graduación.
  const renderPaypalOriginal = typeof window.botonPaypal === 'function' ? window.botonPaypal : null;
  window.botonPaypal = function ivcV11PaypalGuard(total, moneda) {
    const parsed = Number(total);
    if (!Number.isFinite(parsed) || parsed <= 0) return;
    paymentInfo.total = parsed;
    paymentInfo.moneda = String(moneda || 'MXN');
    $('totalProducto').textContent = `Armazones · ${formatMoney(parsed)}`;
    $('ivcOrderStatus').textContent = 'Productos verificados. Puedes continuar.';
    $('ivcFlowToRx').disabled = false;
    updateSummary();
    tryRenderPaypal();
  };

  function formatMoney(value) {
    const currency = /^[A-Z]{3}$/.test(paymentInfo.moneda || '') ? paymentInfo.moneda : 'MXN';
    try { return new Intl.NumberFormat('es-MX', { style: 'currency', currency }).format(value); }
    catch { return `${currency} ${Number(value).toFixed(2)}`; }
  }
  function updateSummary() {
    const amount = paymentInfo.total;
    const count = [...document.querySelectorAll('#tableListaProductos tbody tr')]
      .filter(tr => tr.cells.length === 5 && !tr.cells[0]?.hasAttribute('colspan')).length;
    $('ivcFlowItemCount').textContent = `${count} ${count === 1 ? 'producto' : 'productos'}`;
    $('ivcFlowBaseTotal').textContent = amount == null ? 'Cargando…' : formatMoney(amount);
    const rxCost = $('ivcFlowRxCost');
    const total = $('ivcFlowGrandTotal');
    const label = $('ivcFlowTotalLabel');
    const decision = $('ivcFlowFinalDecision');
    if (choice === 'rx') {
      rxCost.textContent = 'Por cotizar';
      total.textContent = 'Pendiente de cotización';
      label.textContent = 'Total final';
      decision.textContent = 'Elegiste lentes graduados. Tu graduación y tu receta todavía no se envían al sistema. El importe final debe incluir micas y tratamientos antes de habilitar el pago.';
      decision.classList.add('is-rx');
    } else if (choice === 'plain') {
      rxCost.textContent = 'No solicitada';
      total.textContent = amount == null ? 'Cargando…' : formatMoney(amount);
      label.textContent = 'Total a pagar';
      decision.textContent = 'Continuarás únicamente con los armazones del pedido, sin graduación ni micas graduadas añadidas.';
      decision.classList.remove('is-rx');
    } else {
      rxCost.textContent = 'Sin elegir';
      total.textContent = '—';
      label.textContent = 'Total a pagar';
      decision.textContent = 'Elige una opción de graduación antes de continuar.';
    }
    payBox.hidden = choice !== 'plain' || step !== 3;
    quoteBox.hidden = choice !== 'rx' || step !== 3;
    const payIntro = $('ivcPayIntro');
    if (payIntro) payIntro.hidden = choice === 'rx';
    const warning = $('ivcPayWarning');
    if (warning) warning.hidden = choice === 'plain';
  }
  function tryRenderPaypal() {
    if (paypalRendered || step !== 3 || choice !== 'plain' || !paymentInfo.total) return;
    if (!renderPaypalOriginal || !window.paypal?.Buttons) {
      console.warn('El SDK de PayPal o la función de pago aún no están disponibles.');
      const text = $('ivcPaypalStatus');
      if (text) text.textContent = 'No se pudo preparar PayPal. Actualiza la página y vuelve a intentarlo.';
      return;
    }
    try {
      // Ya se muestra el contenedor: PayPal puede medir su ancho correctamente.
      payBox.hidden = false;
      renderPaypalOriginal(paymentInfo.total.toFixed(2), paymentInfo.moneda);
      paypalRendered = true;
      const text = $('ivcPaypalStatus');
      if (text) text.textContent = '';
    } catch (error) {
      console.error('No se pudo cargar PayPal:', error);
      const text = $('ivcPaypalStatus');
      if (text) text.textContent = 'No se pudo cargar PayPal. Comprueba tu conexión.';
    }
  }
  function showStep(next) {
    if (next === 2 && !(paymentInfo.total > 0)) return;
    if (next === 3 && !(paymentInfo.total > 0 && choice)) return;
    step = next;
    panes.forEach((pane, index) => {
      if (!pane || index === 0) return;
      pane.hidden = index !== step;
      pane.classList.toggle('is-current', index === step);
    });
    indicators.forEach(indicator => {
      const idx = Number(indicator.dataset.flowIndicator);
      indicator.classList.toggle('is-current', idx === step);
      indicator.classList.toggle('is-done', idx < step);
      if (idx === step) indicator.setAttribute('aria-current', 'step');
      else indicator.removeAttribute('aria-current');
    });
    root.dataset.step = String(step);
    updateSummary();
    if (step === 3) tryRenderPaypal();
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    root.scrollIntoView({ block: 'start', behavior: reduce ? 'instant' : 'smooth' });
  }
  function selectChoice(next) {
    choice = next;
    const rx = $('ivcChooseRx');
    const plain = $('ivcChoosePlain');
    rx.classList.toggle('is-selected', next === 'rx');
    plain.classList.toggle('is-selected', next === 'plain');
    rx.setAttribute('aria-pressed', String(next === 'rx'));
    plain.setAttribute('aria-pressed', String(next === 'plain'));
    $('ivcFlowRxReveal').hidden = next !== 'rx';
    $('ivcFlowPlainNote').hidden = next !== 'plain';
    $('ivcFlowToPay').disabled = false;
    updateSummary();
  }
  $('ivcFlowToRx').addEventListener('click', () => showStep(2));
  $('ivcFlowBack1').addEventListener('click', () => showStep(1));
  $('ivcFlowToPay').addEventListener('click', () => showStep(3));
  $('ivcFlowSkipRx').addEventListener('click', () => { selectChoice('plain'); showStep(3); });
  $('ivcFlowBack2').addEventListener('click', () => showStep(2));
  $('ivcChooseRx').addEventListener('click', () => selectChoice('rx'));
  $('ivcChoosePlain').addEventListener('click', () => selectChoice('plain'));
  // Estado inicial: ni el contenedor PayPal ni el importe final son visibles.
  payBox.hidden = true;
  quoteBox.hidden = true;
  updateSummary();
})();
