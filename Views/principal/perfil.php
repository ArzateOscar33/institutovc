<?php
// Rediseño visual del área de pago. Preserva los IDs utilizados por assets/js/clientes.js.
include_once 'Views/template/header.php';
$ivcVerified = (int)($data['verificar']['verify'] ?? 0) === 1;
$ivcName = trim((string)($_SESSION['nombreCliente'] ?? '') . ' ' . (string)($_SESSION['apellidoPCliente'] ?? ''));
$ivcMail = (string)($_SESSION['correoCliente'] ?? '');
$ivcE = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-checkout-v10.css?v=10.0.0">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-checkout-steps-v11.css?v=11.1.0">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-checkout-v12.css?v=12.0.0">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-checkout-cotizacion-v44.css?v=4.9.1ux3">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-paypal-sandbox-v45.css?v=4.9.1ux3">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-confirmacion-pedido.css?v=1.0">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-recetas-fase3.css?v=3.0.0">
<main class="ivc-checkout-v10" id="ivcCheckoutV10">
  <section class="ivc-checkout-hero">
    <div class="container ivc-checkout-hero-inner">
      <div>
        <p class="ivc-checkout-eyebrow"><span class="ivc-checkout-eyebrow-dot"></span> TU CUENTA · INSTITUTO VISUAL CALIFORNIA</p>
        <h1>Tu compra, <em>con claridad.</em></h1>
        <p>Revisa tus armazones, prepara tu información visual y continúa con tu pedido.</p>
      </div>
      <div class="ivc-checkout-hero-art" aria-hidden="true">
        <svg width="150" height="150" viewBox="0 0 150 150" fill="none"><circle cx="75" cy="75" r="63" stroke="#7DBDDB" stroke-width=".8"/><circle cx="75" cy="75" r="47" stroke="#A6D7ED" stroke-width=".8"/><path d="M30 69h90M54 53v-5m42 5v-5" stroke="#287CB3" stroke-width="2"/><path d="M31 65 36 87c3 11 18 14 26 6l4-6c4-6 14-6 18 0l4 6c8 8 23 5 26-6l5-22M33 70l27-5 5 2h20l5-2 27 5" stroke="#287CB3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
    </div>
  </section>

  <div class="container ivc-checkout-container">
    <?php if ($ivcVerified): ?>
      <nav class="ivc-checkout-tabs" aria-label="Secciones de mi cuenta">
        <div class="nav nav-tabs" id="myTab" role="tablist">
          <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true"><span>01</span> Pago</button>
          <button class="nav-link" id="pendientes-tab" data-bs-toggle="tab" data-bs-target="#pendientes-tab-pane" type="button" role="tab" aria-controls="pendientes-tab-pane" aria-selected="false">Mis pedidos</button>
          <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos-tab-pane" type="button" role="tab" aria-controls="productos-tab-pane" aria-selected="false">Mis productos</button>
        </div>
      </nav>
      <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
<?php include_once 'Views/principal/ivc-checkout-cotizacion-v44.php'; ?>
<div class="ivc-flow-shell" id="ivcCheckoutFlow" data-step="1" hidden aria-hidden="true" style="display:none">
  <ol class="ivc-flow-progress" aria-label="Progreso de compra">
    <li class="ivc-flow-progress-item is-current" data-flow-indicator="1" aria-current="step"><span class="ivc-flow-progress-number">1</span><span><strong>Tu pedido</strong><small>Verifica tus armazones</small></span></li>
    <li class="ivc-flow-progress-line" aria-hidden="true"></li>
    <li class="ivc-flow-progress-item" data-flow-indicator="2"><span class="ivc-flow-progress-number">2</span><span><strong>Graduación</strong><small>Elige cómo continuar</small></span></li>
    <li class="ivc-flow-progress-line" aria-hidden="true"></li>
    <li class="ivc-flow-progress-item" data-flow-indicator="3"><span class="ivc-flow-progress-number">3</span><span><strong>Pago</strong><small>Resumen y confirmación</small></span></li>
  </ol>
  <section class="ivc-flow-step is-current" id="ivcFlowStep1" data-flow-step="1" aria-labelledby="ivcOrderTitle">
    <div class="ivc-checkout-columns ivc-checkout-columns-single">
      <div class="ivc-checkout-left"><section class="ivc-checkout-panel ivc-checkout-order" aria-labelledby="ivcOrderTitle">
                <div class="ivc-checkout-panel-header">
                  <div><div class="ivc-checkout-kicker">PASO 01 · REVISA TU PEDIDO</div><h2 id="ivcOrderTitle">Tus armazones</h2><p>Comprueba los productos y las cantidades antes de continuar.</p></div>
                  <span class="ivc-checkout-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 8h14l1 12H4L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span>
                </div>
                <div class="ivc-checkout-table-wrap">
                  <table class="table align-middle ivc-checkout-items" id="tableListaProductos">
                    <thead><tr><th scope="col">Imagen</th><th scope="col">Producto</th><th scope="col">Precio</th><th scope="col">Cantidad</th><th scope="col">Subtotal</th></tr></thead>
                    <tbody><tr><td colspan="5" class="ivc-checkout-loading">Cargando tus productos…</td></tr></tbody>
                  </table>
                </div>
                <div class="ivc-checkout-order-footer"><span>Subtotal de productos</span><strong id="totalProducto">Calculando…</strong></div>
                <a class="ivc-checkout-continue" href="<?php echo BASE_URL; ?>principal/shop">← Seguir viendo armazones</a>
              </section></div>
      <aside class="ivc-flow-aside-card"><div class="ivc-flow-aside-mark">01 / 03</div><h3>Una compra a tu medida.</h3><p>Revisa cada artículo antes de continuar. En el siguiente paso podrás indicar si necesitas graduación.</p><div class="ivc-flow-aside-symbol" aria-hidden="true">◎</div></aside>
    </div>
    <div class="ivc-flow-navigation"><span class="ivc-flow-nav-note" id="ivcOrderStatus" role="status">Estamos preparando tu pedido…</span><button class="ivc-flow-primary" id="ivcFlowToRx" type="button" disabled>Continuar a graduación <span aria-hidden="true">→</span></button></div>
  </section>
  <section class="ivc-flow-step" id="ivcFlowStep2" data-flow-step="2" aria-label="Opciones de graduación" hidden>
    <div class="ivc-flow-heading"><div class="ivc-checkout-kicker">PASO 02 · GRADUACIÓN</div><h2>¿Necesitas tus lentes graduados?</h2><p>Primero elige cómo quieres comprar. Si ya tienes receta, puedes consultar los materiales y sus precios de lista; si aún no sabes tu graduación, puedes comprar el armazón y realizar la valoración después.</p></div>
    <div class="ivc-flow-choice-grid" role="group" aria-label="Selecciona si necesitas graduación">
      <button class="ivc-flow-choice" id="ivcChooseRx" type="button" aria-pressed="false"><span class="ivc-flow-choice-icon" aria-hidden="true">◉</span><strong>Ya tengo mi graduación</strong><small>Consulta materiales y precios, e ingresa la graduación o selecciona tu receta (JPG, PNG o PDF).</small><span class="ivc-flow-choice-check" aria-hidden="true">✓</span></button>
      <button class="ivc-flow-choice" id="ivcChoosePlain" type="button" aria-pressed="false"><span class="ivc-flow-choice-icon" aria-hidden="true">◌</span><strong>Solo quiero el armazón</strong><small>Paga el armazón sin agregar micas graduadas.</small><span class="ivc-flow-choice-check" aria-hidden="true">✓</span></button>
      <button class="ivc-flow-choice" id="ivcChooseUnknown" type="button" aria-pressed="false"><span class="ivc-flow-choice-icon" aria-hidden="true">?</span><strong>No conozco mi graduación</strong><small>Compra el armazón ahora y agenda una revisión para definir las micas después.</small><span class="ivc-flow-choice-check" aria-hidden="true">✓</span></button>
    </div>
    <section class="ivc-lens-catalog" id="ivcLensCatalog" hidden aria-labelledby="ivcLensTitle">
      <div class="ivc-lens-title"><div><span class="ivc-checkout-kicker">CATÁLOGO DE LA ÓPTICA · VISTA PREVIA</span><h3 id="ivcLensTitle">Elige el tipo de mica</h3><p>Los importes corresponden a la lista enviada por la óptica. La selección es ilustrativa y la compatibilidad con la receta debe verificarse.</p></div><span class="ivc-lens-tag">Precios MXN</span></div>
      <div class="ivc-lens-fields">
        <label>Tipo de lente<select id="ivcLensFamily" class="form-select"><option value="">Selecciona un tipo</option><option value="mono">Monofocal</option><option value="bi">Bifocal</option><option value="prog">Progresivo</option></select></label>
        <label>Material y tratamiento<select id="ivcLensOption" class="form-select" disabled><option value="">Primero elige el tipo de lente</option></select></label>
      </div>
      <div class="ivc-lens-price" id="ivcLensPrice" aria-live="polite"><span>Precio de lista seleccionado</span><strong>—</strong><small>Los precios indicados corresponden a un par de micas. Verifica el total en el resumen de compra.</small></div>
      <p class="ivc-lens-validation" id="ivcLensValidation" hidden role="alert"></p>
    </section>
    <div class="ivc-flow-unknown-note" id="ivcFlowUnknownNote" hidden><strong>¿Aún no sabes qué graduación necesitas?</strong><p>No necesitas adivinarla. Puedes comprar tus lentes con una valoración pendiente y agendar una cita para confirmar tu graduación.</p><a href="<?php echo BASE_URL; ?>principal/agendar" target="_blank" rel="noopener">Agendar valoración visual ↗</a><label class="ivc-unknown-check"><input type="checkbox" id="ivcUnknownConfirm"><span>Entiendo que la graduación debe confirmarse antes de preparar mis lentes.</span></label></div>
    <div class="ivc-flow-rx-reveal" id="ivcFlowRxReveal" hidden><section class="ivc-checkout-panel ivc-checkout-rx" aria-labelledby="ivcRxTitle">
                <div class="ivc-checkout-panel-header">
                  <div><div class="ivc-checkout-kicker">PASO 02 · PREPARA TUS LENTES</div><h2 id="ivcRxTitle">Tu graduación</h2><p>Diseñamos este espacio para que puedas proporcionar tu receta antes del pago.</p></div>
                  <span class="ivc-checkout-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18v13H3zM8 3h8M6 11h12M8 15h8"/></svg></span>
                </div>
                <div class="ivc-checkout-demo-note" role="note"><svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v1"/></svg><div>Puedes guardar tu receta para que el personal de la óptica la revise antes de preparar tus lentes.</div></div>
                <div class="ivc-checkout-rx-layout">
                  <div class="ivc-checkout-rx-intro"><span class="ivc-checkout-rx-illustration" aria-hidden="true"><svg viewBox="0 0 92 76" width="83" height="68" fill="none"><rect x="21" y="5" width="48" height="61" rx="5" fill="#EAF6FC" stroke="#8FC9EA"/><path d="M30 20h29M30 29h21M30 38h25" stroke="#287CB3" stroke-width="2" stroke-linecap="round"/><path d="M36 48h19l-4 8H40z" fill="#8FC9EA"/></svg></span><div><h3>¿Ya tienes una receta?</h3><p>Podrás ingresar los valores de forma manual o seleccionar tu receta en una fotografía o PDF.</p></div></div>
                  <div class="ivc-checkout-rx-controls">
                    <label for="ivcRxProduct">¿Para qué armazón?</label>
                    <select id="ivcRxProduct" class="form-select"><option value="">Selecciona un producto del pedido</option></select>
                    <div class="ivc-checkout-rx-switch" role="group" aria-label="Forma de proporcionar la graduación">
                      <button type="button" class="is-active" id="ivcRxManualBtn" aria-controls="ivcRxManual" aria-pressed="true">✎ Ingresar graduación</button>
                      <button type="button" id="ivcRxUploadBtn" aria-controls="ivcRxUpload" aria-pressed="false">↥ Subir mi receta</button>
                    </div>
                  </div>
                </div>

                <div id="ivcRxManual" class="ivc-checkout-rx-method">
                  <div class="ivc-checkout-rx-hint"><span aria-hidden="true">ⓘ</span> Si no conoces algún valor, déjalo vacío. Copia únicamente los datos que aparecen en tu receta.</div>
                  <div class="ivc-checkout-rx-grid">
                    <div class="ivc-checkout-rx-labels"><span>Ojo</span><span>Esfera (ESF)</span><span>Cilindro (CIL)</span><span>Eje (°)</span><span>Adición (ADD)</span></div>
                    <?php foreach (['od' => 'Derecho (OD)', 'oi' => 'Izquierdo (OI)'] as $eye => $eyeLabel): ?>
                    <div class="ivc-checkout-rx-row"><div class="ivc-checkout-eye"><span class="ivc-checkout-eye-dot" aria-hidden="true"></span><?php echo $eyeLabel; ?></div>
                      <label><span>Esfera</span><input type="number" inputmode="decimal" step="0.25" min="-30" max="30" placeholder="Ej. -1.50" id="ivcRx-<?php echo $eye; ?>-esf"></label>
                      <label><span>Cilindro</span><input type="number" inputmode="decimal" step="0.25" min="-15" max="15" placeholder="Ej. -0.75" id="ivcRx-<?php echo $eye; ?>-cil"></label>
                      <label><span>Eje</span><input type="number" inputmode="numeric" step="1" min="0" max="180" placeholder="0 – 180" id="ivcRx-<?php echo $eye; ?>-eje"></label>
                      <label><span>ADD</span><input type="number" inputmode="decimal" step="0.25" min="0" max="5" placeholder="Opcional" id="ivcRx-<?php echo $eye; ?>-add"></label>
                    </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="ivc-checkout-extra-row"><label for="ivcRxDp">Distancia pupilar (DP) · mm <span>Opcional</span></label><input type="number" min="40" max="80" step="0.5" inputmode="decimal" id="ivcRxDp" placeholder="Ej. 62"></div>
                </div>

                <div id="ivcRxUpload" class="ivc-checkout-rx-method" hidden>
                  <label class="ivc-checkout-file-zone" for="ivcRxFile" id="ivcRxDropzone">
                    <span class="ivc-checkout-file-glyph" aria-hidden="true"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V3m-5 5 5-5 5 5"/><path d="M4 16v4h16v-4"/></svg></span>
                    <strong>Selecciona una fotografía o PDF</strong><span>O arrastra tu archivo aquí</span><small>JPG, PNG o PDF · Hasta 10 MB · Carga y revisión segura</small>
                  </label>
                  <input type="file" id="ivcRxFile" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" class="ivc-checkout-visually-hidden">
                  <div id="ivcRxFileResult" class="ivc-checkout-file-result" hidden aria-live="polite"></div>
                  <p id="ivcRxFileError" class="ivc-checkout-file-error" role="alert" hidden></p>
                </div>
                <div class="ivc-checkout-rx-bottom"><span class="ivc-checkout-pill">Guardado seguro en tu cuenta · Receta sujeta a revisión</span><span>La receta debe ser revisada por personal de la óptica.</span></div>

                <!-- IVC RECETAS FASE 3: registro seguro, NO vincula a pedido -->
                <div class="ivc-rx-save-card" id="ivcRxSaveCard" data-base="<?php echo htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8'); ?>">
                    <h3>Guardar tu receta para revisión</h3>
                    <p>Tu información quedará en tu cuenta y será revisada por personal autorizado de la óptica. El personal deberá aprobarla antes de comenzar la preparación de tus lentes.</p>
                    <label for="ivcRxDate">Fecha de emisión de tu receta (opcional)</label>
                    <input type="date" id="ivcRxDate" max="<?php echo date('Y-m-d'); ?>">
                    <label for="ivcRxNotes">Observaciones (opcional)</label>
                    <textarea rows="2" id="ivcRxNotes" maxlength="1000" placeholder="Ej. Receta emitida por un especialista externo"></textarea>
                    <label class="ivc-rx-consent"><input type="checkbox" id="ivcRxConsent"><span>Autorizo enviar esta información de graduación y/o archivo para que Instituto Visual California la revise. Entiendo que requiere validación antes de fabricar mis lentes.</span></label>
                    <button type="button" id="ivcRxSaveButton">Guardar receta para revisión</button>
                    <p class="ivc-rx-save-status" id="ivcRxSaveStatus" aria-live="polite" role="status"></p>
                    <div id="ivcRxSavedList" class="ivc-rx-saved-list" hidden></div>
                </div>
              </section></div>
    <div class="ivc-flow-plain-note" id="ivcFlowPlainNote" hidden><strong>Compra sin graduación.</strong> Podrás continuar al pago con el subtotal de los armazones. No se agregarán micas graduadas.</div>
    <div class="ivc-flow-navigation"><button class="ivc-flow-back" id="ivcFlowBack1" type="button">← Volver a productos</button><button class="ivc-flow-skip" id="ivcFlowSkipRx" type="button">Omitir graduación →</button><button class="ivc-flow-primary" id="ivcFlowToPay" type="button" disabled>Continuar al resumen <span aria-hidden="true">→</span></button></div>
  </section>
  <section class="ivc-flow-step" id="ivcFlowStep3" data-flow-step="3" aria-label="Resumen final y pago" hidden>
    <div class="ivc-flow-heading"><div class="ivc-checkout-kicker">PASO 03 · CONFIRMACIÓN</div><h2>Revisa antes de finalizar</h2><p>Comprueba cómo deseas comprar tus armazones y el importe correspondiente.</p></div>
    <div class="ivc-checkout-columns">
      <section class="ivc-checkout-panel ivc-flow-final-summary" aria-label="Resumen del pedido"><div class="ivc-checkout-panel-header"><div><div class="ivc-checkout-kicker">RESUMEN DE COMPRA</div><h2>Todo en orden</h2><p>Puedes volver al paso anterior para cambiar la elección.</p></div></div><div class="ivc-flow-final-rows">
        <div><span>Productos del carrito</span><strong id="ivcFlowItemCount">—</strong></div>
        <div><span>Importe de armazones</span><strong id="ivcFlowBaseTotal">—</strong></div>
        <div><span>Graduación / micas</span><strong id="ivcFlowRxCost">Debes elegir una opción</strong></div>
        <div class="ivc-flow-final-total"><span id="ivcFlowTotalLabel">Total a pagar</span><strong id="ivcFlowGrandTotal">—</strong></div>
      </div><div class="ivc-flow-final-decision" id="ivcFlowFinalDecision" aria-live="polite"></div><div class="ivc-flow-extra-info">El total definitivo de tu compra se mostrará antes de realizar el pago.</div></section>
      <aside class="ivc-checkout-right" aria-label="Resumen y pago">
              <div class="ivc-checkout-payment ivc-checkout-panel">
                <div class="ivc-checkout-kicker">PASO 03 · REALIZA TU PAGO</div>
                <h2>Finaliza tu pedido</h2>
                <div class="ivc-checkout-customer"><div class="ivc-checkout-avatar" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-1a7 7 0 0 1 14 0v1"/></svg></div><div><span>Estás comprando como</span><strong><?php echo $ivcE($ivcName); ?></strong><small><?php echo $ivcE($ivcMail); ?></small></div></div>
                <div class="ivc-flow-quote-lock" id="ivcQuoteLock" hidden>
                    <div class="ivc-flow-quote-icon" aria-hidden="true">⌕</div>
                    <h3>Revisa el total antes de pagar</h3>
                    <p>Consulta el resumen actualizado para verificar el costo de tu armazón y tus micas.</p>
                    <a class="ivc-flow-link-cta" href="<?php echo BASE_URL; ?>principal/agendar">Solicitar asesoría de graduación →</a>
                  </div>
                  <!-- Confirmación para clientes que proporcionaron su graduación -->
<div class="ivc-flow-payment-scope" id="ivcKnownFrameOption" hidden>

    <label for="ivcKnownFrameConfirm"
           class="d-flex align-items-start gap-2 mb-0">

        <input
            type="checkbox"
            id="ivcKnownFrameConfirm"
            class="form-check-input mt-1"
        >

        <span>
            Confirmo que revisé los artículos y sus precios antes de continuar con el pago.
        </span>

    </label>

</div>
                <div class="ivc-flow-payment-scope" id="ivcPayScope" hidden role="note"></div>
                <div class="ivc-checkout-payment-intro" id="ivcPayIntro"><span class="ivc-checkout-payment-icon" aria-hidden="true">✓</span><div><strong>Pago protegido con PayPal</strong><p>Elige PayPal o tarjeta de débito o crédito para continuar.</p></div></div>
                <div class="ivc-checkout-paypal-box" id="ivcPayReal" hidden><div class="ivc-checkout-paypal-heading"><span>Pago en línea</span><strong>PayPal</strong></div><div id="paypal-button-container" aria-label="Opciones de pago con PayPal"></div><p class="ivc-flow-paypal-status" id="ivcPaypalStatus" role="status"></p></div>
                <p class="ivc-checkout-payment-warning" id="ivcPayWarning"><strong>Importante:</strong> revisa la graduación y el material de tus micas antes de confirmar el pago. Las recetas requieren validación de la óptica.</p>
                <div class="ivc-checkout-help"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 5h14v14H5zM8 9h8M8 13h6"/></svg><div><span>¿Tienes dudas sobre tu graduación?</span><a href="<?php echo BASE_URL; ?>principal/agendar">Agenda una revisión visual →</a></div></div>
                <a class="ivc-checkout-logout" href="<?php echo BASE_URL; ?>clientes/salir">Cerrar sesión</a>
              </div>
            </aside>
    </div>
    <div class="ivc-flow-navigation"><button class="ivc-flow-back" id="ivcFlowBack2" type="button">← Volver a graduación</button><a class="ivc-flow-alt" href="<?php echo BASE_URL; ?>principal/shop">Seguir comprando →</a></div>
  </section>
</div>
        </div>
        <div class="tab-pane fade" id="pendientes-tab-pane" role="tabpanel" aria-labelledby="pendientes-tab" tabindex="0"><section class="ivc-checkout-panel ivc-checkout-history"><div class="ivc-checkout-panel-header"><div><div class="ivc-checkout-kicker">MI CUENTA</div><h2>Historial de pedidos</h2><p>Consulta el estado de tus compras.</p></div></div><div class="table-responsive"><table id="tblPendientes" class="table table-hover" style="width:100%"><thead><tr><th>Transacción</th><th>Monto</th><th>Fecha</th><th>Detalle</th></tr></thead><tbody></tbody></table></div></section></div>
        <div class="tab-pane fade" id="productos-tab-pane" role="tabpanel" aria-labelledby="productos-tab" tabindex="0"><section class="ivc-checkout-panel ivc-checkout-history"><div class="ivc-checkout-panel-header"><div><div class="ivc-checkout-kicker">MI CUENTA</div><h2>Mis productos</h2><p>Revisa tus compras y califica tus productos.</p></div></div><div class="table-responsive"><table id="tblProductos" class="table table-hover" style="width:100%"><thead><tr><th>ID</th><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Calificación</th></tr></thead><tbody></tbody></table></div></section></div>
      </div>
    <?php else: ?>
      <section class="ivc-checkout-panel ivc-checkout-notverified"><span aria-hidden="true">✉</span><h2>Verifica tu correo electrónico</h2><p>Antes de continuar con tu pedido, confirma la dirección de correo registrada en tu cuenta.</p><a href="<?php echo BASE_URL; ?>">Volver al inicio</a></section>
    <?php endif; ?>
  </div>
</main>

<!-- Conserva el modal original y sus identificadores para assets/js/clientes.js. -->
<div id="modalPedido" class="modal fade" tabindex="-1" aria-labelledby="ivcPedidoModalTitle" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5" id="ivcPedidoModalTitle">Estado del pedido</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><div class="row g-3 mb-3"><div class="col-md-4"><div id="estadoEnviado" class="services-icon-wap ivc-checkout-status"><i class="fa fa-truck" aria-hidden="true"></i><span>Pendiente</span></div></div><div class="col-md-4"><div id="estadoProceso" class="services-icon-wap ivc-checkout-status"><i class="fa fa-exchange-alt" aria-hidden="true"></i><span>En proceso</span></div></div><div class="col-md-4"><div id="estadoCompletado" class="services-icon-wap ivc-checkout-status"><i class="fa fa-check-circle" aria-hidden="true"></i><span>Completado</span></div></div></div><div class="table-responsive"><table id="tablePedidos" class="table table-hover align-middle" style="width:100%"><thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr></thead><tbody></tbody></table></div></div></div></div></div>
<?php include_once 'Views/template/footer.php'; ?>
<!-- Se conservan scripts compartidos con el historial; el flujo de pago antiguo permanece desactivado. -->
<script src="<?php echo BASE_URL; ?>assets/js/clientes.js?v=13.3.0"></script>
<script src="<?php echo BASE_URL; ?>assets/DataTables/datatables.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/es-MX.js"></script>
<!-- El SDK de PayPal se carga exclusivamente desde ivc-paypal-sandbox-v45.js; no cargar el SDK heredado. -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/DataTables/datatables.css">
<script src="<?php echo BASE_URL; ?>assets/js/ivc-checkout-v10.js?v=10.0.0"></script>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-checkout-steps-v12.js?v=12.3.0"></script>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-checkout-cotizacion-v44.js?v=4.9.1ux3"></script>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-paypal-sandbox-v45.js?v=4.9.2confirmacion"></script>
<script src="<?php echo BASE_URL; ?>assets/js/ivc-recetas-checkout.js?v=4.9.1c"></script>
