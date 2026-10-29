<?php
/**
 * Instituto Visual California - Lista de deseos V8 (rediseño exclusivamente visual).
 * NO se modifica listaDeseo.js ni carrito.js: se preservan #tableListaDeseo y sus clases.
 */
include_once 'Views/template/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-deseos-v8.css?v=8.0.0">
<main id="ivc-wishlist" class="ivcw-page">
  <section class="ivcw-hero" aria-labelledby="ivcwTitle">
    <div class="container ivcw-hero-inner">
      <nav class="ivcw-breadcrumb" aria-label="Ruta de navegación">
        <a href="<?php echo BASE_URL; ?>">Inicio</a><span aria-hidden="true">/</span><span aria-current="page">Lista de deseos</span>
      </nav>
      <div class="ivcw-hero-grid">
        <div class="ivcw-hero-copy">
          <span class="ivcw-eyebrow"><span class="ivcw-eyebrow-line"></span> TU SELECCIÓN PERSONAL</span>
          <h1 id="ivcwTitle">Tus favoritos,<br><em>a tu manera.</em></h1>
          <p>Guarda los armazones que más te gustan. Vuelve a verlos cuando quieras y elige el estilo que mejor va contigo.</p>
          <a class="ivcw-hero-link" href="<?php echo BASE_URL; ?>principal/shop">Explorar colección <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></a>
        </div>
        <div class="ivcw-hero-art" aria-hidden="true">
          <div class="ivcw-art-orbit ivcw-art-orbit-one"></div><div class="ivcw-art-orbit ivcw-art-orbit-two"></div>
          <div class="ivcw-heart-icon"><svg viewBox="0 0 96 96" fill="none"><path d="M80 28c0 21-32 43-32 43S16 49 16 28a19 19 0 0 1 32-14 19 19 0 0 1 32 14Z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M35 29h26M42 25l-2 11M54 25l2 11" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg></div>
          <div class="ivcw-art-caption">PIEZAS QUE TE INSPIRAN <span>✦</span> ESTILO QUE PERDURA</div>
        </div>
      </div>
    </div>
  </section>

  <section class="container ivcw-content" aria-label="Productos guardados">
    <div class="ivcw-toolbar">
      <div>
        <span class="ivcw-eyebrow">NUESTRA COLECCIÓN · TUS ELECCIONES</span>
        <h2>Tu selección<span class="ivcw-heading-dot">.</span></h2>
        <p id="ivcwCountLabel" aria-live="polite">Cargando tus favoritos...</p>
      </div>
      <a href="<?php echo BASE_URL; ?>principal/shop" class="ivcw-text-link">Seguir descubriendo <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></a>
    </div>

    <div id="ivcwLoading" class="ivcw-loading" role="status" aria-live="polite">
      <span class="ivcw-spinner" aria-hidden="true"></span> Preparando tu selección…
    </div>

    <section id="ivcwEmpty" class="ivcw-empty" hidden aria-labelledby="ivcwEmptyTitle">
      <div class="ivcw-empty-mark" aria-hidden="true">
        <svg viewBox="0 0 90 90" fill="none"><path d="M72 29c0 18-27 37-27 37S18 47 18 29a16 16 0 0 1 27-12 16 16 0 0 1 27 12Z" stroke="currentColor" stroke-width="2.6" stroke-linejoin="round"/><path d="M30 29h30" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/></svg>
      </div>
      <span class="ivcw-eyebrow">UN MUNDO DE POSIBILIDADES</span>
      <h3 id="ivcwEmptyTitle">Tu lista espera su<br><em>primer favorito.</em></h3>
      <p>Aún no has guardado productos. Explora nuestros armazones y marca con el corazón los modelos que te encanten.</p>
      <a class="ivcw-btn-primary" href="<?php echo BASE_URL; ?>principal/shop">Descubrir armazones <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></a>
    </section>

    <div id="ivcwProducts" class="ivcw-products" hidden>
      <!-- Mantener este ID y tbody: listaDeseo.js inyecta las filas y asigna los listeners. -->
      <table class="table" id="tableListaDeseo">
        <thead><tr><th scope="col">Imagen</th><th scope="col">Producto</th><th scope="col">Precio</th><th scope="col">Cantidad</th><th scope="col">Acciones</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
    <div id="ivcwError" class="ivcw-error" hidden role="alert">No pudimos cargar tus favoritos. <a href="">Volver a intentar</a>.</div>

    <aside class="ivcw-note" aria-label="Información sobre favoritos">
      <div class="ivcw-note-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s-9-5.8-9-12a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 6.2-9 12-9 12Z"/></svg></div>
      <div><strong>Una selección que habla de ti.</strong><p>Reúne tus favoritos y compara sus detalles antes de elegir. Tus artículos guardados se mantienen en este navegador.</p></div>
      <a href="<?php echo BASE_URL; ?>principal/shop">Ver colección <span aria-hidden="true">↗</span></a>
    </aside>
  </section>
</main>
<?php include_once 'Views/template/footer.php'; ?>
<!-- El script funcional original permanece sin modificaciones. -->
<script src="<?php echo BASE_URL; ?>assets/js/modulos/listaDeseo.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/modulos/ivc-deseos-v8.js?v=8.0.0"></script>
