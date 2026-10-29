<?php
include_once 'Views/template/header.php';
$ivcEsc = static fn($s): string => htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$ivcHomeCategories = $data['categorias'] ?? [];
$ivcHomeProducts = $data['nuevoProductos'] ?? [];
?>
<main class="ivc-home" id="contenido-principal">
    <!-- Carrusel original de Bootstrap: se conserva su ID para compatibilidad. -->
    <div class="carousel slide carousel-fade ivc-editorial-carousel" id="template-mo-zay-hero-carousel" data-bs-ride="carousel" data-bs-interval="6800" aria-roledescription="carrusel" aria-label="Nuestras colecciones y atención visual">
        <div class="carousel-inner">
            <section class="carousel-item active ivc-hero-slide">
                <img class="ivc-hero-photo" src="https://images.pexels.com/photos/5766609/pexels-photo-5766609.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1920" alt="Armazones exhibidos en una óptica" fetchpriority="high" onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/img/banner_img_006.jpg';">
                <div class="ivc-hero-shade"></div>
                <div class="container ivc-hero-inner">
                    <div class="ivc-hero-content">
                        <span class="ivc-overline ivc-hero-eyebrow">INSTITUTO VISUAL CALIFORNIA <span class="ivc-dot"></span> TIJUANA</span>
                        <h1>Tu visión.<br><em>Tu mejor versión.</em></h1>
                        <p>Descubre armazones que complementan tu personalidad y una atención dedicada al cuidado de tu mirada.</p>
                        <div class="ivc-hero-buttons"><a class="ivc-btn ivc-btn-light" href="<?php echo BASE_URL; ?>principal/shop">Explorar colección <span aria-hidden="true">↗</span></a><a class="ivc-btn ivc-btn-glass" href="<?php echo BASE_URL; ?>principal/agendar">Agendar cita <span aria-hidden="true">→</span></a></div>
                    </div>
                    <span class="ivc-hero-side-note">ESTILO · VISIÓN · BIENESTAR</span>
                </div>
            </section>
            <section class="carousel-item ivc-hero-slide">
                <img class="ivc-hero-photo" src="https://images.pexels.com/photos/5752319/pexels-photo-5752319.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1920" alt="Persona usando anteojos modernos" loading="lazy" onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/img/banner_img_004.png';">
                <div class="ivc-hero-shade"></div>
                <div class="container ivc-hero-inner">
                    <div class="ivc-hero-content"><span class="ivc-overline ivc-hero-eyebrow">DISEÑO PARA CADA MIRADA</span><h2>Encuentra el estilo<br><em>que habla de ti.</em></h2><p>Entre líneas clásicas y diseños actuales, hay un armazón hecho para acompañarte todos los días.</p><div class="ivc-hero-buttons"><a class="ivc-btn ivc-btn-light" href="<?php echo BASE_URL; ?>principal/shop">Ver armazones <span aria-hidden="true">↗</span></a><a class="ivc-btn ivc-btn-glass" href="<?php echo BASE_URL; ?>principal/about">Conócenos <span aria-hidden="true">→</span></a></div></div>
                </div>
            </section>
            <section class="carousel-item ivc-hero-slide">
                <img class="ivc-hero-photo" src="https://images.pexels.com/photos/5621850/pexels-photo-5621850.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1920" alt="Atención personalizada durante una revisión visual" loading="lazy" onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/img/banner_img_001.jpg';">
                <div class="ivc-hero-shade"></div>
                <div class="container ivc-hero-inner">
                    <div class="ivc-hero-content"><span class="ivc-overline ivc-hero-eyebrow">CUIDADO VISUAL</span><h2>Mira cada día<br><em>con más claridad.</em></h2><p>Tu bienestar visual merece tiempo y atención. Agenda tu visita de forma sencilla desde nuestro sitio.</p><div class="ivc-hero-buttons"><a class="ivc-btn ivc-btn-light" href="<?php echo BASE_URL; ?>principal/agendar">Agenda tu cita <span aria-hidden="true">↗</span></a><a class="ivc-btn ivc-btn-glass" href="<?php echo BASE_URL; ?>principal/about">Más información <span aria-hidden="true">→</span></a></div></div>
                </div>
            </section>
        </div>
        <div class="ivc-hero-navigation container">
            <div class="carousel-indicators"><button type="button" data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Diapositiva 1"></button><button type="button" data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="1" aria-label="Diapositiva 2"></button><button type="button" data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="2" aria-label="Diapositiva 3"></button></div>
            <div class="ivc-hero-arrows"><button type="button" data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide="prev" aria-label="Imagen anterior">←</button><button type="button" data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide="next" aria-label="Imagen siguiente">→</button></div>
        </div>
    </div>

    <div class="ivc-value-strip" aria-label="Nuestra propuesta"><div class="container"><span>UNA MIRADA MÁS PERSONAL</span><span class="ivc-value-separator" aria-hidden="true">✦</span><span>ARMAZONES PARA CADA ESTILO</span><span class="ivc-value-separator" aria-hidden="true">✦</span><span>ATENCIÓN VISUAL EN TIJUANA</span></div></div>

    <section class="ivc-section ivc-categories-section" aria-labelledby="ivc-categories-heading">
        <div class="container">
            <div class="ivc-section-heading ivc-reveal"><div><span class="ivc-overline">DESCUBRE TU ESTILO</span><h2 id="ivc-categories-heading">Encuentra tu <em>próxima mirada.</em></h2></div><a class="ivc-text-link" href="<?php echo BASE_URL; ?>principal/shop">Ver toda la colección <span aria-hidden="true">↗</span></a></div>
            <?php if (!empty($ivcHomeCategories)): ?>
              <div class="row g-3 g-xl-4 ivc-categories-grid">
                <?php foreach ($ivcHomeCategories as $categoria): ?>
                    <div class="col-6 col-md-4 col-xl-2 ivc-reveal">
                        <a class="ivc-category-card" href="<?php echo BASE_URL; ?>principal/categorias/<?php echo (int)$categoria['id']; ?>" aria-label="Explorar categoría <?php echo $ivcEsc($categoria['categoria']); ?>">
                            <span class="ivc-category-image"><img src="<?php echo $ivcEsc($categoria['imagen']); ?>" alt="Armazones de la categoría <?php echo $ivcEsc($categoria['categoria']); ?>" loading="lazy" decoding="async"><span class="ivc-category-visit" aria-hidden="true">↗</span></span>
                            <strong><?php echo $ivcEsc($categoria['categoria']); ?></strong><span>Explorar modelos</span>
                        </a>
                    </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?><p class="ivc-empty-state">Las categorías se mostrarán aquí cuando estén disponibles en el catálogo.</p><?php endif; ?>
        </div>
    </section>

    <!-- Editorial fotográfico: enlaza las funciones reales de tienda y citas. -->
    <section class="ivc-editorial-section" aria-label="Nuestra forma de cuidar tu mirada">
        <div class="container ivc-editorial-grid">
            <div class="ivc-editorial-large ivc-reveal"><img src="https://images.pexels.com/photos/5621850/pexels-photo-5621850.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1200" alt="Cliente recibiendo asesoría para elegir sus lentes" loading="lazy" onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/img/banner_img_001.jpg';"><span class="ivc-photo-caption">01 / ASESORÍA Y CUIDADO VISUAL</span></div>
            <div class="ivc-editorial-copy ivc-reveal"><span class="ivc-overline">MUCHO MÁS QUE ARMAZONES</span><h2>Cuidamos lo que<br>más importa:<br><em>tu mirada.</em></h2><p>Creemos que ver bien y sentirte bien con tus lentes van de la mano. Descubre nuestras opciones y acércate a nosotros cuando necesites orientación.</p><a class="ivc-text-link" href="<?php echo BASE_URL; ?>principal/agendar">Conoce nuestra atención <span aria-hidden="true">↗</span></a><div class="ivc-editorial-index">IVC <span>·</span> CALIFORNIA</div></div>
        </div>
    </section>

    <section class="ivc-section ivc-products-section" aria-labelledby="ivc-products-heading">
      <div class="container">
        <div class="ivc-section-heading ivc-reveal"><div><span class="ivc-overline">EN NUESTRA ÓPTICA</span><h2 id="ivc-products-heading">Selección <em>destacada.</em></h2><p>Armazones para acompañarte en cada momento.</p></div><a class="ivc-text-link" href="<?php echo BASE_URL; ?>principal/shop">Explorar tienda <span aria-hidden="true">↗</span></a></div>
        <?php if (!empty($ivcHomeProducts)): ?>
          <div class="row g-3 g-lg-4 ivc-products-grid">
            <?php foreach ($ivcHomeProducts as $producto): ?>
              <?php $ivcStars = max(0, min(5, (int)($producto['calificacion'] ?? 0))); ?>
              <div class="col-12 col-sm-6 col-lg-4 ivc-reveal"><article class="ivc-product-card">
                <a class="ivc-product-image" href="<?php echo BASE_URL; ?>principal/detail/<?php echo (int)$producto['id']; ?>"><img src="<?php echo $ivcEsc($producto['imagen']); ?>" alt="<?php echo $ivcEsc($producto['nombre']); ?>" loading="lazy" decoding="async"><span class="ivc-product-pill">COLECCIÓN IVC</span><span class="ivc-product-go" aria-hidden="true">↗</span></a>
                <div class="ivc-product-details"><div class="ivc-product-rating" aria-label="Calificación: <?php echo $ivcStars; ?> de 5"><span aria-hidden="true"><?php echo str_repeat('★', $ivcStars) . str_repeat('☆', 5 - $ivcStars); ?></span></div><h3><a href="<?php echo BASE_URL; ?>principal/detail/<?php echo (int)$producto['id']; ?>"><?php echo $ivcEsc($producto['nombre']); ?></a></h3><p><?php echo $ivcEsc($producto['descripcion']); ?></p><div class="ivc-product-bottom"><strong>$<?php echo number_format((float)$producto['precio'], 2); ?> <small><?php echo MONEDA; ?></small></strong><a href="<?php echo BASE_URL; ?>principal/detail/<?php echo (int)$producto['id']; ?>" aria-label="Ver <?php echo $ivcEsc($producto['nombre']); ?>">Ver detalles <span aria-hidden="true">↗</span></a></div></div>
              </article></div>
            <?php endforeach; ?>
          </div>
        <?php else: ?><p class="ivc-empty-state">Aquí aparecerán los productos disponibles en tu catálogo.</p><?php endif; ?>
      </div>
    </section>

    <section class="ivc-appointment-banner" aria-label="Agenda tu cita"><div class="container"><div class="ivc-appointment-inner ivc-reveal"><div><span class="ivc-overline">TU BIENESTAR VISUAL</span><h2>Una mirada más clara<br>comienza con <em>una cita.</em></h2><p>Encuentra un momento para ti. Agenda tu visita al Instituto Visual California.</p></div><a class="ivc-btn ivc-btn-deep" href="<?php echo BASE_URL; ?>principal/agendar">Agendar ahora <span aria-hidden="true">↗</span></a></div></div></section>
</main>

<!-- Alertas de confirmación/cancelación desde el flujo de citas original. -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  <?php if (isset($_SESSION['alerta_confirmacion'])): ?>
    if (typeof Swal !== 'undefined') Swal.fire({title:'¡Cita confirmada!', text: <?php echo json_encode($_SESSION['alerta_confirmacion'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, icon:'success', confirmButtonColor:'#287CB3'});
    <?php unset($_SESSION['alerta_confirmacion']); ?>
  <?php endif; ?>
  <?php if (isset($_SESSION['alerta_error'])): ?>
    if (typeof Swal !== 'undefined') Swal.fire({title:'Aviso', text: <?php echo json_encode($_SESSION['alerta_error'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>, icon:'error', confirmButtonColor:'#287CB3'});
    <?php unset($_SESSION['alerta_error']); ?>
  <?php endif; ?>
});
</script>
<?php include_once 'Views/template/footer.php'; ?>
