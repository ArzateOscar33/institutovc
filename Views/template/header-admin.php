<!doctype html>
<html lang="es">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--favicon-->
    <link href="<?php echo BASE_URL; ?>assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
    <link href="<?php echo BASE_URL; ?>assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
    <!-- loader-->
    <link href="<?php echo BASE_URL; ?>assets/css/pace.min.css" rel="stylesheet" />
    <script src="<?php echo BASE_URL; ?>assets/js/pace.min.js"></script>
    <!-- Bootstrap CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/bootstrap-extended.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/app.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/icons.css" rel="stylesheet">
    <!-- Theme Style CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dark-theme.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/semi-dark.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header-colors.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL . 'assets/DataTables/datatables.min.css'; ?>">
    <link href="<?php echo BASE_URL; ?>assets/css/dropzone.css" rel="stylesheet" type="text/css" />
    <!-- Identidad visual IVC: panel administrativo -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/ivc-admin-e1.css?v=4911">
    <title><?php echo TITLE . ' - ' . $data['title']; ?></title>
</head>

<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        <div class="sidebar-wrapper" data-simplebar="true">
            <div class="sidebar-header">
                <div>
                    <img src="<?php echo BASE_URL; ?>assets/img/logo.jpg" class="logo-icon rounded-circle" width="50" height="50" alt="logo icon">
                </div>
                <div>
                    <h6 class="label"><?php echo ' ' .TITLE; ?></h6>
                </div>
                <div class="toggle-icon ms-auto"><i class='bx bx-arrow-to-left'></i>
                </div>
            </div>
            <!--navigation-->
            <?php
            $ivcRol = RolesAcceso::rol();
            $ivcAdmin = RolesAcceso::permiso('dashboard');
            $ivcProductos = RolesAcceso::permiso('productos.leer');
            $ivcCategorias = RolesAcceso::permiso('categorias.leer');
            $ivcMicas = RolesAcceso::permiso('micas.leer');
            $ivcCatalogoPermitido = $ivcProductos || $ivcCategorias || $ivcMicas;
            $ivcRecetas = RolesAcceso::permiso('recetas.leer');
            $ivcPedidos = RolesAcceso::permiso('pedidos.listar');
            $ivcPos = RolesAcceso::permiso('pos.acceder');
            $ivcCitas = RolesAcceso::permiso('citas.leer');
            $ivcRoles = RolesAcceso::permiso('roles.gestionar');
            $ivcUsuarios = RolesAcceso::permiso('usuarios.administrar');
            $ivcRuta = strtolower(trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/'));
            $ivcSeccion = basename($ivcRuta);
            $ivcInicio = ($ivcSeccion === 'home' && strpos($ivcRuta, 'admin/') !== false);
            $ivcCatalogo = in_array($ivcSeccion, ['productos', 'categorias', 'micas'], true);
            $ivcOperacion = in_array($ivcSeccion, ['pedidos', 'recetasadmin', 'citas'], true);
            $ivcGestion = in_array($ivcSeccion,['usuarios','roles'],true);
            ?>
            <div class="ivc-sidebar-label">PANEL DE CONTROL</div>
            <ul class="metismenu ivc-menu-e1" id="menu">
                <?php if ($ivcAdmin): ?>
                <li class="<?php echo $ivcInicio ? 'mm-active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>admin/home" class="<?php echo $ivcInicio ? 'ivc-active-link' : ''; ?>">
                        <div class="parent-icon"><i class="bx bx-grid-alt"></i></div>
                        <div class="menu-title">Dashboard</div>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($ivcPos): ?>
                <li class="<?php echo $ivcSeccion === 'pos' ? 'mm-active' : ''; ?>">
                    <a href="<?php echo BASE_URL; ?>pos" class="<?php echo $ivcSeccion === 'pos' ? 'ivc-active-link' : ''; ?>">
                        <div class="parent-icon"><i class="bx bx-store-alt"></i></div>
                        <div class="menu-title">Punto de Venta</div>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($ivcPedidos || $ivcRecetas || $ivcCitas): ?>
                <li class="<?php echo $ivcOperacion ? 'mm-active' : ''; ?>">
                    <a href="javascript:;" class="has-arrow" aria-label="Expandir Operación">
                        <div class="parent-icon"><i class="bx bx-briefcase-alt-2"></i></div>
                        <div class="menu-title">Operación</div>
                    </a>
                    <ul class="<?php echo $ivcOperacion ? 'mm-show' : ''; ?>">
                        <?php if ($ivcPedidos): ?><li><a href="<?php echo BASE_URL; ?>pedidos" class="<?php echo $ivcSeccion === 'pedidos' ? 'ivc-active-link' : ''; ?>"><i class="bx bx-package"></i> Pedidos</a></li><?php endif; ?>
                        <?php if ($ivcRecetas): ?><li><a href="<?php echo BASE_URL; ?>recetasAdmin" class="<?php echo $ivcSeccion === 'recetasadmin' ? 'ivc-active-link' : ''; ?>"><i class="bx bx-file"></i> Recetas</a></li><?php endif; ?>
                        <?php if ($ivcCitas): ?><li><a href="<?php echo BASE_URL; ?>citas" class="<?php echo $ivcSeccion === 'citas' ? 'ivc-active-link' : ''; ?>"><i class="bx bx-calendar-check"></i> Citas</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if ($ivcCatalogoPermitido): ?>
                <li class="<?php echo $ivcCatalogo ? 'mm-active' : ''; ?>">
                    <a href="javascript:;" class="has-arrow" aria-label="Expandir Catálogo">
                        <div class="parent-icon"><i class="bx bx-store"></i></div>
                        <div class="menu-title">Catálogo</div>
                    </a>
                    <ul class="<?php echo $ivcCatalogo ? 'mm-show' : ''; ?>">
                        <?php if ($ivcProductos): ?><li><a href="<?php echo BASE_URL; ?>productos" class="<?php echo $ivcSeccion === 'productos' ? 'ivc-active-link' : ''; ?>"><i class="bx bx-glasses"></i> Productos</a></li><?php endif; ?>
                        <?php if ($ivcCategorias): ?><li><a href="<?php echo BASE_URL; ?>categorias" class="<?php echo $ivcSeccion === 'categorias' ? 'ivc-active-link' : ''; ?>"><i class="bx bx-category"></i> Categorías</a></li><?php endif; ?>
                        <?php if ($ivcMicas): ?><li><a href="<?php echo BASE_URL; ?>micas" class="<?php echo $ivcSeccion === 'micas' ? 'ivc-active-link' : ''; ?>"><i class="bx bx-layer"></i> Micas y precios</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if ($ivcUsuarios || $ivcRoles): ?>
                <li class="<?php echo $ivcGestion ? 'mm-active' : ''; ?>">
                    <a href="javascript:;" class="has-arrow" aria-label="Expandir Administración">
                        <div class="parent-icon"><i class="bx bx-cog"></i></div>
                        <div class="menu-title">Administración</div>
                    </a>
                    <ul class="<?php echo $ivcGestion ? 'mm-show' : ''; ?>">
                        <?php if ($ivcUsuarios): ?><li><a href="<?php echo BASE_URL; ?>usuarios" class="<?php echo $ivcGestion ? 'ivc-active-link' : ''; ?>"><i class="bx bx-user"></i> Usuarios</a></li><?php endif; ?>
                        <?php if ($ivcRoles): ?><li><a href="<?php echo BASE_URL; ?>roles" class="<?php echo $ivcSeccion === 'roles' ? 'ivc-active-link' : ''; ?>"><i class="bx bx-shield-quarter"></i> Roles y permisos</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <!--end navigation-->
        </div>
        <!--end sidebar wrapper -->
        <!--start header -->
        <header>
            <div class="topbar d-flex align-items-center">
                <nav class="navbar navbar-expand">
                    <div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
                    </div>
                    <div class="search-bar flex-grow-1 ivc-topbar-caption"><span class="ivc-topbar-dot"></span> Panel administrativo</div>
                    <div class="user-box dropdown">
                        <a class="d-flex align-items-center nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo BASE_URL; ?>assets/img/logo.jpg" class="user-img" alt="user avatar">
                            <div class="user-info ps-3">
                                <p class="user-name mb-0"><?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="designattion mb-0"><?php echo htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                <small class="badge bg-primary text-white"><?php echo htmlspecialchars($ivcRol??'',ENT_QUOTES,'UTF-8'); ?></small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            
                            <li>
                                <div class="dropdown-divider mb-0"></div>
                            </li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL . 'admin/salir'; ?>"><i class='bx bx-log-out-circle'></i><span>Cerrar sesión</span></a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </header>
        <!--end header -->
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">