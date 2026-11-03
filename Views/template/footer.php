<?php /* Pie público y modales originales: sus IDs son parte de la integración con carrito.js/login.js. */ ?>
<!-- IVC_CART_DRAWER_V9: Offcanvas lateral. Mantener los IDs para carrito.js/login.js. -->
<div class="offcanvas offcanvas-end ivc-cart-drawer" tabindex="-1" id="myModal" aria-labelledby="ivcCartLabel" aria-describedby="ivcCartDescription" data-bs-scroll="false" data-bs-backdrop="true">
    <div class="offcanvas-header ivc-cart-drawer__header">
        <div class="ivc-cart-drawer__heading">
            <span class="ivc-cart-drawer__eyebrow">TU SELECCIÓN</span>
            <div class="ivc-cart-drawer__title-line">
                <h2 class="offcanvas-title" id="ivcCartLabel">Tu carrito</h2>
                <span class="ivc-cart-drawer__count" id="ivcCartItemCount" aria-label="Productos en carrito">0</span>
            </div>
            <p id="ivcCartDescription">Tus armazones favoritos, listos para continuar.</p>
        </div>
        <button type="button" class="ivc-cart-drawer__close" data-bs-dismiss="offcanvas" aria-label="Cerrar carrito">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5 5 19"/></svg>
        </button>
    </div>

    <div class="offcanvas-body ivc-cart-drawer__body">
        <div class="ivc-cart-drawer__support"><span class="ivc-cart-drawer__support-icon" aria-hidden="true">✦</span> Una buena elección empieza con una mirada.</div>
        <div id="ivcCartEmpty" class="ivc-cart-drawer__empty" hidden>
            <div class="ivc-cart-drawer__empty-illustration" aria-hidden="true">
                <svg viewBox="0 0 220 165" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="110" cy="78" r="70" fill="#EAF6FC"/>
                    <circle cx="155" cy="25" r="6" fill="#B8E1F5"/><circle cx="44" cy="118" r="4" fill="#B8E1F5"/>
                    <path d="M41 64h138l-8 79H49L41 64Z" fill="white" stroke="#287CB3" stroke-width="4" stroke-linejoin="round"/>
                    <path d="M79 68V49a31 31 0 0 1 62 0v19" stroke="#287CB3" stroke-width="4" stroke-linecap="round"/>
                    <path d="M63 97c9-2 22-2 31 0 7 2 8 16 3 22-4 5-14 7-21 3-5-3-7-11-8-17M157 97c-9-2-22-2-31 0-7 2-8 16-3 22 4 5 14 7 21 3 5-3 7-11 8-17M96 100c8-5 20-5 28 0" stroke="#153B5B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M169 65v-9m-5 5h10" stroke="#8FC9EA" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>
            <h3>Tu carrito te espera</h3>
            <p>Aún no has agregado armazones. Explora nuestra colección y encuentra tu favorito.</p>
            <a class="ivc-cart-drawer__explore" href="<?php echo BASE_URL; ?>principal/shop">Explorar colección <span aria-hidden="true">→</span></a>
        </div>
        <div class="ivc-cart-drawer__list" id="ivcCartList">
            <table class="ivc-cart-drawer__table" id="tableListaCarrito">
                <thead class="visually-hidden"><tr><th>Imagen</th><th>Producto</th><th>Precio unitario</th><th>Cantidad</th><th>Subtotal</th><th>Eliminar</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
        <span id="ivcCartLive" class="visually-hidden" aria-live="polite" aria-atomic="true"></span>
    </div>
    <div class="ivc-cart-drawer__footer" id="ivcCartFooter">
        <div class="ivc-cart-drawer__totals">
            <div><span class="ivc-cart-drawer__total-caption">Subtotal estimado</span><small>Productos seleccionados</small></div>
            <div class="ivc-cart-drawer__money"><strong id="totalGeneral">0.00</strong><small>MXN</small></div>
        </div>
        <?php if (!empty($_SESSION['correoCliente'])): ?>
            <a class="ivc-cart-drawer__checkout" href="<?php echo BASE_URL; ?>clientes">Continuar con mi pedido <span aria-hidden="true">→</span></a>
        <?php else: ?>
            <button class="ivc-cart-drawer__checkout" id="ivcCartLogin" type="button">Iniciar sesión para continuar <span aria-hidden="true">→</span></button>
        <?php endif; ?>
        <button type="button" class="ivc-cart-drawer__continue" data-bs-dismiss="offcanvas">Seguir explorando la tienda</button>
    </div>
</div>
<!-- FIN IVC_CART_DRAWER_V9 -->

<div class="modal fade ivc-login-modal" id="modalLogin" tabindex="-1" aria-labelledby="titleLogin" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h2 class="modal-title" id="titleLogin">Bienvenido de nuevo</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body">
        <div class="ivc-login-symbol" aria-hidden="true">IVC</div>
        <form action="#" method="post" onsubmit="return false;">
          <div id="frmLogin">
            <label for="correoLogin" class="form-label">Correo electrónico</label>
            <input class="form-control mb-3" type="email" id="correoLogin" name="correoLogin" placeholder="correo@ejemplo.com" autocomplete="email">
            <label for="claveLogin" class="form-label">Contraseña</label>
            <input class="form-control mb-3" type="password" id="claveLogin" name="claveLogin" placeholder="Tu contraseña" autocomplete="current-password">
            <p class="small"><a href="<?php echo htmlspecialchars(BASE_URL,ENT_QUOTES,'UTF-8'); ?>activacion">¿Compraste en la óptica? Activa tu cuenta web</a></p>
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3"><a href="#" id="btnRegister">Crear una cuenta</a><button type="button" class="btn ivc-btn-primary" id="login">Iniciar sesión</button></div>
          </div>
          <div class="d-none" id="frmRegister">
            <label for="apellidoPaternoRegistro" class="form-label">Apellido paterno</label><input class="form-control mb-3" type="text" id="apellidoPaternoRegistro" autocomplete="family-name">
            <label for="apellidoMaternoRegistro" class="form-label">Apellido materno</label><input class="form-control mb-3" type="text" id="apellidoMaternoRegistro">
            <label for="nombreRegistro" class="form-label">Nombre</label><input class="form-control mb-3" type="text" id="nombreRegistro" autocomplete="given-name">
            <label for="correoRegistro" class="form-label">Correo electrónico</label><input class="form-control mb-3" type="email" id="correoRegistro" autocomplete="email">
            <label for="claveRegistro" class="form-label">Contraseña</label><input class="form-control mb-3" type="password" id="claveRegistro" autocomplete="new-password">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap"><a href="#" id="btnLogin">Ya tengo una cuenta</a><button class="btn ivc-btn-primary" type="button" id="registrarse">Registrarme</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<footer class="ivc-footer" id="tempaltemo_footer">
  <div class="container">
    <div class="ivc-footer-top">
      <div class="ivc-footer-intro">
        <span class="ivc-footer-kicker">INSTITUTO VISUAL CALIFORNIA</span>
        <h2>Una mejor forma<br>de <em>ver la vida.</em></h2>
        <p>Atención visual cercana y una selección de armazones para acompañar cada mirada.</p>
        <a href="<?php echo BASE_URL; ?>principal/agendar" class="ivc-footer-cta">Agenda tu cita <span aria-hidden="true">↗</span></a>
      </div>
      <div class="ivc-footer-column">
        <h3>Explora</h3>
        <a href="<?php echo BASE_URL; ?>">Inicio</a>
        <a href="<?php echo BASE_URL; ?>principal/shop">Colección de armazones</a>
        <a href="<?php echo BASE_URL; ?>principal/agendar">Agenda tu cita</a>
        <a href="<?php echo BASE_URL; ?>principal/about">Sobre nosotros</a>
      </div>
      <div class="ivc-footer-column ivc-footer-contact">
        <h3>Visítanos</h3>
        <p>Blvd. Alberto Limón Padilla 1-8-8, La Pechuga, Fovissste, 22457 Tijuana, B.C.</p>
        <a href="tel:+526644913156">(664) 491-3156</a>
        <a href="https://www.google.com/maps/search/?api=1&amp;query=Instituto+Visual+California+Tijuana" target="_blank" rel="noopener noreferrer">Cómo llegar <span aria-hidden="true">↗</span></a>
      </div>
    </div>
    <div class="ivc-footer-bottom">
      <p>© <?php echo date('Y'); ?> Instituto Visual California. Todos los derechos reservados.</p>
      <span>Diseño y desarrollo: ArzaTech</span>
    </div>
  </div>
</footer>
<!-- Se mantienen los recursos y variables JS del sitio original; no se altera el flujo de PayPal. -->
<script src="<?php echo BASE_URL; ?>assets/js/jquery-1.11.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/jquery-migrate-1.2.1.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/templatemo.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/custom.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>const base_url = <?php echo json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="<?php echo BASE_URL; ?>assets/js/carrito.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/login.js"></script>
<!-- IVC_CART_DRAWER_V9_SCRIPT -->
<script src="<?php echo BASE_URL; ?>assets/js/ivc-cart-drawer-v9.js?v=9.1.0"></script>
<script defer src="https://cdn.jsdelivr.net/npm/gsap@3.14.2/dist/gsap.min.js"></script>
<script defer src="<?php echo BASE_URL; ?>assets/js/ivc-animations.js?v=3.0.0"></script>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-carrito-configurado-v43.css?v=4.3.0">
<script src="<?php echo BASE_URL; ?>assets/js/ivc-carrito-configurado-v43.js?v=4.9.1c"></script>
</body>
</html>
