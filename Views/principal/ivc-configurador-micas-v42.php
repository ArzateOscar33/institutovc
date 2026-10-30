<?php
/** Fase 4.2: configuración y cotización, sin modificar el carrito tradicional. */
if (!isset($productId, $price, $stock, $isAvailable, $ivcEscape)) { return; }
?>
<section class="ivc42-entry" aria-label="Personalizar lentes oftálmicos">
  <span class="ivc42-tag">LENTES A TU MEDIDA</span>
  <h2>¿Los prefieres con graduación?</h2>
  <p>Elige un par de micas, adjunta tu receta o solicita una valoración. Consulta el precio completo antes de decidir.</p>
  <button type="button" id="ivc42Open" class="ivc42-entry-button" <?php echo $isAvailable ? '' : 'disabled'; ?>>
    Configurar mis micas <span aria-hidden="true">→</span>
  </button>
  <small>El botón anterior agrega <strong>solo el armazón</strong>. Las micas se cotizan por separado durante esta fase.</small>
</section>
<div class="ivc42-backdrop" id="ivc42Backdrop" hidden></div>
<aside class="ivc42-drawer" id="ivc42Drawer" role="dialog" aria-modal="true" aria-labelledby="ivc42Title" aria-hidden="true" hidden
  data-id-producto="<?php echo (int)$productId; ?>"
  data-precio-armazon="<?php echo $ivcEscape(number_format((float)$price, 2, '.', '')); ?>"
  data-stock="<?php echo (int)$stock; ?>"
  data-url-base="<?php echo $ivcEscape(BASE_URL); ?>">
  <header class="ivc42-header">
    <div><span class="ivc42-kicker">INSTITUTO VISUAL CALIFORNIA · PERSONALIZA TUS LENTES</span>
      <h2 id="ivc42Title">Configura tus micas</h2></div>
    <button id="ivc42Close" class="ivc42-close" type="button" aria-label="Cerrar configurador">×</button>
  </header>
  <div class="ivc42-content" id="ivc42Content">
    <div class="ivc42-product-recap"><span>Armazón · <?php echo $ivcEscape($productName ?? 'Modelo seleccionado'); ?></span><strong>MXN $<?php echo number_format((float)$price, 2); ?></strong></div>
    <section aria-labelledby="ivc42StepOne">
      <div class="ivc42-section-title"><span class="ivc42-num">01</span><h3 id="ivc42StepOne">Elige tu tipo de lente</h3></div>
      <div id="ivc42Types" class="ivc42-types" role="group" aria-label="Tipo de lente"></div>
      <div id="ivc42Materials" class="ivc42-materials" role="radiogroup" aria-label="Material de micas"></div>
    </section>
    <section aria-labelledby="ivc42StepTwo">
      <div class="ivc42-section-title"><span class="ivc42-num">02</span><h3 id="ivc42StepTwo">¿Cómo proporcionarás tu graduación?</h3></div>
      <fieldset class="ivc42-recipe-options">
        <legend class="visually-hidden">Opciones de graduación</legend>
        <label><input type="radio" name="ivc42RecetaModo" value="ARCHIVO" checked> Adjuntar una receta nueva</label>
        <label><input type="radio" name="ivc42RecetaModo" value="EXISTENTE"> Usar una receta guardada</label>
        <label><input type="radio" name="ivc42RecetaModo" value="VALORACION"> No conozco mi graduación; necesito valoración</label>
        <label><input type="radio" name="ivc42RecetaModo" value="PENDIENTE"> Entregaré mi receta después</label>
      </fieldset>
      <div id="ivc42UploadPanel" class="ivc42-subpanel">
        <label for="ivc42File">Selecciona tu receta (JPG, PNG o PDF; máximo 10 MB)</label>
        <input id="ivc42File" type="file" accept="image/jpeg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf">
        <p>La receta se guardará de forma privada y quedará pendiente de revisión por la óptica.</p>
      </div>
      <div id="ivc42ExistingPanel" class="ivc42-subpanel" hidden>
        <label for="ivc42Existing">Elige una de tus recetas pendientes o aprobadas</label>
        <select id="ivc42Existing"><option value="">Cargando recetas…</option></select>
        <p id="ivc42ExistingHelp"></p>
      </div>
      <p id="ivc42SpecialNotice" class="ivc42-special-notice" hidden></p>
      <p class="ivc42-external-warning"><strong>Recetas externas:</strong> la óptica revisará su legibilidad y compatibilidad antes de fabricar las micas. Si los datos proporcionados son incorrectos, podrá solicitarse una corrección o valoración adicional.</p>
    </section>
    <section class="ivc42-quote" aria-labelledby="ivc42SummaryTitle">
      <div class="ivc42-section-title"><span class="ivc42-num">03</span><h3 id="ivc42SummaryTitle">Resumen de referencia</h3></div>
      <div><span>Armazón(es)</span><strong id="ivc42FrameTotal">—</strong></div>
      <div><span>Par(es) de micas</span><strong id="ivc42LensTotal">—</strong></div>
      <div class="ivc42-total"><span>Total estimado</span><strong id="ivc42Estimate">—</strong></div>
      <p>Los precios vigentes provienen del catálogo de la óptica. La <strong>cotización definitiva la calculará PHP</strong> al guardar tu selección; es válida durante 15 minutos y no reserva inventario.</p>
    </section>
    <div id="ivc42Message" class="ivc42-message" role="status" aria-live="polite"></div>
    <div id="ivc42Result" class="ivc42-result" hidden></div>
  </div>
  <footer class="ivc42-footer">
    <button type="button" id="ivc42Quote" class="ivc42-submit" disabled>Calcular y guardar cotización</button>
    <p>Cotización informativa: <strong>no añade micas al carrito ni realiza cobros</strong>. La compra configurada estará disponible próximamente.</p>
  </footer>
</aside>
