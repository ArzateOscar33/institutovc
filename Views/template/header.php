<?php
/* Cabecera pública. No se usa en el panel administrativo. */
$ivcPerfil = ($data['perfil'] ?? 'no') === 'no';
$ivcPageTitle = htmlspecialchars((string)($data['title'] ?? 'Inicio'), ENT_QUOTES, 'UTF-8');
$ivcIcon = static function (string $name, int $size = 21): string {
    $paths = [
        'search' => '<circle cx="10.8" cy="10.8" r="6.4"/><path d="m16 16 5 5"/>',
        'bag' => '<path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'heart' => '<path d="M20.8 8.6c0 4.2-8.8 10-8.8 10s-8.8-5.8-8.8-10a4.7 4.7 0 0 1 8.8-2.1 4.7 4.7 0 0 1 8.8 2.1Z"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 21v-1.2a7 7 0 0 1 14 0V21"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'glasses' => '<path d="M2 9.5 3 16a4 4 0 0 0 7.9.3l.2-.8a1 1 0 0 1 1.8 0l.2.8A4 4 0 0 0 21 16l1-6.5M2 12l5-1h3.5M22 12l-5-1h-3.5"/>',
        'location' => '<path d="M12 21s7-5.6 7-12a7 7 0 1 0-14 0c0 6.4 7 12 7 12Z"/><circle cx="12" cy="9" r="2.4"/>',
    ];
    $shape = $paths[$name] ?? $paths['arrow'];
    return '<svg class="ivc-svg-icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $shape . '</svg>';
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#153B5B">
    <title><?php echo htmlspecialchars((string) TITLE, ENT_QUOTES, 'UTF-8') . ' — ' . $ivcPageTitle; ?></title>
    <link rel="icon" sizes="32x32" href="<?php echo BASE_URL; ?>assets/favicon/favicon-32x32.png">
    <link rel="icon" sizes="192x192" href="<?php echo BASE_URL; ?>assets/favicon/android-icon-192x192.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/templatemo.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/custom.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/slick.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/slick-theme.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- IMPORTANTE: se carga DESPUÉS de la plantilla Zay para sustituir el verde original. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-editorial.css?v=3.0.0">
    <!-- IVC_CART_DRAWER_V9_STYLES -->
   <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-cart-drawer-v9.css?v=9.2.0">
   
</head>
<body class="ivc-public">
    <div class="ivc-topbar">
        <div class="container ivc-topbar-content">
            <span>INSTITUTO VISUAL CALIFORNIA <span aria-hidden="true">·</span> TU MIRADA, NUESTRA PRIORIDAD</span>
            <a href="tel:+526644913156">Atención: (664) 491-3156 <?php echo $ivcIcon('arrow', 14); ?></a>
        </div>
    </div>
    <header class="ivc-site-header">
        <nav class="navbar navbar-expand-lg navbar-light ivc-navbar" aria-label="Navegación principal">
            <div class="container ivc-nav-container">
                <a class="navbar-brand ivc-brand" href="<?php echo BASE_URL; ?>" aria-label="Instituto Visual California - Inicio">
                    <span class="ivc-brand-mark"><?php echo $ivcIcon('glasses', 30); ?></span>
                    <span class="ivc-brand-text"><strong>INSTITUTO VISUAL</strong><small>CALIFORNIA</small></span>
                </a>
                <button class="navbar-toggler ivc-nav-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#templatemo_main_nav" aria-controls="templatemo_main_nav" aria-expanded="false" aria-label="Abrir menú">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse ivc-nav-collapse" id="templatemo_main_nav">
                    <ul class="navbar-nav ivc-nav-links">
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>">Inicio</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>principal/shop">Colección</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>principal/about">Nosotros</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>principal/agendar">Citas</a></li>
                    </ul>
                    <div class="ivc-nav-actions">
                        <button class="ivc-icon-btn" type="button" data-bs-toggle="modal" data-bs-target="#templatemo_search" aria-label="Buscar productos" title="Buscar productos">
                            <?php echo $ivcIcon('search'); ?>
                        </button>
                        <a class="ivc-icon-btn position-relative <?php echo $ivcPerfil ? '' : 'd-none'; ?>" href="#" id="verCarrito" aria-label="Ver carrito" title="Carrito" data-bs-toggle="offcanvas" data-bs-target="#myModal">
                            <?php echo $ivcIcon('bag'); ?>
                            <span class="ivc-counter" id="btnCantidadCarrito">0</span>
                        </a>
                        <a class="ivc-icon-btn position-relative <?php echo $ivcPerfil ? '' : 'd-none'; ?>" href="<?php echo BASE_URL; ?>principal/deseo" aria-label="Lista de deseos" title="Favoritos">
                            <?php echo $ivcIcon('heart'); ?>
                            <span class="ivc-counter" id="btnCantidadDeseo">0</span>
                        </a>
                        <?php if (!empty($_SESSION['correoCliente'])): ?>
                            <a class="ivc-icon-btn ivc-account" href="<?php echo BASE_URL; ?>clientes" aria-label="Mi cuenta" title="Mi cuenta"><?php echo $ivcIcon('user'); ?></a>
                        <?php else: ?>
                            <button class="ivc-icon-btn ivc-account" type="button" data-bs-toggle="modal" data-bs-target="#modalLogin" aria-label="Iniciar sesión" title="Iniciar sesión"><?php echo $ivcIcon('user'); ?></button>
                        <?php endif; ?>
                    </div>
                    <a class="ivc-header-cta" href="<?php echo BASE_URL; ?>principal/agendar">Agendar cita <?php echo $ivcIcon('arrow', 17); ?></a>
                </div>
            </div>
        </nav>
    </header>
    <!-- Conserva #templatemo_search, #inputModalSearch y #resultBusqueda: utilizados por assets/js/login.js. -->
    <div class="modal fade ivc-search-modal" id="templatemo_search" tabindex="-1" aria-labelledby="ivcSearchLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h2 class="modal-title" id="ivcSearchLabel">¿Qué armazones buscas?</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                <div class="modal-body">
                    <label for="inputModalSearch" class="form-label">Busca por nombre o modelo</label>
                    <div class="input-group ivc-search-input"><input type="search" class="form-control" id="inputModalSearch" name="q" placeholder="Ej. armazones, lentes, colección..." autocomplete="off"><span class="input-group-text"><?php echo $ivcIcon('search'); ?></span></div>
                    <div class="row mt-4" id="resultBusqueda" aria-live="polite"></div>
                </div>
            </div>
        </div>
    </div>
