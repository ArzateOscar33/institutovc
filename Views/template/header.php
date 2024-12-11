
<!DOCTYPE html>
<html lang="en">

<head>
    <title ><?php echo TITLE .' - ' . $data['title']; ?></title>
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL;?>assets/favicon/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="<?php echo BASE_URL;?>assets/favicon/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL;?>assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="<?php echo BASE_URL;?>assets/favicon/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL;?>assets/favicon/favicon-16x16.png">

<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">






    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="apple-touch-icon" href="<?php echo BASE_URL . 'assets/img/apple-icon.png';?>">

    <link rel="stylesheet" href="<?php echo BASE_URL .'assets/css/bootstrap.min.css' ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL .'assets/css/templatemo.css' ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL .'assets/css/custom.css' ?>">

    <!-- Load fonts style after rendering the layout styles -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;200;300;400;500;700;900&display=swap">
    <link rel="stylesheet" href="<?php echo BASE_URL .'assets/css/fontawesome.min.css' ?>">
   <!-- Slick -->
   <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL . 'assets/css/slick.min.css'; ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL . 'assets/css/slick-theme.css'; ?>">
   
   
    
</head>

<body>


    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light shadow">
        <div class="container d-flex justify-content-between align-items-center">

            <a class="navbar-brand text-success logo h1 align-self-center" href="<?php echo BASE_URL;?>">
               <?php echo TITLE;?>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#templatemo_main_nav" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="align-self-center collapse navbar-collapse flex-fill  d-lg-flex justify-content-lg-between" id="templatemo_main_nav">
                <div class="flex-fill">
                    <ul class="nav navbar-nav d-flex justify-content-between mx-lg-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL  ?>">Inicio</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL . 'principal/agendar' ?>"> Agenda tu Cita</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL . 'principal/shop' ?>">Tienda</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL . 'principal/about' ?>">Sobre Nosotros</a>
                        </li>
                    </ul>
                </div>
                <div class="navbar align-self-center d-flex">
                    <div class="d-lg-none flex-sm-fill mt-3 mb-4 col-7 col-sm-auto pr-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="inputMobileSearch" placeholder="Search ...">
                            <div class="input-group-text">
                                <i class="fa fa-fw fa-search"></i>
                            </div>
                        </div>
                    </div>
                    <a class="nav-icon d-none d-lg-inline" href="#" data-bs-toggle="modal" data-bs-target="#templatemo_search">
                        <i class="fas fa-fw fa-search text-dark mr-2"></i>
                    </a>
                    
                    <a class="nav-icon position-relative text-decoration-none <?php echo ($data['perfil']=='no') ? '' : 'd-none'; ?>" href="#" id="verCarrito">
                        <i class="fas fa-fw fa-cart-arrow-down text-dark mr-1"></i>
                        <span class="position-absolute top-0 left-100 translate-middle badge rounded-pill bg-light text-dark" id="btnCantidadCarrito">0</span>
                    </a>
                    <a class="nav-icon position-relative text-decoration-none <?php echo ($data['perfil']=='no') ? '' : 'd-none'; ?>" href="<?php echo BASE_URL . 'principal/deseo';?>">
                        <i class="fas fa-fw fa-heart text-dark mr-1"></i>
                        <span id="btnCantidadDeseo" class="position-absolute top-0 left-100 translate-middle badge rounded-pill bg-light text-dark">0</span>
                    </a> 
                    <?php if (!empty($_SESSION['correoCliente'])) { ?>
                    <a class="nav-icon position-relative text-decoration-none" href="<?php echo BASE_URL.'clientes' ?>"  >
                      <img class="img-thumbnail" src="<?php echo BASE_URL .'assets/img/clientes/cliente.png'?>" alt="-logo cliente-" width="35">
                      </a>
                      <?php } else{ ?>
                    <a class="nav-icon position-relative text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#modalLogin">
                      <i class="fas fa-fw fa-user text-dark mr-3"></i>
                        <?php }?>
                        </a>
                </div>
            </div>

        </div>
    </nav>
    <!-- Close Header -->

    <!-- Modal -->
    <div class="modal fade bg-white" id="templatemo_search" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="w-100 pt-1 mb-5 text-right">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-content modal-body border-0 p-0">
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="inputModalSearch" name="q" placeholder="Buscar Producto ...">
                    <button type="submit" class="input-group-text bg-success text-light">
                        <i class="fa fa-fw fa-search text-white"></i>
                    </button>
                </div>
                
            <div class="row" id="resultBusqueda">

</div>
            </div>
        </div>
    </div>
</body>
</html>