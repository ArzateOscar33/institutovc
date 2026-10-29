<?php
/**
 * Instituto Visual California | Ficha de producto V7
 * Se integra con el controlador Principal::detail(), carrito.js y detail.js originales.
 * No reemplaza el header, footer ni la lógica de compras.
 */
include_once 'Views/template/header.php';

$ivcEscape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$ivcFallback = BASE_URL . 'assets/img/ivc-producto-sin-foto.svg';
$ivcImageUrl = static function ($value) use ($ivcFallback): string {
    $image = trim((string) $value);
    if ($image === '' || stripos($image, 'robohash.org') !== false) {
        return $ivcFallback;
    }
    if (preg_match('~^https?://~i', $image)) {
        return $image;
    }
    if (strpos($image, '/') === 0) {
        return $image;
    }
    return BASE_URL . ltrim(preg_replace('~^\./~', '', $image), '/');
};
$producto = $data['producto'] ?? [];
$productId = (int) ($producto['id'] ?? 0);
$productName = (string) ($producto['nombre'] ?? 'Armazón');
$categoryName = (string) ($producto['categoria'] ?? 'Armazones');
$categoryId = (int) ($producto['id_categoria'] ?? 0);
$price = (float) ($producto['precio'] ?? 0);
$stock = max(0, (int) ($producto['cantidad'] ?? 0));
$isAvailable = $stock > 0 && (int) ($producto['estado'] ?? 1) === 1;
$description = trim(html_entity_decode(strip_tags((string) ($producto['descripcion'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
$mainUrl = $ivcImageUrl($producto['imagen'] ?? '');

// Galería: imagen principal más imágenes de la carpeta producto (sin duplicados).
$gallery = [['src' => $mainUrl, 'alt' => 'Vista principal de ' . $productName]];
$gallerySeen = [$mainUrl => true];
foreach (($data['imagenes'] ?? []) as $file) {
    $filename = (string) $file;
    if ($filename === '' || basename($filename) !== $filename || !preg_match('/\.(png|jpe?g|webp|gif)$/i', $filename)) {
        continue;
    }
    $url = BASE_URL . 'assets/images/productos/' . $productId . '/' . rawurlencode($filename);
    if (!isset($gallerySeen[$url])) {
        $gallery[] = ['src' => $url, 'alt' => 'Otra vista de ' . $productName];
        $gallerySeen[$url] = true;
    }
}
$related = is_array($data['relacionados'] ?? null) ? $data['relacionados'] : [];
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-detalle-v7.css?v=7.0.0">
<main id="ivc-product-detail" class="ivcd-product-page">
  <div class="container ivcd-container">
    <nav class="ivcd-breadcrumb" aria-label="Ruta de navegación">
      <a href="<?php echo BASE_URL; ?>">Inicio</a><span aria-hidden="true">/</span>
      <a href="<?php echo BASE_URL; ?>principal/shop">Colección</a><span aria-hidden="true">/</span>
      <span aria-current="page"><?php echo $ivcEscape($productName); ?></span>
    </nav>

    <div class="ivcd-product-layout">
      <div class="ivcd-gallery" aria-label="Fotografías de producto">
        <div class="ivcd-gallery-main">
          <div class="ivcd-gallery-badge"><span></span> NUESTRA COLECCIÓN</div>
          <button class="ivcd-main-photo" id="ivcdOpenZoom" type="button" aria-label="Ampliar fotografía de <?php echo $ivcEscape($productName); ?>">
            <img id="product-detail" src="<?php echo $ivcEscape($gallery[0]['src']); ?>" alt="<?php echo $ivcEscape($gallery[0]['alt']); ?>" fetchpriority="high" data-fallback="<?php echo $ivcEscape($ivcFallback); ?>">
            <span class="ivcd-zoom-prompt" aria-hidden="true">
              <svg viewBox="0 0 24 24"><circle cx="10" cy="10" r="6.4"/><path d="m15 15 6 6M7.5 10h5M10 7.5v5"/></svg>
              Clic para ampliar
            </span>
          </button>
        </div>
        <?php if (count($gallery) > 1): ?>
          <div class="ivcd-gallery-thumbs" id="ivcdGalleryThumbs" aria-label="Selecciona una fotografía">
            <?php foreach ($gallery as $i => $img): ?>
              <button type="button" class="ivcd-thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-gallery-index="<?php echo $i; ?>" aria-label="Mostrar imagen <?php echo $i + 1; ?>" aria-pressed="<?php echo $i === 0 ? 'true' : 'false'; ?>">
                <img src="<?php echo $ivcEscape($img['src']); ?>" alt="" loading="lazy" data-fallback="<?php echo $ivcEscape($ivcFallback); ?>">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <p class="ivcd-gallery-help">Selecciona una miniatura o amplía la imagen para apreciar los detalles.</p>
      </div>

      <div class="ivcd-info-panel">
        <div class="ivcd-eyebrow"><span class="ivcd-rule"></span> INSTITUTO VISUAL CALIFORNIA</div>
        <a class="ivcd-category" href="<?php echo BASE_URL . 'principal/categorias/' . $categoryId; ?>"><?php echo $ivcEscape($categoryName); ?> <span aria-hidden="true">↗</span></a>
        <h1><?php echo $ivcEscape($productName); ?></h1>
        <div class="ivcd-price-row">
          <span class="ivcd-price"><?php echo $ivcEscape(MONEDA); ?> $<?php echo number_format($price, 2, '.', ','); ?></span>
          <span class="ivcd-stock <?php echo $isAvailable ? 'in-stock' : 'out-stock'; ?>">
            <span class="ivcd-stock-dot"></span><?php echo $isAvailable ? 'Disponible' : 'Sin existencias'; ?>
          </span>
        </div>
        <div class="ivcd-divider"></div>
        <div class="ivcd-description-block">
          <h2>Acerca de este modelo</h2>
          <p><?php echo $description !== '' ? nl2br($ivcEscape($description)) : 'Conoce este modelo de nuestra colección de armazones.'; ?></p>
        </div>

        <form class="ivcd-purchase" id="ivcdPurchaseForm" action="#" method="get" onsubmit="return false;">
          <input type="hidden" id="idProducto" value="<?php echo $productId; ?>">
          <div class="ivcd-purchase-label">Cantidad</div>
          <div class="ivcd-quantity-row">
            <div class="ivcd-stepper" role="group" aria-label="Selecciona la cantidad">
              <button type="button" class="ivcd-qty-button" data-ivcd-qty="-1" aria-label="Reducir cantidad">−</button>
              <output id="var-value" aria-live="polite">1</output>
              <button type="button" class="ivcd-qty-button" data-ivcd-qty="1" aria-label="Aumentar cantidad">+</button>
            </div>
            <input type="hidden" id="product-quanity" value="1" data-max="<?php echo $stock; ?>">
            <span class="ivcd-qty-note"><?php echo $isAvailable ? 'Selecciona las piezas que deseas' : 'No disponible para compra'; ?></span>
          </div>
          <div class="ivcd-purchase-actions">
            <button type="button" id="btnAddCart" class="ivcd-buy-button" <?php echo $isAvailable ? '' : 'disabled'; ?>>
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
              Agregar al carrito <span aria-hidden="true">→</span>
            </button>
            <button type="button" class="ivcd-wish-button btnAddDeseo" id="btnAddDeseoSingle" prod="<?php echo $productId; ?>" aria-label="Agregar a favoritos" title="Agregar a favoritos">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.6c0 4.2-8.8 10-8.8 10s-8.8-5.8-8.8-10a4.7 4.7 0 0 1 8.8-2.1 4.7 4.7 0 0 1 8.8 2.1Z"/></svg>
            </button>
          </div>
          <p class="ivcd-purchase-hint">Tu selección se guardará en el carrito para continuar con el pedido.</p>
        </form>
        <div class="ivcd-help-card">
          <span class="ivcd-help-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 0 1 6 1c0 2-3 2.2-3 4.2M12 17.5v.2"/></svg></span>
          <div><strong>¿Necesitas orientación para elegir?</strong><p>Podemos ayudarte a encontrar una opción para tu estilo y necesidades visuales.</p></div>
          <a href="<?php echo BASE_URL; ?>principal/agendar" aria-label="Agendar cita para recibir orientación">Agendar cita <span aria-hidden="true">↗</span></a>
        </div>
      </div>
    </div>

    <?php if (count($related) > 0): ?>
      <section class="ivcd-related" aria-labelledby="ivcdRelatedTitle">
        <div class="ivcd-related-heading">
          <div><span class="ivcd-eyebrow ivcd-related-eyebrow">TAMBIÉN TE PUEDE INTERESAR</span>
            <h2 id="ivcdRelatedTitle">Armazones que combinan contigo<span class="ivcd-blue-dot">.</span></h2>
            <p>Descubre más modelos de la categoría <?php echo $ivcEscape($categoryName); ?>.</p></div>
          <div class="ivcd-carousel-controls">
            <button type="button" id="ivcdRelatedPrev" aria-label="Ver productos anteriores" title="Anterior">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
            </button>
            <button type="button" id="ivcdRelatedNext" aria-label="Ver productos siguientes" title="Siguiente">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
            </button>
          </div>
        </div>
        <div class="ivcd-related-viewport" id="ivcdRelatedViewport" role="region" aria-label="Carrusel de productos similares" tabindex="0">
          <div class="ivcd-related-track" id="ivcdRelatedTrack">
            <?php foreach ($related as $relatedProduct):
              $relId = (int) ($relatedProduct['id'] ?? 0);
              if ($relId === $productId) { continue; }
              $relName = (string) ($relatedProduct['nombre'] ?? 'Armazón');
              $relPhoto = $ivcImageUrl($relatedProduct['imagen'] ?? '');
            ?>
              <article class="ivcd-related-card">
                <a class="ivcd-related-photo" href="<?php echo BASE_URL . 'principal/detail/' . $relId; ?>" aria-label="Ver detalle de <?php echo $ivcEscape($relName); ?>">
                  <img src="<?php echo $ivcEscape($relPhoto); ?>" alt="<?php echo $ivcEscape($relName); ?>" loading="lazy" data-fallback="<?php echo $ivcEscape($ivcFallback); ?>">
                  <span class="ivcd-related-overlay">Ver modelo <span aria-hidden="true">↗</span></span>
                </a>
                <div class="ivcd-related-meta"><span><?php echo $ivcEscape($categoryName); ?></span></div>
                <h3><a href="<?php echo BASE_URL . 'principal/detail/' . $relId; ?>"><?php echo $ivcEscape($relName); ?></a></h3>
                <p><?php echo $ivcEscape(MONEDA); ?> $<?php echo number_format((float) ($relatedProduct['precio'] ?? 0), 2, '.', ','); ?></p>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
        <p class="ivcd-carousel-hint">Desliza para descubrir más modelos <span aria-hidden="true">↔</span></p>
      </section>
    <?php else: ?>
      <section class="ivcd-related ivcd-related-empty" aria-labelledby="ivcdRelatedTitle">
        <h2 id="ivcdRelatedTitle">Descubre más de nuestra colección</h2>
        <p>Explora otros modelos que podrían interesarte.</p>
        <a href="<?php echo BASE_URL; ?>principal/shop">Ver colección <span aria-hidden="true">↗</span></a>
      </section>
    <?php endif; ?>
  </div>
</main>

<!-- Visor de imágenes ampliadas. Usa Bootstrap ya incluido en footer.php. -->
<div class="modal fade ivcd-zoom-modal" id="ivcdZoomModal" tabindex="-1" aria-hidden="true" aria-label="Fotografía ampliada">
  <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><span class="ivcd-zoom-heading">Galería · <?php echo $ivcEscape($productName); ?></span><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar imagen ampliada"></button></div>
    <div class="modal-body">
      <button type="button" id="ivcdZoomPrevious" class="ivcd-lightbox-arrow" aria-label="Fotografía anterior">‹</button>
      <img id="ivcdZoomImage" src="<?php echo $ivcEscape($mainUrl); ?>" alt="<?php echo $ivcEscape($productName); ?>" data-fallback="<?php echo $ivcEscape($ivcFallback); ?>">
      <button type="button" id="ivcdZoomNext" class="ivcd-lightbox-arrow" aria-label="Fotografía siguiente">›</button>
    </div>
    <div class="modal-footer"><span id="ivcdZoomCounter" aria-live="polite"></span></div>
  </div></div>
</div>
<script type="application/json" id="ivcdGalleryData"><?php echo json_encode($gallery, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<!-- El controlador de compra original permanece intacto. -->
<script src="<?php echo BASE_URL; ?>assets/js/modulos/detail.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/modulos/ivc-detalle-v7.js?v=7.0.0"></script>
<?php include_once 'Views/template/footer.php'; ?>
