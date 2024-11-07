<?php include_once 'Views/template-principal/header.php'?>


    <!-- Open Content -->
    <section class="bg-light">
        <div class="container pb-5">
            <div class="row">
                <div class="col-lg-5 mt-5">
                    <div class="card mb-3">
                        <img class="card-img img-fluid" src="<?php  echo $data['producto']['imagen']; ?> " alt="Card image cap" id="product-detail">
                    </div>
                </div>
                <!-- col end -->
                <div class="col-lg-7 mt-5">
                    <div class="card">
                        <div class="card-body">
                            <h1 class="h2"><?php echo $data['producto']['nombre']?></h1>
                            <p class="h3 py-2"><?php echo MONEDA .' '. $data['producto']['precio']?></p>
                            <p class="py-2">
                            </p>
                            <ul class="list-inline">
                                <li class="list-inline-item">
                                    <h6>Categoria</h6>
                                </li>
                                <li class="list-inline-item">
                                    <p class="text-muted"><strong><?php echo $data['producto']['categoria']?></strong></p>
                                </li>
                            </ul>

                            <h6>Description:</h6>
                            <p><?php echo $data['producto']['descripcion']?></p>
                       
                       

                            <form action="" method="GET">
                                <input type="hidden" id="idProducto"  value="<?php echo $data['producto']['id']?>">
                                <div class="row">

                                    <div class="col-auto">
                                        <ul class="list-inline pb-3">
                                            <li class="list-inline-item text-right">
                                                Cantidad
                                                <input type="hidden"  id="product-quanity" value="1">
                                            </li>
                                            <li class="list-inline-item"><span class="btn btn-success" id="btn-minus">-</span></li>
                                            <li class="list-inline-item"><span class="badge bg-secondary" id="var-value">1</span></li>
                                            <li class="list-inline-item"><span class="btn btn-success" id="btn-plus">+</span></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="row pb-3">
                                    <div class="col d-grid">
                                        <button type="submit" class="btn btn-success btn-lg" name="submit" value="buy">Comprar</button>
                                    </div>
                                    <div class="col d-grid">
                                        <button type="button" class="btn btn-success btn-lg" id="btnAddCart" >Agregar al Carrito</button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Close Content -->

    <!-- Start Article -->
    <section class="py-5">
        <div class="container">
            <div class="row text-left p-2 pb-3">
                <h4>Productos Relacionados</h4>
            </div>

            <!--Start Carousel Wrapper-->
            <div id="carousel-related-product">
            <?php foreach($data['relacionados'] as $relacion){ ?>
                <div class="p-2 pb-3">
                    <div class="product-wap card rounded-0">
                        <div class="card rounded-0">
                            <img class="card-img rounded-0 img-fluid" src="<?php echo $relacion['imagen']; ?> ">
                            <div class="card-img-overlay rounded-0 product-overlay d-flex align-items-center justify-content-center">
                                <ul class="list-unstyled">
                                    <li><a class="btn btn-success text-white btnAddDeseo" href="#" prod="<?php echo $relacion['id']; ?>"><i class="far fa-heart"></i></a></li>
                                    <li><a class="btn btn-success text-white mt-2" href="<?php echo BASE_URL .'principal/detail/'.$relacion['id']?>"><i class="far fa-eye"></i></a></li>
                                    <li><a class="btn btn-success text-white mt-2 btnAddCarrito" href="#" prod="<?php echo $relacion['id']; ?>" ><i class="fas fa-cart-plus"></i></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <a href="<?php echo BASE_URL .'principal/detail/'.$relacion['id']?>" class="h3 text-decoration-none"><?php echo $relacion['nombre']; ?> </a>
                            <ul class="w-100 list-unstyled d-flex justify-content-between mb-0">
                                <li>M/L/X/XL</li>
                                <li class="pt-2">
                                    <span class="product-color-dot color-dot-red float-left rounded-circle ml-1"></span>
                                    <span class="product-color-dot color-dot-blue float-left rounded-circle ml-1"></span>
                                    <span class="product-color-dot color-dot-black float-left rounded-circle ml-1"></span>
                                    <span class="product-color-dot color-dot-light float-left rounded-circle ml-1"></span>
                                    <span class="product-color-dot color-dot-green float-left rounded-circle ml-1"></span>
                                </li>
                            </ul>
                            <ul class="list-unstyled d-flex justify-content-center mb-1">
                                <li>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-warning fa fa-star"></i>
                                    <i class="text-muted fa fa-star"></i>
                                </li>
                            </ul>
                            <p class="text-center mb-0"><?php echo MONEDA .' '. $relacion['precio']; ?> </p>
                        </div>
                    </div>
                </div>
                <?php } ?>
               

                

            </div>


        </div>
    </section>
    <!-- End Article -->


    <!-- Start Footer -->
    <?php include_once 'Views/template-principal/footer.php'?>
    <!-- End Footer -->

    <!-- Start Script -->
    <script src="assets/js/jquery-1.11.0.min.js"></script>
    <script src="assets/js/jquery-migrate-1.2.1.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/templatemo.js"></script>
    <script src="assets/js/custom.js"></script>
    <!-- End Script -->

    <!-- Start Slider Script -->
    <script src="<?php echo BASE_URL;?>assets/js/slick.min.js"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL . 'assets/css/slick.min.css'; ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL . 'assets/css/slick-theme.css'; ?>">
    <script>
        $('#carousel-related-product').slick({
            infinite: true,
            arrows: false,
            slidesToShow: 4,
            slidesToScroll: 3,
            dots: true,
            responsive: [{
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: 3,
                        slidesToScroll: 3
                    }
                },
                {
                    breakpoint: 600,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 3
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 3
                    }
                }
            ]
        });
    </script>
    <!-- End Slider Script -->

</body>

</html>