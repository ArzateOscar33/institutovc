<?php
/**
 * Instituto Visual California | Sobre nosotros (rediseño V5)
 * Compatible con header/footer editorial V3 y catálogo V4.
 * Esta vista no modifica controladores, modelos ni base de datos.
 */
include_once 'Views/template/header.php';
$ivcAboutIcon = static function (string $name, int $size = 28): string {
    $paths = [
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.8"/>',
        'heart' => '<path d="M20.8 8.5c0 4.2-8.8 10.1-8.8 10.1S3.2 12.7 3.2 8.5a4.8 4.8 0 0 1 8.8-2.2 4.8 4.8 0 0 1 8.8 2.2Z"/>',
        'shield' => '<path d="M12 2 4 6v6c0 5 3.5 8.1 8 10 4.5-1.9 8-5 8-10V6l-8-4Z"/><path d="m8.5 12 2.4 2.5 4.8-5"/>',
        'spark' => '<path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5L12 2Z"/>',
        'pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.7"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/><path d="m9 15 2 2 4-4"/>',
        'glasses' => '<path d="M2 9.5 3 16a4 4 0 0 0 7.9.3l.2-.8a1 1 0 0 1 1.8 0l.2.8A4 4 0 0 0 21 16l1-6.5M2 12l5-1h3.5M22 12l-5-1h-3.5"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'northeast' => '<path d="M6 18 18 6M8 6h10v10"/>',
    ];
    $shape = $paths[$name] ?? $paths['arrow'];
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$shape.'</svg>';
};
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-about.css?v=5.0.0">
<main class="ivc-about" id="ivc-about-main">
  <!-- Hero: editorial suave, dos columnas, sin alterar navegación compartida -->
  <section class="ivc-about-hero" aria-labelledby="ivc-about-title">
    <div class="container ivc-about-hero-grid">
      <div class="ivc-about-hero-copy">
        <div class="ivc-about-kicker ivc-about-anim">QUIÉNES SOMOS <span></span> INSTITUTO VISUAL CALIFORNIA</div>
        <h1 id="ivc-about-title" class="ivc-about-anim">Una mirada clara.<br><em>Una atención más humana.</em></h1>
        <p class="ivc-about-hero-lead ivc-about-anim">En Instituto Visual California creemos que cuidar tu visión también significa escucharte, orientarte y ayudarte a encontrar lentes que se adapten a tu día a día.</p>
        <div class="ivc-about-hero-actions ivc-about-anim">
          <a class="ivc-about-button ivc-about-button-dark" href="<?php echo BASE_URL; ?>principal/agendar">Agenda tu cita <?php echo $ivcAboutIcon('northeast', 19); ?></a>
          <a class="ivc-about-text-button" href="#ivc-about-essence">Conoce nuestra esencia <span aria-hidden="true">↓</span></a>
        </div>
        <div class="ivc-about-hero-footnote ivc-about-anim"><span class="ivc-about-mini-symbol"><?php echo $ivcAboutIcon('eye', 21); ?></span> Cuidado visual · Armazones · Tijuana, B.C.</div>
      </div>
      <div class="ivc-about-hero-visual ivc-about-anim">
        <div class="ivc-about-hero-photo-wrap">
          <img class="ivc-about-hero-photo" src="https://images.pexels.com/photos/5621863/pexels-photo-5621863.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1300" alt="Fotografía ilustrativa de una revisión de la vista en una óptica" loading="eager" fetchpriority="high" decoding="async" onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/img/banner_img_001.jpg';">
        </div>
        <div class="ivc-about-picture-label"><span class="ivc-about-picture-icon"><?php echo $ivcAboutIcon('spark', 22); ?></span><span><strong>Tu mirada nos importa</strong><small>Compromiso con tu bienestar visual</small></span></div>
        <span class="ivc-about-photo-index" aria-hidden="true">IVC / 01</span>
      </div>
    </div>
    <div class="ivc-about-hero-bottom"><div class="container ivc-about-hero-bottom-inner"><span>UNA EXPERIENCIA MÁS PERSONAL</span><span class="ivc-about-tiny-star" aria-hidden="true">✳</span><span>UNA MIRADA MÁS CLARA</span><span class="ivc-about-tiny-star" aria-hidden="true">✳</span><span>CONTIGO EN CADA ELECCIÓN</span></div></div>
  </section>

  <!-- Identidad / manifiesto -->
  <section class="ivc-about-section ivc-about-story" id="ivc-about-essence" aria-labelledby="ivc-about-story-title">
    <div class="container ivc-about-story-grid">
      <div class="ivc-about-story-image ivc-about-reveal">
        <img src="https://images.pexels.com/photos/5766609/pexels-photo-5766609.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1200" alt="Fotografía ilustrativa de una variedad de armazones exhibidos en una óptica" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?php echo BASE_URL; ?>assets/img/banner_img_004.png';">
        <div class="ivc-about-photo-stamp"><span>IVC</span><small>ÓPTICA EN TIJUANA</small></div>
      </div>
      <div class="ivc-about-story-copy ivc-about-reveal">
        <span class="ivc-about-eyebrow">NUESTRA ESENCIA / 01</span>
        <h2 id="ivc-about-story-title">Miramos más allá de <em>los armazones.</em></h2>
        <div class="ivc-about-short-rule"></div>
        <p>Somos una óptica comprometida con ofrecer atención visual profesional y una experiencia cercana. Nos interesa que cada persona encuentre opciones que respondan a sus necesidades, preferencias y estilo.</p>
        <p>Nuestro propósito es acompañarte en cada decisión, con un trato transparente y una selección de productos ópticos pensados para el día a día.</p>
        <a class="ivc-about-inline-link" href="<?php echo BASE_URL; ?>principal/shop">Descubre nuestra colección <?php echo $ivcAboutIcon('northeast', 19); ?></a>
      </div>
    </div>
  </section>

  <!-- Misión, visión, valores que ya existen en la vista original -->
  <section class="ivc-about-section ivc-about-identity" aria-labelledby="ivc-about-identity-title">
    <div class="container">
      <div class="ivc-about-section-heading ivc-about-reveal"><div><span class="ivc-about-eyebrow">LO QUE NOS DEFINE / 02</span><h2 id="ivc-about-identity-title">Los valores detrás de <em>cada mirada.</em></h2></div><p>Una filosofía sencilla: combinar atención, confianza y calidad en cada visita.</p></div>
      <div class="ivc-about-values-grid">
        <article class="ivc-about-value-card ivc-about-reveal">
          <div class="ivc-about-card-top"><span>01 / PROPÓSITO</span><span class="ivc-about-card-icon"><?php echo $ivcAboutIcon('heart', 27); ?></span></div>
          <h3>Nuestra misión</h3>
          <p>Proveer servicios ópticos de calidad con atención personalizada y profesional, ofreciendo productos que ayuden a mejorar el bienestar visual de nuestros clientes.</p>
          <span class="ivc-about-card-underline"></span>
        </article>
        <article class="ivc-about-value-card ivc-about-reveal">
          <div class="ivc-about-card-top"><span>02 / FUTURO</span><span class="ivc-about-card-icon"><?php echo $ivcAboutIcon('eye', 28); ?></span></div>
          <h3>Nuestra visión</h3>
          <p>Ser una referencia en cuidado visual por nuestra atención, innovación y compromiso con las personas, contribuyendo al bienestar de nuestra comunidad.</p>
          <span class="ivc-about-card-underline"></span>
        </article>
        <article class="ivc-about-value-card ivc-about-reveal">
          <div class="ivc-about-card-top"><span>03 / PRINCIPIOS</span><span class="ivc-about-card-icon"><?php echo $ivcAboutIcon('shield', 27); ?></span></div>
          <h3>Nuestros valores</h3>
          <ul class="ivc-about-principles">
            <li><strong>Excelencia</strong><span>Atención cuidadosa en cada detalle.</span></li>
            <li><strong>Confianza</strong><span>Trato honesto y transparente.</span></li>
            <li><strong>Profesionalismo</strong><span>Compromiso con un servicio responsable.</span></li>
          </ul>
          <span class="ivc-about-card-underline"></span>
        </article>
      </div>
    </div>
  </section>

  <!-- Pasos / enlaces reales de navegación -->
  <section class="ivc-about-section ivc-about-path" aria-labelledby="ivc-about-path-title">
    <div class="container">
      <div class="ivc-about-section-heading ivc-about-reveal"><div><span class="ivc-about-eyebrow">ESTAMOS PARA TI / 03</span><h2 id="ivc-about-path-title">Tu experiencia <em>con nosotros.</em></h2></div><p>Una forma sencilla de acercarte a tu siguiente par de lentes.</p></div>
      <div class="ivc-about-path-grid">
        <article class="ivc-about-step ivc-about-reveal"><div class="ivc-about-step-top"><strong>01</strong><span><?php echo $ivcAboutIcon('calendar', 27); ?></span></div><h3>Agenda tu visita</h3><p>Selecciona una fecha y un horario desde nuestra plataforma de citas.</p><a href="<?php echo BASE_URL; ?>principal/agendar">Agendar cita <?php echo $ivcAboutIcon('arrow', 17); ?></a></article>
        <article class="ivc-about-step ivc-about-reveal"><div class="ivc-about-step-top"><strong>02</strong><span><?php echo $ivcAboutIcon('heart', 27); ?></span></div><h3>Recibe orientación</h3><p>Conoce nuestras opciones de atención visual y resuelve tus dudas directamente con nosotros.</p><a href="#ivc-about-location">Dónde encontrarnos <?php echo $ivcAboutIcon('arrow', 17); ?></a></article>
        <article class="ivc-about-step ivc-about-reveal"><div class="ivc-about-step-top"><strong>03</strong><span><?php echo $ivcAboutIcon('glasses', 29); ?></span></div><h3>Elige tu estilo</h3><p>Explora los armazones disponibles y descubre cuál combina mejor contigo.</p><a href="<?php echo BASE_URL; ?>principal/shop">Ver colección <?php echo $ivcAboutIcon('arrow', 17); ?></a></article>
      </div>
    </div>
  </section>

  <!-- Mapa conserva el embed ORIGINAL del sistema -->
  <section class="ivc-about-location" id="ivc-about-location" aria-labelledby="ivc-about-location-title">
    <div class="ivc-about-location-copy ivc-about-reveal">
      <div class="ivc-about-location-inner">
        <span class="ivc-about-eyebrow">VEN A CONOCERNOS / 04</span>
        <h2 id="ivc-about-location-title">Estamos cerca <em>de ti.</em></h2>
        <p>Será un gusto recibirte en Instituto Visual California. Encuentra nuestra ubicación y agenda tu próxima visita.</p>
        <div class="ivc-about-address"><span><?php echo $ivcAboutIcon('pin', 24); ?></span><address>Blvd. Alberto Limón Padilla 1-8-8,<br>La Pechuga, Fovissste, 22457<br>Tijuana, Baja California.</address></div>
        <a class="ivc-about-location-phone" href="tel:+526644913156">(664) 491-3156</a>
        <div class="ivc-about-location-actions"><a class="ivc-about-button ivc-about-button-light" href="<?php echo BASE_URL; ?>principal/agendar">Agendar visita <?php echo $ivcAboutIcon('northeast', 18); ?></a><a class="ivc-about-map-link" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&amp;query=Instituto+Visual+California+Tijuana">Cómo llegar <?php echo $ivcAboutIcon('northeast', 16); ?></a></div>
      </div>
    </div>
    <div class="ivc-about-map-shell ivc-about-reveal">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3362.3352500207823!2d-116.94746357484243!3d32.53261462797978!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80d9471dc77f80dd%3A0x77e78ae8607ee6d!2sInstituto%20Visual%20California!5e0!3m2!1ses-419!2smx!4v1695756017882!5m2!1ses-419!2smx" title="Mapa de ubicación de Instituto Visual California, Tijuana" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>
  </section>

  <section class="ivc-about-final" aria-labelledby="ivc-about-final-title"><div class="container ivc-about-final-inner ivc-about-reveal"><div><span class="ivc-about-eyebrow">TU MIRADA, NUESTRA PRIORIDAD</span><h2 id="ivc-about-final-title">Comencemos a ver <em>con otros ojos.</em></h2><p>Da el siguiente paso hacia una atención visual cercana.</p></div><a class="ivc-about-button ivc-about-button-dark" href="<?php echo BASE_URL; ?>principal/agendar">Agenda tu cita <?php echo $ivcAboutIcon('northeast', 20); ?></a></div></section>
</main>
<script defer src="<?php echo BASE_URL; ?>assets/js/ivc-about.js?v=5.0.0"></script>
<?php include_once 'Views/template/footer.php'; ?>
