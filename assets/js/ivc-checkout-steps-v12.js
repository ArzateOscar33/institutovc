/* Instituto Visual California V12
 * Catálogo visual de micas (precios de lista aportados por la óptica).
 * NO modifica pedidos, NO almacena recetas y NO cobra micas por PayPal.
 */
(() => {
  'use strict';
  const root = document.getElementById('ivcCheckoutFlow');
  if (!root || root.dataset.initialized === '1') return;
  root.dataset.initialized = '1';
  const el = id => document.getElementById(id);
  const panes = [null, el('ivcFlowStep1'), el('ivcFlowStep2'), el('ivcFlowStep3')];
  const indicators = [...root.querySelectorAll('[data-flow-indicator]')];
  const payBox = el('ivcPayReal');
  const quoteBox = el('ivcQuoteLock');
  const payScope = el('ivcPayScope');
  const originalPay = typeof window.botonPaypal === 'function' ? window.botonPaypal : null;
  let step = 1;
  let choice = null; // plain | known | unknown
  let paypalRendered = false;
  let baseTotal = null;
  let currency = 'MXN';

  // El precio de lista no especifica si es por par o por pieza; se conserva
  // únicamente como referencia hasta confirmación comercial y clínica.
  const catalog = {
    mono: [
      ['CR39 Verde o Azul', 550, 'Límite indicado en la lista: ESF ±4.00, CIL hasta −2.00'],
      ['CR39 Fotocromático con AR', 1300, 'Límite indicado en la lista: ESF ±4.00, CIL hasta −2.00'],
      ['POLY', 350, ''],
      ['POLY AR', 800, ''],
      ['POLY FOTO AR', 1600, ''],
      ['HI INDEX AR', 1800, ''],
      ['HI INDEX FOTO AR', 2800, ''],
      ['HÍPER INDEX', 2800, ''],
      ['HÍPER INDEX FOTO AR', 3800, '']
    ],
    bi: [
      ['CR39 Blanco', 350, ''],
      ['CR39 AR', 800, ''],
      ['CR39 Fotocromático con AR', 2000, '']
    ],
    prog: [
      ['CR39 Blanco', 1500, ''],
      ['CR39 AR', 1800, ''],
      ['CR39 / Hi Index Fotocromático con AR', 3000, '']
    ]
  };
  let selectedLens = null;
  const family = el('ivcLensFamily');
  const lensSelect = el('ivcLensOption');
  const lensValidation = el('ivcLensValidation');
  const rxProduct = el('ivcRxProduct');
  function money(amount) {
    try { return new Intl.NumberFormat('es-MX', { style: 'currency', currency }).format(amount); }
    catch (_) { return `${currency} ${Number(amount).toFixed(2)}`; }
  }
  function lensDetails() {
    const data = catalog[family.value] || [];
    const i = Number(lensSelect.value);
    return lensSelect.value !== '' && Number.isInteger(i) ? data[i] || null : null;
  }
  function renderLensPrice() {
    selectedLens = lensDetails();
    const box = el('ivcLensPrice');
    const strong = box.querySelector('strong');
    const note = box.querySelector('small');
    strong.textContent = selectedLens ? money(selectedLens[1]) : '—';
    note.textContent = selectedLens
      ? `${selectedLens[0]}. ${selectedLens[2] ? selectedLens[2] + '. ' : ''}Confirma si el importe es por par o pieza; sujeto a compatibilidad con la receta. No se cobra todavía.`
      : 'Selecciona un material para ver su precio de lista. No se cobra todavía.';
    updateSummary();
  }
  family.addEventListener('change', () => {
    const records = catalog[family.value] || [];
    lensSelect.replaceChildren(new Option(records.length ? 'Selecciona un material' : 'Primero elige el tipo de lente', ''));
    records.forEach(([name, price], i) => lensSelect.add(new Option(`${name} — ${money(price)}`, String(i))));
    lensSelect.disabled = records.length === 0;
    renderLensPrice();
  });
  lensSelect.addEventListener('change', renderLensPrice);
  el('ivcUnknownConfirm').addEventListener('change', () => {
    if (choice === 'unknown') updateSummary();
  });

  function productCount() {
    return [...document.querySelectorAll('#tableListaProductos tbody tr')].filter(tr => tr.cells.length === 5 && !tr.cells[0]?.hasAttribute('colspan')).length;
  }
  function canPayFrameOnly() {
    return choice === 'plain' || (choice === 'unknown' && el('ivcUnknownConfirm').checked);
  }
  function updateSummary() {
    const count = productCount();
    el('ivcFlowItemCount').textContent = `${count} ${count === 1 ? 'producto' : 'productos'}`;
    el('ivcFlowBaseTotal').textContent = baseTotal === null ? 'Cargando…' : money(baseTotal);
    const cost = el('ivcFlowRxCost');
    const total = el('ivcFlowGrandTotal');
    const label = el('ivcFlowTotalLabel');
    const decision = el('ivcFlowFinalDecision');
    const quote = selectedLens ? money(selectedLens[1]) : null;
    if (choice === 'known') {
      cost.textContent = quote ? `${quote} (de lista)` : 'Por seleccionar';
      total.textContent = baseTotal && quote ? `${money(baseTotal + selectedLens[1])} *` : 'Por definir';
      label.textContent = 'Total ilustrativo';
      decision.textContent = 'El precio mostrado es una estimación: supone un solo material para un armazón y que el precio de lista corresponde a la unidad de venta requerida. La receta y la selección no se guardan ni llegan a la óptica. *No es un cobro ni una cotización definitiva.';
      decision.classList.add('is-rx');
    } else if (choice === 'unknown') {
      cost.textContent = quote ? `${quote} (referencia, no incluido)` : 'Por determinar después';
      total.textContent = baseTotal === null ? 'Cargando…' : money(baseTotal);
      label.textContent = 'A pagar hoy: solo armazones';
      decision.textContent = 'Todavía no conoces tu graduación. Puedes comprar el armazón ahora; la valoración, las micas y su pago serán un trámite posterior independiente. Este sistema aún no vincula esa preferencia al pedido.';
      decision.classList.remove('is-rx');
    } else if (choice === 'plain') {
      cost.textContent = 'No incluidas';
      total.textContent = baseTotal === null ? 'Cargando…' : money(baseTotal);
      label.textContent = 'A pagar hoy: solo armazones';
      decision.textContent = 'Comprarás únicamente los armazones que aparecen en tu pedido, sin lentes graduados.';
      decision.classList.remove('is-rx');
    } else {
      cost.textContent = 'Sin seleccionar';
      total.textContent = '—';
      label.textContent = 'Total a pagar';
      decision.textContent = 'Debes seleccionar cómo deseas continuar.';
    }
    payBox.hidden = step !== 3 || !canPayFrameOnly();
    quoteBox.hidden = step !== 3 || choice !== 'known';
    payScope.hidden = step !== 3 || !canPayFrameOnly();
    payScope.textContent = choice === 'unknown'
      ? 'PAGO DE ARMAZONES ÚNICAMENTE. No incluye examen visual, micas ni tratamiento, y el pedido no conservará tu solicitud de graduación.'
      : 'PAGO DE ARMAZONES ÚNICAMENTE. No incluye micas graduadas.';
    const payIntro = el('ivcPayIntro');
    if (payIntro) payIntro.hidden = choice === 'known';
    const warning = el('ivcPayWarning');
    if (warning) {
      warning.hidden = false;
      warning.innerHTML = '<strong>Importante:</strong> la receta, la graduación y el material escogido son solo una demostración visual. <strong>No se guardan ni se cobran.</strong> PayPal solo cobrará los armazones con la integración actual.';
    }
  }

  // clientes.js llama a botonPaypal en cuanto carga el pedido; retrasamos su
  // render hasta el paso final y solamente para pedidos de armazones.
  window.botonPaypal = function guardedPaypalV12(total, moneda) {
    const price = Number(total);
    if (!Number.isFinite(price) || price <= 0) return;
    baseTotal = price;
    currency = /^[A-Z]{3}$/.test(String(moneda)) ? String(moneda) : 'MXN';
    el('totalProducto').textContent = `Subtotal armazones: ${money(price)}`;
    el('ivcOrderStatus').textContent = 'Productos cargados. Puedes continuar.';
    el('ivcFlowToRx').disabled = false;
    updateSummary();
    maybeRenderPaypal();
  };
  function maybeRenderPaypal() {
    if (paypalRendered || step !== 3 || !canPayFrameOnly() || !(baseTotal > 0)) return;
    if (!originalPay || !window.paypal?.Buttons) {
      el('ivcPaypalStatus').textContent = 'No se pudo cargar PayPal. Verifica tu conexión.';
      return;
    }
    payBox.hidden = false;
    try {
      originalPay(baseTotal.toFixed(2), currency);
      paypalRendered = true;
      el('ivcPaypalStatus').textContent = '';
    } catch (error) {
      console.error('Error al iniciar PayPal:', error);
      el('ivcPaypalStatus').textContent = 'No se pudo cargar PayPal.';
    }
  }
  function changeStep(next) {
    if (next === 2 && !(baseTotal > 0)) return;
    if (next === 3) {
      if (!choice) return;
      if (choice === 'known') {
        if (!selectedLens) { showValidation('Selecciona primero el tipo de lente y su material.'); return; }
        if (rxProduct && !rxProduct.value) { showValidation('Selecciona el armazón al que corresponde esta graduación.'); return; }
      }
      if (choice === 'unknown' && !el('ivcUnknownConfirm').checked) {
        showValidation('Confirma que deseas comprar solo el armazón y pagar las micas después.');
        return;
      }
    }
    showValidation('');
    step = next;
    panes.forEach((pane, index) => {
      if (!pane || !index) return;
      pane.hidden = index !== step;
      pane.classList.toggle('is-current', index === step);
    });
    indicators.forEach(indicator => {
      const index = Number(indicator.dataset.flowIndicator);
      indicator.classList.toggle('is-current', index === step);
      indicator.classList.toggle('is-done', index < step);
      if (index === step) indicator.setAttribute('aria-current', 'step');
      else indicator.removeAttribute('aria-current');
    });
    root.dataset.step = String(step);
    updateSummary();
    if (step === 3) maybeRenderPaypal();
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    root.scrollIntoView({ block: 'start', behavior: reduced ? 'auto' : 'smooth' });
  }
  function showValidation(message) {
    lensValidation.textContent = message;
    lensValidation.hidden = !message;
    if (message) lensValidation.scrollIntoView({ block:'center', behavior:'smooth' });
  }
  function choose(next) {
    choice = next;
    const options = [['known','ivcChooseRx'],['plain','ivcChoosePlain'],['unknown','ivcChooseUnknown']];
    for (const [key,id] of options) {
      el(id).classList.toggle('is-selected', next === key);
      el(id).setAttribute('aria-pressed', String(next === key));
    }
    el('ivcLensCatalog').hidden = next === 'plain';
    el('ivcFlowRxReveal').hidden = next !== 'known';
    el('ivcFlowUnknownNote').hidden = next !== 'unknown';
    el('ivcFlowPlainNote').hidden = next !== 'plain';
    el('ivcFlowToPay').disabled = false;
    el('ivcFlowSkipRx').hidden = next === 'plain';
    showValidation('');
    updateSummary();
  }
  el('ivcFlowToRx').addEventListener('click', () => changeStep(2));
  el('ivcFlowBack1').addEventListener('click', () => changeStep(1));
  el('ivcFlowBack2').addEventListener('click', () => changeStep(2));
  el('ivcFlowToPay').addEventListener('click', () => changeStep(3));
  el('ivcFlowSkipRx').addEventListener('click', () => { choose('plain'); changeStep(3); });
  el('ivcChooseRx').addEventListener('click', () => choose('known'));
  el('ivcChoosePlain').addEventListener('click', () => choose('plain'));
  el('ivcChooseUnknown').addEventListener('click', () => choose('unknown'));
  payBox.hidden = true;
  quoteBox.hidden = true;
  updateSummary();
})();
